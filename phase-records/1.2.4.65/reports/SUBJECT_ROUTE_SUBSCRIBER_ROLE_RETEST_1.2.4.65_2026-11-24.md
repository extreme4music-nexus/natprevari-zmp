## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Teacher proposal route with full member-role stack — .65 — 2026-11-24

## Result
**PASS 8/8** on the exact .65 phase source. The synthetic Teacher account carried all six roles: `zmp_teacher`, `subscriber`, `zmp_moderator`, `zmp_commission`, `zmp_testator`, and `zmp_organizer`. The production `admin-post.php` route accepted the Teacher submission and returned HTTP 302 with `zmp_subject_status=submitted`.

## Fixture and local boundaries
Fixture: `tests/phase65-subject-register-admin-post.php`; SHA-256 `22C6EA8A296131CBADFC883EE6E8630796FD2EE5C75B8892D5DE5D0E38F18357`. It ran only at `http://127.0.0.1:8097` against blank/schema-only synthetic DB `zmp65_subject_test` with prefix `z65s_`. The fixture enforces loopback, expected database/prefix, a sole synthetic administrator baseline, empty feature tables and disabled WP cron.

## Assertions and cleanup
All eight passed: the school-profile engagement service returned an array (including the valid empty result), schema preflight, submitted HTTP response, allowlisted redirect status, one pending proposal, event actor/timestamp, exact baseline cleanup/diagnostic restoration, and no email or outbound-network marker. The database and account cleanup are verified separately after the run.

## Disposition
This confirms the positive proposal route and school-profile service for a Teacher who also holds Moderator, Commission, Testator and Organizer roles, while retaining Subscriber. It does not grant Teacher functions to a Testator-only or Organizer-only account. No production site, real account, email, YouTube or external service was used. The broader role and President transition suite is in `ROLE_STACK_AND_PRESIDENT_ROLE_PRESERVATION_1_2_4_65_2026-11-24.md`. The overall .65 phase remains open.