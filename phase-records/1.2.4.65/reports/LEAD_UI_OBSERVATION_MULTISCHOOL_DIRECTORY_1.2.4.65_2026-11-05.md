## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Lead UI Observation — Multi-School Employment Directory — 1.2.4.65 — 2026-11-05

## Scope and status
Lead-observed functional UI evidence on the existing synthetic WSL site only. This is not an independent UI Reviewer or Test Engineer PASS and does not close the ledger gate `multischool_populated_directory_responsive_keyboard_ui`; keyboard acceptance is incomplete. Existing backend/service scoped PASS evidence remains unchanged.

## Environment
- Existing site: `http://127.0.0.1:8094`, WSL path `/home/tonic/zmp-local-sandbox/phase65-test/site`.
- Existing synthetic database: `zmp65_isolated`, prefix `z65_`; schema probes reported both school-profile and school-engagement schemas ready.
- Existing synthetic Teacher account: user ID 130; credentials are in the root local-only credential file and are intentionally omitted here.
- No new site, sandbox, or server was created. PHP/MariaDB services were left running.

## Actions and observations
1. Teacher signed into WordPress and saved two synthetic schools through the Teacher profile UI: primary `Синтетичко училиште 65 А` (Центар) and additional `Синтетичко училиште 65 Б` (Карпош). The profile UI displayed both active schools and the server-derived `Скопски` region.
2. Schema was available. The original profile warning was caused by the Teacher account lacking its mentor/profile record; the normal Teacher profile save created it. This is resolved in this synthetic site.
3. WordPress page #37 was a theme placeholder. It was updated on this synthetic site to contain `[zmp_employment_directory]` so the existing UI could be exercised.
4. Teacher saved different employment and workload values for the two schools, a visible non-retired status for each, and an active replacement status for school B. The first attempt correctly rejected replacement without the required colleague and dates; after adding synthetic colleague/date values, the UI confirmed the school profiles were saved.
5. Authenticated Teacher directory output grouped the teacher under both schools and displayed only opted-in employment, workload, retirement, and replacement status. Synthetic official email values and replacement colleague/date values were not rendered.
6. Unauthenticated Edge view of the same page showed the login-required message and did not show school/employment/email markers.
7. At an explicit 390x844 viewport, the directory had a 375px document/body width and no horizontal overflow; the rendered mobile layout showed the directory heading, privacy explanation, and school cards.
8. Keyboard check focused the site header menu item on the first Tab. A subsequent Tab attempt timed out; full focus traversal/keyboard acceptance was not completed.

## Disposition
- Populated, authenticated content and privacy behavior: observed by Lead; evidence supports continued independent UI review.
- Anonymous page view: UI observation agrees with existing anonymous-response PASS; it does not establish database no-mutation.
- Mobile no-overflow observation: recorded; no independent responsive review claimed.
- Keyboard: incomplete. Keep UI acceptance gate open (currently BLOCKED pending independent UI/keyboard evidence).
- Anonymous exact DB no-mutation remains BLOCKED as recorded; no preimage was collected and no claim is added.
- President photo/consent PASSes and all unrelated accepted evidence remain untouched.

## Local synthetic data note
The synthetic Teacher now has two synthetic schools and synthetic school-profile values in the existing `phase65-test` site. Page #37 is now a shortcode fixture. Keep this environment and its account local-only; exclude credentials/database material from reports, GitHub, and ZIPs. No product source files changed in this UI observation.
