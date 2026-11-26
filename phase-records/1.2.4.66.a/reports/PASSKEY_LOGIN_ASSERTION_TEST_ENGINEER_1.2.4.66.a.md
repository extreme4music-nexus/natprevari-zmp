# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Test Engineer — Passkey assertion login (.66.a)

## Scope and candidate

This test-only record covers the anonymous `admin-ajax.php?action=zmp_passkey_login_finish` assertion endpoint. It verifies generic denial, no authenticated session, one-use challenge behavior, and preservation of synthetic credential records. The approved candidate is:

- `includes/class-zmp-passkeys.php`: `76CC60511EA21446E35D5C9BF4A2DFC1472F8094EBE1CB29F17ECD8677214FDE`
- `includes/class-zmp-passkey-store.php`: `E581879AD75DB716005E076A440F8C2732A1EE114D87F02BC4856DDE26C6E0A8`
- `zmp-competitions.php`: `15A87C3ECB1FFDFCACE9198D2F206060117396FA9C58FFD80F2A9A00D9CC9B93`
- New fixture: `tests/phase66a-passkey-login-assertion.php`, SHA-256 `E455DC6F3532B46FADA4A13FEB7E4B35E105FE14843356EF78F516F84A513089` (before runtime execution).

The fixture is a new test-only artifact. It does not edit or replace the existing passkey login-options fixture/report.

## Environment verified before sync

Observed site: `/home/tonic/zmp-local-sandbox/phase66a-test/site`; DB `zmp66a_isolated`; prefix `z66a_`; active plugin list contained only `zmp-competitions`. Counts were users 1, credentials 1, challenges 0, protected UI credential rows 1. PHP PID 286942 owned `127.0.0.1:8101`; MariaDB PID 286676 used the phase sandbox's private socket. Both were active. The site entrypoint hash before candidate sync was `BE6EBB302DFB0DFCE2D5DFE1D7FEE080F0B51030ACCB C B396A4C6B8C5BF4C47` (spaced here only to prevent accidental copy; exact hash is `be6ebb302dfb0dfc9e2d5dfe1d7fee080f0b51030accbcb396a4c6b8c5bf4c47`).

## Assertion fixture feasibility

The pinned dependency is `web-auth/webauthn-lib` 5.3.9. Its vendor tree contains the production `AuthenticatorAssertionResponseValidator` and response denormalizers; the inspected vendor tree did not expose a ready-made test credential/assertion fixture utility. COSE includes signing APIs, but producing a complete stored credential record and browser-format assertion would require bespoke key/credential generation. To avoid introducing custom cryptographic fixture/verification code, the fixture deliberately uses malformed synthetic assertion material to test rejection paths. Valid assertion acceptance and counter persistence are **NOT RUN** and remain an open acceptance item.

## Checks

| Check | Status | Evidence |
|---|---|---|
| Fixture syntax | NOT RUN | Awaiting authorized execution on the synced candidate. |
| Invalid assertion: generic 400 JSON, no account fields, no WP session, challenge consumed | NOT RUN | Fixture invokes the real `wp_ajax_nopriv_zmp_passkey_login_finish` hook. |
| Replayed challenge denied | NOT RUN | Same synthetic challenge is submitted a second time. |
| Wrong origin denied before consumption | NOT RUN | Same-origin headers are changed only in-memory for the fixture request. |
| Wrong ceremony denied | NOT RUN | Synthetic registration ceremony challenge is sent to login finish. |
| Expired challenge denied | NOT RUN | Only fixture-owned challenge expiry is backdated and then removed. |
| User, credential bytes, protected UI row and challenge count preserved after cleanup | NOT RUN | Fixture fingerprints all credential records and checks baseline counts/label row. |
| Valid cryptographic assertion and persisted counter | NOT RUN | No reusable pinned-library fixture was identified; no custom signature verification was written. |
| DB integrity / post-run environment parity | NOT RUN | Awaiting candidate test run. |

Exact intended command: `wp --path=/home/tonic/zmp-local-sandbox/phase66a-test/site eval-file /mnt/z/Documents/Natprevari_ZMP/ZMP_PROJECT_LIBRARY/03_PHASES/zmp-competitions-1.2.4.66.a/tests/phase66a-passkey-login-assertion.php`.

No test result or PASS is claimed in this pre-run section. Candidate synchronization and the assigned suite run are pending Lead-authorized execution.

## Current candidate run — 2026-11-05

The approved source files were synchronized to the verified synthetic site after candidate hashes matched the Lead's announcement. Site identity remained `/home/tonic/zmp-local-sandbox/phase66a-test/site`, DB `zmp66a_isolated`, prefix `z66a_`; only `zmp-competitions` was active. Exact source/site hashes matched after sync:

