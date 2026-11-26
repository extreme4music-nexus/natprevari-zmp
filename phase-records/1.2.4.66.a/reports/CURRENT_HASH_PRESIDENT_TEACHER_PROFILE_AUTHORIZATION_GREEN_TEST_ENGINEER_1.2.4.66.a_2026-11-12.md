# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Test Engineer report — President teacher-profile authorization GREEN (`.66.a`)

**Run time:** 2026-11-12 05:40 UTC  
**Result:** **GREEN / PASS** — one isolated-clone invocation.  
**Scope:** President self teacher-profile authorization, profile read/write and other-user denial.  
**Product-source edits:** none.

## Exact candidate and fixture hashes

The independent Code Reviewer returned exact-hash PASS with no findings for the two changed candidate files exercised by this check:

- `includes/class-zmp-mentor-school-profile.php` — SHA-256 `BED1C58E6D895FA04907D6C53CAA9494946FBF0BF53C13E115D0AF8A834F6C31`.
- `includes/class-zmp-school-profile-ui.php` — SHA-256 `FFDC2C810280924E5217CDB4D4083D5B49C6EE02D8194479278CAECD0F450F26`.

The clone copies matched those hashes. Other guarded/runtime-bound files also matched: mentor schools `72D8EC5C13817C9825539ABA015E765831A95D477432CF846DE05832F54F1B46`; database schema `ED0C587D041C06F00DBC122E3AFF0BF2A8C8708193630BA82E1FEE1A23762E16`; regions `3557AD3BFEC2F701627CB546B16650F3F08C3924AFFBA54B48A3A43450830222`; plugin entrypoint `C0F6CD135F7FBEFB09847FA047F76828736C30C0FA802DAD3F06FDD9C3A4989F`.

After installing the exact reviewed profile-service and renderer files into the disposable clone, all 67 first-party phase files matched the candidate source tree (67 checked, 0 mismatches).

Test-only fixture `tests/phase66a-president-teacher-profile-authorization-helper.php` — SHA-256 `6605AD66A015E43D4BD2EFD788FB0D1CAAB9B93B5954E30FECAA863DBC0D6C6B`. It asserts `can_manage_own_teacher_profile()` for the President self, real profile read/write success, and denial of another user's profile. The prior RED fixture remains unchanged (SHA-256 `7F17C49BFD2768BD8776EF5B446A0DEDA9A573BB6E1DE088D94C70608B4F9502`) and its RED report was not overwritten.

## Environment, baseline and recovery

Only `/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/site` was used: URL `http://127.0.0.1:8102`, database `zmp66a_isolated`, prefix `z66a_`, private MariaDB socket, sole installed/active plugin `zmp-competitions/zmp-competitions.php`. Schema marker `3`, profile schema, school schema and President role checks passed.

Before the run, baseline counts were users/credentials/challenges `1/1/0`; mentors/schools/profiles/proposals/events/mandates/mandate-audit were all zero. The protected synthetic account's credential was present. `wp db check` passed. A fresh clone-only database backup was created under mode `0600` and verified for compressed-stream integrity, non-empty content, table-definition presence and database integrity. Backup identifiers, size, hashes and contents remain private in the sandbox.

Clone MariaDB PID `3828` and private socket remained active. Owner MariaDB PID `409` and Owner PHP PID `25642` on port `8101` remained untouched. No clone PHP listener was started on `8102`. No browser, HTTP, production or external network was used.

## One-run command and behavior result

The exact fixture copy hash was verified before its single invocation. Command:

```text
wp --path=/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/site --quiet eval-file /home/tonic/zmp-local-sandbox/phase66a-retest-20261111/tests/phase66a-president-teacher-profile-authorization-helper.php /home/tonic/zmp-local-sandbox/phase66a-retest-20261111/tests/phase66a-president-teacher-profile-authorization-helper.php
```

Observed result:

- Fixture setup, mentor mapping and active school: **PASS**.
- President self helper authorization: **PASS**.
- President self-profile read: **PASS**.
- President self-profile write: **PASS**.
- Other-user helper, read and write: **DENIED** as required.
- Fixture cleanup and database integrity: **PASS**.
- Process exit: `0`.

This confirms the reviewed candidate's self-scoped helper permits the President's own teacher profile while rejecting access from another account. It does not claim browser/UI verification.

## Cleanup and final state

The fixture removed only the synthetic President's profile row, active-school engagement, mentor row and user, and restored the original current user and debug options. The fixture's full before/after table fingerprint comparison passed. Independent post-run counts returned to users/credentials/challenges `1/1/0`, all fixture-related mapping/profile/proposal/mandate tables returned to zero, and the protected credential remained present. A separate `wp db check` passed.

The clone-private test fixture copy was removed. No fixture state files or test workers remained. The private recovery backup remains in the sandbox. Clone MariaDB PID `3828`, Owner MariaDB PID `409`, Owner PHP PID `25642`/port `8101`, and the private socket were unchanged; port `8102` remained free.