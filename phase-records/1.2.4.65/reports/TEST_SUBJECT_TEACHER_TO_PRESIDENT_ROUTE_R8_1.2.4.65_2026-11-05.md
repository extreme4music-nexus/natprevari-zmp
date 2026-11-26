## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Test Engineer runtime — subject Teacher to active President route (R8)

**Result: FAIL for route acceptance.** The exact independently reviewed candidate executed once. Its POST received HTTP 302, but only 1 of 7 assertions passed; expected redirect state, proposal persistence/event, response privacy, exact cleanup, and the no-outbound-activity check failed. No retry was made. This is not a route PASS.

## Candidate and target

- Fixture SHA-256: `F8EC211A4A95CDA6390A7E01D72D024E0D039074F0258B0D01818D91CCDF15C4` (exact reviewed R8 candidate).
- Independent review: PASS, recorded in `CODE_REVIEW_SUBJECT_REGISTER_ADMIN_POST_R8_1.2.4.65_2026-11-05.md`, SHA-256 `E260AEA8808D99E7BF18AE561BB6FB524187A71BE4D60E71F0CFB93E82581B4A`.
- Test target: fresh synthetic WordPress site `/home/tonic/zmp-local-sandbox/phase65-test/site-subject-r6`, database `zmp65_subject_test` (`z65s_` prefix), loopback port `8097`.
- Preflight confirmed the task paths/database absent and port `8097` unbound. The active site was `/home/tonic/zmp-local-sandbox/phase65-test/site`, database `zmp65_isolated`, PHP PID `145525` on port `8094`.

## Runtime evidence

- Setup performed a fresh WordPress install, activated only `zmp-competitions`, and confirmed one diagnostics option row. Before the fixture, its exact raw option fingerprint (SHA-256 over raw `option_value`, NUL, and `autoload`) was `d30bc258ee187b0dbd18cc9487c9fd7a7e3c8d1359a845000bcd8a9daa53851b` (one event).
- Exact invocation: `wp --path=/home/tonic/zmp-local-sandbox/phase65-test/site-subject-r6 eval-file /mnt/z/Documents/Natprevari_ZMP/zmp-competitions-1.2.4.65/tests/phase65-subject-register-admin-post.php`.
- Server log recorded one `POST /wp-admin/admin-post.php` with HTTP 302. The fixture reported `PASS teacher_submit_http_302`, then `FAIL teacher_submit_redirect_status`, `FAIL teacher_submit_persisted_unique_pending`, `FAIL teacher_submit_event_actor_and_timestamp`, and `FAIL teacher_submit_response_has_no_phrase_or_address`.
- Fixture output: `SUMMARY 1/7 PASS`, with `FAIL exact_cleanup_and_baseline_restoration` and `FAIL mail_and_outbound_network_guard_not_triggered`. The R8 marker idempotence change prevented the prior marker-write exception. The outbound guard detected an attempted non-allowlisted HTTP request and blocked it; it did not establish that external network access succeeded.
- Before task DB disposal, aggregate proposal/event/catalog/alias table counts were `0/0/0/0`, and the sole synthetic administrator remained (`users=1`). The diagnostics option remained present with 3 total events and fingerprint `7c17e0791373001fc28dd5c5c56f851b974e3573b18bf93ee25fb475c4e8f711`; `autoload` was `off`. The fingerprint differed from the activation baseline, so exact diagnostics restoration **FAILED**.
- After fixture cleanup, fixture-owned route MU files and marker files both counted `0`. The fixture nevertheless reported cleanup failure; no changed diagnostics option was selectively overwritten. No proposal text, event text, user names, or credentials were retained here.

## Cleanup and protected state

- Stopped only task PHP PID `281995`; port `8097` was released.
- Dropped only task database `zmp65_subject_test` and removed only the task-owned site and temp paths. Verified database and paths absent and port `8097` unbound.
- The active `zmp65_isolated` database normalized export hash was unchanged pre/post: `b0a08f61824049d80c7f0a350ca9f4d00fbcf317e1227928420b0497ba6064c`. Active PHP PID `145525` remained listening on `8094`.
- Persistent production-clone sandbox was not accessed. No product source or gate-ledger changes were made in this runtime task.
- Preserve prior R3 FAIL and all President photo/consent PASS evidence.
