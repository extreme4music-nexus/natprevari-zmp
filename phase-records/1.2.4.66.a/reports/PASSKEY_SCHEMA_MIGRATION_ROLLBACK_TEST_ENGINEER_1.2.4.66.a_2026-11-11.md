# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Test Engineer — Passkey Schema Migration and Rollback Runtime — 1.2.4.66.a

Date: 2026-11-11 (Europe/Skopje, UTC+01:00)  
Role: Independent Test Engineer  
Scope: Bounded migration-idempotence, rollback-to-exact-prior-version, password-login, unrelated ZMP read/render, candidate reinstall and clone restoration. Synthetic clone only. No production, Owner browser, or external network access. No product source or shared test-harness edits.

## Result summary

| Check | Result | Scope / limit |
|---|---|---|
| Existing schema v3 idempotence/compatibility | **PASS** | Already-migrated schema; not a fresh `.65` database install. |
| Deactivate `.66.a`, revert to exact `.65` artifact | **PASS** | Disposable clone only. |
| Passkey data/marker retained during rollback | **PASS** | Marker 3, one credential row and zero challenges remained unchanged. |
| Prior-version password login | **PASS** | Temporary synthetic administrator, actual HTTP `wp-login.php` request; account deleted. |
| Passkey feature unavailable under `.65` | **PASS** | Classes/hooks absent; anonymous login AJAX route HTTP 400; profile page contained no passkey controls. |
| Unrelated ZMP read/render | **PASS** | Competition Structure admin page rendered over HTTP 200 and its `render_page()` behavior returned content. |
| Reinstall exact `.66.a` and verify compatible schema | **PASS** | Marker 3, schema compatibility and DB integrity were valid after activation. |
| Fresh schema creation from pre-passkey `.65` DB | **NOT RUN** | Clone already held schema v3 and a protected synthetic credential before this task. No table/marker deletion or downgrade was used to fabricate a pre-migration state. |
| Partial migration failure injection | **NOT RUN** | Would require corrupting an existing table/index or altering the marker while a protected credential exists; unsafe and outside this bounded runtime sequence. No failure claim is made. |

The migration procedure therefore has **partial runtime evidence**, not complete migration acceptance. The safely available existing-v3 idempotent path and rollback/reinstall path passed; fresh creation and failure/rollback injection remain NOT RUN.

## Exact candidate and prior artifact

- Current `.66.a` first-party source manifest: `reports/CODE_REVIEW_WHOLE_PHASE_CURRENT_1.2.4.66.a_SOURCE_HASHES.txt`, SHA-256 `5A88B12F4AAA2D195F7E5C402C16FCCC12744E6FDA392CACAEA1ABD4E7FAE02B`. The source tree and installed clone matched all **67/67** manifest entries before and after this procedure.
- Key `.66.a` hashes: `includes/class-zmp-passkey-store.php` `F9038DE6499B844529118FE98AB8BD32FE30414BD1061BC35650F638308D02DB`; `includes/class-zmp-passkeys.php` `ACEE4133DEE295E2BDD401EF22B7CD304626A53F0F09D698027C69E34DCDC2AE`; `zmp-competitions.php` `C0F6CD135F7FBEFB09847FA047F76828736C30C0FA802DAD3F06FDD9C3A4989F`.
- Exact prior artifact available: `ZMP_PROJECT_LIBRARY/04_RELEASES/VERSIONS/1.2.4.65/ZIP/zmp-competitions-1.2.4.65.zip`, SHA-256 `6181EF7E35F0AEEF9EED0AB70715096455AE337AAEFC3D3FB69C775E4D80A1D4`. Locked manifest `HASHES/SOURCE_SHA256_1.2.4.65.txt`, SHA-256 `40E01B235B16511DE4D26560D1A6670DB1392BF5F167393BF074E9771A19B7CB`, lists 755 files. Every ZIP entry matched its manifest hash (755/755), with no extras and one root `zmp-competitions`; `unzip -t` passed. Extracted prior entrypoint hash `291D0E7601B1DA01CDC96FE12774FB632E2638169E8526DDC3E9864D4B124A96` matches the manifest and declares version/schema `1.2.4.65`. Its packaged PHP contains no passkey/WebAuthn/step-up implementation.
- The installed clone's original `.66.a` plugin tree was preserved before replacement: 2,243 files copied; recursive comparison reported no differences. After reinstall, key hashes above and 67/67 first-party manifest parity were rechecked.

