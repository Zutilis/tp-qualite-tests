<?php

namespace App\Http\Controllers\Api;

use App\Domain\Task\InvalidTaskException;
use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class TaskController extends Controller
{
    public function __construct(private readonly TaskService $tasks)
    {
    }

    public function index(Request $request)
    {
        $status = $request->query('status');

        return TaskResource::collection($this->tasks->list($status));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'priority' => ['nullable', 'string', 'max:50'],
            'due_date' => ['nullable', 'date'],
        ]);

        $task = $this->createTask($data);

        return TaskResource::make($task)->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Task $task)
    {
        return TaskResource::make($task);
    }

    public function update(Request $request, Task $task)
    {
        $data = $request->validate([
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'priority' => ['sometimes', 'nullable', 'string', 'max:50'],
            'due_date' => ['sometimes', 'nullable', 'date'],
        ]);

        $task = $this->updateTask($task, $data);

        return TaskResource::make($task);
    }

    public function complete(Task $task)
    {
        return TaskResource::make($this->tasks->complete($task));
    }

    public function destroy(Task $task)
    {
        $this->tasks->delete($task);

        return response()->noContent();
    }

    private function createTask(array $data): Task
    {
        try {
            return $this->tasks->create($data);
        } catch (InvalidTaskException $exception) {
            throw ValidationException::withMessages(['title' => [$exception->getMessage()]]);
        }
    }

    private function updateTask(Task $task, array $data): Task
    {
        try {
            return $this->tasks->update($task, $data);
        } catch (InvalidTaskException $exception) {
            throw ValidationException::withMessages(['title' => [$exception->getMessage()]]);
        }
    }
}
