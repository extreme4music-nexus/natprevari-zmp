# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Concurrent President route replay diagnostic V2 — exact-hash runtime result

Date: 2026-11-12 (Europe/Skopje)

## Result

- Current fixture: `tests/phase66a-president-step-up-concurrent-route-replay-diagnostic-v2.php`
- Fixture SHA-256: `E534058B473722EB26E199075900A3A64FDCC96F93341A0BA3DE291391A6D924`
- Exact-hash static process-supervision review: PASS, recorded before runtime.
- Isolated clone: `/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/site`; synthetic DB `zmp66a_isolated`, prefix `z66a_`; private MariaDB PID 3828. Port 8102 was free; no test web server was started.
- Runtime command: WP-CLI `eval-file` for the exact fixture, passing the fixture path both as the script and its required invocation-path argument.
- Runtime result: **PASS**, exit 0. Two workers reached the exact consume boundary; status matrix PASS (one rejected decision and one replay failure); worker exits evaluated and PASS; proposal event, challenge consumption, credential counter, baseline cleanup, temporary artifact removal, and database integrity all PASS.
- The fixture reported no proof, cookie, credential, or private-key values. Cleanup restored the pre-run synthetic baseline, removed its private transient artifacts, and `wp db check` passed.

## Bounded invocation correction

- The first WP-CLI invocation omitted the fixture-path argument expected by the fixture guard. It returned `BLOCKED: exact clone/source/config preflight failed; writes=0`, exit 2. No application/database writes occurred.
- The corrected invocation supplied that argument and produced the PASS above. It was the sole runtime behavior attempt; the earlier preflight refusal did not enter the behavior test.

## Backup handling

- Metadata-only inspection found two pre-existing clone SQL artifacts, each 251,395 bytes with mode 600 and owner `tonic:tonic`; neither could be identified as a partial file from the earlier failed export, so both were preserved.
- One bounded `wp db export` retry succeeded, exit 0. New private clone backup `clone-retry-20261112-01.sql`: 251,395 bytes, mode 600, owner `tonic:tonic`. The backup contents and digest were not read or included in project records.
- No partial artifact was removed because none was identified from metadata.

## Disposition

- Earlier `worker_exit` FAIL and external-supervision preflight BLOCKED reports are preserved as historical evidence for earlier fixture/command states. They do not override the PASS of this exact current fixture hash. No product source changed.
- This scoped concurrency diagnostic does not satisfy browser step-up submission, remaining profile UI save/readback, whole-phase review, Auditor, ZIP, or verified GitHub synchronization. `.66.a` remains **IN PROGRESS / NOT_READY**.
