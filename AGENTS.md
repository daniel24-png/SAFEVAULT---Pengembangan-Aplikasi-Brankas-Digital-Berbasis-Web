# AGENTS.md - SAFEVAULT Master Instructions for AI Coding Agents

## 1. Project Identity and Mission

You are the coding agent assigned to the **SAFEVAULT** repository. SAFEVAULT is a PHP-based personal digital vault project for an academic cryptography and application-security assignment. The application is being developed incrementally from an existing PHP 8.2, Apache, and MariaDB foundation.

The current repository is not a new project scaffold. SAFEVAULT v0.1 already exists and has been implemented and tested. Preserve the existing codebase and its working behavior. Do not replace it with Python, FastAPI, Streamlit, Next.js, SQLite, or another stack unless the developer explicitly approves a migration.

The product is a personal vault, not a multi-user chat application. Do not import messaging, recipient, inbox, message-transfer, or multi-user assumptions from unrelated project templates.

## 2. Mandatory Context Loading and Routing

Before implementing a feature or fixing a bug:

1. Read `INIT.md` for the current phase, environment, open decision gates, and execution sequence.
2. Read `context/000-project-overview-and-architecture.md` for the project architecture and current implementation status.
3. Read only the numbered context files relevant to the requested task, using the routing table below.
4. Inspect the actual repository files before proposing or changing implementation details. The live code and database schema are authoritative for what currently exists.

| File | Scope | Read when |
| :--- | :--- | :--- |
| `INIT.md` | Project bootstrap, verified baseline, roadmap, decision gates | First session or before starting a new version/phase |
| `context/000-project-overview-and-architecture.md` | Product scope, requirement map, stack, module boundaries, repository layout | Architecture, planning, or cross-cutting changes |
| `context/001-database-and-storage-schema.md` | MariaDB schema, persistence, encrypted data, storage paths | Database, repository, or file-storage work |
| `context/002-authentication-dongle-and-biometrics.md` | Argon2id baseline, session protections, USB dongle, face recognition | Authentication and v0.2 work |
| `context/003-secret-notes-encryption.md` | Vigenere + AES-256-GCM design for Secret Notes | Secret Notes and text encryption work |
| `context/004-secure-file-vault.md` | ChaCha20-Poly1305 file encryption | Secure File Vault work |
| `context/005-steganography-blockchain-and-ui-integration.md` | PNG LSB, hash-chain ledger, PHP UI integration | Steganography, asset ledger, and cross-feature integration |
| `context/006-testing-and-demo-scenarios.md` | Regression, security, cryptographic, integration, and demo tests | Testing, release verification, or lecturer demonstrations |

Do not load every context file for every small task. Load only the context needed for the change.

## 3. Source-of-Truth and Status Labels

Use these meanings in plans, reports, comments, and documentation:

- **IMPLEMENTED AND VERIFIED**: Present in the repository and supported by a recorded test or runtime check.
- **IMPLEMENTED, VERIFICATION LIMITED**: Present in code, but one or more runtime paths have not been tested.
- **PLANNED**: Agreed roadmap scope, not yet implemented.
- **DECISION REQUIRED**: A material design detail is unresolved. Do not silently choose it.
- **NOT IN SCOPE**: Explicitly excluded from the current project.

Never describe a planned feature as implemented. Never claim a test passed unless it was actually executed and its result was observed. Distinguish automated tests, manual tests, code inspection, and Antigravity-reported results.

## 4. Strict Engineering and Communication Rules

1. Do not use emojis in source code, comments, commit messages, documentation, logs, UI labels, or terminal output.
2. Do not use em dashes or en dashes in repository documentation, code comments, strings, or generated reports. Use ordinary ASCII hyphens, colons, commas, or parentheses.
3. Write source code, identifiers, function names, database names, comments, and technical logs in English. Use Indonesian for end-user UI labels and lecturer-facing demonstration messages unless the existing UI convention requires otherwise.
4. Do not guess when a requirement, cryptographic format, key lifecycle, route, schema, or file path is unclear. Inspect the repository first. Ask the developer before making a consequential design choice.
5. Keep changes small, focused, and traceable to the active task. Do not perform broad refactoring while implementing a feature.
6. Preserve the working v0.1 baseline. Do not reset, truncate, seed over, or delete the user's existing database or accounts.
7. Do not create, print, commit, or expose real secrets, private keys, passwords, biometric images, or production user data.

## 5. Existing Technology and Security Baseline

The observed development environment is Windows with XAMPP, Apache 2.4.58, PHP 8.2.12, and MariaDB 10.4.32. The active local URL used for verification is `http://localhost/safevault/`. The local `safevault` path has been reported as a junction to `C:\xampp\htdocs\savefault`; do not rename folders, alter the junction, or hardcode either path into application logic without explicit approval.

The database name is `safevault_db`. The existing project uses PHP and PDO for MariaDB access. The only existing database tables are `users` and `login_attempts`. Operational logic currently resides in `includes/` and `public/`, while `app/` subdirectories (`controllers`, `models`, `services`, `views`) are currently empty scaffolding directories. The `storage/` directory does not currently exist on disk and is planned as a runtime directory to be created only when future vault features require it.

The v0.1 baseline includes:

