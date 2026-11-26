# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# Passkey Flow Debug — 1.2.4.66.a — 2026-11-25

## Scope and safety

Investigation and browser checks use only the local clone at `http://localhost:8111` and synthetic President account `codex66a_president_2511` (WordPress user ID 5). Production was not accessed. The intended browser action targeted only synthetic proposal `id=1`. A separate event for “Музичко образование” (`id=2`, `approved_new`, actor 5) was found during later readback at 23:07:47. The owner later clarified that they themselves clicked Cancel, then deliberately retried the approval, entered their PIN, and accepted. Supersede any earlier implication that the approval was an assistant action or accidental. The exact event-to-attempt mapping is not established in this historical report.

## Findings

1. **Decision assertion verifier had a fatal argument mismatch.** After the allow-list correction, the request reached WebAuthn validation but raised `TypeError`. The safe diagnostic localized the callsite to `class-zmp-passkeys.php:959`: `request_options()` now requires an allow-credentials argument, while step-up verification and the existing discoverable-login paths still called it with two arguments. PHP rejected the call before assertion validation, so the decision remained pending. The step-up call now passes a descriptor for the already owner-verified credential; the argument is optional for discoverable login, which intentionally has no allow-list.
2. **Passkey removal control lost the credential identifier.** The stored WebAuthn credential ID is binary. The profile renderer placed those bytes directly in an HTML `data-credential-id` attribute; the browser exposed an empty attribute. The remove handler therefore received no credential ID.
3. **Begin/remove require the WordPress account password, not the Windows PIN.** Earlier registration attempts were rejected at `wp_check_password()` (`password_rejected`) and did not reach WebAuthn. The local synthetic account password was reset for this test only; no production account was changed.

## Code changes

- Step-up request options include only credential descriptors belonging to the current authenticated user. During verification, the exact credential that passed owner/record checks is supplied as the allow-list entry.
- `request_options()` now defaults to an empty descriptor list for discoverable login; the step-up verifier explicitly supplies the matched credential, eliminating the missing-argument `TypeError` without weakening credential scope.
- The profile UI now encodes binary credential IDs as canonical base64url for the HTML attribute. The remove handler validates and decodes that token before owner-scoped deletion.

## Verification so far

- PHP lint passed for the two edited files in both the phase folder and the running local clone.
- Phase-source and local-runtime SHA-256 values matched after copying the changes.
- In the local browser, removal displayed “Passkey е отстранет од оваа сметка.” The database confirmed zero credential rows for synthetic user 5.
- Registration completed successfully: the local PHP log recorded `registration_finish_succeeded` and `finish_handler_succeeded` at 22:53:02. The credential row was present for synthetic user 5 afterward.
- The browser assertion was received and rejected as `step_up_type_error`; after adding safe file/line diagnostics, a subsequent assertion localized it to the `request_options()` callsite. No sensitive assertion values or exception message were logged.
- The function-call fix is present in both `.66.a` phase source and the running `:8111` clone. PHP lint passed for both copies; SHA-256 matches: `bb2175202d4936cdb4b88163e2baa4d442644c7844027762aaae38fd76ae4c9a`.
- After the user completed the local authenticator prompt, the same browser flow logged `step_up_assertion_validation_succeeded` and `step_up_verification_succeeded` at 23:06:23; the authenticated `admin-post.php` decision returned HTTP 302 and redirected to `zmp_subject_decision_status=approved`.
- Readback from the local database confirmed synthetic proposal `id=1` is `approved`, decided by synthetic President `user_id=5`, with canonical subject `id=1`. The passkey credential's `last_used_at` was updated to `2026-11-25 22:06:23` (database-local time). A separate event for “Музичко образование” (`id=2`) was recorded at 23:07:47 by actor 5. The owner later confirmed personally completing a Cancel-then-retry approval flow; do not describe this event as an accidental approval by the assistant. The report does not claim an exact mapping to that event.
- Phase source and active `:8111` runtime are byte-identical for `class-zmp-passkeys.php`, SHA-256 `bb2175202d4936cdb4b88163e2baa4d442644c7844027762aaae38fd76ae4c9a`.

## Current status

**PASS — local end-to-end passkey decision for synthetic proposal `id=1` verified after the TypeError fix.** The browser assertion validated, the intended proposal transition and event were persisted, and passkey-use readback changed. The owner later confirmed their own Cancel-then-retry decision with PIN; prior claims that another actor caused an accidental approval are superseded. Production was not accessed.

