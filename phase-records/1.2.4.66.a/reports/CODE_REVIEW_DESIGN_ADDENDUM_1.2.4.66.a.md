# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Independent Design Addendum Review — ZMP 1.2.4.66.a

Date: 2026-11-11  
Scope: Only the additive section “Additive design correction and migration/rollback procedure — 2026-11-11” in `DESIGN_1.2.4.66.a.md`, compared with the exact current passkey store/entrypoint sources and `.66.a` roadmap criteria 2 and 4. No edits to design/product, tests, browser, database, or services.

## Reviewed identities

- New design SHA-256: `E78FB709EEAC00A7E6C02DBDA6E98907B82FED1FA49C4F5D6C03E8E1BF62C3A9`
- Current `includes/class-zmp-passkey-store.php` SHA-256: `F9038DE6499B844529118FE98AB8BD32FE30414BD1061BC35650F638308D02DB`
- Current `zmp-competitions.php` SHA-256: `C0F6CD135F7FBEFB09847FA047F76828736C30C0FA802DAD3F06FDD9C3A4989F`
- `PROJECT_ROADMAP.md` SHA-256: `6FF57B9AC120E7982C1A0F2CE4E519C531F974ACBDA8A60213562D0627105074`

## Verdict

**SCOPED DESIGN REVIEW PASS** for the additive migration/rollback procedure against roadmap `.66.a` criteria 2 and 4. The new section resolves the prior review’s concrete design gap and status inconsistencies in its scope. This PASS is for design documentation only; migration/rollback runtime remains NOT RUN, and the previous report’s verdict for its older design hash is preserved unchanged.

## Evidence and notes

- The roadmap requires owner-supplied hosting compatibility assessment and documented migration/rollback (criterion 2), plus compatibility and rollback verification (criterion 4). The addendum distinguishes the Owner-reported target-host PHP/MySQL/HTTPS/self-test facts from the separate synthetic WSL PHP/WP runtime and explicitly avoids claiming production inspection/deployment. It also clarifies the locally bundled vendor dependency versus absent global Composer/production deployment.
- The addendum accurately describes `ZMP_Passkey_Store::init()`/entrypoint wiring to `plugins_loaded` schema installation. The store uses its own `SCHEMA_VERSION = '3'`, dedicated credential/challenge tables, `dbDelta()`, structural/index validation, and a distinct `zmp_passkey_schema_version` marker. `schema_is_compatible()` requires both exact structure and marker. The design’s warning that table/index changes may partially apply, while passkey operations fail closed pending normal idempotent repair, matches the source control flow.
- Marker write/readback and post-write structure validation are described accurately. The procedure does not advise manually pre-setting the marker, dropping tables, or recreating data during repair.
- The entrypoint registers no passkey-specific table-drop deactivation/uninstall behavior; the addendum’s routine rollback preserves the additive tables/marker, treats them as inert under the previous code, and separates future credential purge policy from rollback. It recommends a verified backup, limits full restore because it can discard later unrelated writes, and gives a synthetic-site rollback/reinstall verification sequence.
- No concrete omission or source/design contradiction was found within the requested addendum scope. Its final statement correctly keeps migration/rollback runtime, current-hash functional checks, browser ceremonies, Master, Auditor, and release gates OPEN/NOT RUN.

This is not a source review, runtime test, package review, or phase acceptance.
