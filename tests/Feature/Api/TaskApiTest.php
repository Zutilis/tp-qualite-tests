<?php

namespace Tests\Feature\Api;

use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_task_with_valid_data(): void
    {
        $response = $this->postJson('/api/tasks', [
            'title' => 'Préparer la soutenance',
            'priority' => 'high',
            'due_date' => now()->addDays(3)->toDateString(),
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.title', 'Préparer la soutenance');
        $this->assertDatabaseHas('tasks', ['title' => 'Préparer la soutenance']);
    }

    public function test_it_rejects_a_task_without_a_title(): void
    {
        $response = $this->postJson('/api/tasks', [
            'priority' => 'low',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_it_rejects_a_task_with_an_invalid_priority(): void
    {
        $response = $this->postJson('/api/tasks', [
            'title' => 'Corriger le bug',
            'priority' => 'urgentissime',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_it_lists_only_late_tasks_when_filtering_by_status(): void
    {
        Task::factory()->create([
            'title' => 'Tâche en retard',
            'due_date' => now()->subDays(2),
            'completed' => false,
        ]);
        Task::factory()->create([
            'title' => 'Tâche future',
            'due_date' => now()->addDays(2),
            'completed' => false,
        ]);

        $response = $this->getJson('/api/tasks?status=late');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.title', 'Tâche en retard');
    }

    public function test_completing_a_task_removes_it_from_the_late_list(): void
    {
        $task = Task::factory()->create([
            'due_date' => now()->subDay(),
            'completed' => false,
        ]);

        $this->assertSame(1, app(\App\Services\TaskService::class)->countLate());

        $response = $this->patchJson("/api/tasks/{$task->id}/complete");

        $response->assertOk();
        $response->assertJsonPath('data.completed', true);
        $response->assertJsonPath('data.is_late', false);
        $this->assertSame(0, app(\App\Services\TaskService::class)->countLate());
    }

    public function test_it_deletes_a_task(): void
    {
        $task = Task::factory()->create();

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_it_returns_404_for_a_missing_task(): void
    {
        $response = $this->getJson('/api/tasks/999');

        $response->assertNotFound();
    }
}
