# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Passkey Login and Step-Up Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use `superpowers:subagent-driven-development` to implement this plan task-by-task. Steps use checkbox syntax for tracking.

**Goal:** Add passkey registration and login, plus one-use step-up authentication for one approved privileged ZMP action, without weakening WordPress authorization.

**Architecture:** Keep WordPress user IDs, sessions, roles/capabilities, active mandates and resource checks authoritative. Use a maintained server-side WebAuthn library; keep private keys in authenticators, store public credential records in a dedicated plugin table, and bind one-use challenges to ceremony/user/session/action as applicable. The candidate step-up operation is active-President approval/rejection of a subject proposal; this exact operation still needs Owner confirmation.

**Tech Stack:** WordPress/PHP, browser WebAuthn API, `web-auth/webauthn-lib` 5.3.9 candidate, plugin-owned MySQL tables, existing admin-post/REST patterns, synthetic local WSL WordPress.

**Spec:** `../../../DESIGN_1.2.4.66.a.md`; acceptance: `../../../../../PROJECT_ROADMAP.md`, subsection `.66.a`.

## Global Constraints
- No production access, production SSH, live-site requests or real email.
- Test only synthetic data in a phase-scoped disposable local site; preserve persistent clone and `.65` site/database/services.
- WebAuthn validates authenticator control; it never grants ZMP authorization or signs a decision.
- Never store authenticator private keys, passwords, session cookies, WebAuthn assertions, raw challenges or recovery secrets in source, JavaScript, ordinary WordPress options, logs, reports, ZIPs or GitHub.
- Exact origin/RP ID allowlist; HTTPS except localhost; fresh server-generated random challenges; consume once and fail closed.
- Current CLI is PHP 8.5.4 with JSON/OpenSSL/Sodium/mbstring; the Owner-reported hosting self-tests are recorded in the phase report.
- No ZIP or GitHub write until required functional/UI review and Auditor gates pass; exact repository/branch must be known first.

## Review Focus
1. Challenge replay, expiry, session/user/action/resource mismatch: prove denial and no protected write.
2. Login credential enumeration and cross-user binding: prove identical public failure and only the credential's exact WordPress user can receive a session.
3. Origin/RP ID/ceremony mismatch and client response tampering: prove library validation rejects each.
4. Role, active-mandate or resource changes after passkey verification: prove the final route rechecks current server authorization.
5. Authenticator cancellation/loss and rollback: prove there is no hidden bypass and existing WordPress/password/competition state remains intact.

---

### Task 1: Phase source baseline and disposable runtime

**Files:**
- Create/copy: `.66.a` plugin source tree from the latest approved `.65` candidate, excluding `reports/`, prior `tests/`, `tmp/`, credentials, uploads and database artifacts.
- Create: `reports/ENVIRONMENT_PREFLIGHT_1.2.4.66.a.md` (sanitized facts only).
- Modify: `zmp-competitions.php` version metadata only after the candidate folder is established; keep schema marker unchanged unless a reviewed migration requires it.

**Interfaces:** establishes a phase-specific plugin source tree and synthetic runtime only; no feature API yet.

- [ ] Verify the source folder and current `.65` authoritative records; preserve `.65` unchanged.
- [ ] Inventory WSL PIDs/ports and confirm new test port is free; record PID/port/purpose before starting anything.
- [ ] Build `/home/tonic/zmp-local-sandbox/phase66a-test/site` with a new synthetic-only database and only the `.66.a` ZMP plugin; never copy `.65` or persistent-clone participant data.
- [ ] Record WordPress version, web-SAPI PHP/extensions, localhost origin, DB identity, synthetic-user counts and plugin inventory without secrets/PII.
- [ ] Verify clean plugin activation and DB integrity; remove only task-owned temporary artifacts after evidence is recorded.

### Task 2: Credential and challenge storage

**Files:**
- Create: `includes/class-zmp-passkey-store.php` for dedicated credential/challenge tables and transactional repository methods.
- Modify: `zmp-competitions.php` to load the store and include a versioned, additive schema migration.
- Test: `tests/phase66a-passkey-store.php` for repository behavior on the disposable site.

