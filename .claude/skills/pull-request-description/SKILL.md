---
name: pull-request-description
description: Write a pull request title and description for this repo in the owner's preferred style – short keyword bullets grouped by area, no filler. Use when asked to prepare/write a PR description or title ("przygotuj opis PR", "opis pull request", "PR description"), or right after pushing a branch that needs a PR to main.
---

# Pull request title and description

The owner wants PR descriptions that read like a human changelog: short keyword bullets, no AI filler. Write them in English (like the commits), talk to the user in their language.

## 1. Collect facts (don't guess)

```bash
git log --oneline main..HEAD          # every commit in the PR – including earlier, unpushed ones
git diff --stat main...HEAD           # touched areas
git diff --name-only main...HEAD -- api/migrations   # migrations, if any
```

- Cover **all** commits in `main..HEAD`, not only the ones from this session. Say how many commits the PR contains.
- Check results (tests, PHPStan, phpcs, ESLint, tsc, `composer audit`) only if they were actually run on the final state. Never invent numbers.

## 2. Title

- One line, ≤ ~80 characters, lists the main topics: `Gender categories, competition/team countries, result validation`.
- No prefixes like `feat:`, no trailing period.

## 3. Description template

```markdown
## Changes

**<Area 1>**
- <what changed, a few words>
- <old> → <new> for renames, versions, counts

**<Area 2>**
- ...

## Migrations
- `<table.column>` – <one-line note>

After deploy: <manual steps, if any>.

## Checks
- <N> tests, PHPStan, phpcs, ESLint, tsc – OK

🤖 Generated with [Claude Code](https://claude.com/claude-code)
```

Rules:
- Group bullets by area with bold headers (e.g. **Gender**, **Countries**, **Validation**, **Security**, **Tooling**, **Tests**, **Fixes**). Only areas that the PR touches.
- Each bullet: a short phrase, not a sentence. No "This PR…", no "improves overall quality", no repeated explanations.
- Use `→` for changes of version, value or behaviour (`Twig 3.21 → 3.30`, `Empty gender → form error instead of 500`).
- Name classes, files, commands and config keys in backticks when they help a reviewer find the change.
- `## Migrations` only when the PR adds migrations; add an `After deploy:` line when data needs manual follow-up.
- `## Checks` lists only what was verified. Mention known leftovers in one bullet (e.g. `composer audit: 50 → 32 (rest = Symfony 7.3)`).
- Keep the attribution line last; tell the user once that they can delete it.

## 4. Hand-off

- Give the title and description as separate code blocks, ready to paste.
- Give the compare link with the form open:
  `https://github.com/gajdakrz/sport-scores/compare/main...<branch>?expand=1`
  The green **Create pull request** button is under the description field, on the right.
- CI (`.github/workflows/ci.yaml`) runs only on pushes to `main` and PRs to `main` – a pushed branch alone triggers nothing. After the PR exists, ask for its number and check CI and SonarCloud (`&pullRequest=<number>`, see AGENTS.md → SonarCloud).

## Example

Title:
```
English comments, safer login check, unused-import tooling, dependency security fixes
```

Description:
```markdown
## Changes

**English only**
- Polish comments → English (src, tests, assets/ts, eslint config)
- Login page and account status message in English

**Security**
- Deactivated account checked after the password (`checkPostAuth`) – wrong password gives the same error as an unknown account
- Twig 3.21 → 3.30 (critical `{% use %}` injection + others), PHPUnit 12.5, PHP_CodeSniffer 3.13.6
- `composer audit`: 50 → 32 advisories (rest = Symfony 7.3, needs 7.4 LTS upgrade)

**Tooling**
- Slevomat `UnusedUses` rule in phpcs – unused imports fail lint
- `make fix` – removes unused imports automatically

**Tests**
- Login tests for a deactivated account (wrong / correct password)
- 87 PHPUnit 12.5 notices fixed: stubs instead of mocks without expectations

## Checks
- 401 tests, PHPStan, phpcs, ESLint, tsc – OK

🤖 Generated with [Claude Code](https://claude.com/claude-code)
```
