# 004 - SAFEVAULT Secure File Vault

## 1. Scope and Status

Secure File Vault is planned for SAFEVAULT v0.4. Files will use ChaCha20-Poly1305, which is different from the Vigenere plus AES-256-GCM pipeline planned for Secret Notes.

The two feature pipelines must use separate keys. Do not reuse the Secret Notes key for file encryption.

## 2. Algorithm and Parameters

Use an established cryptographic implementation of ChaCha20-Poly1305.

Required parameters:

- Key: 256 bits (32 bytes).
- Nonce: 96 bits (12 bytes) for the standard AEAD construction.
- Authentication tag: produced and verified by the AEAD implementation.
- Input: raw file bytes, not text-decoded content.
- Output: authenticated ciphertext plus a serialized nonce and any required format metadata.

Never reuse a nonce with the same key. Generate nonces using a cryptographically secure random generator. Reject invalid tags and return no partial plaintext.

## 3. File Encryption Flow

The intended server-side flow is:

1. Receive a file upload from an authenticated user.
2. Validate request size, file size, permissions, and allowed policy.
3. Generate an opaque server-side storage identifier. Do not use a client-provided filename as a filesystem path.
4. Read file bytes safely and encrypt using ChaCha20-Poly1305.
5. Persist only the encrypted bytes in a protected storage location. The `storage/` directory and its `encrypted-files/` subdirectory do not exist physically yet; they will be created at runtime when this feature is implemented.
6. Store the metadata needed to identify and authorize the encrypted file.
7. Return a safe success result without exposing storage secrets or physical paths unnecessarily.

The exact file format, storage directory creation, and metadata schema are governed by `SV-DEC-08` and `context/001-database-and-storage-schema.md`.

## 4. File Decryption and Download Flow

1. Require an authenticated session.
2. Verify that the current user is authorized to access the file.
3. Resolve the stored path from a server-controlled opaque identifier.
4. Verify the path remains inside the approved storage root.
5. Load the nonce and encrypted payload according to the approved envelope format.
6. Decrypt and authenticate the payload in memory or a safely managed temporary stream.
7. Return the original file bytes with a safe download response.
8. Clean up temporary plaintext files, if any are unavoidable.

Do not trust a request parameter containing a filesystem path. Protect against path traversal, symlink escape, unauthorized ID substitution, and arbitrary file download.

## 5. Upload Validation and Resource Limits

`SV-DEC-08` must define:

- Maximum upload size.
- Allowed or disallowed file types.
- Whether empty files are supported.
- Storage quota and retention policy.
- Original filename handling.
- MIME type detection and response behavior.
- Temporary-file cleanup behavior.

Client-supplied MIME types and extensions are untrusted. Validate server-side. Avoid loading arbitrarily large files into memory. Stream or chunk processing may be needed after measuring realistic project constraints.

## 6. Key Management

- Use a key dedicated to ChaCha20-Poly1305 file encryption.
- Keep the key outside MariaDB and source control.
- Do not hardcode keys in PHP files or HTML/JavaScript.
- Define key backup, rotation, and migration behavior before changing the key format.
- Include a cipher version in the stored record or envelope so future versions can be handled safely.
- A key loss means encrypted files may be unrecoverable. Document this accurately and do not promise recovery without a tested backup design.

## 7. Demonstration Requirements

The lecturer demonstration should use a disposable sample file, such as a sample PDF, and show:

1. Original file is readable before upload.
2. Encrypted file is stored in the protected vault directory.
3. Stored bytes are not directly the original document and cannot be opened as the original format.
4. Authorized download decrypts successfully and reproduces the original bytes.
5. Unauthorized access fails.
6. Tampered ciphertext or tag fails authentication.

Do not claim that an encrypted file is secure merely because it has a `.enc` extension. The cryptographic authentication and access-control checks must be tested.

## 8. Required v0.4 Tests

- Encrypt/decrypt round trip for text-like, binary, empty (if allowed), and large test files.
- Different encryption operations use different nonces.
- Ciphertext tampering fails authentication.
- Tag tampering fails authentication.
- Wrong key fails authentication.
- Truncated or malformed payload fails safely.
- Oversized upload is rejected.
- Unsupported file type is handled according to policy.
- Path traversal and crafted identifiers cannot escape the storage root.
- Unauthorized users cannot download another user's file.
- Temporary plaintext files are cleaned up.
- Existing v0.1 and v0.3 tests continue to pass.
