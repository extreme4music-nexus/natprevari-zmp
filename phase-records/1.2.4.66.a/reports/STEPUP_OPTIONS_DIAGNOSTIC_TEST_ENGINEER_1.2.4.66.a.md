# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Task5 `step_up_options` denial diagnostic — 1.2.4.66.a

Date: 2026-11-06  
Role: Test Engineer  
Scope: one synthetic `approve_new` options request on the existing authorized local `.66.a` site. This is a separate diagnostic, not a matrix rerun.

## Exact candidate and fixture

Independent Code Review report: `reports/CODE_REVIEW_TASK5_SUBJECT_STEPUP_1.2.4.66.a.md`, SHA-256 `A0F815A61B32714C9C6DC7EF89A5A565836A61ADA519A7AF7366CE8120B95C62`, SPEC/QUALITY PASS (static only).

The four reviewed source/site hashes matched: passkeys PHP `F01D94425E06A7978F1FC1D721BFFE0B3404FB71069E7246BB91F10E0AE0B6E7`; President approvals PHP `7E8C431DA2CEEFEB09C91E23AB293260E959743765E35CE9B27A3BAEFE904021`; JS `49D7EC3947F3CF133DF1E54D9FD0B0B3D2E1810885CE94D5986D105C1A50F92B`; CSS `7B3C4B9D7AB98F05DE817E5D1356240AD3C92924B7CF6B04AC6D6AB4FE800F44`.

Diagnostic fixture `tests/phase66a-step-up-options-diagnostic.php`, SHA-256 `9E1E989B29C63B9051A5C6C79FC1461D38F2BF45488305116C636F5D77FA877A`; PHP lint PASS. It ran once using `wp --path=/home/tonic/zmp-local-sandbox/phase66a-test/site eval-file /mnt/z/Documents/Natprevari_ZMP/ZMP_PROJECT_LIBRARY/03_PHASES/zmp-competitions-1.2.4.66.a/tests/phase66a-step-up-options-diagnostic.php`.

## Sanitized preconditions and result

All requested boolean checks were `true`: exact President role; current capability; active mandate; `can_review`; current user matches; session token valid; route nonce valid; secure request context; configured-origin match; schema/library availability; exact synthetic credential row; exact credential-list count.

The single `step_up_options()` call returned `OPTIONS_ARRAY`. Scoped challenge count changed 0→1; stored metadata matched the fixture President, `step_up`, fixed action, exact proposal resource and `approve_new`; context hash shape was valid; challenge was unconsumed. Diagnostic stage: `OPTIONS_RETURNED`. The fixture printed no nonce, token, credential/assertion/challenge identifier, context digest, raw `WP_Error`, or personal data.

## Cleanup and environment

Fixture cleanup: PASS. Post-run `wp db check --silent`: Success. Counts (users/credentials/challenges/proposals/events/mandates/audit/subjects/aliases) restored exactly to `1/1/0/0/0/0/0/0/0`. Full credential fingerprint `511b7006cfa53e2bced9ef69b96331783af1472295afe6638d347235575afe6e` and protected credential digest `d610dfaf3460da32e95d2207add33bb903cafbc3dd40786e220c48b8a6f1affd` matched pre-run. Role-option SHA `514d4be7f0cc4a96f3e57bcd828c03c609212621a806fe1052e7e11fd6abe788`, debug-event SHA `48ab8d7c9c9e6899aa6c5b152a28441f55bd8086a5f29bce234ca5f704c34c68`, and absent debug-capture option matched preflight. Only plugin `zmp-competitions` remained installed/active. Exact four-file source/site parity PASS. Existing PHP PID 286942 remained bound to 127.0.0.1:8101 and MariaDB PID 286676 remained active; neither was stopped.

## Conclusion and limits

This diagnostic does **not identify the root cause** of the prior matrix failure. It proves one carefully isolated synthetic President/session/nonce/credential/proposal setup succeeds against the same reviewed candidate and that an options challenge can be created. The earlier matrix failure remains preserved as FAIL; no matrix rerun or Task4 check was done. The exact differing setup order/state has not been isolated. Concurrent challenge replay remains NOT RUN. No product code was changed, and this report is not Task5 runtime acceptance or phase acceptance.
