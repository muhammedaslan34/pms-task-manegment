<?php

namespace App\Models;

use App\Enums\Priority;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'page_link',
        'description',
        'priority',
        'status',
        'submitted_by',
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

    public function images(): HasMany
    {
        return $this->hasMany(TaskImage::class);
    }

    public function hasImages(): bool
    {
        return $this->relationLoaded('images') ? $this->images->isNotEmpty() : $this->images()->exists();
    }
}
