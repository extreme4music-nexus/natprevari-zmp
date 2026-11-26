# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# President Lifecycle Route Check — ZMP Competitions 1.2.4.65

Date: 2026-11-03

Environment: isolated synthetic WordPress site `http://127.0.0.1:8094`, database `zmp65_isolated`; phase-owned PHP PID 145525 and MariaDB PID 144183. No production or external requests.

Independent Test Engineer exercised actual `admin-post.php` routes with temporary synthetic fixtures. No product source edits were made.

## Results

- Invalid nonce returned HTTP 403 for governance-document download, governance archive, admin facsimile download, and certificate registration: **PASS** for these negative nonce checks.
- Synthetic Teacher certificate registration with a valid Teacher-bound nonce returned HTTP 302 and created no certificate: **PASS** for role denial.
- Admin facsimile download returned HTTP 200 and response-body SHA-256 matched the synthetic fixture. Synthetic President without `manage_options` and Administrator with an invalid nonce both received HTTP 403: **PASS** for private-download authorization.
- President governance download/archive and certificate-replacement POST returned HTTP 403 even though the locally generated governance nonce validated with `wp_verify_nonce()`. The CLI-created session/nonce did not produce a valid browser-bound request, so those President HTTP flows are **BLOCKED**; no product defect is inferred.
- Signing-key-loss/recovery over actual HTTP is **BLOCKED** for the same session/nonce limitation. The existing service-level test passes replacement-key registration and certificate supersession; this does not prove the real admin-post route.
- A harness assertion expected an empty body for the Teacher denial; observed response was HTTP 403 with normal WordPress error HTML. The route authorization itself denied correctly; the harness assertion was overstrict.

## Cleanup

The independent Test Engineer reports exact temporary route fixtures, files, created directories, harness, cookies and snapshot outputs removed. Pre/post `wp db check` passed. During follow-up setup work, two additional synthetic President accounts IDs 107/108, their mandate rows 57/58 and fake attachment posts 32/33 were created but no browser action was performed; those exact records were removed, and a read-only query confirmed zero remaining mandate rows for IDs 57/58. Final `wp db check` passed.

## Acceptance boundary

The route tests provide partial negative/admin-route coverage only. They do not close President-bound authenticated workflow, signing-key HTTP recovery, election-document end-to-end, role matrix, or full phase integration gates. A browser-authenticated synthetic President test is still required for those routes.
