# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# School profile role-stack fix — 1.2.4.65 — 2026-11-24

## Defect found
The `.66.a` and `.66.b` school-profile service called `ZMP_Mentor_School_Profile::can_manage_own_teacher_profile()` from `get_profile()`, `get_engagements()` and `save_profile()`, but the method was absent. A local profile read raised `Call to undefined method ZMP_Mentor_School_Profile::can_manage_own_teacher_profile()`, preventing profile data from loading. The earlier 26-check role/mandate suite had not exercised this specific profile-render call path.

## Correction
Added the shared authorization helper to this phase snapshot and synchronized it into its loopback runtime copy. The helper requires an authenticated current owner and `is_teacher()`, which requires both `zmp_teacher` and `subscriber` while allowing President, Moderator, Commission, Testator and Organizer as additional roles. A different current user is rejected. Testator-only and Organizer-only users with Subscriber are also rejected. The same helper is present across .65, umbrella .66, .66.a and .66.b for consistent phase lineage.

## Runtime evidence
Fixture: `tests/phase65-school-profile-stacked-teacher-service.php`; SHA-256 `F7D740BB1E5BA13AB23FCE97CAA8D9C1318A7304290312423030BB18A965476E`. It ran against synthetic database `zmp65_subject_test`, prefix `z65s_`, and only loopback site `http://127.0.0.1:8097`.

**PASS 9/9** on each of the four local phase snapshots:

1. Teacher, Subscriber, Moderator, Commission, Testator and Organizer coexist.
2. Testator+Subscriber without Teacher is denied.
3. Organizer+Subscriber without Teacher is denied.
4. The helper accepts the complete Teacher role stack.
5. The helper rejects the same profile when a different user is current.
6. The engagement service returns the owned school and profile data.
7. The profile renderer produces the form without the unavailable-data notice.
8. Email, employment, workload and visibility controls render.
9. Test-created users and mentor/school/profile rows are removed and DB baselines restored exactly.

PHP lint passed for the modified class in each phase and the shared test fixture. The loopback class hash matches its corresponding phase source hash `EF0080EC174E132DE4906273E3CB4CDD497D743081E037F68E4591F8BE2B2343`. All four local WordPress DB checks passed; the original synthetic administrator remained, and no test-owned profile, school or mentor row remained. No email, YouTube, external network or production site was used.

## Scope
This closes the profile-read/render failure for the tested multi-role teacher with an existing synthetic school engagement. Profile-save POST and production-specific user data have separate evidence scopes. No whole-phase readiness status is implied.