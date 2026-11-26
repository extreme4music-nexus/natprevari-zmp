# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Test Engineer — unready-schema school email admin-post candidate

**Status: CURRENT CANDIDATE CLEAN-DATABASE RUNTIME PASS / EXACT-HASH REVIEW PASS; PRIOR INVALID-CLONE FAIL PRESERVED**

## Scope

Candidate: `tests/phase65-school-email-unready-submit-admin-post.php`  
SHA-256 (revised candidate): `D7BC02157DA5B22AA465C7716EDB471737D518688D6E745FC234418AB15AB7A0`  
PHP syntax: **PASS** — `php -l`, no syntax errors.  
Revised-candidate runtime: **NOT RUN**. The prior candidate runtime below remains **FAIL** and is not replaced by this revision.

The revised candidate uses synthetic Teacher fixtures 31 and 30, requires their shared active synthetic school, removes only the verified `mentor_active (mentor_id,is_active)` index, sets a 4-minute retry cooldown, and POSTs the actual authenticated `admin-post.php` route. It asserts HTTP 302 plus exact redirect target `http://127.0.0.1:8095/` and only the query `zmp_school_email_status=request_failed`, no response markers for email/request data, no new matching request or requester event, and no invocation of `wp_mail` as observed by a temporary clone-local MU-plugin guard. The clone-local shutdown marker independently checks the same scheme, host, port, path, and exact query before recording that the request reached the expected redirect; it stores no URL or request data.

The initial exact-hash review returned **FAIL**: caught route/assertion failures could leave exit code 0, and retry-option restoration was asserted without checking database state. A second review found that MU guard/marker/directory removal results were unchecked, permitting a false PASS with residue. The previous candidate deferred PASS output until after cleanup, exits nonzero for route/assertion or cleanup failure, verifies each retry option's original existence/value/autoload, and verifies guard/marker absence and removal of a test-created MU directory. Its exact hash `2A31CCAC6A5E3A9DAF1FA585580E21E5B98C555A9CF35F218B4205C372AB6C7E` received independent Code Review **PASS** before the single runtime attempt. That run **FAILed only** at the generic fail-closed redirect assertion after receiving HTTP 403; the clone PHP access log confirms `POST /wp-admin/admin-post.php` returned 403. The earlier diagnostic incorrectly searched the raw `Location` text. Additional likely cause, not confirmed by rerunning: the candidate created the nonce before setting the matching logged-in cookie in the WP-CLI process, so its nonce session token may not have matched the submitted cookie. The revised candidate now temporarily sets that exact generated `LOGGED_IN_COOKIE` before `wp_create_nonce`, then restores its previous in-memory cookie state. A subsequent exact-hash review found the redirect assertion did not verify the destination; the revised candidate now checks `http`, `127.0.0.1`, port `8095`, path `/`, and the sole query pair `zmp_school_email_status=request_failed` both in the client assertion and the clone-local shutdown marker. On mismatch it emits only numeric HTTP status and allowlisted `Location` query keys/values; raw Location, host, body, and unrecognized query values are never emitted. The revised exact hash has not been reviewed or run.

## Clone-only target and lifecycle

- Disposable clone used for the one prior run: `/home/tonic/zmp-local-sandbox/phase65-unready-email-test/site`
- Clone database: `zmp65_unready_email_test`, prefix `z65u_`
- Clone URL: `http://127.0.0.1:8095/`
- The active `.65` site `/home/tonic/zmp-local-sandbox/phase65-test/site`, database `zmp65_isolated`, and port 8094 are rejected by candidate guards and must remain untouched.
- Runtime cleanup emitted no `CLEANUP` failure. Post-run `wp db check` passed; the `mentor_active (mentor_id,is_active)` index was restored with expected columns; no matching pending/approved request for synthetic Teacher 31 → 30 remained.
- PHP server PID `166948` was stopped, and port `8095` was verified released. No server remains active from this run. Do not rerun the revised candidate until new exact-hash Code Review PASS and Lead direction; recheck project-managed processes/ports before any future clone server start.

## Fixture, rollback and disposal plan

The future run must first verify clone path, DB identity/prefix, synthetic-only contents, installed plugin inventory, route-source hashes, and the exact index definition. It must stop before mutation if any precondition differs. The candidate does not create users. It snapshots exact existence/value/autoload for only the three retry options it changes. It does not read or edit debug options, trace sidecars, or pre-existing session-token metadata; product-generated diagnostics may accumulate only inside the disposable clone.

The controlled missing index is recreated in `finally`; retry options are restored to their prior existence/value/autoload; the generated auth session is destroyed; the exact-hash temporary MU guard and its marker are removed only when they match this run's known content. Any request created after the recorded ID baseline is deleted only when requester/owner/school identity matches the synthetic fixture; associated events are deleted only by that request ID. If exact restoration cannot be proven, the run cannot PASS and the disposable clone must be discarded after preserving evidence. Do not delete or reset the active `.65` site/database.

The clone itself should be destroyed only after the run evidence and cleanup state are captured; then verify its directory/database removal and that owned server PID/port/socket are gone. If cloning or isolation cannot be proven, report **BLOCKED** and do not point the candidate at the active site.

## Review and risk

The exact-hash Code Review PASS applies to current candidate `D7BC02157DA5B22AA465C7716EDB471737D518688D6E745FC234418AB15AB7A0` and test-script safety only, not route behavior. The candidate is constrained to an isolated clean-clone DB/prefix/port, but still uses destructive DDL inside that disposable database, modifies retry options, creates a short-lived session, writes a temporary MU guard, and exercises normal product diagnostics. The prior functional attempt remains **FAIL** and is unsuitable for acceptance because its clone copied existing synthetic staff data. That clone/DB has been removed; the current clean-DB runtime is delegated and has not yet returned. No PASS is claimed.


## Clean blank-database runtime result — 2026-11-04

The exact-reviewed candidate SHA D7BC02157DA5B22AA465C7716EDB471737D518688D6E745FC234418AB15AB7A0 ran once against a newly created blank WordPress database with only the ZMP plugin and new synthetic admin/Teacher 30/31 plus shared school fixtures. Pre-run and post-run wp db check PASS; relevant plugin source hashes matched; mentor_active(mentor_id,is_active) was restored. The real local POST /wp-admin/admin-post.php route returned the exact generic fail-closed redirect with zmp_school_email_status=request_failed; no request or event was created, no response disclosure occurred, and wp_mail was not invoked. Candidate output: PASS: generic fail-closed redirect; no request/event, response disclosure, or mail marker; exact cleanup verified. The isolated clone/database were removed and owned PHP server PID 169108 on port 8095 was stopped; PID absence and port release were verified. The prior candidate failure on the copied synthetic-staff database remains historical and is excluded from acceptance. No product code changed.
