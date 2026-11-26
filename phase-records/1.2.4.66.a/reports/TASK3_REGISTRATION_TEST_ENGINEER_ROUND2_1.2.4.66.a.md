# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Task 3 Test Engineer Report — Round 2

**Date:** 2026-11-05  
**Scope:** Two focused `.66.a` passkey review findings: optional current-password field on ordinary profile updates, and secure-origin enforcement on the credential-removal POST. This report is additive; earlier Task 3 and `.66.a` store evidence remains unchanged.

## Environment and provenance

- WordPress site: `/home/tonic/zmp-local-sandbox/phase66a-test/site`
- Synthetic database: `zmp66a_isolated`, prefix `z66a_`
- Active plugin: `zmp-competitions`
- Site services were already running and were left running: PHP PID `286942`, `127.0.0.1:8101`; isolated MariaDB socket present.
- Active plugin source matched the phase source by SHA-256:
  - `includes/class-zmp-passkeys.php`: `270aa52ea356fe63d24104b04b5b34e1da820f7cac00b701abfe1160d15b95cb`
  - `admin/class-zmp-passkey-profile.php`: `5c9c24c91e4d7c3a8e9f91a84ac1856cec73176f02cb62757ccdf65ca2bbbccc`

## Checks

Exact command:

```text
wp --path=/home/tonic/zmp-local-sandbox/phase66a-test/site eval-file /mnt/z/Documents/Natprevari_ZMP/ZMP_PROJECT_LIBRARY/03_PHASES/zmp-competitions-1.2.4.66.a/tests/phase66a-passkey-review-round2.php
```

**Exit:** `1`  
**Result:** `FAIL (2/16)`

- **FAIL — ordinary profile form with blank password:** the live `show_user_profile` render produced the current-password field with HTML `required`, so a blank value blocks normal browser form validation. A separate real WordPress `edit_user()` self-profile POST with `current_password` blank succeeded and updated only the synthetic test account. Browser interaction itself was **NOT RUN**.
- **FAIL — insecure non-loopback HTTP removal:** the actual `admin_post_zmp_passkey_remove` handler did not satisfy the combined rejection-and-row-preservation assertion for the synthetic HTTP non-loopback request. The assertion checked both the safe response and survival of that exact synthetic credential; it did not print the two sub-results separately. No credentials or request payloads were included in output.
- **PASS — HTTPS removal:** the synthetic HTTPS request met the expected allow case.
- **PASS — HTTP localhost removal:** the synthetic localhost request met the expected allow case.
- **PASS — HTTP 127.0.0.1 removal:** the synthetic loopback-IP request met the expected allow case.
- **PASS — fixture cleanup and UI fixture preservation:** all test users and credential rows returned to their pre-run counts; the separately tracked UI-review credential row remained unchanged.

The three allowed-origin assertions exercised the real removal POST handler with valid synthetic owner, password, and nonce data. Origins were assigned only to request-server variables in the WP-CLI process. No site URL/configuration was changed and no network request was sent to the synthetic external hostname.

## Cleanup and database integrity

- `wp db check`: **PASS**, all tables reported `OK`.
- Post-run table counts: users `1`, passkey credentials `1`, passkey challenges `0`.
- The one remaining credential row is the pre-existing UI-review fixture; its owner/label query returned `1` before and after this test.
- The test itself confirmed the synthetic user and credential row counts matched their pre-run baselines.
- PHP service remained on PID `286942`, port `8101`; MariaDB socket remained present. No service was started, stopped, or restarted.
- No production code, production data, production email, or real authenticator data was used.

## Changed files and hashes

- Added test-only fixture: `tests/phase66a-passkey-review-round2.php`  
  SHA-256: `76cfedb57e3432e61b159495fa20daa9d5d159a2a187114df9ecc8ecf2168a56`
- Added this report. No product source files were changed.

## Limitations

The profile-control check exercised the live WordPress render hook and ordinary PHP profile-save path, but did not click-submit the profile form in a browser. The insecure-origin assertion combined the response and credential-preservation predicates; the test output identifies the criterion failure but did not expose which predicate differed. The exact failed criterion remains **FAIL**, not PASS.

## Stable-candidate verification follow-up — 2026-11-05

The earlier red evidence above is preserved as a point-in-time result. The current candidate was checked independently after the secure-origin and profile-control fixes.

### Source synchronization

The site remained `/home/tonic/zmp-local-sandbox/phase66a-test/site`, database `zmp66a_isolated`, prefix `z66a_`, with only `zmp-competitions` active. PHP services were reused (PID `286942`, port `8101`) and MariaDB remained available. Before synchronization, all four backend PHP files matched the phase source. The Frontend-owned `assets/js/zmp-passkeys.js` differed; the exact phase file was copied to the authorized synthetic plugin, then both hashes matched. No persistent site URL/configuration or tracked UI credential was changed.

### Runtime checks

