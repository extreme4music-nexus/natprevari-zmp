# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Passkey schema v3 and aggregate quota test preparation — `.66.a`

Date: 2026-11-05  
Role: Test Engineer  
Status: test-only preparation complete; runtime against v3 is **NOT RUN**.

## Scope and acceptance

Prepared narrowly scoped test updates for the schema-v3 peer binding and Round 4 findings. Login challenge issuance fixtures now derive `peer_hash` as HMAC-SHA256 of the canonical validated synthetic IP using `wp_salt('auth')`; login finish does not include/match peer hash. The existing origin digest remains required. Registration still requires empty context/peer hashes; step-up still requires its action context hash and empty peer hash.

The global-quota fixture seeds exactly 500 live synthetic login rows representing distinct synthetic peers and invokes the actual anonymous `zmp_passkey_login_options` WordPress AJAX hook once. Acceptance is a fixed generic HTTP 400 denial, zero additional challenge rows, no session creation, and exact restoration of user/credential/challenge baselines after deleting only fixture-inserted challenge hashes. It refuses to run unless the active site is already schema v3 with exact `peer_hash CHAR(64) NOT NULL DEFAULT ''`, compatible tables, and an empty challenge baseline. The test uses an Engineering Lead acceptance threshold of 500 live anonymous login issuance rows per 300 seconds globally; the per-peer quota remains five.

The storage suite verifies schema marker 3, exact peer column shape and ordered `ceremony_peer_expiry` index. It then changes only the empty synthetic challenge table's `peer_hash` shape one at a time (wrong type, nullable, wrong default), expects `schema_is_compatible()` to return false for each, and restores `CHAR(64) NOT NULL DEFAULT ''` in `finally` before continuing. These ALTER cases are deliberately not run on the site's current v2 schema. The full v2→v3 migration remains **NOT RUN** pending an approved frozen source candidate sync; the test site pre-migration marker is recorded below.

## Pre-sync environment checkpoint

Read-only check on the authorized synthetic site `/home/tonic/zmp-local-sandbox/phase66a-test/site` confirmed DB `zmp66a_isolated`, prefix `z66a_`, active plugin inventory only `zmp-competitions`, schema marker `2`, and existing services PHP PID `286942` and MariaDB PID `286676`. Site source hashes are the prior schema-v2 candidate: passkeys `BAA1D3D6CF27C7A06BA44A6ADC5FCA73AAD2E61AC5BF9668DE4AB6D938C14F9A`, store `9AA2ACE4B9932DB8DD7D158BC22B003AB6D8F46D11B55ADD101798E54431F95D`, entrypoint `15A87C3ECB1FFDFCACE9198D2F206060117396FA9C58FFD80F2A9A00D9CC9B93`. Current phase source candidate (not synced) is passkeys `6C6ED88DB103FA908301AB57A65F228EAC2BAB6C73C26444EB310120F623BA96`, store `AA11AD06D134DA3055AE0F7969F2AAF1F51DC97DF8FB677CF726E9669FECD848`, entrypoint `15A87C3ECB1FFDFCACE9198D2F206060117396FA9C58FFD80F2A9A00D9CC9B93`.

At this checkpoint, no source file had been synced, no database rows/schema had been changed, no fixture had been run against the v2 site, and no service lifecycle action had been taken. The v2 site could not safely exercise the newly authored v3 peer-HMAC/global quota fixtures.

## Fixture changes and exact hashes

All listed test fixtures passed `php -l` (exit 0, “No syntax errors detected”). They have not been run against the v3 candidate.

