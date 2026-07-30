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
            self::Pending => __('Pending'),
            self::InProgress => __('In Progress'),
            self::Completed => __('Completed'),
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

    /** Soft card surface classes for dashboard stats (non-interactive). */
    public function cardClasses(): string
    {
        return match ($this) {
            self::Pending => 'border-slate-200/80 from-slate-50 to-white',
            self::InProgress => 'border-blue-200/80 from-blue-50 to-white',
            self::Completed => 'border-emerald-200/80 from-emerald-50 to-white',
        };
    }

    public function iconClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-slate-100 text-slate-600',
            self::InProgress => 'bg-blue-100 text-blue-700',
            self::Completed => 'bg-emerald-100 text-emerald-700',
        };
    }
}
