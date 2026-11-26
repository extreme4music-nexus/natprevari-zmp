# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Test Engineer — Subject route denial R3 candidate — 2026-11-04

## Candidate status

**Prepared for independent exact-hash review. Runtime NOT RUN.** This report is only for the R3 test candidate; it does not change or replace the R1/R2 runtime failures or their reports.

- Candidate: `tests/phase65-subject-president-route-denial-r3-clone.php`
- Candidate SHA-256: `5FD2DBB4C28CF0EF6A91A0F334F69CA82008C43C841AFA8FB1CDAB7C5796A332`
- PHP lint: **PASS** — `No syntax errors detected` for the exact candidate above.
- Product source hashes guarded by the fixture remain: proposal service `8DD89AA7AAFE5A7AB1FC6F83A2B65112865842E9354578BB5A1BE18061C89F69`; President approval service `720E0692D8534DD261C68457101E574701198B49C233CF98072B2B8CFD8A16C4`.
- Exact site guards remain database `zmp65_subject_catalog_test`, prefix `z65_`, plugin version `1.2.4.65`, root `/home/tonic/zmp-local-sandbox/phase65-subject-catalog/site`, and loopback URL `http://127.0.0.1:8100`.

## R3 changes and assertion scope

- Before the President queue GET, the candidate sends a GET to the Teacher's own `/wp-admin/profile.php` using the same short-lived session cookie. It asserts HTTP 200 and that the returned profile HTML contains the fixture's synthetic display-name marker. The response body and synthetic marker are examined only in memory; neither is printed or saved. The output reports only the numeric control status.
- The `pre_http_request` guard now permits exactly that profile path in addition to the existing local `admin.php` and `admin-post.php` paths, still restricted to `http://127.0.0.1:8100`.
- For the queue GET, the candidate prints only its numeric HTTP status and a `Location` path class from the fixed allowlist `none`, `login`, `profile`, `admin`, or `other`. It parses only the path for classification; it never prints or stores the full URL, query values, cookies, nonce, or response body in output/evidence. The body remains in memory solely for the existing no-disclosure assertions.
- The expected queue denial remains strict: **HTTP 403**. The existing no-disclosure, valid-nonce decision denial, no-mutation, diagnostic-option restoration, exact baseline, and cleanup assertions remain intact.
- Static comparison confirms the cleanup block is unchanged from R2. The R2 fixture remains at SHA-256 `83A96C6127BD41B781D565AFFF2B1FE9893CBBF6A1DA7870337BBC2956F75DAF`.

## Runtime and review status

- Independent exact-hash Code Review: **PENDING**.
- R3 runtime: **NOT RUN**. No site files, database rows, credentials, or server processes were changed for this candidate preparation.
- Prior evidence remains unchanged: R1 is **FAIL 9/10**; R2 is **FAIL 9/10**, observed status 302, with authenticated denial **NOT VERIFIED** because its redirect target was not captured. Do not repeat either prior candidate.

## Safety check

The candidate has the same exact disposable-site, database, prefix, version, loopback, and loaded-source guards as R2. Cleanup remains byte-for-byte equivalent after its changed request block. R3 must receive independent exact-hash review and a fresh preflight before any runtime; this report does not authorize or claim that run.
