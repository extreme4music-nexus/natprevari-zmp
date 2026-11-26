# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Whole-Phase Code Review — Current Candidate — ZMP 1.2.4.66.a

**Review date:** 2026-11-11  
**Verdict:** **SCOPED STATIC SOURCE REVIEW PASS; PHASE ACCEPTANCE BLOCKED / OPEN**  
**Reviewer:** Independent Code Reviewer (read-only)

## Inputs and candidate identity

Read the current root `SESSION_SNAPSHOT_CURRENT.md`, `ZMP_PROJECT_LIBRARY/PROJECT_ROADMAP.md`, and `.66.a` plan, state, patch notes, What's New, debugging guide, test report, plus existing code-review, debugger, UI-review and Test Engineer reports relevant to schema-v3 quota/cleanup, passkey login, enrollment diagnostics and President subject step-up. The active candidate is the `.66.a` phase folder, not an installed site snapshot.

The exact SHA-256 manifest for all first-party PHP, JavaScript, CSS and HTML source files in the phase candidate (excluding tests and third-party `vendor/`) is `reports/CODE_REVIEW_WHOLE_PHASE_CURRENT_1.2.4.66.a_SOURCE_HASHES.txt`. It includes the entrypoint and all first-party files under `admin/`, `assets/`, and `includes/`. Key reviewed current hashes:

| File | SHA-256 |
|---|---|
| `includes/class-zmp-passkeys.php` | `acee4133dee295e2bdd401ef22b7cd304626a53f0f09d698027c69e34dcdc2ae` |
| `includes/class-zmp-passkey-store.php` | `f9038de6499b844529118fe98ab8bd32fe30414bd1061bc35650f638308d02db` |
| `admin/class-zmp-passkey-profile.php` | `705c7dda64db3307db34c06d1a5134aea76464df78cab77bd3406e5e6a5ddd6d` |
| `assets/js/zmp-passkeys.js` | `b8b2bd2f352797671a3bc92fcf3887d3c2a77cff2104bafe69eab47dc685d4fb` |
| `includes/class-zmp-president-subject-approvals.php` | `7e8c431da2ceefeb09c91e23ab293260e959743765e35ce9b27a3baefe904021` |
| `assets/js/zmp-president-subject-stepup.js` | `49d7ec3947f3cf133df1e54d9fd0b0b3d2e1810885ce94d5986d105c1a50f92b` |

## Review result

No new confirmed source-code defect was found in this static review. **Static review scope PASS** for the inspected candidate paths and listed criteria; this is not a runtime or phase acceptance PASS.

- **Schema-v3 migration and compatibility:** `ZMP_Passkey_Store::install_schema()` keeps the marker update behind exact structural validation and rechecks the marker/shape; compatibility fails closed when either is wrong. The schema adds the peer hash/index without deleting credential/challenge tables. Existing v2 peerless login challenges are intentionally unconsumable. (Current store source lines 17–79 and 554 onward.)
- **Login issuance limits and challenge cleanup:** `rate_limited_login_options()` takes the global then peer lock, counts consumed as well as pending recent login rows, and releases locks in reverse order; failed lock/count/release paths deny issuance. `create_challenge()` rejects login bindings without a peer hash and performs expiry cleanup before insert, returning failure on cleanup exceptions or `false`. This resolves the older static P2s for consumed-at quota churn and ignored expired-row cleanup in the reviewed source. (Passkeys source around lines 360–475; store source around lines 331–377.)
- **Enrollment diagnostics:** server-side diagnostic labels and response header are gated by the strict `ZMP_PASSKEY_DIAGNOSTICS === true` constant; the frontend displays detailed failure categories only when the localized flag is exactly true. It maps only known header values, does not render arbitrary response bodies/headers/URLs, and retains generic wording when diagnostics are disabled. (Profile PHP lines 91–105, 153–233; JS lines 235 onward and 452 onward.)
- **President proposal step-up:** decision handling checks the active President role/mandate, request method/action/nonce, validates the exact decision and canonical target, verifies the challenge/assertion bound to the route context, then calls the existing decision service and emits constrained outcome diagnostics. Static trace shows no nonce-only path to the decision service. (Approvals source lines 190–245; detailed context binding remains in the current passkeys source.)
- **Adjacent compatibility and privacy:** reviewed the current entrypoint wiring, enrollment/login integration and reports for rollback/migration expectations. No source path in this candidate drops the new credential/challenge tables as part of ordinary rollback; rollback evidence remains runtime-not-run.

## Findings

**No new source-code finding to report.** Historical findings are not silently closed by this report: closure is limited to the static control-flow observations above and previously recorded, hash-bound focused evidence. Earlier expected-red quota, cleanup and schema-shape reports are preserved; the later focused evidence records correction on the candidate hashes at that time. This review did not execute those fixtures against today’s source hashes.

## Acceptance status and limits

**Phase acceptance: BLOCKED / OPEN.** The source review does not clear these recorded gates:

- The latest enrollment failure is unresolved and not correlated to a current passkey-options request. The current UI/options diagnostic discriminator has not been exercised; browser registration and attestation have no success evidence.
- Task4 browser login flow, Task5 authenticator decision flow, cancellation/unavailable-authenticator cases, concurrent replay, migration/rollback runtime, and the required independent whole-phase functional/UI checks remain NOT RUN or NOT VERIFIED in the phase records. A scoped Task5 rendered UI observation does not cover the missing browser ceremonies or mobile-simulator keyboard review.
- Focused PASS reports for login, quota, schema, and step-up are attached to earlier exact source hashes; later diagnostic edits changed the passkey JS/PHP, profile, store and entrypoint as applicable. Do not extend their PASS status to the current source without the phase’s hash-impact reconciliation and required targeted reruns.
- No package, GitHub branch synchronization, Auditor decision, or release acceptance is established.

This was read-only. No tests, browser interaction, database/service operations, production access, source edits, or secret inspection were performed. Third-party `vendor/` implementation was not independently reviewed; the plugin’s integration points were reviewed against the pinned WebAuthn API as reflected in the phase artifacts.
