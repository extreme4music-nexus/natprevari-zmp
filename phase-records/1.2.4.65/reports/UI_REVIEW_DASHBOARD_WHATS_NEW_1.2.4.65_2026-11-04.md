## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Independent UI Review — Dashboard What's New (`.65`)

## Result

**PASS** for rendered desktop presentation, version selection, copy visibility, and keyboard operation of the What's New selector. This review does not establish mobile/responsive acceptance.

## Scope and binding

- Local site: `http://127.0.0.1:8100/wp-admin/admin.php?page=zmp-competitions`
- Synthetic WordPress administrator on the disposable `.65` subject-catalog clone; no production site or data.
- Rendered source: `admin/class-zmp-admin.php`, SHA-256 `06C99026398640208A18F5E59249EC1B25BDB7B4B348E6B60E94C3A5D7C1402E`, matching the workspace candidate.
- Chrome desktop viewport: approximately 1383 × 689.

## Findings

- Dashboard title and the `Што е ново?` card rendered. The native `Избери верзија` selector exposed `.65`, `.64`, and `.63` alongside earlier versions.
- `.65` showed four in-progress/current items: President lifecycle/register/email integration, optional active-mandate profile photo, unready-schema and URL-check diagnostics, and privacy-safe `schema_not_ready` AJAX-action logging. The text was visibly labeled as ongoing/under review; it did not claim release completion.
- `.64` rendered the three version-specific entries for per-school employment/email recording, active-President decision-based email access, and user-selected directory employment fields.
- `.63` rendered the three version-specific entries for teacher subject proposals and President decisions, searchable/paged/filtered school registry export, and limited diagnostic data.
- At the inspected viewport the selector and all four `.65` bullets fit in the card, with no visible clipping or horizontal overflow. Dashboard summary cards aligned below it.
- Keyboard interaction passed: the selector received focus with a visible outline; ArrowDown/Return changed `.65` to `.64`, another selection changed to `.63`, and ArrowUp/Return restored `.65`. Focus remained on the selector and its accessible label/value were exposed in the accessibility tree.
- No mutation controls were used and no application data was changed. The reviewer-created Chrome task tab was closed after inspection.

## Limitation

Mobile/responsive rendering was not part of this desktop-only review. No mobile viewport or extension emulation was run.
