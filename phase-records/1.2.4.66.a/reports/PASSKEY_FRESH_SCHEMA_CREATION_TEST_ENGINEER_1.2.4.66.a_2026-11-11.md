# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Test Engineer — Fresh Passkey Schema Creation — 1.2.4.66.a

Date: 2026-11-11 (Europe/Skopje, UTC+01:00)  
Role: Independent Test Engineer  
Status: **PASS — fresh schema creation on a copied synthetic child database**. Partial-failure injection remains NOT RUN.

## Reason for a separate child site/database

The existing isolated `.66.a` clone at `/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/site` already contained schema marker version 3, both passkey tables, and a protected synthetic credential. Dropping those in place would destroy protected test state and would not be a safe way to exercise first creation. As recorded before child setup, this acceptance criterion therefore used a uniquely named child sandbox and separate DB schema. Only the child copy had its passkey-specific tables and marker removed. The existing clone, its DB, MariaDB PID 3828, and the Owner site/processes were left unchanged.

This child models **absence of passkey schema on copied synthetic data**. It is not a complete historical `.65` database: its baseline data was copied from the sanitized `.66.a` synthetic clone, then only the passkey tables and version marker were removed while the exact `.65` plugin artifact was active.

## Candidate and prior artifact

- Current `.66.a` first-party source manifest SHA-256: `5A88B12F4AAA2D195F7E5C402C16FCCC12744E6FDA392CACAEA1ABD4E7FAE02B`. Candidate source and final child installed plugin matched all **67/67** manifest entries.
- Key `.66.a` hashes: `includes/class-zmp-passkey-store.php` `F9038DE6499B844529118FE98AB8BD32FE30414BD1061BC35650F638308D02DB`; `zmp-competitions.php` `C0F6CD135F7FBEFB09847FA047F76828736C30C0FA802DAD3F06FDD9C3A4989F`.
- Pre-passkey runtime used the exact locked `.65` release artifact already verified against its 755-entry manifest: ZIP SHA-256 `6181EF7E35F0AEEF9EED0AB70715096455AE337AAEFC3D3FB69C775E4D80A1D4`. The child copy matched that artifact hash, `unzip -t` passed, and its entrypoint hash matched the `.65` manifest. `.65` version loaded as `1.2.4.65`; its passkey store class and passkey action hooks were absent.

## Child identity and pre-mutation recovery

- Child site: `/home/tonic/zmp-local-sandbox/phase66a-schema-create-20261111/site`.
- Separate child DB schema: `zmp66a_schema_create`; prefix `z66a_`. It uses the existing private MariaDB socket owned by PID **3828** (`--skip-networking`); no new MariaDB process was started. The child DB remains distinct from main clone DB `zmp66a_isolated`.
- Effective child `WP_HOME` and `WP_SITEURL`: `http://127.0.0.1:8102`; no PHP server was started. At child preflight, only plugin `.66.a` was active, WordPress was copied from the sanitized local clone, and non-synthetic-email row count was zero.
- Before changing plugin version or child schema, a clone-only recovery backup was created, verified, and kept mode **0600** inside the child sandbox. Its path, name, size, and hashes are intentionally withheld; details remain local to the sandbox.
- Initial child DB integrity passed. Baseline: users/credentials/challenges/proposals/proposal events/mandates/mandate audit/subjects/aliases = `1/1/0/0/0/0/0/0/0`; unrelated ZMP categories = **5**, competitions = **1**. The one copied passkey credential existed only in the child before its passkey table was removed.

## Migration sequence and result

1. Deactivated `.66.a` in the child, switched its plugin directory to the exact `.65` release artifact, and activated `.65`. The schema marker and passkey data were preserved at this stage. No passkey classes or enrollment/login hooks were registered under `.65`.
2. Deactivated `.65` in the child. Using only the child DB connection, removed only `z66a_zmp_passkey_credentials`, `z66a_zmp_passkey_challenges`, and option `zmp_passkey_schema_version`. A DB-only query confirmed both tables and marker absent. User count remained 1; categories remained 5; competitions remained 1; DB integrity passed. This is the recorded pre-migration state.
3. Restored the exact current `.66.a` plugin files from the preserved candidate copy and activated `.66.a`. Immediately after the activation command, before a normal plugin-loading request, DB-only checks still showed both passkey tables and the marker absent.
4. The next normal WP-CLI WordPress bootstrap fired the plugin’s `plugins_loaded` migration path. Exact runtime check command:

```sh
wp --path=/home/tonic/zmp-local-sandbox/phase66a-schema-create-20261111/site eval 'global $wpdb; $ct=$wpdb->get_var("SHOW TABLES LIKE \"z66a_zmp_passkey_credentials\""); $ht=$wpdb->get_var("SHOW TABLES LIKE \"z66a_zmp_passkey_challenges\""); echo "marker=".get_option("zmp_passkey_schema_version","missing")." credentials_table=".($ct?"YES":"NO")." challenges_table=".($ht?"YES":"NO")." compatible=".(ZMP_Passkey_Store::schema_is_compatible()?"PASS":"FAIL")."\n";'
```

   Result: `marker=3 credentials_table=YES challenges_table=YES compatible=PASS`.
5. Database index inspection confirmed the expected indexes:
   - Credentials: `PRIMARY(id)`, `credential_id_hash(credential_id_hash)`, `user_id(user_id)`.
   - Challenges: `PRIMARY(id)`, `challenge_hash(challenge_hash)`, `expires_at(expires_at)`, `user_id(user_id)`, and composite `ceremony_peer_expiry(ceremony,peer_hash,expires_at)`.
6. `wp db check` passed. Post-migration counts were users/credentials/challenges/proposals/proposal events/mandates/mandate audit/subjects/aliases = `1/0/0/0/0/0/0/0/0`; unrelated categories remained **5**, competitions remained **1**. Pre/post `CHECKSUM TABLE` comparisons for categories, competitions, seasons and season-category mappings matched. No literal checksum values are included.

## Final state and limitations

- Child database is left available for later audit with `.66.a` active, schema marker 3, both expected passkey tables and indexes present, schema compatibility PASS, and DB integrity PASS. It contains no passkey credential or challenge rows because this was a fresh-schema test.
- Final child source/clone parity is **67/67** at manifest SHA `5A88B12F4AAA2D195F7E5C402C16FCCC12744E6FDA392CACAEA1ABD4E7FAE02B`.
- No partial-migration failure or corrupt-schema recovery was injected; that criterion remains **NOT RUN**. This report establishes actual first creation from a child DB with only the passkey-specific schema/marker removed, not full historical `.65` database compatibility or partial-failure behavior.
- No product source, shared fixture/harness, phase ledger, original clone, or Owner site was edited. No UI/browser behavior, production access, package, GitHub sync, or overall phase acceptance is claimed.
- Final process check: main/child DB service PID **3828** remains running, Owner PHP PID **620** and MariaDB PID **409** remain running and untouched, no PHP server remains active, and loopback port 8102 is free.