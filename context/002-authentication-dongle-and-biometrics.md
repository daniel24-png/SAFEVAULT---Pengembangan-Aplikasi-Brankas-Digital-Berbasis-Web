# 002 - SAFEVAULT Authentication, USB Dongle, and Biometrics

## 1. Scope and Status Separation

This context covers the existing v0.1 password authentication and the planned v0.2 authentication enhancements.

- Argon2id password authentication: IMPLEMENTED AND VERIFIED according to the v0.1 test report.
- Session and login protections: IMPLEMENTED AND VERIFIED according to the recorded v0.1 checks.
- USB dongle using ECDSA P-256 challenge-response: PLANNED for v0.2.
- Webcam Face Recognition: PLANNED for v0.2.
- Multi-factor session state machine: PLANNED for v0.2.

Do not replace or weaken the existing password authentication when adding factors. Inspect the live login and session code first.

## 2. Existing Password Authentication (v0.1 IMPLEMENTED)

The current login flow supports username or email and verifies the submitted password against the stored PHP Argon2id hash.

Use PHP's `password_hash()` with `PASSWORD_ARGON2ID` for hash creation and `password_verify()` for verification, following the existing implementation and configuration. Do not implement raw SHA-256 password hashing. Do not add a manual salt column for PHP's password hashing API.

Preserve these existing verified controls:

- PDO prepared statements with native prepares configured.
- CSRF validation for state-changing forms (`includes/csrf.php`).
- Output escaping through `e()` (`htmlspecialchars`) in `includes/functions.php`.
- Generic and safe error handling that does not leak internal details.
- Session ID regeneration (`session_regenerate_id(true)`) after successful login.
- Authentication guards (`require_auth()`, `is_authenticated()`) for protected pages.
- Logout through the existing CSRF-protected POST flow (`public/logout.php`).
- Login rate limiting of five failed attempts in a 15-minute window, scoped by account and IP (`login_attempts` table).
- Idle timeout of 15 minutes and absolute timeout of 8 hours.
- HttpOnly and SameSite=Lax cookie settings; Secure when HTTPS is used.

## 3. Planned v0.2 Multi-Factor Authentication Architecture

The planned v0.2 enhanced flow adds two independent factors to the existing password factor:

1. **Knowledge (Factor 1 - Existing v0.1):** Argon2id password verification against MariaDB `users` table.
2. **Possession (Factor 2 - Planned v0.2):** USB flash drive containing a software-based private key file, verified via ECDSA P-256 challenge-response.
3. **Inherence (Factor 3 - Planned v0.2):** Webcam face verification against an enrolled biometric reference.

### Planned Multi-Factor Session Flow

In v0.2, the authentication workflow must transition through an explicit staged session state machine:
- Stage 0 (Unauthenticated / Guest): User enters credentials on `login.php`.
- Stage 1 (Password Verified): Argon2id succeeds. Session enters pre-auth state (`$_SESSION['pre_auth_user_id']`). Full dashboard access is NOT granted.
- Stage 2 (Dongle Challenge-Response): Server generates an unpredictable, one-time expiring challenge. Client supplies ECDSA signature from USB key. Server verifies using enrolled public key.
- Stage 3 (Biometric Verification): Client captures face frame, processes verification against enrolled reference data, and server validates compliance.
- Stage 4 (Fully Authenticated): All required factors passed. Session promoted to full authenticated status (`$_SESSION['user_id']`), session ID regenerated, and user redirected to dashboard.

Failure at any factor terminates the authentication attempt, records appropriate rate-limiting attempts, and destroys intermediate pre-auth state.

## 4. Planned USB Dongle: ECDSA P-256 Challenge-Response

### 4.1 Intended Role

The USB flash drive is planned to carry a software-based private key file. The application stores the corresponding public key in the database upon enrollment. During login, the server issues a fresh random challenge, and the client or local helper signs it with the private key. The server verifies the signature using PHP's native `openssl_verify()` with `OPENSSL_ALGO_SHA256`.

