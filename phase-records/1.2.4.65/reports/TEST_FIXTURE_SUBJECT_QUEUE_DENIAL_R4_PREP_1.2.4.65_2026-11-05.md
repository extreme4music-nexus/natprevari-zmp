# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Subject queue Teacher denial R4 — test fixture preparation

## Candidate

- Test-only candidate: `tests/phase65-subject-president-route-denial-r4.php`.
- Exact SHA-256: `D2BFC3B70DCFE7FDB11E417836AB8C2EE7F5B619D0903A8A1FA33264DDC8F8BA`.
- PHP lint: **PASS** (`php -l`, no syntax errors).
- Runtime: **NOT RUN**. Awaiting independent exact-hash Code Review PASS.
- Product source and gate ledger: unchanged.

## Concrete scope

R4 preserves R3 as historical FAIL and keeps its isolated fresh-install, user/table baseline, session cleanup, exact diagnostics option restoration, and route guard boundaries. Its positive control now uses the same session-token/auth-cookie construction as the reviewed Teacher submit route: one valid Teacher proposal submit is sent over loopback using the cookie and Teacher nonce. It requires the sanitized redirect status `submitted`, then validates the ordered allowlisted init diagnostic with `logged_in=1`, exactly one role, and the submitted diagnostic actor matching the synthetic Teacher. This establishes that the same HTTP cookie is authenticated before attempting the negative President route checks.

The same cookie is used for one direct President subject queue GET and one decision POST with a valid President-route nonce generated for that Teacher session. The checks require a 403 queue response with no redirect, absence of the synthetic proposal and decision controls in the response, a generic 403 decision denial, and an allowlisted `teacher_forbidden` diagnostic. Proposal, event, catalog, and alias snapshots are compared before and after the negative requests. Final cleanup is limited to the exact synthetic Teacher, session, proposal/event, and captured diagnostics state; unknown diagnostic suffixes are not claimed as route evidence.

The candidate preflights `DISABLE_WP_CRON=true` and a recent synthetic `update_core` transient. The existing outbound guard keeps its deny behavior; output records only a boolean blocked marker and never a URL or destination.

## Next gate

Independent Code Reviewer should review the exact candidate SHA above. Do not run R4 until that exact-hash review passes. Preserve R3 runtime FAIL and its evidence; R4 is a new diagnostic candidate and is not a product fix or acceptance result.
