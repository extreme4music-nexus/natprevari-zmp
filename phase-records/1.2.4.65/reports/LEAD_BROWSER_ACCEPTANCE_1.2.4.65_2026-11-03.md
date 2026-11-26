# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Lead Browser Acceptance — ZMP Competitions 1.2.4.65

Date: 2026-11-03

## Dashboard What's New

- Environment: isolated synthetic WordPress site `/home/tonic/zmp-local-sandbox/phase65-test/site`; URL `http://127.0.0.1:8094/wp-admin/admin.php?page=zmp-competitions`; Chrome task tab; no production connection.
- Authenticated rendered UI (accessibility tree): selected `.65` and confirmed the in-progress entries appeared; selected `.64` and confirmed its school employment/email entries appeared; selected `.63` and confirmed its subject proposal/register/diagnostics entries appeared.
- Result: **PASS** for the three version selections and their rendered content.
- Account handling: synthetic Teacher user ID 30 was temporarily granted the administrator role solely for this dashboard inspection, then restored to exactly `zmp_teacher`. A post-restore `wp db check` passed.
- Scope: browser/AX verification of text and selector behavior. No form submission or content mutation. Does not cover mobile responsive behavior or role denial.

## Existing profile checkbox UI finding

- The initial Lead session found the retired/replacement checkboxes visually stretched. Frontend corrected the checkbox flex layout in `assets/css/zmp-public.css` (SHA-256 `BD12B88D3C84CF6E184F9AF02286B48D39273D33857FF925141DA28384FDC392`).
- Independent UI Reviewer rechecked the rendered controls at approximately 1384×689 and PASSed compact, aligned checkboxes. Independent Code Reviewer PASSed the exact CSS hash and confirmed ordinary input widths and hidden/grid behavior remain intact.
- Mobile View could not switch from its existing admin-post tab, so responsive acceptance remains **BLOCKED**. No profile was saved during the layout review.

## Synthetic President self-consent profile check

- A disposable synthetic President (user 109), active mandate (row 59) and fake local image attachment (post 34) were created. Chrome authenticated to the local President profile. The public-photo consent checkbox rendered unchecked by default; it was toggled on in the form.
- The form submission did not complete: after the attempted click there was no consent metadata in the database, and closing the tab prompted that changes might not be saved. Therefore the actual self-consent save route is **NOT RUN**, not PASS. No public data was changed.
- Exact fixtures were removed (user 109, mandate 59, attachment 34); a read-only query confirmed no remaining mandate row, and `wp db check` passed. The synthetic credential remained in session memory and was not included in any report.
