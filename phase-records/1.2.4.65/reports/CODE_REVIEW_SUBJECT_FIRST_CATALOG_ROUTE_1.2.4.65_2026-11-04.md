# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Code Review — `.65` first-catalog submission route fixture

Reviewer: independent Code Reviewer  
Review mode: static, read-only exact-hash review

## Decision

**PASS** for the reviewed disposable-clone candidate `tests/phase65-subject-first-catalog-admin-post-clone.php`, SHA-256 `8E2B6F6780FF81FA52B0F7764A0F364E01F11F716767E1C361CE1C4AF9A5F0B3`.

The earlier base candidate `tests/phase65-subject-first-catalog-admin-post.php`, SHA-256 `2EA666367D24543A0631277D325A397C3CF9FDD60A55B10710930678E9DE1781`, also passed review but was not executed because its site/DB/prefix/port guards targeted a different clone. The clone-bound candidate differs only in those guards and is tied to `/home/tonic/zmp-local-sandbox/phase65-subject-catalog/site`, database `zmp65_subject_catalog_test`, prefix `z65_`, and loopback port `8100`.

## Reviewed behavior and cleanup

- Requires the exact disposable DB, table prefix, loopback WordPress/admin-post URL, one synthetic administrator, compatible proposal schema, and empty proposal/event/catalog/alias tables.
- Creates one unique synthetic Teacher and request-bound session/nonce, then sends one POST to the real `zmp_submit_subject_proposal` admin-post route.
- Checks the 302 submitted redirect without exposing subject text, pending proposal actor/state, one submitted audit event, and unchanged empty catalog/aliases.
- `finally` restores the original cookie/current user, destroys the created session, removes only identity-matched synthetic rows and Teacher, restores exact diagnostic-option rows, and compares full-row baseline hashes.

## Limitations

This is positive first-catalog submission only. It does not cover negative-role requests, rendered UI, or a dedicated diagnostic-leak assertion. Its `pre_http_request` filter limits requests from the WP-CLI runner to the intended loopback endpoint; it is not an independent outbound-network guard inside the separate PHP server process. These limits were recorded before runtime and remain in force.

No runtime or database operation was performed by the reviewer.
