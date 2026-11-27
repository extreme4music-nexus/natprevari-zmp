# Messenger live warning — diagnostic build — 1.2.4.66.a — 2026-11-27

## Owner-reported reproduction

Historical reproduction: the owner installed the messenger-schema-fix.zip and the live administrator page still displayed the warning. The owner later ran the read-only diagnostic; its result and the subsequent source finding are recorded below.

The owner also reports that private-message exchange works end-to-end for a synthetic Moderator account. Record that as a user-confirmed PASS for sending/receiving messages only. The remaining warning is specifically about Messenger schema readiness for automatic retention cleanup; message exchange success does not prove the cleanup schema gate or scheduled deletion is healthy.

## Why the warning alone is insufficient

`ZMP_Role_Messenger::render_retention_health_notice()` displays the same text when either the durable retry state is pending or the shared `zmp_db_version` marker is not current. Those states can be caused by an actual Messenger mismatch, a failed read/write of schema-control options, a delayed retry, or another schema prerequisite preventing the shared marker from advancing. The warning itself does not identify which condition applies.

The shared marker commit in `zmp_competitions_commit_messenger_schema()` also depends on school-profile, school-email, subject-proposal, mentor-school, and facsimile schema readiness. A failure in one of these components can leave Messenger's retention notice active even if Messenger's own tables are compatible.

## Diagnostic added

The administrator page **ЗМП Натпревари → Дебагирање** now has **Провери ја Messenger шемата**. It performs fresh reads and displays/logs:

- installed plugin version and required schema version;
- database marker state/value, retry option state, and retry deadline state;
- Messenger schema compatibility and the first mismatching table/column/index, including expected and observed metadata;
- the latest already-recorded schema prerequisite diagnostics from other components, without rerunning their readiness functions.

The diagnostic action does not itself run a migration, change Messenger options, alter tables, or read message content; it writes one filtered diagnostic event to the existing ZMP log. It runs after normal WordPress/plugin request initialization, so the existing automatic five-minute retry may run first when due. The event contains schema metadata and fixed state labels only; no SQL error text, credentials, message body, or personal data is recorded. It deliberately does not rerun the other components' `schema_is_compatible()` methods: some of those methods persist Messenger retry state when they detect drift, which would alter the state under investigation. Instead, the screen shows only matching events already in the diagnostic log.

## Next action and status

Install the current root-cause ZIP listed at the top of the `.66.a` release manifest. After the next schema retry, inspect the latest Plugin schema prerequisite not ready event for schema_prerequisite_component. The Debugging button remains read-only and can display that stored event. Share its result or export the communications event. Do not label Messenger cleanup PASS until the blocker is fixed, the global marker advances, and a fresh readiness check succeeds.

This report records the diagnostic and root-cause code correction. It is not evidence that the live-site problem is fixed.

## Build and local verification

- Candidate ZIP: `04_RELEASES/VERSIONS/1.2.4.66.a/ZIP/zmp-competitions-1.2.4.66.a-2026-11-27-messenger-diagnostic-03.zip`.
- SHA-256: `5b248c34e4819760466d82f2a1c7b884428cee57dde88f9c16f6c0a514b71ecd`; 9,155,708 bytes.
- Package: 2,244 source files; ZIP contains 2,244 files (2,246 entries including directory entries); exact relative-path comparison **2,244/2,244 PASS**; ZIP content readback **PASS**; hashes for the three changed PHP files, plugin entry point, and license **5/5 match**.
- PHP syntax: changed PHP files **3/3 PASS**.
- On the existing local `:8111` clone, the diagnostic returned: `notice_active=false`, marker `current`, retry `none`, Messenger schema-compatible `yes`; before/after retry/marker option values were unchanged. Other component checks were deliberately not rerun. This verifies the diagnostic's healthy-state output only; the local clone is not the live production database.
- Local WordPress Debugging page render: button and result panel present **2/2 PASS**. Privacy-context sanitizer retained the expected diagnostic fields and reduced the prefix-presence flag to a boolean **PASS**.
- SHA-256 inventory: `04_RELEASES/VERSIONS/1.2.4.66.a/HASHES/SHA256_MESSENGER_DIAGNOSTIC_2026-11-27.txt`.

## Owner diagnostic result — 2026-11-27 02:31

The owner ran the diagnostic on the live site and supplied:

- plugin/schema required version 1.2.4.66.a;
- zmp_db_version marker 1.2.4.53.2 (stale);
- retry state pending, with a five-minute retry deadline;
- Messenger schema compatibility yes, with no Messenger table/column/index mismatch;
- five stored prerequisite events, all from 2026-11-25 and naming school email or school engagement readiness.

This proves the warning is not currently explained by a structural Messenger mismatch. The Messenger warning is gated by the plugin-wide schema marker and retry option; the marker advances only after all participating plugin schema prerequisites pass. School-profile/email prerequisite failures are the most relevant existing clues, but their events predate this diagnostic and do not prove the current failing component.

A second source defect was found in zmp_competitions_init(): the stale marker previously bypassed the five-minute retry cooldown. The code now relies on schema_retry_allowed() even when the marker is stale.

The migration commit now records the first failing prerequisite in the safe diagnostic context as schema_prerequisite_component, including install results, component schema checks, and database-marker persistence failures. The next installed build can therefore identify the actual blocker without executing a separate readiness check from the diagnostic button.

**No database marker was changed manually. No production message or user data was changed. The live cleanup issue remains open until the new event identifies its blocker and a successful retry advances the schema marker.**


## 2026-11-27 code correction after live diagnostic

The owner-provided live snapshot reports Messenger structural compatibility YES, shared marker 1.2.4.53.2 stale, and a pending retry. Review of zmp_competitions_commit_messenger_schema() confirms the marker is shared: it advances only after Messenger, school-profile, school-email, subject-proposal, mentor-school, region-request, and facsimile schema prerequisites pass. The notice therefore does not prove a Messenger table defect.

A retry-dispatch bug was confirmed: the stale marker previously made schema_upgrade_required true and bypassed schema_retry_allowed(), so the five-minute cooldown did not apply on every request. This build removes that bypass. It also logs the first failed prerequisite/install result or marker persistence step as schema_prerequisite_component. Exact live blocker remains unknown until this code runs on the live site.
