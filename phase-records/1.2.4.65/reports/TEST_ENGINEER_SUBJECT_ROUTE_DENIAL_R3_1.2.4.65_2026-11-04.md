# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Test Engineer — Subject route denial R3 runtime — 2026-11-04

## Result

**FAIL — 9/11 assertions. Authenticated President-queue denial remains NOT VERIFIED.** The positive profile control returned HTTP `302`, so it did not prove the synthetic Teacher cookie was recognized. The queue request also returned HTTP `302`, with the allowlisted `Location` class `login`, rather than the required `403`. No retry was made; this does not establish a product authorization defect.

## Exact candidate and independent review

- Fixture: `tests/phase65-subject-president-route-denial-r3-clone.php`
- Exact fixture SHA-256: `5FD2DBB4C28CF0EF6A91A0F334F69CA82008C43C841AFA8FB1CDAB7C5796A332`
- Independent exact-hash review: **PASS**, `reports/CODE_REVIEW_SUBJECT_ROUTE_DENIAL_R3_1.2.4.65_2026-11-04.md`, SHA-256 `71210BB98F93E17363CF8C95BEE40133CA9B17C2F96311DD66A5CA2513F296C5`.
- The exact reviewed fixture was staged to the disposable site's uploads directory; its staged SHA-256 matched before execution.

## Fresh preflight

- Exact site root: `/home/tonic/zmp-local-sandbox/phase65-subject-catalog/site`; URL `http://127.0.0.1:8100`.
- Database `zmp65_subject_catalog_test`; prefix `z65_`; `wp db check` **PASS**.
- Only installed/active plugin: ZMP Competitions `1.2.4.65`.
- Exactly one synthetic administrator and one total synthetic user were present.
- Loaded source hashes matched the fixture guards: proposal service `8DD89AA7AAFE5A7AB1FC6F83A2B65112865842E9354578BB5A1BE18061C89F69`; President approval service `720E0692D8534DD261C68457101E574701198B49C233CF98072B2B8CFD8A16C4`.
- Existing PHP PID `218531` listened only on `127.0.0.1:8100`; existing MariaDB PID `144183` was the phase database service. Both were reused and left running.

## Runtime assertions

- **FAIL:** `teacher_cookie_authenticates_to_own_profile`; `AUTH_CONTROL_HTTP_STATUS 302`. The profile response did not provide the expected authenticated Teacher page and synthetic display-name marker.
- **FAIL:** `teacher_direct_queue_url_returns_403`; `DIRECT_QUEUE_HTTP_STATUS 302`.
- Queue redirect classification: `DIRECT_QUEUE_LOCATION_CLASS login`. Only this allowlisted path class was recorded; no redirect URL, host or query values were emitted. The positive-control redirect target was not captured.
- **PASS:** direct queue response did not disclose the synthetic subject or decision controls.
- **PASS:** nonce-valid decision POST returned 403 with generic denial and no redirect.
- **PASS:** proposal remained pending with only its submission event; catalog and alias tables remained empty.
- **PASS:** request started; diagnostic options were restored exactly; captured database baselines were restored exactly; fixture cleanup assertion passed.

Because the positive control failed, this run does not prove that the queue request reached route authorization as an authenticated Teacher. Preserve the strict expected 403. Do not reinterpret the 302 as successful denial or a confirmed product defect.

## Cleanup and post-run state

- Fixture-reported exact cleanup and baseline restoration: **PASS**.
- Post-run `wp db check`: **PASS**. Proposal, proposal-event, catalog and alias tables each had zero rows. Exactly one administrator remained; the only plugin remained ZMP `.65`.
- The single temporary R3 fixture copy staged for this run was removed and verified absent. Existing phase PHP PID `218531`/port `8100` and MariaDB PID `144183` remained unchanged.
- No production site, real account, external URL, or email was used.

## Preserved history and next action

- R1 remains **FAIL 9/10**; its evidence is unchanged.
- R2 remains **FAIL 9/10**, observed queue status 302; its evidence is unchanged.
- R3 remains **FAIL 9/11**, with profile control 302 and queue redirect class `login`; authenticated route denial remains **NOT VERIFIED**.

No further runtime attempt is recommended without a concrete change that can establish authenticated local HTTP semantics. A debugger may inspect the local cookie/session setup and server behavior read-only. Any new candidate requires independent exact-hash review and a fresh preflight before its single authorized run.
