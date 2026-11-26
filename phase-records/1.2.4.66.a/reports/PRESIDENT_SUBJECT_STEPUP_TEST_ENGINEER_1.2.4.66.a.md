# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Test Engineer — President Subject Step-Up TDD RED

## Scope

One focused `.66.a` Task 5 test of the approved active-President subject-proposal decision route. It uses only two uniquely named synthetic users, the existing mandate/proposal services, and one localhost admin-post `reject` carrying a valid action nonce but no WebAuthn proof. The expected red criterion is that missing step-up proof must leave the synthetic proposal pending and append no rejected event. This does not approve the route or close the phase.

The fixture preserves the protected passkey credential, active service processes, unrelated debug events/options and global role options. It cleans only its synthetic users, session, mandate/audit rows, proposal/event rows and debug events associated with its unique IDs.

## Preflight and candidate

Pre-run authorized site: `/home/tonic/zmp-local-sandbox/phase66a-test/site`; DB `zmp66a_isolated`, prefix `z66a_`; only active plugin `zmp-competitions`; passkey schema v3. Baseline users/credentials/challenges/proposals/proposal events/mandates/mandate audit: `1/1/0/0/0/0/0`. No President-role users, active mandates today, overlapping active/scheduled terms, or pending proposals. Protected credential digest: `511b7006cfa53e2bced9ef69b96331783af1472295afe6638d347235575afe6e`. PHP PID `286942` listens on `127.0.0.1:8101`; MariaDB PID `286676`.

Exact source/site parity before run:

| File | SHA-256 |
|---|---|
| `includes/class-zmp-president-subject-approvals.php` | `720E0692D8534DD261C68457101E574701198B49C233CF98072B2B8CFD8A16C4` |
| `includes/class-zmp-mentor-subject-proposals.php` | `8DD89AA7AAFE5A7AB1FC6F83A2B65112865842E9354578BB5A1BE18061C89F69` |
| `includes/class-zmp-president-mandate.php` | `5B1A0B83F2BA1809125C2BBFF4B57C201E3E6BD72792C62BFFE73B8DE870CA40` |
| `includes/class-zmp-passkeys.php` | `587CEAA26CB3A023717B0B366249449D0385FC038204FD798D50562473B73DC7` |

Fixture: `tests/phase66a-president-subject-step-up.php`, SHA-256 `FA92415A0A4D3218A8DF4287A68BFA0223D714C93B7413DD0F607BFE0E75C346`; PHP lint **PASS** (`No syntax errors detected`).

## Results

## Single bounded runtime result

Exact command: `wp --path=/home/tonic/zmp-local-sandbox/phase66a-test/site eval-file /mnt/z/Documents/Natprevari_ZMP/ZMP_PROJECT_LIBRARY/03_PHASES/zmp-competitions-1.2.4.66.a/tests/phase66a-president-subject-step-up.php` — **FAIL (1/6), exit 1**, expected TDD RED. The sole failed acceptance assertion was: `MISSING STEP-UP PROOF must leave proposal pending and append no rejected event`.

The fixture created two uniquely named synthetic accounts, appointed the synthetic President through `ZMP_President_Mandate::appoint`, and submitted a unique pending proposal through `ZMP_Mentor_Subject_Proposals::submit`. It sent exactly one loopback `admin-post.php?action=zmp_president_subject_decision` request with a valid action nonce and `decision=reject`; no step-up challenge, assertion, or proof fields were sent. The route returned its normal reject redirect (HTTP 302 with `zmp_subject_decision_status=rejected`); the proposal row became `rejected` for the synthetic President and exactly one `rejected` proposal event was written. This reproduces the missing Task5 guard in the current candidate. The status and event were captured before exact cleanup.

Cleanup: **PASS**. Fixture deleted only the proposal events and proposal keyed to its own proposal ID/key; mandate audit/row keyed to its own mandate ID; destroyed its own President session token; deleted only its two synthetic users; cleaned their user caches; restored request/current-user/cookie globals; removed only its own debug events while preserving unrelated entries; and verified debug events and global role-option value returned to their exact pre-run hashes. No credential, challenge or protected user was changed.

Final baseline: users/credentials/challenges/proposals/proposal events/mandates/mandate audit `1/1/0/0/0/0/0`; President-role users `0`; pending proposals `0`; protected credential digest unchanged at `511b7006cfa53e2bced9ef69b96331783af1472295afe6638d347235575afe6e`. `wp db check --silent` **PASS** (`Success: Database checked.`). Source/site parity remained exact: approvals `720E0692D8534DD261C68457101E574701198B49C233CF98072B2B8CFD8A16C4`; proposal service `8DD89AA7AAFE5A7AB1FC6F83A2B65112865842E9354578BB5A1BE18061C89F69`; mandate service `5B1A0B83F2BA1809125C2BBFF4B57C201E3E6BD72792C62BFFE73B8DE870CA40`; passkeys `587CEAA26CB3A023717B0B366249449D0385FC038204FD798D50562473B73DC7`. PHP PID `286942` still listens on `127.0.0.1:8101`; MariaDB PID `286676` remains active. No source sync, service lifecycle change or further test was performed.

This is a concrete Task5 behavior **FAIL/RED** for the current candidate, not a pass or phase acceptance. Product code remains unchanged; no second endpoint invocation was made.

