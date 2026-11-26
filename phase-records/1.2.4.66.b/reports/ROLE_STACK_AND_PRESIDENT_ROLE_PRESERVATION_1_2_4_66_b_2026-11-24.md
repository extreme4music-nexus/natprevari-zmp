# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Role combinations and President role preservation — 1.2.4.66.b — 2026-11-24

## Product rule
Members must retain WordPress subscriber. Where a member also has zmp_teacher, the Teacher capabilities continue to work when stacked with any of the authorized member roles: President, Moderator, Commission, Testator or Organizer. Testator-only and Organizer-only accounts are not automatically treated as Teachers. The Moderator role by itself retains only its existing moderator-scoped directory/register access. Commission-only or external committee accounts do not receive Teacher functions without zmp_teacher. The checks also require a valid active mandate and President capability for President-only operations.

## Defects corrected
- Exact one-role checks had rejected legitimate teacher and President role combinations in profiles, internal directory, register, subject proposal/review, school-email access, President governance, and facsimile/certificate access.
- President appointment eligibility and the administrator's candidate list accepted only a single subscriber or zmp_president role. They now accept the approved member-role stack while requiring subscriber and rejecting unknown roles/direct capabilities.
- President role grant used WP_User::set_role(), which removed existing Teacher and Subscriber roles. It now adds the President role and verifies all original roles remain. Removing the President role preserves the other roles.

## Local runtime results
On a synthetic WordPress installation bound to loopback for this exact phase source, the focused role and mandate suite passed **26/26**:
- subscriber + teacher member accepted as President candidate; President grant preserved Subscriber and Teacher; removal preserved Subscriber and Teacher.
- Administrator candidate list, active mandate eligibility, President subject review, governance read, school-email service/UI and facsimile/certificate access.
- Teacher behavior with Moderator, Commission, Testator and Organizer roles stacked.
- Testator-only and Organizer-only users were not treated as Teachers; missing Subscriber was rejected; exact synthetic users and mandate rows were removed.

The Teacher proposal route behavior is covered by the existing .65 cross-phase route report; this snapshot has direct role-policy and President mandate runtime coverage in the 26-check suite below.

## Source and cleanup checks
- Local runtime code was SHA-256 compared to its matching source phase across .65, umbrella .66, .66.a and .66.b: **52/52 files matched** (13 affected PHP files per phase).
- PHP lint: **54/54** corrected product, route-fixture and focused runtime test files passed.
- All four local databases passed wp db check; only each site's original synthetic administrator remained; tested role-test users and mandate rows were removed.
- Tests used blank/synthetic local data, loopback sites and no outbound network. No production site, real user, email, YouTube or external service was contacted.

## Status boundary
This closes the mixed-role compatibility and President role-preservation defects at the tested scopes in this phase. It does not close other historical .65 FAIL/BLOCKED/NOT RUN gates or certify the full phase. The fixed top-level PROJECT_ROADMAP.md was not changed. ZIP packaging remains for the end of the requested work.


## School profile role-stack fix
The follow-up found and fixed the missing own-profile authorization helper. See 
reports/SCHOOL_PROFILE_STACKED_TEACHER_FIX_1_2_4_66_b_2026-11-24.md for the defect trace and 9/9 rendered profile result on all four local phase snapshots. The earlier 26-check role suite did not exercise this helper call path, so that result remains limited to its stated scope.


## Stacked active President school-email service
The active President appointment, pending-queue read, approval service and privacy-preserving requester status readback passed 8/8 with Teacher+Subscriber+Moderator+Commission+Testator+Organizer retained. This is service-level evidence; the historical 15/25 HTTP marker-instrumentation failure remains separate. See 
reports/SCHOOL_EMAIL_STACKED_PRESIDENT_SERVICE_1_2_4_66_b_2026-11-24.md.
