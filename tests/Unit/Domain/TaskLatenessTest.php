<?php

namespace Tests\Unit\Domain;

use App\Domain\Task\TaskRules;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class TaskLatenessTest extends TestCase
{
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        parent::setUp();

        $this->now = new DateTimeImmutable('2026-07-04 10:00:00');
    }

    public function test_a_task_with_a_past_due_date_and_not_completed_is_late(): void
    {
        $dueDate = new DateTimeImmutable('2026-07-01 10:00:00');

        $this->assertTrue(TaskRules::isLate($dueDate, false, $this->now));
    }

    public function test_a_completed_task_is_never_late_even_past_its_due_date(): void
    {
        $dueDate = new DateTimeImmutable('2026-07-01 10:00:00');

        $this->assertFalse(TaskRules::isLate($dueDate, true, $this->now));
    }

    public function test_a_task_with_a_future_due_date_is_not_late(): void
    {
        $dueDate = new DateTimeImmutable('2026-07-10 10:00:00');

        $this->assertFalse(TaskRules::isLate($dueDate, false, $this->now));
    }

    public function test_a_task_without_a_due_date_is_never_late(): void
    {
        $this->assertFalse(TaskRules::isLate(null, false, $this->now));
    }

    public function test_a_task_due_at_the_exact_current_instant_is_not_yet_late(): void
    {
        $this->assertFalse(TaskRules::isLate($this->now, false, $this->now));
    }

    public function test_it_counts_only_the_late_tasks_in_a_list(): void
    {
        $tasks = [
            ['due_date' => new DateTimeImmutable('2026-07-01 10:00:00'), 'completed' => false], // late
            ['due_date' => new DateTimeImmutable('2026-07-01 10:00:00'), 'completed' => true],   // done, not late
            ['due_date' => new DateTimeImmutable('2026-07-10 10:00:00'), 'completed' => false],  // future, not late
            ['due_date' => null, 'completed' => false],                                          // no due date
            ['due_date' => new DateTimeImmutable('2026-06-01 10:00:00'), 'completed' => false],  // late
        ];

        $this->assertSame(2, TaskRules::countLate($tasks, $this->now));
    }

    public function test_it_counts_zero_late_tasks_on_an_empty_list(): void
    {
        $this->assertSame(0, TaskRules::countLate([], $this->now));
    }
}
