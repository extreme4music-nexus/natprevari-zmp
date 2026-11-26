# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Test Engineer — authenticated multi-school directory cache transport — 1.2.4.65

Date: 2026-11-05

## Result
**PASS for authenticated cache-transport headers only.** One GET to the populated employment directory returned HTTP 200 and rendered two school groups/two person entries for the existing synthetic Teacher #130. The response carried Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0; Pragma: no-cache; and expired Expires. Vary and Age were absent. The response was private and non-cacheable by shared caches within this HTTP response scope. This does not close the broader integrated role/URL/AJAX/REST matrix.

## Target and preflight
- Reused existing site /home/tonic/zmp-local-sandbox/phase65-test/site, URL http://127.0.0.1:8094, DB zmp65_isolated, prefix z65_.
- Existing PHP process PID 145525 on 8094 and MariaDB PID 144183 were confirmed; no server was started or stopped.
- Only active plugin: zmp-competitions. Teacher #130 role: zmp_teacher. Existing DB integrity check passed before the request.
- Route: GET http://127.0.0.1:8094/?page_id=37, authenticated using a short-lived local synthetic Teacher session. Only response status, the specified cache/privacy headers, and content counts were retained. No response body, cookie, token, credential, or profile value was recorded.
- Workspace renderer SHA-256: 255A9673C082F3472DCDEF676DC80E8F63C70AF68B14CD2AC56919F936FD7042.

## Observations
- HTTP status: 200.
- Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0.
- Pragma: no-cache.
- Expires: Wed, 11 Jan 1984 05:00:00 GMT.
- Vary: absent.
- Age: absent.
- Authenticated directory markers: 2 school groups and 2 person entries. No body content is preserved.

## Data integrity and limits
- Raw full DB export SHA-256 before request: b471d10f68aa83480e8edce4419f3ac0aeac0f02fcac16fd6a2565f562c9e696.
- Raw full DB export SHA-256 after request and temporary session cleanup: c03712dc0e6996acd62721fd15fb7ee3a50aa9f4f77943079dc51980f3c9b4d7.
- The export hashes differ. This invocation did not normalize the dump's timestamp comments or retain a row-level preimage, so the delta cannot be classified. Exact no-mutation and session cleanup are NOT VERIFIED; no speculative restoration was attempted. Database integrity after the request passed.
- The task-owned temporary authentication/header/body files and directory were removed by the cleanup trap. No other request, test, or retry was made. Active 8094 service and persistent clone were untouched.
- This reports the server's authenticated response headers; it is not an independent proxy/CDN cache observation.

## Additive read-only diagnosis of the differing DB export fingerprints — 2026-11-05
- Retained pre/post evidence from the authenticated GET run consists only of the two raw full-export hashes, HTTP status/cache headers, and response markup counts (2 school-group markers, 2 person markers). No per-table or session/profile/employment row counts or row-level baseline were captured before the GET, so individual row changes cannot be established.
- The short-lived WP session cleanup called WP_Session_Tokens::destroy() with the exact generated token while it remained in memory. No explicit session-token readback verified deletion; exact session cleanup remains NOT VERIFIED.
- A later read-only pair of consecutive full exports produced unequal raw hashes, but equal hashes after removing the mysqldump “Dump completed on” timestamp comment, confirming that raw export SHA values can vary with a volatile dump footer. This later observation cannot classify the original before/after mismatch and is not evidence that the original request made no DB change.
- A separate read-only aggregate WP-CLI probe omitted global $wpdb and emitted a PHP Warning plus TISDK_CRASH no_attribution. It issued no GET and made no intentional write, but the local crash instrumentation may have appended a sanitized diagnostic event. This possible append is unattributed; no cleanup/reset was attempted. The exact post-probe DB state is therefore treated as potentially changed.
- Preserve the authenticated HTTP cache-header PASS as a separate result. Exact no-mutation and session cleanup remain NOT VERIFIED; no database row was restored or overwritten.
