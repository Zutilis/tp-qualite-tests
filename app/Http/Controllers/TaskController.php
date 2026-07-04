<?php

namespace App\Http\Controllers;

use App\Domain\Task\InvalidTaskException;
use App\Domain\Task\TaskRules;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function __construct(private readonly TaskService $tasks)
    {
    }

    public function index(Request $request): View
    {
        $status = $request->query('status');

        return view('tasks.index', [
            'tasks' => $this->tasks->list($status),
            'status' => $status,
            'lateCount' => $this->tasks->countLate(),
            'priorities' => TaskRules::PRIORITIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'priority' => ['nullable', 'string', 'max:50'],
            'due_date' => ['nullable', 'date'],
        ]);

        try {
            $this->tasks->create($data);
        } catch (InvalidTaskException $exception) {
            return back()->withErrors(['title' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('tasks.index')->with('status', 'Tâche créée avec succès.');
    }

    public function complete(Task $task): RedirectResponse
    {
        $this->tasks->complete($task);

        return redirect()->route('tasks.index')->with('status', 'Tâche marquée comme terminée.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $this->tasks->delete($task);

        return redirect()->route('tasks.index')->with('status', 'Tâche supprimée.');
    }
}