| Test fixture | SHA-256 | Current v2 status |
|---|---|---|
| `tests/phase66a-passkey-login.php` | `982F3B0685480A91CF640BB4533DB8D287B25157ACE11AF2184DFA075E2F0C1D` | NOT RUN; requires v3 peer column/HMAC |
| `tests/phase66a-passkey-login-assertion.php` | `03B37F11C6D3623D625D949FB3FA82BA84DE9A456AD8B1071AD8F9B152ED7DFE` | NOT RUN; requires v3 peer column/HMAC |
| `tests/phase66a-passkey-store.php` | `264D29276892203AED6B6BFE378B0A4066319DB261A778C4FF60AD38D68347F6` | NOT RUN; migration/shape mutation requires v3 |
| `tests/phase66a-step-up-context.php` | `44A2068AADEDF176ED9E3B823AADEABA78AF5CC44F2C9FAEF4178F48E23A2B1` | NOT RUN; login seed now follows v3 peer contract |
| `tests/phase66a-login-transient-eviction.php` | `663512CF0418C8B756258CB969AC8490F2C267901A6B2A21E648994FB14005CB` | NOT RUN; now checks durable DB quota despite irrelevant transient deletion |
| `tests/phase66a-login-issuance-churn.php` | `E2C696BAC0EAE526A47D920D0B7B566D89F23D93C23C475432EE0DF2C39E03BF` | NOT RUN in this update; existing prior-hash PASS remains historical |
| `tests/phase66a-login-context-rate.php` | `FDF19DEAADEADEEA045A3052AFA34BAA96F29EDDAC6D652D66D0AB9EF908763` | NOT RUN in this update; existing prior-hash PASS remains historical |
| `tests/phase66a-login-global-quota.php` | `604DDDED0A8EB52A2F6C978069F28EAA2ECA954CEAFD4F2B14D27FA0245F9B6F` | Current v3 bounded run: expected RED, cleanup PASS |

The initial v2 checkpoint did not produce expected RED evidence: v3-only tests refused to run before seeding or schema mutation. The earlier transient-eviction/churn RED/PASS history remains bound to its original fixture and source hashes in their existing reports. Those prior outcomes are not overwritten or reinterpreted.

## Authorized v3 synthetic run — 2026-11-05

The Engineering Lead authorized a bounded sync/run despite the known round4 SPEC/QUALITY FAIL, solely for synthetic migration and expected-RED evidence, not release acceptance. Before sync, site identity was confirmed as `/home/tonic/zmp-local-sandbox/phase66a-test/site`, URL `http://127.0.0.1:8101`, DB `zmp66a_isolated`, prefix `z66a_`, one active plugin `zmp-competitions`, users=1, credentials=1, challenges=0, and the protected credential row's `credential_id_hash`=`55a9a0d901ee3f23eefe4c550e4c0eba6337cb34d7a25fdd01c877402be5519b`. Only the two changed PHP source files were synchronized; entrypoint remained unchanged.

Migration evidence: before plugin load the schema marker was v2 and `peer_hash` was absent. After WP-CLI plugin load, **PASS**: marker `3`, `peer_hash CHAR(64) NOT NULL DEFAULT ''`, and index `ceremony_peer_expiry (ceremony, peer_hash, expires_at)` present in order. The protected credential remained present.

Fixture outcomes on exact v3 candidate:

| Check | Result |
|---|---|
| `phase66a-passkey-login.php` | **PASS (9), exit 0** |
| `phase66a-passkey-login-assertion.php` | **PASS (13), exit 0**; valid signature/counter persistence **NOT RUN** |
| `phase66a-login-context-rate.php` | **PASS (7), exit 0** |
| `phase66a-login-transient-eviction.php` | **PASS (4), exit 0**; five accepted/consumed; no transient existed; sixth denied |
| `phase66a-login-issuance-churn.php` | **PASS (9), exit 0**; five accepted/consumed; later options denial |
| `phase66a-passkey-store.php` | **FAIL (5/79), exit 1**: two strict exact-binding assertions and three expected schema-shape rejection assertions failed. The schema shape findings reproduce the Round 4 issue: compatibility remains true for wrong type, nullable, and wrong default. Shape restoration completed; final schema compatible. The exact-binding assertions compare normalized return arrays against inputs that omit normalized empty `peer_hash`; treat as fixture expectation mismatch, not a separate product conclusion. |
| `phase66a-step-up-context.php` | **FAIL (2/7), exit 1**: assertions expected the normalized output to omit `peer_hash`; cleanup passed. This is a fixture expectation mismatch for the additive normalized empty field, not an independently established product defect. |
| `phase66a-passkey-registration.php` | **PASS (49), exit 0** |
| `phase66a-passkey-review-round2.php` | **PASS (18), exit 0** |
| `phase66a-login-global-quota.php` | First attempt: **FAIL (3/5), exit 1**, observed 500 seeded rows plus a successful 501st option/challenge; cleanup initially missed that returned challenge. One-time cleanup fixture SHA `59CEF4C508A34927DF031E608D43F578A6F70299C607606302302181FEE27FBF` verified this was the sole unconsumed row for synthetic peer `127.0.0.201` on the previously empty challenge baseline, deleted exactly that challenge, and preserved the protected credential fingerprint. Corrected fixture then ran once: **expected RED FAIL (2/5), exit 1**; 500 rows seeded, options response created row 501 (`challenge_rows_after_request=501`), no HTTP denial status captured. **Cleanup PASS** after the corrected run; exact 500 seeded hashes and the returned 501st challenge were removed. |

