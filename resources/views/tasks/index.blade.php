<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestionnaire de tâches</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: system-ui, sans-serif; max-width: 760px; margin: 2rem auto; padding: 0 1rem; color: #1f2933; }
        h1 { font-size: 1.5rem; }
        form.create-task { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1.5rem; }
        form.create-task input[type="text"] { flex: 1 1 220px; padding: 0.5rem; }
        form.create-task select, form.create-task input[type="date"] { padding: 0.5rem; }
        button { padding: 0.5rem 0.9rem; border: none; border-radius: 4px; cursor: pointer; }
        button.primary { background: #2563eb; color: white; }
        button.complete { background: #16a34a; color: white; }
        button.delete { background: #dc2626; color: white; }
        .filters { margin-bottom: 1rem; }
        .filters a { margin-right: 0.75rem; text-decoration: none; color: #2563eb; }
        .filters a.active { font-weight: bold; text-decoration: underline; }
        ul.task-list { list-style: none; padding: 0; }
        li.task { display: flex; align-items: center; justify-content: space-between; padding: 0.6rem 0; border-bottom: 1px solid #e5e7eb; }
        .task-title.completed { text-decoration: line-through; color: #6b7280; }
        .badge { display: inline-block; padding: 0.1rem 0.5rem; border-radius: 999px; font-size: 0.75rem; margin-left: 0.5rem; }
        .badge.late { background: #fee2e2; color: #b91c1c; }
        .badge.priority-high { background: #fee2e2; color: #b91c1c; }
        .badge.priority-medium { background: #fef3c7; color: #92400e; }
        .badge.priority-low { background: #dcfce7; color: #166534; }
        .status { background: #ecfdf5; color: #065f46; padding: 0.5rem 0.75rem; border-radius: 4px; margin-bottom: 1rem; }
        .errors { background: #fef2f2; color: #b91c1c; padding: 0.5rem 0.75rem; border-radius: 4px; margin-bottom: 1rem; }
        .stats { color: #6b7280; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <h1>Gestionnaire de tâches</h1>

    @if (session('status'))
        <div class="status" data-testid="flash-status">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="errors" data-testid="form-errors">{{ $errors->first() }}</div>
    @endif

    <p class="stats" data-testid="late-count">Tâches en retard : {{ $lateCount }}</p>

    <form class="create-task" method="POST" action="{{ route('tasks.store') }}">
        @csrf
        <input type="text" name="title" placeholder="Titre de la tâche" value="{{ old('title') }}" data-testid="task-title-input">
        <select name="priority" data-testid="task-priority-select">
            @foreach ($priorities as $priority)
                <option value="{{ $priority }}" @selected(old('priority') === $priority)>{{ ucfirst($priority) }}</option>
            @endforeach
        </select>
        <input type="date" name="due_date" value="{{ old('due_date') }}" data-testid="task-due-date-input">
        <button type="submit" class="primary" data-testid="task-submit">Ajouter</button>
    </form>

    <nav class="filters">
        <a href="{{ route('tasks.index') }}" class="{{ $status === null ? 'active' : '' }}">Toutes</a>
        <a href="{{ route('tasks.index', ['status' => 'pending']) }}" class="{{ $status === 'pending' ? 'active' : '' }}">En cours</a>
        <a href="{{ route('tasks.index', ['status' => 'late']) }}" class="{{ $status === 'late' ? 'active' : '' }}">En retard</a>
        <a href="{{ route('tasks.index', ['status' => 'completed']) }}" class="{{ $status === 'completed' ? 'active' : '' }}">Terminées</a>
    </nav>

    <ul class="task-list" data-testid="task-list">
        @forelse ($tasks as $task)
            <li class="task" data-testid="task-item" data-task-id="{{ $task->id }}">
                <div>
                    <span class="task-title {{ $task->completed ? 'completed' : '' }}">{{ $task->title }}</span>
                    <span class="badge priority-{{ $task->priority }}">{{ ucfirst($task->priority) }}</span>
                    @if ($task->is_late)
                        <span class="badge late" data-testid="late-badge">En retard</span>
                    @endif
                </div>
                <div>
                    @unless ($task->completed)
                        <form method="POST" action="{{ route('tasks.complete', $task) }}" style="display:inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="complete" data-testid="task-complete-button">Terminer</button>
                        </form>
                    @endunless
                    <form method="POST" action="{{ route('tasks.destroy', $task) }}" style="display:inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="delete" data-testid="task-delete-button">Supprimer</button>
                    </form>
                </div>
            </li>
        @empty
            <li data-testid="empty-state">Aucune tâche pour ce filtre.</li>
        @endforelse
    </ul>
</body>
</html>
