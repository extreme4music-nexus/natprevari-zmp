# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Test Engineer — official-school-email President decision admin-post

Date: 2026-11-04  
Result: **PASS 17/17** for the scoped decision route on the disposable `.65` site.

## Evidence

- Exact reviewed script: `tests/phase65-school-email-decision-admin-post.php`, SHA-256 `CFE6B28E3AAFCA06BEFFCD928F4E19C2FDD152A067B7B19841F3913BB2CC9BCC`.
- PHP lint: **PASS**. Independent exact-hash Code Review: **PASS** after four preserved review FAIL checkpoints (listed below). Script execution exit code: **0**.
- Environment: `zmp65_isolated`, prefix `z65_`; pre-run `wp db check` PASS and tested-pair baseline zero; only active plugin was `zmp-competitions`. Existing PHP PID `145525` listened on `127.0.0.1:8094` and remained running. All POSTs used that exact loopback admin-post endpoint; no external URL was contacted.
- Fixtures: synthetic requester Teacher 31, owner Teacher 30, active synthetic President 25. The test did not modify mandate 23 or 24, or scheduled mandate 60 for user 111.
- The test created one uniquely identified pending request through the service, then exercised the HTTP decision route:
  - Anonymous request: denied with a generic HTTP error; no address in the response.
  - Active President session with a bad nonce: HTTP 403 with the generic security response; no address.
  - Teacher/non-President with a valid request-bound nonce: HTTP 302 with generic `decision_failed`; request state and original submitted-event identity/payload remained unchanged.
  - Active President 25 with a valid request-bound nonce and `decision=rejected`: HTTP 302 with `request_rejected`; request status became `rejected`, `decided_by=25`, `decided_at` was set, `approved_email_hash` remained empty and `approved_email_epoch` remained zero. Exactly one rejection audit event was recorded with actor 25 and the expected requester/owner/school IDs. Only the expected submitted and rejected events existed.
- No email address appeared in any route response. The temporary `pre_wp_mail` guard confirmed mail was not invoked.
- Cleanup: exact synthetic request and all linked test events removed; temporary President/Teacher sessions invalidated; mail marker, MU guard/directory and test-script copy removed. Post-run `wp db check`: **PASS**; reverse pair row count: zero. Original approved request ID 1 and event IDs 1–5 remained unchanged; mandate IDs 23/24 and scheduled 60 remained unchanged. Temporary-copy and MU-directory absence were verified.

## Preserved Code Review failures

The following exact-hash reviews were **FAIL** before the final PASS; no test was executed at those hashes:

1. `70DC8F5A00B4C3DAD98CAB7A8C3F0744185A5CB4B42578DE3EE28B56CC7CADC5`: possible insert followed by setup error could leave a fixture without a recorded request ID.
2. `0FB35621C8E4730E4C2769AE562D4444960546AFBD9C0F8461584EF44A29B3E6`: cleanup uncertainty could be overwritten as PASS; a session token could escape final invalidation verification.
3. `FD37E03F5781791F520B01E27DE773E4E42BB61F13A624B1AAEF564FFEFE1B57`: session tracking before cookie-name failure and exact pending request tuple preservation needed strengthening.
4. `32821AB923DBC38AE8F88645353ADC5FBE0C6996FB562E2E39105BAB3561DF1A`: denial assertions needed to preserve the submitted audit event ID and exact payload.

Final corrected hash `CFE6B28E3AAFCA06BEFFCD928F4E19C2FDD152A067B7B19841F3913BB2CC9BCC` received Code Review **PASS** before the single execution.

## Scope still open

This covers denials and the President rejection path only; President approval, unready-schema request submission, other lifecycle routes and AJAX attribution remain open or unverified. The supplied AJAX log's action remains unidentified, and legal/Board approval remains **NOT VERIFIED**. `.65` remains **IN PROGRESS / NOT READY FOR AUDITOR**.
