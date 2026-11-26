## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Messenger role and scope matrix — 1.2.4.66.b

Date: 2026-11-25
Environment: retained local WordPress clone `http://localhost:8103`, database `zmp66b_isolated`, prefix `z66a_`. No production site or YouTube account was accessed.

## Scope and role model

Executed the focused runtime fixture `tests/role-messenger-scope-matrix-current-runtime.php` against the active plugin. Every synthetic role account was created with both required baseline roles, `subscriber` and `zmp_teacher`. The stacked synthetic member additionally had Testator, Moderator and Commission roles; assignments granted tester-room access and one exact regional Commission room, but no Organizer or national Commission assignment. A separate synthetic President had Subscriber, Teacher, President and unassigned Commission roles. The fixture tested `ZMP_Role_Messenger::can_access_room()` and removed exact created accounts/assignment rows in its shutdown cleanup handler.

## Result

PASS 18/18 assertions:

- All synthetic accounts retained Subscriber + Teacher.
- Moderator may access moderator room and is denied tester room.
- Stacked Testator + Moderator + Commission member accesses only assigned tester, moderator and exact Skopje commission rooms; Organizer, unscoped national Commission and Pelagonia are denied.
- Organizer may access organizer room and is denied Commission room.
- Commission may access the assigned category and exact Skopje region, but is denied another category and Pelagonia.
- Synthetic President (Subscriber + Teacher + President + unassigned Commission) and Administrator do not bypass room assignment checks.
- Ordinary Teacher is denied Moderator and Commission rooms.
- Exact cleanup restored the test-user and assignment-table row counts to their pre-test values.

Captured runtime output:

```json
{"checks":{"subscriber_teacher_present_on_all_synthetic_roles":true,"moderator_room":true,"moderator_not_tester":true,"stacked_tester_assigned_room":true,"stacked_moderator_room":true,"stacked_regional_commission_room":true,"stacked_tester_not_organizer":true,"stacked_regional_scope_not_national":true,"stacked_regional_other_region_denied":true,"organizer_assigned_room":true,"organizer_not_commission":true,"commission_assigned_category":true,"commission_other_category_denied":true,"commission_exact_region":true,"commission_other_region_denied":true,"president_has_no_bypass":true,"administrator_has_no_bypass":true,"ordinary_teacher_denied":true},"exact_cleanup":true,"all_assertions_pass":true}
```

PHP syntax check and full WordPress database integrity check passed. The local home page returned HTTP 200. Fixture SHA-256: `9254f1135a1ed798a6781d79dec81b1109c8daf2240535c77931e9f7d911c3e9`.

A pre-test read found the retained clone's existing user 116 has President + Commission but lacks Subscriber + Teacher. That existing account was not modified or used as a substitute for the required President role shape; this matrix uses a separate correctly stacked synthetic President and deletes it exactly.

## Limits

This is a focused room authorization/scope runtime test. It does not verify browser rendering, message creation/read/attachment flows, every Messenger AJAX route, schema migration/recovery, or live production behavior. Those broader gates remain open.
