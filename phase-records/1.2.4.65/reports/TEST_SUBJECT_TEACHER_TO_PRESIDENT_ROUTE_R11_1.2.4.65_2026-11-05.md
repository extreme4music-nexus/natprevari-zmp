# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Test Engineer runtime — R11 WP-Cron preflight

**Result: FAIL — route transport did not return a response.** The reviewed R11 fixture was invoked once. It reported a local transport failure after the Teacher POST substep began. No retry was made and no product-route PASS is claimed.

## Candidate and preflight

- Candidate: `tests/phase65-subject-register-admin-post.php`, SHA-256 `7540C73BC2ACB33399FE6FA49DFA3B6CD374ABF0593ED439AA635B394BE1F4A1` (rechecked immediately before invocation).
- Independent exact-hash Code Review: PASS, report SHA-256 `FCD75D5D1AE862B6923D397BE902D1A7D662DC680D47656F4AC21F7851B69545`.
- Fresh synthetic site/DB: `/home/tonic/zmp-local-sandbox/phase65-test/site-subject-r6`, DB `zmp65_subject_test`, prefix `z65s_`, loopback `127.0.0.1:8097`.
- Preflight confirmed site/tmp paths and DB absent, 8097 unbound, and active site PHP PID `145525` on 8094. Active DB normalized export SHA-256 before run: `7233a6d75584eff0c43aa350663785a0a9a9afc27b5aa1d5364e1425d2cc3703`.
- `DISABLE_WP_CRON` was set to boolean `true` before activation. WP-CLI evaluation returned `true`. A temporary task-owned MU probe emitted only `X-R11-WPCRON: true` on a harmless local GET; probe file was removed before the fixture invocation.

## One-shot route outcome

- Fixture invocation: `wp --path=/home/tonic/zmp-local-sandbox/phase65-test/site-subject-r6 eval-file /mnt/z/Documents/Natprevari_ZMP/zmp-competitions-1.2.4.65/tests/phase65-subject-register-admin-post.php`.
- Observed substeps: Cron preflight started/passed; schema preflight started/passed; Teacher submit POST started.
- Fixture result: `FAIL: controlled first-submit route stopped at stage=route_runtime_exception`, `TRANSPORT_ERROR_CODE=http_request_failed`, `TRANSPORT_CURL_ERRNO=NOT_AVAILABLE`, followed by `INDETERMINATE: a local admin-post transport response was ambiguous; route cleanup preserved unknown diagnostics.`
- This fixture did not report an HTTP response code or allowlisted redirect status. Proposal persistence, event, and route-cleanup assertions are **NOT RUN / unverified**. Outbound marker outcome is **NOT ESTABLISHED**; no network marker evidence was emitted. The route POST was not retried.
- Runtime observation: the temporary PHP server returned the harmless Cron probe successfully, but no listener was present by fixture invocation/postflight. The server was started in a short-lived WSL shell and did not remain available across shell exit; this is the confirmed environment transport blocker. This does not establish that plugin behavior succeeded or failed beyond the fixture's transport failure.

## Cleanup and protected state

- Removed only the task site and `/home/tonic/zmp-local-sandbox/phase65-test/tmp/subject-route-r6`; dropped only disposable DB `zmp65_subject_test`. Postflight confirmed site/tmp absent, task DB absent, and port 8097 unbound.
- Active `zmp65_isolated` normalized export hash after cleanup: `7233a6d75584eff0c43aa350663785a0a9a9afc27b5aa1d5364e1425d2cc3703`, equal to pre-run. Active PHP PID `145525` remained on 8094. Persistent production-clone sandbox was not accessed.
- Task-local diagnostics fingerprint/readback was not successfully captured before disposal; no exact restoration claim is made. The disposable database was dropped after the ambiguous route attempt.
- No product-source changes or gate-ledger changes were made. Preserve earlier R10 FAIL and R9/R3 FAIL statuses and all President photo/consent PASS evidence.
