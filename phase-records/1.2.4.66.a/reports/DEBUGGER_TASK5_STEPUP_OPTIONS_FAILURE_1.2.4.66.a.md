# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Debugger report — Task 5 step-up options issuance failure — 1.2.4.66.a

Date: 2026-11-06  
Scope: read-only diagnosis of the one bounded Task5 synthetic matrix run. No source, fixture, site, database, browser, service, or ledger files were changed by the diagnosis before this report.

## Exact evidence examined

| Item | SHA-256 |
|---|---|
| `includes/class-zmp-passkeys.php` | `F01D94425E06A7978F1FC1D721BFFE0B3404FB71069E7246BB91F10E0AE0B6E7` |
| `includes/class-zmp-president-subject-approvals.php` | `7E8C431DA2CEEFEB09C91E23AB293260E959743765E35CE9B27A3BAEFE904021` |
| `assets/js/zmp-president-subject-stepup.js` | `49D7EC3947F3CF133DF1E54D9FD0B0B3D2E1810885CE94D5986D105C1A50F92B` |
| `assets/css/zmp-president-subject-stepup.css` | `7B3C4B9D7AB98F05DE817E5D1356240AD3C92924B7CF6B04AC6D6AB4FE800F44` |
| Matrix fixture | `452005C5CF306D0FF2A927169562C82E4DCE49F021371E2DCB058B2DB27528B5` |
| Test Engineer runtime report | `F078670A8CFC3655C184AE0B919A569D389F92065FF50C7759A86EB663772B29` |
| Independent Code Review | `A0F815A61B32714C9C6DC7EF89A5A565836A61ADA519A7AF7366CE8120B95C62` |

The exact-hash Code Review verdict is SPEC PASS / QUALITY PASS for static source scope. It did not execute runtime checks.

## Observed failure

The single bounded matrix run exited 1 with **FAIL (2/2)** at the first valid `approve_new` case. The fixture called `ZMP_Passkeys::step_up_options(...)`; it received an error before invoking the President decision route. The failed assertion was “step-up options issued for approve_new”; execution then stopped at “valid new-subject challenge unavailable.” This is an executed failure, not an expected RED.

The Test Engineer reported cleanup PASS, `wp db check --silent` Success, restored exact baseline counts (users 1, credentials 1, challenges/proposals/events/mandates/audit/subjects/aliases 0), unchanged full credential fingerprint and protected credential digest, unchanged role/debug state, four-file source/site parity PASS, only the ZMP plugin installed/active, and preserved PHP/MariaDB processes. Concurrent replay and affected Task4 login rechecks were NOT RUN. No retry occurred.

## Diagnosis

**Root cause remains unresolved: the current evidence does not distinguish a product defect from a fixture/runtime precondition mismatch.** The fixture helper reports only `is_wp_error()` and does not emit the error code or record which guard failed. The production method returns the same generic error for multiple prerequisites, so the one run cannot identify the failed condition.

At `ZMP_Passkeys::step_up_options()` (candidate source around lines 22–57), issuance can fail at any of these grouped checks:

- canonical user/resource/action/decision/subject-target validation;
- route nonce verification;
- current logged-in user/session equality and active-President role/capability/mandate checks;
- request origin/host/scheme/port validation;
- passkey-store schema compatibility and WebAuthn library availability;
- an eligible stored credential for that President;
- challenge insertion, serialization, or the returned WebAuthn options’ challenge/RP/user-verification shape.

The fixture invokes the API directly after setting its President session, nonce, and request globals. Static inspection shows `approve_new` with canonical ID `0` is accepted by the context validator, so that decision-target rule alone does not explain the failure. However, no per-gate runtime evidence exists to eliminate the other checks or a mismatch in fixture state. The authorized run’s successful setup/cleanup proves the fixture could create and clean synthetic records, not that every step-up precondition was valid at issuance time.

## Safe next action

