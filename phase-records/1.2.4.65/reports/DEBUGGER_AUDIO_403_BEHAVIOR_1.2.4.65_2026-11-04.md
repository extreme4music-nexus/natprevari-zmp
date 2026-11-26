# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Debugger — approved-audio HTTP 403 report

**Status: source behavior reconciled; positive route runtime still open.** Read-only inspection only. No external URL was requested and no production site was accessed.

The “Провери URL” control submits a local POST to `admin-post.php` with action `zmp_audio_tool_audit` and a per-tool nonce. The handler checks `manage_options` and the nonce, invokes the server-side URL probe, saves the result, then redirects to the audio-tools admin page. The refresh is expected Post/Redirect/Get behavior.

The current `.65` probe performs a separate server-side HTTPS GET with bounded time/response size, TLS verification, and redirects disabled. It maps 401/403 to `probe_denied`; the UI explains that this server probe denial does not establish that the public link is broken. The user-provided screenshots establish the reported prior UI result, but do not identify the destination URL or the code version that produced it. The live site and URL were not inspected.

Existing synthetic regression `tests/phase65-audio-probe-403.php` intercepts HTTP locally and passes 37/37, including the distinct 403 classification and teacher visibility rules. Existing negative admin-post coverage passes 6/6 for unauthorized submission and incomplete manual-review confirmation. Those results do not exercise a successful positive admin-post audit and rendered redirect notice.

### Follow-up result — synthetic 403 audit admin-post PASS (2026-11-04)

The next-check item above is superseded for the positive *audit* route (not the manual-approval route). Exact-reviewed test candidate `7CB66FF0D4606C8B33ADD3CCA6647ADBD2739FA0C399287AFD9A9FD4D897DB4F` passed 11/11 on a disposable synthetic clone: the actual loopback admin-post request intercepted only the synthetic HTTPS GET as 403, persisted `probe_denied`, did not approve the tool, returned the expected redirect and explanatory UI, issued no external HTTP/email, and restored/removed test state. Clone DB integrity and server/port cleanup passed. No current code defect was confirmed. Earlier 9/10 candidate instrumentation failure remains historical and is superseded only for this same route scenario. The distinct positive manual-review/approval workflow remains open. Full evidence: `reports/TEST_ENGINEER_AUDIO_AUDIT_ADMIN_POST_CANDIDATE_1.2.4.65_2026-11-04.md`.

**Next acceptance check:** in a disposable synthetic WordPress clone, invoke the actual authorized admin-post audit with a synthetic tool and intercept the outbound request using `pre_http_request` to return 403. Assert the exact loopback POST/redirect, persisted `probe_denied`/403 result and explanatory rendered label; prove no external request/email occurred and restore exact option/session/fixture state. Never use the owner’s real destination URL. Browser and server probe differences such as WAF/CDN/IP reputation remain hypotheses, not diagnosed facts.
