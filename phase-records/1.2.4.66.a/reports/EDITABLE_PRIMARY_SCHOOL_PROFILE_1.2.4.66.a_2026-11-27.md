**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL AND ADDITIVE FOR MEMBERS GENERALLY; THE PRESIDENT MUST HAVE BOTH `subscriber` AND `zmp_teacher`. OTHER AUTHORIZED ROLES MAY BE STACKED WITHOUT BREAKING SCHOOL PROFILE FUNCTIONS.**

# Editable primary school fields — `.66.a` — 2026-11-27

## Finding and correction

An existing Teacher/President profile had `readonly` on the primary school and municipality inputs. The profile save handler also explicitly rejected a changed primary-school key. A linked mentor profile therefore could not complete missing school details or correct them.

The inputs are now editable for an authorized profile editor. The server still validates school and municipality lengths and derives a valid region. It updates the mentor record and, when one exists, updates the same primary school engagement row inside the existing transaction. The stable engagement ID keeps subject and grade assignments attached. A duplicate school-key collision remains rejected safely. An incomplete linked profile gets primary subject/grade controls so the first save can satisfy the approved-catalog assignment requirement.

## Verification on the exact package

- Runtime: existing local clone `http://localhost:8111`, database `zmp_debug_prodclone_20261125`, prefix `wp_yqqdt5_`. No new server was started.
- A full local database export and plugin-folder backup were created before replacing the old clone plugin. No production website or production data was accessed.
- Installed ZIP candidate `zmp-competitions-1.2.4.66.a-2026-11-27-editable-school-profile.zip` into the already-running clone using WP-CLI. Active version: `1.2.4.66.a`.
- Active clone runtime parity against the `.66.a` phase/package: **2,244/2,244 exact file hashes PASS**.
- Regression fixture: `tests/phase66a-editable-primary-school.php`; executed after the full ZIP installation: **5/5 PASS**. It verified the stacked synthetic `subscriber + zmp_teacher + zmp_president + administrator` profile, editable fields and first assignment controls, save from blank school data, correction of school and municipality, and preservation of the same engagement ID and subject/grade linkage.
- Exact synthetic cleanup restored the before/after counts for users, mentors, school engagements, assignments and grades.
- PHP syntax: changed implementation and regression fixture **PASS**; packaged PHP lint **1,968/1,968 PASS**.
- Clone HTTP response: **200**. Clone database integrity check: **PASS**.
- ZIP integrity and package parity: **2,244/2,244 PASS**. ZIP SHA-256: `65e2f59611170897708816103d5691fea20e7dc1b27675ac22fc1ad6ffc73b95`.

## Release status

This correction is included in the `.66.a` phase source and the current installable ZIP candidate. The package and GitHub synchronization are recorded in `reports/EDITABLE_PRIMARY_SCHOOL_PROFILE_ZIP_BUILD_1.2.4.66.a_2026-11-27.md` and `reports/GITHUB_SYNC_READBACK_EDITABLE_PRIMARY_SCHOOL_1.2.4.66.a_2026-11-27.md`. This focused test does not replace unrelated `.66.a` acceptance items. Owner installation on the official website remains pending.
