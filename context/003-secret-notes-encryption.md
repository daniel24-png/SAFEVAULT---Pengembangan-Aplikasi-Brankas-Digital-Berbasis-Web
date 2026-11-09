# 003 - SAFEVAULT Secret Notes and Text Encryption

## 1. Scope and Status

Secret Notes is planned for SAFEVAULT v0.3. It protects private text content stored in the vault.

Agreed high-level algorithm sequence:

```text
Plaintext
   -> Vigenere classical transformation
   -> AES-256-GCM authenticated encryption
   -> Encoded encrypted payload for storage
```

The Vigenere layer is an academic demonstration of a classical cipher. AES-256-GCM is the modern authenticated-encryption layer. Vigenere alone must never be described as secure modern protection.

No Secret Notes encryption feature should be described as implemented until code, storage, and round-trip tests exist.

## 2. Vigenere Layer

### 2.1 Purpose

Demonstrate a classical substitution cipher as the first step of the educational super-encryption pipeline.

### 2.2 Unresolved Character Policy

`SV-DEC-05` must be resolved before implementation:

- Option A: shift ASCII letters `A-Z` and `a-z`; preserve digits, whitespace, punctuation, and non-ASCII characters unchanged. Advance the key only for shifted letters.
- Option B: use another explicitly defined alphabet or character range.

Do not silently choose a character policy. The algorithm must preserve the original text exactly after decryption, including Indonesian text, line breaks, digits, punctuation, and supported Unicode characters.

### 2.3 Requirements

- Validate that the Vigenere key is present and follows the approved character policy.
- Keep the educational transformation deterministic for the same plaintext and key.
- Provide a small known-answer example that can be checked manually during a lecturer demonstration.
- Do not expose the Vigenere key in HTML, client-side JavaScript, logs, or database rows.

## 3. AES-256-GCM Layer

### 3.1 Algorithm

Use an established PHP-compatible cryptographic implementation that supports AES-256-GCM. Inspect available PHP extensions/libraries and the existing dependency policy before choosing an implementation.

Required parameters:

- Key size: 256 bits (32 bytes).
- Nonce/IV: fresh, cryptographically random nonce for every encryption under the same key. A 96-bit nonce is the standard recommended GCM size.
- Authentication tag: store or serialize the tag with the ciphertext using an explicitly documented format.
- Associated data: decide whether to bind note ID, owner ID, record version, or other context as AAD. Do not introduce AAD until its canonical encoding is defined.

### 3.2 Security Requirements

- Never reuse a nonce with the same key.
- Generate nonces with a cryptographically secure random generator.
- Validate key length and ciphertext structure before decryption.
- Verify the GCM authentication tag before returning plaintext.
- Wrong keys, corrupted payloads, or invalid tags must fail closed. Never return partial plaintext.
- Do not log plaintext notes, keys, nonces together with sensitive content, or decrypted payloads.

### 3.3 Serialization

`SV-DEC-06` must define the exact stored envelope. A versioned envelope should identify the format version and carry the nonce, ciphertext, and authentication tag in an unambiguous representation. The precise field names, delimiter, Base64 variant, and storage column must be selected before coding.

Do not store the AES key in MariaDB.

## 4. Super-Encryption Pipeline

After the character policy and AES-GCM envelope are approved, implement paired functions conceptually equivalent to:

- `vigenere_encrypt(plaintext, key)`
- `vigenere_decrypt(ciphertext, key)`
- `encrypt_aes256_gcm(plaintext_bytes, key, associated_data)`
- `decrypt_aes256_gcm(envelope, key, associated_data)`
- `secret_note_encrypt(plaintext)`
- `secret_note_decrypt(envelope)`

Use the project's existing naming conventions if they differ. Do not introduce a second parallel crypto API without need.

Round-trip requirement:

```text
secret_note_decrypt(secret_note_encrypt(original_text)) == original_text
```

The plaintext should be encrypted server-side before persistence and decrypted server-side only after authorization.

## 5. Database and Key Management

- Store the encrypted payload, not plaintext note content.
- Keep keys outside MariaDB and source control.
- Confirm whether the title, tags, preview text, and other metadata are sensitive and must also be encrypted.
- Confirm whether the database-field protection requested in the project brief means one application-level AEAD layer or a second independent encryption layer. This is `SV-DEC-07`.
- If more than one key is required, keep each key purpose-separated and document the lifecycle.
- Define backup and recovery behavior. A database backup without its required key material may be unrecoverable; key backups must be handled separately and securely.

Do not derive a key directly from a user password without an approved password-based key-derivation design. Do not assume the login password is itself the vault encryption key.

## 6. Inspection and Lecturer Demonstration

Provide a controlled inspection view that can demonstrate:

1. Original test plaintext.
2. Vigenere output.
3. AES-256-GCM encoded envelope or ciphertext preview.
4. Value actually stored in MariaDB.
5. Successful decryption back to the original text.

The view must use test data and must not reveal production keys. Avoid displaying full private notes in logs or browser developer output.

## 7. Required v0.3 Tests

- Vigenere known-answer test.
- Vigenere encrypt/decrypt round trip for mixed case, punctuation, digits, spaces, line breaks, and supported Unicode.
- AES-256-GCM round trip for empty, short, long, and multiline inputs.
- Same plaintext encrypted twice produces distinct nonces and ciphertext envelopes under normal random generation.
- Modified ciphertext fails authentication.
- Modified tag fails authentication.
- Wrong key fails authentication.
- Malformed or truncated envelope fails safely.
- Note stored in MariaDB is ciphertext, not plaintext.
- Unauthorized users cannot read or decrypt another owner's note.
- No key or plaintext is written to logs.
- Existing v0.1 authentication and regression tests still pass.
