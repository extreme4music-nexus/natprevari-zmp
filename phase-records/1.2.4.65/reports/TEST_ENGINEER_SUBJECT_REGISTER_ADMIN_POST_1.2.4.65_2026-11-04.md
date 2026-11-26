# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Test Engineer — `.65` subject proposal/register admin-post route

Date: 2026-11-04

## Result

- **PHP lint: PASS.** Candidate `tests/phase65-subject-register-admin-post.php` has no syntax errors.
- **Exact-hash Code Review: PASS.** SHA-256 `6752164B8AC9A7C49C90E4FF64F353DFFBFE594398845F879A80EDC8221A3B64`.
- **Runtime: BLOCKED before site or database inspection.** One elevated WSL preflight returned `wsl: Failed to translate '\\192.168.50.23\NAS\Documents\Natprevari_ZMP'`, followed by `SITE_PATH_MISSING` and exit 1. The path guard stopped the command before process/port inspection, canonical-record query, or `wp db check`. Per the preflight guard, no retry was made.
- **Route assertions: NOT RUN.** No temporary test copy was staged; no HTTP POST, database query/mutation, synthetic fixture, session, option, mail, or outbound network operation occurred. No product or disposable-site state changed.

The script is only a reviewed test candidate; lint and Code Review do not establish route behavior. The eligible active-canonical-row prerequisite, pre/post database integrity, actual route responses, events, cleanup, and network/mail guards remain **NOT RUN**.

## Intended bounded coverage

With a working approved disposable `.65` runtime, this script exercises the Teacher proposal-submit route, anonymous/bad-nonce/non-President decision denials, duplicate-safe `approve_new` rejection, and active-President `approve_alias` success against one existing approved active canonical record. It uses one unique synthetic phrase and a temporary alias; it does not modify product source. The script includes exact proposal/event/catalog/alias snapshots, session cleanup, per-request debug-event cleanup, loopback-only HTTP and outbound-mail/network guards. None of those runtime assertions have been exercised yet.

## Preserved exact-hash review failures

These candidate review failures were fixed before any execution and remain part of the evidence history:

1. `EB7A2741DFF2ACD2A3B4392C233B320BEC650E12039984135FB65CA060468F02` — alias cleanup did not require exact inserted row ID; ambiguous HTTP needed bounded late-commit readback; catalog needed an immediate post-`approve_new` assertion.
2. `388B1C145A9AD4DD499E5417235461B2E797E56DFEB83A00795DAD6BD9B94932` — preserve exact submitted-event identity/payload and clean only this run's diagnostic events.
3. `CFF9F79A37A6C7D6E9DF155F2E470A072A817963699ABC6BEEA4B3F8B66E5FFD` — handle an existing empty diagnostic option and assert the initial submitted-event payload.

The final exact-hash review passed only the current hash above. No failed candidate was executed.

## Acceptance status

`admin-post` subject proposal/register behavior remains **NOT RUN** and must not be marked PASS. `.65` remains **IN PROGRESS / NOT READY FOR AUDITOR**; this test does not close the separate browser/UI register checks, legal/Board approval, or the other open phase gates.

## Bounded recovery follow-up — 2026-11-04

One distinct WSL invocation used explicit Linux cwd `/home/tonic/zmp-local-sandbox` to avoid inheriting the NAS UNC cwd. It failed before shell execution with `Wsl/E_ACCESSDENIED` (exit 1). The active site, process, and database could not be inspected; no route, copy, database/session/option mutation, mail/network operation, or product edit occurred. Route assertions remain **NOT RUN**. Preserve this access-denied result alongside the earlier `SITE_PATH_MISSING`; do not repeat the same WSL runtime attempt in this recovery. Full bounded recovery record: `reports/LEAD_UI_WSL_RECOVERY_FOLLOWUP_1.2.4.65_2026-11-04.md`.

## Elevated WSL preflight and candidate prerequisite — 2026-11-04

After the non-elevated `Wsl/E_ACCESSDENIED` result, the user-authorized elevated WSL invocation succeeded. Read-only preflight confirmed `/home/tonic/zmp-local-sandbox/phase65-test/site`, DB `zmp65_isolated`, prefix `z65_`, only the `.65` plugin active, and installed hashes matching the four subject-route class files in the source tree. Pre-run and post-run `wp db check` passed; services remained MariaDB PID `144183` and PHP PID `145525` on `127.0.0.1:8094`.

