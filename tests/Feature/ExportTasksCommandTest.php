<?php

namespace Tests\Feature;

use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ExportTasksCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_prints_the_selected_tasks_as_json_lines_with_screenshot_urls(): void
    {
        [$first, $second, $third, $skipped] = Task::factory()->count(4)->create();
        $first->images()->create(['path' => 'screenshots/a.png']);

        $this->assertSame(0, Artisan::call('tasks:export', ['ids' => [(string) $first->id, $second->id.'-'.$third->id, '999999']]));

        $output = Artisan::output();
        $lines = collect(explode("\n", trim($output)))->filter(fn (string $line) => str_starts_with($line, '{'));
        $rows = $lines->map(fn (string $line) => json_decode($line, true, flags: JSON_THROW_ON_ERROR))->values();

        $this->assertSame([$first->id, $second->id, $third->id], $rows->pluck('id')->all());
        $this->assertNotContains($skipped->id, $rows->pluck('id')->all());
        $this->assertSame($first->title, $rows[0]['title']);
        $this->assertCount(1, $rows[0]['image_urls']);
        $this->assertNotEmpty($rows[0]['image_urls'][0]);
        $this->assertSame([], $rows[1]['image_urls']);
        $this->assertStringContainsString('Not found: 999999', $output);
    }
}
