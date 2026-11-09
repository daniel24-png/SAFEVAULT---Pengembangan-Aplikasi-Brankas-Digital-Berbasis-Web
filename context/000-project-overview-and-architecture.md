# 000 - SAFEVAULT Project Overview and Architecture

## 1. System Summary

**SAFEVAULT** is a PHP-based personal digital vault. It is designed to protect private notes, files, image-based secret payloads, and records of important digital assets. It is a personal vault, not a chat or messaging platform.

The application is developed incrementally. The PHP web foundation and baseline security controls were implemented in v0.1. Subsequent versions add authentication factors and cryptographic vault features.

### Planned capability map

1. **Secure Authentication:** Existing Argon2id password login, followed by planned webcam Face Recognition and a software-based USB dongle using ECDSA P-256 challenge-response.
2. **Secret Notes:** Planned Vigenere classical transformation followed by AES-256-GCM authenticated encryption.
3. **Secure File Vault:** Planned ChaCha20-Poly1305 encryption for files, using a key separate from the text pipeline.
4. **Image Steganography:** Planned encrypted payload embedded into a lossless PNG using Least Significant Bit (LSB) manipulation.
5. **Asset Integrity Ledger:** Planned SHA-256 hash chain for recording important vault assets and verifying integrity.

Only the v0.1 foundation is currently implemented. Capabilities 1's additional factors and capabilities 2-5 remain planned until their version is completed and tested.

## 2. Project Scope and Requirement Traceability

The exact final lecturer rubric has not been reproduced in this context pack. Use this functional scope as the working project map, but do not invent lecturer criterion numbers or claim a criterion is satisfied without the actual rubric and a passing demonstration.

| Feature area | Working project requirement | Version | Current status |
| :--- | :--- | :--- | :--- |
| Basic authentication | Register, login, logout, and protected dashboard | v0.1 | IMPLEMENTED AND VERIFIED according to recorded checks |
| Password protection | Argon2id password hashing and safe verification | v0.1 | IMPLEMENTED AND VERIFIED according to recorded checks |
| Web application security | CSRF, output escaping, session protection, rate limiting, directory protection, security headers | v0.1 | IMPLEMENTED AND VERIFIED according to recorded checks |
| Biometric authentication | Webcam-based face verification prototype | v0.2 | PLANNED |
| USB possession factor | USB flash drive with ECDSA P-256 challenge-response | v0.2 | PLANNED |
| Secret Notes | Vigenere plus AES-256-GCM | v0.3 | PLANNED |
| Secure File Vault | ChaCha20-Poly1305 | v0.4 | PLANNED |
| Image Steganography | Encrypted payload embedded in PNG LSB | v0.5 | PLANNED |
| Asset integrity | SHA-256 hash-chained ledger | v0.6 | PLANNED |
| Full system integration | End-to-end test and demonstration | v1.0 | PLANNED |

## 3. Technology Stack

### Existing stack

- Operating environment: Windows with XAMPP for local development.
- Web server: Apache 2.4.58 (Win64) provided by XAMPP.
- Server-side language: PHP 8.2 (PHP 8.2.12).
- Database: MariaDB 10.4.32, accessed through PDO with native prepared statements.
- Database name: safevault_db.
- Operational code placement: operational v0.1 logic resides in includes/ (bootstrap, functions, CSRF) and public/ (entry points, views, assets).
- Scaffolding placement: app/ subdirectories (controllers, models, services, views) are currently empty scaffolding directories containing only .gitkeep and an .htaccess access-denial rule.
- Storage placement: the storage/ directory does not currently exist physically on disk. It is planned as a runtime directory that will be created when required by future vault features.
- Browser-facing application: PHP pages under the existing public/ directory, served with custom styling in public/assets/css/style.css and client scripts in public/assets/js/app.js.

Do not replace this stack with Python, FastAPI, Streamlit, Next.js, or SQLite. A helper process or service may be considered only if the developer approves it after a documented compatibility analysis.

### Existing local environment recorded during v0.1 verification

- Apache 2.4.58 (Win64).
- PHP 8.2.12.
- MariaDB 10.4.32.
- Database: safevault_db.
- Local URL: http://localhost/safevault/.
- C:\xampp\htdocs\safevault was reported as a junction to C:\xampp\htdocs\savefault.

These are observed local settings, not portable production constants. Inspect current environment settings before relying on them.

## 4. Architectural Principles

