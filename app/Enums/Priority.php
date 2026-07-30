<?php

namespace App\Enums;

enum Priority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Low',
            self::Medium => 'Medium',
            self::High => 'High',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Low => 'bg-gray-100 text-gray-700 ring-gray-200',
            self::Medium => 'bg-amber-100 text-amber-800 ring-amber-200',
            self::High => 'bg-red-100 text-red-700 ring-red-200',
        };
    }
}
