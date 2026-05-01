# SportScores

A sports results management application built with **Symfony 7** (backend) and **TypeScript** (frontend), running in a **Docker** environment.

---

## Requirements

| Tool           | Minimum version |
|----------------|-----------------|
| Docker         | 24+             |
| Docker Compose | 2+              |
| Make           | 3.8+            |

No local PHP or Node.js installation required — everything runs inside containers.

---

## Tech Stack

- **PHP** 8.4 + **Symfony** 7
- **Doctrine ORM** + migrations
- **PostgreSQL** 16
- **TypeScript** + Webpack Encore
- **PHPUnit** + **Xdebug** (coverage)
- **Docker** / **Docker Compose**
- **Makefile** as the primary command interface

---

## Installation & Setup

### 1. Clone the repository

```bash
git clone <repo-url>
cd sport-scores
```

### 2. Configure environment

```bash
cp .env .env.local
```

Fill in `.env.local` with your local values (database credentials, secrets, etc.).

### 3. Start containers

```bash
docker compose up -d
```

This starts three services:

| Container               | Description                                              | Port          |
|-------------------------|----------------------------------------------------------|---------------|
| `sport-scores-php`      | PHP 8.4-fpm + Nginx                                      | `8080 → 80`   |
| `sport-scores-postgres` | PostgreSQL 16                                            | `5432 → 5432` |
| `sport-scores-node`     | Node 20 — runs `npm install && npm run watch` on startup | —             |

### 4. Install PHP dependencies

```bash
make composer-install
```

### 5. Frontend

The `sport-scores-node` container runs `npm install && npm run watch` automatically on startup — no manual step needed during development.

For a one-off production build, run it inside the container:

```bash
docker exec sport-scores-node npm run build
```

### 6. Set up the database

```bash
make db          # creates database + runs migrations + loads fixtures (dev)
```

The application will be available at: **http://localhost:8080**

---

## Makefile Commands

### Docker & Application

> Commands for managing containers and the application (e.g. `make up`, `make bash`, `make cache-clear`) — update this section with your actual Makefile targets.

### Testing

| Command            | Description                                                                      |
|--------------------|----------------------------------------------------------------------------------|
| `make test`        | Rebuild test database (`test-db`) and run PHPUnit                                |
| `make test-db`     | Drop → create → migrate → load fixtures on test DB (with TTY — terminal use)    |
| `make test-db-ide` | Drop → create → migrate → load fixtures on test DB (without TTY — IDE use)      |

All `test-db*` targets use `--purge-with-truncate` and a 30-second timeout on fixture loading to prevent hanging.

---

## Testing

The project uses **PHPUnit**. The test database (`sport-scores-db_test`) is separate from the development database and is fully rebuilt (drop → create → migrate → fixtures) before each test run.

```bash
# Rebuild test database and run the full test suite
make test

# Rebuild test database only (useful when running tests manually from the IDE)
make test-db
```

### PhpStorm Configuration

To run tests and coverage from the IDE:

1. **Settings → PHP** → set the CLI Interpreter to the `sport-scores-php` container (Docker).
2. **Settings → Tools → External Tools** → add a new tool:
    - Program: `make`
    - Arguments: `test-db-ide`
    - Working directory: `$ProjectFileDir$`
3. In your PHPUnit run configuration, add the external tool above under **Before Launch**.

---

## Architecture

### Backend (Symfony 7)

```
src/
├── Controller/
│   └── BaseController.php                      # CSRF validation, shared logic
├── EventListener/
│   └── CustomBadRequestExceptionListener.php   # global error handler
├── DataFixtures/
│   └── TestUserFixture.php
├── Entity/
│   └── AbstractEntity.php                      # createdAt, modifiedAt (lifecycle callbacks)
└── ...
```

**Key conventions:**

- Controllers extend `BaseController`, which provides `validateCsrfToken()` as shared CSRF validation logic.
- Service-layer exceptions (`CustomBadRequestException`) are caught by `CustomBadRequestExceptionListener` and returned as JSON following RFC 7807:
  ```json
  { "errors": [{ "message": "...", "field": "..." }] }
  ```
- AJAX responses never use `addFlash()` — messages are passed directly in the JSON payload.
- Timestamps (`createdAt`, `modifiedAt`) are managed automatically by Doctrine lifecycle callbacks defined in `AbstractEntity`.

### Frontend (TypeScript + Bootstrap)

