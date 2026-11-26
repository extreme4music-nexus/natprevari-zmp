# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Test Engineer — Passkey Login Options — ZMP 1.2.4.66.a

**Date:** 2026-11-05  
**Status:** **FAIL (expected RED; login API not implemented)**  
**Scope:** Anonymous discoverable passkey login options and a one-use, anonymous challenge. Test-only files; no product implementation changes.

## Environment and safety

- Site: /home/tonic/zmp-local-sandbox/phase66a-test/site, URL http://127.0.0.1:8101.
- Synthetic database: zmp66a_isolated, prefix z66a_.
- Active plugin inventory: only zmp-competitions.
- Existing services were reused: PHP PID 286942 on loopback 8101; MariaDB PID 286676 on its existing private socket. They remained active after the check.
- Pre/post counts: users 1; credentials 1; protected Synthetic UI review only rows 1; challenges 0.
- Test explicitly called wp_set_current_user(0) and supplied no username/account identifier. No test user, credential, challenge or site configuration was changed. The protected UI credential remained untouched.

## Source and fixture provenance

- Current includes/class-zmp-passkeys.php SHA-256: a7fb96b373390daf83115606769b97f996b543c9a14b4342448d5ff026623b1e.
- Current includes/class-zmp-passkey-store.php SHA-256: 88497a37c87c2a9961b4fc3980c535d30d536bf9df31f31cf27c9b04bee02d19.
- New test-only fixture tests/phase66a-passkey-login.php SHA-256: 3c3ee20f8fcad5465fc00999a639943f16e8d8418d672ac32aa1203571a1be69.

## Results

1. **PASS, exit 0** — PHP lint:
   php -l /mnt/z/Documents/Natprevari_ZMP/ZMP_PROJECT_LIBRARY/03_PHASES/zmp-competitions-1.2.4.66.a/tests/phase66a-passkey-login.php
   
   Output: No syntax errors detected in /mnt/z/Documents/Natprevari_ZMP/ZMP_PROJECT_LIBRARY/03_PHASES/zmp-competitions-1.2.4.66.a/tests/phase66a-passkey-login.php.

2. **FAIL (1/5), exit 1 — expected RED** — exact behavior command:
   wp --path=/home/tonic/zmp-local-sandbox/phase66a-test/site eval-file /mnt/z/Documents/Natprevari_ZMP/ZMP_PROJECT_LIBRARY/03_PHASES/zmp-competitions-1.2.4.66.a/tests/phase66a-passkey-login.php
   
   Output:
   FAIL (1/5): anonymous passkey login options
   - anonymous login_options API is implemented
   CLEANUP PASS
   
   ZMP_Passkeys::login_options() is absent in this candidate. The fixture safely skipped invoking it, confirmed no challenge row was created, and returned no account-specific result. The remaining current-run checks passed: anonymous current-user state, unchanged challenge count, no result/account data on the missing-API path, and cleanup. Discoverable options, RP ID, user verification, empty credential allowlist, separate challenges and challenge-row binding remain NOT RUN until the API exists.

3. **PASS, exit 0** — wp --path=/home/tonic/zmp-local-sandbox/phase66a-test/site db check --silent → Success: Database checked.

4. **PASS cleanup/baseline** — post-run counts match pre-run counts; the single protected UI credential remains present; challenge count is zero. Active plugin inventory and PHP/MariaDB services are unchanged.

## Disposition

This is the pre-implementation RED checkpoint for Task 4, not product acceptance. No product code or phase documentation was edited. Preserve prior PASS evidence only for its bound source hashes; this login behavior remains NOT IMPLEMENTED / NOT RUN.



## Additive acceptance update — anonymous login origin/RP binding

Backend has clarified that Task 4 anonymous login challenges must carry a canonical origin/RP digest, while registration remains context-empty and step-up action-context semantics remain unchanged. The canonical digest is `sha256("zmp-passkey-login-origin-v1\n" . canonicalOrigin . "\n" . rpId)`; canonical origin lowercases scheme/host, omits effective default ports, and retains nondefault ports. The login-options fixture was updated to assert this stored digest and now labels the row origin/RP-bound. Its earlier RED/PASS history remains bound to its recorded fixture/source hashes and former empty-context expectation; this revised fixture has not been rerun against the in-progress implementation.

## Corrected candidate verification — 2026-11-05

The revised origin/RP-bound login-options fixture SHA `e17944f50fe7a559458e529c5e70c6c2014998e0c7ae668184a247aaf65c3d01` was run against the corrected candidate (`class-zmp-passkeys.php` `9708137ce291d8d55a00b04e35b2f1b7cd4fdf69efa533fc41c4c8a1ab053925`, store `9aa2ace4b9932db8dd7d158bc22b003ab6d8f46d11b55add101798e54431f95d`, entrypoint `15a87c3ecb1ffdfcace9198d2f206060117396fa9c58ffd80f2a9a00d9cc9b93`) on `/home/tonic/zmp-local-sandbox/phase66a-test/site`, DB `zmp66a_isolated`, prefix `z66a_`: **PASS (9), exit 0**, cleanup PASS. The result is specific to this revised fixture and candidate; the earlier RED remains preserved.

## Churn-fix candidate rerun — 2026-11-05

With only `class-zmp-passkeys.php` changed to SHA `baa1d3d6cf27c7a06ba44a6adc5fca73aad2e61ac5bf9668de4ab6d938c14f9a` (store `9aa2ace4b9932db8dd7d158bc22b003ab6d8f46d11b55add101798e54431f95d`, entrypoint `15a87c3ecb1ffdfcace9198d2f206060117396fa9c58ffd80f2a9a00d9cc9b93`), login-options fixture SHA `e17944f50fe7a559458e529c5e70c6c2014998e0c7ae668184a247aaf65c3d01` returned **PASS (9), exit 0**, cleanup PASS on the existing synthetic site. This is the candidate-specific result; prior pass history remains preserved.
