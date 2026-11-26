# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Task4 passkey regression checks — Test Engineer — 2026-11-06

## Scope and environment
- Ran only the established anonymous login-options and valid synthetic ES256 assertion/session/counter fixtures, once each, on `/home/tonic/zmp-local-sandbox/phase66a-test/site` (`http://127.0.0.1:8101`), DB `zmp66a_isolated`, prefix `z66a_`.
- Preflight: only `zmp-competitions` 1.2.4.66.a active; passkey schema marker 3, mandate schema marker 2; baseline counts users/credentials/challenges/proposals/proposal-events/mandates/subjects/aliases `1/1/0/0/0/0/0/0`; `wp db check` PASS. Existing PHP 286942 on loopback port 8101 and MariaDB 286676 remained active.
- Both fixtures linted without syntax errors. Login fixture SHA-256 `982F3B0685480A91CF640BB4533DB8D287B25157ACE11AF2184DFA075E2F0C1D`; valid assertion fixture SHA-256 `F2C3F9FFBB008C095A4C7493F88161AF2A5CCABC35C54C772B3D2D116028707B`.

## Results
- **PASS** — `phase66a-passkey-login.php`: `PASS (9): anonymous passkey login options and one-use challenge`; exact cleanup confirmed synthetic baselines and protected UI row unchanged.
- **PASS** — After intervening DB check and baseline counts matched, `phase66a-passkey-valid-login.php`: `PASS (17): valid passkey login, session and counter`; fixture reported cleanup, baseline/fingerprint restoration, session logout/revocation, and that assertion/cookie material was not printed.
- **PASS** — Post-run `wp db check --silent`; baseline counts remained `1/1/0/0/0/0/0/0`; sole plugin and protected synthetic credential state remained. PHP 286942 and MariaDB 286676 preserved.
- **PASS** — Exact source/site parity for all four reviewed candidate files: `class-zmp-passkeys.php` `F01D94425E06A7978F1FC1D721BFFE0B3404FB71069E7246BB91F10E0AE0B6E7`; `class-zmp-president-subject-approvals.php` `7E8C431DA2CEEFEB09C91E23AB293260E959743765E35CE9B27A3BAEFE904021`; step-up JS `49D7EC3947F3CF133DF1E54D9FD0B0B3D2E1810885CE94D5986D105C1A50F92B`; step-up CSS `7B3C4B9D7AB98F05DE817E5D1356240AD3C92924B7CF6B04AC6D6AB4FE800F44`.

## Limitations preserved
- These focused Task4 regressions do not resolve or change the separate original Task5 matrix **FAIL**; its root cause remains unresolved. Concurrent replay remains **NOT RUN**. No UI/browser or package/GitHub checks were performed. No product or site source files were edited.
