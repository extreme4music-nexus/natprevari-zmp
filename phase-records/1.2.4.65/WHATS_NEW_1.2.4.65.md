# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# 🚨 OWNER POLICY — WORDPRESS MEMBER ROLE STACKING

## REQUIRED RULE — READ BEFORE CONTINUING ANY PHASE

**EVERY ZMP MEMBER ACCOUNT MUST HAVE THE WORDPRESS `subscriber` ROLE.**

**`zmp_teacher` IS OPTIONAL FOR A MEMBER, BUT ANY MEMBER MAY ALSO HAVE IT. IT MUST WORK TOGETHER WITH `subscriber` AND WITH OTHER VALID ZMP ROLES WITHOUT BREAKING THE PLUGIN.**

- `subscriber` is the required baseline membership role.
- `zmp_teacher` is additive, not a replacement for `subscriber`, and is not required for every member.
- Members may also hold any authorized combination of `zmp_moderator`, `zmp_commission`, `zmp_testator`, `zmp_organizer`, and `zmp_president`. Role assignment/removal must preserve the other existing roles.
- The President must always retain both `subscriber` and `zmp_teacher`, in addition to `zmp_president` and any other authorized roles.
- An external Commission person who is not a ZMP member may lack `subscriber` and `zmp_teacher`; if that person is a ZMP member, `subscriber` is required and `zmp_teacher` remains optional.
- Teacher-only functions (school profile, teacher directory, teacher subject proposals, and other explicitly teacher-scoped features) require `zmp_teacher`. Missing `zmp_teacher` must not break unrelated plugin functions for a valid Subscriber member.
- Presence of `zmp_teacher` must not make a valid role stack fail, hide unrelated plugin features, replace roles, or crash the plugin. Authorization must check the capability/role needed for each operation rather than requiring an exact one-role array.
- Test fixtures for a ZMP member must include `subscriber`. Teacher-positive fixtures must include both `subscriber` and `zmp_teacher`; negative Teacher-scope fixtures may omit `zmp_teacher` while retaining `subscriber`.

**THIS OWNER POLICY, RECORDED 2026-11-25, SUPERSEDES ANY CONFLICTING HISTORICAL WORDING IN ROADMAPS, PLANS, REPORTS, TEST NOTES, OR PHASE COPIES. HISTORICAL PASS/FAIL RESULTS KEEP THEIR ORIGINAL SCOPE AND MUST NOT BE EXPANDED TO COVER ROLE COMBINATIONS THEY DID NOT TEST.**

---

# What’s New — ZMP Competitions 1.2.4.65

Status: **IN PROGRESS / UNRELEASED**. This is a working-candidate summary; it does not claim release acceptance. Focused backend behaviors have scoped PASS evidence; the rendered subject-catalog unavailable-state UI, external URL reachability, integration and some role-bound routes remain open or NOT RUN.

## 1.2.4.63 — teacher proposals and school register

- Teachers can propose unlisted school subjects and follow President decisions; authorized teachers, moderators, the active President and administrators can use role-checked school register search, pagination and privacy-filtered CSV exports.
- Proposal/register diagnostics are privacy-limited. The original `.63` candidate remains frozen; later documentation/diagnostics carry-forward was recorded in `.64`.

## 1.2.4.64 — school email and employment directory

- Official email and employment details are stored per school engagement. Email requests require an auditable active-President decision; employment fields have separate visibility controls for the internal Teacher-and-higher directory.
- `.64` received independent Auditor PASS on 2026-11-03. Its historical full feature runtime result **FAIL 115/120** remains preserved alongside scoped passing follow-ups; this summary does not erase it.

## 1.2.4.65 — implemented in candidate; acceptance in progress