ECDSA P-256 is used strictly for digital signatures and authentication, not for data encryption.

### 4.2 Limitation of a Normal USB Flash Drive

A regular USB flash drive is a software-based possession factor, not a hardware-bound security module (HSM or FIDO2 key). A private key file stored on a USB drive can be duplicated. Therefore:
- Describe this accurately as a software-based USB dongle.
- Do not claim the key is non-exportable or physically bound to the drive hardware.
- State clearly that copying the private key file weakens the possession factor.

### 4.3 Unresolved Decisions for USB Authentication

The following decisions must be resolved before implementation:
1. **USB private key file format:** Choice of PEM (PKCS#8), raw DER, or structured JSON container.
2. **USB drive detection and communication mechanism:** Mechanism by which the browser or local environment accesses the USB drive file (e.g. HTML5 File Input API prompt, local bridge, or directory pick).
3. **Challenge expiration and replay prevention:** Exact challenge lifetime (e.g. 60 seconds), nonce length (32 bytes cryptographically random), session binding, and one-time consumption logic.
4. **Private key backup and recovery:** Procedure for lost, corrupted, or replaced USB flash drives, revocation of old public keys, and administrator recovery.

## 5. Planned Face Recognition

### 5.1 Intended Role

The webcam feature is a prototype biometric inherence factor. The browser captures a face frame via HTML5 MediaDevices and submits verification data.

### 5.2 Client-Side Security Constraint

CRITICAL: Do not assume browser-generated face embeddings or client-side match booleans are secure against client-side manipulation. An attacker with access to browser developer tools, modified client scripts, or proxy tools can forge arbitrary embeddings or send spoofed success responses.
- Any client-side feature extraction must be paired with server-side validation or cryptographic session binding.
- Document the threat model and prototype boundaries honestly for academic demonstration.

### 5.3 Unresolved Decisions for Face Recognition

The following decisions must be resolved before implementation:
1. **Face recognition processing location:** Client-side JavaScript (e.g. WebGL/WASM models) versus server-side processing (and any server dependency requirements).
2. **Face descriptor model/library:** The exact model or library used for detection and descriptor extraction, ensuring compatibility with the existing PHP/XAMPP environment without foreign runtimes.
3. **Face matching threshold:** The mathematical metric (e.g. Euclidean distance or cosine similarity) and exact numerical cutoff threshold for a match.
4. **Biometric enrollment and re-enrollment:** How reference templates are initially captured, user consent, secure reference storage (protected from public web access), and re-enrollment/deletion.
5. **Liveness detection scope:** Whether any presentation-attack detection (blink check, motion challenge) is included, or if the feature is explicitly documented as a static prototype without production-grade liveness guarantees.

## 6. Authentication State and Session Integration Rules

- Preserve current v0.1 session protections and CSRF controls.
- Do not grant `$_SESSION['user_id']` until all required factors pass.
- Keep factor error messages generic to prevent account enumeration.
- Rate limiting must cover all authentication stages to prevent brute-force attacks against any single factor.
- Enforce server-side authorization checks on every protected request.

## 7. Required v0.2 Tests

At minimum:

### Password regression
- Correct password succeeds.
- Incorrect password fails.
- Existing rate limiting and session timeout remain fully intact.

### USB Dongle tests
- Valid enrolled key signs a fresh challenge and verifies.
- Unknown public key fails.
- Expired challenge fails.
- Replayed challenge fails.
- Invalid or corrupted signature fails.
- Revoked key fails.
- No private key material appears in logs or server responses.

### Biometrics tests
- Valid enrolled face matches under approved threshold.
- Non-matching face is rejected.
- Camera permission denied or device unavailable handled safely.
- Biometric reference data is never exposed via public URLs.

### Integration tests
- Multi-factor session state progresses sequentially.
- Partial completion (e.g. password ok, dongle fail) does not permit access.
- Existing v0.1 test suite (43/43 tests) continues to pass without regression.