The exact-reviewed candidate `6752164B8AC9A7C49C90E4FF64F353DFFBFE594398845F879A80EDC8221A3B64` was invoked once and safely stopped before fixture creation/HTTP because no approved active canonical subject exists in the empty synthetic catalog. No route assertion, row/session/option/request mutation, mail/network call, or product edit occurred. The staged test script and its empty `tests/` directory were removed; post-run `wp db check` passed. Route behavior remains **NOT RUN**; do not rerun this hash. The empty catalog is an unmet test prerequisite, not evidence of a product failure. Full recovery record: `reports/LEAD_UI_WSL_RECOVERY_FOLLOWUP_1.2.4.65_2026-11-04.md`.

## 2026-11-04 — First-catalog candidate follow-up

- The earlier exact-reviewed candidate `6752164B8AC9A7C49C90E4FF64F353DFFBFE594398845F879A80EDC8221A3B64` did run only its precondition and correctly stopped because the synthetic catalog and alias table were empty. That result remains BLOCKED before route assertions; it is not overwritten.
- Revised test-only candidate `tests/phase65-subject-register-admin-post.php`, SHA-256 `22395985D379B9428662FBED736B37CB7969622A8FA72518FB17E3BC31D5950B`, changes the scenario to first-catalog creation: require empty catalog/alias tables, Teacher submission, President `approve_new`, exact active canonical/event assertions, and a read-only rendered subject-option check for a second Teacher when available. It removes the inconsistent assertion that a random unique proposal must fail as a duplicate. PHP lint and exact-hash Code Review PASS. The reviewer noted a bounded P3 residual: a route still in flight past both readback windows is intentionally left INDETERMINATE rather than deleting unverified data.
- One runtime attempt was made after staging the exact hash-matching file into the disposable `.65` site. The shell returned `bash: line 1: tests/phase65-subject-register-admin-post.php: Permission denied` before PHP lint, `wp db check`, or `wp eval-file` output. Runtime route behavior is therefore BLOCKED / NOT RUN; no route request or fixture ran. No retry was made.
- Post-attempt `wp db check` PASS. The staged script was removed; `tests/` remains because it contains a trace artifact with an unexpected location, preserved pending review. The site remains on the expected disposable DB/prefix and only the `.65` plugin; no product source, database, session, option, mail, or network state changed.
- Current candidate is reviewed but not functionally verified. Subject proposal/admin-post acceptance remains OPEN; do not infer route PASS from review/lint.

## Correction — final single `.65` subject-register route attempt

- The first invocation of the revised candidate failed at command dispatch and did not reach PHP/WP-CLI. Per the Owner's direction, one corrected invocation used `wp eval-file` (no further retry).
- The exact candidate hash remained `22395985D379B9428662FBED736B37CB7969622A8FA72518FB17E3BC31D5950B`; staged SHA-256 matched, PHP lint PASS, and a fresh pre-run `wp db check` PASS. Runtime then reached the first local route request but stopped with `per-request diagnostic trace could not be verified`; output was **FAIL 1/3** (`DIAGNOSTIC_EVENTS_REMOVED 0`, exact cleanup FAIL, mail/network guard PASS, exact debug cleanup FAIL). Route acceptance assertions were not reached. Do not claim the subject workflow passed.
- Post-run `wp db check` PASS. Read-only checks showed no remaining synthetic proposal/event rows, zero catalog rows, zero alias rows, and no `wp-content/mu-plugins` directory. The staged test script was removed.
- Remaining state is intentionally untouched because ownership/baseline cannot be proved: debug option row ID 128, length 76,767 bytes, autoload off; a session-token meta row for President 25 (baseline unknown); and one trace file under `/home/tonic/zmp-local-sandbox/phase65-test/site/tests/`, an unexpected path relative to `WP_CONTENT_DIR=/home/tonic/zmp-local-sandbox/phase65-test/site/wp-content`. The `tests/` directory remains because it contains this file.
- Sanitized trace metadata: basename `.zmp65-debug-trace-<redacted>.json`; created `2026-11-04T04:14:01.4068881Z`, modified `2026-11-04T04:14:01.4137737Z`, 817 bytes, SHA-256 `1746E94CE81F11208AC94B0C9448EA6C9A2E034B69B314D8BC4011A752F3EBBE`; root JSON keys `baseline_hash`, `flow_id`, `events`; flow ID `33d7d2e1-dbf1-4dbf-beeb-6ac764df8774`; two events with safe context fields `form_action`, `post_fields_count`, `logged_in`, `role_count`, `flow_id`. No event messages/payloads or trace token are recorded here. A read-only option correlation attempt returned a tool `TISDK_CRASH/no_attribution` and no output; therefore exact event ownership and baseline restoration remain unverified.
- Subject-register admin-post acceptance is **FAIL/INCOMPLETE / NOT VERIFIED**, not PASS. No further run or mutation of the debug option, session, or trace file was made.