**Interfaces:**
- `ZMP_Passkey_Store::save_credential(int $user_id, \Webauthn\CredentialRecord $record, string $label): bool`
- `ZMP_Passkey_Store::find_credential(string $credential_id): ?\Webauthn\CredentialRecord`
- `ZMP_Passkey_Store::create_challenge(array $binding): ?string`
- `ZMP_Passkey_Store::consume_challenge(string $challenge_id, array $expected_binding): ?array`
- `ZMP_Passkey_Store::delete_credential(int $user_id, string $credential_id): bool`

- [ ] Write failing behavior checks for exact user binding, duplicate credential rejection, expiration, atomic one-use consumption and no plaintext/secret logging.
- [ ] Run each focused behavior check and record the expected pre-implementation failure.
- [ ] Implement minimal dedicated-table repository with unique credential ID, bounded fields, UTC expiry and atomic `consumed_at` transition; never use options/transients for challenge truth.
- [ ] Run the focused checks and inspect DB rows for synthetic-only state; verify `wp db check` and restoration/cleanup.

### Task 3: Passkey registration

**Files:**
- Create: `includes/class-zmp-passkeys.php` for WebAuthn service and ceremony validation.
- Create: `admin/class-zmp-passkey-profile.php` for active-President enrollment and owner-only credential removal UI.
- Create: `assets/js/zmp-passkeys.js` for browser `navigator.credentials.create()` only.
- Modify: `zmp-competitions.php` for versioned Composer autoload and hooks.
- Test: `tests/phase66a-passkey-registration.php` for valid synthetic attestation and negative cases.

**Interfaces:**
- `ZMP_Passkeys::registration_options(int $user_id, string $current_password): array|WP_Error`
- `ZMP_Passkeys::finish_registration(int $user_id, string $challenge_id, string $payload_json): true|WP_Error`

Registration options and finish require the current authenticated account owner to have the exact `zmp_president` role and an active mandate. Removal requires the signed-in credential owner, current password, and dedicated nonce, but does not require a current role or mandate. These are logical return contracts; avoid PHP 8.2 standalone `true` return-type syntax because the plugin has no previously declared PHP minimum. The WebAuthn dependency itself requires PHP 8.2 and is not loaded below that version.

- [ ] Write failing tests for unauthenticated/wrong-role/inactive-mandate registration denial, challenge/user/session mismatch, unexpected origin/RP ID rejection and duplicate credential denial.
- [ ] Run tests to verify expected failure before implementation.
- [ ] Integrate the reviewed WebAuthn 5.3.9 serializers/validators; allow only the intended attestation mode and exact origin; require recent account reauthentication and user verification.
- [ ] Persist only library credential/public-key material, credential ID, exact user ID, bounded label/transport/counter metadata; never persist the private key.
- [ ] Run tests and verify audit events contain fixed categories only, not challenge/assertion/credential payloads.

### Task 4: Passkey login

**Files:**
- Modify: `includes/class-zmp-passkeys.php` with login ceremony.
- Modify: `zmp-competitions.php` to add narrowly scoped public login endpoints.
- Create: `assets/js/zmp-passkey-login.js` for `navigator.credentials.get()`.
- Modify: the login UI integration file identified in source review, preserving password login.
- Test: `tests/phase66a-passkey-login.php` with generated synthetic cryptographic material.

**Interfaces:**
- `ZMP_Passkeys::login_options(): array|WP_Error`
- `ZMP_Passkeys::finish_login(string $challenge_id, string $payload_json): int|WP_Error` (returns verified WordPress user ID internally; public response remains generic).

- [ ] Write failing tests for valid assertion/session creation, invalid signature, unknown credential, cross-user handle, wrong origin, wrong RP ID, expired/replayed challenge and generic unknown-account failure.
- [ ] Run tests before implementation and verify the failures are due to missing behavior.
- [ ] Implement discoverable-credential login with random non-PII user handles, one-use anonymous challenge, exact origin/RP ID, required user verification and WordPress session creation only after successful verification.
- [ ] Keep password login and current recovery behavior unchanged; no real email is sent.
- [ ] Run focused tests and actual browser flow on the disposable local site; verify user identity and logout/session cleanup.

