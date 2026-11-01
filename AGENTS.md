# AGENTS.md

Aplikacja do zarządzania wynikami sportowymi: Symfony 7.3 + PHP 8.4, Doctrine ORM 3, PostgreSQL, frontend TypeScript + Bootstrap 5 (Webpack Encore). Kod aplikacji jest w `api/`, wszystko działa w Dockerze.

## Komendy

PHP i Node nie są zainstalowane lokalnie – komendy uruchamiaj w kontenerach (katalog roboczy kontenera = `api/`).
Agent nie ma TTY: używaj `docker exec` **bez** `-it` (dlatego `make test` i `make test-db` nie zadziałają).

```bash
docker compose up -d                                                  # start środowiska
make test-db-ide                                                      # odbudowa bazy testowej (drop/create/migrate/fixtures)
docker exec sport-scores-php sh -c "APP_ENV=test php bin/phpunit"     # wszystkie testy
docker exec sport-scores-php sh -c "APP_ENV=test php bin/phpunit tests/Unit/Service/BracketBuilderTest.php"  # jeden plik
docker exec sport-scores-php vendor/bin/phpstan analyse --no-progress --memory-limit=1G # PHPStan, level 9 (domyślne 128M nie wystarcza)
docker exec sport-scores-php vendor/bin/phpcs                         # PSR-12
docker exec sport-scores-node npm run lint                            # ESLint (assets/ts)
docker exec sport-scores-node npm run typecheck                       # tsc --noEmit
```

Pozostałe cele (`up`, `composer-install`, `db`, `cache-clear`) są w `Makefile` i działają bez TTY.

## Zanim uznasz zadanie za skończone

- PHPUnit, PHPStan (level 9) i phpcs przechodzą bez błędów. W CI lint i PHPStan mają `continue-on-error`, więc błąd nie zatrzyma pipeline'u – sprawdź lokalnie.
- Przy zmianach w `assets/ts/` przechodzą `lint` i `typecheck`.
- Nowy kod w `src/` ma testy – pokrycie trafia do SonarCloud (`coverage.xml`).

## Konwencje backendu

- Każdy plik PHP zaczyna się od `declare(strict_types=1);`.
- Kontrolery dziedziczą po `App\Controller\BaseController`: `Web/` – widoki Twig + AJAX, `Api/` – endpointy JSON.
- Błędy walidacji: `throwFormErrors($form)` lub `CustomBadRequestException`. Listener zamienia je na JSON `{ "errors": [{ "message", "field" }] }` – nie buduj tej odpowiedzi ręcznie.
- Akcje AJAX zmieniające dane sprawdzają CSRF przez `validateCsrfToken()`.
- W odpowiedziach AJAX nie używaj `addFlash()` – komunikat idzie w JSON-ie.
- `createdAt` / `modifiedAt` ustawia `AbstractEntity` (lifecycle callbacks) – nie ustawiaj ich ręcznie.
- Zmiana schematu = nowa migracja (`php bin/console make:migration`). Nie edytuj istniejących plików w `migrations/`.

## Konwencje frontendu

- Każdy moduł w `assets/ts/<feature>/` ma `ModalInitializer` z metodą `init()`, która wywołuje `AppBase.initPendingFlash()`.
- Komunikat po AJAX + przeładowaniu strony: `AppBase.showFlashAfterReload()`, potem `window.location.reload()`.

## Testy

- `tests/Unit`, `tests/Integration`, `tests/Functional` (kontrolery Web/Api, repozytoria). Wspólne helpery w `tests/Trait/ControllerTestTrait.php`.
- DAMA DoctrineTestBundle cofa każdy test w transakcji – testy nie muszą sprzątać bazy, ale wymagają załadowanych fixtures (`make test-db-ide`).

## Bezpieczeństwo

- Nie commituj sekretów. Lokalne wartości trzymaj w `api/.env.local`, nie w `api/.env`.
- Nie wyłączaj reguł PHPStan / Sonar adnotacjami (`@phpstan-ignore`, `NOSONAR`) – napraw przyczynę.