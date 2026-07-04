<?php

namespace App\Models;

use App\Domain\Task\TaskRules;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'priority',
        'due_date',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed' => 'boolean',
    ];

    protected $appends = [
        'is_late',
    ];

    public function getIsLateAttribute(): bool
    {
        return TaskRules::isLate($this->due_date, (bool) $this->completed, now());
    }
}