### Task 5: Step-up for one privileged action

**Files:**
- Modify: `includes/class-zmp-president-subject-approvals.php` to require an assertion in the selected decision POST.
- Modify: `includes/class-zmp-passkeys.php` to bind step-up ceremony to the active user/session, action key, proposal ID and intended decision.
- Modify: President decision form assets/UI as needed.
- Test: `tests/phase66a-president-subject-step-up.php`.

**Interfaces:**
- `ZMP_Passkeys::step_up_options(int $user_id, string $action, int $resource_id, string $decision): array|WP_Error`
- `ZMP_Passkeys::verify_step_up(int $user_id, string $challenge_id, string $action, int $resource_id, string $decision, string $payload_json): true|WP_Error`

- [ ] Write failing tests for valid active-President approval/rejection and denial for wrong role, inactive mandate, changed resource, changed decision, wrong session, stale/replayed assertion and missing nonce.
- [ ] Run the focused tests to verify failure before implementing the protected path.
- [ ] Verify assertion first, then independently recheck WordPress capability, active mandate, proposal status/resource, action nonce and current assignment before any decision/audit write.
- [ ] Ensure step-up proof cannot authorize a second request or a different proposal/decision; test no-write behavior for every denial.
- [ ] Run the real local synthetic route/UI flow; restore DB baseline and verify integrity.

### Task 6: UI, diagnostics, recovery and rollback

**Files:**
- Modify: passkey profile/login/President UI and CSS only if required.
- Modify: `WHATS_NEW_1.2.4.66.a.md`, `DEBUGGING_1.2.4.66.a.md`, test report, patch notes and state.
- Test: narrow UI keyboard/mobile and feature-specific recovery/rollback checks.

- [ ] Provide clear states for unsupported browser, cancellation, expired challenge and generic login failure; retain keyboard operation and password fallback.
- [ ] Use fixed safe diagnostic categories and remove raw WebAuthn request/response objects from library and application loggers.
- [ ] Test feature rollback/deactivation without deleting users, credentials unexpectedly, competition data or unrelated settings; document explicit credential revocation/deletion behavior.
- [ ] Obtain independent UI review before final UI tests; then independent exact-hash code review, Test Engineer report, Debugger only for actual product defects, Master reconciliation and Auditor decision.

### Task 7: Record owner-supplied hosting information and phase disposition

**Files:**
- Update: `reports/ENVIRONMENT_PREFLIGHT_1.2.4.66.a.md`, `PROJECT_STATE_1.2.4.66.a.md`, `TEST_REPORT_1.2.4.66.a.md`, `WORK_LOG_1.2.4.66.a.md`.

- [ ] Mark local runtime compatibility only from the separate synthetic site.
- [x] Record Owner-supplied HTTPS, REST, WP-Cron and ES256/P-256 self-test outcomes plus PHP 8.3.36 / MySQL 8.0.41 runtime compatibility in the phase report.
- [ ] Close all acceptance gates truthfully. Do not create ZIP or GitHub branch while any required gate is FAIL/BLOCKED/NOT RUN.

## Self-review
- Roadmap criterion 1 is covered by the threat model and exact action/role review.
- Criterion 2 is covered with an explicit production-vs-local evidence boundary and migration/rollback evidence.
- Criterion 3 is covered by registration, user binding, storage and privacy tests.
- Criterion 4 is covered by login, step-up, negative/replay/expiry/unavailable-authenticator, compatibility and rollback tests.
- Five review-focus risks map to explicit behavior tests above; no general test-runner work is planned.


## Owner decision and implementation sequence correction — 2026-11-05
The Owner confirmed the exact first protected step-up flow: active-President subject-proposal approve/reject. The static route trace and action context are recorded in `DESIGN_1.2.4.66.a.md`. The detailed implementation sequence remains: finish Task 4 passkey login first while preserving password login, then implement Task 5 step-up on the confirmed route. The earlier sentence saying Owner confirmation was still needed is superseded. Store context-hash schema v2 is implemented and its focused behavior/regression tests are recorded in the current Test Engineer report.
