# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Runtime gate follow-up — ZMP 1.2.4.66.a

Date: 2026-11-11. This additive readiness note supersedes only migration/rollback rows of 
reports/GATE_READINESS_CURRENT_1.2.4.66.a_2026-11-11.md (SHA-256 83C79F19BA4B4E2BE1CE07160949B9D7678CCF5C94C6F84803BE1F4DE368B092). It is not Master or Auditor approval.

## New independent runtime evidence

Test Engineer report $migration, SHA-256 $mh, is bound to candidate manifest SHA-256 5A88B12F4AAA2D195F7E5C402C16FCCC12744E6FDA392CACAEA1ABD4E7FAE02B and the independently verified .65 rollback ZIP/manifest hashes in that report.

- **PASS — current v3 schema idempotence:** ZMP_Passkey_Store::install_schema() returned true, compatibility check and wp db check passed, schema marker remained 3 and schema fingerprint stayed unchanged.
- **PASS — exact prior-version rollback/reinstall on a disposable synthetic clone:** deactivation and .65 activation preserved schema marker and protected credential. .65 had no passkey implementation; passkey UI/actions were absent. Actual synthetic password login and an unrelated Competition Structure admin render succeeded. Exact .66.a was reinstalled; schema compatibility and DB integrity passed; pretest/post-restore database comparison and baseline matched. Temporary test state was removed. Clone PHP stopped/port 8102 released; original Owner services untouched.
- **NOT RUN — first schema creation from a pre-passkey .65 database:** initial clone already had v3 and a protected synthetic credential. No tables/marker were deleted or downgraded to simulate older state.
- **NOT RUN — partial-migration failure injection:** would require structural corruption/marker alteration and was not attempted.

## Current overall disposition

Overall remains **NOT_READY / .66.a IN PROGRESS**. Current-hash Task3/4/5 focused tests remain PASS49/PASS17/PASS38; scoped Task5 queue UI PASS remains tied to exact matching files. Browser registration/authenticator and login UI, step-up cancellation/unavailability, true concurrency, fresh schema creation, partial failure recovery, current profile UI, Master, Auditor, ZIP and verified GitHub synchronization remain open/not run. No package or sync was created.

The next safe acceptance step for schema coverage is a separate disposable synthetic migration DB/site because the already-tested clone contains a protected passkey credential; do not drop tables or alter the marker in that clone. Preserve the Owner's active site/session and original local services.