- **PASS (16), exit 0** — `wp --path=/home/tonic/zmp-local-sandbox/phase66a-test/site eval-file /mnt/z/Documents/Natprevari_ZMP/ZMP_PROJECT_LIBRARY/03_PHASES/zmp-competitions-1.2.4.66.a/tests/phase66a-passkey-review-round2.php` → `PASS (16): focused Task 3 profile and removal-origin behavior`.
- **Initial attempt: FAIL (1/49), exit 1** — the Task 3 suite's synthetic removal POST omitted `HTTP_ORIGIN` and the other request-origin server variables now required by the actual secure-context check. This did not test a browser-origin request. The test-only helper was updated to model the actual authorized loopback origin and to restore each server variable exactly.
- **PASS (49), exit 0 after fixture correction** — `wp --path=/home/tonic/zmp-local-sandbox/phase66a-test/site eval-file /mnt/z/Documents/Natprevari_ZMP/ZMP_PROJECT_LIBRARY/03_PHASES/zmp-competitions-1.2.4.66.a/tests/phase66a-passkey-registration.php` → `PASS (49): Task 3 registration and passkey management behaviors`.
- **PASS** — PHP lint (`php -l`) for `includes/class-zmp-passkeys.php`, `includes/class-zmp-passkey-store.php`, `admin/class-zmp-passkey-profile.php`, `zmp-competitions.php`, `tests/phase66a-passkey-registration.php`, and `tests/phase66a-passkey-review-round2.php`; all reported no syntax errors.
- **PASS, exit 0** — `node --check /mnt/z/Documents/Natprevari_ZMP/ZMP_PROJECT_LIBRARY/03_PHASES/zmp-competitions-1.2.4.66.a/assets/js/zmp-passkeys.js`.
- **PASS, exit 0** — `wp --path=/home/tonic/zmp-local-sandbox/phase66a-test/site db check` → `Success: Database checked.`
- **PASS cleanup** — post-run counts were users `1`, passkey credentials `1`, challenges `0`; the protected UI-review credential query returned `1`. Both runtime fixtures confirmed their temporary user/credential counts returned to baseline. The UI-review credential was not removed or altered.
- No storage suite was rerun because its tested code/configuration did not change.

### Stable-candidate hashes

Phase source and active synthetic plugin copies match:

| File | SHA-256 |
|---|---|
| `includes/class-zmp-passkeys.php` | `569bdbca7cff123be8824b786c86a4466c581db81e69bc7a317fa66058dc4551` |
| `includes/class-zmp-passkey-store.php` | `fa58ed5c1355fb729504e330271260462657cd4ed61ee36410737600db38dea5` |
| `admin/class-zmp-passkey-profile.php` | `57024d14d98b996937cd4d65efd5c397329462ea961a60c5555ac4e80bd1d7e1` |
| `zmp-competitions.php` | `be6ebb302dfb0dfc9e2d5dfe1d7fee080f0b51030accbcb396a4c6b8c5bf4c47` |
| `assets/js/zmp-passkeys.js` | `f5b388f46d2e23a9f278b3bf37d27f02b60bc40b458bc71e7616f30723af1f04` |

Updated test-only files:

- `tests/phase66a-passkey-registration.php`: `7c041c1f6f5c269fb87cdf84d08ef9949d6ce92b52f0b6a65b77bd80fa9c5959`
- `tests/phase66a-passkey-review-round2.php`: `76cfedb57e3432e61b159495fa20daa9d5d159a2a187114df9ecc8ecf2168a56`

The preserved original red result is superseded for the current candidate by **PASS (16)**. Browser form submission, browser WebAuthn enrollment, actual attestation, and phase acceptance remain **NOT RUN**.

## Exact-origin review follow-up — 2026-11-05

After the independent `PASS (16)` / `PASS (49)` run and cleanup above, Code Review identified two narrower secure-origin gaps. The round-2 fixture now includes separate synthetic denial cases. This is the current status; the earlier `PASS (16)` is preserved as evidence for the prior candidate state and must not be used as a current green result.

**Command:**

```text
wp --path=/home/tonic/zmp-local-sandbox/phase66a-test/site eval-file /mnt/z/Documents/Natprevari_ZMP/ZMP_PROJECT_LIBRARY/03_PHASES/zmp-competitions-1.2.4.66.a/tests/phase66a-passkey-review-round2.php
```

**Exit:** `1`  
**Result:** `FAIL (2/18)`

- **FAIL — loopback Host/Origin from a non-loopback client:** expected a rejected response with the exact synthetic credential retained; observed response `accepted`, credential `deleted`.
- **FAIL — HTTPS alternate Host/Origin differing from configured `WP_HOME`:** expected a rejected response with the exact synthetic credential retained; observed response `accepted`, credential `deleted`.
- Existing insecure non-loopback HTTP denial still observed `rejected`, credential `retained`.
- Existing HTTP loopback-origin cases for `localhost` and `127.0.0.1` from a loopback client passed.
- The profile check and ordinary blank-password WordPress save assertions remained green in this run.
- **NOT RUN — matching configured HTTPS positive case:** this site defines `WP_HOME` as `http://127.0.0.1:8101`. A `pre_option_home` filter cannot override the defined constant, and changing stored site URL/configuration was outside this check. The alternate HTTPS mismatch case above was exercised directly without network access.

