## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Test Engineer runtime — R13 seeded core-update transient

**Result: 6/7 PASS; outbound-network-guard check FAIL.** The exact reviewed R11 fixture ran once on a fresh synthetic site with both WP-Cron disabled and a fresh `update_core` site transient seeded. The Teacher route returned a valid redirect and passed its data/event/cleanup assertions. A network marker still triggered; no further subject-route attempt is authorized by this bounded check.

## Candidate and preflight

- Candidate `tests/phase65-subject-register-admin-post.php` SHA-256: `7540C73BC2ACB33399FE6FA49DFA3B6CD374ABF0593ED439AA635B394BE1F4A1`, rechecked immediately before fixture invocation.
- Independent exact-hash Code Review: PASS, report SHA-256 `FCD75D5D1AE862B6923D397BE902D1A7D662DC680D47656F4AC21F7851B69545`.
- Fresh synthetic site `/home/tonic/zmp-local-sandbox/phase65-test/site-subject-r6`, DB `zmp65_subject_test`, prefix `z65s_`, loopback `127.0.0.1:8097`.
- Preflight confirmed task site/tmp paths and DB absent, port 8097 unbound, and active PHP PID `145525` on 8094. Active DB normalized export SHA-256 before run: `7233a6d75584eff0c43aa350663785a0a9a9afc27b5aa1d5364e1425d2cc3703`.
- `DISABLE_WP_CRON` was set to boolean true before activation; WP-CLI returned true. A site transient `update_core` was seeded with a recent `last_checked`, matching `version_checked`, and an array-valued `updates`. The fixed-field boolean/timestamp validation returned true in WP-CLI and via the task-only HTTP probe (`X-R13-UPDATE-CORE: true`). The same probe returned `X-R13-WPCRON: true`.
- Persistent server session `62895` was alive as task PHP PID `284759` on 8097 for the probe and the route. Candidate hash remained exact.

## One-shot route evidence

- Fixture invocation: `wp --path=/home/tonic/zmp-local-sandbox/phase65-test/site-subject-r6 eval-file /mnt/z/Documents/Natprevari_ZMP/zmp-competitions-1.2.4.65/tests/phase65-subject-register-admin-post.php`.
- Observed substeps: Cron preflight passed; schema preflight passed; Teacher submit POST started; response received and sanitized; proposal rows inspected; allowlisted diagnostic suffix captured.
- Teacher submit returned HTTP **302** with allowlisted `zmp_subject_status=submitted`.
- Assertions **PASS**: schema preflight; HTTP 302; redirect status enum; unique pending proposal persistence; event actor and timestamp; exact diagnostics cleanup and baseline restoration. Fixture summary: **6/7 PASS**.
- Assertion **FAIL**: `mail_and_outbound_network_guard_not_triggered`. Fixture marker kind was `network`; the outbound guard recorded a blocked non-allowlisted attempt. No destination, URL, body, or stack was retained; the destination enum and caller remain **unconfirmed**. No evidence establishes external request success.
- Allowlisted diagnostic tuples: `ZMP test request reached WordPress init.` / `teacher_subject_proposals` / `none`; `Subject proposal submitted.` / `teacher_subject_proposals` / `none`.
- Server logs again included WordPress core update-check connection warnings while processing the request, despite the seeded transient checking true beforehand. The warnings do not prove successful external connectivity and cannot be safely tied to the guard marker. This check does not infer the marker caller from those warnings.

## Cleanup and protected state

- Stopped only task PHP PID `284759` by ending its owned exec session. Verified PID absent and port 8097 released.
- Dropped only DB `zmp65_subject_test`; removed only task site and `/home/tonic/zmp-local-sandbox/phase65-test/tmp/subject-route-r6`. Verified site/tmp absent.
- Active `zmp65_isolated` normalized export hash after cleanup: `7233a6d75584eff0c43aa350663785a0a9a9afc27b5aa1d5364e1425d2cc3703`, equal to pre-run. Active PHP PID `145525` remained on 8094. Persistent production-clone sandbox was not accessed.
- No product-source or gate-ledger changes were made. Preserve R13 outbound-guard FAIL, R12 outbound-guard FAIL, R11 pre-route transport FAIL, earlier R10 FAIL and R9/R3 FAIL statuses, and all President photo/consent PASS evidence.
