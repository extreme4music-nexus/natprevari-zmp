# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Audio UI acceptance evidence — ZMP Competitions 1.2.4.65

Date: 2026-11-04  
Point status: **MASTER PASS — Audio UI only**  
Phase status: **IN PROGRESS / NOT READY FOR AUDITOR**

## Scope

This record consolidates the `.65` Audio UI evidence for the per-tool mobile tabs, eligible manual-review controls, `uncheckable / HTTP 0` warning, responsive layout and native viewport-resize focus behavior. It does not replace the detailed independent review or Test Engineer route reports, and does not grant whole-phase acceptance.

## Candidate identity

The final Master follow-up confirmed that the source and disposable-site copies still match the previously reviewed candidate. Current source SHA-256 values:

| File | SHA-256 |
|---|---|
| `includes/class-zmp-approved-audio-tools.php` | `10641166B008302FD12A05CD8AD0372DB30ECB5B8B7B31AF626475C17FA8F1E7` |
| `assets/css/zmp-admin.css` | `773271DD16BBD021C4308F8E6339E4F17C9149BEB711DE48BFFF099C260341AB` |
| `assets/js/zmp-admin.js` | `E7D30554D7032C489F184785E2BBC66E741C78EF992EE4D0ACA8F9581C528404` |

No product source or test fixture changed during the final UI follow-up.

## Acceptance evidence

| Criterion | Result | Evidence and scope |
|---|---|---|
| Mobile catalog has one tab group per tool, with `Алатка`, `Функција`, `Автоматска проверка`, `Рачна одлука и белешка`, `Проверени услови`, `Дејства` | **PASS** | Independent UI report additive follow-up exercised the six panels and Left/Right keyboard selection at 420×912; device-preset follow-up verified the tabs/action-panel at iPhone 5 (320×568) and iPhone 17 Pro Max (440×956). It reports an internal tab-strip scroll and no visible page-level horizontal overflow. See `UI_REVIEW_AUDIO_MANUAL_FORM_INDEPENDENT_1.2.4.65_2026-11-04.md`, additive sections dated 2026-11-04. |
| Eligible unapproved `uncheckable / HTTP 0` row explains the inconclusive server check and exposes the manual checklist, note and decision controls in `Дејства` | **PASS for the observed pending HTTP 0 row** | Final live Chrome observation rendered `Не може да се провери · HTTP 0`, the warning to personally open and confirm the link, the checklist, note, approve, reject and archive controls. No action was submitted and no external link was opened. See `MASTER_AUDIO_UI_FOLLOWUP_1.2.4.65_2026-11-04.md`. |
| Native browser resize across the 782px CSS breakpoint retains the corresponding action focus | **PASS** | Final live check resized 742px → 1440px → 742px. Focus moved from the selected mobile `Дејства` section to that record's desktop `Уреди` control and remained on the same control after returning to mobile. Same Master follow-up report. |
| Manual-review route requires the reviewed state and records a reviewer-bound decision without external network access | **PASS — 21/21** | Exact-reviewed candidate and cleanup details: `TEST_ENGINEER_AUDIO_MANUAL_APPROVAL_POSITIVE_1.2.4.65_2026-11-04.md`. External HTTP attempts: zero. The synthetic `url_opened` input is test data and is not evidence of human URL opening. |
| Anonymous and incomplete-confirmation submissions are denied | **PASS — 6/6** | `TEST_ENGINEER_AUDIO_ADMIN_POST_NEGATIVE_1.2.4.65_2026-11-03.md`; no candidate approval/state change. |
| 401/403 classification and denied-state behavior | **PASS — 37/37 regression; 11/11 synthetic 403 route** | Detailed candidates and preserved earlier outcomes are in `TEST_REPORT_1.2.4.65.md` and `UI_REVIEW_1.2.4.65.md`. The 403 explanation identifies a server-side probe denial, not a confirmed broken browser link. |

Older independent UI observations that found the HTTP 0 fixture absent or the action form unavailable are preserved in the dated sections of the independent UI report. They predate the final live HTTP 0 fixture observation and are superseded only for the exact warning/action and resize-focus gaps closed by the final Master follow-up. Historical findings for HTTP 200/rejected fixtures remain valid for those fixture states.

## User-visible behavior and safe diagnosis

The admin catalog has six desktop columns. At the mobile breakpoint, each tool is rendered as its own tabbed card; tab selection shows the matching information/action panel, and arrow keys move among tabs. The action panel's manual-review form appears only for a fresh eligible audit state when the row is not already approved: `available`, `changed_review`, `probe_denied`, or `uncheckable` with HTTP 0. HTTP 404, other unavailable results and redirects are not eligible. HTTP 0 is inconclusive, not proof that the destination is broken or safe.

Manual review is an administrator action (`manage_options`) protected by an item-bound nonce. It requires the eight content/terms checks; `probe_denied` and HTTP 0 additionally require the browser-open confirmation; a note and logged-in reviewer ID are also required. Review time, checklist, reviewer ID and URL hash are stored. Approval retains `probe_denied` and `uncheckable` as inconclusive states rather than reporting an automated success. Changing the URL invalidates the stored review match.

For a safe local diagnosis, inspect only a synthetic record's generic audit state, HTTP status class, freshness, checklist completeness, note presence and URL-hash match. Do not open real/owner URLs or copy destinations, query strings, response bodies, reviewer notes, personal information, nonces, cookies, tokens or private/internal addresses into logs or reports. No real destination reachability or human opening is claimed by these tests.

## Disposition

The scoped Audio UI point is **Master PASS**. The local UI checks did not submit a form or alter the inspected record. Actual remote URL reachability/human opening remains **NOT RUN** under the local-only boundary. Other `.65` acceptance items, whole-phase Master readiness, package/hash parity, GitHub synchronization/readback, Actions review and Auditor remain open; this report does not change their status.

## Source references

- `MASTER_AUDIO_UI_FOLLOWUP_1.2.4.65_2026-11-04.md` — final scoped Master PASS, exact pending HTTP 0 rendering and real browser resize focus evidence.
- `CODE_REVIEW_AUDIO_UI_SOURCES_1.2.4.65_2026-11-04.md` — independent Code Review PASS for the exact PHP/CSS/JS product hashes listed above.
- `UI_REVIEW_AUDIO_MANUAL_FORM_INDEPENDENT_1.2.4.65_2026-11-04.md` — independent UI observations and dated corrections/follow-ups, including mobile tabs and device presets.
- `TEST_ENGINEER_AUDIO_MANUAL_APPROVAL_POSITIVE_1.2.4.65_2026-11-04.md` — exact-reviewed positive route runtime and cleanup.
- `TEST_ENGINEER_AUDIO_ADMIN_POST_NEGATIVE_1.2.4.65_2026-11-03.md` — anonymous and incomplete-confirmation denial runtime.
- `TEST_REPORT_1.2.4.65.md` — broader probe classification and regression history.

