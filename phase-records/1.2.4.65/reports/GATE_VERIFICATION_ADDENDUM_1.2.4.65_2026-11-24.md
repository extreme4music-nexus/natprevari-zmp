# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# .65 gate verification addendum — 2026-11-24

The prior gate ledger dated 2026-11-05 remains preserved as historical baseline: 31 PASS / 4 FAIL / 2 BLOCKED / 10 NOT RUN. Current focused retests are recorded here without rewriting historical results.

## Positive Teacher submission and profile service
The exact .65 source passed **8/8** on a loopback WordPress clone. A synthetic Teacher account had `zmp_teacher`, `subscriber`, Moderator, Commission, Testator and Organizer roles. The teacher school-profile engagement service returned a valid array (an empty result is valid for the blank synthetic school fixture); subject schema preflight passed; actual `admin-post.php` submission returned HTTP 302 with `zmp_subject_status=submitted`; one pending proposal and matching event actor/time were persisted; cleanup restored baseline; no email or outbound network marker fired. Fixture hash: `22C6EA8A296131CBADFC883EE6E8630796FD2EE5C75B8892D5DE5D0E38F18357`. Detailed report: `SUBJECT_ROUTE_SUBSCRIBER_ROLE_RETEST_1.2.4.65_2026-11-24.md`.

## President queue denial
The current .65 source passed the authenticated queue denial check **7/7** with both WordPress auth cookies. Teacher profile positive control returned 200; direct queue GET and nonce-valid decision POST returned 403 without redirect or disclosure; exact cleanup and outbound guard passed. Historical R5 remains unchanged. Evidence: `SUBJECT_PRESIDENT_QUEUE_AUTH_COOKIE_RETEST_1.2.4.65_2026-11-24.md` and fixture `tests/phase65-subject-president-route-denial-auth-cookie.php`.

## Role-stack and scope
The four phase source snapshots `.65`, umbrella `.66`, `.66.a` and `.66.b` have focused 26/26 role/mandate checks each. Teacher capabilities remain available with stacked President, Moderator, Commission, Testator and Organizer roles, provided Subscriber is present. Testator-only and Organizer-only negative controls do not gain Teacher functions. The four per-phase reports document this result. These scoped retests do not close the other historical .65 FAIL/BLOCKED/NOT RUN gates.

The previous scoped reconciliation remains 34 PASS / 2 FAIL / 2 BLOCKED / 9 NOT RUN against the historic 47-gate snapshot. Overall .65 remains NOT READY; no phase-wide acceptance or release readiness is claimed. The fixed root `PROJECT_ROADMAP.md` was not changed.
## School profile repair carried through .66.b
The follow-up found a concrete code defect in `.66.a` and `.66.b`: the school-profile service called a missing own-profile authorization helper. The helper is now present in the `.65`, umbrella `.66`, `.66.a` and `.66.b` source/runtime snapshots. A populated synthetic school-profile form test passed **9/9 on each snapshot**, including Teacher+Subscriber+President/Moderator/Commission/Testator/Organizer access, rejection when another user is current, rendered controls with no unavailable-data notice, exact test data cleanup and database integrity. Per-phase evidence is in `reports/SCHOOL_PROFILE_STACKED_TEACHER_FIX_*.md`. The old profile failure is closed for this tested local flow; historic FAIL/BLOCKED/NOT RUN items outside this scope remain open, and the old ledger is preserved.
## Stacked active President school-email service — 2026-11-24
The current `.65`, `.66`, `.66.a` and `.66.b` local snapshots each passed the scoped school-email appointment/queue/approval/privacy-readback test **8/8** with the President retaining Teacher, Subscriber, Moderator, Commission, Testator and Organizer. Fixture hash `73484807B6536FCE4CCA80BC3325F466E9B31F935F42D56D228F2DD7C9758BC8`; per-phase reports record the exact synthetic DB/prefix and cleanup. This does not alter the historical `president_email_approval_full_instrumented_script` **FAIL 15/25**: the old direct HTTP run's approval assertions passed, but its temporary MU-guard marker assertions failed. The direct `admin-post.php` marker scenario remains unresolved and was not repeated here. No gate-count promotion is made.
## Current Teacher subject route retest — 2026-11-24
The earlier paragraph preserving the R10–R13 outbound-marker attempts remains historically accurate. A new exact-snapshot direct `admin-post.php` retest now passes **8/8** on `.65`, umbrella `.66`, `.66.a` and `.66.b`, with the stacked Teacher+Subscriber+Moderator+Commission+Testator+Organizer role set. Each request returned HTTP 302 `submitted`, persisted one proposal and its actor/time event, and restored the tracked database/debug state. The route-level mail/network guard recorded no marker on any snapshot; a separate URL observer on `.65` also recorded no non-loopback URL. Per-phase evidence is `reports/TEACHER_SUBJECT_ROUTE_STACKED_ADMIN_POST_*.md`. This supersedes the prior “current direct route remains unresolved” status for this scoped current fixture only. Historical run reports and the 34/2/2/9 gate snapshot remain unchanged; unrelated gates remain open.

## Current direct school-email approval route retest — 2026-11-24
The earlier unresolved HTTP-marker note is superseded for the current scoped route: the authenticated synthetic President `admin-post.php` approval test passed **10/10** on `.65`, `.66`, `.66.a` and `.66.b`. Redirect, privacy, decision actor/hash/profile epoch, event history, no unexpected outbound request, and exact cleanup passed. WordPress core update-check requests, when triggered by the local test harness, were blocked by its temporary guard. Per-phase evidence is `reports/SCHOOL_EMAIL_STACKED_PRESIDENT_ADMIN_POST_*.md`. Historical instrumented failure remains preserved and unrelated gates are not promoted by these focused retests.
