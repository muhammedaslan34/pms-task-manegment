<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Handles one Telegram update (from the webhook or from telegram:poll):
 *  - callback queries "task:{id}:{status}" change a task's status,
 *    "task:{id}:show" sends the task card;
 *  - commands /start, /tasks, /task {id}, /help.
 *
 * Only the configured owner chat (services.telegram.chat_id) may act on tasks;
 * everything else is ignored, except /start which just tells a chat its id.
 */
class TelegramUpdateHandler
{
    private const OPEN_TASKS_LIMIT = 15;

    public function __construct(
        private readonly TelegramBot $bot,
        private readonly TaskTelegramNotifier $notifier,
    ) {
    }

    public function handle(array $update): void
    {
        if (isset($update['callback_query']) && is_array($update['callback_query'])) {
            $this->handleCallbackQuery($update['callback_query']);
        } elseif (isset($update['message']) && is_array($update['message'])) {
            $this->handleMessage($update['message']);
        }
    }

    private function isOwnerChat(mixed $chatId): bool
    {
        $owner = $this->notifier->chatId();

        return $owner !== null && (is_int($chatId) || is_string($chatId)) && (string) $chatId === $owner;
    }

    private function handleCallbackQuery(array $query): void
    {
        $chatId = $query['message']['chat']['id'] ?? null;
        $fromId = $query['from']['id'] ?? null;

        // In a private chat chat.id == from.id; for a group owner chat, the chat id decides.
        if (! $this->isOwnerChat($chatId) && ! ($chatId === null && $this->isOwnerChat($fromId))) {
            Log::warning('Telegram: ignored callback query from an unauthorized chat.', ['chat_id' => $chatId, 'from_id' => $fromId]);

            return;
        }
        $chatId ??= $fromId;

        $queryId = (string) ($query['id'] ?? '');
        $messageId = $query['message']['message_id'] ?? null;

        if (! preg_match('/^task:(\d+):([a-z_]+)$/', (string) ($query['data'] ?? ''), $matches)) {
            $this->answer($queryId, $this->notifier->translate('Unknown action.'));

            return;
        }

        $task = Task::with('images')->find((int) $matches[1]);
        if (! $task) {
            $this->answer($queryId, $this->notifier->translate('Task #:id no longer exists.', ['id' => $matches[1]]), alert: true);

            return;
        }

        if ($matches[2] === 'show') {
            $this->answer($queryId);
            $this->sendTaskCard($chatId, $task);

            return;
        }

        $status = TaskStatus::tryFrom($matches[2]);
        if (! $status) {
            $this->answer($queryId, $this->notifier->translate('Unknown action.'));

            return;
        }

        $label = $this->notifier->statusLabel($status);

        if ($task->status === $status) {
            $toast = $this->notifier->translate('Task #:id is already :status.', ['id' => $task->getKey(), 'status' => $label]);
        } else {
            $task->setStatus($status);
            $toast = $this->notifier->statusEmoji($status).' '.$this->notifier->translate('Task #:id marked as :status.', ['id' => $task->getKey(), 'status' => $label]);
            Log::info('Telegram: task status changed.', ['task_id' => $task->getKey(), 'status' => $status->value, 'from_id' => $fromId]);
        }

        $this->answer($queryId, $toast);

        if (is_int($messageId)) {
            $isNew = Str::startsWith((string) ($query['message']['text'] ?? ''), '🆕');
            $this->editTaskMessage($chatId, $messageId, $task->refresh(), $isNew, $this->notifier->statusChangedFooter($status));
        }
    }

    private function handleMessage(array $message): void
    {
        $chatId = $message['chat']['id'] ?? null;
        $text = trim((string) ($message['text'] ?? ''));

        if ($chatId === null || ! str_starts_with($text, '/')) {
            return;
        }

        // "/tasks@MyBot 12" → command "tasks", argument "12"
        [$command, $argument] = array_pad(preg_split('/\s+/', $text, 2), 2, '');
        $command = strtolower(Str::before(ltrim($command, '/'), '@'));

        if ($command === 'start') {
            $this->sendStart($chatId);

            return;
        }

        if (! $this->isOwnerChat($chatId)) {
            Log::info('Telegram: ignored command from an unauthorized chat.', ['chat_id' => $chatId, 'command' => $command]);

            return;
        }

        match ($command) {
            'tasks' => $this->sendOpenTasks($chatId),
            'task' => $this->sendTaskById($chatId, $argument),
            default => $this->sendHelp($chatId),
        };
    }

