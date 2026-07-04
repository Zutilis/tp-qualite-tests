# Gestionnaire de tâches

Application Laravel de gestion de tâches (créer, prioriser, filtrer, marquer comme terminée,
détecter les tâches en retard), développée selon une démarche TDD avec une stratégie de tests
à plusieurs niveaux (unitaires, intégration, end-to-end) et une pipeline CI/CD.

## Stack technique

- Backend : Laravel 11 (PHP 8.4)
- Base de données : SQLite
- Interface : Blade (rendu serveur, sans JavaScript côté client)
- Tests unitaires et d'intégration : PHPUnit (via `php artisan test`)
- Tests end-to-end : Playwright
- CI/CD : GitHub Actions

## Prérequis

- PHP >= 8.2 avec l'extension `sqlite3`
- Composer
- Node.js >= 18 et npm (uniquement nécessaire pour les tests E2E)

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

`composer install` crée déjà le fichier `database/database.sqlite` et lance les migrations
(scripts `post-create-project-cmd` de Laravel) ; la commande `php artisan migrate` ci-dessus
est nécessaire si vous avez besoin de la relancer manuellement (par exemple après un
`migrate:fresh`).

## Lancer l'application

```bash
php artisan serve
```

L'application est disponible sur http://127.0.0.1:8000.

## Lancer les tests

```bash
# Tous les tests PHPUnit (unitaires + intégration)
php artisan test

# Uniquement les tests unitaires (couche domaine, sans base de données)
php artisan test --testsuite=Unit

# Uniquement les tests d'intégration (API + interface web, base SQLite en mémoire)
php artisan test --testsuite=Feature
```

Pour les tests end-to-end (Playwright) :

```bash
npm install
npx playwright install --with-deps chromium
npx playwright test
```

Le fichier `playwright.config.ts` démarre automatiquement `php artisan serve --port=8123`
et réinitialise la base de données (`php artisan migrate:fresh`) avant l'exécution des tests,
aucune manipulation manuelle n'est donc nécessaire.

## Preuve d'exécution des tests

Sortie réelle de `php artisan test` (29 tests, 53 assertions, aucun échec) :

```
   PASS  Tests\Unit\Domain\TaskLatenessTest
  ✓ a task with a past due date and not completed is late
  ✓ a completed task is never late even past its due date
  ✓ a task with a future due date is not late
  ✓ a task without a due date is never late
  ✓ a task due at the exact current instant is not yet late
  ✓ it counts only the late tasks in a list
  ✓ it counts zero late tasks on an empty list

   PASS  Tests\Unit\Domain\TaskPriorityTest
  ✓ it accepts authorised priorities with data set "low"
  ✓ it accepts authorised priorities with data set "medium"
  ✓ it accepts authorised priorities with data set "high"
  ✓ it rejects an unknown priority
  ✓ it rejects an empty priority

   PASS  Tests\Unit\Domain\TaskRulesTest
  ✓ it rejects a null title
  ✓ it rejects an empty title
  ✓ it rejects a title made only of whitespace
  ✓ it accepts a valid title

   PASS  Tests\Feature\Api\TaskApiTest
  ✓ it creates a task with valid data
  ✓ it rejects a task without a title
  ✓ it rejects a task with an invalid priority
  ✓ it updates a task with valid data
  ✓ it rejects an update that clears the title
  ✓ it rejects an update with an invalid priority
  ✓ it lists only late tasks when filtering by status
  ✓ completing a task removes it from the late list
  ✓ it deletes a task
  ✓ it returns 404 for a missing task

   PASS  Tests\Feature\Web\TaskWebTest
  ✓ the home page lists existing tasks
  ✓ submitting the form creates a task and redirects to the list
  ✓ submitting the form without a title shows an error and does not create a task

  Tests:    29 passed (53 assertions)
  Duration: 0.31s
```

Sortie réelle de `npx playwright test` (2 tests E2E, aucun échec) :

```
Running 2 tests using 1 worker

  ✓  1 [chromium] › tests/e2e/tasks.spec.ts:4:5 › Parcours utilisateur - gestion des tâches › un utilisateur crée une tâche et la voit apparaître dans la liste (661ms)
  ✓  2 [chromium] › tests/e2e/tasks.spec.ts:18:5 › Parcours utilisateur - gestion des tâches › une tâche en retard disparaît du compteur une fois terminée (270ms)

  2 passed (2.4s)
```

## Fonctionnalités

- Créer une tâche (titre, priorité, date d'échéance optionnelle)
- Refuser une tâche sans titre ou avec une priorité non autorisée
- Marquer une tâche comme terminée
- Supprimer une tâche
- Filtrer les tâches (toutes / en cours / en retard / terminées)
- Détecter automatiquement les tâches en retard (date d'échéance dépassée et non terminée)
- Compter le nombre de tâches en retard

## Architecture

```
app/
  Domain/Task/        Règles métier pures (aucune dépendance à Laravel/Eloquent)
    TaskRules.php
    InvalidTaskException.php
  Models/Task.php      Modèle Eloquent (persistance)
  Services/
    TaskService.php    Couche applicative : orchestre domaine + persistance
  Http/
    Controllers/
      TaskController.php        Contrôleur web (Blade)
      Api/TaskController.php    Contrôleur API JSON
    Resources/TaskResource.php  Sérialisation JSON

resources/views/tasks/index.blade.php   Interface utilisateur

routes/
  web.php   Routes de l'interface (Blade)
  api.php   Routes de l'API JSON

tests/
  Unit/Domain/         Tests unitaires de la logique métier pure
  Feature/Api/          Tests d'intégration de l'API JSON
  Feature/Web/           Tests d'intégration de l'interface web
  e2e/                    Test end-to-end Playwright
```

La logique métier (validation du titre, de la priorité, calcul du retard) vit dans
`app/Domain/Task/TaskRules.php`, une classe PHP pure sans dépendance au framework : elle est
testée unitairement sans base de données ni bootstrap Laravel.

## Documentation complémentaire

- [`QA_REPORT.md`](./QA_REPORT.md) : stratégie qualité, démarche TDD documentée, couverture de
  tests, CI/CD, limites et améliorations possibles.