- The candidate integrates the President, election-document, facsimile, school-register and official-email flows; broad end-to-end and role-route acceptance is still in progress.
- The working candidate fixes a President election-document download form that omitted its required `admin-post` action. The exact-hash code review passes; the post-fix browser response remains unverified after Chrome returned `ERR_BLOCKED_BY_CLIENT`. This correction is unreleased and is not a runtime PASS.
- A President may opt in to showing a validated same-site profile image beside an active mandate on Home. The preference defaults off and can only be changed by that President. Backend checks pass 12/12; the actual self-profile save and restoration pass. Independent consent UI, responsive 390×844 and keyboard focus are PASS. The scoped privacy review combines source review and the Lead's authenticated render: no private filesystem/governance/facsimile URL or unrelated plugin-added field appeared; the UI Reviewer could not independently inspect the Lead-owned tab. Candidate remains unreleased pending whole-phase acceptance.
- School schema drift fails closed and schedules the shared retry after five minutes. The add-school missing-index path now uses the same retry-marking gate; its focused regression passes 13/13 with exact cleanup and DB checks. The approved-email disclosure route also returns a generic refusal without exposing an address during a missing-schema cooldown.
- **Audio UI — scoped Master PASS:** HTTP 401/403 is classified as `probe_denied`, not as a confirmed broken URL. On mobile, every tool has its own six-tab section menu (`Алатка`, `Функција`, `Автоматска проверка`, `Рачна одлука и белешка`, `Проверени услови`, `Дејства`). The `Дејства` panel exposes the manual-review checklist for eligible unapproved rows, including a pending `uncheckable / HTTP 0` row, with an explanatory warning. Independent responsive/keyboard review and the final rendered HTTP 0 plus native-resize focus check are recorded in `reports/AUDIO_UI_ACCEPTANCE_1.2.4.65_2026-11-04.md`. Focused audit regression passes 37/37, negative approval routes 6/6, and positive synthetic local manual approval 21/21. External URL reachability and human opening remain NOT RUN under the local-only boundary. This PASS accepts the Audio UI point only; `.65` remains in progress and unreleased.
- Dashboard What's New includes `.63`, `.64`, and the in-progress `.65` entry. Desktop selector/content checks pass.
- The phase debugging guide also carries forward privacy-safe troubleshooting for `.63` subject proposals, President decisions, school assignments and register/export, and `.64` school profiles, employment visibility, and official-email requests/decisions/disclosure, alongside the active `.65` issues.
- Schema diagnostics on the WordPress AJAX route now capture only the sanitized action name for `schema_not_ready`; the focused regression passes 14/14 and excludes request payloads, nonces and cookies.
- **Candidate follow-up, unreleased:** approved-subject query failure is distinguished from a valid empty catalog and affected writes fail closed. The synthetic query-error regression passes 12/12. The rendered unavailable-state UI remains NOT RUN.
- Fresh-install timestamp schema correction is implemented: schema-drift verification passes 12 assertions; catalog migration passes 6/6; combined clean-install/timestamp-upgrade verification passes 5 assertions. These focused results do not establish whole-phase acceptance.
- Remaining integrated President/register/privacy and role-bound route checks, profile DOM privacy review, package/hash parity, GitHub synchronization, and Auditor checks are not complete. Specific legal/policy approval remains a production rollout gate, not a `.65` technical blocker. No `.65` ZIP or GitHub sync exists.

### Subject catalog schema correction — candidate status
The fresh-install and nullable-timestamp correction is implemented. Focused synthetic schema-drift checks pass 12 assertions; migration passes 6/6 and combined clean-install/timestamp-upgrade verification passes 5 assertions. Approved-catalog query-error behavior passes 12/12. Rendered unavailable-state UI remains NOT RUN. Candidate work is unreleased.

### Subject catalog availability and schema migration — candidate status
On the synthetic `.65` clone, the query-error behavior passes 12/12, schema drift passes 12 assertions, migration passes 6/6, and combined install/upgrade checks pass 5 assertions. A valid empty catalog is distinguished from a database/schema failure; affected subject and school writes fail closed. Rendered unavailable-state UI remains NOT RUN. Candidate work is not released until all `.65` acceptance gates pass.

### President archive access — candidate status
The .65 archive-access behavior passed its focused synthetic transition test (15/15), including direct-predecessor access for the current President and denials for former, unlinked, administrator and unrelated records. This is candidate work and has not shipped; whole-phase acceptance remains open.

### Approved-audio manual review — candidate status
The reviewed synthetic manual-approval admin-post passed 21/21, including reviewer-bound approval and retention of the 403 probe context. No external URL was requested. Candidate work remains unreleased pending all .65 gates.

