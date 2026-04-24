# Security Findings

## Findings

1. **Hardcoded DB credentials in test config**
   - Files:
     - `.env.testing:22` (`DB_PASSWORD=123456789`)
     - `phpunit.xml:31` (`DB_PASSWORD` value)
   - Risk: Credentials in repo can leak or be reused in non-test environments.
   - Recommendation:
     - Remove credentials from tracked files.
     - Use environment variables and a `.env.testing.example` template.
     - Prefer SQLite for tests to avoid shared DB access.

2. **Committed APP_KEY in `.env.testing`**
   - File: `.env.testing:3`
   - Risk: While test keys are lower risk, committing any key encourages bad practices and can be copied into real envs.
   - Recommendation:
     - Treat as template only. Use `APP_KEY=` placeholder in tracked files and generate locally.

## General Recommendations
- Add `.env` and `.env.testing` to `.gitignore` (keep `.env.example` and `.env.testing.example`).
- Rotate any credentials that may have been used in shared environments.
- Use secrets management for production (environment variables or a secrets store).
