<?php

namespace App\Models;

use App\Enums\Priority;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'page_link',
        'description',
        'screenshot_path',
        'priority',
        'status',
        'submitted_by',
        'assigned_to',
        'resolution_note',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'priority' => Priority::class,
            'status' => TaskStatus::class,
            'completed_at' => 'datetime',
        ];
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function screenshotUrl(): ?string
    {
        return $this->screenshot_path
            ? asset('storage/' . $this->screenshot_path)
            : null;
    }
}
