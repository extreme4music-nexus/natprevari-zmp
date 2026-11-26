# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER RULE — READ FIRST

**THE OWNER-CONFIRMED 2026-11-24 PASS RESULTS REMAIN FINAL FOR THEIR EXACT SCOPES. THIS REPORT ADDS ONLY NEW CHECKS FOR INHERITED `.65` DEFECTS AND A SEPARATE PRESIDENT ROLE-POLICY DEFECT. IT DOES NOT REOPEN OR DOWNGRADE THE 2026-11-24 PASSES.**

**ROLE STACK: EVERY ZMP MEMBER MUST HAVE `subscriber`; `zmp_teacher` IS OPTIONAL AND ADDITIVE; THE PRESIDENT MUST HAVE BOTH `subscriber` AND `zmp_teacher`.**

# `.65` → `.66.a` inherited-fix reconciliation — 2026-11-25

## Scope and disposition

Compared the current `.65` fixes against `.66.a` source rather than treating the `.65` PASS as proof for `.66.a`.

| `.65` correction | `.66.a` finding before this reconciliation | Current disposition |
|---|---|---|
| Normalize integer display widths for MySQL schema comparisons | Shared normalizer and its four consumer checks were absent; valid widthless `BIGINT UNSIGNED` metadata could fail closed | Added shared normalizer and applied it to mentor-school, school-profile, school-email and President/registration structural checks |
| Bypass retry cooldown for a positively read stale schema marker | `.66.a` ordinary init used the retry cooldown even for a known older marker | Added stale-marker detection and a locked re-read; only a confirmed old marker bypasses cooldown |
| Allow `administrator + subscriber + zmp_teacher` for the administrator’s own teacher profile | `administrator` was not on the Teacher role-stack allow-list | Added `administrator` as an additive role; both `subscriber` and `zmp_teacher`, plus own-profile checks, remain required |
| Administrator Teacher-profile feedback | `.65` provides actionable guidance when the correct Subscriber + Teacher stack has no linked teacher row; `.66` wording was abbreviated | Transferred the complete guidance into the WordPress profile notice; synthetic missing-link render **1/1 PASS** |
| President first-time self-setup owner guard | Requiring Teacher on every President also made an administrator-edit of another unlinked President look eligible for first-time self-setup | Added owner-only guard and safe `president_setup_not_owner` diagnostic; current stacked-role transaction regression **10/10 PASS** |
| Messenger legacy scoped-index repair | Repair code already existed; the owner-confirmed `.66.a` Messenger migration PASS is from 2026-11-24 | Preserved as final for its exact scope; not reopened |

The comparison found a separate policy mismatch in `.66.a`: President first-setup guards treated `zmp_president + subscriber` without `zmp_teacher` as a valid self-setup stack. The current owner rule requires President to also be Teacher. `.66.a` and `.66.b` now require the Teacher role for that self-service path and accept the permitted stacked roles.

## Local runtime evidence

Current support source SHA-256 `49fa8ffd590efd1bb26d51cbe7ad000d33cff807f8260395580243293079e1e3`; Debug source SHA-256 `c3c0771ff72a35a16bb5af0f6ace079128c6c19f756929cfffa142920ada5a28`; UI source SHA-256 `b845de01e03ecb68eb634ccb1ed460220340cecf4722bdd64fdf9e35c68d9475`.

- Stacked-President first-time setup: **10/10 PASS** on the current phase source. This includes denied admin-edit of another unlinked President, owner form render, invalid-input rollback, owner save/readback, email collision no-autolink, and exact cleanup/integrity. The denial event and `president_setup_not_owner` code appeared in the local Debugging event stream.

UI source hash after the inherited guidance transfer: `b845de01e03ecb68eb634ccb1ed460220340cecf4722bdd64fdf9e35c68d9475`.

- Missing-profile guidance render: **1/1 PASS** on this exact phase source, with synthetic Administrator + Subscriber + Teacher and no mentor row; expected actionable notice rendered; synthetic user removed.

Only the existing local loopback clone at `http://127.0.0.1:8100` was used. No new server was started, production was not accessed, and the main `PROJECT_ROADMAP.md` was not edited.

- `.66.a` regression fixture: **12/12 PASS**. Covers integer-width normalization; mentor-school, profile and school-email schema readiness; Administrator + Subscriber + Teacher with additive Moderator/Commission/Testator/Organizer roles; actual synthetic profile save/readback; strict Subscriber and Teacher requirements; stacked President positive/negative service checks; exact cleanup.
- Schema upgrade cooldown fixture: **2/2 PASS**. A known stale marker with a retry deadline 240 seconds ahead migrates immediately; a current marker retains ordinary cooldown behavior.
- Partial profile-schema injection: **4/4 PASS**. Removed only the known synthetic `school_id` index, confirmed readiness and read operation fail closed with `schema_not_ready`, restored the exact index and prior options.
- `wp db check`: **PASS** after cleanup and restoration; local server returned HTTP 200.
- PHP syntax: **14/14 PASS** for the affected `66.a` PHP sources and fixtures.

Key `.66.a` source hashes:

- `zmp-competitions.php`: `2f748383e511f06d6910d958027132c8942d444512e0fcc2b5ebbbffefb42c26`
- `includes/class-zmp-database.php`: `69c288f5fa0452b0561a387a333191f06846d9f376ec650e36f8910a21028c21`
- `includes/class-zmp-mentor-school-profile.php`: `c50f9c97d094f38730af68cc9dbd09b24299593a2d0e0c09045b2b2cd7f6a95c`
- `includes/class-zmp-support.php`: `49fa8ffd590efd1bb26d51cbe7ad000d33cff807f8260395580243293079e1e3`
- `includes/class-zmp-debug.php`: `c3c0771ff72a35a16bb5af0f6ace079128c6c19f756929cfffa142920ada5a28`
- `includes/class-zmp-school-profile-ui.php`: `b845de01e03ecb68eb634ccb1ed460220340cecf4722bdd64fdf9e35c68d9475`
- `includes/class-zmp-mentor-subjects.php`: `4a7af9b44bbb3d4704edfa1b01e8f60578c4f80de5e69f1ada9c684264ecdb2e`

Fixtures: `tests/phase66a-president-teacher-stacked-profile-self-setup.php`, `tests/phase66a-inherited65-regression.php`, `tests/phase66a-inherited65-stale-schema-retry.php`, and `tests/phase66a-partial-school-profile-schema-fail-closed.php`.

## `.66.a` remaining product checks

- President subject-decision **browser passkey step-up is still not closed**. The 2026-11-12 local browser attempt showed a generic verification error and did not submit a decision; the exact failing stage was not captured. Registration and repeat login remain owner-confirmed PASS and were not repeated.
- President/Teacher WordPress Profile **browser form submit/readback remains unverified**. Local service-level profile save/readback now passes; it is not represented as browser-form evidence.
- Other broader passkey, user-interface and phase-wide behavior is outside the scoped `.65` transfer tests and must keep its own exact evidence. The concurrent replay current-candidate PASS and the 2026-11-24 role-stack/Messenger PASS records remain authoritative for their precise scopes.

This reconciliation is not whole-phase completion and does not claim production verification. The production Messenger warning and the browser step-up result require later owner-side site testing; no production request or account was used here.