## Test-only trace instrumentation correction — 2026-11-04

- The prior `FAIL 1/3` is preserved as historical evidence. Debugger isolated the stop to a candidate instrumentation path mismatch: its generated MU-plugin wrote the run trace relative to `__DIR__` (the staged `site/tests` directory), while the candidate reader and cleanup looked under `WP_CONTENT_DIR/mu-plugins`. This does not establish a product-route failure; the route assertions were not reached.
- The candidate now resolves `WP_CONTENT_DIR` once and derives a canonical absolute MU-plugin/trace directory from that resolved path. The generated writer receives that exact path, and writer, reader, and cleanup use it consistently. The generated trace is created exclusively (`x+b`), includes the run token for identity checks, and cleanup removes only a trace whose embedded token matches this run. A newly-created MU directory is removed only after owned guard/trace files are handled and only if it is empty.
- No product source, database, active-site state, old debug option/session state, or previously reported trace file was read or changed for this correction. No runtime/provisioning was performed. PHP lint **PASS**; corrected candidate SHA-256: `E0BDEC6EF7C984377BC1A23CE6CF68BFF7E02E74763E538CC5644DA1EE86A005`. Exact-hash Code Review is pending.
- Subject-register route behavior remains **NOT VERIFIED**. This instrumentation correction does not change the earlier FAIL or turn any acceptance criterion into PASS. Any future route run requires a fresh, isolated synthetic environment and separately reviewed exact candidate hash.

## Fresh-database candidate preparation — 2026-11-04

The previously reviewed candidate `E0BDEC6EF7C984377BC1A23CE6CF68BFF7E02E74763E538CC5644DA1EE86A005` was guarded for the active phase DB and fixed user IDs. It must not be used as a clean-clone test. A test-only revision now requires database `zmp65_subject_test`, prefix `z65s_`, and exact loopback `http://127.0.0.1:8097/`. Before creating fixtures it requires a fresh WordPress install with exactly one administrator and no other users. It then creates two uniquely named synthetic Teacher users and one synthetic Subscriber, appoints the latter as President through `ZMP_President_Mandate::appoint()` under the synthetic administrator, verifies the active mandate/role, and inserts two synthetic mentor rows. No credential value is emitted; fixture data is intended only for the disposable database that must be dropped after the run.

The first-catalog scenario still requires empty subject catalog and alias tables and performs the Teacher submission, anonymous/bad-nonce/non-President denial checks, President `approve_new`, persisted canonical/event checks, and rendered subject option check for the second synthetic Teacher. Route-specific writes, diagnostics, sessions, marker files, and exact cleanup remain guarded. If setup or any precondition fails after fixture creation, discard the exact disposable database/site rather than attempting cleanup against the active site.

Current candidate SHA-256: `15CC3AD061EAC5F2E0BBBAB22302CA41D6383F0DDC714B7E3AF5DBF834AB95F5`. PHP lint: **PASS** (`No syntax errors detected`). Exact-hash independent Code Review: **PENDING**. Runtime: **NOT RUN**. This revision does not change or supersede prior route attempts; historical `FAIL 1/3` and all earlier blocker records remain preserved. No product source, database, session, old debug option, or old trace file was modified.

## Exact-hash review P2 remediation — 2026-11-04

Independent Code Review of candidate `15CC3AD061EAC5F2E0BBBAB22302CA41D6383F0DDC714B7E3AF5DBF834AB95F5` returned **FAIL P2**: the guard/marker preflight did not reject broken symlinks, marker writes were not exclusive, and cleanup did not prove guard inode ownership. That candidate has not been run. Its failure is preserved.

