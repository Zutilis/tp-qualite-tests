<?php

namespace Tests\Unit\Domain;

use App\Domain\Task\InvalidTaskException;
use App\Domain\Task\TaskRules;
use PHPUnit\Framework\TestCase;

class TaskRulesTest extends TestCase
{
    public function test_it_rejects_a_null_title(): void
    {
        $this->expectException(InvalidTaskException::class);

        TaskRules::assertValidTitle(null);
    }

    public function test_it_rejects_an_empty_title(): void
    {
        $this->expectException(InvalidTaskException::class);

        TaskRules::assertValidTitle('');
    }

    public function test_it_rejects_a_title_made_only_of_whitespace(): void
    {
        $this->expectException(InvalidTaskException::class);

        TaskRules::assertValidTitle('   ');
    }

    public function test_it_accepts_a_valid_title(): void
    {
        TaskRules::assertValidTitle('Préparer la démonstration');

        $this->addToAssertionCount(1);
    }
}
