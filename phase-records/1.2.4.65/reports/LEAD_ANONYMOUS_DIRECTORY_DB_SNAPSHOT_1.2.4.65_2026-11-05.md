## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Lead Runtime Observation — Anonymous Multi-School Directory No-Mutation — 1.2.4.65 — 2026-11-05

## Scope and outcome
Lead executed one unauthenticated browser request to the existing synthetic employment-directory page and compared a full database export fingerprint immediately before and after. The rendered page showed the teacher-login notice and no school/employment/email markers. Normalized database export hashes matched exactly: **no database change detected for this anonymous shortcode GET**.

## Method
- Existing WSL site only: `/home/tonic/zmp-local-sandbox/phase65-test/site`, `http://127.0.0.1:8094`, DB `zmp65_isolated`.
- Route: `GET /?page_id=37`; page #37 contains `[zmp_employment_directory]`.
- Browser: task-owned Edge tab with no WordPress session. Accessibility tree showed `Најавете се со наставнички профил за да ги видите споделените податоци.`; no school names, employment terms, or synthetic emails appeared.
- Before and after the GET, streamed `wp db export - --quiet` directly into `sed '/^-- Dump completed on /d' | sha256sum`. The only normalized line is the dump's time-dependent completion footer; no SQL dump was saved or displayed.
- Before hash: `71991bf0e876e1d9d138a71b2388a5dca56a13744306361c5f57528d3ee84a08`.
- After hash: `71991bf0e876e1d9d138a71b2388a5dca56a13744306361c5f57528d3ee84a08`.
- The Edge task tab was closed after observation. No site data changed and no service/site was created or stopped.

## Limitations and reconciliation
An earlier internal test fixture calls protected service methods directly while anonymous; those calls intentionally append sanitized diagnostic events, as documented by `DEBUGGER_MULTISCHOOL_ANONYMOUS_DB_CHANGE_1.2.4.65_2026-11-05.md`. This run covers the actual externally reachable anonymous shortcode GET and proves no DB export content change for that request. It does not erase or reset the expected diagnostic history. An independent Test Engineer exact-snapshot run is requested before changing the integrated gate disposition.

## Disposition
Lead observation only; preserve the gate as BLOCKED until independent Test Engineer verification and Master reconciliation. President photo/consent PASS evidence is unaffected.

## Method-selection note
An initial `wp db export --stdout` invocation was invalid: WP-CLI rejected the unsupported option and the pipeline hashed an empty stream; that result was discarded. A following unnormalized export hash differed across request because the dump has a time-dependent completion footer. The final evidence uses the documented `wp db export -` STDOUT syntax and removes only that footer line before hashing. The resulting pre/post normalized hashes are identical.
