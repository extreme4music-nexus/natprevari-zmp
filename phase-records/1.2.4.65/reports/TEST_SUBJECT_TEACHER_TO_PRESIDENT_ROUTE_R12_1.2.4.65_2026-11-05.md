## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Test Engineer runtime — R12 persistent-server retry

**Result: 6/7 PASS; outbound-network-guard check FAIL.** This one bounded retry used the exact reviewed R11 fixture after its prior attempt had failed before receiving an HTTP response. The route returned a valid redirect and passed its concrete persistence/event/cleanup assertions. The guard still recorded a network marker, so the route is not fully accepted. No claim is made that an external request succeeded.

## Candidate and preflight

- Candidate `tests/phase65-subject-register-admin-post.php` SHA-256: `7540C73BC2ACB33399FE6FA49DFA3B6CD374ABF0593ED439AA635B394BE1F4A1`, rechecked before the sole fixture invocation.
- Independent exact-hash Code Review: PASS, report SHA-256 `FCD75D5D1AE862B6923D397BE902D1A7D662DC680D47656F4AC21F7851B69545`.
- Fresh synthetic site `/home/tonic/zmp-local-sandbox/phase65-test/site-subject-r6`, DB `zmp65_subject_test`, prefix `z65s_`, loopback `127.0.0.1:8097`.
- Preflight confirmed task site/tmp paths and DB absent, port 8097 unbound, and active site PHP PID `145525` on 8094. Active DB normalized export SHA-256 before run: `7233a6d75584eff0c43aa350663785a0a9a9afc27b5aa1d5364e1425d2cc3703`.
- `DISABLE_WP_CRON` was set to boolean `true` before activation. WP-CLI evaluation returned true. A temporary task-owned MU probe emitted only `X-R11-WPCRON: true` on a harmless local GET; the probe was removed before the route. The PHP server was held in persistent exec session `69520`, PID `284323`, and port 8097 was confirmed listening during both the HTTP probe and route invocation.

## One-shot route evidence

- Fixture invocation: `wp --path=/home/tonic/zmp-local-sandbox/phase65-test/site-subject-r6 eval-file /mnt/z/Documents/Natprevari_ZMP/zmp-competitions-1.2.4.65/tests/phase65-subject-register-admin-post.php`.
- Observed substeps: Cron preflight passed; schema preflight passed; Teacher submit POST started; response received and sanitized; proposal rows inspected; allowlisted diagnostic suffix captured.
- Teacher submit returned HTTP **302** with allowlisted `zmp_subject_status=submitted`.
- Assertions **PASS**: schema preflight; HTTP 302; redirect status enum; unique pending proposal persistence; event actor and timestamp; exact diagnostics cleanup and baseline restoration. Fixture summary: **6/7 PASS**.
- Assertion **FAIL**: `mail_and_outbound_network_guard_not_triggered`. The fixture reported marker kind `network`; the test guard recorded a blocked non-allowlisted attempt. No destination, URL, body, or stack was retained in the evidence. The caller and destination class remain **unconfirmed**; no marker data suitable for a privacy-safe enum classification was available before cleanup. No evidence establishes an external request succeeded.
- Allowlisted diagnostic tuples: `ZMP test request reached WordPress init.` / `teacher_subject_proposals` / `none`; `Subject proposal submitted.` / `teacher_subject_proposals` / `none`.
- The PHP server log confirmed the inbound `POST /wp-admin/admin-post.php` received HTTP 302. It also emitted WordPress update-check connection warnings; they do not establish a successful external connection and are not attributed to the fixture marker.

## Cleanup and protected state

- Stopped only task PHP PID `284323` by ending its owned exec session. Verified PID absent and port 8097 released.
- Dropped only disposable DB `zmp65_subject_test`; removed only task site and `/home/tonic/zmp-local-sandbox/phase65-test/tmp/subject-route-r6`. Verified task DB/site/tmp absent.
- Active `zmp65_isolated` normalized export hash after cleanup: `7233a6d75584eff0c43aa350663785a0a9a9afc27b5aa1d5364e1425d2cc3703`, equal to pre-run. Active PHP PID `145525` remained on 8094. Persistent production-clone sandbox was not accessed.
- No product-source or gate-ledger changes were made. Preserve R11 prior attempt as a separate pre-route transport FAIL, earlier R10 outbound-guard FAIL and R9/R3 FAIL statuses, and all President photo/consent PASS evidence.