Keep this failure and cleanup PASS intact. The responsible Backend Developer should perform a read-only, code-path diagnosis of each prerequisite against the recorded fixture/setup/report and identify the specific failing gate before changing source or fixture. Do not repeat the unchanged matrix or weaken the generic external error. If a concrete product or fixture correction is supported, freeze new exact hashes, obtain independent review, then authorize one affected bounded synthetic rerun with the same preflight and cleanup safeguards. Keep Task5 runtime FAIL pending that evidence; phase `.66.a` remains IN PROGRESS.

## Follow-up comparison — matrix failure vs isolated options diagnostic — 2026-11-06

Additional exact evidence compared read-only:

| Item | SHA-256 |
|---|---|
| Isolated diagnostic fixture `tests/phase66a-step-up-options-diagnostic.php` | `9E1E989B29C63B9051A5C6C79FC1461D38F2BF45488305116C636F5D77FA877A` |
| Isolated diagnostic report `reports/STEPUP_OPTIONS_DIAGNOSTIC_TEST_ENGINEER_1.2.4.66.a.md` | `1A530F8EE3E33130AA6DD1E52928ADB61E22CEE3742EC9A501457C6103573B4A` |
| Failed matrix fixture | `452005C5CF306D0FF2A927169562C82E4DCE49F021371E2DCB058B2DB27528B5` |
| Failed matrix report | `F078670A8CFC3655C184AE0B919A569D389F92065FF50C7759A86EB663772B29` |
| Reviewed passkeys implementation | `F01D94425E06A7978F1FC1D721BFFE0B3404FB71069E7246BB91F10E0AE0B6E7` |

### Shared setup and result

Both fixtures create a synthetic Teacher and President; appoint the President through `ZMP_President_Mandate::appoint`; establish the President as current user with a synthetic WordPress session/cookies; create an ephemeral P-256 credential; create a uniquely named pending `approve_new` proposal; generate the route nonce; configure loopback request context; and call the same `ZMP_Passkeys::step_up_options()` with `approve_new`, canonical target `0`, and that nonce. The matrix invokes it through its `$issue` closure; the diagnostic calls it directly. Both used the same reviewed source/site hashes.

The isolated diagnostic additionally captured 12 sanitized precondition booleans immediately before the call; all were true (President role/capability/active mandate/`can_review`, current user and valid session, valid nonce, secure request/origin, schema/library, exact credential row/list). It observed `OPTIONS_ARRAY`, one correctly bound unconsumed challenge created, and exact cleanup. The matrix recorded no such per-gate booleans and recorded only a generic `WP_Error` from its first options call. Therefore the diagnostic proves options issuance can succeed under this exact source with an isolated approve_new setup, but it does not reveal what differed at the matrix call.

### Observed setup/order differences

- The matrix creates seven pending proposals and two approved canonical subjects before its first options request. The diagnostic creates one pending proposal and no catalog subjects.
- The matrix writes the synthetic user-handle meta before storing the credential; the diagnostic stores the credential and then writes the same meta.
- The diagnostic explicitly checks all prerequisite booleans and calls `list_credentials_for_user()` immediately before options. The matrix does not capture those values at its call site, though `step_up_options()` itself evaluates the prerequisites.
- Both reset the current user to the President, set the request context, and generate the President action nonce before calling options. The compared setup code shows no explicit difference in assertion inputs/action/target/nonce argument; both calls supply the same shape of values.

Static inspection of the proposal submit and approved-subject creation paths shows those calls operate on their respective proposal/catalog rows and do not intentionally change the current user, session token, or request globals. The options method does not read proposal or subject rows, so the extra proposal/catalog rows alone are not a demonstrated cause. The credential-handle ordering difference also is not a demonstrated cause: the diagnostic's final credential list contained exactly one usable credential, while the matrix storage call succeeded and its post-run cleanup restored the credential fingerprint.

## Updated diagnosis

