<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::InProgress => 'In Progress',
            self::Completed => 'Completed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'bg-slate-100 text-slate-700 ring-slate-200',
            self::InProgress => 'bg-blue-100 text-blue-700 ring-blue-200',
            self::Completed => 'bg-emerald-100 text-emerald-700 ring-emerald-200',
        };
    }
}
