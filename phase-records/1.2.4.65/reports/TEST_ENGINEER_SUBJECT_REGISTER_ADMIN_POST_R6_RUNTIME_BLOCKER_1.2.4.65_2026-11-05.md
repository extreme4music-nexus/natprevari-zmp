# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Test Engineer — subject-register admin-post R6 runtime staging blocker — 1.2.4.65

Date: 2026-11-05  
Candidate: `tests/phase65-subject-register-admin-post.php`  
Candidate SHA-256: `06911F1CACD1683D582269DAA360E632945515B873518CC930BACC788C01888C`  
Exact-hash Code Review: **PASS** (`reports/CODE_REVIEW_SUBJECT_REGISTER_ADMIN_POST_R6_1.2.4.65_2026-11-05.md`)  
Runtime result: **NOT RUN — bounded disposable-site staging blocker**

## Preflight

- Candidate workspace SHA matched the reviewed SHA above.
- Target database `zmp65_subject_test`, site `/home/tonic/zmp-local-sandbox/phase65-test/site-subject-r6`, task temp `/home/tonic/zmp-local-sandbox/phase65-test/tmp/subject-route-r6`, and loopback port 8097 were absent/unbound before staging.
- Existing MariaDB PID 144183 and PHP PID 145525 on port 8094 were present and left untouched.
- No existing `zmp65_subject_test` database was found.

## Bounded staging attempts

No WordPress install, plugin activation, HTTP request, route assertion, or fixture execution occurred.

1. A preflight command used a case-sensitive uppercase expected digest while `sha256sum` emitted lowercase. It stopped before creating site/temp/database state.
2. The staging command passed manifest source validation and created only the task site/temp plus `zmp65_subject_test`, then the Windows-authored source manifest's CRLF paths made `tar` treat a trailing carriage return as part of each source path. Staging stopped before WordPress install.
3. One bounded correction normalized those CRLFs. The archive path list was still resolved from the wrong working directory; `tar` stopped before install or plugin activation. No additional correction was attempted.

## Cleanup and disposition

- Dropped only the task-created `zmp65_subject_test` database.
- Removed only `/home/tonic/zmp-local-sandbox/phase65-test/site-subject-r6` and `/home/tonic/zmp-local-sandbox/phase65-test/tmp/subject-route-r6` after exact resolved-path and no-symlink checks.
- Verified database, site and temp absence; port 8097 remains unbound; existing service PIDs 144183 and 145525 remain unchanged.
- No product source or existing site/database was modified. Preserve previous R3 route-denial FAIL and authenticated queue NOT RUN statuses. This R6 candidate's route behavior remains **NOT RUN**; do not treat the source-review PASS or staging cleanup as runtime acceptance.
