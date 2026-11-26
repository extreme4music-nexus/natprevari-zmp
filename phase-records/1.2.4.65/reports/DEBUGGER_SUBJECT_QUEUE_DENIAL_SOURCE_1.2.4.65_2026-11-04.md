## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Debugger — President subject queue authorization discrepancy

Date: 2026-11-04  
Scope: read-only source diagnosis of the recorded subject-queue denial fixture; no runtime request or site mutation.

## Finding

No product defect is established. `ZMP_President_Subject_Approvals::can_review()` requires an authenticated user, the President dashboard capability, the sole `zmp_president` role and an active mandate; `render()` independently returns 403 when authorization fails. Source: `includes/class-zmp-president-subject-approvals.php`, `can_review()` and `render()` (lines 16–37 and 55–58). The exact current file SHA-256 is `720E0692D8534DD261C68457101E574701198B49C233CF98072B2B8CFD8A16C4`.

The prior negative-role fixture's queue GET returned 302, but did not record the `Location` or a server-resolved actor. Its same-cookie Teacher profile control also returned 302 to login. This does not prove the request was authenticated as a Teacher, so an authenticated Teacher denial remains **NOT VERIFIED**. Existing queue-denial fixtures/hashes are not to be rerun.

## Smallest remaining acceptance

If this route is pursued again, one fresh exact-reviewed diagnostic must first establish a synthetic Teacher session on an allowed profile route, then request the queue and record HTTP status plus a redacted redirect class/role marker. Do not infer the role from a client-supplied cookie or accept a login redirect as an authorization denial. No source change is recommended from this source-only diagnosis.