- `class-zmp-passkeys.php`: `76cc60511ea21446e35d5c9bf4a2dfc1472f8094ebe1cb29f17ecd8677214fde`
- `class-zmp-passkey-store.php`: `e581879ad75db716005e076a440f8c2732a1ee114d87f02bc4856dde26c6e0a8`
- `zmp-competitions.php`: `15a87c3ecb1ffdfcace9198d2f206060117396fa9c58ffd80f2a9a00d9cc9b93`

New fixture exact hash before and after its single run: `e455dc6f3532b46fada4a13feb7e4b35e105fe14843356ef78f516f84a513089`.

| Command/check | Outcome |
|---|---|
| `wp --path=/home/tonic/zmp-local-sandbox/phase66a-test/site eval-file /mnt/z/Documents/Natprevari_ZMP/ZMP_PROJECT_LIBRARY/03_PHASES/zmp-competitions-1.2.4.66.a/tests/phase66a-passkey-login-assertion.php` | **FAIL (3/12), exit 1**. Failed assertions: invalid assertion 400 JSON denial; invalid assertion consumed challenge; fixture could not create an anonymous `registration` ceremony challenge for wrong-ceremony test. Fixture printed `CLEANUP PASS`; all its owned challenge rows were removed. Wrong-origin denial assertion did not appear in failures. No per-response diagnostic was captured, so the first two failures do not establish which controller gate failed. Treat them as unresolved test evidence, not product PASS or a confirmed product defect. Wrong-ceremony case is a fixture setup limitation because the store rejected creating that synthetic binding. |
| Existing login-options fixture | **PASS (9), exit 0**, cleanup PASS. |
| Existing storage suite | **PASS (65), exit 0**. |
| Existing step-up context suite | **PASS (7), exit 0**, cleanup PASS. |
| Existing registration suite | **PASS (49), exit 0**. |
| Existing Task 3 boundary suite | **PASS (18), exit 0**. |
| PHP lint: new fixture and three synchronized PHP files | **PASS**, no syntax errors. |
| `wp --path=/home/tonic/zmp-local-sandbox/phase66a-test/site db check` | **PASS**, all reported tables `OK`; exit 0. |

Post-run observed counts: users 1, credentials 1, challenges 0, protected UI credential rows 1. Fixture reported its complete credential-record fingerprint and protected-row baseline unchanged. Services remained active: PHP PID 286942 on `127.0.0.1:8101`, MariaDB PID 286676. No service lifecycle changes were made. Site/source hashes still matched the three values above. The exact fixture command exited 1; each listed regression/lint/db command exited 0.

This run does not verify valid assertion acceptance or counter persistence; both remain **NOT RUN**. It is not a phase-level PASS.

## Bounded fixture correction and diagnostic — same candidate

The original `FAIL (3/12)` run is preserved above. Its invalid-assertion response and challenge-consumption failures were caused by the fixture sending `home_url('/')` as the `Origin` header, which contains a trailing slash. The WebAuthn request context requires an origin without a path. The fixture was corrected test-only to normalize `Origin` to `scheme://host[:port]`. Its wrong-ceremony test now mutates only its own login challenge row to `registration`, because storage correctly rejects creating an anonymous registration binding. Candidate product files and site configuration were unchanged.

Corrected fixture SHA-256: `c16d4596cb4a551b5d9d6f1dbea8b28baee025cfacf041f1667c0b07c5e7d1c`.

One bounded rerun: `wp --path=/home/tonic/zmp-local-sandbox/phase66a-test/site eval-file /mnt/z/Documents/Natprevari_ZMP/ZMP_PROJECT_LIBRARY/03_PHASES/zmp-competitions-1.2.4.66.a/tests/phase66a-passkey-login-assertion.php` — **FAIL (1/13), exit 1**. The JSON error response, no-session, consumed challenge, replay, wrong-origin, wrong-ceremony, and expiry assertions all passed; only the combined HTTP transport-status assertion failed. The fixture did not print the separate captured `status_header` and `wp_die` values, so this bounded run cannot identify which transport signal differed. This is unresolved test evidence, not a product PASS. Cleanup PASS; post-run users 1, credentials 1, challenges 0, protected UI rows 1; PHP/MariaDB services remained active.

Valid assertion acceptance/counter persistence remains **NOT RUN**. The original run remains historical evidence; the corrected run supersedes its fixture-input defect, not its source/candidate identity.

## P2 red checks — issue-time origin/RP binding and anonymous challenge volume

A separate test-only fixture was added after preserving the assertion-login run above: `tests/phase66a-login-context-rate.php`, SHA-256 `3135d84e27e37ed94a326d8b13de37e0a1457cdb292eaa6a7b8736b6d3ac1837`. It invokes the actual anonymous WordPress AJAX endpoints. Its explicit rate policy is at most five pending anonymous login challenges per verified source IP over the 300-second challenge TTL, tested with 12 requests from a synthetic loopback IP. Its cleanup removes only fixture-owned challenge hashes and the one exact IP-hashed transient key; it restores a pre-existing value if present.