Lint: **PASS**, `php -l` on both synchronized source files and the corrected global quota fixture; all report no syntax errors. DB integrity: **PASS**, `wp db check` returned “Success: Database checked.” Final state: users=1, credentials=1, challenges=0; protected row hash remained `55a9a0d901ee3f23eefe4c550e4c0eba6337cb34d7a25fdd01c877402be5519b`. Candidate/source-site parity **PASS**: passkeys `6C6ED88DB103FA908301AB57A65F228EAC2BAB6C73C26444EB310120F623BA96`; store `AA11AD06D134DA3055AE0F7969F2AAF1F51DC97DF8FB677CF726E9669FECD848`; entrypoint `15A87C3ECB1FFDFCACE9198D2F206060117396FA9C58FFD80F2A9A00D9CC9B93`. PHP PID `286942` and MariaDB PID `286676` remained active; no service lifecycle changes.

The global aggregate limit remains **FAIL / expected RED** on this candidate: the options endpoint inserted an additional live login challenge at 500 seeded rows. Round4 schema-shape compatibility also remains **FAIL / expected RED**. No phase acceptance or release conclusion is made.

## Required next run after source review/sync

## Focused candidate verification round 2 — 2026-11-05

The exact v3 candidate below was synchronized after a fresh preflight on the authorized synthetic site. Only the two changed passkey PHP files were synchronized; the entrypoint remained unchanged. The existing schema marker was already `3`, so this round did not perform a v2-to-v3 migration.

Candidate/source-site parity:

| File | SHA-256 |
|---|---|
| `includes/class-zmp-passkeys.php` | `587CEAA26CB3A023717B0B366249449D0385FC038204FD798D50562473B73DC7` |
| `includes/class-zmp-passkey-store.php` | `4A6BC4D0DACD23A0C41318FCAEAEA6886B1032406E40269A89CFF19401036B42` |
| `zmp-competitions.php` (unchanged) | `15A87C3ECB1FFDFCACE9198D2F206060117396FA9C58FFD80F2A9A00D9CC9B93` |

Test-only expectation correction: store and step-up fixtures compare normalized bindings with sorted keys so PHP array insertion order does not create a false failure. Current fixture hashes: storage `tests/phase66a-passkey-store.php` SHA `D88A8811A22E9BC8A6F231FD10ADFE0B5F6AABE43E7A9502E76BCED015CEC6DF`; step-up context `tests/phase66a-step-up-context.php` SHA `69E4FE12C2D0E8C232F55A89C90E1983E3F16CC5210867E9735B2F551F391025`.

| Focused check | Result |
|---|---|
| Storage/schema fixture, first run before comparator correction | **FAIL (2/79), exit 1** on strict array-order checks; schema-shape assertions themselves passed and the fixture restored the table. Baseline after failure was 1 user, 1 credential, 0 challenges; schema marker 3 and exact peer column/index restored. |
| Storage/schema fixture, one rerun after test-only correction | **PASS (79), exit 0**. This includes wrong type/nullability/default detection and exact restoration. |
| Login options `tests/phase66a-passkey-login.php` SHA `982F3B0685480A91CF640BB4533DB8D287B25157ACE11AF2184DFA075E2F0C1D` | **PASS (9), exit 0**. |
| Global quota `tests/phase66a-login-global-quota.php` SHA `604DDDED0A8EB52A2F6C978069F28EAA2ECA954CEAFD4F2B14D27FA0245F9B6F` | **PASS (5), exit 0**. Observed 500 seeded rows, HTTP status 400, and challenge count still 500 after denial; fixture cleanup returned total challenges to baseline. |
| Per-peer issuance churn `tests/phase66a-login-issuance-churn.php` SHA `E2C696BAC0EAE526A47D920D0B7B566D89F23D93C23C475432EE0DF2C39E03BF` | **PASS (9), exit 0**. Five options/challenges accepted and consumed; one later denial across 12 options/invalid-finish cycles. |
| Transient eviction `tests/phase66a-login-transient-eviction.php` SHA `663512CF0418C8B756258CB969AC8490F2C267901A6B2A21E648994FB14005CB` | **PASS (4), exit 0**. Five accepted/consumed; no transient existed; sixth option denied. |
| Step-up context `tests/phase66a-step-up-context.php` SHA `69E4FE12C2D0E8C232F55A89C90E1983E3F16CC5210867E9735B2F551F391025` | **NOT RUN** in this scoped round; only the strict comparison expectation was corrected and PHP lint passed. |
| Round5 expired-row cleanup failure P2 | **NOT RUN** per Lead direction; do not author/run a cleanup-failure fixture against this candidate before Backend's fix. |

