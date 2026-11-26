# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Test Engineer — Current-Hash Functional Revalidation — 1.2.4.66.a

Date: 2026-11-11  
Role: Independent Test Engineer  
Scope: Synthetic current-candidate Task3 registration/management, Task4 valid passkey login, and Task5 President subject-step-up matrix. No source or fixture edits. No Owner browser/session access, production access, or real email.

## Candidate and isolated environment

- Current first-party source manifest `CODE_REVIEW_WHOLE_PHASE_CURRENT_1.2.4.66.a_SOURCE_HASHES.txt`, SHA-256 `5A88B12F4AAA2D195F7E5C402C16FCCC12744E6FDA392CACAEA1ABD4E7FAE02B`; the 67 candidate source files and all 67 isolated-clone plugin files matched the manifest.
- Key exact candidate hashes: passkeys `ACEE4133DEE295E2BDD401EF22B7CD304626A53F0F09D698027C69E34DCDC2AE`; store `F9038DE6499B844529118FE98AB8BD32FE30414BD1061BC35650F638308D02DB`; profile `705C7DDA64DB3307DB34C06D1A5134AEA76464DF78CAB77BD3406E5E6A5DDD6D`; entrypoint `C0F6CD135F7FBEFB09847FA047F76828736C30C0FA802DAD3F06FDD9C3A4989F`.
- Isolated site: `/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/site`; WordPress 7.1.4; active plugin inventory only `zmp-competitions` 1.2.4.66.a. Clone DB `zmp66a_isolated`, prefix `z66a_`.
- Clone MariaDB PID **3828**, private socket `/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/run/mariadb.sock`, `--skip-networking`. Clone-only PHP server PID **5483** was started on `127.0.0.1:8102` for the Task5 attempt; `/wp-login.php` GET returned 200. It was stopped after the blocked attempt; PID no longer exists and port 8102 is released. MariaDB PID 3828 remains running for follow-on work.
- Original Owner site PHP PID **620** at `127.0.0.1:8101` and MariaDB PID **409** were not changed. The clone's own `WP_HOME`, `WP_SITEURL`, `home` and `siteurl` were set to `http://127.0.0.1:8102`; its `wp-config.php` points to clone-only MariaDB root/private socket. The original site's config/options were not changed.
- Clone baseline immediately before tests: users/credentials/challenges/subject proposals/proposal events/mandates/mandate audit = `1/1/0/0/0/0/0`; protected synthetic `zmp66a_admin` credential label `Synthetic UI review only` present. `wp db check` PASS.

## Results

### Task 3 — registration and passkey management

- **PASS (49), exit 0.** Command: `wp --path=/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/site eval-file /home/tonic/zmp-local-sandbox/phase66a-retest-20261111/tests/phase66a-passkey-registration.php`.
- Fixture SHA-256 `7C041C1F6F5C269FB87CDF84D08EF9949D6CE92B52F0B6A65B77BD80FA9C5959`. Candidate source hashes are listed above; all first-party candidate/plugin files matched the 67-file manifest.
- Output included expected fixed allowlisted registration diagnostics for denied President preconditions and successful options branches, then `PASS (49): Task 3 registration and passkey management behaviors`. No secrets or personal payloads were printed.
- Cleanup/baseline PASS: `1/1/0/0/0/0/0`; protected credential remained. `wp db check` PASS.

### Task 4 — valid passkey login

- **PASS (17), exit 0.** Command: `wp --path=/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/site eval-file /home/tonic/zmp-local-sandbox/phase66a-retest-20261111/tests/phase66a-passkey-valid-login.php`.
- Fixture SHA-256 `F2C3F9FFBB008C095A4C7493F88161AF2A5CCABC35C54C772B3D2D116028707B`. Candidate source hashes are listed above.
- Output confirms valid assertion/login, session and counter checks; fixture reported cleanup PASS and logout revocation. It explicitly emitted no private material/assertion/cookie values.
- Cleanup/baseline PASS: `1/1/0/0/0/0/0`; protected credential fingerprint preserved. `wp db check` PASS.

### Task 5 — President subject-step-up decision matrix

- **BLOCKED before fixture writes; no product behavior executed.** Command attempted once: `wp --path=/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/site eval-file /home/tonic/zmp-local-sandbox/phase66a-retest-20261111/tests/phase66a-president-subject-step-up-matrix.php`.
- Fixture SHA-256 `AF0652DAA0FC09F17962BF21350AE523461CDBE3253049C7F08571C7F647EC88`; the unchanged fixture has a static round-2 setup/cleanup review PASS (`CODE_REVIEW_TASK5_STEPUP_MATRIX_ROUND2_1.2.4.66.a.md`).
- Fixture emitted `BLOCKED: configured Origin and admin-post URL scheme/host/port do not match; no fixture writes`. Its preflight and route helper require `admin_url()` to be exactly `http://127.0.0.1:8101/wp-admin/admin-post.php`; it then uses `wp_remote_post()` over HTTP for the actual decision route. The isolated clone was deliberately set to port 8102 to avoid POSTs reaching the Owner's original PHP site on port 8101. The fixture was not changed and was not pointed at the original site. No route request was issued by this test.
- Cleanup/baseline after the blocked preflight remains PASS: `1/1/0/0/0/0/0`; `wp db check` PASS. No retry. A safe route matrix needs a separately reviewed fixture or another approved isolated routing setup; do not simply set clone URL back to 8101 because that would target the Owner's original server.

