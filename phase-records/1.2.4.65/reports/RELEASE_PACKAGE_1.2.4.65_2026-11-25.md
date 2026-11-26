# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# `.65` WordPress install ZIP — 2026-11-25

## Artifact

- ZIP: `04_RELEASES/VERSIONS/1.2.4.65/ZIP/zmp-competitions-1.2.4.65-2026-11-25.zip`
- SHA-256: `D6DF6E9E466FB3BECCF91D0F151C526905931AB80728A957C5D42D4B0755698C`
- Size: 5,173,371 bytes
- Source: the current `.65` phase plugin tree, packaged under the WordPress directory `zmp-competitions/`.
- Contents: 755 files from `admin/`, `assets/`, `includes/`, `vendor/`, and `zmp-competitions.php`. Phase reports, tests, temporary files, and project documentation are excluded from the install ZIP.
- File manifest: `04_RELEASES/VERSIONS/1.2.4.65/HASHES/SOURCE_SHA256_1.2.4.65-2026-11-25.txt` (755 SHA-256 entries).
- Verification: reopened the archive and compared every entry byte-for-byte with its phase source; **755/755 matched**. PHP syntax validation: **625/625 PASS**.

## Link to 2026-11-24 PASS evidence

The phase source contains the Messenger migration fix with SHA-256 `BABFE20C900A4421DFD5154939EEE84BEB9824A80FF3B56639B8E9FC202BC655`, matching both the `.65` migration report and the isolated `.65` migration runtime at `site-schema-20261102` (port 8096). The role-stack, President, school-profile, school-email, and subject-route focused PASS evidence remains in the dated `.65/reports/*2026-11-24*.md` records; the exact scope of each PASS remains as stated there.

During packaging reconciliation, the general review clone at `site` (port 8095) was found to differ from the current phase source in 14 PHP files. It is an older/stale copy and was not used as the ZIP source. The `.65` subject-test clone (port 8097) matches the current phase PHP tree except for `class-zmp-role-messenger.php`, whose separately tested migration fix is present in the phase source and in the dedicated migration runtime. No server, database, production site, YouTube, or Drive state was changed.

## Scope statement

This record certifies package integrity against the current `.65` phase source and PHP syntax validation. It does not turn focused runtime checks into a claim that every possible plugin workflow or the entire historic phase gate set received a package-wide end-to-end run. Existing `.65` PASS evidence is preserved and limited to its documented scopes. Main `PROJECT_ROADMAP.md` was not changed. The prior undated `.65` ZIP was left untouched.
