# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Test Engineer Report — Task 3 Registration Review-Fix Round 1
Phase: ZMP Competitions 1.2.4.66.a
Date: 2026-11-05
Status: FAIL on current candidate; focused product-behavior failures reproduced.

## Target and command
- Synthetic site: /home/tonic/zmp-local-sandbox/phase66a-test/site
- URL: http://127.0.0.1:8101
- Database: zmp66a_isolated, prefix z66a_
- Active plugin: zmp-competitions 1.2.4.66.a
- Test file: tests/phase66a-passkey-registration.php
- Command: wp --path=/home/tonic/zmp-local-sandbox/phase66a-test/site eval-file /mnt/z/Documents/Natprevari_ZMP/ZMP_PROJECT_LIBRARY/03_PHASES/zmp-competitions-1.2.4.66.a/tests/phase66a-passkey-registration.php
- Result: FAIL (7/40), exit status 1. WordPress and the active phase plugin loaded; this is not a runner or environment failure.

## FAIL findings
1. Registration options did not reject a President whose mandate is scheduled but not active; it returned a registration challenge.
2. The denial did not prevent a registration challenge from being written for that inactive President.
3. Registration options did not reject a President with no mandate; it returned a registration challenge.
4. The denial did not prevent a registration challenge from being written for the mandate-less President.
5. In the controlled insert interleaving, the competing handle row was written first and the request's local candidate second; registration options returned the local candidate instead of the deterministic first stored handle.
6. ZMP_Passkeys::remove_credential($user_id, $credential_id, $current_password) is absent.
7. The profile POST action admin_post_zmp_passkey_remove is not registered.

## PASS checks
- Synthetic fixture roles and mandate states were validated: scheduled/no-active, no mandate, and active mandate.
- Active President could receive registration options after current-password verification.
- Duplicate preseeded handle metadata returned the same canonical WebAuthn user handle across repeated options calls without adding more metadata rows.
- PASS — the query-hook interleaving ran after WordPress's unique lookup and inserted the competing handle before the original INSERT; both rows were ordered and the competing row was first.
- FAIL — registration options used the request-local handle, not the first stored race-winner handle in that controlled interleaving.
- Enrollment options left current user roles and capabilities unchanged.
- Test-created user, credential, challenge, mandate, and passkey user-handle metadata counts returned to baseline after cleanup.
- Captured output and new diagnostic-log checks contained no synthetic password or credential identifier values.
- wp db check: PASS.

## NOT RUN
- Wrong-owner, wrong-password, wrong-nonce, and successful demoted-owner credential removal: NOT RUN because both the removal API and profile POST action are absent.
- UI/browser checks: NOT RUN by assignment; UI review remains a separate gate.
- No authenticator attestation was manufactured. No existing credential or password was changed. No email was sent.
- Task 2 storage PASS evidence was not rerun or reset by this review-fix task.

All temporary synthetic accounts and their session tokens, mandates, credential rows, challenge rows, and passkey user-handle metadata were removed. No production resources were accessed.
