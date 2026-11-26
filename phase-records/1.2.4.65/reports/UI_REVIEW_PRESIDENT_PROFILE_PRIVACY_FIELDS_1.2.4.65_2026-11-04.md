## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Independent UI Review — President profile privacy fields — 1.2.4.65

Date: 2026-11-04  
Reviewer: Independent UI Reviewer (read-only source inspection; no browser rerun)  
Requested gate: `/wp-admin/profile.php` private upload URL and unrelated profile-field exposure.

## Evidence inspected

- `includes/class-zmp-president-public-display.php`, SHA-256 `91BD91B0F4F76579553457E3F0B9779EBFE79CA9D5E509040F689AB2DD5D5D76`.
- `assets/js/zmp-president-public-display.js`, SHA-256 `98AC7EB36ED34F12C7F4874E41A9322FBF52387CA102F9A4AE0898FA30139E54`.
- Existing independent rendered report: `reports/UI_REVIEW_PRESIDENT_PROFILE_MOBILE_INDEPENDENT_1.2.4.65_2026-11-04.md`.

## Source observations

- `render_profile_photo_fields()` emits the custom photo section only when the target has a President record and the current user can edit it. Editing another user's profile additionally requires `manage_options` (PHP lines 204–210). The existing UI report confirms this section rendered on the synthetic President's own `/wp-admin/profile.php` page.
- The authorized profile section includes a hidden attachment ID and image-preview `src` derived from `local_attachment_url()` (lines 213–247). The URL helper accepts only a valid inherited image attachment within the site's uploads URL/path and rejects credentials, query/fragment, different host/scheme/port, and paths outside uploads (lines 522–582). This indicates the preview URL is intentionally visible to the authorized profile viewer; source inspection alone does not establish whether any environment-specific URL is private or whether the rendered DOM contains additional URLs.
- Public `public_mandate_data()` projects only display name and mandate dates, and adds `photo_url` only when opt-in meta is exactly `1` and the local URL validates (lines 41–82). The public AJAX response serializes the limited projection and state fields (lines 105–147). The JavaScript consumes the President projection and populates the public Home block (JS lines 120–160); it does not read the profile form.

## Verdict

**NOT CLOSED — rendered DOM evidence is still required.** The source paths are privacy-scoped as described, but this review did not inspect the actual rendered `/wp-admin/profile.php` DOM for the precise URL values or inventory unrelated WordPress profile fields. The prior 390×844 responsive and keyboard PASS remains valid and was not repeated. Do not count this source review as a rendered privacy-field PASS.

No files outside this report were changed. No test, browser action, login, or database operation was performed.

## Addendum — authenticated local render evidence and inspection boundary — 2026-11-04

An authenticated synthetic President profile was rendered in the Owner-authorized local Chrome session at `http://127.0.0.1:8094/wp-admin/profile.php`. The visible page contained standard WordPress profile sections, the custom school-profile unavailable notice, and the public photo/consent section. No private upload URL or unrelated custom private field was visible as text. The independent UI Reviewer separately confirmed the same visible result. Neither review inspected the image `src` attribute or other hidden DOM values, so this is partial rendered evidence and the requested privacy gate remains **NOT CLOSED**.

The browser tool refused the `view-source:` scheme and explicitly disallowed equivalent inspection via DevTools/CDP or another browser surface. No workaround was attempted. Owner was asked for an explicitly approved inspection method or permission to leave the gate open. This addendum does not change the original read-only source findings or claim a full privacy pass.

The task-created Chrome tab was closed after the review. The synthetic account's temporary password was replaced with a fresh unknown value and all session tokens were destroyed. No profile fields were changed and no external email was sent.
