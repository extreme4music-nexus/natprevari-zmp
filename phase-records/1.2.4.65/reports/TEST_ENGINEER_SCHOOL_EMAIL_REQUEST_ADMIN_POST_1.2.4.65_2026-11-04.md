## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Test Engineer — official-school-email request admin-post

Date: 2026-11-04  
Result: **PASS** for one actual synthetic Teacher request submission on the disposable `.65` site.

## Evidence

- Exact reviewed test script: `tests/phase65-school-email-admin-post-smoke.php`, SHA-256 `A8B97E481302AF7757A2AA18C9011BDF710E9D9EE16E0950FACC2531291A81B0`.
- PHP lint: **PASS**, no syntax errors. Independent exact-hash Code Review: **PASS**.
- Environment: DB `zmp65_isolated`, table prefix `z65_`; only active plugin was `zmp-competitions`. Existing PHP server PID `145525` listened on `127.0.0.1:8094` and was left running. The test used only that loopback endpoint.
- Synthetic requester Teacher ID 31 submitted to synthetic owner Teacher ID 30 for their shared active school. The actual `admin-post` returned HTTP **302**. The database showed the new request as `pending` and exactly one `submitted` event attributed to requester 31.
- A temporary MU `pre_wp_mail` guard intercepted and blocked any mail call. The marker confirmed mail was **not invoked**. No address was returned or disclosed.
- Script exit code: **0**. Its exact cleanup removed the test request and related event rows, invalidated the temporary authentication session, removed the mail marker/guard and its temporary directory, and verified those states.
- Post-run `wp db check`: **PASS**. A readback found zero rows for the tested 31→30 pair. The prior 30→31 approved request (ID 1) and event rows (IDs 1–5) remained unchanged. The temporary script copy was removed and its absence verified.

## Preserved preflight failures

Two earlier exact-reviewed script invocations stopped before any HTTP request:

1. The first failed the disposable-site guard because `$wpdb` was not imported into WP-CLI eval-file scope before checking DB/prefix. No session, mail guard, request, event, or DB change occurred.
2. The second stopped because this site had no `wp-content/mu-plugins` directory. No POST, request, event, session or mail call occurred.

Both were test-script preflight failures, not plugin-behavior failures. The corrected exact-hash script was reviewed before the successful single route execution above.

## Scope still open

This PASS covers only ready-schema Teacher request submission. The President decision route is not covered by this report. The supplied AJAX log's originating action remains unidentified; no related `wp_ajax_*` handler exists in current source. Legal/Board approval remains **NOT VERIFIED**. `.65` remains **IN PROGRESS / NOT READY FOR AUDITOR**.
