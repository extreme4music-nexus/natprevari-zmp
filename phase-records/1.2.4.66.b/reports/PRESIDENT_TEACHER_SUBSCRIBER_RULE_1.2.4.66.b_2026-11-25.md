## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# President Teacher + Subscriber eligibility — 1.2.4.66.b

Date: 2026-11-25
Environment: retained local WordPress clone `http://localhost:8103`, isolated database `zmp66b_isolated` / prefix `z66a_`. No production account was changed.

## Finding and repair

The President candidate list and server-side appointment guards required Subscriber, but treated `zmp_teacher` as optional. That did not match the Owner's rule that a President is always both a Teacher and Subscriber.

Updated the `.66.b` candidate filter, `ZMP_President_Mandate::eligible_appointee()`, and the central President-role grant helper to require both `subscriber` and `zmp_teacher`. The appointment error now tells administrators to select a Subscriber + Teacher account. Existing extra allowed roles remain intact; this change does not silently add Teacher to an existing account.

Candidate/runtime SHA-256 matches:

- `admin/class-zmp-president-mandate-admin.php`: `a65c05e15f172c4f35817de3f7919ea9e156a85f220e2b0d3c8f56f48c57c336`
- `includes/class-zmp-president-mandate.php`: `39d830d7d4d56de297627de2151befa1d83313a5e2beaf76271cd5f18d7ab547`
- `zmp-competitions.php`: `33130da368ef62cc00ac9d7d715e399ea257428967537b9f7b4cd134046a64c8`

PHP lint passed for all three modified files.

## Runtime evidence

`tests/president-mandate-teacher-eligibility-current-runtime.php`: PASS 6/6. A stacked Subscriber + Teacher member remained selectable; a Subscriber-only account was excluded; stale President-role and Administrator accounts were excluded. Direct appointment of the Subscriber-only account returned `president_account_ineligible` before writing a mandate or audit event. Exact test-user cleanup passed. Fixture SHA-256: `97ff9ef1e3ebe1884129b5701be016c376b6b9efcf152226aada26fe3de22c2`.

The existing positive stacked-member admin-post appointment fixture also passed after the change: future appointment scheduled, Subscriber + Teacher + Moderator + Commission + Testator + Organizer retained, one appointed event, no President role before the start date, exact cleanup. Candidate-filter regression passed 4/4. `wp db check` passed; local home returned HTTP 200.

## Local clone note

The pre-existing local test account ID 116 has President + Commission roles but lacks Subscriber + Teacher. It was not changed. It is not used as evidence of a compliant President. The new tests use disposable correctly stacked synthetic accounts.

## Scope limits

This validates the current candidate filter and server-side appointment boundary. It does not mutate or repair any existing President account, prove production role state, or close other mandate lifecycle/UI or whole-phase gates.
