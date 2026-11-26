# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Code Review — Subject Admin-Post Fixture R11

Date: 2026-11-05  
Reviewer: Independent Code Reviewer  
Verdict: **PASS — exact-hash test-fixture review only**  
Candidate: `tests/phase65-subject-register-admin-post.php`  
SHA-256: `7540C73BC2ACB33399FE6FA49DFA3B6CD374ABF0593ED439AA635B394BE1F4A1`  
Focused note: `reports/TEST_FIXTURE_WP_CRON_PREFLIGHT_R11_1.2.4.65_2026-11-05.md`  
Focused note SHA-256: `CAB9CB8B4162627DF882770BD2167DE22426EAEBEB7FE9310889E18114F5EBB2`

## Scope and evidence

Read-only exact-hash review of R11's cron preflight and retained R10 fixture behavior. Both assigned hashes were verified. Reviewed the ordering of the preflight against fixture writes and POST, synthetic input normalization, one-request limit, guard behavior, diagnostics privacy/restoration, and cleanup. No runtime or edits were performed.

The R11 note records that R10 reached the first Teacher-submit assertions and restored its diagnostics option, while a network guard marker was recorded; the originating request remains unconfirmed. R11 runtime is **NOT RUN**. The note requires confirming `DISABLE_WP_CRON` in both WP-CLI and the PHP HTTP server context before runtime.

## Findings

No code correctness or safety finding was identified in the R11 change.

- **Cron preflight ordering** — `tests/phase65-subject-register-admin-post.php:100-107` requires `defined('DISABLE_WP_CRON') && DISABLE_WP_CRON === true`, records fixed start/fail/pass stage names, and throws a fixed `stage_wp_cron_disabled_required` before it generates the fixture phrase or creates synthetic users/mentor/mandate rows. The sole POST invocation remains later at `:569`; no preflight-failure path reaches it.
- **R10 subject semantics retained** — `:107-120` creates a bounded synthetic phrase and derives its key using the same sanitize, trim/whitespace collapse, accent removal, lowercase fallback, and SHA-256 semantics as `ZMP_Mentor_Subject_Proposals::normalize_name()`. Values are defined before the closures and cleanup code that capture/use them.
- **One local POST and blocked outbound activity retained** — `:452-482, 490-496` contains the single `$post_route()` invocation and accepts only the exact generated local admin-post URL with POST; the temporary MU guard blocks all other outbound HTTP and short-circuits mail (`:301-412`). No redirect is followed.
- **Diagnostic privacy and exact restoration retained** — diagnostic output remains fixed stage names and allowlisted tuples/status codes. Response body, full Location, cookie, nonce, and raw context are not emitted. Cleanup still requires exact baseline prefix, expected tuple suffix, suffix hash, conditional raw-row update/delete, cache invalidation, and exact readback (`:502-542, 606-624, 792-852`). Unknown or partial suffixes remain preserved and cause failure.
- **Synthetic cleanup retained** — feature tables must be empty before fixture setup and only fixture-owned proposal/event rows, users, sessions, guards, and markers are removed. Table snapshots and absence checks remain part of cleanup (`:60-88, 641-790`).

## Disposition and limits

**PASS for the assigned R11 fixture hash and the requested cron preflight behavior.** The code checks the constant in the process running this fixture. The HTTP request is handled by a separate PHP server process, so runtime setup must still verify `DISABLE_WP_CRON === true` in that HTTP context as the focused note specifies; the fixture's WP-CLI preflight alone does not prove the server context. R11 runtime remains **NOT RUN**, and route/network behavior is not newly accepted. Preserve R10's recorded 6/7 result with its blocked network marker, R9 fixture FAIL, R3 FAIL, and all President photo/consent PASS evidence unchanged. No phase release is authorized by this review.
