## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Test Engineer Report — Schema and School Register (`.65`)

Date: 2026-11-03  
Environment: disposable synthetic WordPress `.65` site `/home/tonic/zmp-local-sandbox/phase65-test/site`, database `zmp65_isolated`, table prefix `z65_`; WordPress 7.1.4, Raft theme, only `zmp-competitions` active. No production site or real email was accessed.

## School subject proposal and assignment flow

**Result: PASS 9/9 service-level behavior assertions.** Synthetic Teacher A and Teacher B accounts, two schools for Teacher A, one shared school for Teacher B, and one active synthetic President mandate were created in the isolated DB. The test exercised the product services directly; it did not submit browser forms or HTTP requests.

| Check | Result |
|---|---|
| Active synthetic President recognized | PASS |
| Repeated normalized proposal is idempotent and remains pending | PASS |
| Pending proposal is absent from approved subject catalog | PASS |
| Teacher cannot decide a proposal | PASS — `forbidden` |
| Active President approval creates exactly one active canonical catalog row | PASS |
| Another teacher can see the approved subject in the picker source | PASS |
| Teacher with two schools can save multiple subject/grade pairs for each school | PASS — two grades on one school, a different year on the second |
| Second teacher can assign the approved subject to the shared school | PASS |
| Rejected proposal creates no active approved catalog row | PASS |

Cleanup: all test users, mentor/school rows, proposal/audit rows, mandate, catalog subject, assignments and grade rows were removed in the fixture `finally` block. Follow-up queries found 0 matching test users, 0 test catalog rows and 0 test schools. `wp db check` PASS.

## Schema drift / school-profile and email prerequisite

**Result: PASS for the tested fail-closed service paths; actual authenticated HTTP path coverage remains NOT RUN.** The test temporarily removed only the required `mentor_active` index from `z65_zmp_mentor_schools`, exercised the profile read, profile save callback, and email-access schema installer, then restored the exact index.

- `ZMP_Mentor_School_Profile::get_profile()` returned `schema_not_ready` with the required index absent.
- `ZMP_School_Profile_UI::save()` set its error notice under the missing-index condition; no profile data was written.
- `ZMP_School_Email_Access::install()` returned false because its school-profile schema prerequisite was not ready.
- `ZMP_Role_Messenger::schema_retry_pending()` returned true after the readiness failure.
- `admin_post_zmp_school_email_request_submit` was registered.
- The index and retry options were restored to the captured baseline. `zmp_db_version` remained `1.2.4.65`; post-test `wp db check` PASS.

An initial call to `submit_request()` using WordPress IDs 1 and 2 without a logged-in synthetic Teacher returned `forbidden`. That input did not satisfy the requester's role/session preconditions and is not counted as a schema-route PASS. A valid authenticated HTTP submission, profile.php request, AJAX request, and post-expiry migration retry were not exercised in this report; the existing phase-scoped retry tests are separate evidence (`phase65-schema-retry-routes.php`, 27/27, and `phase65-schema-trigger-performance.php`, 31/31).

### Follow-up attempt: authenticated browser HTTP routes

**Result: NOT RUN.** The available CUA inventory exposed an existing `.65` Chrome tab, but opening a separate task tab returned `Owl extension-created tabs must be active.` I did not navigate or reuse the existing tab because that would interfere with another task's active browser session. No authenticated request was sent and no route result is inferred from the service-level tests. The relevant source scan found no school-profile or school-email-specific `wp_ajax_` registration; the available school email actions are `admin_post_zmp_school_email_request_submit`, `admin_post_zmp_school_email_access_decide`, and `admin_post_zmp_school_email_access_disclose`. A generic unrelated AJAX action was not substituted.

At the time of this checkpoint, these route checks remained **NOT RUN**: authenticated `user-edit.php` profile save under ready schema and drift; authenticated school-email `admin-post.php` submit under ready schema and drift; any AJAX route tied to these schema prerequisites. The authenticated profile check below supersedes only that profile-save gap.

## Status and remaining scope

- Roadmap point 9 (subject proposals, shared approved catalog, school/grade assignments, approval/rejection): **service-level PASS for the scenarios above; UI/HTTP and duplicate-name/audit checks remain open**.
- Roadmap point 6 (`schema_not_ready`): **focused service/callback fail-closed behavior PASS; actual authenticated profile/AJAX/admin-post route traces and eligible post-cooldown recovery remain NOT RUN here**.
- Roadmap points 1/2 email privacy and history: **NOT RUN in this report**.
- No product source files or persistent clone data were changed. Only phase-disposable synthetic rows/options/index were touched and restored.

### Bounded route follow-up — 2026-11-03

**Result: BLOCKED by WSL access; no new route test ran.** One attempt to access the authorized disposable `.65` site with `wsl.exe -d Ubuntu` returned `Wsl/E_ACCESSDENIED` before entering the distribution. No WordPress command, fixture mutation, database operation, or route request occurred. This is an environment access result, not a product test failure; prior PASS evidence above remains intact.

