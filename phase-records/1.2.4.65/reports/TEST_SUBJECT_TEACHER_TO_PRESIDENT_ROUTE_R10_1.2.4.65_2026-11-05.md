# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Test Engineer runtime — R10 first Teacher submit diagnostic

**Result: 6/7 PASS; outbound-guard check FAIL.** The valid synthetic Teacher submission route executed once and satisfied the schema, HTTP response, redirect enum, persistence, audit event, and exact cleanup assertions. The guard recorded and blocked a non-allowlisted network attempt. No claim is made that an external request succeeded.

## Candidate and setup

- R10 fixture SHA-256: `06DD604E63C0357FFCCBC4D57FB4F837BDF0AED171D879508D35669549CC4692`.
- Independent exact-hash review: PASS, report SHA-256 `40CCE5CFA30D682261897E747266600B16F0D9B3FD01FDD23AB025B52A5624BC`.
- Fresh synthetic target: `/home/tonic/zmp-local-sandbox/phase65-test/site-subject-r6`, DB `zmp65_subject_test` (`z65s_`), loopback `127.0.0.1:8097`.
- Preflight confirmed task site/tmp paths and database absent, port `8097` unbound. Active site was DB `zmp65_isolated`, PHP PID `145525` on port `8094`.
- Plugin activation diagnostics baseline: one option row, `autoload=off`, fingerprint `eb02857fd9bd27c9e147cddc24f915d6be31254ad9859279437555ac37d62a13` (SHA-256 over raw `option_value`, NUL, and `autoload`).

## One-shot route evidence

- Exact invocation: `wp --path=/home/tonic/zmp-local-sandbox/phase65-test/site-subject-r6 eval-file /mnt/z/Documents/Natprevari_ZMP/zmp-competitions-1.2.4.65/tests/phase65-subject-register-admin-post.php`.
- Schema preflight: **PASS**.
- Exactly one Teacher POST returned HTTP **302** and allowlisted `zmp_subject_status=submitted`.
- Allowlisted diagnostic tuples: `ZMP test request reached WordPress init.` / `teacher_subject_proposals` / `none`; `Subject proposal submitted.` / `teacher_subject_proposals` / `none`.
- Assertions **PASS**: schema preflight, HTTP 302, redirect status enum, unique pending proposal persistence, submit event actor/time, exact diagnostics cleanup and baseline restoration.
- Assertion **FAIL**: `mail_and_outbound_network_guard_not_triggered`. The fixture-owned marker proved the marker kind was `network`; the test guard blocked the request. The exact allowed local POST destination was the configured `127.0.0.1:8097/wp-admin/admin-post.php`; the marker is emitted only for a request outside that allowlist. The request destination was not retained, and no evidence establishes an external request succeeded.
- Post-test aggregate proposal/event/catalog/alias/user/mentor counts before disposal: `0/0/0/0/1/0`. Diagnostics returned exactly to the activation baseline fingerprint above, with one event and `autoload=off`. Fixture-owned route MU files and marker files counted `0` after fixture cleanup.
- Read-only source trace found no direct `wp_remote_*`, cURL, or socket call in the Teacher submit handler, proposal service, or diagnostics logger. The marker does not retain its destination, so the caller of the blocked network attempt remains **unconfirmed** and is handed to Debugger. Response-body privacy inspection was not part of this diagnostic candidate.

## Cleanup and protected state

- Stopped only task PHP PID `283387`; port `8097` released.
- Dropped only task DB `zmp65_subject_test` and removed only task site/tmp paths. Verified DB and paths absent and port `8097` unbound.
- Active `zmp65_isolated` normalized export hash before and after this R10 attempt: `7233a6d75584eff0c43aa350663785a0a9a9afc27b5aa1d5364e1425d2cc3703` (unchanged). Active PHP PID `145525` remained on `8094`.
- This run's active DB hash is distinct from the earlier historical `b0a08f61824049d80c7f0a350ca9f4d00fbcf317e1227928420b0497ba6064c`; no active-site data was modified. Persistent production-clone sandbox was not accessed.
- No product-source or gate-ledger changes were made during this runtime task. Preserve R9 fixture runtime FAIL, R3 FAIL, and all President photo/consent PASS evidence.
