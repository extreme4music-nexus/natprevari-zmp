# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# `.65` Lead Recovery Follow-up — Responsive UI and Subject Route

Date: 2026-11-04

## Responsive public portal

- Opened a fresh Chrome tab on the disposable `.65` site at `http://127.0.0.1:8094/?page_id=28` and used Chrome's built-in DevTools Device Toolbar.
- Set Responsive viewport to **390 × 844**. The page rendered with its header and portal navigation wrapping within the narrow viewport; the President section and card content remained inside the visible page bounds. No horizontal clipping was visible in the rendered view.
- An independent UI Reviewer repeated the check in a fresh Chrome tab at the same viewport, scrolled once, and confirmed the hamburger/header, social-icon wrapping, portal/subtab wrapping and card text within the visible page bounds, with no visible horizontal clipping in the inspected sections. The reviewer did not measure DOM `scrollWidth`.
- **Independent visual PASS, limited to the public portal's responsive rendering at this viewport.** This does not verify the authenticated President profile's responsive layout or keyboard controls, and does not replace a full UI review.
- President consent-control keyboard review remains **BLOCKED**: the available Chrome session was a synthetic Teacher profile and WordPress reported the session expired. The UI Reviewer did not reach or alter any President profile controls.
- Closed the fresh task-created Chrome tab after review. No site data or browser profile settings changed.

## Subject proposal/register route

- Historical preflight results remain preserved: one attempt ended at `SITE_PATH_MISSING`; a separate non-elevated call with explicit Linux cwd `/home/tonic/zmp-local-sandbox` returned `Wsl/E_ACCESSDENIED` before shell execution.
- With the user's already-granted WSL authorization, an elevated read-only invocation succeeded. The site path is `/home/tonic/zmp-local-sandbox/phase65-test/site`; it reported DB `zmp65_isolated`, prefix `z65_`, active plugin `zmp-competitions`, and `wp db check` PASS. PHP PID `145525` serves `127.0.0.1:8094`; MariaDB PID `144183` uses the isolated phase65 database. Four installed subject-route class files matched the `.65` source hashes exactly.
- The exact-reviewed candidate (`6752164B8AC9A7C49C90E4FF64F353DFFBFE594398845F879A80EDC8221A3B64`, PHP lint and Code Review PASS) was invoked once after that preflight. It stopped before fixture creation or HTTP because the synthetic subject catalog has no approved active canonical subject. Post-run `wp db check` PASS; the staged test file and empty test directory were removed; no proposal/catalog/alias/event/session/option/request mutation, mail or network operation occurred. Route assertions remain **NOT RUN**.
- The empty catalog is a real site prerequisite/state, not a WSL blocker. A revised test candidate is being prepared to exercise approval from the expected empty-catalog baseline; the old hash must not be rerun. No product defect is established.

## Phase decision

`.65` remains **IN PROGRESS / NOT READY FOR AUDITOR**. This follow-up closes only the public-portal responsive viewport check. Subject-register runtime, President profile keyboard/responsive behavior, the integrated President lifecycle, positive audio approval runtime, owner AJAX-action attribution, unready-schema email submission, legal/Board evidence and release gates remain open or NOT RUN. ZIP, GitHub sync, Actions and Auditor are still **NOT RUN**.
