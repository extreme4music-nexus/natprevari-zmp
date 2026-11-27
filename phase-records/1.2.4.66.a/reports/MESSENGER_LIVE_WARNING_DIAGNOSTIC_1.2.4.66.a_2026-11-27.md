# Messenger live warning — diagnostic build — 1.2.4.66.a — 2026-11-27

## Owner-reported reproduction

The owner confirmed installing `04_RELEASES/VERSIONS/1.2.4.66.a/ZIP/zmp-competitions-1.2.4.66.a-2026-11-27-messenger-schema-fix.zip`. The live administrator page still displays the Messenger schema warning. Therefore the local migration PASS in the earlier report does not prove that the live database has been repaired. The live root cause remains undetermined until this diagnostic is run on that site.

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

Install the diagnostic ZIP listed at the top of the `.66.a` release manifest, open the Debugging page, and run the button once. Share the result panel or export the **Пораки и известувања** event. Use that evidence to fix the identified live cause; do not label the Messenger issue PASS before the live warning clears and a fresh readiness check succeeds.

This report documents the diagnostic implementation only. It is not evidence that the live-site problem is fixed.

## Build and local verification

- Candidate ZIP: `04_RELEASES/VERSIONS/1.2.4.66.a/ZIP/zmp-competitions-1.2.4.66.a-2026-11-27-messenger-diagnostic-03.zip`.
- SHA-256: `5b248c34e4819760466d82f2a1c7b884428cee57dde88f9c16f6c0a514b71ecd`; 9,155,708 bytes.
- Package: 2,244 source files; ZIP contains 2,244 files (2,246 entries including directory entries); exact relative-path comparison **2,244/2,244 PASS**; ZIP content readback **PASS**; hashes for the three changed PHP files, plugin entry point, and license **5/5 match**.
- PHP syntax: changed PHP files **3/3 PASS**.
- On the existing local `:8111` clone, the diagnostic returned: `notice_active=false`, marker `current`, retry `none`, Messenger schema-compatible `yes`; before/after retry/marker option values were unchanged. Other component checks were deliberately not rerun. This verifies the diagnostic's healthy-state output only; the local clone is not the live production database.
- Local WordPress Debugging page render: button and result panel present **2/2 PASS**. Privacy-context sanitizer retained the expected diagnostic fields and reduced the prefix-presence flag to a boolean **PASS**.
- SHA-256 inventory: `04_RELEASES/VERSIONS/1.2.4.66.a/HASHES/SHA256_MESSENGER_DIAGNOSTIC_2026-11-27.txt`.
