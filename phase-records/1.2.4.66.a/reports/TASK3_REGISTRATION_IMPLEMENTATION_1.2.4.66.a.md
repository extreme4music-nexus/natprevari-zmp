# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Task 3 — Passkey registration implementation (1.2.4.66.a)

**Status:** Registration and profile-management backend implementation is present; review-fix round 1 focused checks PASS. Independent review/UI review and browser registration remain **NOT RUN**; this is not phase acceptance.

## Scope and behavior

Implemented a registration service and current-user profile controller. Enrollment requires the authenticated account owner with the exact `zmp_president` role and an active mandate, and verifies the current password. Options and finish both recheck role/mandate. The one-use challenge is bound to account, registration ceremony, and current WordPress session hash. The account receives a stable random non-PII WebAuthn user handle. Creation options request user verification and resident credentials and use attestation `none`. Origin and RP ID come from site configuration; the HTTP exception is limited to localhost/127.0.0.1. Finish rechecks the authenticated user/session, consumes the challenge, validates the serialized ceremony with the installed WebAuthn 5.3.9 validator and stores the validated credential. Errors are generic and credential/challenge/assertion/private-key material is not logged.

The profile provides owner-only credential removal after current-password reauthentication and a dedicated nonce. A credential owner can remove a passkey after role/mandate loss. A minimal store API lists only that user's credential identifiers, labels, and creation times for the removal controls; it does not return full records to the profile controller.

The profile controller renders only on the current user's own profile, validates POST and action-specific nonce, and exposes the registration script configuration only on that profile screen. Markup uses a fieldset wrapper inside WordPress's existing profile form. The JavaScript integration contract is documented to the Frontend Developer; this backend slice does not implement login or privileged step-up routes.

## Files changed

- `includes/class-zmp-passkeys.php`
- `includes/class-zmp-passkey-store.php` (owner-scoped listing API; Task 2 suite rerun)
- `admin/class-zmp-passkey-profile.php`
- `zmp-competitions.php` (WebAuthn autoload guarded below PHP 8.2)

The above files were synced to the authorized synthetic site before the final checks. Their source and sandbox-copy SHA-256 hashes match:

| File | SHA-256 |
|---|---|
| `includes/class-zmp-passkeys.php` | `270aa52ea356fe63d24104b04b5b34e1da820f7cac00b701abfe1160d15b95cb` |
| `includes/class-zmp-passkey-store.php` | `fa58ed5c1355fb729504e330271260462657cd4ed61ee36410737600db38dea5` |
| `admin/class-zmp-passkey-profile.php` | `5c9c24c91e4d7c3a8e9f91a84ac1856cec73176f02cb62757ccdf65ca2bbbccc` |
| `zmp-competitions.php` | `be6ebb302dfb0dfc9e2d5dfe1d7fee080f0b51030accbcb396a4c6b8c5bf4c47` |
| `assets/js/zmp-passkeys.js` (Frontend-owned) | `21e3b1585a5a608eb6173955c1fcd05076245fcee7b7ff4aaf497508500b91a5` |

## Verification evidence

Target: `/home/tonic/zmp-local-sandbox/phase66a-test/site`, loopback `127.0.0.1:8101`, synthetic database `zmp66a_isolated` / prefix `z66a_`. Existing phase PHP and MariaDB services were reused.

- **PASS** — `wp --path=/home/tonic/zmp-local-sandbox/phase66a-test/site eval-file /mnt/z/Documents/Natprevari_ZMP/ZMP_PROJECT_LIBRARY/03_PHASES/zmp-competitions-1.2.4.66.a/tests/phase66a-passkey-registration.php` → `PASS (19): passkey registration API and guard behavior`.
- **PASS** — PHP syntax checks (`php -l`) for all three changed PHP files: no syntax errors.
- **PASS** — `wp --path=/home/tonic/zmp-local-sandbox/phase66a-test/site db check` → `Success: Database checked.`
- **PASS** — A temporary local smoke check verified current-password registration options, exact configured local origin/RP ID, attestation `none`, required user verification and resident key selection. It made no attestation claim. Its challenge/user fixture and temporary script were cleaned; the script path was verified absent.
- **PASS** — Source/sandbox-copy SHA-256 hashes match for each changed product file.

