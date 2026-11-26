# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Test Engineer runtime — subject Teacher to active President route (R7)

**Result: FAIL / route behavior NOT established.** The exact reviewed fixture ran its assertion summary, but its first local POST received HTTP 500 from the fixture MU guard before the plugin route handler could be exercised. No retry was made.

## Candidate and target

- Fixture SHA-256: `9DC8F632FAA57CEF4FA0A8051FA45C94D91A857006E3DA905883EA1A95EE4544` (exact reviewed R7 candidate).
- Independent review: PASS, recorded in `CODE_REVIEW_SUBJECT_REGISTER_ADMIN_POST_R7_1.2.4.65_2026-11-05.md`, SHA-256 `E89BE8AC70D7DC00A7031BF64D931A45E9E0FDB66D369515E31A933E057DB301`.
- Test target: fresh synthetic WordPress site `/home/tonic/zmp-local-sandbox/phase65-test/site-subject-r6`, DB `zmp65_subject_test` (`z65s_` prefix), port `8097`.
- The fixture source hash was rechecked immediately before execution and matched the reviewed candidate.

## Runtime evidence

- Setup created a fresh WordPress install, activated only `zmp-competitions`, and confirmed plugin activation had created one `zmp_debug_events` option row. Its exact row fingerprint (SHA-256 over raw `option_value`, NUL, and `autoload`) before fixture execution was `125dc3452d33491870c2ced5bc7f1d4bf85fb080a4de4e422c7f52b256bad2dd`.
- Exactly one POST reached `/wp-admin/admin-post.php`; the local server returned HTTP 500. The server recorded `RuntimeException: network guard marker failed` in the fixture-owned MU guard before the route handler ran.
- Fixture output: `SUMMARY 0/7 PASS`; the five Teacher-submit response/persistence assertions, exact cleanup, and outbound guard checks all reported FAIL. The fixture also reported `unique proposal row was not recoverable after the submit POST`.
- Route behavior: **NOT established**. The fixture’s assertions ran against the failed response, but the controlled route handler did not execute successfully.
- Postflight aggregate counts before disposal: proposal/event/catalog/alias tables `20/0/0/0`; users `1`; diagnostics option row `1`, diagnostics events `2`; fixture-owned subject-route MU files `0`. No proposal text, event text, user names, or credentials were retained in this report.
- Diagnostics exact restoration: **FAIL**. Postflight diagnostics row fingerprint was `57b5f6b37b37198e9ed12930c90fe0336bdbb3814e319939a78a4fff65dade2b`, different from the activation baseline. The changed option was not selectively overwritten; it was removed only as part of dropping the disposable task database.

## Cleanup and protected state

- Stopped only task PHP PID `281485`; verified port `8097` released.
- Dropped only task database `zmp65_subject_test`; removed only task site and temp paths. Verified database and paths absent and port `8097` unbound.
- Active site remained PHP PID `145525` on `127.0.0.1:8094`, DB `zmp65_isolated`. Normalized full export SHA-256 was unchanged pre/post: `b0a08f61824049d80c7f0a350ca9f4d00fbcf317e1227928420b0497ba6064c`.
- Persistent production-clone sandbox was not accessed. No source or gate-ledger changes were made in this runtime task.
- Preserve prior R3 FAIL and all President photo/consent PASS evidence.
