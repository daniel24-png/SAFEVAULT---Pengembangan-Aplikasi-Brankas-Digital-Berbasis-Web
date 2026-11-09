# INIT.md - SAFEVAULT Project Bootstrap and Execution Sequence

## 1. Purpose

This file is the entry point for a new Antigravity session or a new SAFEVAULT development phase. It records the existing baseline, tells the agent which context to load, and identifies decisions that must be settled before the affected implementation begins.

SAFEVAULT is an existing PHP project. Do not scaffold a replacement application and do not migrate the project to Python, FastAPI, Streamlit, Next.js, SQLite, or another stack unless the developer explicitly authorizes that change.

## 2. Initialization Protocol

At the beginning of a task, follow this sequence:

1. Read `AGENTS.md`.
2. Read `context/000-project-overview-and-architecture.md`.
3. Inspect the actual repository tree, current Git status, and relevant source files. Do not assume that this context pack is newer than the live implementation.
4. Identify the requested version and current phase.
5. Load only the task-specific context files listed in `AGENTS.md`.
6. Check the relevant decision gates below. Ask only when an unresolved decision affects the requested work.
7. Propose a focused implementation plan. Do not modify unrelated files.

## 3. Environment and Repository Baseline

### Observed local environment

| Item | Recorded value |
| :--- | :--- |
| Operating environment | Windows with XAMPP |
| Web server | Apache 2.4.58 (Win64) |
| PHP | 8.2.12 |
| Database server | MariaDB 10.4.32 |
| Database name | `safevault_db` |
| Local application URL | `http://localhost/safevault/` |
| Reported active path | `C:\xampp\htdocs\safevault` |
| Reported junction target | `C:\xampp\htdocs\savefault` |

The `safevault` path was reported as a Windows junction to `savefault`. Preserve the working URL and existing path arrangement. Do not rename either folder or recreate the junction without explicit approval.

The repository uses PHP and PDO with MariaDB. Confirm exact paths, extensions, and configuration from the live repository before issuing commands or changing files.

### v0.1 baseline

**Status: IMPLEMENTED AND VERIFIED ACCORDING TO THE RECORDED TEST REPORT AND DEVELOPER MANUAL CHECK.**

Implemented foundation:

- Architecture: PHP 8.2 with MariaDB accessed via PDO with native prepared statements.
- Code placement: operational v0.1 logic resides in `includes/` (bootstrap, functions, CSRF) and `public/` (index, login, register, dashboard, logout, assets).
- Scaffolding: `app/` subdirectories (`controllers`, `models`, `services`, `views`) are empty scaffolding directories containing only `.gitkeep` and an `.htaccess` rule denying web access.
- Runtime storage: the `storage/` directory does not currently exist physically on disk. It will be created at runtime when required by future vault features. Do not create it manually during documentation tasks.
- Database: active tables are `users` and `login_attempts` in `safevault_db`. No dongle, biometric, note, or file tables exist yet.
- Public homepage: `public/index.php`.
- User registration and login using username or email: `public/register.php` and `public/login.php`.
- Authenticated dashboard and logout: `public/dashboard.php` and `public/logout.php`.
- Argon2id password hashing with PHP password hashing functions (`password_hash`, `password_verify`).
- PDO database access and prepared statements with emulation disabled.
- CSRF protection for relevant POST operations (`includes/csrf.php`).
- HTML output escaping with `e()` helper (`htmlspecialchars`).
- Session ID regeneration after successful credential verification.
- Login rate limiting by account and IP (5 failed attempts within 15 minutes).
- Idle timeout of 15 minutes and absolute timeout of 8 hours.
- HttpOnly and SameSite=Lax session cookies; Secure is conditional on HTTPS.
- Apache directory and sensitive-file protection (`.htaccess`).
- CSP, Permissions-Policy, X-Frame-Options, X-Content-Type-Options, and Referrer-Policy.
- Regression suite at `tests/test_safevault.php` and DB audit at `tests/audit_db_real.php`.

Recorded verification evidence:

- Apache configuration test returned `Syntax OK`.
- `mod_headers` and `mod_rewrite` were reported active.
- Actual Apache GET responses showed the CSP and Permissions-Policy headers on homepage, login, and register pages without duplicate header values.
- Sensitive directories/files returned 403 in the reported checks, while public pages returned 200.
- The regression suite reported 43/43 passing tests and exit code 0.
- The database audit reported two existing user records and no test-induced data changes.
- The developer separately confirmed manual dashboard verification.

The report did not expose the dashboard's raw HTTP response headers in the attached report, so do not claim that exact response was independently captured by the report. The developer's manual dashboard check is recorded as completed. Re-test after any shared bootstrap or security-header change.

Do not include real usernames, emails, password hashes, session identifiers, private data, or database dumps in context files.

## 4. Version Roadmap

| Version | Scope | Status |
| :--- | :--- | :--- |
| v0.1 | PHP foundation, password authentication, core web flow, baseline security hardening, regression tests | Implemented and verified according to recorded report and manual check |
| v0.2 | Authentication enhancement: webcam Face Recognition prototype and USB flash-drive-based ECDSA P-256 challenge-response | Next planned phase |
| v0.3 | Secret Notes: Vigenere classical transformation followed by AES-256-GCM encryption | Planned |
| v0.4 | Secure File Vault: ChaCha20-Poly1305 file encryption | Planned |
| v0.5 | Image steganography: encrypted payload embedded in PNG using LSB, with extraction | Planned |
| v0.6 | Blockchain-style integrity ledger: SHA-256 hash chain for registered vault assets and verification | Planned |
| v1.0 | Integration, security review, end-to-end testing, documentation, and lecturer demonstration | Planned |

