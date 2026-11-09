# 006 - SAFEVAULT Testing and Lecturer Demonstration Scenarios

## 1. Testing Principles

SAFEVAULT must be tested incrementally. Run focused tests for the active feature, then the full regression suite. Distinguish:

- Unit tests: isolated functions such as validation and cryptographic round trips.
- Integration tests: PHP, MariaDB, filesystem, and session interactions.
- Security tests: negative cases such as unauthorized access, tampering, CSRF failure, and invalid authentication.
- Manual tests: browser behavior, camera permission, USB insertion/removal, and visual UI checks.
- Runtime verification: actual Apache HTTP response headers and `.htaccess` behavior.

Never claim a test passed unless it was actually run and the result was observed. Record the command, exit code, counts, and environment.

## 2. Existing v0.1 Regression Baseline

The existing regression and database integrity verification commands are:

```powershell
php tests/test_safevault.php
php tests/audit_db_real.php
```

The recorded v0.1 report states:

- `test_safevault.php`: 43 tests passed, 0 tests failed, exit code 0.
- `audit_db_real.php`: successful real MariaDB connection, schema matches `database/schema.sql`, and Argon2id hash prefix validated without modifying data.

The test suite covers:

1. Cryptographic/runtime environment prerequisites (PASSWORD_ARGON2ID).
2. Argon2id password hashing and verification.
3. CSRF token generation, matching, and validation.
4. Backend input validation (username, email, password length).
5. Database operations and duplicate registration prevention.
6. Login flow with username and email.
7. Session security and session ID regeneration.
8. Protected pages and logout cleanup.
9. Login rate limiting (5 attempts in 15 minutes, per-account and per-IP).
10. Idle session timeout (15 minutes) and absolute session timeout (8 hours).

Treat 43/43 as the existing recorded baseline, not as an expected count for every future test suite. New tests will increase the count as features are implemented. Inspect the actual test file before assuming exact test names or behavior.

## 3. v0.1 Manual and Runtime Checks

After relevant changes, verify:

### Browser flow

- Homepage loads.
- Registration works with valid input.
- Duplicate username/email is rejected.
- Login works with username and email where supported.
- Incorrect credentials are rejected.
- Dashboard requires authentication.
- Logout invalidates the session.
- Browser back navigation does not restore authenticated content after logout.

### Security behavior

- CSRF-invalid state-changing request is rejected.
- Untrusted output is escaped.
- Failed login attempts are throttled after the defined threshold.
- Per-account throttling works across IPs.
- Per-IP throttling works across accounts.
- Idle timeout occurs at 15 minutes.
- Absolute timeout occurs at 8 hours.
- Background requests do not incorrectly extend activity where this behavior is implemented.
- Sensitive internal paths are blocked by Apache.
- Security headers are verified from actual Apache responses.
- Session cookies have the expected flags for the current HTTP/HTTPS environment.

### Database safety

- Existing users remain intact after tests.
- Test data is cleaned from the isolated test database.
- No destructive tests target the developer's only live database.

## 4. v0.2 Authentication Test Plan

See `context/002-authentication-dongle-and-biometrics.md` for the detailed requirements. At minimum, test:

- Existing Argon2id login still works.
- Correct USB private key signs a fresh challenge.
- Missing, invalid, copied-to-wrong-account, revoked, expired, and replayed dongle challenges fail safely.
- No private key appears in server responses, browser source, logs, or database plaintext fields.
- Valid enrolled face is accepted under the approved threshold.
- Non-matching face, no face, camera denied, and camera unavailable are handled safely.
- One failed factor cannot create an authenticated session.
- Re-enrollment and lost-device recovery follow the approved flow.
- Existing rate limiting, CSRF, session timeout, logout, and protected-page checks continue to pass.

Biometric accuracy claims require measured test cases. A passing unit test alone is not evidence of production-grade liveness detection.

## 5. v0.3 Secret Notes Test Plan

See `context/003-secret-notes-encryption.md`. At minimum:

- Vigenere known-answer example matches the approved alphabet policy.
- Vigenere round trip preserves punctuation, digits, whitespace, and supported Unicode.
- AES-256-GCM encrypt/decrypt round trip succeeds.
- A fresh nonce is used for each encryption under the same key.
- Modified ciphertext, modified tag, wrong key, and malformed envelope fail closed.
- Stored note data is ciphertext, not plaintext.
- Unauthorized access is rejected.
- No plaintext or encryption key is written to logs.

