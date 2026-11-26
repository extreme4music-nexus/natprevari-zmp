# `.66.a` president appointment role policy and ZIP build — 2026-11-26

## Owner rule

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL AND ADDITIVE FOR MEMBERS GENERALLY; THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. OTHER AUTHORIZED ZMP ROLES MAY BE STACKED AND MUST BE PRESERVED.**

## Correction delivered

The administrator is the authorized person who records the first President's mandate. The selected President is a separate non-administrator account. The appointment page's warning previously sounded as if Teacher and other valid ZMP roles disqualified candidates; that wording was incorrect. The form, mandate service, and role-grant path now share the same eligibility rule: require `subscriber + zmp_teacher`, reject Administrator/manage_options, and preserve allowed Moderator, Commission, Testator, Organizer, and President roles. The admin-facing `.66.a` What's New entry now explains the first appointment and the candidate's role requirements.

## Verification

- Focused President eligibility fixture: **18/18 PASS** (central validator, mandate service, and candidate form list); synthetic account cleanup and database integrity: **PASS**. Detailed evidence: `PRESIDENT_APPOINTMENT_ROLE_POLICY_FIX_1.2.4.66.a_2026-11-26.md`.
- PHP syntax: **1,968/1,968 PASS** for packaged PHP files.
- Staging copied from the `.66.a` phase folder. Robocopy file counts: admin 3/3, assets 14/14, includes 49/49, vendor 2,156/2,156; root plugin entry point and license copied separately.
- ZIP archive listing/integrity: **PASS** (`tar -tf`); 2,244 packaged files; expanded bytes 19,590,705.
- Staged source paths and ZIP file paths: **2,244/2,244 exact match**.
- Packaged SHA-256 matched phase source for the plugin entry point, President admin UI, mandate admin UI, mandate service, and license.
- ZIP SHA-256: `EE660D4F521BD655CE79B61E37995AF5933261B45C0FB84882AF547AD8447CD6`; size 7,137,152 bytes.

## Candidate ZIP

`04_RELEASES/VERSIONS/1.2.4.66.a/ZIP/zmp-competitions-1.2.4.66.a-2026-11-26-role-policy-fix.zip`

This is an installable **test candidate** for the owner. Items 1–5 retain their exact-scope PASS evidence and the President appointment policy has the focused PASS above. The full regression of the current source (item 6) and owner installation/visual confirmation remain open. GitHub readback PASS at commit ed434feabfcf965c0ed873a0eb39eaad892db803 on main and version/1.2.4.66.a; the remote ZIP hash matches EE660D4F521BD655CE79B61E37995AF5933261B45C0FB84882AF547AD8447CD6. This package is not represented as full-phase acceptance. The earlier archive named `*-president-appointment.zip` was an intermediate build attempt; use only the `*-role-policy-fix.zip` archive linked above.

No production website, production users, or `wp-config.php` were changed.
