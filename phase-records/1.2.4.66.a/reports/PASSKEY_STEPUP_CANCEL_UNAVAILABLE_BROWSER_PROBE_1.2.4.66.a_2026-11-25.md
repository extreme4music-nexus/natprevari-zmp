# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# Passkey authenticator cancellation/unavailable probe — .66.a — 2026-11-25

## Status

**HISTORICAL PARTIAL SNAPSHOT — superseded by the 2026-11-25 owner-confirmed and repeated PASS.** This earlier attempt did not resolve to an explicit cancellation result. Later, the owner confirmed Cancel followed by deliberate PIN-authenticated retry and acceptance; the same browser path was repeated locally with successful assertion, consumed challenge, and persisted synthetic approval. See `PRESIDENT_TEACHER_PROFILE_UI_LAYOUT_AND_KEYBOARD_CHECK_1.2.4.66.a_2026-11-25.md` and the current scope reconciliation. Waiting for the owner to enter a PIN is expected and is not a defect.

## Local synthetic test

- On the existing local `:8111` clone, a pending synthetic subject proposal was created through the plugin service and displayed to synthetic President/Teacher/Subscriber `codex66a_president_2511` (user 5).
- Clicking “Одобри како нов предмет” started the real passkey step-up flow. While waiting, all decision buttons were disabled and the page showed “Следете го безбедносното барање во прелистувачот.” No authenticator dialog was visible in the captured browser window. Escape did not resolve the request; a reload stopped the client-side request.
- After reload, the proposal remained `pending`, `decided_by=0`, `decided_at=NULL`, `canonical_subject_id=0`, with only its original `submitted` event. This proves no decision bypass or protected mutation occurred during the aborted request.
- A separate enrollment attempt with the same synthetic account did return browser `NotAllowedError`; the page showed its WebAuthn diagnostic, the existing credential count stayed at one, and no second credential was created. The temporary enrollment challenge and test data were removed.
- The synthetic proposal and the associated unconsumed step-up challenge were deleted by their exact synthetic IDs after readback. One pre-existing challenge row was left untouched. DB integrity passed.

## What is still needed

At the time of this historical probe, the actual step-up browser flow did not return before reload. The later owner-confirmed cancellation/retry sequence and successful repeat test supersede this earlier unresolved attempt for the tested browser flow. The separate enrollment `NotAllowedError` remains only enrollment evidence. No source change was made in this probe.