## Final environment and limitations

- Test suite statuses: Task3 **PASS (49)**; Task4 **PASS (17)**; Task5 matrix **BLOCKED before writes**. No UI/browser ceremony, migration/rollback runtime, cancellation, unavailable-authenticator, or concurrent interleaving was tested.
- Clone PHP PID 5483 terminated and port 8102 is released. Clone MariaDB PID 3828 remains live on its private socket with networking disabled for follow-on checks. Original owner processes PID 620 and PID 409 remain live and untouched.
- These are current-candidate focused functional results only. They do not establish whole-phase acceptance, Master/Auditor disposition, package, or GitHub synchronization.

## Addendum — reviewed clone-port adjustment and Task5 rerun

Date: 2026-11-11 (Europe/Skopje)  
This addendum preserves the initial Task5 **BLOCKED** result above and records the later, separately authorized isolated-clone revalidation. The earlier fixture and report text remain historical evidence; the current Task5 result below supersedes the blocked status only for the adjusted fixture hash.

- **Test-only fixture delta:** `tests/phase66a-president-subject-step-up-matrix.php` changed from `AF0652DAA0FC09F17962BF21350AE523461CDBE3253049C7F08571C7F647EC88` to SHA-256 `58FA9A3519DE44C909600634972D9432A093321CF174A13696914F372D860901`. It preserves port 8101 by default and permits port 8102 only when clone `wp-config.php` defines `ZMP_PHASE66A_ISOLATED_TEST_CLONE` as strict boolean `true`; preflight and route helper retain exact loopback host, matching scheme/effective port, and `/wp-admin/admin-post.php` checks. Route remains `wp_remote_post()` to the clone's own `admin_url()`. No product source, original config, or phase ledger was changed.
- **Independent exact-hash review:** PASS for fixture hash `58FA9A3519DE44C909600634972D9432A093321CF174A13696914F372D860901`; review report `CODE_REVIEW_TASK5_CLONE_PORT_FIXTURE_1.2.4.66.a.md`, SHA-256 `A73F3EBA18B4F013BCBFD59D63984854C538FC8ADB7C745FF2C67CEFAB4DBB01`.
- **Clone preflight:** clone site and DB remained `/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/site`, `zmp66a_isolated`, prefix `z66a_`; WP_HOME and WP_SITEURL were both `http://127.0.0.1:8102`; clone-only constant evaluated strict true; active plugin remained only `zmp-competitions`. The reviewed fixture copied into the clone had the same SHA-256 as the reviewed source. Pre-run counts were users/credentials/challenges/proposals/proposal events/mandates/mandate audit/subjects/aliases = `1/1/0/0/0/0/0/0/0`; protected synthetic `zmp66a_admin` still had its sole `Synthetic UI review only` credential. Pre-run `wp db check` PASS at 2026-11-11 12:06:17 +01:00.
- **Server lifecycle:** immediately before start, `ss -ltnp 'sport = :8102'` showed no listener. Exact server command: `php -S 127.0.0.1:8102 -t /home/tonic/zmp-local-sandbox/phase66a-retest-20261111/site`. Clone PHP server PID **5996**, bound only to `127.0.0.1:8102`, purpose `.66.a Task5 route matrix clone-only`; `/wp-login.php` returned HTTP 200. Test ran 2026-11-11 12:06:21–12:06:23 +01:00. After cleanup/baseline and database check at 12:06:34 +01:00, only PID 5996 was stopped after exact command-line verification. PID exited and port 8102 was released. Clone MariaDB PID **3828** remains live with `--skip-networking`. Original owner PHP PID 620 and MariaDB PID 409 remain untouched.
- **Task5 adjusted-fixture result:** **PASS (38), exit 0.** Exact command: `wp --path=/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/site eval-file /home/tonic/zmp-local-sandbox/phase66a-retest-20261111/tests/phase66a-president-subject-step-up-matrix.php`. Safe summarized route outcomes: new subject approval PASS; alias approval PASS; rejection PASS; alias target mismatch rejected while proposal/event and alias/catalog snapshots stayed unchanged. Fixture printed `CLEANUP PASS; protected credential unchanged`; no private material, assertions, cookies, or keys were printed. True concurrent race interleaving remains **NOT RUN**.
- **Post-run integrity:** baseline restored to users/credentials/challenges/proposals/proposal events/mandates/mandate audit/subjects/aliases = `1/1/0/0/0/0/0/0/0`; protected credential label unchanged; `wp db check` PASS. The earlier first Task5 attempt remains documented as **BLOCKED before writes** for fixture hash `AF0652...`; it is not overwritten or reclassified.
