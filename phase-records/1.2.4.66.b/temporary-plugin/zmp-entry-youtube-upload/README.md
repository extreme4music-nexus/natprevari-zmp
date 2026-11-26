# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# ZMP Entry Video Fields — temporary compatibility add-on

Temporary test add-on for ZMP Competitions 1.2.4.66.a. It adds video file selectors to the music-category form on the teacher "Нови пријави" tab and hands selected files to the main plugin's existing per-entry YouTube upload handler after the entry is created.

## Behavior
- Vocal interpretation: one performance video.
- School choir and school orchestra: required and optional composition videos.
- School bands: two optional composition videos.
- Online regional event: the entry remains a draft until all required video slots are uploaded and verified by the main plugin. Files can be selected in the new-entry form or uploaded later from "Мои пријави".
- Physical regional event: video slots are optional; registration can proceed without them.
- Unconfigured regional event: fields are disabled and the main plugin continues to reject registration until the event is configured.
- Uploads reuse the main plugin's existing OAuth settings, authorization, status records, and resumable upload handler. This add-on creates no tables and performs no migration.

## Install and test
Install only on an isolated local/synthetic WordPress copy with ZMP Competitions 1.2.4.66.a active. Do not install on the live public site. Activate this add-on alongside the main plugin, open the teacher portal's "Нови пријави" tab, choose a school and music category, and confirm the correct one or two video selectors appear. Use only synthetic accounts, entries, and video clips. Confirm physical entries remain submittable without a video, and online entries remain drafts until the main plugin reports every required video ready.

If registration succeeds but the browser reports that upload could not be completed, open "Мои пријави" and inspect the existing video status before retrying. Do not repeat an uncertain upload blindly.

## Remove
Deactivate and delete only this add-on after the test. It has no uninstall routine because it creates no database objects or options. Existing entry media/status rows remain owned by the main plugin.

## Scope
This is an experimental UI/flow add-on, not an official ZMP phase release. The add-on has only received PHP syntax and ZIP-structure checks; authenticated WordPress/browser flow has not yet been exercised. The owner is expected to perform the local UI/upload checks and provide screenshots/status evidence.
