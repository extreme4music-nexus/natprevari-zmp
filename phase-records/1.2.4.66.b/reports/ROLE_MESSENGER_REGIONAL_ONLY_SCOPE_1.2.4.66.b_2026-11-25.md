## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Messenger regional-only commission scope — 1.2.4.66.b

Date: 2026-11-25
Environment: retained local clone `http://localhost:8103`, isolated database `zmp66b_isolated` / prefix `z66a_`; active season 1 and completed regional-jury migration were verified before fixture writes. Production was not accessed.

A synthetic Subscriber + Commission account was given one regional jury assignment for category 2 / Skopje and no national `zmp_jury_assignments` row. The active candidate/runtime `can_access_room()` path was evaluated.

PASS 5/5:

- Assignment was regional-only (no national assignment).
- Commission Messenger access to the assigned Skopje category was allowed.
- Pelagonia, an unscoped national room, and another category were denied.
- Exact cleanup restored user and regional-assignment row counts.

PHP lint, full WordPress database integrity check and local HTTP 200 passed. Fixture SHA-256: `51d5d42daa5951a3fde317c0666278d442224c4eb34d906a55216a2e69b1c265`.

This closes only regional-only room authorization. It does not cover regional room rendering, message lifecycle, every endpoint, or production Messenger behavior.