    private function sendStart(int|string $chatId): void
    {
        $text = '👋 '.$this->notifier->escape($this->notifier->translate('Hi! This chat\'s id is'))
            .' <code>'.$this->notifier->escape((string) $chatId).'</code>'."\n\n";

        $text .= $this->isOwnerChat($chatId)
            ? $this->notifier->escape($this->notifier->translate('New tasks will be posted here. Use /tasks to list open tasks.'))
            : $this->notifier->escape($this->notifier->translate('To receive task notifications here, set TELEGRAM_CHAT_ID to this id.'));

        $this->bot->sendMessage($chatId, $text, ['parse_mode' => 'HTML']);
    }

    private function sendHelp(int|string $chatId): void
    {
        $this->bot->sendMessage($chatId, implode("\n", [
            '/tasks — '.$this->notifier->translate('list open tasks'),
            '/task <id> — '.$this->notifier->translate('show one task'),
            '/start — '.$this->notifier->translate('show this chat\'s id'),
        ]));
    }

    private function sendOpenTasks(int|string $chatId): void
    {
        $query = Task::query()->where('status', '!=', TaskStatus::Completed->value);
        $total = (clone $query)->count();

        if ($total === 0) {
            $this->bot->sendMessage($chatId, '🎉 '.$this->notifier->translate('No open tasks.'));

            return;
        }

        $tasks = $query->latest('id')->limit(self::OPEN_TASKS_LIMIT)->get();

        $lines = ['📋 <b>'.$this->notifier->escape($this->notifier->translate('Open tasks')).' ('.$total.')</b>', ''];
        $buttons = [];

        foreach ($tasks as $task) {
            $lines[] = $this->notifier->statusEmoji($task->status).$this->notifier->priorityEmoji($task->priority)
                .' <b>#'.$task->getKey().'</b> '.$this->notifier->escape(Str::limit($task->title, 80));
            $buttons[] = [[
                'text' => '#'.$task->getKey().' · '.Str::limit($task->title, 40),
                'callback_data' => "task:{$task->getKey()}:show",
            ]];
        }

        if ($total > $tasks->count()) {
            $lines[] = '';
            $lines[] = '<i>'.$this->notifier->escape($this->notifier->translate('Showing the latest :count.', ['count' => $tasks->count()])).'</i>';
        }

        $this->bot->sendMessage($chatId, implode("\n", $lines), [
            'parse_mode' => 'HTML',
            'reply_markup' => ['inline_keyboard' => $buttons],
        ]);
    }

    private function sendTaskById(int|string $chatId, string $argument): void
    {
        $task = ctype_digit(ltrim($argument, '#')) ? Task::with('images')->find((int) ltrim($argument, '#')) : null;

        if (! $task) {
            $this->bot->sendMessage($chatId, $this->notifier->translate('Usage: /task <id> (task not found).'));

            return;
        }

        $this->sendTaskCard($chatId, $task);
    }

    private function sendTaskCard(int|string $chatId, Task $task): void
    {
        $this->bot->sendMessage($chatId, $this->notifier->renderTask($task, isNew: false), $this->notifier->messageOptions($task));
    }

    private function editTaskMessage(int|string $chatId, int $messageId, Task $task, bool $isNew, string $footer): void
    {
        $options = $this->notifier->messageOptions($task);

        try {
            $this->bot->editMessageText($chatId, $messageId, $this->notifier->renderTask($task, $isNew, $footer), $options);
        } catch (TelegramException $exception) {
            if ($exception->isNotModified()) {
                return;
            }

            // e.g. message too old to edit: at least refresh the buttons.
            report($exception);

            try {
                $this->bot->editMessageReplyMarkup($chatId, $messageId, $options['reply_markup']);
            } catch (TelegramException $inner) {
                if (! $inner->isNotModified()) {
                    report($inner);
                }
            }
        }
    }

    private function answer(string $queryId, ?string $text = null, bool $alert = false): void
    {
        if ($queryId === '') {
            return;
        }

        try {
            $this->bot->answerCallbackQuery($queryId, $text !== null ? Str::limit($text, 190) : null, $alert);
        } catch (TelegramException $exception) {
            // Expired queries (>15 min, e.g. replayed updates) cannot be answered; the action itself succeeded.
            report($exception);
        }
    }
}
