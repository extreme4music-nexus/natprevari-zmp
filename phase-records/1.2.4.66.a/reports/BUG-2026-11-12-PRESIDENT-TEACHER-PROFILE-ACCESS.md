# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Bug: President cannot access own teacher profile

Date: 2026-11-12 (Europe/Skopje)  
Phase: `.66.a`  
Status: **SELF-SCOPED ACCESS AND SELF-SETUP IMPLEMENTED; EXISTING-LINK READ/WRITE PASS; NEW SETUP RUNTIME NOT RUN; OWNER UI CHECK REQUIRED**

## Owner requirement

The Owner clarified that the President is first and foremost a teacher and needs their teacher profile.

## Observed evidence

- Owner screenshot shows the authenticated synthetic President on `http://127.0.0.1:8101/wp-admin/profile.php`. WordPress renders the profile page and passkey controls. The “Профил по училиште” section instead shows “Податоците за училишниот профил не се достапни.”
- `includes/class-zmp-school-profile-ui.php` SHA-256 `88EA74E055F0E68FCE9F95F8EE97E75357B903DDBB76785E80199FC554A8FC9D` renders that generic error for any `WP_Error` returned by `get_engagements()`.
- `includes/class-zmp-mentor-school-profile.php` SHA-256 `8379706AB7C13553DECB45CE43CA35772C5735CB5D442459D15A54268AE4FC3B`: `is_teacher()` accepts only the exact role set `['zmp_teacher']` (lines 291–295). `get_engagements()` (lines 139–154) applies this gate before returning the current user's school engagements. President role assignment is exclusive (`zmp-competitions.php` SHA-256 `C0F6CD135F7FBEFB09847FA047F76828736C30C0FA802DAD3F06FDD9C3A4989F`, lines 283–336), so a President is denied even if a linked teacher record exists.
- The isolated synthetic President account has zero rows in the mentor table. Therefore changing only the role check would still not produce school-profile content for this fixture. No account, mentor, or school records were changed.
- Screenshot DevTools shows `admin-ajax.php` 403 entries, but their actions are unknown and the school-profile section is rendered server-side. Those 403s are not established as the cause.

## Safe fix direction

Permit an exact President role to access and edit only their own teacher profile through the existing `zmp_mentors.wp_user_id` mapping, while preserving current self-ownership, active-school, nonce, and write checks. Do not grant a teacher role or expand access to another user's private profile. Determine a supported President-to-mentor profile provisioning path; do not invent or seed real profile data. Add focused behavior and UI verification after implementation.

## Verification status

- Code-path mismatch: **PASS — confirmed by source inspection and independent read-only Debugger**.
- Screenshot observation: **PASS — Owner supplied**.
- Active AJAX 403 causality: **NOT VERIFIED**.
- Whether the President's real/local identity has an eligible mentor-school record: **NOT VERIFIED for real data**; the synthetic test President has none.
- Fix, functional test, UI recheck, independent code review: **NOT RUN**.


## Fix status — 2026-11-12
Implemented narrowly self-scoped President profile access. Isolated-clone self read/write and other-user denial GREEN; independent static code review PASS. Candidate hashes BED1C58E6D895FA04907D6C53CAA9494946FBF0BF53C13E115D0AF8A834F6C31 and FFDC2C810280924E5217CDB4D4083D5B49C6EE02D8194479278CAECD0F450F26. Browser UI review NOT RUN. Current synthetic UI President has no mentor row; the revised message explains the missing mapping. Overall phase remains IN PROGRESS / NOT_READY.

## Owner follow-up — first-time self-profile setup — 2026-11-12
The earlier authorization fix allowed a President to manage an already-linked own mentor profile but did not let an unmapped President create the profile. The candidate now exposes the existing WordPress teacher-profile form to the exact President self account and permits the same validated form-save flow. It fails closed with HTTP 409 if any mentor row already has the same email; no row is linked automatically. No role or account mapping was changed in any database. The code and focused fixture are added, but runtime verification is NOT RUN, browser UI review is OWNER ACTION REQUIRED, and independent review remains pending. This follow-up supersedes the earlier “PROFILE MAPPING REQUIRED” as an implementation limitation; the Owner still supplies their own school data through the form.
## Independent final code-review recheck — President self-profile setup — 2026-11-12
- Exact candidate hashes: `includes/class-zmp-support.php` `CFFB2531DEACF4986FF5C6F6E6EFDAED8F08B84C38F00574788B7D247A33F515`; `includes/class-zmp-mentor-subjects.php` `D9D6BA3B5DD56C20E4CBB1E830A44A3210824B18DA3F7C3A9E920CDEEF87C245`; focused fixture `tests/phase66a-president-teacher-profile-self-setup.php` `5C2C588EE752F2038D9835522E90B070706D4F1518C3F8A5A20733402EF10736`.
- Independent static source review: **PASS**; fixture embedded source-hash consistency: **PASS**. Report `reports/CODE_REVIEW_PRESIDENT_TEACHER_PROFILE_SELF_SETUP_FINAL_RECHECK_1.2.4.66.a_2026-11-12.md`, SHA-256 `1E1979573ED9372DD3C4FD266BFFCB7DD3A30851A241210A8215F336AE07663A`.
- PHP syntax lint: **PASS** for source and fixture. Runtime fixture behavior: **NOT RUN**; all three prior attempts stopped before writes, with the latest generic source-guard mismatch preserved. No retry. Runtime remains BLOCKED / NOT RUN. Concurrency interleavings, lock timeout/release failure, and non-InnoDB fail-closed remain untested.
- Teacher-profile visual review: **OWNER ACTION REQUIRED**. The Owner's UI check is separate from this source review. Passkey registration and successful passkey login remain OWNER-CONFIRMED PASS and are unaffected.
## Owner-supplied President teacher-profile UI screenshots — 2026-11-12
Owner screenshots `Screenshot 2026-11-12 150545.png` and `Screenshot 2026-11-12 150551.png` show the Profile page at 1440×900 with the school/subjects section, active-school area, and per-school teacher-profile fields and directory-visibility controls rendered. Record **OWNER-SUPPLIED VISUAL RENDER PASS**, limited to the shown desktop sections. Do not transcribe the school/contact/email values. The screenshots contain no save confirmation or submit result: save/write behavior remains NOT VERIFIED. Mobile/keyboard and first-time unmapped-account fixture behavior remain NOT RUN; fixture runtime remains BLOCKED before writes after three prior guarded attempts. This does not change passkey criteria (registration and login remain OWNER-CONFIRMED PASS), nor overall `.66.a` readiness (IN PROGRESS / NOT_READY). TE reconciliation: `reports/TEST_ENGINEER_CURRENT_EVIDENCE_RECONCILIATION_1.2.4.66.a_2026-11-12.md`, SHA-256 `F6A13400EAB469A1618D8AE46C8C4DB8B60AC08FB925D2394857A4C53888A6B3`.