## Clone identity and pre-mutation baseline

- Isolated site: `/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/site`; clone database `zmp66a_isolated`, prefix `z66a_`; WordPress 7.1.4; only active plugin was `.66.a` 1.2.4.66.a.
- Clone MariaDB PID **3828**, private socket `/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/run/mariadb.sock`, `--skip-networking`. No PHP server was running at preflight. Clone's `WP_HOME`/`WP_SITEURL` config constants were `http://127.0.0.1:8102`; never used port 8101 for clone HTTP.
- Baseline users/credentials/challenges/proposals/proposal events/mandates/mandate audit/subjects/aliases: `1/1/0/0/0/0/0/0/0`. Protected synthetic user `zmp66a_admin` retained its sole `Synthetic UI review only` passkey credential. No secret, private key, passkey record, or personal value was printed.
- Before runtime mutation, clone `wp db check` passed and marker `zmp_passkey_schema_version=3` was confirmed. Both passkey tables and required indexes existed; `ZMP_Passkey_Store::schema_is_compatible()` returned true. Initial schema fingerprint SHA-256 `745dcdc76fa5c319c0ee5a71ec0845fcfe0ee0247c3a0c502ccb5a746ec2f803`; protected credential row unchanged; digest comparison matched; literal digest withheld.

## Backup and verification before runtime changes

At 2026-11-11 12:15:03 +01:00, a private mode-0600 clone-only database backup was created and verified before any runtime mutation. The backup covered the clone database and its synthetic state. Backup path/name, hash, size and command details are intentionally omitted from this report; they remain only in the isolated sandbox.

## Migration idempotence check

Exact command:

```sh
wp --path=/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/site eval 'echo ZMP_Passkey_Store::install_schema() ? "INSTALL_SCHEMA_PASS\n" : "INSTALL_SCHEMA_FAIL\n"; if (!ZMP_Passkey_Store::schema_is_compatible()) { exit(4); }'
```

Result: `INSTALL_SCHEMA_PASS`; `schema_is_compatible()` true; marker remained `3`; `wp db check` passed. Before/after schema fingerprint stayed `745dcdc76fa5c319c0ee5a71ec0845fcfe0ee0247c3a0c502ccb5a746ec2f803`; protected credential row unchanged; digest comparison matched; literal digest withheld. This invocation was a legitimate already-current idempotent path. It does not prove initial creation of missing tables because the clone was already at schema v3.

## Rollback to `.65` and behavior checks

1. `wp --path=/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/site plugin deactivate zmp-competitions` returned success. Immediately afterward, marker 3, credential count 1, challenge count 0 and the protected credential row was unchanged; digest comparison matched; literal digest withheld; `wp db check` passed.
2. The active clone plugin folder was moved to clone-local `run/plugin-66a-installed-deactivated`; the exact verified `.65` package was copied into the active plugin path. `wp plugin activate zmp-competitions` returned success. WP-CLI reported active version `1.2.4.65`.
3. Under `.65`, `ZMP_Passkeys`, `ZMP_Passkey_Store` and `ZMP_Passkey_Profile` were absent; the passkey registration and anonymous login action hooks were absent. `admin-ajax.php?action=zmp_passkey_login_options` returned HTTP **400**, consistent with the route being unavailable. `wp-admin/profile.php` rendered with HTTP 200 and no passkey text or controls. The passkey schema marker stayed 3 and the protected credential row was unchanged; digest comparison matched; literal digest withheld.
4. A synthetic temporary administrator was created with a random password in memory only. No password, username, cookie or response body was logged. Actual loopback HTTP sequence: GET `/wp-login.php` **200** (WordPress test cookie issued); POST `/wp-login.php` **302** (authentication cookies issued); authenticated GET `/wp-admin/` **200** with the admin bar. The authenticated `admin.php?page=zmp-competition-structure` rendered HTTP **200**. The synthetic Competition Structure `render_page()` also returned content (40,097 bytes) under the authorized synthetic administrator. The temporary account was deleted in `finally`; later exact database restoration confirmed no test account or state remained.
5. The password-flow HTTP process was clone PHP PID **9990**, command `php -S 127.0.0.1:8102 -t /home/tonic/zmp-local-sandbox/phase66a-retest-20261111/site`, bound only to loopback port 8102 for `.66.a` rollback runtime. PID metadata was created at 12:20:55 +01:00; the last request log update was 12:22:37 +01:00. After checks, only PID 9990 was stopped after command-line verification; PID exited and `ss` confirmed 8102 released. No server remains running.

