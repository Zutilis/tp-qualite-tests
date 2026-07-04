<?php

namespace App\Services;

use App\Domain\Task\TaskRules;
use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;

class TaskService
{
    /**
     * @param  array{title: ?string, priority?: ?string, due_date?: ?string}  $data
     */
    public function create(array $data): Task
    {
        $title = $data['title'] ?? null;
        $priority = $data['priority'] ?? 'medium';

        TaskRules::assertValidTitle($title);
        TaskRules::assertValidPriority($priority);

        return Task::create([
            'title' => trim($title),
            'priority' => $priority,
            'due_date' => $data['due_date'] ?? null,
        ]);
    }

    /**
     * @param  array{title?: ?string, priority?: ?string, due_date?: ?string}  $data
     */
    public function update(Task $task, array $data): Task
    {
        if (array_key_exists('title', $data)) {
            TaskRules::assertValidTitle($data['title']);
            $task->title = trim($data['title']);
        }

        if (array_key_exists('priority', $data)) {
            TaskRules::assertValidPriority($data['priority']);
            $task->priority = $data['priority'];
        }

        if (array_key_exists('due_date', $data)) {
            $task->due_date = $data['due_date'];
        }

        $task->save();

        return $task;
    }

    public function complete(Task $task): Task
    {
        $task->completed = true;
        $task->save();

        return $task;
    }

    public function delete(Task $task): void
    {
        $task->delete();
    }

    public function list(?string $status = null): Collection
    {
        $query = Task::query()->orderBy('due_date')->orderByDesc('id');

        return match ($status) {
            'completed' => $query->where('completed', true)->get(),
            'pending' => $query->where('completed', false)->get(),
            'late' => $query->where('completed', false)->get()->filter(
                fn (Task $task) => $task->is_late
            )->values(),
            default => $query->get(),
        };
    }

    public function countLate(): int
    {
        $tasks = Task::query()->where('completed', false)->get(['due_date', 'completed']);

        return TaskRules::countLate(
            $tasks->map(fn (Task $task) => [
                'due_date' => $task->due_date,
                'completed' => $task->completed,
            ])->all(),
            now()
        );
    }
}