### President facsimile and certificate submissions — in progress, not released
The authenticated current President can submit an inactive facsimile proposal and register a signed certificate through the President-scoped admin-post routes without being granted an administrator role. The scoped source review and first real UI route flow passed. After the initial key loss, the second inactive facsimile proposal and Certificate C with a distinct fresh key passed through the real UI; Test Engineer verification and scoped Master review passed. This feature remains unreleased pending whole-phase `.65` acceptance.

### Multi-school profile acceptance — candidate-only change, 2026-11-05
- Current Teacher UI save/reload on two synthetic schools demonstrates independent employment term, workload, retired status and per-school visibility for the exercised values. The directory school-scope fix is exact-source reviewed and independently exercised 24/24, including active-school scoping, role checks, per-field visibility and shortcode projection. An anonymous direct-profile GET also redirects to login with private/no-store cache headers. Multi-school remains open because populated-directory responsive/mobile/keyboard UI is BLOCKED / NOT RUN and exact no-mutation for the earlier anonymous fixture is inconclusive. This work is unreleased; no President photo/consent test was repeated.

### Current acceptance status — 2026-11-05
The earlier section describing the President post-loss route as incomplete is superseded by the scoped Test Engineer and Master PASS for facsimile #20 / Certificate C; preserve that history and do not repeat or mutate certificate/facsimile state. The corrected whole `.65` status remains IN PROGRESS / NOT READY FOR AUDITOR.

Multi-school is PARTIAL PASS / OPEN: retain all scoped profile, privacy, role, anonymous-denial and directory runtime PASSes; anonymous exact DB no-mutation remains inconclusive and populated-directory responsive/mobile/keyboard UI is BLOCKED / NOT RUN. Subject proposal → active-President approval remains OPEN / NOT RUN after the candidate stopped pre-assertion at schema readiness and the alternate-clone preflight failed path translation. President photo/consent PASS evidence is preserved.

Remaining independent gates include President direct governance/download response and other route cases, subject unavailable-state UI and authenticated negative-role denial, and school-email approval test integrity (real route behavior is separately evidenced, while its instrumented run is FAIL 15/25 on guard markers and debug-option restoration is unverified). Whole-phase Master, package/hash parity, GitHub readback, Actions and Auditor are NOT RUN. See the dated reports cited in `PLAN_1.2.4.65.md`; do not repeat unchanged PASS checks.


## 2026-11-25 — schema upgrade on ordinary plugin update

A required forward database migration now runs immediately when the stored schema-version marker is confirmed stale, even if an old retry cooldown remains. The five-minute cooldown still throttles repeated attempts when the schema version is current. Verified with an A/B regression on the isolated dump clone. Use the ../../04_RELEASES/VERSIONS/1.2.4.65/ZIP/zmp-competitions-1.2.4.65-schema-retry-fix-2026-11-25.zip package. Details: reports/SCHEMA_UPGRADE_RETRY_COOLDOWN_FIX_1.2.4.65_2026-11-25.md.


## 2026-11-25 — portable BIGINT schema readiness on MySQL

Schema checks now accept BIGINT UNSIGNED whether metadata includes a deprecated integer display width or omits it, while continuing to enforce signedness. This fixes the school-engagement → school-profile → school-email prerequisite chain. The package also includes the forward-migration retry-cooldown correction. Details: reports/MYSQL_INTEGER_WIDTH_SCHEMA_FIX_1.2.4.65_2026-11-25.md. Package: ../../04_RELEASES/VERSIONS/1.2.4.65/ZIP/zmp-competitions-1.2.4.65-mysql-schema-type-fix-2026-11-25.zip.


## 2026-11-25 — Administrator accounts that are also Teachers

A WordPress administrator can now use their own school profile when the account also has the required subscriber and zmp_teacher roles. Administrator alone does not grant Teacher access. Missing teacher-record linkage is reported separately with a clear profile message. Package: ../../04_RELEASES/VERSIONS/1.2.4.65/ZIP/zmp-competitions-1.2.4.65-school-profile-role-fix-2026-11-25.zip. Details: reports/ADMIN_TEACHER_SCHOOL_PROFILE_ROLE_FIX_1.2.4.65_2026-11-25.md.
