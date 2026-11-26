# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Test Engineer — Passkey login issuance churn (.66.a)

## Acceptance and scope

This focused synthetic check exercises the actual anonymous WordPress AJAX options and finish endpoints. For one synthetic loopback peer with matching same-origin headers, it requests login options and immediately submits an invalid assertion to consume each returned challenge, repeated for 12 attempts. Acceptance requires at most five accepted options/challenges over the 300-second challenge window even after prior challenges have been consumed, and later options denial must match the invalid-finish generic JSON/status shape. No product source is changed by this task.

The fixture cleans only challenge hashes it created, the exact transient key for its synthetic peer, and restores captured request globals/current user. It checks users, credential-row fingerprint and the protected `Synthetic UI review only` credential before/after.

## Candidate and environment preflight

- `includes/class-zmp-passkeys.php`: `9708137CE291D8D55A00B04E35B2F1B7CD4FDF69EFA533FC41C4C8A1AB053925`
- `includes/class-zmp-passkey-store.php`: `9AA2ACE4B9932DB8DD7D158BC22B003AB6D8F46D11B55ADD101798E54431F95D`
- `zmp-competitions.php`: `15A87C3ECB1FFDFCACE9198D2F206060117396FA9C58FFD80F2A9A00D9CC9B93`
- Fixture: `tests/phase66a-login-issuance-churn.php` SHA-256 `47C5935EAB4BF9D66C27EC7FE25243AE5A582D10130775063740591D976E51FD` before run.
- Site `/home/tonic/zmp-local-sandbox/phase66a-test/site`; DB `zmp66a_isolated`; prefix `z66a_`; active plugin only `zmp-competitions`.
- Baseline users 1, credentials 1, challenges 0, protected UI row 1.
- PHP PID 286942 owns `127.0.0.1:8101`; MariaDB PID 286676 remains active on the phase sandbox socket.

Exact command: `wp --path=/home/tonic/zmp-local-sandbox/phase66a-test/site eval-file /mnt/z/Documents/Natprevari_ZMP/ZMP_PROJECT_LIBRARY/03_PHASES/zmp-competitions-1.2.4.66.a/tests/phase66a-login-issuance-churn.php`.

## Result

NOT RUN at report creation. Append the bounded candidate outcome and final hashes after the single assigned execution. This is a focused Task 4 result only, never a phase-level PASS.

## Bounded current-candidate run — 2026-11-05

The exact command above returned **FAIL (3/9), exit 1**, with sanitized observation `accepted_options=12; owned_challenges=12; limit_denials=0`. All 12 options requests used the same validated loopback peer and exact configured same-origin headers. Each returned challenge was submitted once to the real finish endpoint with synthetic invalid assertion data; each was rejected generically and consumed. The authenticated-session assertion passed. This reproduces the issuance-churn gap: the current limit tracks only pending challenges, so consuming each challenge frees capacity and does not cap total issuance within 300 seconds.

Failed checks were exactly: (1) total accepted options/challenges exceeded five after earlier challenges were consumed; (2) no request after the issuance limit received denial; (3) there was no later options denial to compare with the invalid-finish generic response. The test did not report raw challenge IDs or credential material.

Fixture SHA-256 at run: `47c5935eab4bf9d66c27ec7fe25243ae5a582d10130775063740591d976e51fd`. PHP lint: **PASS**, no syntax errors. Cleanup printed PASS. Post-run users 1, credentials 1, challenges 0, protected UI row 1; credential fingerprint unchanged. A separate exact transient lookup returned `RATE_TRANSIENT_CLEAN` for the fixture peer. Request globals/current user were restored. PHP PID 286942 and MariaDB PID 286676 remained active.

Post-run source/site parity remained exact: passkeys `9708137ce291d8d55a00b04e35b2f1b7cd4fdf69efa533fc41c4c8a1ab053925`, store `9aa2ace4b9932db8dd7d158bc22b003ab6d8f46d11b55add101798e54431f95d`, entrypoint `15a87c3ecb1ffdfcace9198d2f206060117396fa9c58ffd80f2a9a00d9cc9b93`. No product files, site configuration, unrelated rows, or services were changed. This is a focused FAIL for the reviewer P2, not a phase-level result.

## Corrected churn-fix candidate — 2026-11-05

Candidate source: passkeys `baa1d3d6cf27c7a06ba44a6adc5fca73aad2e61ac5bf9668de4ab6d938c14f9a`; unchanged store `9aa2ace4b9932db8dd7d158bc22b003ab6d8f46d11b55add101798e54431f95d`; entrypoint `15a87c3ecb1ffdfcace9198d2f206060117396fa9c58ffd80f2a9a00d9cc9b93`. Only the passkeys PHP source was synchronized for this round. Site was `/home/tonic/zmp-local-sandbox/phase66a-test/site`, DB `zmp66a_isolated`, prefix `z66a_`, only `zmp-competitions` active.

| Check | Outcome |
|---|---|
| Churn fixture `phase66a-login-issuance-churn.php` SHA `47c5935eab4bf9d66c27ec7fe25243ae5a582d10130775063740591d976e51fd` | **PASS (9), exit 0**. Observed 5 accepted options/challenges and one later denial across 12 cycles. Each accepted challenge was consumed by invalid finish; the limit still held. Later denial matched the generic finish denial. Cleanup PASS. |
| Login-options fixture SHA `e17944f50fe7a559458e529c5e70c6c2014998e0c7ae668184a247aaf65c3d01` | **PASS (9), exit 0**, cleanup PASS. |
| Assertion-denial fixture SHA `9a12d939290560f0afb19972c7f9510d0b8ed5e67c7e4c7fed80cf2494281d00` | **PASS (13), exit 0**, cleanup PASS. Valid assertion/counter persistence remains **NOT RUN**. |
| Origin/rate fixture SHA `c2d53c170d0b8b1448d7060931672ae368833c89b7f69470ef9dfd23e3669422` | **PASS (7), exit 0**, cleanup PASS. |
| Changed source PHP lint | **PASS**, no syntax errors, exit 0. |
| `wp --path=/home/tonic/zmp-local-sandbox/phase66a-test/site db check` | **PASS**, all tables `OK`, exit 0. |

Post-run baseline: users 1, credentials 1, challenges 0, protected UI row 1; credential fingerprints unchanged. Services remained active (PHP PID 286942 on 8101, MariaDB PID 286676). Source/site parity matched all three hashes above. No storage/context/registration/boundary suites were rerun because their tested source paths and configuration were unchanged; their earlier scoped PASS evidence remains tied to its exact prior hashes. No phase-level PASS is claimed.
