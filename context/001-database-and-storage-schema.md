# 001 - SAFEVAULT Database and Storage Schema

## 1. Purpose and Source of Truth

SAFEVAULT v0.1 uses MariaDB through PHP PDO. The database name observed in local verification is `safevault_db`.

The live `database/schema.sql` and the actual database are the source of truth. This document records known v0.1 facts and planned storage principles. Do not invent columns, indexes, foreign keys, or migrations from this document alone.

Currently, only two tables exist in `safevault_db`:
1. `users`
2. `login_attempts`

No tables exist yet for USB dongles, ECDSA public keys, biometric templates, secret notes, encrypted files, or blockchain ledger records. Do not claim or assume that dongle and biometric tables already exist.

Do not migrate the project to SQLite. SQLite table definitions from unrelated templates do not apply to SAFEVAULT.

## 2. Existing v0.1 Tables

### 2.1 `users`

The v0.1 database audit observed the following columns:

| Column | Observed type | Notes |
| :--- | :--- | :--- |
| `id` | `bigint(20) unsigned` | Primary key |
| `username` | `varchar(30)` | Unique, not null |
| `email` | `varchar(254)` | Unique, not null |
| `password_hash` | `varchar(255)` | Stores PHP Argon2id password hash output |
| `created_at` | `timestamp` | Not null; default current timestamp |
| `updated_at` | `timestamp` | Not null; default current timestamp |

The audit reported that all existing password hashes used the Argon2id format. The exact table DDL must be read from `database/schema.sql` before any migration or query change.

Do not add a separate password salt column for PHP `password_hash()`. The generated password hash string contains the algorithm parameters and salt metadata required by `password_verify()`.

### 2.2 `login_attempts`

This table was introduced for v0.1 login rate limiting. The recorded tests verified account-based and IP-based rate limiting, including aggregation across different IPs for one account and across different accounts for one IP.

The exact columns, indexes, retention behavior, and cleanup rules must be read from `database/schema.sql` and the live implementation before changing it. Do not guess the column names.

The last recorded read-only audit observed zero rows in `login_attempts` after test cleanup. This is a point-in-time result, not a permanent invariant.

## 3. Database Access Rules

- Use the existing PDO connection and configuration pattern.
- Use prepared statements for every variable SQL value.
- Preserve native prepared statements if configured in the current connection.
- Use transactions for multi-step database operations where partial completion would leave inconsistent state.
- Keep cryptographic keys outside the database.
- Do not store plaintext secret notes or plaintext sensitive payloads when the corresponding encrypted feature is implemented.
- Never expose raw password hashes, keys, or other sensitive database values in normal application UI.
- Keep all schema changes in the project's migration/schema workflow and test them against an isolated database before applying them to the developer's database.

## 4. Planned Data Model Extensions

The following are logical entities for later versions, not confirmed table names or DDL. Inspect the existing schema and design the minimum migration before implementation.

### 4.1 Secret Notes (v0.3)

A future note record is expected to represent an encrypted private note owned by the authenticated vault user. The final schema must determine:

- Note identifier.
- Owner association if the application supports account-scoped records.
- Optional title and non-sensitive display metadata.
- Encrypted note payload.
- Cipher format/version metadata.
- Nonce and authentication-tag serialization, if not embedded in the payload format.
- Creation and update timestamps.
- Optional classification/importance flag only if it has a clear functional purpose.

Do not store the raw encryption key in the note row or database. Confirm whether title and metadata are also encrypted before finalizing the schema.

### 4.2 Secure File Vault (v0.4)

A file record will need enough metadata to authorize access and locate the encrypted file without exposing its plaintext content. Design the table only after the file lifecycle and storage format are approved. Potential metadata includes:

- File identifier.
- Original filename, if required for restoration.
- MIME type, if required for download.
- Encrypted storage path or opaque storage identifier.
- Cipher format/version.
- Nonce and authenticated ciphertext/tag storage details.
- File size and timestamps.
- Integrity metadata where useful.

Treat the original filename and MIME type as untrusted input. Prevent path traversal and never trust a client-supplied storage path.

### 4.3 Steganography Assets (v0.5)

If generated stego PNG files are retained, store only metadata necessary to identify and authorize the asset, such as an opaque file ID, owner, storage location, format/version, dimensions, and timestamps. Do not store encryption keys or hidden plaintext payloads in the database.

### 4.4 Asset Integrity Ledger (v0.6)

A future ledger may need separate logical records for current asset metadata and append-only block/event records. Final fields must be settled under `SV-DEC-10` before migrations.

Expected concepts:

- Asset ID and stable asset code.
- Asset display name and owner.
- SHA-256 fingerprint of the registered asset content or explicitly defined canonical data.
- Ledger event sequence.
- Event timestamp, actor, event type, deterministic event payload, previous hash, and current hash.

A ledger row stored in the same mutable MariaDB database is tamper-evident only while the attacker cannot rewrite and recompute the full chain. Document this limitation clearly.

## 5. File Storage Layout

The `storage/` directory does not currently exist physically on disk in the repository. It is planned as a runtime directory that will be created dynamically when required by future vault features (such as v0.4 file encryption or v0.5 steganography). Do not create this directory manually during documentation or audit tasks.

When implemented, planned runtime storage locations should remain outside directly served public directories:

```text
storage/
|-- notes/              # [PLANNED] Only if a file-backed note format is approved
|-- encrypted-files/    # [PLANNED] ChaCha20-Poly1305 encrypted file payloads
|-- stego-images/       # [PLANNED] Generated PNG files carrying hidden payloads
`-- reference-data/     # [PLANNED] Restricted biometric reference data, if approved
```

These are proposed logical locations, not existing physical directories. Create only the directories required by an active feature when implementing that feature. Protect stored files from direct public access through Apache rules and enforce server-side authorization for every read/download.

## 6. Migration and Backup Rules

1. Read the existing schema and current table definitions before writing DDL.
2. Back up the database before a migration that changes existing data or structure.
3. Prefer additive, reversible migrations where possible.
4. Do not drop or recreate `users` or `login_attempts` as part of a feature migration.
5. Test migrations against a disposable database copied from the schema, not the developer's only live database.
6. Verify existing user records remain intact after migration.
7. Do not include real database backups in Git or the context pack.

## 7. Lecturer and Developer Inspection

For each encrypted feature, provide a safe way to demonstrate the difference between stored ciphertext and decrypted application output. Do not expose secrets or private keys in the inspection view.

For database-backed encryption, demonstrate that the database contains ciphertext, while the application decrypts only after authorization. Use test content rather than real private notes during a lecturer demonstration.