The focused registration fixture completed its cleanup. The smoke check removed its temporary challenge, session, user metadata, and synthetic account. No raw credentials or ceremony payloads are included in this report.

## Not run / limitations

- **NOT RUN** — Browser-driven WebAuthn enrollment with a platform or roaming authenticator; no successful attestation has been claimed.
- **NOT RUN** — Real registration finish/credential validation via browser ceremony. That requires the Frontend Developer's browser client integration and a synthetic authenticator flow.
- Login and President step-up integration are explicitly outside this slice.
- Independent review and whole-phase acceptance remain with the Lead/review roles.

No installation package or release artifact was created.

## Frontend integration synchronization (2026-11-05)

The Frontend-owned registration script was completed and synchronized to the same synthetic site for integrated server-side verification. Source and sandbox copy hash match: `assets/js/zmp-passkeys.js` SHA-256 `6c2c475c3370a5aa68c80b9c56e69a78402259c5ee9a16d4b1ef6eb3b7804b95`. After synchronization, the registration API/guard test still returned `PASS (19)`, all three backend PHP lint checks passed, and `wp db check` returned `Success: Database checked.` The script syntax result (`node --check`) is recorded by its owner. UI review and browser ceremony are pending; this integration result does not establish an actual authenticator attestation.

## Registration review-fix round 1 — 2026-11-05

Resolved the seven focused failures recorded by the Test Engineer:

- Registration options and finish now fail closed unless the current authenticated account has the exact `zmp_president` role and an active mandate. The profile displays enrollment controls only under this gate.
- User-handle initialization now rereads the first stored `usermeta` row from the database after the insert attempt and uses that canonical value. Subsequent calls also resolve the first stored row, so the forced competing-insert case cannot return the request-local candidate.
- Added `ZMP_Passkeys::remove_credential()` and the `admin_post_zmp_passkey_remove` action. Removal requires the current signed-in owner, current password, the dedicated `_zmp_passkey_remove_nonce`, and a matching owner-bound credential. It does not require the role/mandate, so a demoted owner can remove their own credential.
- Added an owner-scoped store listing method for the profile UI. It returns only validated credential ID, label and creation time for the requested user; the profile does not receive full stored credential records.
- No PHP `Requires PHP` header or previous declared plugin minimum was found. Because WebAuthn 5.3.9 requires PHP >= 8.2, its isolated autoloader is skipped below PHP 8.2. New service return declarations do not use PHP 8.2 standalone `true` types. Passkey operations fail closed if the dependency is unavailable.

Final verification commands against `/home/tonic/zmp-local-sandbox/phase66a-test/site` (DB `zmp66a_isolated`, prefix `z66a_`):

- **PASS (49)** — `wp --path=/home/tonic/zmp-local-sandbox/phase66a-test/site eval-file /mnt/z/Documents/Natprevari_ZMP/ZMP_PROJECT_LIBRARY/03_PHASES/zmp-competitions-1.2.4.66.a/tests/phase66a-passkey-registration.php` → `PASS (49): Task 3 registration and passkey management behaviors`.
- **PASS (65)** — `wp --path=/home/tonic/zmp-local-sandbox/phase66a-test/site eval-file /mnt/z/Documents/Natprevari_ZMP/ZMP_PROJECT_LIBRARY/03_PHASES/zmp-competitions-1.2.4.66.a/tests/phase66a-passkey-store.php` → `PASS (65): credential and one-use challenge storage behavior`.
- **PASS** — `php -l` for `includes/class-zmp-passkeys.php`, `includes/class-zmp-passkey-store.php`, `admin/class-zmp-passkey-profile.php`, and `zmp-competitions.php`; each reported no syntax errors.
- **PASS** — `wp --path=/home/tonic/zmp-local-sandbox/phase66a-test/site db check` → `Success: Database checked.`
- **PASS** — `node --check /mnt/z/Documents/Natprevari_ZMP/ZMP_PROJECT_LIBRARY/03_PHASES/zmp-competitions-1.2.4.66.a/assets/js/zmp-passkeys.js` (exit 0).
- **PASS** — source and sandbox copies have matching SHA-256 for all four backend files and the Frontend-owned JS listed above.
- Focused fixtures cleaned synthetic users, session tokens, mandates, credential/challenge rows and user-handle rows. No raw credential IDs, passwords, nonces, challenges or serialized credential records are in this report.

