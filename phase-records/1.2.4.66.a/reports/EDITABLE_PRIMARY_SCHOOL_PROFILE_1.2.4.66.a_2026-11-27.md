**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL AND ADDITIVE FOR MEMBERS GENERALLY; THE PRESIDENT MUST HAVE BOTH `subscriber` AND `zmp_teacher`. OTHER AUTHORIZED ROLES MAY BE STACKED WITHOUT BREAKING SCHOOL PROFILE FUNCTIONS.**

# Editable primary school fields — `.66.a` — 2026-11-27

## Finding and correction

An existing Teacher/President profile had `readonly` on the primary school and municipality inputs. The profile save handler also explicitly rejected a changed primary-school key. A profile that already had a linked mentor record could therefore not complete the required school details or correct them.

The inputs are now editable for an authorized profile editor. The server still validates the school and municipality lengths and derives a valid region. It updates the mentor record and, when one exists, updates the same primary school engagement row inside the existing transaction. The stable engagement ID keeps subject and grade assignments attached. A duplicate school-key collision remains rejected safely. An incomplete linked profile gets the primary subject/grade controls so the first save can satisfy the approved-catalog assignment requirement.

## Verification

- Runtime: existing local clone `http://localhost:8111`, database `zmp_debug_prodclone_20261125`, prefix `wp_yqqdt5_`; no new server was started.
- The edited phase file was copied to the already running clone. Phase and clone support-file SHA-256: `eacdae0a838795615e18bb061f6e760d718ca8ad6e57c0a8a3c2df86d17e0ef0`.
- New regression fixture: `tests/phase66a-editable-primary-school.php`.
- Fixture result: **5/5 PASS**. It verified a stacked synthetic `subscriber + zmp_teacher + zmp_president + administrator` profile, editable rendered fields and initial assignment controls, successful save from blank school data, school/municipality correction, and preservation of the same engagement ID and subject/grade linkage.
- Exact synthetic cleanup restored the before/after counts for users, mentors, school engagements, assignments, and grades.
- PHP syntax check: **PASS** for the changed implementation and the regression fixture.
- No production site, real account, or production database was accessed or changed. Owner installation/browser confirmation remains separate.

## Release status

This correction is included in the `.66.a` phase source and its installable ZIP candidate. The archive and phase-folder parity are recorded in the release manifest and ZIP-build report. This focused test does not replace unrelated `.66.a` acceptance items.