### 4.1 Existing PHP foundation

Preserve the current application entry points, bootstrap flow, session model, PDO connection, and local configuration mechanism. Inspect the actual project before adding new layers. The documents describe intent, not permission to restructure working code.

### 4.2 Separation of responsibilities

As the project grows, keep responsibilities separated:

- Presentation: PHP pages, HTML, CSS, and JavaScript.
- Application flow: request validation, authentication checks, workflow coordination.
- Security/cryptography: small modules responsible for specific algorithms and validation.
- Persistence: PDO-based repositories or equivalent existing data-access code.
- Storage: files stored outside directly accessible public paths where practical.

Use the repository's established naming and structure. Do not create a new architecture in parallel with the existing one without approval.

### 4.3 Personal-vault model

SAFEVAULT is intended for one owner. Do not add chat recipients, inboxes, message routing, or user-to-user encryption/key exchange unless the developer explicitly changes scope. RSA-OAEP key distribution is not required by the current personal-vault design.

### 4.4 Key separation

Use separate keys for separate purposes. At minimum, do not reuse the AES text key for ChaCha20-Poly1305 file encryption. Keep keys outside the database and source control. Exact key derivation, storage, rotation, and recovery rules must be approved before implementation.

### 4.5 Cryptographic transparency

- Vigenere is a classical educational layer, not modern cryptographic protection.
- AES-256-GCM provides authenticated encryption when nonces are unique per key and tags are correctly verified.
- ChaCha20-Poly1305 provides authenticated encryption when nonces are unique per key and tags are correctly verified.
- LSB steganography hides a payload but does not itself encrypt it.
- A central SHA-256 hash chain is tamper-evident under its assumptions. It is not a decentralized blockchain and does not prevent a privileged operator from rewriting the entire database and recomputing the chain.

## 5. Repository Structure

The following structure accurately reflects the actual repository layout, distinguishing between existing implementation, empty scaffolding directories, and planned runtime paths:

```text
SAFEVAULT/
|-- AGENTS.md                                  # Coding-agent instructions and rules
|-- INIT.md                                    # Project bootstrap and execution sequence
|-- README-CONTEXT-PACK.md                     # Context pack overview
|-- README.md                                  # Project documentation and user manual
|-- .gitignore                                 # Git ignore rules (ignores database.local.php, etc.)
|-- .htaccess                                  # Root Apache protection and redirect to public/
|-- context/                                   # Task-specific context documents
|   |-- 000-project-overview-and-architecture.md
|   |-- 001-database-and-storage-schema.md
|   |-- 002-authentication-dongle-and-biometrics.md
|   |-- 003-secret-notes-encryption.md
|   |-- 004-secure-file-vault.md
|   |-- 005-steganography-blockchain-and-ui-integration.md
|   `-- 006-testing-and-demo-scenarios.md
|-- app/                                       # [EMPTY SCAFFOLDING] Reserved for future modular architecture
|   |-- .htaccess                              # Apache access denial (Require all denied)
|   |-- controllers/                           # Empty directory (.gitkeep)
|   |-- models/                                # Empty directory (.gitkeep)
|   |-- services/                              # Empty directory (.gitkeep)
|   `-- views/                                 # Empty directory (.gitkeep)
|-- config/                                    # Configuration directory
|   |-- .htaccess                              # Apache access denial (Require all denied)
|   |-- database.example.php                   # Versioned database configuration template
|   `-- database.local.php                     # [GITIGNORED] Active local database credentials
|-- database/                                  # Database migrations and DDL
|   |-- .htaccess                              # Apache access denial (Require all denied)
|   `-- schema.sql                             # DDL for safevault_db, users, and login_attempts
|-- includes/                                  # [EXISTING IMPLEMENTATION] Core operational logic
|   |-- .htaccess                              # Apache access denial (Require all denied)
|   |-- bootstrap.php                          # Session bootstrap, security headers, PDO singleton
|   |-- csrf.php                               # CSRF token generation and validation helpers
|   `-- functions.php                          # Sanitization, auth, rate limiting, and query helpers
|-- public/                                    # [EXISTING IMPLEMENTATION] Web server DocumentRoot
|   |-- index.php                              # Homepage
|   |-- login.php                              # User login flow with rate limiting
|   |-- register.php                           # User registration flow
|   |-- dashboard.php                          # Protected personal vault dashboard
|   |-- logout.php                             # CSRF-protected logout handler
|   `-- assets/                                # Static web assets
|       |-- css/
|       |   `-- style.css                      # Application stylesheet
|       |-- js/
|       |   `-- app.js                         # External client-side script (logout handler)
|       `-- images/                            # Static images directory (.gitkeep)
|-- storage/                                   # [PLANNED RUNTIME PATH] Does not physically exist yet
|                                              # Created at runtime when required by v0.3-v0.5 features
`-- tests/                                     # Automated test suites
    |-- .htaccess                              # Apache access denial (Require all denied)
    |-- test_safevault.php                     # 43/43 passing regression test suite
    `-- audit_db_real.php                      # Real MariaDB connection and schema audit
