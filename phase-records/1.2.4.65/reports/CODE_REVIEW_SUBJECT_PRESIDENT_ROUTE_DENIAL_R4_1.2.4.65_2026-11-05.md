## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Code Review — Teacher Denial of President Subject Routes R4

Date: 2026-11-05  
Reviewer: Independent Code Reviewer  
Verdict: **FAIL — one concrete cleanup safety finding**  
Candidate: `tests/phase65-subject-president-route-denial-r4.php`  
SHA-256: `D2BFC3B70DCFE7FDB11E417836AB8C2EE7F5B619D0903A8A1FA33264DDC8F8BA`  
Preparation note SHA-256: `981755BFBBA1BE87EA8865748B0DFDE2DEE9B130B72D43A1F3BDCA411797F496`

## Scope

Read-only exact-hash review of the synthetic Teacher cookie/nonce flow, positive submit control, same-cookie President queue and decision denials, response classification/disclosure assertions, outbound guard, and cleanup ownership/restoration. The candidate and supplied preparation-note hashes match. The fixture was not run and no files were edited.

## Finding

### Medium — Diagnostics cleanup overwrites any unexpected current option state

**Location:** `tests/phase65-subject-president-route-denial-r4.php:384-397` (`finally` diagnostics restoration).

**Evidence:** Cleanup unconditionally performs an `INSERT ... ON DUPLICATE KEY UPDATE` using the originally captured `option_value` and `autoload` for every present diagnostics option, or deletes the option by name when its baseline was absent. It does not first verify that the current option still equals the captured baseline plus exactly the three fixture-owned events (the two authenticated Teacher submit events and the President decision-denial event), nor condition the write/delete on the current raw value and `autoload`. Thus a concurrent or otherwise unknown diagnostic change after capture can be overwritten/deleted while `diagnostics_restored` still reports success. This violates fail-closed ownership for exact restoration.

**Acceptance condition:** Before restoration, accept only an unchanged baseline when no fixture request ran, or the exact baseline prefix plus exactly the expected fixture event suffix after route requests. Match the current raw option value and `autoload` in a conditional restore/delete, clear caches, and verify exact readback. Preserve unknown/incomplete/changed state and report cleanup failure instead of overwriting it.

## Reviewed behavior without additional findings

- The exact Cookie is generated for the synthetic Teacher's current session token; the Teacher submission nonce and President-route nonce are generated under that same Teacher identity. The positive control requires a 302 `submitted` result, a unique pending proposal, and the init diagnostic's `logged_in=1` and one-role evidence before denial checks (`:142-213`).
- The same cookie is used for one direct President queue GET and a nonce-valid decision POST. The checks require 403 with no redirect, absence of proposal/decision controls from the queue body, generic decision denial without the synthetic subject string, an allowlisted `teacher_forbidden` diagnostic, and unchanged proposal/event/catalog/alias snapshots (`:223-287`). Response bodies/headers are used only for local assertions and are not printed.
- The `pre_http_request` guard allows only the required loopback route paths and blocks other WordPress HTTP requests while recording only a boolean (`:104-111, 155-156`). No external URL is emitted.
- User/session/proposal/event cleanup is scoped to the generated synthetic identity and exact expected pending/submitted state. Database table snapshots are checked after cleanup (`:304-398`). This otherwise careful ownership handling does not cure the diagnostics-option overwrite finding above.

## Disposition and limits

**FAIL for this exact R4 fixture hash pending remediation of the diagnostics cleanup finding and independent re-review.** Runtime remains **NOT RUN**; no route behavior is accepted by this report. Preserve the historical R3 runtime **FAIL** and its evidence. This review does not promote R4, and it does not alter prior President photo/consent PASS evidence. No phase release is authorized.
