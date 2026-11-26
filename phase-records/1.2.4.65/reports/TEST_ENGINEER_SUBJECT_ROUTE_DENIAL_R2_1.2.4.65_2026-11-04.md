# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Test Engineer — Subject route denial R2 — 2026-11-04

## Result

**FAIL — 9/10 assertions.** The reviewed fixture observed `DIRECT_QUEUE_HTTP_STATUS 302` for a synthetic Teacher's direct request to the President subject queue. The fixture requires HTTP 403, so `teacher_direct_queue_url_returns_403` failed. The authenticated-denial criterion is **NOT VERIFIED**: the runtime did not capture the `Location` header, so it does not establish whether the request was redirected to login before the route authorization check. This result does not by itself confirm a product defect. No retry was made.

## Exact candidate and preflight

- Fixture: `tests/phase65-subject-president-route-denial-clone.php`
- Fixture SHA-256: `83A96C6127BD41B781D565AFFF2B1FE9893CBBF6A1DA7870337BBC2956F75DAF`
- Exact-hash review: PASS, `reports/CODE_REVIEW_SUBJECT_ROUTE_DENIAL_FIXTURE_R2_1.2.4.65_2026-11-04.md`, SHA-256 `6AB84CC27A56572B0937275FB3BA00A7A1D1F5B2653482F394307F196DBECCE1`
- Disposable site: `/home/tonic/zmp-local-sandbox/phase65-subject-catalog/site`; URL `http://127.0.0.1:8100`; database `zmp65_subject_catalog_test`; prefix `z65_`.
- Preflight: exact root and URL verified; database check PASS; only active plugin was ZMP Competitions `1.2.4.65`; exactly one synthetic administrator was present; PHP PID `218531` served loopback port `8100`; MariaDB PID `144183` was the existing phase database service.
- Loaded source hashes matched the fixture guards: mentor proposal class `8DD89AA7AAFE5A7AB1FC6F83A2B65112865842E9354578BB5A1BE18061C89F69`; President approvals class `720E0692D8534DD261C68457101E574701198B49C233CF98072B2B8CFD8A16C4`.
- The exact reviewed fixture was staged to the disposable site's uploads directory and its staged SHA-256 matched before execution.

## Runtime assertions

- **FAIL:** direct queue URL returned `302`, expected `403` (`DIRECT_QUEUE_HTTP_STATUS 302`); `Location` was not captured.
- **PASS:** queue response did not disclose the synthetic subject or decision controls.
- **PASS:** nonce-valid Teacher decision POST returned `403` with generic denial and no redirect.
- **PASS:** proposal remained pending with its submission event; no decision was recorded.
- **PASS:** catalog and alias tables remained empty.
- **PASS:** request started, diagnostic options were restored exactly, and table baselines were restored exactly.

## Cleanup and post-run state

- Fixture-reported cleanup and exact baseline restoration: **PASS**.
- Post-run `wp db check`: **PASS**. Proposal, proposal-event, catalog and alias tables each contained zero rows. Exactly one administrator remained; ZMP `.65` was the only active plugin.
- The one temporary fixture copy created for this run was removed and verified absent. Existing PHP PID `218531`/port `8100` and MariaDB PID `144183` were left running and unchanged for phase work.
- No credentials, production site, external URL, email, or non-synthetic records were used.

## Interpretation and next action

Preserve the earlier candidate's `FAIL 9/10` unchanged. This R2 result establishes the observed 302 but does not record its redirect target or prove whether authentication/route handling caused it; therefore the authenticated denial criterion remains NOT VERIFIED. Do not rerun this candidate. Have the Debugger inspect the actual route and the fixture's local authenticated request semantics against the preserved evidence; any new runtime candidate requires a concrete reason, independent exact-hash review, and a new bounded run authorization.
