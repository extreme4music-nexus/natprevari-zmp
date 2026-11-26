## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Code Review — Subject Route Denial Fixture R2 — 2026-11-04

## Review result: PASS — exact candidate only

- Candidate: `tests/phase65-subject-president-route-denial-clone.php`
- Exact SHA-256 reviewed: `83A96C6127BD41B781D565AFFF2B1FE9893CBBF6A1DA7870337BBC2956F75DAF`
- PHP lint: **PASS** (`No syntax errors detected`).
- Runtime for this exact hash: **NOT RUN at review time**.
- Product source hashes required by the fixture: `ZMP_Mentor_Subject_Proposals` `8DD89AA7AAFE5A7AB1FC6F83A2B65112865842E9354578BB5A1BE18061C89F69`; `ZMP_President_Subject_Approvals` `720E0692D8534DD261C68457101E574701198B49C233CF98072B2B8CFD8A16C4`.

## Findings

No actionable findings for this test-only candidate.

The updated candidate retains the exact disposable site root, database, prefix, loopback endpoint, plugin version, and loaded source-hash guards (lines 7–29). It verifies a single synthetic administrator and empty subject tables before creating a unique `@example.invalid` Teacher. It then exercises the actual queue GET and nonce-valid decision POST using a short-lived synthetic session. The transport filter restricts both requests to the approved loopback host, port, and admin routes (lines 120–134).

The newly added `DIRECT_QUEUE_HTTP_STATUS` output records only the numeric response status before preserving the strict expected-403 assertion (lines 137–142). It does not emit the response body, cookies, nonce, proposal content, or user identity. This provides the missing evidence needed to diagnose the earlier 9/10 result on hash `742C17BA22BD9ED103292A832CCFE1D3A6D102D3A42ADE32B6E9BCDBFE8C6425`; it does not reinterpret that historical FAIL or weaken the acceptance condition.

Cleanup locates only the exact generated proposal and Teacher, validates pending status, actor and event payload before deletion, handles the narrowly matched orphan-event case, destroys the generated session, restores diagnostic options, and compares captured table snapshots (lines 169–203 and 270–284). If ownership or expected state cannot be established, cleanup fails visibly instead of sweeping unrelated rows. No product code is changed.

## Scope and limitations

- This fixture covers direct President-queue URL denial and the decision `admin-post` denial for a Teacher; it does not cover President approval, wrong-role submission, rendered UI, or unrelated AJAX/REST routes.
- The in-process `pre_http_request` guard constrains this fixture's WordPress HTTP calls. It does not establish independent egress restrictions for the PHP server process.
- This review does not establish runtime behavior for hash `83A96C...`. One bounded run is appropriate only after current site/process/database preflight and exact staged-hash verification; preserve its printed status and all cleanup results.

The prior exact-hash review result for hash `742C17...` remains intact. The prior Test Engineer result remains **FAIL 9/10** because that version did not print the observed queue response status. Debugger source analysis found the expected 403 consistent with the registered-page authorization path, but did not establish the runtime status. This PASS applies only to hash `83A96C...` and does not close the functional acceptance gate.