```

Important architectural distinction:
- Operational v0.1 logic is currently housed inside `includes/` and `public/`.
- The `app/` subdirectories (`controllers`, `models`, `services`, `views`) are empty scaffolding directories containing only `.gitkeep`. They do not currently contain operational classes or modules.
- The `storage/` directory does not currently exist on disk. It is defined as a runtime storage root to be created only when required by future encrypted-file, note-backup, or steganography features. Do not create it manually during documentation tasks.

## 6. v0.1 Implementation Baseline

The following capabilities represent the verified v0.1 baseline currently active in code:

- Operational foundation: PHP 8.2 with MariaDB accessed through PDO with native prepared statements.
- Web entry points: Homepage (`public/index.php`), registration (`public/register.php`), login (`public/login.php`), authenticated dashboard (`public/dashboard.php`), and logout (`public/logout.php`).
- Database tables: `users` and `login_attempts` in `safevault_db`.
- Password authentication: Argon2id password hashing using PHP `password_hash()` and `password_verify()`.
- CSRF protection: Cryptographic token generation and strict validation (`includes/csrf.php`) for all state-changing POST requests.
- Output escaping: Server-side escaping helper `e()` using `htmlspecialchars(..., ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`.
- Session fixation defense: Explicit `session_regenerate_id(true)` upon successful credential authentication.
- Login rate limiting: Five failed attempts within a 15-minute rolling window, enforced per account and per IP using the persistent `login_attempts` table.
- Session timeout management: Idle session timeout of 15 minutes and absolute session timeout of 8 hours, with safe session destruction on expiration.
- Session cookie flags: `HttpOnly` and `SameSite=Lax` enabled; `Secure` flag applied conditionally when running on HTTPS.
- Directory and sensitive-file protection: Apache `.htaccess` rules disabling directory indexing (`Options -Indexes`) and blocking direct web access to `app/`, `config/`, `database/`, `includes/`, and `tests/`, as well as sensitive file extensions (`.env`, `.sql`, `.log`, `.key`, `.local.php`).
- HTTP security headers: Verified headers emitted by `includes/bootstrap.php` and `.htaccess`: `Content-Security-Policy`, `Permissions-Policy`, `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, and `Referrer-Policy: strict-origin-when-cross-origin`.
- Regression verification: 43/43 passing tests in `tests/test_safevault.php`. Real database integrity verified with `tests/audit_db_real.php`.

## 7. Version Plan

| Version | Deliverable | Acceptance focus |
| :--- | :--- | :--- |
| v0.1 | Foundation and baseline hardening | Existing application flows, security controls, regression tests |
| v0.2 | Authentication enhancement | USB challenge-response, biometric enrollment and verification, safe integration with current login |
| v0.3 | Secret Notes | Vigenere/AES-256-GCM round trip, authenticated storage, key separation |
| v0.4 | Secure File Vault | ChaCha20-Poly1305 upload, storage, authorization, and decrypt/download |
| v0.5 | Image Steganography | Encrypt-then-embed into PNG, extraction, payload integrity, capacity validation |
| v0.6 | Asset Integrity Ledger | Asset registration, SHA-256 fingerprint, hash chain, tamper detection |
| v1.0 | Final integration | Cross-feature security, regression, demo, documentation, and release review |

## 8. Explicit Non-Goals Unless Scope Changes

- Multi-user messaging, chat inbox, message recipients, and real-time delivery.
- Distributed blockchain consensus, mining, cryptocurrency, or proof-of-work.
- Claims that a normal USB flash drive is tamper-resistant or hardware-bound.
- Claims that webcam face matching alone provides reliable liveness detection.
- RSA-based user-to-user key exchange.
- Production deployment or compliance certification unless separately requested.
