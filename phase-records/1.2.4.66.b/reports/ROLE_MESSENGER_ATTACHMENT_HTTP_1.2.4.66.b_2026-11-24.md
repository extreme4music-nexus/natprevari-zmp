# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Messenger attachment, download, and export routes — 1.2.4.66.b — 2026-11-24

## Result

**PASS** for the exact .66.b local synthetic runtime. The phase source and active zmp-competitions-66b runtime class-zmp-role-messenger.php matched exactly, SHA-256 $src. Tests used the isolated local WordPress site /home/tonic/zmp-local-sandbox/phase66b-test/site, with only three disposable synthetic users.

| Check | Result |
|---|---|
| Actual loopback multipart reply upload through dmin-post.php | HTTP 302; expected sent flag |
| Attachment row, allowed PDF type, exact byte count and SHA-256 | PASS |
| Private storage outside the web document root, directory mode 0700 and file mode 0600 | PASS |
| Authorized second Moderator download | HTTP 200; returned PDF bytes/hash exactly matched the synthetic source |
| Subscriber with no Moderator assignment downloading the same attachment | HTTP 403; file bytes were not returned |
| Moderator-scoped CSV export | HTTP 200; contained the synthetic thread and reply |
| Exact user, message, notification, attachment, private file and store-directory cleanup | PASS |
| Local database integrity after cleanup | PASS |

The upload used a generated PDF containing no personal or participant data. HTTP requests were sent only to 127.0.0.1:8103. A temporary WordPress HTTP interceptor logged hostnames and blocked background attempts to pi.wordpress.org; no external HTTP request was allowed through. The built-in PHP access log initially contained synthetic nonce query values; those values were immediately redacted, and a follow-up scan found no unredacted nonce. The temporary session JSON, upload/download/CSV bodies, source PDF, and exact private file/directory were removed.

## Reproduction helpers and hashes
role-messenger-attachment-http-stage.php — EF71D5D7CC76F05B03E490D3F2E98ADB3DBA0D97FD7C4BC1F967B4907EF2A0BA
role-messenger-attachment-http-post.php — D20D4D6FC00716602888BDAA76100ECAA358E701345695FD3D9C9EBE603495EE
role-messenger-attachment-readback.php — F07E08FF5ABE0E10A502D4E779DB51CA7CAEA804840AE43F2CDFCC1222EE027B
role-messenger-attachment-http-read.php — C7B85630A76047EB90BB9389E0467AE4DFB0F0EE52FD662937D87C0785F97551
role-messenger-attachment-cleanup.php — 4E622A93E76D24FB55BC89B25E8B93005BE09799DF9AEC8C2154BD3318861561
All helpers are PHP-linted. The session-stage helper generates one-use synthetic cookies/nonces into a mode-0600 file; do not use it against any non-isolated site. Runtime source hash: $runtime.

## Scope

This closes the synthetic Moderator-scope reply attachment, private storage, authorized download, unauthorized denial, and scoped CSV route checks only. Commission/region permission variants, export truncation/large-volume limits, browser accessibility and mobile UI remain separate. No product source was changed, no production site/database was accessed, and no live mail or external service was used.