## 6. v0.4 Secure File Vault Test Plan

See `context/004-secure-file-vault.md`. At minimum:

- ChaCha20-Poly1305 round trip restores identical file bytes.
- Different encryptions use different nonces.
- Modified ciphertext/tag and wrong keys fail authentication.
- Oversized or disallowed uploads are rejected.
- Path traversal and arbitrary storage paths are rejected.
- Unauthorized download is rejected.
- Temporary plaintext data is removed safely.
- Encrypted content is stored outside directly accessible public paths.

## 7. v0.5 Steganography and v0.6 Ledger Test Plan

See `context/005-steganography-blockchain-and-ui-integration.md`.

Steganography tests:

- Embed an encrypted payload into a PNG, save, reload, extract, decrypt, and compare exact plaintext.
- Reject images without a valid payload.
- Reject malformed, truncated, or oversized payloads.
- Confirm output is PNG and visual changes follow the documented LSB behavior.
- Tampering with the encrypted payload fails authentication.

Ledger tests:

- Genesis and subsequent blocks produce deterministic hashes from canonical fields.
- Intact chain verifies.
- Changed block data is detected at the correct index.
- Broken previous-hash links are detected.
- Asset state update and ledger append are atomic.
- All tampering tests use an isolated database.

## 8. Runtime and Browser Verification

For Apache-specific behavior:

- Run Apache configuration validation where available.
- Use the actual Apache service and `curl.exe` or browser Network tools to inspect HTTP responses.
- Test `.htaccess` through Apache. PHP's built-in server does not process `.htaccess`.
- Check response headers for duplicate or conflicting policies.
- Check browser Console for CSP violations after adding scripts, styles, webcam code, or dependencies.
- Do not claim HSTS is required on local HTTP.

For camera features:

- Test through the actual browser origin used by the application.
- Verify Permissions-Policy and browser camera permission separately.
- Test permission denied and camera unavailable states.

For USB features:

- Test with a real USB device and a controlled mock path only if the implementation explicitly supports such a test mode.
- Never use a real personal key or production private key in automated tests.

## 9. Lecturer Demo Sequence (Proposed)

The final demonstration should follow the completed feature set, not pretend planned features are finished. A possible sequence after v1.0 is:

1. **Authentication:** Show password login, USB signature verification, and face verification. Show a rejected attempt when one required factor is invalid. Explain the software-based USB limitation honestly.
2. **Secret Notes:** Create a sample note, show the Vigenere educational transformation, show AES-256-GCM encrypted storage, then decrypt after authorization.
3. **Secure File Vault:** Upload a sample PDF, show that the stored file is encrypted, and download/decrypt it through the authorized application flow.
4. **Image Steganography:** Embed an encrypted sample payload into a PNG, compare cover and stego output, then extract and decrypt.
5. **Asset Integrity Ledger:** Register a sample asset, show its SHA-256 fingerprint and hash-chain record, verify the intact chain, then use an isolated demonstration database to show tamper detection.

Use disposable demonstration content. Do not expose actual private notes, biometric reference images, cryptographic keys, or personal database records.

## 10. Release Acceptance Checklist

Mark each item only after evidence exists.

### v0.1 foundation

- [x] Existing PHP application flow implemented.
- [x] Argon2id password hashing and verification.
- [x] PDO and prepared statements.
- [x] CSRF protection and output escaping.
- [x] Session regeneration, idle timeout, and absolute timeout.
- [x] Login rate limiting.
- [x] Directory/file protection and HTTP security headers.
- [x] Recorded 43/43 regression result.
- [x] Developer-confirmed manual dashboard check.

### Future features

- [ ] v0.2 USB dongle challenge-response implemented and tested.
- [ ] v0.2 Face Recognition implemented and tested.
- [ ] v0.3 Secret Notes encryption and database storage tested.
- [ ] v0.4 Secure File Vault encryption and access control tested.
- [ ] v0.5 LSB steganography and encrypted payload extraction tested.
- [ ] v0.6 SHA-256 asset ledger and tamper detection tested.
- [ ] v1.0 end-to-end and lecturer demonstration completed.

Do not mark a future item complete based on a design document or generated code alone. Require execution evidence.
