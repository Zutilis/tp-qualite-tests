<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestionnaire de tâches</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        :root {
            --bg: #f1f3f9;
            --surface: #ffffff;
            --border: #e5e7eb;
            --text: #1f2933;
            --muted: #6b7280;
            --primary: #4338ca;
            --primary-dark: #362d9c;
            --success: #16a34a;
            --success-dark: #128038;
            --danger: #dc2626;
            --danger-dark: #b91c1c;
            --radius: 10px;
        }

        * { box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
            margin: 0;
            padding: 2.5rem 1rem;
        }

        .page {
            max-width: 720px;
            margin: 0 auto;
        }

        header.app-header {
            margin-bottom: 1.5rem;
        }

        header.app-header h1 {
            font-size: 1.6rem;
            font-weight: 700;
            margin: 0 0 0.35rem;
            letter-spacing: -0.01em;
        }

        .stats {
            color: var(--muted);
            font-size: 0.9rem;
            margin: 0;
        }

        .panel {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04);
            padding: 1.25rem;
            margin-bottom: 1.25rem;
        }

        .alert {
            border-radius: 8px;
            padding: 0.65rem 0.9rem;
            margin-bottom: 1.25rem;
            font-size: 0.9rem;
            border-left: 3px solid transparent;
        }

        .alert.status {
            background: #ecfdf5;
            color: #065f46;
            border-left-color: var(--success);
        }

        .alert.errors {
            background: #fef2f2;
            color: #991b1b;
            border-left-color: var(--danger);
        }

        form.create-task {
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem;
        }

        form.create-task input[type="text"],
        form.create-task select,
        form.create-task input[type="date"] {
            font: inherit;
            padding: 0.55rem 0.7rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: #fafafa;
            color: var(--text);
            transition: border-color 0.15s ease, background 0.15s ease;
        }

        form.create-task input[type="text"] {
            flex: 1 1 220px;
        }

        form.create-task input:focus,
        form.create-task select:focus {
            outline: none;
            border-color: var(--primary);
            background: var(--surface);
        }

        button {
            font: inherit;
            padding: 0.55rem 1rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: transform 0.05s ease, filter 0.15s ease;
        }

        button:hover { filter: brightness(0.94); }
        button:active { transform: translateY(1px); }

        button.primary { background: var(--primary); color: white; }
        button.complete { background: #ecfdf5; color: var(--success-dark); }
        button.delete { background: #fef2f2; color: var(--danger-dark); }

        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
            margin-bottom: 1.25rem;
        }

        .filters a {
            text-decoration: none;
            color: var(--muted);
            font-size: 0.85rem;
            font-weight: 600;
            padding: 0.4rem 0.85rem;
            border-radius: 999px;
            background: var(--surface);
            border: 1px solid var(--border);
        }

        .filters a.active {
            color: white;
            background: var(--primary);
            border-color: var(--primary);
        }

        ul.task-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
        }

        li.task {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            background: var(--surface);
            border: 1px solid var(--border);
            border-left: 4px solid var(--border);
            border-radius: 8px;
            padding: 0.75rem 1rem;
        }

        li.task.priority-border-high { border-left-color: var(--danger); }
        li.task.priority-border-medium { border-left-color: #d97706; }
        li.task.priority-border-low { border-left-color: var(--success); }

        .task-title {
            font-weight: 500;
        }

        .task-title.completed {
            text-decoration: line-through;
            color: var(--muted);
            font-weight: 400;
        }

        .badge {
            display: inline-block;
            padding: 0.15rem 0.6rem;
            border-radius: 999px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            margin-left: 0.4rem;
        }

        .badge.late { background: #fee2e2; color: var(--danger-dark); }
        .badge.priority-high { background: #fee2e2; color: var(--danger-dark); }
        .badge.priority-medium { background: #fef3c7; color: #92400e; }
        .badge.priority-low { background: #dcfce7; color: #166534; }

        .task-actions {
            display: flex;
            gap: 0.4rem;
            flex-shrink: 0;
        }

        .task-actions form { display: inline; }

        .empty-state {
            text-align: center;
            color: var(--muted);
            padding: 2.5rem 1rem;
            background: var(--surface);
            border: 1px dashed var(--border);
            border-radius: var(--radius);
        }
    </style>
</head>
<body>
    <div class="page">
        <header class="app-header">
            <h1>Gestionnaire de tâches</h1>
            <p class="stats" data-testid="late-count">Tâches en retard : {{ $lateCount }}</p>
        </header>

        @if (session('status'))
            <div class="alert status" data-testid="flash-status">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert errors" data-testid="form-errors">{{ $errors->first() }}</div>
        @endif

        <div class="panel">
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
        </div>

        <nav class="filters">
            <a href="{{ route('tasks.index') }}" class="{{ $status === null ? 'active' : '' }}">Toutes</a>
            <a href="{{ route('tasks.index', ['status' => 'pending']) }}" class="{{ $status === 'pending' ? 'active' : '' }}">En cours</a>
            <a href="{{ route('tasks.index', ['status' => 'late']) }}" class="{{ $status === 'late' ? 'active' : '' }}">En retard</a>
            <a href="{{ route('tasks.index', ['status' => 'completed']) }}" class="{{ $status === 'completed' ? 'active' : '' }}">Terminées</a>
        </nav>

        <ul class="task-list" data-testid="task-list">
            @forelse ($tasks as $task)
                <li class="task priority-border-{{ $task->priority }}" data-testid="task-item" data-task-id="{{ $task->id }}">
                    <div>
                        <span class="task-title {{ $task->completed ? 'completed' : '' }}">{{ $task->title }}</span>
                        <span class="badge priority-{{ $task->priority }}">{{ ucfirst($task->priority) }}</span>
                        @if ($task->is_late)
                            <span class="badge late" data-testid="late-badge">En retard</span>
                        @endif
                    </div>
                    <div class="task-actions">
                        @unless ($task->completed)
                            <form method="POST" action="{{ route('tasks.complete', $task) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="complete" data-testid="task-complete-button">Terminer</button>
                            </form>
                        @endunless
                        <form method="POST" action="{{ route('tasks.destroy', $task) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="delete" data-testid="task-delete-button">Supprimer</button>
                        </form>
                    </div>
                </li>
            @empty
                <li class="empty-state" data-testid="empty-state">Aucune tâche pour ce filtre.</li>
            @endforelse
        </ul>
    </div>
</body>
</html>
