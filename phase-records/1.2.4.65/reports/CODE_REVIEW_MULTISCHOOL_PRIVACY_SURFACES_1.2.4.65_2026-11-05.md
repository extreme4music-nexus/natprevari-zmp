# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Independent Code Review — multi-school profile and directory privacy surfaces — 1.2.4.65

Date: 2026-11-05  
Scope: Read-only review of the `.65` school-profile and employment-directory implementation against `PROJECT_ROADMAP.md` lines 678–683 and `.64/PLAN_1.2.4.64.md` acceptance criteria 1–5. No source, test, database, or browser state was changed.

## Exact candidate hashes

| File | SHA-256 |
|---|---|
| `includes/class-zmp-mentor-school-profile.php` | `8379706AB7C13553DECB45CE43CA35772C5735CB5D442459D15A54268AE4FC3B` |
| `includes/class-zmp-school-profile-ui.php` | `88EA74E055F0E68FCE9F95F8EE97E75357B903DDBB76785E80199FC554A8FC9D` |
| `includes/class-zmp-employment-directory.php` | `255A9673C082F3472DCDEF676DC80E8F63C70AF68B14CD2AC56919F936FD7042` |

## Review result

**PASS — no product-code finding in the reviewed scope.** The new all-schools directory query constrains results to active school IDs returned for the authenticated viewer, then queries only their school keys. An explicit school ID outside that set is rejected. SQL values are prepared; field names/projections are fixed in code. The query omits `official_email`, and returned employment fields are included only when their corresponding visibility flag is enabled (`class-zmp-mentor-school-profile.php:216–279`).

The role gate allows exact Teacher and moderator roles, administrators, and Presidents with the dashboard capability and active mandate. Other/mixed roles are denied (`class-zmp-mentor-school-profile.php:291–305`). Owner profile reads and saves require the current user to be the target, an exact Teacher role, and an owned active school (`class-zmp-mentor-school-profile.php:122–197`). Profile UI rendering requires the WordPress `edit_user` capability; saves use a user-bound nonce and unslash the payload once (`class-zmp-school-profile-ui.php:20–35,66–96`).

The directory renderer is registered as `[zmp_employment_directory]`; anonymous users receive a login notice, and rendered values are escaped (`class-zmp-employment-directory.php:5–23,39–89`). The reviewed profile/directory code registers no feature-specific AJAX or REST endpoint and contains no employment-directory search or export handler. These routes/surfaces are **N/A** for this implementation; school-email admin-post routes are separate.

Directory reads apply WordPress no-cache headers and `Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0` (`class-zmp-mentor-school-profile.php:377–380`). Profile UI transients store only a 60-second `success`/`error` status (`class-zmp-school-profile-ui.php:92–96`). Profile-save diagnostics contain user ID, school ID, an `email_changed` boolean, and outcome; generic errors carry only outcome and a sanitized error code (`class-zmp-mentor-school-profile.php:197,382–387`). The reviewed directory projection does not log employment field values.

## Acceptance evidence map and limits

| Criterion | Status | Evidence and boundary |
|---|---|---|
| Independent per-school values, private defaults, replacement/colleague/date validation | **PASS, scoped** | Prior `.64` named runtime assertions remain valid because the corresponding `.65` validation methods are unchanged; `.65` actual Teacher profile save/reload confirmed distinct school 17/18 values. Do not upgrade the `.64` aggregate runtime result: it remains **FAIL 115/120**. |
| Per-field visibility and role behavior | **PASS, scoped** | `.64` field-projection and role assertions remain scoped PASS; `.65` directory fixture independently passed 24/24, including Teacher allow, Subscriber deny, school bounds, enabled/disabled fields, email omission, and shortcode HTML. |
| Anonymous behavior | **PASS for product behavior** | Exact-reviewed `.65` fixture independently passed 6/6 for service denials and anonymous shortcode output with no school/employment fields. **Database no-mutation is inconclusive**: before/after export checksums differed and there was no pre-run snapshot. Read-only Debugger analysis attributes the difference to the two expected sanitized `zmp_debug_events` writes, but cannot prove the exact prior database state. Preserve those events; do not reset options speculatively. See `TEST_ENGINEER_MULTISCHOOL_ANONYMOUS_DENIAL_1.2.4.65_2026-11-05.md` and `DEBUGGER_MULTISCHOOL_ANONYMOUS_DB_CHANGE_1.2.4.65_2026-11-05.md`. |
| Direct URL / AJAX / REST / search / export | **N/A by source inventory**, with direct HTTP profile-route denial not independently exercised in this scoped review | Profile editing uses core WordPress profile routes/hooks and capability checks; no feature-specific profile/directory AJAX, REST, search, or export handler was found. |
| Cache and logs | **Source PASS, runtime transport limit** | Private no-store headers and non-sensitive diagnostic context are present. A browser response-header check was not run. Prior focused `.64` diagnostics redaction evidence remains scoped and is not elevated to a whole-phase claim. |
| Rendered populated directory responsive/mobile/keyboard | **NOT RUN / BLOCKED** | `.64` plan criterion 5 requires this UI review. UI Reviewer report `UI_REVIEW_MULTISCHOOL_DIRECTORY_1.2.4.65_2026-11-05.md` records that Chrome policy refused `file:` credential access and disallowed equivalent credential-retrieval workarounds. No credential was entered and no UI fixture data changed. This source review cannot substitute for the required browser review. |

This review covers the source diff and route inventory only. It does not close the multi-school acceptance point, the `.65` phase, or any `.64` aggregate failure. No Master/Auditor or release verdict is implied.
