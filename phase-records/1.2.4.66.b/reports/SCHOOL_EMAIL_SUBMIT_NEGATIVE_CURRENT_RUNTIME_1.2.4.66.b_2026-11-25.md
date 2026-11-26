## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# School-email request submission negative checks — 2026-11-25

## Environment
The existing isolated WordPress runtime at http://localhost:8103, database zmp66b_isolated, prefix z66a_. The tested school-email service source SHA-256 was d8855bc141d5e1f854b751a10420eb90d04af9b6cbcd2f7a3e0da7664be796ce. No source change was needed.

## Result — PASS 6/6
Using synthetic local teacher/subscriber identities and school engagements, the current service and authenticated admin-post callback rejected:
- reason shorter than the required minimum (invalid_reason);
- requester asking for their own address (forbidden);
- caller-supplied requester identity different from the logged-in user (forbidden);
- subscriber without teacher capability attempting to submit (forbidden);
- invalid nonce at the actual submit callback, before any request or event row was written.

Each service-level denial left request and event totals unchanged. The invalid-nonce route also left both totals unchanged. Exact cleanup removed the synthetic users, mentors, school engagements and profile; wp db check passed for every table. No email was sent and the active President mandate was not changed.

Fixture: tests/school-email-submit-negative-current-runtime.php — SHA-256 6d09da5ad727113b12c08db77cc1bef9bb54aab5b0bc7555a923276d1132cde8.

## Remaining scope
This closes the listed negative submission cases only. Owner eligibility, unavailable/stale contact, unrelated-school relationship, malformed ID, long reason, duplicate/idempotent request, UI submission and broader route/phase cases remain separate checks.


## Request-boundary denials — supplemental PASS 6/6
Five additional current-runtime cases returned the expected fail-closed error and left request/event row totals unchanged: missing official contact profile, unrelated owner school, non-teacher contact, requester supplied another user's school ID, and a reason longer than 500 characters. Exact synthetic cleanup and wp db check passed. No email was sent and no source change was needed.
Fixture: tests/school-email-submit-boundary-current-runtime.php — SHA-256 17822cd7c8626af45d453405f476fda3445c3efa19f0df74d2f4c7e17812aeec.
