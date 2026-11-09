# 005 - SAFEVAULT Image Steganography, Asset Integrity Ledger, and UI Integration

## 1. Scope and Status

This context covers two planned capabilities and the integration principles used by the existing PHP application:

- v0.5: Image steganography using encrypted payloads embedded in lossless PNG images through LSB manipulation.
- v0.6: A local SHA-256 hash-chained ledger for important vault assets.

Both features are PLANNED. Do not create them until the relevant design gates are resolved and their context is reviewed.

## 2. Image Steganography (v0.5)

### 2.1 Intended Concept

A user selects a cover image and supplies a secret payload. The payload is encrypted first, then embedded into the least significant bits of the RGB pixel values of a lossless PNG. Extraction reverses the process: recover the embedded bytes, validate the payload framing, and decrypt it.

Conceptual flow:

```text
Secret plaintext
   -> approved authenticated encryption
   -> framed encrypted payload
   -> LSB embedding into RGB pixels
   -> lossless stego PNG

Stego PNG
   -> LSB extraction
   -> validate framing and lengths
   -> authenticated decryption
   -> original secret plaintext
```

The precise encryption key, nonce/tag format, framing structure, and storage design must be resolved under `SV-DEC-09`. Do not assume that the Secret Notes key can be reused. Note that `storage/stego-images/` does not physically exist yet and will be created at runtime when this feature is implemented.

### 2.2 Image Format

- Output must be PNG to preserve LSB payload bits.
- Do not re-save the stego image as JPEG or another lossy format.
- If other image formats are accepted as input, convert them to a well-defined RGB or RGBA representation before embedding.
- Record image dimensions and ensure all transformations preserve the expected pixel layout.

### 2.3 Embedding Requirements

A future `embed_lsb` implementation should:

1. Validate the input image and convert it to the approved pixel format.
2. Build a versioned payload containing enough metadata to recover the encrypted content safely.
3. Convert payload bytes to bits using a deterministic bit order.
4. Calculate capacity before changing any pixel.
5. Fail clearly if the payload exceeds available capacity.
6. Modify only the intended least significant bits.
7. Save as lossless PNG.
8. Return useful metrics, such as payload bytes, capacity, and maximum pixel delta or PSNR, if the metric is implemented correctly.

Avoid relying on a delimiter alone for arbitrary binary ciphertext. A length-prefixed or otherwise unambiguous versioned framing scheme should be selected in `SV-DEC-09`.

### 2.4 Extraction Requirements

A future `extract_lsb` implementation should:

1. Load the PNG and validate supported color mode.
2. Read the payload framing in the defined bit order.
3. Validate magic/version, declared length, and image capacity bounds before allocating large buffers.
4. Extract exactly the declared payload length.
5. Reject images with missing, malformed, truncated, or unsupported payloads.
6. Authenticate and decrypt the payload. Never return unauthenticated plaintext.

### 2.5 Access and Privacy

Only the authenticated vault owner should access private stego assets unless sharing is explicitly introduced into the product scope. Do not add messaging recipients or file transfer flows.

## 3. Asset Integrity Ledger (v0.6)

### 3.1 Meaning of Blockchain in SAFEVAULT

The planned ledger is a simple local SHA-256 hash chain stored in the application's database. It links sequential records so a modification to a record can be detected during verification.

It is not a public blockchain, a distributed network, a consensus protocol, or proof of immutable storage. A privileged actor who can rewrite all database rows and recompute every hash may be able to rewrite the entire chain. State this limitation in the UI and lecturer documentation.

### 3.2 Asset Definition

`SV-DEC-10` must define what counts as an important vault asset. A possible scope is a user-registered digital file with an asset identifier, display name, owner, and SHA-256 fingerprint of the original content. Do not implement this example as the final rule until confirmed.

### 3.3 Block Fields and Hashing

After the asset definition is approved, define a deterministic block envelope. Possible fields include:

- Sequential block index.
- UTC timestamp in a canonical format.
- Actor or vault owner identifier.
- Event type, such as `GENESIS`, `REGISTER_ASSET`, or `UPDATE_ASSET`.
- Target asset identifier.
- Canonically serialized event data.
- Previous block hash.
- Current block hash.

The current hash should be SHA-256 over a canonical, deterministic serialization of all agreed fields except the current hash itself. Field order, encoding, timestamp format, null representation, and JSON serialization rules must be stable. Do not hash arbitrary PHP array serialization.

Proof-of-work is not part of the agreed roadmap. Do not add it unless explicitly requested and justified.

### 3.4 Transactions and Verification

- Asset state changes and corresponding ledger events should be written atomically in a database transaction.
- The genesis entry should be created exactly once.
- Verification should check sequential indexes, genesis previous-hash value, recomputed hashes, and previous-hash links.
- If a mismatch is found, return a report identifying the first broken block and reason.
- Consider cross-checking the current asset record against the latest ledger event.
- Tests that tamper with rows must use a disposable database, never the user's live database.

## 4. PHP UI and Request Integration

SAFEVAULT currently uses PHP pages and the existing Apache/PHP request lifecycle. Do not introduce FastAPI routers, Streamlit pages, or Next.js endpoints from the unrelated source template.

Before adding routes or pages:

1. Inspect current `public/` pages, shared bootstrap, navigation, session checks, and existing CSS/JavaScript conventions.
2. Reuse the established authentication and CSRF mechanisms.
3. Validate every state-changing request server-side.
4. Add authorization checks at the server-side action, not only by hiding a UI button.
5. Return safe Indonesian UI messages while keeping internal technical details in protected logs.
6. Keep cryptographic work and file operations out of client-side JavaScript. The browser may handle camera capture or presentation, but secret keys and decryption should remain server-side unless an approved architecture explicitly says otherwise.
7. Avoid exposing physical storage paths, private keys, or full decrypted content in URLs or logs.

### Proposed navigation areas

These are conceptual UI areas, not confirmed page filenames or a mandate to add five separate pages:

- Dashboard / vault overview.
- Secret Notes.
- Secure File Vault.
- Image Steganography.
- Asset Integrity Ledger.

Use the existing dashboard layout and navigation style. Avoid redesigning the whole application as part of an individual feature task.

## 5. Required v0.5 and v0.6 Tests

### Steganography

- Embed and extract an encrypted test payload from a valid PNG.
- Recover the exact original plaintext after authenticated decryption.
- Reject an image with no valid payload.
- Reject truncated or malformed framing.
- Reject payloads that exceed image capacity.
- Confirm output remains PNG and survives save/reload.
- Confirm pixel changes stay within the defined LSB behavior.
- Tampered encrypted payload fails authentication.
- Unauthorized users cannot access private stego assets.

### Asset ledger

- Create a genesis entry and register one or more test assets.
- Verify the intact chain.
- Modify a field in a disposable database and verify detection at the correct block.
- Modify a previous-hash link and verify detection.
- Verify a failed asset update does not leave an unmatched ledger event.
- Verify transactions roll back atomically on failure.
- Verify canonical hashing is deterministic across repeated test runs.

### UI integration

- Protected pages reject unauthenticated requests.
- State-changing requests require CSRF validation.
- Invalid IDs and crafted paths do not expose other users' or server files.
- Existing navigation, CSS, and JavaScript remain functional under the current CSP.