**Ambiguity remains.** The isolated diagnostic eliminates an unconditional `approve_new` rejection in the reviewed code, and static validation accepts target zero. No exact fixture/order difference is proven to trigger the matrix failure. Because the matrix only persisted a generic error and did not snapshot the individual prerequisites at the failed call, we cannot conclusively classify the earlier result as product defect or fixture/runtime mismatch. The matrix's FAIL remains valid and unchanged; the diagnostic is separate positive evidence, not a replacement or rerun.

## Next safe step

Preserve both results. If further diagnosis is approved, add bounded sanitized per-gate snapshots around the matrix's first options call, with no secret values or raw error, then obtain exact-hash review and run only that diagnostic fixture once under the same exact preflight/cleanup controls. Do not rerun the unchanged matrix or infer that it failed only because it created extra proposals/subjects.

## Second follow-up comparison — positive-routes failure vs successful matrix-order diagnostic — 2026-11-06

Read-only evidence compared:

| Item | SHA-256 |
|---|---|
| Positive-routes fixture | `622197A685DEF5BBFBEE84116F43B2A906A1640372043DF057E391D9BD517E53` |
| Positive-routes report | `BA0341A5E739450AA1858393D6474CD6B30EB016949931360F0C14849052AC9B` |
| Matrix-order diagnostic fixture | `707081E0C93F46A52879B73E24A87813288B5D97601F3CA57670474A8CAD423A` |
| Matrix-order diagnostic report | `58614EEE613BDED197DBB0FB83F4AE4DB95CB4CA9AA0757192B295FCEB1D2045` |
| Earlier isolated options diagnostic fixture | `9E1E989B29C63B9051A5C6C79FC1461D38F2BF45488305116C636F5D77FA877A` |
| Earlier isolated options diagnostic report | `1A530F8EE3E33130AA6DD1E52928ADB61E22CEE3742EC9A501457C6103573B4A` |

All used the same reviewed product candidate: passkeys `F01D94425E06A7978F1FC1D721BFFE0B3404FB71069E7246BB91F10E0AE0B6E7`, approvals `7E8C431DA2CEEFEB09C91E23AB293260E959743765E35CE9B27A3BAEFE904021`, JS `49D7EC3947F3CF133DF1E54D9FD0B0B3D2E1810885CE94D5986D105C1A50F92B`, CSS `7B3C4B9D7AB98F05DE817E5D1356240AD3C92924B7CF6B04AC6D6AB4FE800F44`.

### Setup and API comparison

The failed positive-routes fixture uses a session helper that sets current President and calls `wp_set_auth_cookie`, captures auth/logged-in cookies, and restores those cookies into `$_COOKIE`; the successful matrix-order diagnostic performs the same hook capture and cookie assignment explicitly. Both set current user back to President after creating proposal/catalog rows, set loopback `HTTP_HOST`, request scheme/port, origin/referer, peer and method, create a President action nonce, verify it, then call `step_up_options()` directly with `president_subject_decision`, a positive proposal ID, `approve_new`, canonical target `0`, and the route nonce. No fixture invokes AJAX for this failing first call; the successful diagnostics also use the direct API, so AJAX vs direct transport does not explain the result.

Credential setup is also materially aligned: all three use an in-memory P-256 credential and handle, call `ZMP_Passkey_Store::save_credential()` and require a truthy return. The positive-routes fixture and matrix write user-handle meta before `save_credential`; both diagnostics do the same in the matrix-order fixture, while the earliest isolated diagnostic writes the handle after saving. Therefore handle/save ordering is not a supported explanation. Credential labels differ by fixture only; labels do not enter the options method’s issuance checks or challenge context. The earlier diagnostic separately observed one exact usable credential in `list_credentials_for_user()`.

