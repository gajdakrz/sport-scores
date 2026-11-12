# AGENTS.md

Sports results management app: Symfony 7.3 + PHP 8.4, Doctrine ORM 3, PostgreSQL, TypeScript + Bootstrap 5 frontend (Webpack Encore). Application code lives in `api/`; everything runs in Docker.

Reply to the user in the language they write in.

## General principle

Follow Symfony, Doctrine, PHP and TypeScript best practices: stay consistent with existing code, keep domain logic in entities/services rather than controllers, validate on the server (the frontend only makes choosing easier), and add tests for new logic.

## Commands

PHP and Node are not installed locally – run commands inside the containers (container working directory = `api/`).
The agent has no TTY: use `docker exec` **without** `-it` (which is why `make test` and `make test-db` won't work).

```bash
docker compose up -d                                                  # start the environment
make test-db-ide                                                      # rebuild the test database (drop/create/migrate/fixtures)
docker exec sport-scores-php sh -c "APP_ENV=test php bin/phpunit"     # all tests
docker exec sport-scores-php sh -c "APP_ENV=test php bin/phpunit tests/Unit/Service/BracketBuilderTest.php"  # single file
docker exec sport-scores-php vendor/bin/phpstan analyse --no-progress --memory-limit=1G # PHPStan, level 9 (the default 128M is not enough)
docker exec sport-scores-php vendor/bin/phpcs                         # PSR-12 + unused `use` imports
make fix                                                              # phpcbf: auto-fix style, remove unused `use` imports
docker exec sport-scores-node npm run lint                            # ESLint (assets/ts)
docker exec sport-scores-node npm run typecheck                       # tsc --noEmit
```

Other targets (`up`, `composer-install`, `db`, `cache-clear`) are in the `Makefile` and work without a TTY.

## Before you consider a task done

- First, clean up after your changes (before running the checks below):
  - run `make fix` – it removes unused `use` imports and fixes code style automatically; don't hunt for unused imports by hand;
  - unused private methods, properties and constants are reported by PHPStan, unused TS imports/variables by ESLint – fix what they report;
  - tools don't catch whole classes, public methods, test helpers or TS exports left without callers (e.g. after a rename or refactor) – check those in the files you touched with `grep`.
- PHPUnit, PHPStan (level 9) and phpcs pass with no errors. phpcs reports line length as a warning – treat warnings as failures too. In CI, lint and PHPStan have `continue-on-error`, so a failure won't stop the pipeline – check locally.
- `docker exec sport-scores-php php bin/console doctrine:schema:validate --env=test` reports OK in both sections (Mapping and Database) – read the full output, not just the tail. Don't use `doctrine:migrations:diff` as a check – it creates a migration file.
- For changes in `assets/ts/`, `lint` and `typecheck` pass.
- New code in `src/` has tests – coverage goes to SonarCloud (`coverage.xml`). Check it locally – see [SonarCloud](#sonarcloud).
- Code follows the SonarCloud rules below (no new issues on the quality gate).

## SonarCloud

Project `gajdakrz_sport-scores` (config: `api/sonar-project.properties`). CI (`.github/workflows/ci.yaml`) runs the analysis on push with `SONAR_TOKEN` – there is no local scanner and no token for agents, so verify in two steps:

1. **Locally, before finishing** – coverage of the code you changed (Xdebug is in the PHP container; output goes to `var/`, which is gitignored):

   ```bash
   docker exec sport-scores-php sh -c "APP_ENV=test XDEBUG_MODE=coverage php bin/phpunit --coverage-text=var/coverage.txt --colors=never"
   docker exec sport-scores-php grep -A1 'App\\Validator\\MyValidator' var/coverage.txt   # per class
   ```

   New and changed classes should be covered (aim for ≥ 80% lines); untouched legacy methods don't count.
2. **After push (CI finished)** – the project is public, so read-only API calls need no token:

   ```bash
   curl -s "https://sonarcloud.io/api/qualitygates/project_status?projectKey=gajdakrz_sport-scores"                 # gate: status OK
   curl -s "https://sonarcloud.io/api/issues/search?componentKeys=gajdakrz_sport-scores&resolved=false&ps=100"       # open issues
   curl -s "https://sonarcloud.io/api/project_analyses/search?project=gajdakrz_sport-scores&ps=1"                   # last analysed commit
   ```

   For a pull request add `&pullRequest=<number>` (gate and issues). Confirm the last analysed `revision` is your commit – if not, the analysis didn't run.

Quality gate on new code: reliability, security and maintainability rating A, duplication ≤ 3%, all security hotspots reviewed. Write code that passes the common Sonar PHP rules:

- cognitive complexity ≤ 15 per function (`php:S3776`) – extract methods instead of nesting;
- no string literal repeated 3+ times (`php:S1192`) – use a constant or enum;
- at most 7 parameters per method (`php:S107`) – pass a DTO;
- no commented-out code (`php:S125`) and no unused private methods, variables or imports;
- no duplicated blocks – reuse helpers (e.g. `tests/Trait/ControllerTestTrait.php`) instead of copy-paste.

## Backend conventions

- File structure (every PHP file, including tests and migrations):
  - starts with `<?php`, a blank line, then `declare(strict_types=1);`;
  - lines are at most 120 characters (PSR-12, enforced by phpcs) – split long conditions, calls and strings when writing, not after the check;
  - ends with a single empty line (a final newline, no trailing blank lines).
- Controllers extend `App\Controller\BaseController`: `Web/` – Twig views + AJAX, `Api/` – JSON endpoints.
- Validation errors: `throwFormErrors($form)` or `CustomBadRequestException`. A listener turns them into JSON `{ "errors": [{ "message", "field" }] }` – don't build this response by hand.
- AJAX actions that modify data check CSRF via `validateCsrfToken()`.
- Don't use `addFlash()` in AJAX responses – the message goes in the JSON.
- `createdAt` / `modifiedAt` are set by `AbstractEntity` (lifecycle callbacks) – don't set them manually.
- Schema change = new migration (`php bin/console make:migration`). Don't edit existing files in `migrations/`.
- Traits live in a `Trait/` subdirectory of the layer that uses them (e.g. `src/Dto/Request/Trait/`, `src/Enum/Trait/`), not in a global `src/Trait/`. Prefer composition or inheritance; use a trait only for small, single-purpose code shared by several classes.

## Frontend conventions

- Every module in `assets/ts/<feature>/` has a `ModalInitializer` with an `init()` method that calls `AppBase.initPendingFlash()`.
- Message after AJAX + page reload: `AppBase.showFlashAfterReload()`, then `window.location.reload()`.

## Tests

- `tests/Unit`, `tests/Integration`, `tests/Functional` (Web/Api controllers, repositories). Shared helpers in `tests/Trait/ControllerTestTrait.php`.
- DAMA DoctrineTestBundle rolls back each test in a transaction – tests don't need to clean up the database, but they require loaded fixtures (`make test-db-ide`).

### What to test and what not to

Test behaviour and business rules, not implementation. Don't write tests for:

- plain getters/setters and constructors without logic (entities, DTOs) – they are covered indirectly by controller and validator tests;
- small domain methods (e.g. `Team::acceptsGender()`) when all their branches are already covered by tests of the validator/service that uses them – don't duplicate coverage;
- framework and library code (Doctrine mapping, Symfony Form, routing, serializer) – assume it works;
- trivial form configuration (`buildForm` with only `add()` calls), enums without methods and `EnumLabelTrait` – the controller functional test covers them;
- private method details – test through the public API;
- exact message or HTML content – assert the status code, the error field (`field`) and the database state.

Writing guidelines:

- Test repositories through controller functional tests, not with standalone repository tests.
- Mock only boundaries (validator context, external services); don't mock entities – create real objects.
- One test = one scenario; the name and `#[TestDox]` describe the expected behaviour. Use `#[DataProvider]` for variants of the same rule.
- Test method names and `#[TestDox]` describe the behaviour in domain words, never HTTP status codes or their names: `nationalTeamWithCompetitionCountryIsRejected`, not `...Returns400` / `...ReturnsBadRequest`. The status code belongs in the assertion (`assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST)`). Name the subject and the outcome (`...IsRejected`, `...IsCreated`, `...KeepsStoredGender`), and the condition when it matters (`...WithoutTeam...`).
- Put repeated request setup (building and submitting a form, finding an entity) in private helpers in the test class or in `ControllerTestTrait`, not copy-paste between tests.
- Every new validation rule has a test for the valid and the invalid case (asserting the error field).

## Git

- Never run `git add` or `git commit` on your own. After changing files, always ask the user whether to stage and commit (and how to split the commits) – wait for an explicit yes.
- Renames/moves of tracked files (`git mv`) are fine as part of the change, but leave staging the rest of the work to the user's decision.
- PR descriptions: English, short keyword bullets grouped by area, no filler – template and example in `.claude/skills/pull-request-description/SKILL.md`.

## Security

- Don't commit secrets. Keep local values in `api/.env.local`, not in `api/.env`.
- Don't disable PHPStan / Sonar rules with annotations (`@phpstan-ignore`, `NOSONAR`) – fix the root cause.
