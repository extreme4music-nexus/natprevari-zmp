## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Exact-hash Code Review — first-catalog active-President route fixture — revision 5

## Verdict
**PASS — exact-hash source review.** Candidate `tests/phase65-subject-first-catalog-active-president-route.php`, SHA-256 `8C885E6CBB867909790032070DE036A56A554E8B8D21A3C384BB7C03E5EDBEB0`.

## Re-review of President precondition
The candidate now pins the already-prevalidated synthetic local President user ID 25 and compares an in-memory SHA-256 fingerprint of login, email, and display label. It also requires the exact sole President role, `.invalid` email suffix, synthetic display label, active-mandate service authorization, and exactly one active mandate row for the current local date. Neither raw identity values nor fingerprint preimage are printed. This avoids a broad account search and stops before fixture creation if the expected synthetic principal or mandate is absent.

## Bound test safety and assertions
The candidate remains bound to `zmp65_isolated`/`z65_`, `127.0.0.1:8094`, the only active ZMP plugin, exact hashes for all six route/service source files, ready proposal schema, and empty proposal/event/catalog/alias tables. It sends exactly three local admin-post POSTs: Teacher submission, President `approve_new`, and duplicate approval. Other HTTP is blocked and counted; mail is short-circuited and counted. Responses are checked for synthetic Teacher/President logins/emails and the generated phrase. Readback verifies proposal, canonical subject and both audit events, including actors, times, and payloads; duplicate approval must not add a subject or event.

State snapshots cover users/usermeta and the created session tokens, posts/postmeta, all options, subject tables, and mandate/audit tables. Cleanup performs bounded readback only after transport ambiguity and never repeats a route request. It validates the full owned pending or approved record set before any cleanup, uses a transaction with rollback on deletion failure, restores only exact diagnostic option rows touched, and deletes the synthetic Teacher only after safe owned-row cleanup. The candidate contains no MU trace-marker/marker-file logic and does not modify/reference the prior route candidates.

## Previously reported findings
The private method, missing `wp_delete_user()` include, President disclosure closure, and partial cleanup findings are resolved in this exact source.

## Limits
This is a read-only fixture review and does not establish product route behavior. Runtime remains **NOT RUN** pending the orchestrator's confirmation and authorized one-shot execution. The candidate owner's PHP lint result is reported separately.
