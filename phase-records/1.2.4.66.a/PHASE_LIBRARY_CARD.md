# 🚨 CURRENT OWNER STATUS — 2026-11-26 — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL AND ADDITIVE; ANY MEMBER MAY ALSO HAVE IT WITHOUT BREAKING OTHER ROLES OR PLUGIN FEATURES. THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`.**

**CURRENT STATUS 2026-11-27: The latest owner live diagnostic finds Messenger schema-compatible YES, global marker 1.2.4.53.2 stale, retry pending; the exact blocking prerequisite is not yet identified. The active root-cause ZIP is zmp-competitions-1.2.4.66.a-2026-11-27-messenger-root-cause-04.zip (SHA-256 ab8acc203ba040f088c9ef75b70a066fb9779608af69881aec8db1dd96b34127). Source now honors the five-minute cooldown for stale markers and records schema_prerequisite_component. Messenger retention cleanup remains unresolved, so .66.a is not fully accepted. PASS/ZIP/GitHub statements from 2026-11-26 are historical checkpoints, not current phase acceptance.**

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

# Phase Library Card — ZMP Competitions 1.2.4.66.a

- Class: active subphase folder; source implementation not yet copied or changed.
- Status: IN PROGRESS; preliminary design and test plan drafted; Owner-reported hosting self-tests PASS; passkey features NOT RUN.
- Authoritative scope: `../PROJECT_ROADMAP.md`, subsection `.66.a`.
- Current phase records: `PROJECT_STATE_1.2.4.66.a.md`, `PLAN_1.2.4.66.a.md`, `DESIGN_1.2.4.66.a.md`, `WORK_LOG_1.2.4.66.a.md`, `TEST_PLAN_1.2.4.66.a.md`, `TEST_REPORT_1.2.4.66.a.md`, `PATCH_NOTES_1.2.4.66.a.md`, `WHATS_NEW_1.2.4.66.a.md`, `DEBUGGING_1.2.4.66.a.md`.

This card is navigation only and is not technical acceptance.