The fixture also checks that login options persist a canonical SHA-256 of `{origin, rp_id}` in `context_hash`. For the config-drift check it changes only the fixture-owned challenge's stored context to another valid loopback origin/RP digest, while sending the finish request with the original configured same-origin headers. Thus the mismatch assertion is separate from the ordinary HTTP Origin check.

Exact command: `wp --path=/home/tonic/zmp-local-sandbox/phase66a-test/site eval-file /mnt/z/Documents/Natprevari_ZMP/ZMP_PROJECT_LIBRARY/03_PHASES/zmp-competitions-1.2.4.66.a/tests/phase66a-login-context-rate.php` — **FAIL (2/7), exit 1**. Failed checks: the 12-request batch exceeded the stated five-pending bound, and login options did not persist the expected issue-time context digest. The configured RP ID check passed. The separate same-origin stored-context mismatch checks passed: the mismatch received a generic error, no session was created, and the challenge stayed unconsumed. Fixture cleanup PASS.

PHP lint for this fixture: **PASS**, no syntax errors. `wp db check`: **PASS**, all tables `OK`, exit 0. After-run counts remained users 1, credentials 1, challenges 0, protected UI rows 1; the credential fingerprint stayed unchanged. PHP PID 286942 and MariaDB PID 286676 remained active; site/source parity for passkeys/store remained exact. No site configuration was changed.

The existing login-options fixture was updated test-only to expect `context_hash = sha256(canonical JSON {origin, rp_id})`, matching the new Task 4 login design. Its current hash is `458a7a7f55a30a686ac01a162b496e2b32d40b29cc2ccabeba4d68ec2aaaa9d6`. Its earlier `PASS (9)` is bound to the previous fixture hash `3c3ee20f8fcad5465fc00999a639943f16e8d8418d672ac32aa1203571a1be69` and the former empty-context criterion; that result remains preserved as historical evidence but does not verify the revised issue-time context requirement. The revised fixture was **NOT RUN** against this candidate.

Valid cryptographic assertion success and counter persistence remain **NOT RUN**. The new failures are concrete P2 gaps for backend follow-up; no phase PASS is claimed.

## Additive login-context fixture revision

The assertion-flow fixture now creates every fixture-owned anonymous login challenge with Backend's canonical origin/RP digest. The login-options fixture, assertion fixture, storage suite, step-up-context fixture, and origin/rate fixture use the same exact formula: `sha256("zmp-passkey-login-origin-v1\n" . canonicalOrigin . "\n" . rpId)`, with normalized scheme/host, omitted effective default port, and retained nondefault port. Registration still rejects a nonempty context digest; step-up action-context logic is unchanged. Previously recorded FAIL/PASS outcomes remain bound to their older exact fixture hashes and were not rewritten. The candidate is changing, so the updated tests were not executed; current old-source RED is **not safe to run** until Backend announces a stable hash and confirms site sync.

## Corrected candidate runtime round — 2026-11-05

Candidate source hashes matched the Lead-approved values before sync and after all checks: `class-zmp-passkeys.php` `9708137ce291d8d55a00b04e35b2f1b7cd4fdf69efa533fc41c4c8a1ab053925`; `class-zmp-passkey-store.php` `9aa2ace4b9932db8dd7d158bc22b003ab6d8f46d11b55add101798e54431f95d`; `zmp-competitions.php` `15a87c3ecb1ffdfcace9198d2f206060117396fa9c58ffd80f2a9a00d9cc9b93`. Only those three product files were synced to the already-authorized phase site. Site remained `/home/tonic/zmp-local-sandbox/phase66a-test/site`, DB `zmp66a_isolated`, prefix `z66a_`, active plugin list only `zmp-competitions`.

