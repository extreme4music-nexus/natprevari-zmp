# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Task5 matrix-order options diagnostic — 1.2.4.66.a

Date: 2026-11-06  
Role: Test Engineer  
Scope: reproduce only the first `approve_new` `step_up_options()` call with the matrix's setup/order. This is not a full matrix rerun.

## Exact candidate and diagnostic

The four reviewed candidate files remained at the independent Code Review PASS hashes: passkeys `F01D94425E06A7978F1FC1D721BFFE0B3404FB71069E7246BB91F10E0AE0B6E7`; approvals `7E8C431DA2CEEFEB09C91E23AB293260E959743765E35CE9B27A3BAEFE904021`; JS `49D7EC3947F3CF133DF1E54D9FD0B0B3D2E1810885CE94D5986D105C1A50F92B`; CSS `7B3C4B9D7AB98F05DE817E5D1356240AD3C92924B7CF6B04AC6D6AB4FE800F44`.

Fixture `tests/phase66a-step-up-options-matrix-order-diagnostic.php`, SHA-256 `707081E0C93F46A52879B73E24A87813288B5D97601F3CA57670474A8CAD423A`; PHP lint PASS. It follows the matrix sequence through exactly one options call: President/active mandate/session, user handle before credential, seven pending synthetic proposals, two canonical subjects, President request context, valid route nonce, one `approve_new` options request.

## One-run outcome

The diagnostic setup difference **did not reproduce the failure**. All sanitized prerequisite booleans were true: exact President role, current capability, active mandate, `can_review`, current user, valid session token, valid route nonce, secure loopback context, configured-origin match, schema/library availability, exact synthetic credential row and credential-list count.

The single call returned `OPTIONS_ARRAY`; the challenge count scoped to that synthetic President/proposal/action/decision changed 0→1; stored metadata matched; context hash had the expected format; challenge remained unconsumed before cleanup. No nonce, token, credential/assertion/challenge identifier, raw error, or personal data was emitted.

## Cleanup and exact state

Fixture cleanup PASS. Post-run `wp db check --silent`: Success. Counts (users/credentials/challenges/proposals/events/mandates/audit/subjects/aliases) restored exactly to `1/1/0/0/0/0/0/0/0`. Full credential fingerprint `511b7006cfa53e2bced9ef69b96331783af1472295afe6638d347235575afe6e`; protected credential digest `d610dfaf3460da32e95d2207add33bb903cafbc3dd40786e220c48b8a6f1affd`; role option SHA `514d4be7f0cc4a96f3e57bcd828c03c609212621a806fe1052e7e11fd6abe788`; debug events SHA `48ab8d7c9c9e6899aa6c5b152a28441f55bd8086a5f29bce234ca5f704c34c68`; debug-capture remained absent. Only `zmp-competitions` remained active. Reviewed source/site parity stayed exact. PHP286942 remained on 127.0.0.1:8101; MariaDB286676 remained active.

## Conclusion

This matrix-order setup does **not** explain the earlier matrix failure. Root cause remains **NOT IDENTIFIED**. The original matrix runtime FAIL is preserved and was not rerun. This one options success is not full Task5 or UI acceptance. Concurrent replay and Task4 checks remain NOT RUN.

## Sanitized error-category capture update — 2026-11-06
The backend candidate now returns fixed internal codes for step-up options failures. This diagnostic fixture was updated to capture only `WP_Error::get_error_code()` when it matches the current source allowlist: `step_up_context_invalid`, `step_up_nonce_invalid`, `step_up_authority_session_unavailable`, `step_up_secure_origin_invalid`, `step_up_store_schema_unavailable`, `step_up_library_unavailable`, `step_up_credentials_unavailable`, `step_up_challenge_insert_failed`, `step_up_options_serialization_invalid`, or `step_up_options_generation_failed`. A non-string or unrecognized code is rendered only as `UNEXPECTED_CODE`. The fixture never reads or prints an error message or raw error data.

Current backend candidate `includes/class-zmp-passkeys.php` SHA-256: `F233CB371DCDEF3A05F447C3E5081F4B7F4DE453FE2BFBE2470B447847E68ED7`. Updated fixture `tests/phase66a-step-up-options-matrix-order-diagnostic.php` SHA-256: `D22CB7F26B0E301CB6778FEE5296AB7A460302F6F0424BCC03644AE03F17D8D1`; `php -l` PASS. No site sync or runtime execution occurred. The earlier diagnostic PASS applies only to the earlier fixture hash `707081E0C93F46A52879B73E24A87813288B5D97601F3CA57670474A8CAD423A` and earlier passkeys hash `F01D94425E06A7978F1FC1D721BFFE0B3404FB71069E7246BB91F10E0AE0B6E7`; it does not establish a result for the updated candidate.

## Updated-candidate one-run result — 2026-11-06
- Exact reviewed candidate passkeys SHA-256 `F233CB371DCDEF3A05F447C3E5081F4B7F4DE453FE2BFBE2470B447847E68ED7`; the only synced site PHP file has the same hash. Other three reviewed files remained source/site-identical at approvals `7E8C431DA2CEEFEB09C91E23AB293260E959743765E35CE9B27A3BAEFE904021`, JS `49D7EC3947F3CF133DF1E54D9FD0B0B3D2E1810885CE94D5986D105C1A50F92B`, CSS `7B3C4B9D7AB98F05DE817E5D1356240AD3C92924B7CF6B04AC6D6AB4FE800F44`.
- Updated fixture `tests/phase66a-step-up-options-matrix-order-diagnostic.php` SHA-256 `D22CB7F26B0E301CB6778FEE5296AB7A460302F6F0424BCC03644AE03F17D8D1`; PHP lint PASS. Independent delta review PASS report SHA `AEDAA5940C5E3F3C8389F1A368007DE3CD865757643D388E358C9A0D8B4F8BC2`.
- One diagnostic invocation: all 12 sanitized prerequisites true; **PASS** `OPTIONS_ARRAY`, `error_code_category=NONE`, scoped challenges 0→1 with expected metadata and unconsumed state before cleanup. No error message/raw error/secrets emitted.
- Cleanup PASS; post-run `wp db check --silent` PASS; exact counts users/credentials/challenges/proposals/events/mandates/audit/subjects/aliases restored `1/1/0/0/0/0/0/0/0`; only plugin active; PHP286942 and MariaDB286676 preserved.
- This is only the focused diagnostic, not Task5 acceptance. Original matrix FAIL and positive-route FAIL remain preserved; their causes and actual routes remain unresolved/unreached. Concurrent replay/interleaving NOT RUN. No additional runtime tests.