Revised candidate SHA-256: `D37EBA95F0F00EEA3D68DA22F82E4B765338E3C8B4A01B7885488D626ECB9279`. The test now rejects paths when `file_exists`, `is_link`, or `lstat` indicate occupancy; makes run-specific names; creates guard/marker/trace files only with exclusive `x+b`; captures and checks regular-file `fstat`/`lstat` device+inode identity before and after reads/writes; embeds and verifies the run token and device/inode in marker/trace payloads; and only unlinks unchanged files whose current path, opened handle, token and expected content/hash all match. Any uncertain/partial file remains in the disposable clone and causes cleanup failure; the clone must then be discarded. The hidden guard filename was changed to a normal `.php` basename so WordPress loads it from the MU-plugin directory.

PHP lint: **PASS** (`No syntax errors detected`). Exact-hash re-review: **PENDING**. Runtime: **NOT RUN**. No product files, active database, old debug option, session, or old trace artifacts were changed.

### Candidate identity-check follow-up — 2026-11-04

The candidate `D37EBA95F0F00EEA3D68DA22F82E4B765338E3C8B4A01B7885488D626ECB9279` incorporates the P2 fix but is superseded and has **not** been run. A further source-only tightening now verifies the opened handle and current nonsymlink regular path identity before flushing and after reading/writing marker and trace contents; the marker and trace payloads bind the run token to their recorded device/inode; guard creation/readback/cleanup additionally bind exact source SHA and the run token. Cleanup checks the same identity/token/content immediately before unlink and confirms absence afterward. The candidate preserves unverified paths and reports cleanup failure.

Current candidate SHA-256: `4526F32D260A71FF4217F86B1FC21CF888BE3A35C134BA885CD1594D03FDB2F1`. Report hash is the exact current file, reported alongside this candidate hash by the Lead. PHP lint: **PASS**. Exact-hash Code Review: **PENDING**. Runtime: **NOT RUN**. No product code, active database, or pre-existing trace/session/debug state was touched.


### Trace-lock review correction — 2026-11-04

Independent exact-hash review of candidate `4526F32D260A71FF4217F86B1FC21CF888BE3A35C134BA885CD1594D03FDB2F1` returned **FAIL P2**: the route-process trace writer did not hold `LOCK_EX` for its full write, and readers could stop polling as soon as a partially written trace path appeared. That candidate was not run; this failure is preserved.

Current test-only candidate SHA-256: `833ACA970C708CF7BADF64EB6CAEDC21688CB3E2709B3B383869D77B374A151A`. It now holds an exclusive lock through trace/marker creation, complete write, flush, identity checks and readback, releasing locks in `finally`; readers take a shared lock and recheck path/handle identity before reading; polling retries until a full token/inode-validated JSON trace is available. Guard/marker/trace cleanup takes an exclusive lock and validates current regular-file identity, run token and expected content/hash before unlinking; it preserves uncertain paths and fails cleanup. PHP lint: **PASS**. Exact-hash re-review: **PENDING**. Runtime: **NOT RUN**. No product code, active DB, or old trace/session/debug state was touched.

## One clean-clone runtime attempt — 2026-11-04

Candidate SHA-256: `833ACA970C708CF7BADF64EB6CAEDC21688CB3E2709B3B383869D77B374A151A`; its exact-hash Code Review PASS applies to that unchanged candidate. The reviewed report hash before this runtime addendum was `9E3D446A0D7B80C1F5A6C572C83B7D7F44671F4E2C5D21CC0335F37E0575910F`.

The one invocation used a fresh code-only WordPress clone and newly created blank database `zmp65_subject_test` / prefix `z65s_`; all 755 copied plugin runtime files matched the `.65` source by path and SHA-256, with zero differences. The site had one initial synthetic administrator, only the ZMP plugin active/installed, empty subject catalog and alias tables, and DB integrity PASS. PHP server PID `172292`, port `127.0.0.1:8097`, purpose “subject-register-admin-post functional verification”, phase `.65`, was recorded before use.

**Runtime: INDETERMINATE / BLOCKED before route response (exit 3), not a product FAIL.** Candidate stdout was `DIAGNOSTIC_EVENTS_REMOVED 0`; stderr reported `local HTTP response was ambiguous; stopping before another route request` followed by `INDETERMINATE`. The server log contains only its startup line and zero admin-post access lines, so the route never reached the HTTP server. No retry was made.