| Exact check | Outcome |
|---|---|
| Revised login-options fixture `phase66a-passkey-login.php` (SHA `e17944f50fe7a559458e529c5e70c6c2014998e0c7ae668184a247aaf65c3d01`) | **PASS (9), exit 0**; cleanup PASS; issue-time canonical origin/RP binding checked. |
| Assertion-denial fixture `phase66a-passkey-login-assertion.php` (SHA `1e56006329096bd8589c315f172198660c07fe659725e2ed9aa51e1d0cb9dfff`) | **FAIL (1/13), exit 1**. Sole failure: `invalid assertion returns HTTP 400 transport status`. Its generic JSON shape, no-session, one-use consumption/replay, wrong-origin, wrong-ceremony, expiry, and cleanup checks passed. Existing bounded diagnostics did not identify the captured status signal; no further harness diagnostic was performed. Valid assertion/counter persistence remains **NOT RUN**. |
| Origin/rate fixture `phase66a-login-context-rate.php` (SHA `c2d53c170d0b8b1448d7060931672ae368833c89b7f69470ef9dfd23e3669422`) | **PASS (7), exit 0**; cleanup PASS. Enforces issue-time context digest, config-drift denial before consume, and at most five pending anonymous challenges per IP over 300 seconds under 12 actual endpoint requests. |
| Storage fixture `phase66a-passkey-store.php` (SHA `7077084d58e2b226c12f70f5e6223ff53e9cc78dc12a691aa9410c931b8fdf91`) | **PASS (68), exit 0**; canonical login digest accepted, mismatched/missing/malformed shapes denied, registration empty-context policy and step-up semantics retained. |
| Step-up context fixture `phase66a-step-up-context.php` (SHA `373c3216f71a34751d4ae0a0a408908740b433e3b1302d7cb321b13eef4a1f68`) | **PASS (7), exit 0**, cleanup PASS; login shape updated while step-up checks unchanged. |
| Task 3 registration fixture `phase66a-passkey-registration.php` (SHA `7c041c1f6f5c269fb87cdf84d08ef9949d6ce92b52f0b6a65b77bd80fa9c5959`) | **PASS (49), exit 0**. |
| Task 3 boundary fixture `phase66a-passkey-review-round2.php` (SHA `1dc01d8a4ad82e6db136404154c71640afd37cf9e771e425cba7a8a9c19d4bfa`) | **PASS (18), exit 0**. |
| PHP lint: all five revised fixtures, both changed product classes and plugin entrypoint | **PASS**, no syntax errors (each command exit 0). |
| `wp --path=/home/tonic/zmp-local-sandbox/phase66a-test/site db check` | **PASS**, all reported tables `OK`, exit 0. |

After-run baseline: users 1, credentials 1, challenges 0, protected UI credential rows 1. Fixtures with explicit cleanup output reported PASS; aggregate pre/post counts and credential fingerprints remained unchanged. The protected synthetic credential was preserved. PHP PID 286942 remained on 127.0.0.1:8101 and MariaDB PID 286676 remained active. Site/source parity matched all three candidate hashes above. No services or site configuration were changed.

This scoped run is not a phase-level PASS. The assertion transport-status check remains FAIL/unresolved; valid cryptographic assertion and counter persistence remain NOT RUN.


## Assertion transport assertion correction — 2026-11-05

The preceding table records **FAIL (1/13)** because the fixture incorrectly required both the HTTP status header and the subsequent `wp_die` response argument to equal 400. Local WordPress core `wp-includes/functions.php` confirms `wp_send_json()` calls `status_header($status_code)` when given 400, then terminates AJAX with `wp_die(..., ['response' => null])`. The test-only assertion was corrected to check the actual HTTP status header only; product source was unchanged.

Corrected assertion fixture SHA-256: `9a12d939290560f0afb19972c7f9510d0b8ed5e67c7e4c7fed80cf2494281d00`. One targeted rerun on the same exact candidate returned **PASS (13), exit 0**; cleanup PASS. Generic JSON error, fixed message shape, no account fields/session, challenge consumption/replay, wrong-origin, wrong-ceremony and expiry checks all passed. This supersedes the fixture's over-constrained transport assertion only; the earlier exact-hash failed run is retained as historical evidence. Valid cryptographic assertion/counter persistence remain **NOT RUN**.

Post-rerun baseline: users 1, credentials 1, challenges 0, protected UI credential rows 1. Candidate/site parity remained exact for passkeys `9708137ce291d8d55a00b04e35b2f1b7cd4fdf69efa533fc41c4c8a1ab053925`, store `9aa2ace4b9932db8dd7d158bc22b003ab6d8f46d11b55add101798e54431f95d`, entrypoint `15a87c3ecb1ffdfcace9198d2f206060117396fa9c58ffd80f2a9a00d9cc9b93`. PHP PID 286942 and MariaDB PID 286676 remained active.

## Churn-fix candidate affected-flow rerun — 2026-11-05

The passkeys class changed to `baa1d3d6cf27c7a06ba44a6adc5fca73aad2e61ac5bf9668de4ab6d938c14f9a`; store and entrypoint remain `9aa2ace4b9932db8dd7d158bc22b003ab6d8f46d11b55add101798e54431f95d` and `15a87c3ecb1ffdfcace9198d2f206060117396fa9c58ffd80f2a9a00d9cc9b93`. On that exact candidate, assertion fixture SHA `9a12d939290560f0afb19972c7f9510d0b8ed5e67c7e4c7fed80cf2494281d00` returned **PASS (13), exit 0**, and origin/rate fixture SHA `c2d53c170d0b8b1448d7060931672ae368833c89b7f69470ef9dfd23e3669422` returned **PASS (7), exit 0**; both cleanup checks passed. Valid cryptographic assertion success and counter persistence remain **NOT RUN**.