| Requested route follow-up | Status | Evidence / limit |
|---|---|---|
| President governance/archive callback authorization in an in-process synthetic President context | BLOCKED | WSL refused before the WP-CLI runtime could be reached; no in-process callback invoked |
| Certificate replacement callback authorization in an in-process synthetic President context | BLOCKED | WSL refused before the WP-CLI runtime could be reached; no callback invoked |
| Audio negative/admin-post validation and state transitions | BLOCKED | WSL refused before the WP-CLI runtime could be reached; no request, option, or candidate mutation |

No source, database, or fixture changed during this follow-up. The WSL access block below was later superseded for the focused feature regression only; the President/audio route checks remain blocked or not run as listed.

### Mentor school retry-marker feature regression — 2026-11-03

**Result: PASS 13/13 assertions.** After WSL access was restored, the exact candidate implementation and corrected regression were copied to the `.65` disposable plugin and their hashes matched before execution. Independent Code Review had PASSed the corrected test cleanup at the same test hash.

| Item | Result | Evidence |
|---|---|---|
| Product source | PASS — exact candidate sync | `includes/class-zmp-mentor-schools.php` SHA-256 `72D8EC5C13817C9825539ABA015E765831A95D477432CF846DE05832F54F1B46` matched the disposable site's active plugin copy |
| Regression file | PASS — exact candidate sync | `tests/phase65-mentor-school-retry-marker.php` SHA-256 `DA5672D4986BE63853E3FF23F401B2CA76B35847A3567FBE707F6B8576535904` matched the disposable site's test copy |
| Pre-test database integrity | PASS | `wp db check`: “Success: Database checked.” |
| Regression | PASS 13/13 | `wp eval-file` exit code `0`; controlled missing `mentor_active` index made `add_school()` fail closed with `school_schema_not_ready`, persisted retry intent/deadline, throttled retry before deadline, and did not write a school row |
| Cleanup/readback | PASS | Assertions confirmed schema repair, exact baseline school-row count, zero test rows for synthetic mentor `2147483000`, and restoration of each snapshotted option's exact existence/value/autoload state |
| Post-test database integrity | PASS | `wp db check`: “Success: Database checked.” |
| Independent read-only post-check | PASS | `z65_zmp_mentor_schools` had 2 total rows and 0 rows for synthetic mentor `2147483000`; retry marker/deadline options were absent, while `zmp_db_version` and `zmp_debug_events` retained their post-test baseline autoload flags (`auto` and `off` respectively) |

At the time of the `add_school()` test, that regression covered only school readiness/retry and did not close authenticated President, audio admin-post, or school-profile/AJAX route acceptance. The authenticated Teacher profile route is addressed in the follow-up section below; President, audio and unidentified AJAX routes remain open. No external URL or production site was accessed. No candidate source was edited by the Test Engineer.

### Authenticated school-profile HTTP route — 2026-11-03

**Result: PASS 11/11 assertions** on the exact candidate test SHA-256 `613C08F2F655CA3011ACB2827FF5BEFC65621BB45CB002B6807C903D6B65C5C1`, after exact-hash Code Review PASS. Environment: disposable `.65` WordPress site, database `zmp65_isolated`, prefix `z65_`, PHP PID 145525 on loopback port 8094; only the `.65` plugin was active. The Test Engineer copied the reviewed script to the site, verified the copied hash, ran it once, and removed the temporary copy afterward.

| Check | Result |
|---|---|
| Pre-test `wp db check` | PASS |
| Actual authenticated `/wp-admin/profile.php` GET and rendered nonces | PASS |
| Ready-schema profile save | PASS; requested workload persisted during the test |
| Missing `mentor_active` index profile save | PASS; fail-closed, baseline workload unchanged, valid five-minute retry deadline recorded |
| Assertions / failures | 11 PASS / 0 failures; script's final branch selects exit 0. Wrapper's separate numeric status echo was blank, so a numeric wrapper status is not independently claimed. |
| Post-test `wp db check` and index | PASS; both original index columns verified |
| Cleanup/readback | PASS; exact profile row, user row, listed profile usermeta rows, option existence/value/autoload restored; `_session_tokens` rows and retry options absent; no temporary script remains |
| Services | `.65` PHP PID 145525 remains available on port 8094; not stopped |

This closes only the actual authenticated Teacher profile save path for ready and deliberately incomplete schema. It does not establish the school-email `admin-post` submit/decision routes, any `admin-ajax` route, President-bound routes, register UI, audio `admin-post`, or mobile acceptance. Debugger confirmed the current product source has no profile/email `wp_ajax_*` action; the originating owner log lacks an AJAX `action`, so that log remains unidentified rather than attributed to an invented route. No production site or external destination was accessed.
