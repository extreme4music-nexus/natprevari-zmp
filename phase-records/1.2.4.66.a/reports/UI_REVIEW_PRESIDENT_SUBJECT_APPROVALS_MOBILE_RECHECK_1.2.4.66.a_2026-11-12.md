# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Mobile UI cache mismatch diagnosis — President subject approvals

- Date: 2026-11-12
- Route: `http://localhost:8101/wp-admin/admin.php?page=zmp-president-subject-approvals`
- Browser context: existing Edge tab `913915354`; no new tab opened.
- Viewport: 375 × 812 CSS px.
- Candidate PHP file SHA-256: `45435CB47D290D736622B71E8C0C5E92416BBF4418E2A0020B23D61EF0B16D88`.
- Candidate admin CSS file SHA-256: `A3BD3F9DF6AA91C5973A79AED5254BBCA0AC6AC4E4D982B81F1DD482A490BC83`.
- Loaded stylesheet URL at the time: `http://localhost:8101/wp-content/plugins/zmp-competitions/assets/css/zmp-admin.css?ver=1.2.4.66.a`.

## Measured result

- The mobile breakpoint matched at 375 px. Document/body widths were 360 px, so no horizontal overflow was observed.
- Controls stacked vertically and measured 312 px wide; buttons were 40 px high.
- The label/select block measured 260 px high with computed `flex-basis: 260px` and `flex-grow: 1`; the action wrapper measured 422 px high and the first button started at y=770.
- Those computed values contradict the candidate CSS file's mobile rule `flex: 0 1 auto`. The stylesheet link still carried the fixed phase version `1.2.4.66.a`; the stylesheet response bytes were not inspected. This supports a stale-CSS/application mismatch, but does not prove what bytes Edge received or prove caching was the cause.

## Status and limits

This pre-cache-key-fix render is **FAIL for the requested compact label/select spacing / candidate application unresolved**. The no-overflow and stacked-control findings are scoped PASS. No decision, passkey, profile data, or persistence action was touched. Keyboard-only traversal, screen-reader behavior, selector interaction, decision/authenticator flow, persistence, other devices, and physical mobile remain NOT RUN.

A following source change gives the admin CSS a filemtime cache key; see `UI_REVIEW_PRESIDENT_SUBJECT_APPROVALS_MOBILE_CACHEFIX_1.2.4.66.a_2026-11-12.md` for the fresh-URL recheck. The original rendered mismatch remains preserved as historical evidence.
