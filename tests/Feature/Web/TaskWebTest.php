<?php

namespace Tests\Feature\Web;

use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_home_page_lists_existing_tasks(): void
    {
        Task::factory()->create(['title' => 'Relire le rapport QA']);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Relire le rapport QA');
    }

    public function test_submitting_the_form_creates_a_task_and_redirects_to_the_list(): void
    {
        $response = $this->post('/tasks', [
            'title' => 'Écrire les tests E2E',
            'priority' => 'high',
            'due_date' => now()->addDay()->toDateString(),
        ]);

        $response->assertRedirect(route('tasks.index'));
        $this->assertDatabaseHas('tasks', ['title' => 'Écrire les tests E2E']);

        $this->get('/')->assertSee('Écrire les tests E2E');
    }

    public function test_submitting_the_form_without_a_title_shows_an_error_and_does_not_create_a_task(): void
    {
        $response = $this->post('/tasks', ['priority' => 'low']);

        $response->assertRedirect();
        $response->assertSessionHasErrors('title');
        $this->assertDatabaseCount('tasks', 0);
    }
}
