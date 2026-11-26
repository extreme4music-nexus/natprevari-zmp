## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Exact-hash Code Review — first-catalog active-President route fixture — revision 4

## Verdict
**PASS — exact-hash source review.** Candidate `tests/phase65-subject-first-catalog-active-president-route.php`, SHA-256 `76B380696FC578E1064ABACAD3980AA7494736A2BAB2C651F22E7105363094E1`.

## Review scope and evidence
- Verified the six bound product source hashes against the `.65` workspace source for Mentor route, President approvals, proposal service, subject catalog service, President mandate, and diagnostics service.
- Candidate blocks unless site/database/prefix are exact `127.0.0.1:8094` / `zmp65_isolated` / `z65_`, only the ZMP plugin is active, those installed source hashes match, required schemas/classes are ready, all four subject proposal/event/catalog/alias tables are empty, and exactly one `.invalid`-email, Synthetic-display-name President has exactly one current active mandate.
- Actual admin-post flows are constrained to three POSTs at the exact local admin-post URL: Teacher proposal, active-President `approve_new`, and duplicate `approve_new`. Non-loopback HTTP is blocked and counted; `pre_wp_mail` short-circuits any mail and counts it. Response checks inspect body and redirect location for generated Teacher and President logins/emails and the synthetic phrase.
- Readback verifies proposal status/actors/decision timestamp/canonical ID; canonical subject name/key/status/activity/creator/approver; submitted and approved audit event types, actor IDs, timestamps and exact JSON payload; duplicate attempt fails without a second catalog row or audit event.
- Snapshots cover users/usermeta (including exact session state), posts/postmeta, all options, subject tables and mandate/audit tables. Cleanup validates the complete run-owned pending or approved row/event/catalog shape before changing rows. Owned record cleanup uses a transaction and rolls back on any delete/commit failure; user deletion is gated on safe cleanup. Only the exact diagnostic option rows are restored. Transport ambiguity triggers bounded readback only; the route is never repeated.
- Current candidate contains no MU trace-marker/marker-file logic and does not reference or alter the old route candidates.

## Finding resolution
The prior review's P1 cleanup finding is resolved: all run-owned proposal, event and canonical rows are validated as one safe state before cleanup; cleanup uses a database transaction, aborts and rolls back on the first failed deletion, and retains the synthetic Teacher if cleanup ownership/state is uncertain. The private-method, missing helper include, and President no-disclosure closure findings are also resolved in the reviewed source.

## Limits
No runtime was run as part of this review. PHP lint PASS is reported separately by the candidate owner. This verdict approves the test-only candidate source for one bounded runtime attempt after the orchestrator confirms the intended preflight; it does not establish route acceptance or phase completion.
