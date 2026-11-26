# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Task 2 — Passkey credential and challenge storage

Date: 2026-11-05 (Europe/Skopje)

## Scope and result

Implemented the dedicated storage slice only. Passkey registration, login, step-up routes and UI are outside this task; this report covers only the storage slice.

- Added an additive, separately versioned schema for `zmp_passkey_credentials` and `zmp_passkey_challenges`. It uses its own `zmp_passkey_schema_version` marker; the shared plugin schema marker remains at `1.2.4.65`.
- Credential rows bind one unique hashed credential ID to one existing WordPress user and retain only the WebAuthn 5.3.9 `CredentialRecord` JSON, bounded label, and timestamps. Serializer round-trip is provided by the library's official `WebauthnSerializerFactory`; no private-key field is accepted.
- Challenges are 32 random bytes encoded base64url and returned once. The database stores only SHA-256 of the returned value. `consume_challenge()` treats that value as its opaque lookup token and atomically consumes it only when all normalized fields match: user ID, session hash, ceremony, action, resource ID and decision. Expiry is 300 seconds. Session tokens are not accepted or stored; callers must pass only a 64-hex-character hash.
- WordPress initializes the dedicated tables from `plugins_loaded`; the method is idempotent and does not advance the shared schema marker.
- Duplicate credential IDs are rejected before insertion and protected by a unique index for concurrent races. A failed insert suppresses WPDB output, removes only newly-added matching `EZSQL_ERROR` entries, clears only that failed query/error from in-memory WPDB diagnostics, and restores the prior suppression flag in `finally`.

## Changed candidate files

- `includes/class-zmp-passkey-store.php`
- `zmp-competitions.php`
- `vendor/webauthn/composer.json`
- `vendor/webauthn/composer.lock`
- `vendor/webauthn/vendor/` — locked runtime dependencies

The existing root `vendor/` Composer tree was preserved. The candidate files were copied to the phase-scoped synthetic site only. Source/site entrypoint and store hashes matched before final testing.

## Validation evidence

- **PASS** — `tests/phase66a-passkey-store.php`: 34 focused behavior assertions on the actual local synthetic WordPress site; credential save/find round-trip; exact WordPress user binding; duplicate rejection; wrong-owner and owner deletion; challenge binding mismatches; one-use consume; expiry denial; hash-only persistence; and privacy-safe failures.
- **PASS** — forced insert-error regression: temporary synthetic `BEFORE INSERT` trigger returned a fixed failure without credential markers in captured output/logs or new `EZSQL_ERROR` entries; WPDB `last_query`/`last_error` were cleared; prior suppression state was restored; no credential row was written; the trigger and test fixtures were removed.
- **PASS** — `php -l` on the store and plugin entrypoint.
- **PASS** — `composer validate` (valid; Composer emitted its non-fatal exact-version-pin and absent-root-license warnings).
- **PASS** — `wp db check` against isolated synthetic database `zmp66a_isolated` after test cleanup.
- **PASS** — local runtime reports PHP 8.5.4 `cli-server`; database/site are synthetic only. 
- The test and runtime did not access `.65`, the persistent clone, production, or real email. Temporary test rows/user and the SQL trigger were removed.

## Dependency provenance

- `web-auth/webauthn-lib` **5.3.9**, MIT, source commit `727e378fb7a36c26be5c911e4a0120c146741ce7`.
- Composer platform is pinned to PHP 8.2.0 so dependency resolution does not silently require the local PHP 8.5 runtime. Locked Symfony serializer/property-info/clock/UID components are 7.4.x; total locked set is 32 packages.
- Composer package cache/runtime checks passed locally. The PHP 8.2 target is a dependency-resolution floor, not proof of production-host compatibility.

## Frozen hashes

| File | SHA-256 |
|---|---|
| `includes/class-zmp-passkey-store.php` | `69ed117647859852a3f27ce8799d9bb2add48ed1fafea9879a8adbf854c0ce9c` |
| `zmp-competitions.php` | `1d6e5dcbdab17b39897aa4a5d03654516a105a5cb379e319c59198c6617b5e6` |
| `tests/phase66a-passkey-store.php` | `7d005f435572f5a026740ba7942664a3a34b250e06f2f09c01a14f5fd149e745` |
| `vendor/webauthn/composer.json` | `6c21a781436d19f8fa43c7afff03797c7232462c8d8f96c759bbfe3858fa52f0` |
| `vendor/webauthn/composer.lock` | `10782b6588428a61b300224fd0dedd9bebfd3b56ccdce5fe262d7ee6928767f0` |

