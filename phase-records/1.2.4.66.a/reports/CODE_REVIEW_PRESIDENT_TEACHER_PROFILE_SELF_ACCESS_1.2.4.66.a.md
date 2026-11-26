# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Code Review — President Self-Service Teacher Profile — `.66.a`

**Date:** 2026-11-12  
**Verdict:** **PASS, static source review only; no findings.**  
**Runtime/UI verification:** **NOT RUN.**

## Exact reviewed source hashes

- `includes/class-zmp-mentor-school-profile.php`: `BED1C58E6D895FA04907D6C53CAA9494946FBF0BF53C13E115D0AF8A834F6C31`
- `includes/class-zmp-school-profile-ui.php`: `FFDC2C810280924E5217CDB4D4083D5B49C6EE02D8194479278CAECD0F450F26`

Both hashes were recomputed and matched the requested candidates.

## Review result

`can_manage_own_teacher_profile()` requires a logged-in current user whose ID exactly matches the requested ID, then permits only an exclusive `zmp_teacher` role or exclusive `zmp_president` role with `view_zmp_president_dashboard` (profile service lines 298–307). `get_profile()`, `get_engagements()`, and `save_profile()` enforce the self-ID/helper check (lines 121–169). Reads enumerate active engagements; profile reads and writes additionally require `owned_school()` for the same mentor and active school ID (mentor-school service lines 241–249). No cross-user or cross-school IDOR path was found in the reviewed calls.

The renderer/save hooks retain `edit_user` checks. The nonce action includes the target user ID and is verified before processing. Submitted profile fields are unslashed and constrained before the service call, whose authorization and active-school checks remain authoritative (UI lines 69–99). The no-mapping `teacher_profile_required` message is distinct from the generic unavailable message (UI lines 20–32).

## Limits

This is a static review of the two exact files only. No tests, database/site access, browser interaction, or runtime validation were performed. This report does not establish feature acceptance or phase acceptance.