The frontend is written in **TypeScript** and styled with **Bootstrap 5**. Assets are bundled via **Webpack Encore**.

```
assets/
├── app/
│   ├── AppBase.ts              # base class: flash messages, shared utilities
│   ├── types.ts                # FlashType union type, constants (FLASH_TIME_MS)
│   └── [feature]/
│       └── ModalInitializer.ts # init() — modal setup + flash message bootstrap
└── ...
```

**Key conventions:**

- Each feature area has its own entry point with a `ModalInitializer` class and an `init()` method.
- `init()` must call `AppBase.initPendingFlash()` to handle flash messages after page reload (via `sessionStorage`).
- Flash messages after AJAX + reload flow: `AppBase.showFlashAfterReload()` → `window.location.reload()`.



---

## Environment Variables

| Variable       | Description                        |
|----------------|------------------------------------|
| `DATABASE_URL` | PostgreSQL connection string       |
| `APP_ENV`      | Environment: `dev`, `prod`, `test` |
| `APP_SECRET`   | Symfony application secret         |

Example `.env.local`:

```dotenv
DATABASE_URL="postgresql://app:!ChangeMe!@127.0.0.1:5432/app?serverVersion=16&charset=utf8"
APP_SECRET=your-secret-here
```

---

## Project Structure

```
sport-scores/
├── api/                        # Symfony application root (mounted into container)
│   ├── assets/
│   │   ├── styles/
│   │   │   └── app.css
│   │   └── ts/
│   │       ├── bracket/
│   │       ├── common/
│   │       ├── competition/
│   │       ├── country/
│   │       ├── event/
│   │       ├── game/
│   │       ├── gameResult/
│   │       ├── home/
│   │       ├── memberPosition/
│   │       ├── person/
│   │       ├── season/
│   │       ├── sport/
│   │       ├── team/
│   │       ├── teamMember/
│   │       └── app.ts
│   ├── config/
│   ├── migrations/
│   ├── public/                 # Web root (index.php, compiled assets)
│   ├── src/
│   │   ├── Command/
│   │   ├── Controller/
│   │   ├── DataFixtures/
│   │   ├── Dto/
│   │   ├── Entity/
│   │   ├── Enum/
│   │   ├── EventListener/
│   │   ├── Exception/
│   │   ├── Form/
│   │   ├── Helper/
│   │   ├── Model/
│   │   ├── Repository/
│   │   ├── Security/
│   │   ├── Service/
│   │   ├── Twig/
│   │   ├── Validator/
│   │   └── Kernel.php
│   ├── templates/
│   ├── tests/
│   └── .env
├── docker-compose.yml
└── Makefile
```

---

## Screenshots

> Screenshots are stored in `docs/screenshots/`. Add images there and they will render automatically on GitHub.

**Menu**

![Menu](docs/screenshots/menu.png)

**Game index**

![Game index](docs/screenshots/game-index.png)

**Game new record modal**

![Game new record modal](docs/screenshots/game-new-record-modal.png)

**Team index**

![Team index](docs/screenshots/team-index.png)

**Team details**

![Team details](docs/screenshots/team-details.png)

**Team bracket**

![Team bracket](docs/screenshots/team-bracket.png)

**Team members index**

![Team members index](docs/screenshots/team-members-index.png)

---

## Code Quality

The project is verified with static analysis and code style tools on every change.

### PHPStan — level 9

Static analysis at the maximum strictness level — no errors reported.

```bash
docker exec sport-scores-php vendor/bin/phpstan analyse
```

### PHP CodeSniffer — PSR-12

Code style enforced against the PSR-12 standard, applied to the `tests/` directory — no violations reported.

```bash
docker exec sport-scores-php vendor/bin/phpcs tests/ --standard=PSR12
```

### ESLint

TypeScript frontend is linted with ESLint across all source files — no errors reported.

```bash
docker exec sport-scores-node npm run lint
# runs: eslint "assets/ts/**/*.ts"
```

### SonarQube

Code quality continuously verified via the **SonarQube** (12.2.0.84584) plugin integrated into PhpStorm — no open issues.

---

## License

MIT License

Copyright (c) 2026 Krzysztof Gajda

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

## Contact

Krzysztof Gajda
[gajda.krzysztof@gmail.com](mailto:gajda.krzysztof@gmail.com)
[linkedin.com/in/krzysztofgajda](https://www.linkedin.com/in/krzysztofgajda)
[github.com/gajdakrz](https://github.com/gajdakrz/)