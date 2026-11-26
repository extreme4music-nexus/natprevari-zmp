# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Test Engineer — Valid Discoverable Passkey Login

## Scope and status

This is a focused `.66.a` Task 4 test of a cryptographically valid synthetic WebAuthn assertion through the actual anonymous WordPress login-options and login-finish AJAX hooks. It exercises WordPress session creation, one-use challenge consumption, stored signature counter advancement, logout, and exact synthetic cleanup. It does not constitute phase acceptance.

The ephemeral P-256 private key, assertion payload and session cookie exist only in the WP-CLI PHP process. The fixture prints only check labels and aggregate cleanup outcome. It does not implement its own signature verifier; the plugin's installed WebAuthn 5.3.9 validator verifies the assertion.

## Library and fixture design

- Runtime dependency observed in the active `.66.a` plugin: `web-auth/webauthn-lib` 5.3.9.
- The fixture uses the installed CBOR encoder/map/byte-string types to create a COSE ES256 P-256 public key from OpenSSL's in-memory key details.
- It stores a synthetic discoverable credential for a disposable synthetic subscriber, requests options through `wp_ajax_nopriv_zmp_passkey_login_options`, signs `authenticatorData || SHA-256(clientDataJSON)` in memory, and submits through `wp_ajax_nopriv_zmp_passkey_login_finish`.
- It verifies the response, exact authenticated user, valid emitted WordPress session token, stored counter `1`, consumed challenge, and logout/session revocation. Finally it deletes only its owned challenge, credential row and synthetic user, then compares database counts and the complete credential-table fingerprint with the pre-run baseline, including the protected UI credential.

## Candidate and runtime

Pre-run observed site: `/home/tonic/zmp-local-sandbox/phase66a-test/site`; database `zmp66a_isolated`; prefix `z66a_`; active plugin inventory contains only `zmp-competitions`; passkey schema marker `3`; baseline users/credentials/challenges `1/1/0`. Existing PHP PID `286942` served `127.0.0.1:8101`; MariaDB PID `286676` served the isolated site. No service lifecycle change was made.

Pre-run active plugin hashes:

| File | SHA-256 |
|---|---|
| `includes/class-zmp-passkeys.php` | `587CEAA26CB3A023717B0B366249449D0385FC038204FD798D50562473B73DC7` |
| `includes/class-zmp-passkey-store.php` | `F9038DE6499B844529118FE98AB8BD32FE30414BD1061BC35650F638308D02DB` |
| `zmp-competitions.php` | `15A87C3ECB1FFDFCACE9198D2F206060117396FA9C58FFD80F2A9A00D9CC9B93` |

Fixture: `tests/phase66a-passkey-valid-login.php`, SHA-256 `F2C3F9FFBB008C095A4C7493F88161AF2A5CCABC35C54C772B3D2D116028707B`; `php -l` **PASS** (`No syntax errors detected`).

## Results

## Runtime results

The first fixture attempt used SHA-256 `637438047B2F66C66B741A2718E8AFAB41D359E066AAB80A3CB29CCFFF562C3D`. Command: `wp --path=/home/tonic/zmp-local-sandbox/phase66a-test/site eval-file /mnt/z/Documents/Natprevari_ZMP/ZMP_PROJECT_LIBRARY/03_PHASES/zmp-competitions-1.2.4.66.a/tests/phase66a-passkey-valid-login.php`. It returned **FAIL (2/9), exit 1** because a fixture closure had not captured the local site origin variables; PHP emitted undefined-variable warnings and the endpoint could not issue options. Its cleanup passed and returned database counts to baseline. This is a fixture setup failure, not evidence about plugin behavior. The fixture was corrected to capture the exact configured origin; only the corrected focused run follows.

Corrected command (fixture hash above): `wp --path=/home/tonic/zmp-local-sandbox/phase66a-test/site eval-file /mnt/z/Documents/Natprevari_ZMP/ZMP_PROJECT_LIBRARY/03_PHASES/zmp-competitions-1.2.4.66.a/tests/phase66a-passkey-valid-login.php` — **PASS (17), exit 0**. The real options endpoint returned discoverable options; the plugin’s installed validator accepted the in-memory signed assertion; the exact synthetic user became the current user; its emitted WordPress session token verified; the credential counter persisted as `1`; and the challenge was consumed. `wp_logout()` cleared the active user and revoked that session token. No private key, signed payload, cookie or user credentials were printed or persisted.

Cleanup: **PASS**. Users/credentials/challenges returned to `1/1/0`; the complete credential-table fingerprint and protected UI row compared equal within the fixture before/after. A separate sanitized row digest after cleanup was `511b7006cfa53e2bced9ef69b96331783af1472295afe6638d347235575afe6e` (recorded only as a hash; no credential contents were output).

Post-run `wp db check --silent`: **PASS**, output `Success: Database checked.` Site and phase source hashes remained identical: passkeys `587CEAA26CB3A023717B0B366249449D0385FC038204FD798D50562473B73DC7`; store `F9038DE6499B844529118FE98AB8BD32FE30414BD1061BC35650F638308D02DB`; entrypoint `15A87C3ECB1FFDFCACE9198D2F206060117396FA9C58FFD80F2A9A00D9CC9B93`. PHP PID `286942` remained listening on `127.0.0.1:8101`; MariaDB PID `286676` remained active. No source sync, service lifecycle change, unrelated suite rerun or browser test occurred.

This is a focused Task 4 functional **PASS** for synthetic endpoint login/session/counter behavior only. Other Task 4 and phase acceptance gates remain governed by their separate evidence; no phase-level PASS is claimed.

