# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Live Messenger and school-profile investigation — 2026-11-18

## Current live finding

After the Owner installed `.66.b`, the administrator-only ZMP log on `muzika.org.mk` showed Messenger migration failures at 17:39:56, 17:45:06, 17:50:22, and 17:55:22. The affected table was `wp_yqqdt5_zmp_role_messages`; the failed operation was `dbDelta ALTER`. The Messenger prerequisite then failed closed, so automated retention cleanup remained stopped. The UI did not include the database error text or code, so the exact database cause could not yet be identified from these events. A follow-up build that maps the sanitized WordPress database error into an allow-listed class was prepared but could not be installed through the browser: its native Windows file picker is visible to the UI controller but its `Open` control is not actionable.

Earlier at 17:29:03 the same page logged both `admin_facsimile` and `messenger` prerequisites as not ready. At 17:39:56 the new heartbeat logged Messenger only. This is not sufficient evidence that the facsimile schema is now ready; it needs an independent post-retry check.

School profile entries in the supplied diagnostics showed a successful synthetic school-engagement creation at 17:18:59, followed by profile operations denied with `forbidden`. Those entries do not identify the requested synthetic account or prove that the profile read path works for an eligible teacher. Do not treat this as a schema failure or a successful profile test until an eligible synthetic account is used.

## Code change prepared

The Messenger installer now maps the database error text retained by WordPress to a small allow-listed class (`duplicate_column`, `duplicate_key`, `key_too_long`, `permission_denied`, `syntax_error`, `object_missing`, `unknown_column`, `invalid_definition`, `alter_rejected`, or `database_error`). Raw SQL, database error text, message content, and user data are not logged. This should expose enough evidence to choose a repair without adding sensitive database text to the administrator diagnostics.

## Verification status

- PHP syntax check for the changed Messenger file: PASS on Windows PHP 8.5.11 and WSL PHP 8.5.4.
- Local runtime validation of the allow-listed error classifier: pending.
- Updated package installation and live retry with classified error: blocked by the native Windows file picker; no live code was changed.
- Exact Messenger schema repair and retention-queue safety check on the live site: pending.
- Independent admin-facsimile and eligible synthetic school-profile checks: pending.
- Synthetic upload: an 8-second, generated-color video with a synthetic 440 Hz tone was created. YouTube Studio confirmed the channel content list was empty and opened the upload flow, but the native file picker could not be operated; upload did not occur and no public video was created.

The Owner explicitly authorized a public synthetic test video. The attempted video contains no real people or third-party material. It remains local because YouTube upload stopped at the native file-picker limitation.

