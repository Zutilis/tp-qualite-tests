<?php

namespace App\Domain\Task;

use DateTimeInterface;

class TaskRules
{
    public const PRIORITIES = ['low', 'medium', 'high'];

    public static function assertValidTitle(?string $title): void
    {
        if ($title === null || trim($title) === '') {
            throw new InvalidTaskException('Le titre de la tâche est obligatoire.');
        }
    }

    public static function assertValidPriority(string $priority): void
    {
        if (! in_array($priority, self::PRIORITIES, true)) {
            throw new InvalidTaskException(sprintf(
                'La priorité "%s" n\'est pas autorisée (valeurs possibles : %s).',
                $priority,
                implode(', ', self::PRIORITIES)
            ));
        }
    }

    public static function isLate(?DateTimeInterface $dueDate, bool $completed, DateTimeInterface $now): bool
    {
        if ($completed || $dueDate === null) {
            return false;
        }

        return $dueDate->getTimestamp() < $now->getTimestamp();
    }

    /**
     * @param  iterable<array{due_date?: ?DateTimeInterface, completed?: bool}>  $tasks
     */
    public static function countLate(iterable $tasks, DateTimeInterface $now): int
    {
        $count = 0;

        foreach ($tasks as $task) {
            $dueDate = $task['due_date'] ?? null;
            $completed = $task['completed'] ?? false;

            if (self::isLate($dueDate, $completed, $now)) {
                $count++;
            }
        }

        return $count;
    }
}
