## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# YouTube registration fields — local browser regression

**Date:** 2026-11-18  
**Phase:** `.66.b`  
**Result:** PASS after correcting a slot-label defect  
**Environment:** isolated local WordPress clone; local Chromium/Playwright; no upload, form submit, or outbound YouTube request.

## Scope and setup

The current `.66.b` PHP shortcode render was already checked separately with `youtube-registration-fields-render-check.php`: a synthetic Subscriber+Teacher account and synthetic school received the registration form, mode configuration for the school/category, the hidden YouTube fieldset, and the versioned script. The helper cleaned its fixtures and restored baseline counts. This browser check exercised the exact `assets/js/zmp-entry-youtube-fields.js` from `.66.b` against a local HTML fixture that reproduces the selector and data-config contract. Mode fixtures cover vocal, choir, orchestra and band in online and physical modes, plus an unconfigured school. It deliberately does not submit the form or select/upload any file.

The local runtime and source JavaScript SHA-256 matched: `81054f7dcea06bf71b2ad52930c1468d9c5bfcd153e45fd68f63820b9f23b41e`.

## Defect found and correction

The first browser run showed that the UI appended `*` to every slot whenever the regional event was online. That incorrectly made choir/orchestra optional compositions and both band compositions appear required, despite the category rules. Updated the label rule: in online mode only vocal performance and choir/orchestra required-composition slots receive `*`; optional slots remain labeled optional. Physical mode keeps every slot optional. Unconfigured mode leaves the section visible and disables its inputs.

## Final browser assertions

All ten assertions passed with zero JavaScript exceptions:

- Vocal: one performance slot; required online, optional physical.
- Choir: required and optional composition; only required composition marked required online; both optional physically.
- Orchestra: same slot and requirement behavior as choir.
- Band: two optional compositions in both online and physical modes.
- Unconfigured region: section remains visible and its input is disabled.

The fieldset became visible after initialization. Online and physical guidance text matched their mode. The YouTube connection flag was false in the fixture, so the UI correctly stated that upload is unavailable; no network request occurred.

## Evidence and limits

- Browser assertion output was produced by `/home/tonic/zmp-local-sandbox/phase66b-test/youtube-js-check.mjs` against `http://127.0.0.1:8103/zmp-youtube-js-check.html`.
- Source asset: `assets/js/zmp-entry-youtube-fields.js`; `node --check` passed.
- PHP render/enqueue/cleanup evidence: `reports/youtube-registration-fields-render-check.php` (separate local runtime report already described in current phase notes).
- This closes local UI rendering and slot-definition behavior only. It does not verify a live YouTube OAuth connection, upload, processing/privacy, full form submission, deferred uploads from “Мои пријави”, or the unresolved `session_unknown` upload record. Do not retry that record until the owner confirms the channel state.