## Handoff

## Review fix round 1 — 2026-11-05

Code Review findings addressed:

1. Ceremony bindings are now validated by type. Registration requires an existing positive WordPress user ID and nonempty SHA-256 session hash. Login accepts only the anonymous zero-user binding with empty session/action/decision and zero resource. Step-up requires an existing positive user ID, valid session hash, nonempty action and decision, and positive resource ID. Missing/incomplete privileged bindings fail before any challenge insert.
2. Schema compatibility now checks all required columns plus primary, unique and lookup indexes on both tables. `install_schema()` runs `dbDelta`, verifies the complete structure, and only then reads/writes the dedicated version marker. An existing marker cannot mask missing structure; partial columns/indexes are repaired by `dbDelta` and verified before compatibility is returned.
3. Composer lock package count corrected to 32.

Final fix-round checks on the actual `.66.a` synthetic site (`/home/tonic/zmp-local-sandbox/phase66a-test/site`, database `zmp66a_isolated`):

- **PASS (65)** — `wp --path=/home/tonic/zmp-local-sandbox/phase66a-test/site eval-file /mnt/z/Documents/Natprevari_ZMP/ZMP_PROJECT_LIBRARY/03_PHASES/zmp-competitions-1.2.4.66.a/tests/phase66a-passkey-store.php`, exit 0. This includes all registration/login/step-up shape denials, valid anonymous login, fully-bound step-up, partial-column/index detection and repair, credential ownership/serialization, replay/expiry, failed-insert privacy handling, and fixture cleanup.
- **PASS** — `php -l` for `includes/class-zmp-passkey-store.php` and `zmp-competitions.php`.
- **PASS** — `wp --path=/home/tonic/zmp-local-sandbox/phase66a-test/site db check`; all tables report OK.
- **PASS** — `composer validate --working-dir=vendor/webauthn --no-check-publish` (manifest valid; same non-fatal exact-pin and absent-root-license warnings).
- **PASS** — source/site store SHA-256 matches after sync: `69ed117647859852a3f27ce8799d9bb2add48ed1fafea9879a8adbf854c0ce9c`.
- Test Engineer confirms no fixture user, credential/challenge rows, or temporary trigger remain; the partial schema/index were repaired and compatibility restored.

Updated Task 2 candidate hashes:

| File | SHA-256 |
|---|---|
| `includes/class-zmp-passkey-store.php` | `69ed117647859852a3f27ce8799d9bb2add48ed1fafea9879a8adbf854c0ce9c` |
| `tests/phase66a-passkey-store.php` | `7d005f435572f5a026740ba7942664a3a34b250e06f2f09c01a14f5fd149e745` |

Task 2 is ready for scoped independent re-review. This is not acceptance of `.66.a`: passkey registration/login/step-up route integration, UI, rollback, whole-phase tests/reviews, package, GitHub synchronization and Auditor decision remain open or unrun.

## Additive update — profile credential listing for Task 3 removal — 2026-11-05

Task 3 requires an owner profile to list the current user's passkeys so the owner can select one for removal. Added `ZMP_Passkey_Store::list_credentials_for_user(int $user_id)`, which validates each stored record and returns only credential ID, label, and creation timestamp for the requested owner. It does not change table schema, serialization format, challenge behavior, credential ownership, or deletion rules. This additive store change is not covered by the earlier independent review and requires a scoped code-review recheck.

- **PASS (65)** — exact existing storage command: `wp --path=/home/tonic/zmp-local-sandbox/phase66a-test/site eval-file /mnt/z/Documents/Natprevari_ZMP/ZMP_PROJECT_LIBRARY/03_PHASES/zmp-competitions-1.2.4.66.a/tests/phase66a-passkey-store.php` → `PASS (65): credential and one-use challenge storage behavior`.
- **PASS** — `php -l` for `includes/class-zmp-passkey-store.php`.
- **PASS** — `wp --path=/home/tonic/zmp-local-sandbox/phase66a-test/site db check` → `Success: Database checked.`
- Current `includes/class-zmp-passkey-store.php` SHA-256, with source and sandbox copies matching: `fa58ed5c1355fb729504e330271260462657cd4ed61ee36410737600db38dea5`.

The prior Task 2 report and hashes above preserve the original reviewed storage candidate. This update does not change that earlier review verdict; the new listing method remains pending review.
