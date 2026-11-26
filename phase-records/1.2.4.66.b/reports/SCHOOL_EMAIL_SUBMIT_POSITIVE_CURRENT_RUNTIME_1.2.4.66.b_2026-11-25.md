# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# School-email request submission positive flow — 2026-11-25

## Environment
Tested against the existing isolated WordPress runtime at http://localhost:8103, database zmp66b_isolated, prefix z66a_. Current service source SHA-256: d8855bc141d5e1f854b751a10420eb90d04af9b6cbcd2f7a3e0da7664be796ce.

## Result — PASS
The actual authenticated admin-post submit callback accepted a valid nonce and a synthetic teacher/subscriber request from one active engagement to another teacher at the same school.
- Exactly one pending request was created for the owner's matching school engagement.
- Exactly one submitted audit event was written by the requester. Its payload omitted the email address.
- Requester readback returned the created request.
- Captured redirect contained no email address.
- Fixture removed both synthetic users, mentor rows, school engagements, profile and request/event rows; before/after request and event totals matched.
- wp db check passed for all tables. No email was sent; active President mandate unchanged.
- Fixture: tests/school-email-submit-positive-current-runtime.php — SHA-256 6f177a9ddde178c95c2dcb8d1a93f96985eb17e6c21163b46a0561b0791a365a.

## Harness note
The first fixture run created the expected request and audit event but failed its readback assertion because the public row key is request_id, not id. The shutdown cleanup ran exactly. After correcting the fixture field, the final run passed all six assertions. This was a fixture defect, not a product-code change.
