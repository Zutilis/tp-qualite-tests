<?php

namespace Tests\Unit\Domain;

use App\Domain\Task\InvalidTaskException;
use App\Domain\Task\TaskRules;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TaskPriorityTest extends TestCase
{
    #[DataProvider('validPriorities')]
    public function test_it_accepts_authorised_priorities(string $priority): void
    {
        TaskRules::assertValidPriority($priority);

        $this->addToAssertionCount(1);
    }

    public static function validPriorities(): array
    {
        return [
            'low' => ['low'],
            'medium' => ['medium'],
            'high' => ['high'],
        ];
    }

    public function test_it_rejects_an_unknown_priority(): void
    {
        $this->expectException(InvalidTaskException::class);

        TaskRules::assertValidPriority('urgent');
    }

    public function test_it_rejects_an_empty_priority(): void
    {
        $this->expectException(InvalidTaskException::class);

        TaskRules::assertValidPriority('');
    }
}
