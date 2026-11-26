## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Test Engineer runtime — R5 authenticated Teacher subject-queue denial

**Result: FAIL 13/17 fixture checks.** The authenticated Teacher submit positive control succeeded and established the same synthetic session was authenticated in the WordPress request (`logged_in=1`, `role_count=1`). The direct President queue GET returned a login redirect rather than the expected 403, while the nonce-valid decision POST returned a generic 403 without mutation. The fixture also expected three diagnostics events but the route emitted four, so its fail-closed diagnostics restoration check did not pass. No route request was retried.

## Candidate and isolated setup

- R5 candidate SHA-256: `51494D483C0C98D9081CE4C78920AF5F1C7A72FEB3453385AB8799C7C9282246`.
- Independent exact-hash Code Review: PASS, report SHA-256 `A3C14DC01E6912F3A663CBF603731C3AF1D3F91B65C4476A39CDA086FD07192F`.
- Fresh synthetic site `/home/tonic/zmp-local-sandbox/phase65-test/site-subject-r6`, DB `zmp65_subject_test`, prefix `z65s_`, loopback port 8097. Candidate and loaded subject-service source hashes matched their reviewed/preflight values.
- WP-CLI preflight: `DISABLE_WP_CRON=true`, fresh `update_core` transient, and plugin active all **PASS**. HTTP probe in the persistent server session returned Cron=true and update_core=true. PHP task PID `285160` remained listening on 8097 throughout the route requests.
- Active 8094 PHP PID `145525`; active synthetic DB normalized export hash before the run: `7233a6d75584eff0c43aa350663785a0a9a9afc27b5aa1d5364e1425d2cc3703`.

## Route outcomes

- One Teacher submit POST returned HTTP **302**, allowlisted `submitted`; proposal persisted as one pending synthetic Teacher proposal. Authenticated init diagnostics recorded `logged_in=1` and `role_count=1`. These checks **PASS** and establish the cookie on the submit request.
- Using the same cookie, direct queue GET returned HTTP **302** with the allowlisted location class `login`. No synthetic proposal name or decision controls appeared in its response (**PASS**). The expected queue status was 403 with no redirect; that assertion **FAILS**. Because the request redirected, the President queue callback's own 403 path is not proven by this result.
- Using the same cookie and a locally verified nonce, the decision POST returned HTTP **403**, no redirect location, and the generic denial body without the synthetic subject (**PASS**). Proposal, event, catalog and alias snapshots remained unchanged (**PASS**).
- No outbound guard block was observed in the fixture's own guard; its output was the fixed boolean `no`. No destination or URL was captured. This does not establish success of an external request.

## Diagnostics finding and cleanup

- R5 reported **13/17 PASS**. The four failed checks were: expected direct queue 403/no redirect; decision diagnostic classifier; exact diagnostics restoration; combined exact-baseline cleanup.
- Before deleting the disposable database, read-only bounded diagnostics classification observed five stored events total: one prior event followed by four route events. Sanitized classes: submit init (`logged_in=1`, one role), submit success, decision init (`logged_in=1`, one role), and President decision denied (`forbidden`). This matches the product probe behavior: each plugin POST, including the President decision POST, emits an init diagnostic before its route event. R5 expected only the submit init, submit success, and decision denial, so its route suffix was unrecognized.
- The candidate preserved that unexpected diagnostics suffix and did not rewrite the diagnostics option. Its exact diagnostics restoration check therefore **FAILS**. The option's safe raw fingerprint immediately before disposal was `7a57c4c0fed120af1dfe485b041ac224984461979429895b6b2d70db43f77e19`; no claim is made that its prior exact value was restored. The disposable DB was then dropped.
- Stopped only task PHP PID `285160`; port 8097 released. Removed only the task site/tmp and dropped only `zmp65_subject_test`. Postflight confirmed site/tmp absent, database count 0, and port 8097 unbound.
- Active DB normalized export hash after cleanup: `7233a6d75584eff0c43aa350663785a0a9a9afc27b5aa1d5364e1425d2cc3703`, equal to pre-run. Active PID `145525` remained on 8094. Persistent production-clone sandbox was not accessed.

R3 remains historical FAIL; R5 did not confirm the queue's 403 path and no product defect is established. No product source or gate-ledger changes were made. Any follow-up fixture work must account for the additional President-decision init event, preserve unknown diagnostics, and retain the R5 runtime result without retrying its requests.