- Homepage, registration, login, authenticated dashboard, and logout.
- Argon2id password hashing using PHP password hashing functions.
- PDO prepared statements with native prepares configured.
- CSRF token protection for POST flows (`includes/csrf.php`).
- Output escaping with `htmlspecialchars` through the existing helper `e()`.
- Session ID regeneration after successful login.
- Login rate limiting: five failed attempts within a 15-minute window, scoped by account and IP, with throttling on the next attempt after the threshold is reached (`login_attempts` table).
- Idle session timeout of 15 minutes and absolute timeout of 8 hours.
- Session cookie protections including HttpOnly and SameSite=Lax; Secure is conditional on HTTPS.
- Internal directory and sensitive-file access restrictions through Apache configuration and `.htaccess`.
- HTTP security headers including CSP, Permissions-Policy, X-Frame-Options, X-Content-Type-Options, and Referrer-Policy.
- A regression suite at `tests/test_safevault.php` and DB audit at `tests/audit_db_real.php`.

The v0.1 verification report recorded 43/43 passing regression tests, Apache configuration syntax validation, actual HTTP response checks for the public pages, directory-protection checks, and database integrity checks. The developer also confirmed manual dashboard verification. Treat these as the recorded baseline; rerun relevant checks after changes.

## 6. Security Engineering Rules

### 6.1 PHP and Database

- Use PDO prepared statements for all variable SQL values.
- Preserve native prepared statements where the current connection configuration uses them.
- Validate data on the server. Client-side validation is supplementary only.
- Escape untrusted output for its HTML context. Reuse the existing escaping helper where appropriate.
- Apply CSRF validation to state-changing requests.
- Preserve authentication guards on protected pages and actions.
- Keep local credentials and cryptographic keys outside committed source files. Follow the existing local configuration pattern and `.gitignore`.
- Never expose raw database credentials, password hashes, secrets, or private keys in UI or logs.

### 6.2 Modern Cryptography

- Use established cryptographic libraries and authenticated encryption for modern data protection. Do not invent a modern cipher or mode.
- Vigenere is included only as an academic classical-cipher demonstration. It is not a substitute for modern encryption and must not be described as secure by itself.
- For AES-GCM, use a fresh nonce for every encryption under the same key. Authenticate before returning plaintext. Reject invalid tags and malformed ciphertext.
- For ChaCha20-Poly1305, use a unique nonce per key and reject authentication failures. Do not reuse the text-encryption key.
- Keep keys out of the database and source control. Separate keys by purpose. Do not invent a key-derivation scheme or key-storage lifecycle; follow the approved context decision.
- Do not invent an RSA key-distribution layer. SAFEVAULT is a personal vault, and multi-user key exchange is not part of the current scope.

### 6.3 USB Dongle and Biometrics

- A normal USB flash drive containing a private-key file is a software-based possession factor, not a hardware-bound security key. The file can be copied. Documentation and demonstrations must state this limitation accurately.
- ECDSA P-256 is for signing and challenge-response authentication, not for encrypting vault content.
- Use unpredictable, one-time, expiring challenges and verify signatures against the enrolled public key once that design is approved.
- Do not claim production-grade biometric liveness or anti-spoofing unless a real presentation-attack-detection mechanism has been implemented and evaluated.
- Treat biometric reference data as sensitive. Restrict access, avoid logging it, and define deletion/re-enrollment behavior before implementation.

### 6.4 Apache and Runtime Verification

- `.htaccess` behavior must be tested through Apache. PHP's built-in development server does not process `.htaccess` and cannot prove Apache rewrite or directory protection behavior.
- Validate actual HTTP responses when testing security headers. Source-code presence alone is not sufficient evidence.
- Do not add CSP exceptions such as `unsafe-inline` or `unsafe-eval` merely to silence errors. Inspect the blocked resource and choose the narrowest viable fix.
- `Permissions-Policy: camera=(self)` is intended to permit camera use only for the same origin; browser permission is still required.
- Do not force HSTS on local HTTP. Evaluate HSTS only when the application is deployed over HTTPS.

## 7. Change and Test Workflow

For each task:

1. State the relevant context files and inspect the current implementation.
2. Summarize the smallest intended change before editing when the task is cross-cutting or security-sensitive.
3. Check for existing behavior and tests before adding duplicate logic.
4. Implement only the requested scope.
5. Run the focused test first, then the complete regression suite when practical.
6. For security-sensitive changes, include a negative test as well as a success test.
7. Verify that the existing database and user records remain intact.
8. Report changed files, commands run, exact test totals, observed failures, and any unverified areas.

Use isolated test data or a temporary test database for destructive test scenarios. Never run destructive tests against the user's live `safevault_db`.

## 8. Stop and Ask Conditions

Stop and ask the developer before coding if:

- The request implies changing the PHP/MariaDB architecture or migrating frameworks.
- A cryptographic mode, key size, key derivation, nonce format, ciphertext serialization, or key recovery behavior is not specified.
- A USB enrollment, recovery, or first-use bootstrap rule is unclear.
- A biometric library, face-matching threshold, webcam access method, or liveness expectation materially affects implementation and is unresolved.
- A new database table or migration could affect existing user data and the migration strategy is not clear.
- A lecturer criterion is being added, removed, or reinterpreted without the actual rubric.
- The live repository differs materially from these documents.

Do not stop for harmless implementation details that can be resolved by inspecting existing code and tests. Explain any unresolved decision precisely and ask only the most useful question.