## Reinstall `.66.a`, integrity and restoration

1. Deactivated `.65`, confirmed passkey tables/marker/credential unchanged, moved its files to clone-local `run/plugin-65-tested`, and restored the preserved `.66.a` tree to `site/wp-content/plugins/zmp-competitions`. Exact recursive comparison against the preserved tree passed (2,243 files). `wp plugin activate zmp-competitions` succeeded.
2. WP-CLI reported active version `1.2.4.66.a`; after activation, `ZMP_Passkey_Store::schema_is_compatible()` returned true; marker stayed 3; counts returned to `1/1/0/0/0/0/0/0/0`; protected credential row was unchanged; digest comparison matched; literal digest withheld; `wp db check` passed.
3. To remove all test-only database effects (including the temporary user’s auto-increment consumption), the verified pre-runtime clone backup was imported back into the same isolated database. The first post-import check invoked WordPress plugin listing and showed only the options table differed; that was not accepted as exact restoration. The same verified backup was imported again, followed only by DB-level check/export/queries. No further imports were performed.
4. Exact restoration verification: pretest and final post-restore database content comparison matched after excluding generated export-time metadata. Final DB check passed; marker 3, active `.66.a` plugin option, and baseline counts `1/1/0/0/0/0/0/0/0` were confirmed with DB-only queries. The restored DB stores its original raw `home`/`siteurl` values as 8101; clone-local `WP_HOME`/`WP_SITEURL` constants still override the effective clone URLs to 8102. This preserves the exact pretest database snapshot while keeping clone HTTP isolated.
5. Final process check: clone MariaDB PID **3828** remains running on the private socket with `--skip-networking`; clone PHP PID 9990 is stopped and port 8102 released. Original Owner PHP PID **620** and MariaDB PID **409** remained live and untouched. Final `.66.a` first-party source/clone parity remains **67/67** at manifest SHA `5A88B12F4AAA2D195F7E5C402C16FCCC12744E6FDA392CACAEA1ABD4E7FAE02B`.

## Limitations and disposition

- This report verifies idempotence on an already-v3 schema and a complete exact-prior-version rollback/reinstall sequence on a synthetic clone. It is not proof of initial migration from a `.65`-era database, nor partial-migration failure recovery. Those remain **NOT RUN** and must not be recorded as PASS.
- No product source, shared fixture/harness, phase ledger, or Owner site was edited. Temporary plugin swaps, generated test account, and all database writes were confined to the disposable clone and returned to its verified pretest state.
- No ZIP, GitHub synchronization, UI authenticator ceremony, production access, or phase acceptance is claimed.
### Probe qualification

The first password-flow probe forwarded only the `wordpress_logged_in` cookie and therefore did not establish an authenticated admin session (dashboard/profile/structure requests redirected). It was **not counted** as a product-flow PASS or FAIL. The corrected, bounded probe forwarded the WordPress cookies set by the successful login response; it then observed the PASS statuses reported above. The temporary account was deleted in both probes. No product code was changed in response.