After the red checks, `wp db check` returned `Success: Database checked.` Counts were users `1`, credentials `1`, challenges `0`; the protected UI-review credential query returned `1`. The synthetic test credentials/users were cleaned. PHP PID `286942` on port `8101` and the isolated MariaDB socket remained active. No product source changed during this follow-up.

The updated round-2 test fixture SHA-256 is `51d32016b51347bda94e63b86464cb93751babda79db1ea1aed1c60299f64cf9`. Backend and JS source/site hashes remain the matching values in the stable-candidate table above. Updated fixture PHP lint passed before the run. The `.66.a` storage suite was not rerun.

## Stable exact-candidate independent verification — 2026-11-05

This round checked the updated candidate after the exact-origin review fixes. Phase source and active synthetic-site hashes were compared before testing and matched; no product files were synchronized or changed in this round.

Target remained `/home/tonic/zmp-local-sandbox/phase66a-test/site`, DB `zmp66a_isolated`, prefix `z66a_`, with `zmp-competitions` active. Existing services were reused: PHP PID `286942` on `127.0.0.1:8101`; the isolated MariaDB socket was present.

### Functional results

- **PASS (18), exit 0** — command:

  ```text
  wp --path=/home/tonic/zmp-local-sandbox/phase66a-test/site eval-file /mnt/z/Documents/Natprevari_ZMP/ZMP_PROJECT_LIBRARY/03_PHASES/zmp-competitions-1.2.4.66.a/tests/phase66a-passkey-review-round2.php
  ```

  Output: `PASS (18): focused Task 3 profile and removal-origin behavior`.
- **PASS (49), exit 0** — command:

  ```text
  wp --path=/home/tonic/zmp-local-sandbox/phase66a-test/site eval-file /mnt/z/Documents/Natprevari_ZMP/ZMP_PROJECT_LIBRARY/03_PHASES/zmp-competitions-1.2.4.66.a/tests/phase66a-passkey-registration.php
  ```

  Output: `PASS (49): Task 3 registration and passkey management behaviors`.
- The earlier exact-origin **FAIL (2/18)** remains in the history above for its earlier candidate hash. This exact candidate passes both added denial cases; the prior red evidence has not been removed or rewritten.
- **PASS** — PHP lint command `php -l` on each of `includes/class-zmp-passkeys.php`, `includes/class-zmp-passkey-store.php`, `admin/class-zmp-passkey-profile.php`, `zmp-competitions.php`, `tests/phase66a-passkey-registration.php`, and `tests/phase66a-passkey-review-round2.php`; all six returned exit 0 with no syntax errors.
- **PASS, exit 0** — `node --check /mnt/z/Documents/Natprevari_ZMP/ZMP_PROJECT_LIBRARY/03_PHASES/zmp-competitions-1.2.4.66.a/assets/js/zmp-passkeys.js`.
- **PASS, exit 0** — `wp --path=/home/tonic/zmp-local-sandbox/phase66a-test/site db check` → `Success: Database checked.`
- **PASS cleanup/baseline** — after both suites, counts were users `1`, passkey credentials `1`, challenges `0`; the protected UI-review credential query returned `1`. Temporary fixtures were removed, and the tracked UI credential was not removed or changed.
- The Task 2 store suite was not rerun because its tested code and configuration did not change. No browser form submit, UI fixture deletion, or browser authenticator ceremony was performed.

### Exact-candidate hashes

Each source file and its active synthetic-site copy had matching SHA-256:

| File | SHA-256 |
|---|---|
| `includes/class-zmp-passkeys.php` | `a7fb96b373390daf83115606769b97f996b543c9a14b4342448d5ff026623b1e` |
| `includes/class-zmp-passkey-store.php` | `fa58ed5c1355fb729504e330271260462657cd4ed61ee36410737600db38dea5` |
| `admin/class-zmp-passkey-profile.php` | `57024d14d98b996937cd4d65efd5c397329462ea961a60c5555ac4e80bd1d7e1` |
| `zmp-competitions.php` | `be6ebb302dfb0dfc9e2d5dfe1d7fee080f0b51030accbcb396a4c6b8c5bf4c47` |
| `assets/js/zmp-passkeys.js` | `f5b388f46d2e23a9f278b3bf37d27f02b60bc40b458bc71e7616f30723af1f04` |

Test file hashes: `phase66a-passkey-registration.php` — `7c041c1f6f5c269fb87cdf84d08ef9949d6ce92b52f0b6a65b77bd80fa9c5959`; `phase66a-passkey-review-round2.php` — `51d32016b51347bda94e63b86464cb93751babda79db1ea1aed1c60299f64cf9`.

These are Test Engineer behavior and syntax/database-integrity results only. UI browser validation and the remaining whole-phase review/acceptance gates remain separate.
