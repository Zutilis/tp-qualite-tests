# Rapport QA — Gestionnaire de tâches

## 1. Présentation du projet

- **Thème choisi** : gestionnaire de tâches.
- **Objectif** : permettre à un utilisateur de créer des tâches, de les prioriser, de les
  filtrer, de les marquer comme terminées et de suivre celles qui sont en retard, avec une
  logique métier testée à tous les niveaux.
- **Stack utilisée** : Laravel 11 (PHP 8.4) pour le backend et l'interface Blade, SQLite comme
  base de données, PHPUnit pour les tests unitaires/intégration, Playwright pour le test
  end-to-end, GitHub Actions pour la CI/CD.
- **Principales fonctionnalités** : création/suppression de tâche, validation du titre et de la
  priorité, marquage "terminée", filtrage par statut (toutes / en cours / en retard /
  terminées), calcul du nombre de tâches en retard.

## 2. Fonctionnalités développées

- Créer une tâche (titre, priorité, date d'échéance optionnelle).
- Modifier une tâche existante (titre, priorité, date d'échéance).
- Refuser la création ou la modification d'une tâche sans titre (ou dont le titre est
  vide/uniquement composé d'espaces).
- Refuser la création ou la modification d'une tâche avec une priorité non autorisée.
- Marquer une tâche comme terminée.
- Supprimer une tâche.
- Lister les tâches avec un filtre (`all`, `pending`, `late`, `completed`).
- Calculer le nombre de tâches en retard.
- Interface web (Blade) permettant de réaliser ce parcours sans écrire de requête HTTP à la
  main.
- API JSON (`/api/tasks`) exposant les mêmes fonctionnalités.

Le périmètre a volontairement été limité à ces fonctionnalités pour rester un projet complet,
testé et documenté plutôt qu'une application large mais incomplète.

## 3. Règles métier principales

Toutes ces règles vivent dans `app/Domain/Task/TaskRules.php`, indépendamment du framework :

- Une tâche sans titre (`null`, chaîne vide ou uniquement des espaces) est invalide.
- Une priorité doit appartenir à la liste autorisée : `low`, `medium`, `high`.
- Une tâche est "en retard" si sa date d'échéance est strictement dans le passé **et** qu'elle
  n'est pas terminée.
- Une tâche terminée n'est jamais considérée comme en retard, même si sa date d'échéance est
  dépassée.
- Une tâche sans date d'échéance n'est jamais en retard.

## 4. Démarche TDD

Quatre cycles TDD ont été menés, du plus bas niveau (règles métier pures) au plus haut niveau
(API). Chaque cycle a été réellement exécuté avec `php artisan test` : les extraits ci-dessous
sont les sorties console obtenues à chaque étape (rouge puis vert), pas une reconstruction a
posteriori.

### Cycle 1 — Validation du titre d'une tâche

**Comportement attendu** : une tâche sans titre (null, vide, ou espaces uniquement) doit être
rejetée ; un titre valide doit être accepté.

**Test écrit** (`tests/Unit/Domain/TaskRulesTest.php`) appelant
`TaskRules::assertValidTitle()`.

**Résultat initial : échec** (la classe n'existe pas encore) :

```
FAIL  Tests\Unit\Domain\TaskRulesTest
⨯ it rejects a null title
⨯ it rejects an empty title
⨯ it rejects a title made only of whitespace
⨯ it accepts a valid title

FAILED  Tests\Unit\Domain\TaskRulesTest > it rejects a null title  Exception
Class "App\Domain\Task\InvalidTaskException" does not exist
```

**Code ajouté** : `App\Domain\Task\InvalidTaskException` et
`TaskRules::assertValidTitle()` (rejette `null`/chaîne vide après `trim()`).

**Résultat final : succès** :

```
PASS  Tests\Unit\Domain\TaskRulesTest
✓ it rejects a null title
✓ it rejects an empty title
✓ it rejects a title made only of whitespace
✓ it accepts a valid title

Tests:    4 passed (4 assertions)
```

### Cycle 2 — Validation de la priorité

**Comportement attendu** : seules les priorités `low`, `medium`, `high` sont acceptées ; toute
autre valeur doit être rejetée.

**Test écrit** (`tests/Unit/Domain/TaskPriorityTest.php`) appelant
`TaskRules::assertValidPriority()`.

**Résultat initial : échec** (méthode inexistante) :

```
FAILED  Tests\Unit\Domain\TaskPriorityTest > it rejects an unknown priority
Failed asserting that exception of type "Error" matches expected exception
"App\Domain\Task\InvalidTaskException". Message was: "Call to undefined method
App\Domain\Task\TaskRules::assertValidPriority()"

Tests:    5 failed (2 assertions)
```

**Code ajouté** : `TaskRules::assertValidPriority()` et la constante
`TaskRules::PRIORITIES = ['low', 'medium', 'high']`.

**Résultat final : succès** :

```
PASS  Tests\Unit\Domain\TaskPriorityTest
✓ it accepts authorised priorities with data set "low"
✓ it accepts authorised priorities with data set "medium"
✓ it accepts authorised priorities with data set "high"
✓ it rejects an unknown priority
✓ it rejects an empty priority

Tests:    5 passed (5 assertions)
```

### Cycle 3 — Détection et comptage des tâches en retard

**Comportement attendu** : une tâche non terminée dont la date d'échéance est passée est "en
retard" ; une tâche terminée ou sans date d'échéance ne l'est jamais ; on peut compter les
tâches en retard dans une liste.

**Test écrit** (`tests/Unit/Domain/TaskLatenessTest.php`) appelant `TaskRules::isLate()` et
`TaskRules::countLate()`.

**Résultat initial : échec** (méthodes inexistantes) :

```
FAILED  Tests\Unit\Domain\TaskLatenessTest > it counts zero late tasks on an empty list  Error
Call to undefined method App\Domain\Task\TaskRules::countLate()

Tests:    7 failed (0 assertions)
```

**Code ajouté** : `TaskRules::isLate()` (compare la date d'échéance à l'instant courant, tient
compte du statut terminé) et `TaskRules::countLate()` (itère sur une liste de tâches).

**Résultat final : succès** (suite unitaire complète) :

```
PASS  Tests\Unit\Domain\TaskLatenessTest
✓ a task with a past due date and not completed is late
✓ a completed task is never late even past its due date
✓ a task with a future due date is not late
✓ a task without a due date is never late
✓ a task due at the exact current instant is not yet late
✓ it counts only the late tasks in a list
✓ it counts zero late tasks on an empty list

Tests:    17 passed (17 assertions)
```

### Cycle 4 — Intégration API : terminer une tâche la retire du retard

**Comportement attendu** : `PATCH /api/tasks/{id}/complete` doit marquer la tâche comme
terminée et elle ne doit plus apparaître comme "en retard", à travers toute la pile
(route → contrôleur → service → base de données).

**Test écrit** (`tests/Feature/Api/TaskApiTest.php`), en s'appuyant sur les routes/contrôleurs
qui n'existaient pas encore.

**Résultat initial : échec** (routes API absentes, tout retourne 404) :

```
FAILED  Tests\Feature\Api\TaskApiTest > completing a task removes it from the late list
Expected response status code [200] but received 404.

FAILED  Tests\Feature\Api\TaskApiTest > it deletes a task
Expected response status code [204] but received 404.

Tests:    6 failed, 1 passed (8 assertions)
```

**Code ajouté** : `routes/api.php`, `App\Http\Controllers\Api\TaskController`,
`App\Http\Resources\TaskResource`, `App\Services\TaskService`.

**Résultat final : succès** :

```
PASS  Tests\Feature\Api\TaskApiTest
✓ it creates a task with valid data
✓ it rejects a task without a title
✓ it rejects a task with an invalid priority
✓ it lists only late tasks when filtering by status
✓ completing a task removes it from the late list
✓ it deletes a task
✓ it returns 404 for a missing task

Tests:    7 passed (19 assertions)
```

Il n'a pas été jugé utile de montrer l'historique Git complet ; la démarche ci-dessus reflète
fidèlement l'ordre réel d'écriture des tests et du code (rouge puis vert) pour chacun des
quatre cycles.

## 5. Risques qualité identifiés

- **Mauvaise validation des données** : une priorité ou un titre invalide accepté par erreur
  casserait la cohérence des données (mitigé par `TaskRules` + tests dédiés).
- **Erreur de calcul du retard** : une erreur de comparaison de dates (`<=` au lieu de `<`, fuseau
  horaire, etc.) donnerait un statut "en retard" incorrect (mitigé par les cas limites testés :
  date future, date exactement égale à l'instant courant, absence de date).
- **Régression sur une règle métier** : une modification future du service pourrait recasser une
  règle déjà validée (mitigé par la suite de tests exécutée automatiquement en CI/CD).
- **Incohérence entre l'API et l'interface web** : les deux exposent la même logique via
  `TaskService`, ce qui limite le risque de divergence de comportement.
- **Route API acceptant des données invalides** : testé explicitement (titre manquant, priorité
  hors liste → 422).

## 6. Stratégie de tests

- **Tests unitaires** (`tests/Unit/Domain`) : ciblent uniquement `app/Domain/Task/TaskRules.php`,
  une classe PHP pure sans dépendance à Laravel. Ils s'exécutent sans base de données et
  vérifient les règles métier isolément (titre, priorité, calcul du retard).
- **Tests d'intégration** (`tests/Feature/Api`, `tests/Feature/Web`) : vérifient que les routes,
  contrôleurs, service applicatif et base de données (SQLite en mémoire) fonctionnent ensemble,
  côté API JSON et côté interface Blade.
- **Test end-to-end** (`tests/e2e/tasks.spec.ts`) : vérifie un parcours utilisateur réel dans un
  navigateur piloté par Playwright, du remplissage du formulaire jusqu'à l'affichage du résultat
  à l'écran — ce que ne peuvent pas garantir les tests précédents, qui n'exercent jamais le rendu
  HTML/JS réel ni les interactions navigateur.
- **Couvert** : validation métier (titre, priorité), calcul du retard, CRUD des tâches, filtrage,
  un parcours de création et un parcours de complétion d'une tâche en retard.
- **Non couvert** : authentification/autorisation (hors périmètre de ce mini-projet), pagination,
  tri avancé, notifications.

## 7. Tests unitaires réalisés

| Fichier | Cas couverts |
|---|---|
| `tests/Unit/Domain/TaskRulesTest.php` | titre `null`, vide, espaces uniquement (cas d'erreur), titre valide (cas nominal) |
| `tests/Unit/Domain/TaskPriorityTest.php` | les 3 priorités valides (cas nominaux), priorité inconnue et priorité vide (cas d'erreur) |
| `tests/Unit/Domain/TaskLatenessTest.php` | tâche en retard (cas nominal), tâche terminée non en retard, tâche future non en retard, tâche sans date, tâche à l'instant exact (cas limite), comptage sur liste mixte et sur liste vide (cas limite) |

Total : 16 tests unitaires, 16 assertions.

## 8. Tests d'intégration réalisés

| Fichier | Cas couverts |
|---|---|
| `tests/Feature/Api/TaskApiTest.php` | création valide (201), titre manquant (422), priorité invalide (422), modification valide (200), modification avec titre vide (422), modification avec priorité invalide (422), filtrage `status=late`, complétion d'une tâche en retard, suppression (204), tâche introuvable (404) |
| `tests/Feature/Web/TaskWebTest.php` | affichage de la liste, création via formulaire + redirection, formulaire invalide → erreur affichée, aucune tâche créée |

Total : 13 tests d'intégration, 37 assertions.

## 9. Test E2E réalisé

`tests/e2e/tasks.spec.ts` (Playwright, navigateur Chromium) :

1. **Créer une tâche et la voir apparaître dans la liste** : remplissage du formulaire (titre,
   priorité), soumission, vérification du message de confirmation et de la présence de la
   tâche avec le bon badge de priorité dans le DOM rendu.
2. **Une tâche en retard disparaît du compteur une fois terminée** : création d'une tâche avec
   une date d'échéance passée, vérification du badge "En retard" et du compteur, clic sur
   "Terminer", vérification que le compteur revient à 0 et que la tâche apparaît bien dans le
   filtre "Terminées".

Ce parcours a été choisi car il exerce la règle métier la plus riche du projet (calcul du
retard) de bout en bout, à travers un vrai navigateur, ce qu'aucun test unitaire ou
d'intégration ne peut garantir.

## 10. Pipeline CI/CD

- **Emplacement** : `.github/workflows/ci.yml`.
- **Déclenchement** : à chaque `push` et `pull_request` sur la branche `main`.
- **Job `backend-tests`** : installe PHP 8.4 et les dépendances Composer, prépare le fichier
  `.env` et la clé d'application, puis exécute `php artisan test --testsuite=Unit` puis
  `--testsuite=Feature`.
- **Job `e2e-tests`** (dépend du précédent) : installe PHP, crée le fichier SQLite et exécute
  `php artisan migrate --force` (le schéma doit exister avant que le serveur ne démarre), installe
  Node 22 et les navigateurs Playwright, puis exécute `npx playwright test` (qui démarre lui-même
  `php artisan serve` et réinitialise la base via `migrate:fresh` avant les tests). Le rapport HTML
  Playwright est publié comme artefact du job.
- **En cas d'échec** : le job correspondant est marqué en échec et bloque la fusion si une
  protection de branche est configurée ; les logs détaillés (assertions, requêtes/réponses) sont
  disponibles dans l'onglet "Actions" de GitHub.
- **Vérification réelle** : les deux jobs ont été exécutés sur GitHub Actions (pas seulement en
  local) après un premier échec du job E2E (le serveur ne répondait pas car la base ne contenait
  pas encore les tables au démarrage) ; l'ajout de `php artisan migrate --force` avant le
  démarrage du serveur a corrigé le problème, confirmé par une exécution réussie sur GitHub.
- **Limites actuelles** : la pipeline ne calcule pas de couverture de code, ne publie pas
  automatiquement l'application, et n'exécute qu'un navigateur (Chromium) pour l'E2E. Ces choix
  sont volontaires pour garder une pipeline simple et rapide, cohérente avec la taille du projet.

## 11. Utilisation de l'IA générative

- **Outil utilisé** : Claude (Anthropic), via l'interface en ligne de commande Claude Code.
- **Usage réel** : l'assistant a été utilisé comme accélérateur d'écriture pour l'ensemble du
  projet (code applicatif, tests, configuration CI/CD, rédaction de la documentation), sous
  supervision et validation continue : chaque commande de test a été réellement exécutée avant
  et après l'écriture du code (cycles rouge/vert), et les extraits de log présentés dans ce
  rapport sont authentiques (aucune sortie de terminal n'a été inventée).
- **Ce qui a été conservé** : la structure en couche domaine/service/contrôleur, les cas de test
  nominaux/limites/erreurs demandés par le sujet, la configuration Playwright avec réinitialisation
  automatique de la base de données.
- **Ce qui a été modifié/refusé** : les propositions ont été systématiquement vérifiées par
  exécution réelle des tests plutôt qu'acceptées telles quelles ; le périmètre fonctionnel a été
  volontairement restreint (pas d'authentification dans cette partie du projet) pour respecter la
  consigne de garder un projet simple et complet plutôt qu'ambitieux et inachevé.
- **Limites observées** : un assistant IA peut produire du code syntaxiquement correct mais
  fonctionnellement faux (ex. une condition de retard inversée) si les tests ne sont pas
  exécutés réellement à chaque étape ; c'est pourquoi chaque cycle TDD documenté ci-dessus a été
  vérifié par une exécution effective de `php artisan test`, et non simplement rédigé a posteriori.

## 12. Limites actuelles

- Pas d'authentification ni de notion de propriétaire de tâche : le tableau de bord et l'API
  sont partagés par tous les utilisateurs, sans compte ni droits d'accès.
- Pas de pagination : au-delà d'un grand nombre de tâches, la liste complète est chargée à
  chaque requête.
- Le calcul du retard se fait au niveau du jour (`due_date` est une date sans heure) : deux
  tâches échéant le même jour ne sont pas départagées à l'heure près.
- Le test E2E ne couvre qu'un seul navigateur (Chromium) et un seul poste (pas de test
  responsive/mobile).
- Aucune mesure de couverture de code (ex. Xdebug/PCOV) n'est configurée dans la CI.

## 13. Améliorations possibles

- Ajouter l'authentification et la notion de propriétaire de tâche pour distinguer
  plusieurs utilisateurs.
- Ajouter la pagination de la liste des tâches et un tri par priorité/date.
- Ajouter une mesure de couverture de code dans la CI avec un seuil minimum.
- Ajouter un test E2E supplémentaire couvrant la suppression d'une tâche et le filtrage par
  priorité.
- Étendre les tests E2E à un second navigateur (ex. WebKit/Firefox) pour la CI.