Do not begin a later version while the active version's acceptance tests are failing, unless the developer explicitly chooses to proceed with a documented blocker.

## 5. Context Routing

| Task | Read these files |
| :--- | :--- |
| Architecture, scope, roadmap, repository structure | `context/000-project-overview-and-architecture.md` |
| Database tables, schema, persistence, storage | `context/001-database-and-storage-schema.md` |
| Password, sessions, USB dongle, face recognition | `context/002-authentication-dongle-and-biometrics.md` |
| Secret Notes, Vigenere, AES-GCM, key handling | `context/003-secret-notes-encryption.md` |
| Encrypted file storage and decryption | `context/004-secure-file-vault.md` |
| PNG steganography, asset hash chain, UI integration | `context/005-steganography-blockchain-and-ui-integration.md` |
| Regression, security, crypto, integration, demo tests | `context/006-testing-and-demo-scenarios.md` |

Read `000` for every cross-cutting feature. Avoid loading all numbered contexts for a narrow task.

## 6. Open Decision Gates

The gates below are intentionally separate from the agreed roadmap. A roadmap item can be planned while low-level details remain undecided. Resolve only the gate relevant to the current feature before implementation.

| ID | Topic | Current status | Required resolution |
| :--- | :--- | :--- | :--- |
| `SV-DEC-01` | Face recognition processing, model, threshold, and liveness | DECISION REQUIRED | Resolve face recognition processing location (client JS vs server-side), model/library, matching metric and numerical threshold, and liveness detection scope. Do not assume browser-generated face embeddings are secure against client-side manipulation. |
| `SV-DEC-02` | Biometric enrollment, storage, deletion, and re-enrollment | DECISION REQUIRED | Define how facial reference data is enrolled with user consent, where reference templates are stored, how they are protected from public access, and how users can delete or re-enroll. |
| `SV-DEC-03` | USB dongle private key format, challenge expiry, and replay prevention | DECISION REQUIRED | Define USB private key file format (PEM, DER, JSON), challenge length/randomness, challenge expiration window (e.g. 60 seconds), one-time consumption logic, and ECDSA P-256 signature verification in PHP. |
| `SV-DEC-04` | USB drive detection, enrollment, backup, and recovery | DECISION REQUIRED | Define USB drive detection and communication mechanism in the browser/OS, public-key enrollment binding, lost/damaged USB drive recovery, and key revocation. |
| `SV-DEC-05` | Vigenere character handling | DECISION REQUIRED | Confirm whether the cipher shifts only ASCII letters A-Z/a-z and preserves other characters, or uses another explicitly specified alphabet. |
| `SV-DEC-06` | AES-256-GCM payload format and key lifecycle | DECISION REQUIRED | Confirm nonce/tag serialization, key source, rotation/recovery approach, and versioning. Never store keys in the database or source control. |
| `SV-DEC-07` | Database encryption scope for Secret Notes | DECISION REQUIRED | Confirm which note fields are encrypted, whether application-level AES-GCM is the sole layer or an additional database-field layer is required, and how keys are separated. |
| `SV-DEC-08` | Secure File Vault storage format and limits | DECISION REQUIRED | Confirm maximum upload size, permitted file types, storage naming, nonce/ciphertext serialization, retention, and download authorization. |
| `SV-DEC-09` | Steganography payload encryption and framing | DECISION REQUIRED | Confirm encryption/key choice, payload header/version/length framing, delimiter or length-prefix strategy, and maximum image size. The agreed concept is encrypt first, then embed in PNG LSB. |
| `SV-DEC-10` | Blockchain asset definition and ledger scope | DECISION REQUIRED | Define what counts as an asset, which metadata is hashed, ownership semantics, event types, and retention. Default concept is a local SHA-256 hash chain, not a distributed blockchain. |
| `SV-DEC-11` | Lecturer rubric traceability | PARTIALLY DOCUMENTED | Map features to the exact lecturer rubric when the final rubric is available. Do not import unknown criteria from the unrelated source template documents. |

Do not ask the developer to resolve every gate at once. Ask only the decision needed for the task currently being implemented.

## 7. Development Environment Checks

Before running application commands, inspect the existing README, composer/dependency files if present, PHP extensions, Apache configuration, and database configuration. Do not assume Composer is used or required.

Typical local workflow, subject to the actual repository configuration:

1. Start Apache and MariaDB through XAMPP.
2. Confirm the active local URL responds.
3. Run the existing regression command from the project root:

   ```powershell
   php tests/test_safevault.php
   ```

4. Use the project's existing database initialization/migration process. Do not reset the database to obtain a clean test state.
5. Verify security headers and `.htaccess` behavior through Apache, not PHP's built-in server.

## 8. Current Next Task

The next planned phase is **SAFEVAULT v0.2: Authentication Enhancement**.

Recommended execution order:

1. Inspect the existing login, registration, session, bootstrap, database, configuration, and CSRF code.
2. Produce a design proposal for the USB dongle and biometric integration without editing files.
3. Resolve only the relevant v0.2 decision gates.
4. Implement dongle enrollment and challenge-response in a small, testable increment.
5. Implement face enrollment and verification in a separate increment.
6. Integrate the factors into login while preserving the existing Argon2id path as the knowledge factor.
7. Run focused tests, full regression tests, manual browser checks, and database-integrity checks.

Do not implement v0.2 immediately from this INIT file alone. First inspect the current codebase and prepare the design proposal.