PHP lint **PASS** on both candidate source files and all changed/run fixtures for this round. `wp db check` **PASS** (“Success: Database checked.”). Final synthetic baseline: users=1, credentials=1, challenges=0; protected UI credential hash remained `55a9a0d901ee3f23eefe4c550e4c0eba6337cb34d7a25fdd01c877402be5519b`. Site remained DB `zmp66a_isolated`, prefix `z66a_`, only active plugin `zmp-competitions`. Source/site parity matches the table above. PHP PID `286942` listens on `127.0.0.1:8101`; MariaDB PID `286676` remained active. No service lifecycle change.

No UI test, valid cryptographic assertion/counter test, unrelated regression, package, GitHub sync, or phase acceptance occurred in this round. The new Round5 cleanup-failure finding remains open and awaits Backend's product correction.

## Round6 expired-row cleanup failure verification — 2026-11-05

The Round6 exact-hash review candidate was tested on the authorized existing synthetic site after a fresh baseline. `class-zmp-passkeys.php` was unchanged at SHA `587CEAA26CB3A023717B0B366249449D0385FC038204FD798D50562473B73DC7`; only changed `class-zmp-passkey-store.php` was synced at SHA `F9038DE6499B844529118FE98AB8BD32FE30414BD1061BC35650F638308D02DB`; entrypoint remained `15A87C3ECB1FFDFCACE9198D2F206060117396FA9C58FFD80F2A9A00D9CC9B93`. Source/site hashes matched after sync.

New focused fixture `tests/phase66a-login-expired-cleanup-failure.php`, SHA-256 `60F2FDD5AEC0816DDD0EBC7EC149ECA615587C35C799B25D7992D823BB84B584`, forces only the exact expired-challenge cleanup SQL (`DELETE FROM z66a_zmp_passkey_challenges WHERE expires_at <= UTC_TIMESTAMP()`) to become one deliberate invalid SQL statement through a narrowly matching WPDB query filter. It seeds one expired, fixture-owned login row and calls the actual nopriv login-options AJAX endpoint with same-origin headers and a synthetic peer.

- First fixture attempt: **FAIL (1/5)** because the fixture also required `$wpdb->last_error` to remain observable after the AJAX callbacks. Observed evidence already showed one exact-query interception, fixed generic HTTP 400, and the single expired row still present; cleanup passed. The extra `last_error` observation was not retained by the completed WordPress request path.
- Corrected fixture run (one rerun): **PASS (5), exit 0**. Observed `cleanup_interceptions=1`, `generic_http_400=true`, `challenge_rows_after_request=1`; the one row is the deliberately seeded expired row, proving no new login challenge was inserted. The fixture removed its query filter and exact owned row; cleanup passed. No product code changed during this test.

Because challenge insertion shares the corrected store path, the scoped issuance checks were also run: login options **PASS (9)**; per-peer issuance churn **PASS (9)**; transient eviction **PASS (4)**. Global quota was **NOT RUN** in this round: passkeys source/hash was unchanged and the at-cap denial path exits before challenge creation; prior global-cap PASS remains on that same passkeys hash. No unrelated test suite was run.

Pre-run and final synthetic baseline: site `/home/tonic/zmp-local-sandbox/phase66a-test/site`, DB `zmp66a_isolated`, prefix `z66a_`, schema marker `3`, only active plugin `zmp-competitions`; users=1, credentials=1, challenges=0 after cleanup. Protected UI credential hash remained `55a9a0d901ee3f23eefe4c550e4c0eba6337cb34d7a25fdd01c877402be5519b`. `wp db check` **PASS** (“Success: Database checked.”). PHP lint **PASS** for the changed store file and focused fixture. PHP PID `286942` listens on `127.0.0.1:8101`; MariaDB PID `286676` remained active. No service lifecycle change.

Round6 cleanup-failure acceptance is **PASS** on the exact candidate above. This is focused synthetic evidence only; it does not constitute whole-phase or release acceptance. Valid signature/counter, UI, and unrelated gates were not run here.
