# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Debugger — `.65` subject register admin-post route — 2026-11-05

## Status
**No confirmed product defect. Actual Teacher proposal and active-President approval route behavior remains NOT VERIFIED.** This is a read-only diagnosis; no runtime or database action was performed.

## Source-path findings
Reviewed current source hashes:

- `includes/class-zmp-mentor-subject-proposals.php`: `8DD89AA7AAFE5A7AB1FC6F83A2B65112865842E9354578BB5A1BE18061C89F69`
- `includes/class-zmp-president-subject-approvals.php`: `720E0692D8534DD261C68457101E574701198B49C233CF98072B2B8CFD8A16C4`
- `includes/class-zmp-mentor-subjects.php`: `A350933909C8046AB98B6F6CA9DC21DBAC68CD090ECC00F4BCCF89EBBF5475F6`

The proposal service requires the caller to be the exact current logged-in `zmp_teacher`, requires a ready schema, normalizes the proposed subject key, and uses a transaction and unique-key check to avoid duplicate pending proposals. A successful proposal inserts a `submitted` event in the same transaction.

The decision service requires the exact current logged-in active President. `approve_new` locks a pending proposal, rejects a normalized catalog/alias match, inserts the canonical subject through the President-only method, and records the `approved_new` event transactionally. The admin-post handler checks exact President/active mandate eligibility, POST method, action, and nonce before calling the decision service and issuing a status redirect. The administrator role is not accepted as President authority.

The source path has plausible authentication, nonce, duplicate-prevention, and audit boundaries. This source reading does not establish successful HTTP execution, the configured user/cookie state, or any route acceptance result.

## Preserved route-test evidence

- Candidate `22395985D379B9428662FBED736B37CB7969622A8FA72518FB17E3BC31D5950B` passed PHP lint and exact-hash Code Review. Its one runtime attempt returned **FAIL 1/3** at `per-request diagnostic trace could not be verified`; cleanup/debug-trace assertions failed and the route acceptance assertions were not reached. The report notes the first local route request was attempted, but does not provide a verified route response or behavior assertion. Preserve as test-instrumentation FAIL and route NOT VERIFIED; do not infer product failure.
- A later exact-reviewed candidate `8454A91516E3882500F0C87A88E33B6CF9551FCD44EDF3957730E0449C2F0A33` ran once on a fresh synthetic clone and returned **FAIL 0/3** at the same per-request diagnostic trace gate. Its server log contained admin-post.php requests, but no sanitized WP_Error/cURL result or route acceptance assertions were captured. The disposable DB/clone and owned server were cleaned up as documented. Preserve this test-only FAIL; it neither passes nor disproves product behavior.
- Current workspace file `tests/phase65-subject-register-admin-post.php` has SHA-256 `7286913590E35F2FB0665F069A8FA33B475ECDFDA3ACDC76898E2AD4BD952F88`, which differs from the two historical candidates above. I did not review or execute this new hash; do not claim it inherited prior review/runtime evidence.
- The earlier precondition stop due an empty catalog is preserved separately. The `.65` schema/migration and approved-subject query-error focused PASSes do not prove the proposal/President admin-post workflow.

Detailed history: `reports/TEST_ENGINEER_SUBJECT_REGISTER_ADMIN_POST_1.2.4.65_2026-11-04.md`; summary: `TEST_REPORT_1.2.4.65.md` subject-route entries.

## Minimal next acceptance check
A single, independently reviewed, bounded run must use an empty ready catalog/alias baseline and real synthetic authenticated request contexts for (1) exact Teacher submission and (2) exact active-President `approve_new` via the registered admin-post handler. Verify the expected redirect/status, one canonical approved row, the pending proposal's decision fields, and the `submitted`/`approved_new` audit events; also verify unauthorized-role/invalid-nonce denials if they are not already covered by valid current evidence. Keep email/network blocked, bind the test to exact installed source hashes, and prove exact cleanup plus post-run DB integrity. Do not reuse either failed candidate or iterate the MU-guard/trace harness; if a compliant browser/session route is unavailable, leave the route NOT RUN and report that blocker.
