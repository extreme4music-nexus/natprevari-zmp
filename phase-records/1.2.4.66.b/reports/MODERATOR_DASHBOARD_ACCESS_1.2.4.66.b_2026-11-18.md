## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Moderator dashboard in the main portal — 2026-11-18

## Owner report and evidence

The Owner reported that a moderator could not see the dashboard and clarified that it should be in the main site panel. The role-aware navigation already had a moderator portal tab, but the main portal defaulted every account to **Почетна**. The moderator dashboard was therefore not the first panel shown after opening the main site portal.

Read-only review of the live WordPress user editor showed the affected account assigned the **Модератор на натпревар** role. The source checks for that role slug as well as `zmp_moderator_cap` and `view_zmp_moderator_dashboard`. The issue was not an unassigned moderator role.

## Change

- Renamed the main portal tab from **Модератори** to **Модераторска табла** for clarity.
- For a non-administrator moderator opening the main portal without a selected tab, the portal now opens `zmp_portal_tab=moderatori` by default. Administrators retain the general **Почетна** default.
- The moderator workspace's default **Преглед** panel still renders `ZMP_Moderator_Operations::render_panel()`. That panel includes the season/category/phase/region selection and regional event controls.
- No separate WordPress admin menu was added. An earlier temporary source change for such a menu was reverted before packaging.
- Existing moderator role/capability, request nonce, and event-scope checks remain in place.

## Separate school-profile diagnostic

The production debug row for user ID 131 reported `forbidden` while viewing `/wp-admin/profile.php`. The school profile is owner-only teacher data; this record does not establish a moderator-dashboard defect or a database-schema failure. The UI's generic message is misleading in that context, but it is outside this dashboard-navigation change.

## Verification status

- Reviewed the main portal's default-tab selection and the moderator portal's default `overview` panel wiring.
- Confirmed the affected account has the moderator role in the live user editor (read-only inspection; no account changes).
- No live settings were changed. Installation and visual confirmation on the main portal as the moderator remain pending; this source build is not yet verified on the live page.

## Owner correction — 2026-11-18: requested menu structure and implementation

The previous section above suggesting that the main portal tab be renamed or become the moderator default is superseded. The main menu item remains **Модератори** and its original default behavior is restored. The requested capability is a child tab, **Начин на одржување**, inside that moderator workspace.

The new child panel provides selectors for academic year, active category, available competition phase, and the region when the regional phase is selected. It saves the delivery mode against the selected scope using the existing `zmp_stage_events` / `zmp_stage_regional_events` records. Existing event details are preserved when changing only the mode. The save path remains behind the moderator capability, request nonce, and active-scope checks and adds an audit event.

Mode rules: regional music entries require a specific region and physical/online choice (no combined mode); state phase stays physical; other selectable stages offer physical, online, or combined. The central `PROJECT_ROADMAP.md` is unchanged. This submenu is the Owner-directed UI detail within the roadmap's moderator organization/role-portal scope.

Verification status: source and ZIP wiring are prepared; no production setting has been changed. Live appearance and save/readback need the Owner to install the updated package and open **Модератори → Начин на одржување** as a moderator.

Package artifact: ZMP_PROJECT_LIBRARY/zmp-competitions-1.2.4.66.b-MODERATOR-DELIVERY-MODE-SUBMENU.zip; archive root zmp-competitions-66b/ verified; SHA-256 69503067BAFC40F4AB4C534B527B9175814AEC9681AB7CCC7C3D7F5CDEF00750. The earlier MAIN-PORTAL-MODERATOR-DASHBOARD.zip is superseded by this package.

## Follow-up verification — local synthetic runtime — 2026-11-18

The prior statement that save/readback awaited installation is superseded for the isolated local `.66.b` site. After backing up and synchronizing the local test plugin to the exact current candidate hashes, the moderator delivery panel rendered and the valid-nonce regional save/readback passed with a synthetic Subscriber+Moderator account; audit and cleanup passed. The detailed evidence is in `MODERATOR_DELIVERY_MODE_RUNTIME_TEST_1.2.4.66.b_2026-11-18.md`. This did not install on the production site and does not claim live visual/accessibility acceptance.
