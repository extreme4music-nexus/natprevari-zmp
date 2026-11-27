# 🚨 CURRENT OWNER STATUS — 2026-11-27 — READ FIRST

**OWNER CONFIRMED 2026-11-27: synthetic Moderator message exchange works, but the installed `messenger-schema-fix.zip` still shows the automatic-cleanup schema warning on the live site. Message exchange is PASS; retention-cleanup readiness is NOT PASS / root cause unknown.** A diagnostic build adds a read-only Messenger checker to the Debugging page. Install the candidate at the top of the release manifest, run that check, and use its result before further migration changes.

Prior local evidence (not live confirmation): reports/MESSENGER_SCHEMA_AUTO_UPGRADE_FIX_1.2.4.66.a_2026-11-27.md; reports/MESSENGER_SCHEMA_FIX_ZIP_BUILD_1.2.4.66.a_2026-11-27.md. Current diagnostic and owner next step: reports/MESSENGER_LIVE_WARNING_DIAGNOSTIC_1.2.4.66.a_2026-11-27.md. Current ZIP and hash: `../../04_RELEASES/VERSIONS/1.2.4.66.a/RELEASE_MANIFEST_1.2.4.66.a.md`.

# HISTORICAL OWNER STATUS — 2026-11-26 — SUPERSEDED


**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL AND ADDITIVE; ANY MEMBER MAY ALSO HAVE IT WITHOUT BREAKING OTHER ROLES OR PLUGIN FEATURES. THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`.**

**HISTORICAL CHECKPOINT ONLY — 2026-11-26:** the record at that date marked items 1–7 complete and listed a then-current ZIP/GitHub readback. It predates the 2026-11-27 source and Messenger schema-marker change. Use the current evidence block above and the release manifest; owner validation of this Messenger fix remains pending.

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


- Direct stacked-President school-email approval route runtime evidence (2026-11-24): 
reports/SCHOOL_EMAIL_STACKED_PRESIDENT_ADMIN_POST_1_2_4_66_a_2026-11-24.md.

- Stacked Teacher subject proposal route runtime evidence (2026-11-24): 
reports/TEACHER_SUBJECT_ROUTE_STACKED_ADMIN_POST_1_2_4_66_a_2026-11-24.md.

- Messenger legacy scoped_threads index repair and synthetic migration evidence (2026-11-24): 
reports/MESSENGER_LEGACY_SCOPED_THREADS_REPAIR_1.2.4.66_a_2026-11-24.md.
- `.65` inherited-fix reconciliation, `.66.a` test results and current open scopes (2026-11-25): `reports/INHERITED_65_FIXES_AND_66A_OPEN_SCOPE_1.2.4.66.a_2026-11-25.md`.
- Focused regression fixtures: `tests/phase66a-inherited65-regression.php`, `tests/phase66a-inherited65-stale-schema-retry.php`, `tests/phase66a-partial-school-profile-schema-fail-closed.php`.
- Current President + Teacher + Subscriber self-setup regression: `tests/phase66a-president-teacher-stacked-profile-self-setup.php` (**10/10 PASS**, 2026-11-25).

- Historical phase-source/ZIP comparison (2026-11-25): reports/PHASE_SOURCE_SERVER_ZIP_PARITY_1.2.4.66.a_2026-11-25.md. Its stale-ZIP finding applied at that checkpoint; the current 2026-11-27 Messenger schema fix ZIP is listed in the release manifest.

- Current open-scope reconciliation (2026-11-25): reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-25.md; distinguishes current PASS scopes from genuinely open work.

## Current owner verification — 2026-11-26

- Seven-item status: `reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`.
- Functional/security regression: `reports/FULL_FUNCTIONAL_SECURITY_REGRESSION_1.2.4.66.a_2026-11-26.md`.
- Current candidate package and hashes: `../../04_RELEASES/VERSIONS/1.2.4.66.a/RELEASE_MANIFEST_1.2.4.66.a.md`.
- Restrictive project license: `LICENSE.md`; third-party notices remain under `vendor/`.
