# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER RULE — READ FIRST

**THE OWNER-CONFIRMED 2026-11-24 PASS RESULTS REMAIN FINAL FOR THEIR EXACT SCOPES. THIS REPORT ADDS ONLY NEW CHECKS FOR INHERITED `.65` DEFECTS AND A SEPARATE PRESIDENT ROLE-POLICY DEFECT. IT DOES NOT REOPEN OR DOWNGRADE THE 2026-11-24 PASSES.**

**ROLE STACK: EVERY ZMP MEMBER MUST HAVE `subscriber`; `zmp_teacher` IS OPTIONAL AND ADDITIVE; THE PRESIDENT MUST HAVE BOTH `subscriber` AND `zmp_teacher`.**

# `.65` → `.66.b` inherited-fix reconciliation — 2026-11-25

## Scope and disposition

Compared the `.65` corrective behavior directly with `.66.b`. `.66.b` already had richer diagnostic integer-type normalizers in its mentor-school, profile, school-email and subject-proposal validators; those were preserved. The forward-migration retry guard and administrator Teacher-role allow-list were still missing and are now corrected.

| `.65` correction | `.66.b` finding before this reconciliation | Current disposition |
|---|---|---|
| Normalize integer display widths in schema checks | Equivalent `.66.b` normalization already existed in its own diagnostic validators | Preserved; exact schema readiness was exercised at runtime |
| Bypass retry cooldown for a positively read stale schema marker | Ordinary init used retry cooldown even for a confirmed stale marker | Added stale-marker detection and locked re-read; current markers retain normal throttling |
| Allow `administrator + subscriber + zmp_teacher` for the owner’s Teacher profile | Administrator was missing from the Teacher role-stack allow-list | Added as an additive role; Subscriber + Teacher and own-profile checks remain required |
| Administrator Teacher-profile feedback | `.65` provides actionable guidance when the correct Subscriber + Teacher stack has no linked teacher row; `.66` wording was abbreviated | Transferred the complete guidance into the WordPress profile notice; synthetic missing-link render **1/1 PASS** |
| President first-time self-setup owner guard | Requiring Teacher on every President also made an administrator-edit of another unlinked President look eligible for first-time self-setup | Added owner-only guard and safe `president_setup_not_owner` diagnostic; current stacked-role transaction regression **10/10 PASS** |
| Messenger legacy scoped-index repair | Existing code and owner-confirmed `.66.b` migration evidence remain present | Preserved; no duplicate or replacement claim |

The `.66.b` President profile and subject-assignment guards also contained a policy mismatch: President setup was recognized only for `zmp_president + subscriber`, despite the current rule requiring `zmp_teacher`. The guards now require Subscriber + Teacher + President and allow the documented additional member roles.

## Local runtime evidence

Current support source SHA-256 `49fa8ffd590efd1bb26d51cbe7ad000d33cff807f8260395580243293079e1e3`; Debug source SHA-256 `f6850be2957ccc285736b7b1b9483c9ce633270ae725f24e09ec21bba95dac6b`; UI source SHA-256 `b845de01e03ecb68eb634ccb1ed460220340cecf4722bdd64fdf9e35c68d9475`.

- Stacked-President first-time setup: **10/10 PASS** on the current phase source. This includes denied admin-edit of another unlinked President, owner form render, invalid-input rollback, owner save/readback, email collision no-autolink, and exact cleanup/integrity. The denial event and `president_setup_not_owner` code appeared in the local Debugging event stream.

UI source hash after the inherited guidance transfer: `b845de01e03ecb68eb634ccb1ed460220340cecf4722bdd64fdf9e35c68d9475`.

- Missing-profile guidance render: **1/1 PASS** on this exact phase source, with synthetic Administrator + Subscriber + Teacher and no mentor row; expected actionable notice rendered; synthetic user removed.

Tests reused the existing local loopback clone at `http://127.0.0.1:8100`. The `.66.b` tree was loaded only into that local clone for the focused run, then the clone was returned to `.66.a`. No new server was started, production was not accessed, and the main `PROJECT_ROADMAP.md` was not edited.

- `.66.b` role/profile regression: **11/11 PASS**. Includes schema readiness, Administrator + Subscriber + Teacher with additional Moderator/Commission/Testator/Organizer roles, synthetic school-profile save/readback, President stack requirements, subject-service authorization and fixture cleanup.
- Schema upgrade cooldown: **2/2 PASS**. A positively stale marker migrates despite a 240-second retry deadline; a current marker retains the retry cooldown.
- Partial profile-schema injection: **4/4 PASS**. Missing synthetic `school_id` index fails closed with `schema_not_ready`; the exact index and prior options are restored.
- Local database integrity: **PASS** after cleanup; loopback HTTP returned 200 with `.66.a` restored.
- PHP syntax: **5/5 PASS** for the changed `.66.b` source files and its regression fixture. The shared partial-schema and retry fixtures also passed with `.66.b` loaded.

Key `.66.b` source hashes:

- `zmp-competitions.php`: `cd882b3da66d91de2826af69db9c6520542ff53db779f1d4f4f2bf624baba556`
- `includes/class-zmp-mentor-school-profile.php`: `b42fe2ca152c10d822a3a8c1b1e77676dda40edea1aa75ec867e0584565c7a0c`
- `includes/class-zmp-support.php`: `49fa8ffd590efd1bb26d51cbe7ad000d33cff807f8260395580243293079e1e3`
- `includes/class-zmp-debug.php`: `f6850be2957ccc285736b7b1b9483c9ce633270ae725f24e09ec21bba95dac6b`
- `includes/class-zmp-school-profile-ui.php`: `b845de01e03ecb68eb634ccb1ed460220340cecf4722bdd64fdf9e35c68d9475`
- `includes/class-zmp-mentor-subjects.php`: `4a7af9b44bbb3d4704edfa1b01e8f60578c4f80de5e69f1ada9c684264ecdb2e`

Fixtures: `tests/phase66b-president-teacher-stacked-profile-self-setup.php`, `tests/phase66b-inherited65-regression.php`; the shared retry and partial-schema fixtures are recorded in the `.66.a` report and were run with `.66.b` loaded.

## Remaining product checks

- `.66.a` President browser step-up remains unverified; that result does not turn `.66.b`'s scoped passes into browser evidence.
- `.66.a` President/Teacher browser profile submit/readback remains unverified; the local service-level profile write/readback is a separate PASS.
- The owner-directed Drive video upload and jury-thumbnail flow is still **DOCUMENTED / NOT IMPLEMENTED OR TESTED**, as already stated in `reports/DRIVE_VIDEO_SUBMISSION_AND_JURY_THUMBNAIL_DESIGN_1.2.4.66.b_2026-11-25.md`.
- The live Messenger warning remains unverified against production; this work proves local code behavior only.

This report does not claim whole-phase completion or production verification.
