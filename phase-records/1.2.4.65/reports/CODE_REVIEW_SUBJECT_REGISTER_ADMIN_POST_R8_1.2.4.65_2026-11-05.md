## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Code Review — Subject Register Admin-Post Fixture R8

Date: 2026-11-05  
Reviewer: Independent Code Reviewer  
Verdict: **PASS — exact-hash fixture review only**  
Candidate: `tests/phase65-subject-register-admin-post.php`  
SHA-256: `F8EC211A4A95CDA6390A7E01D72D024E0D039074F0258B0D01818D91CCDF15C4`

## Scope

Read-only review of the R8 test-fixture marker idempotence correction after the R7 fixture-side HTTP 500. Reviewed the marker writer/validator, create-race path, marker cleanup, and diagnostics-option baseline restoration. No candidate execution or source edits were performed. The workspace root has no Git metadata, so the review verified the supplied candidate hash and inspected the candidate source and prior R7 review report rather than generating a raw Git diff against the R7 source blob.

## Findings

No correctness or safety finding was identified in the reviewed R8 marker correction.

- `tests/phase65-subject-register-admin-post.php:312-352` accepts an existing marker only for `mail` or `network`, rejects symlinks and non-regular files, acquires an exclusive lock, and verifies path/open-handle device and inode identity before and after reading. It bounds marker size and validates the exact JSON key set, current run token, requested kind, and marker file device/inode. This makes repeat writes idempotent only for the same run and same marker kind; malformed, stale, unknown, and cross-kind markers fail closed.
- `tests/phase65-subject-register-admin-post.php:354-360` handles an exclusive-create race by revalidating a marker that appeared after the initial absence check. A failed create with no marker present remains a fail-closed error.
- `tests/phase65-subject-register-admin-post.php:362-398` validates the newly created marker's regular-file status and path/handle identity around payload write, flush, and readback. It serializes the current run token, kind, and created file identity.
- The R7 cleanup path remains present at `tests/phase65-subject-register-admin-post.php:685-725`: it removes only the task-named guard after identity/hash/token verification, validates a marker against the current run token and file identity before cleanup, and requires the marker path to be absent afterward. Unknown marker state is preserved and cleanup fails closed.
- The diagnostics baseline preservation/restoration block remains at `tests/phase65-subject-register-admin-post.php:794-914`. It requires the captured baseline prefix plus exactly the six expected route events, conditionally restores the captured raw option value and `autoload`, invalidates option caches, and verifies the exact restored row. A changed or unknown option state is preserved rather than overwritten. This matches the R7 review's accepted cleanup design.
- The reviewed candidate contains fixture changes only; no product source is part of this review scope.

## Disposition and limits

**PASS for the supplied R8 fixture hash and reviewed marker/cleanup logic.** This is not runtime acceptance, product-code approval, route behavior acceptance, or whole-phase acceptance. R8 runtime remains **NOT RUN** pending the separately authorized bounded test. Preserve the R6 and R7 runtime **BLOCKED** history; this review does not promote either runtime result. Preserve all existing President photo/consent PASS evidence unchanged. No phase release is authorized by this report.