The positive-routes fixture creates three pending proposals and two canonical subjects. The successful matrix-order diagnostic creates the same seven named matrix proposals and two canonical subjects in the same order as the failed original matrix; the earlier diagnostic creates only one proposal and no subjects. Consequently, extra proposal/catalog setup is not sufficient to reproduce the failure and is not a supported cause.

Request globals also align: both failing and successful matrix-order setups set HTTP host, request scheme, port, HTTPS state, Origin, Referer, remote address and POST method before setup and reset them before nonce/options. The positive-routes fixture additionally preserves `PHP_SELF` and `SCRIPT_NAME` for cleanup but does not alter them before the API call. The step-up options path checks secure request/origin/host/port state and does not read those two saved values.

### Results and limits

The positive-routes attempt stopped before its first actual admin-post route because the `approve_new` options call returned an error. The successful matrix-order diagnostic captured 12/12 prerequisite booleans true and returned `OPTIONS_ARRAY`, creating one scoped unconsumed challenge which it then cleaned. The earlier isolated diagnostic also returned `OPTIONS_ARRAY`. Each diagnostic is a distinct positive run, not a rerun or replacement of the failed matrix/positive-routes outcomes.

**No concrete setup difference found supports attribution. Root cause remains unresolved.** The most apparent differences—session helper spelling, handle/save order, label, number of proposals/subjects, and API transport—are contradicted or neutralized by the successful matrix-order diagnostic and inspected code paths. However, the failed fixture did not capture the individual prerequisites at its failed call; the successful diagnostics used different fresh synthetic IDs/nonces/session tokens and runtime moments. Thus these observations cannot prove whether the original failure was a transient state/precondition issue or a product defect.

Keep both failed outcomes and both diagnostic PASS outcomes distinct. No fixture, source, site, or database was changed, and no test was run during this comparison. Safe next action is to preserve the unresolved finding; any further diagnosis requires a separately reviewed, sanitized per-prerequisite capture at the failing-call location. Do not infer a fixture-only defect or rerun unchanged positive/matrix fixtures.

## Confirmed cause — fixture closure captured President ID before assignment — 2026-11-06

The previous “root cause unresolved” conclusion is **superseded for the two failed calls** by direct source evidence in both fixtures:

- `phase66a-president-subject-step-up-matrix.php`: `$president_id` is initialized to `0` at line 28. `$issue` is declared at line 99 with `use (..., $president_id, ...)`, which captures the then-current value by value. The real President ID is assigned later at line 168. Calls such as `$issue(...)` at line 222 therefore pass user ID `0` to `ZMP_Passkeys::step_up_options()`.
- `phase66a-president-subject-step-up-positive-routes.php`: `$president_id` is initialized to `0` at line 27. `$issue` is declared at line 91 with by-value `$president_id` capture. The real President ID is assigned later at line 179. Its call at line 233 likewise passes user ID `0`.

In the reviewed `class-zmp-passkeys.php`, `step_up_options()` normalizes the supplied ID with `positive_id()` at line 23; `0` remains invalid/zero, so the first guard at lines 26–27 returns `WP_Error('step_up_context_invalid')` before any role, nonce, credential, or challenge logic. This exactly explains the common failure in both fixtures. It is a **test-fixture defect**, not evidence that the product rejects the legitimate President request.

The successful diagnostic fixtures call `step_up_options()` directly after assigning `$president_id`, so their arguments contain the real ID and their PASS outcomes are consistent with this finding. Their sanitized prerequisite checks further support the distinction.

Preserve the matrix and positive-route executions as **FAIL — invalidated for product acceptance by confirmed fixture closure-capture defect**. Preserve their cleanup and environment checks as independently valid evidence. Do not rewrite either historical output or claim the product route passed. A corrected fixture must capture `$president_id` by reference (`&$president_id`) or receive it as an explicit parameter, then be re-linted, independently reviewed, and run once under the same synthetic preflight and cleanup controls. No fixture/source/environment was changed and no test was rerun for this diagnosis.