The storage file changed for the minimal list API; the 65 focused Task 2 tests were rerun, and a scoped independent code-review recheck is requested. Browser UI review and real authenticator registration/attestation remain **NOT RUN**. No successful attestation is claimed. Login, step-up, production-hosting verification and phase acceptance remain open.

## Additive update — profile-save and removal-origin review fixes — 2026-11-05

This section updates the current candidate and supersedes only the older round-1 “current hashes” for Task 3. Earlier red runs, implementation details and valid Task 2/Task 3 scoped PASS results remain preserved above. The broader `.66.a` phase remains IN PROGRESS.

### Corrected behavior
- Removed HTML `required` from the current-password control inside WordPress's enclosing profile form. A blank-password self-profile save is allowed; the passkey enrollment and removal service still explicitly validate current-password reauthentication.
- The browser removal handler now checks `window.isSecureContext` before reading the credential ID or password or sending the POST, with a generic localized insecure-context message.
- Server removal checks the secure request context before reading posted fields. HTTP is allowed only for an allowlisted loopback host with an actual loopback `REMOTE_ADDR`, configured loopback policy and same-origin request source. HTTPS requires scheme, normalized host and effective port to match configured `WP_HOME`; each supplied Origin/Referer must match the actual request. Forwarded headers are not trusted.

### Exact current candidate hashes and verification
- `includes/class-zmp-passkeys.php`: `a7fb96b373390daf83115606769b97f996b543c9a14b4342448d5ff026623b1e`
- `admin/class-zmp-passkey-profile.php`: `57024d14d98b996937cd4d65efd5c397329462ea961a60c5555ac4e80bd1d7e1`
- `assets/js/zmp-passkeys.js`: `f5b388f46d2e23a9f278b3bf37d27f02b60bc40b458bc71e7616f30723af1f04`
- `includes/class-zmp-passkey-store.php`: `fa58ed5c1355fb729504e330271260462657cd4ed61ee36410737600db38dea5`
- `zmp-competitions.php`: `be6ebb302dfb0dfc9e2d5dfe1d7fee080f0b51030accbcb396a4c6b8c5bf4c47`
- Independent Test Engineer report `reports/TASK3_REGISTRATION_TEST_ENGINEER_ROUND2_1.2.4.66.a.md` records exact source/site parity and **PASS (18)** for the expanded profile/removal-origin fixture, **PASS (49)** for Task 3 registration/management, six PHP lint checks, JS `node --check`, `wp db check`, and cleanup (one synthetic UI credential remains). The test fixture hash is `51d32016b51347bda94e63b86464cb93751babda79db1ea1aed1c60299f64cf9`.
- The earlier **FAIL (2/18)** for spoofed loopback Host/remote peer and HTTPS alternate origin remains preserved in the Test Engineer report. The current 18/18 run verifies those denial cases. A matching configured-HTTPS positive case is NOT RUN because this site's `WP_HOME` is a defined HTTP loopback constant; site config was not changed.

### Independent review and remaining limits
- Round-3 Code Review returned **SPEC PASS / QUALITY FAIL**: no remaining source finding in the reviewed fix scope, but this report still carried stale round-1 hashes at review time. This additive update addresses that evidence gap; an independent freshness re-review is pending.
- Independent final UI review of the current profile/mobile/keyboard view remains open. Lead's Edge observation is not a substitute.
- Browser authenticator registration/attestation, passkey login, President step-up, whole-phase Master/Auditor and release gates remain **NOT RUN / NOT VERIFIED**. No package or release is claimed.
