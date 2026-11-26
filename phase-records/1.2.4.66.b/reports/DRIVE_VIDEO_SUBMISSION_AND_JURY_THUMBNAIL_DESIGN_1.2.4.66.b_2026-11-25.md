# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Owner-directed Drive video submission and jury preview — 2026-11-25

## Decision

For new competition applications, the plugin will upload the selected video to the ZMP Google Drive through the plugin. The application form will not ask the teacher to upload to YouTube or paste a Drive link. The jury must not see a raw Drive URL or file ID; it sees a small video thumbnail and opens playback inside the authenticated ZMP site.

This is an Owner-directed design decision, not an implementation or test PASS. Existing YouTube records and prior results remain historical evidence. Do not interpret this design note as proof that Drive upload, compression, permissions, thumbnails, or jury playback currently work.

## Teacher submission flow

1. On file selection, show the selected filename and size. Validate the extension and actual media content against the supported video formats. Reject invalid files before upload and explain the reason.
2. Selecting a file does not start network upload. Upload starts only after the teacher presses “Поднеси пријава”.
3. Prevent duplicate submission. Keep the page and form in place; do not refresh or navigate away during upload and verification.
4. Show an animated progress state with plain-language stages: preparing/compressing the video, uploading to Drive, verifying that the complete file exists, and verifying that the jury preview is available.
5. Create or retain the entry as a draft until every required video slot has passed upload and verification. A success response and form refresh happen only after the server has confirmed the uploaded Drive object and required metadata.
6. On validation, network, quota, OAuth, Drive, compression, or verification failure, keep the teacher on the form, retain the draft and selected-file context where possible, give a specific safe message, and allow a retry without starting the application from scratch. A retry must not create duplicate active media rows or orphan Drive files.
7. If an upload finishes but later verification fails, keep the entry draft and either resume/check the same Drive object or clean up only the exact object created by that attempt. Never delete unrelated Drive content.

## Competition slots and requiredness

Preserve the Owner’s category rules:
- Vocal performance: one video slot.
- Choir and orchestra: one optional selection and one required composition.
- Band: two optional selections.
- For a physical competition, video slots are optional.
- For an online competition, the category-required slots are mandatory.
- For a combined competition, apply the configured delivery mode for the participant’s region/category/competition period. The online branch requires its configured slots; the physical branch keeps them optional.

A submission must fail closed if the mode or required-slot configuration cannot be resolved. The moderator-configured regional/category/period mode remains authoritative.

## Drive storage, access, and jury presentation

- Store original and processed videos in a restricted ZMP Drive folder scoped by competition period, category, entry, phase, and slot. Do not use “Anyone with the link” permissions.
- Keep Drive OAuth tokens and raw Drive file IDs server-side. The browser must not receive a permanent Drive download/view URL, OAuth token, resumable session URL, or private file path.
- Jury access is authorized by the plugin for the signed-in commission member assigned to that exact season/category/region/entry/phase. The server uses the connected Drive account to read the restricted object and streams it through the existing authenticated plugin route. Administrators and the owning teacher retain only their explicitly authorized views.
- Jury list shows a small thumbnail card, not a URL or filename-only hyperlink. Clicking the thumbnail opens inline playback through the authenticated ZMP route. The player must support pause, replay, seeking where supported, and useful error messages.
- Prefer retrieving the Drive-generated thumbnail through authenticated server-side Drive API access and proxying it through an authorization-checked plugin endpoint. If Drive does not provide a usable thumbnail, generate a preview frame from the processed video and store it privately. Do not expose the raw thumbnailLink to the browser.
- Before declaring success, verify that the Drive object exists, its size and media metadata match the completed upload, the object is readable through the plugin’s service authorization, and an assigned synthetic juror can play it through the ZMP route. Do not infer jury access merely from upload success.
- Record audit metadata needed for diagnosis, such as flow ID, stage, slot, file size/hash, result code, and synthetic media row ID. Never log OAuth credentials, resumable session URLs, private URLs, user video contents, or unnecessary participant data.

## Compression

Reduce storage use before or during upload with a quality-preserving MP4 profile. Keep the original in private temporary storage until the compressed output has been validated and the Drive upload is confirmed. The policy must define a size/duration ceiling and a quality profile before implementation; do not silently discard the original or silently accept a corrupt/transcoded file.

Compression must preserve aspect ratio and intelligible audio. Validate the resulting container, video/audio streams, duration, dimensions, and output size. If a configured transcoder is unavailable or compression/verification fails, keep the application as a draft and explain the failure. Do not silently fall back to an unbounded original upload.

Google Drive storage does not grant copyright permission. The application must retain the existing rights/consent requirements; moving storage away from YouTube is not a way to bypass copyright obligations.

## Mechanism found in the current .66.b source

The phase already has a Google Drive OAuth integration and resumable upload helper in includes/class-zmp-google-drive.php. It stores video metadata in zmp_category_media, has an authenticated video stream endpoint and a role/assignment check, and the jury currently receives a native video player through video_player(). This is a useful implementation base.

The existing teacher upload_video() path is a redirect-based POST. It does not implement the requested no-refresh submit flow with staged progress, pre-upload compression, final Drive metadata/preview verification, or thumbnail-card-only jury presentation. The current player also exposes an authenticated plugin stream URL in its HTML and has no thumbnail card. These items require implementation and review; this note does not claim they are already fixed.

## Acceptance checks before marking PASS

- Supported and unsupported format, spoofed MIME, empty/oversize file, and required/optional slot behavior.
- Selecting a file causes no upload; pressing “Поднеси пријава” starts exactly one upload.
- Page remains in place with useful progress states; duplicate clicks are blocked.
- Successful resumable upload, interrupted/resumed upload, expired session, API error, quota error, compression failure, and Drive verification failure.
- Draft retention and exact retry behavior; no duplicate entry/media/audit rows or orphaned files after each failure path.
- Exact Drive object metadata, private permissions, no public link, and no credential/session URL in HTML, logs, or diagnostics.
- Assigned synthetic juror can see the thumbnail and play/seek/replay through the plugin. Unassigned juror, other category/region/phase, anonymous visitor, and direct Drive URL access are denied.
- Compression output is playable, preserves intelligible audio and acceptable detail, obeys the configured size ceiling, and does not modify the source file.
- Existing Subscriber baseline and additive Teacher/Moderator/Commission/President role stacks continue to work without role replacement.
- The application is marked submitted and the page refreshes only after all required video slots pass verification.

## Evidence boundary

No product code, database, server, Google account, or video was changed or tested for this design decision. The main project roadmap was not edited.