Post-attempt `wp db check` PASS. Synthetic-only database counts were 4 users, 2 mentor rows and 1 mandate; subject proposals/events/catalog/aliases all remained at 0, and the MU-plugin directory contained no files. The dedicated DB was dropped and absence verified; the exact clone site directory was removed. PID `172292` was absent and port 8097 was released. The PHP process had already ended by the stop check; it was not restarted. No active `.65` DB/site, production, outbound URL, real email or product source was touched. The candidate runtime gate remains **NOT VERIFIED**.

## Sanitized local transport diagnostic follow-up — 2026-11-04

The prior one-shot runtime result remains **INDETERMINATE / BLOCKED before route response**; it is not reclassified as a product failure. Its exact server preflight recorded phase `.65`, PHP PID `172292`, loopback port `8097`, and the intended `POST` path `/wp-admin/admin-post.php`. The test now validates the parsed endpoint scheme, loopback host, port, exact path, and absence of user-info/query/fragment before requesting; it does not print or persist the constructed URL. The earlier server had zero admin-post access records and was stopped; this is evidence about that prior attempt, not a claim that a server is currently running.

This test-only revision preserves the single-attempt/no-retry policy and all subject-register route assertions. On `WP_Error`, it retains only a validated error-code token (otherwise `unclassified`) and an optional numeric cURL errno (0–99999). The raw error message is inspected only in memory to extract a numeric `cURL error N`; it is never emitted or saved. No URL, host, credentials, or raw transport message is captured. The prior candidate/report outcome and hashes above remain historical.

Current candidate SHA-256: `8454A91516E3882500F0C87A88E33B6CF9551FCD44EDF3957730E0449C2F0A33`. PHP lint: **PASS** (`No syntax errors detected`). Exact-hash independent Code Review: **PENDING**. Runtime for this revision: **NOT RUN** pending review. No product source, active database, old debug option, session, or old trace state was modified.

## Fresh-clone staging blocker after exact review — 2026-11-04

The newly reviewed candidate/report hashes were rechecked unchanged. Preflight found port `8097` free, the dedicated DB name absent, and a pre-existing PHP process PID `145525` on port `8094` serving `/home/tonic/zmp-local-sandbox/phase65-test/site`; that unrelated process was not touched. A single bounded attempt to stage the exact `.65` plugin/core into the authorized fresh clone through the Windows-to-WSL path was blocked with `Network access is denied`. The stalled copy was cancelled. A read-only WSL verification confirmed the target clone root is absent; no partial clone, database, or server was created. No runtime or route request occurred. The reviewed test behavior remains **NOT RUN / BLOCKED before runtime**. Do not infer product PASS or FAIL from this environment blocker.


## Exact-hash candidate runtime after review PASS — 2026-11-04

The exact candidate `8454A91516E3882500F0C87A88E33B6CF9551FCD44EDF3957730E0449C2F0A33` and PHP lint were reverified; the staged test fixture hash matched exactly. A fresh synthetic clone used database `zmp65_subject_test`, prefix `z65s_`, WordPress core 7.1.4, one initial synthetic administrator, and exactly one installed/active plugin (`zmp-competitions`). Before the run, database integrity passed and the subject catalog and alias tables each had zero rows. Server preflight found port 8097 free. Owned PHP server PID `212544` was started on `127.0.0.1:8097` for `.65` subject-register-admin-post verification.

The candidate was invoked exactly once. **Test result: FAIL 0/3** (wrapper exit 1): `exact_cleanup_and_baseline_restoration`, `mail_and_outbound_network_guard_not_triggered`, and `exact_debug_event_cleanup_and_baseline_readback` all failed. Stderr: `controlled route test stopped: per-request diagnostic trace could not be verified`. The PHP server log contained 49 lines, including 3 lines matching `admin-post.php`. No sanitized `WP_Error` code or cURL errno was emitted, so the WP_Error transport branch was not observed. Post-run MU-plugin file count was zero; trace JSON presence/content was not independently verified. Do not interpret this as product route PASS or product behavior FAIL; the route acceptance assertions were not reached/verified.

Post-run DB integrity passed. Synthetic counts were users=4, mentor rows=2, mandate rows=1, proposals=0, events=0, catalog=0, aliases=0. No retry was made. PID `212544` was terminated and verified absent; port 8097 was released. The exact disposable DB was dropped and its absence verified, and the exact clone directory was removed and absence verified. The pre-existing server PID `145525` on port 8094 was not touched. The `.65` subject-register functional acceptance remains **NOT VERIFIED**; the observed candidate checks are **FAIL**, with no product defect conclusion supported by this run.

