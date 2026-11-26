# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# School-email duplicate submission / idempotency — 2026-11-25

## Environment
Existing local isolated WordPress at http://localhost:8103, database zmp66b_isolated, prefix z66a_. Service hash d8855bc141d5e1f854b751a10420eb90d04af9b6cbcd2f7a3e0da7664be796ce.

## Result — PASS 4/4
Using two synthetic teacher/subscriber users with a shared active school and a reserved example.test contact:
- First submit_request call created one pending request with existing=false.
- Second call for the same requester, owner and school returned the same request ID with existing=true.
- The database retained one request and one submitted audit event; the original reason remained unchanged.
- Exact cleanup restored request/event counts; wp db check passed. No email sent, no source change, active President mandate unchanged.

Fixture: tests/school-email-submit-idempotent-current-runtime.php — SHA-256 c6e3fafcc229341c8e1b564f07f77b07005ea8ab9fe50f07d769473585d455b8.

## Remaining scope
This validates the service idempotency path. It does not cover concurrent simultaneous submissions, every request status transition, visual form behavior or broader phase acceptance.
