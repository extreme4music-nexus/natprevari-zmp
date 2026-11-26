# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Test Engineer — Passkey quota transient eviction (.66.a)

## Acceptance and scope

Focused expected-RED check for the current transient-backed per-peer passkey issuance quota. Using a single synthetic loopback IP and exact same-origin headers, the fixture requests five options, submits invalid finishes to consume those challenges, deletes only the transient for that exact hashed peer to simulate cache eviction, then requests a sixth option. A durable quota must deny the sixth generically despite transient loss. The fixture restores captured request globals/current user and only its own challenge rows and peer transient. No product source is changed.

## Candidate/environment preflight

- Passkeys PHP: `BAA1D3D6CF27C7A06BA44A6ADC5FCA73AAD2E61AC5BF9668DE4AB6D938C14F9A`
- Store PHP: `9AA2ACE4B9932DB8DD7D158BC22B003AB6D8F46D11B55ADD101798E54431F95D`
- Entrypoint: `15A87C3ECB1FFDFCACE9198D2F206060117396FA9C58FFD80F2A9A00D9CC9B93`
- Site `/home/tonic/zmp-local-sandbox/phase66a-test/site`; DB `zmp66a_isolated`; prefix `z66a_`; active plugin only `zmp-competitions`.
- Baseline users 1, credentials 1, challenges 0, protected UI row 1. PHP PID 286942 owns `127.0.0.1:8101`; MariaDB PID 286676 remains active.
- Fixture `tests/phase66a-login-transient-eviction.php` SHA-256 `20395AF7B3B459809BB3243BB662DB43CC454F6D680D5E03E6B2DCAE0EBCC8EF` before run.

Exact command: `wp --path=/home/tonic/zmp-local-sandbox/phase66a-test/site eval-file /mnt/z/Documents/Natprevari_ZMP/ZMP_PROJECT_LIBRARY/03_PHASES/zmp-competitions-1.2.4.66.a/tests/phase66a-login-transient-eviction.php`.

## Result

NOT RUN at report creation; append the single bounded expected-RED result and exact final hashes after execution. This is a focused Task 4 P2 check, not phase acceptance.

## Bounded current-candidate run — 2026-11-05

The exact command returned expected **FAIL (1/5), exit 1**. Sanitized observation: `initial_options=5; consumed=5; transient_before_eviction=present; sixth_options_success=true`. The first five exact same-origin login-options requests succeeded; each invalid finish returned the fixed generic denial and consumed its challenge. The fixture then deleted only the transient for its synthetic peer and the sixth options request succeeded, failing the durable-quota assertion. This reproduces the round-3 cache-eviction P2 on the unchanged candidate.

Fixture PHP lint: **PASS**, no syntax errors. Cleanup PASS. Post-run users 1, credentials 1, challenges 0, protected UI row 1; credential fingerprint unchanged. Request globals/current user restored; only owned challenge hashes and exact peer transient were cleaned/restored. PHP PID 286942 and MariaDB PID 286676 remained active. Source/site parity remained exact: passkeys `baa1d3d6cf27c7a06ba44a6adc5fca73aad2e61ac5bf9668de4ab6d938c14f9a`, store `9aa2ace4b9932db8dd7d158bc22b003ab6d8f46d11b55add101798e54431f95d`, entrypoint `15a87c3ecb1ffdfcace9198d2f206060117396fa9c58ffd80f2a9a00d9cc9b93`. No product files or site settings changed. This is a focused expected RED, not phase acceptance.
