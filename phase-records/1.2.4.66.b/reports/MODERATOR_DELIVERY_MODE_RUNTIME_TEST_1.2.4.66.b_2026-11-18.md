## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Moderator delivery-mode submenu — local runtime evidence

Date: 2026-11-18 (Europe/Skopje)
Scope: Synthetic local verification of the Owner-directed **Модератори → Начин на одржување** flow. No live site, production database, external service, or real user was accessed.

## Candidate and environment

- Active phase source: `.66.b`.
- Isolated WordPress: `/home/tonic/zmp-local-sandbox/phase66b-test/site` (`http://127.0.0.1:8103`).
- Synthetic database: `zmp66b_isolated`, table prefix `z66a_`.
- Before syncing the candidate, the test plugin folder was backed up to `/home/tonic/zmp-local-sandbox/phase66b-test/backups/plugin-before-sync-20261118-current`. The candidate code directories and entrypoint were copied from `.66.b` into the isolated plugin installation. No database or production files were changed by this sync.
- Exact source/runtime SHA-256 matches: `class-zmp-moderator-operations.php` `2696213036f3b9741a6cf8a6b37680706ab0b7374e0700c0fd591cf1c5a53bee`; `class-zmp-shortcodes.php` `688602f70fb2a4b9f9e88d14da1f347b9f7f1cebb04f2991184ceb5ac18121dd`.
- Test helper: `reports/moderator-delivery-panel-runtime-check.php`, SHA-256 `76b37841965be7474efdb6e866591c98dfc42f832384e2243628e8ff705ad486`; PHP syntax check PASS.

## Results

A disposable synthetic user was created with both `subscriber` and `zmp_moderator` roles. With season 1, category 2 (`vocal_interpretation`), regional phase, and region `polog`, local WordPress rendering and handler checks passed:

- Main portal retains the **Модератори** tab label.
- The **Начин на одржување** child tab and category/phase/region selectors render in the moderator workspace.
- For regional music, the form offers `physical` and `online`, and omits `hybrid`.
- Removing the moderator role and refreshing WordPress's user/capability cache leaves a Subscriber-only account without the moderator tab or child panel.
- A valid moderator nonce and exact active scope saved `online` to the regional event record, attributed the actor, and created the expected `set_delivery_mode` audit event.
- Cleanup removed the synthetic event, audit row, and test user. Post-test counts for the chosen `polog` event and synthetic users were both zero. `wp db check` passed for all database tables.

The first negative-view assertion reused a cached role object; resetting the test user's capability cache corrected the harness. A later save attempt likewise exposed a stale test-session role immediately after re-adding the role; the harness now resets the current user before submitting. The final run passed. WP-CLI emitted its expected redirect warning because the tested handler ends by redirecting; the registered shutdown assertions confirmed the saved state and exact cleanup. These were test-harness issues; no product defect was established.

## Limits

This is a local WordPress rendering and callback/runtime check, not a browser screenshot or visual/accessibility acceptance. It verifies the regional-music online path for one synthetic scope. Hybrid mode for nonregional stages, the fixed physical state phase, other categories/regions, browser interaction, and wider moderator regression checks remain open. No real regional event, live setting, or account was changed.
