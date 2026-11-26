# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# .66.a phase source, local test copy, and ZIP parity - 2026-11-25

## Result

The project phase folder contains the current .66.a fixes. The main changed source files in that phase folder match the local plugin copy used on the existing 127.0.0.1:8100 clone. The existing release ZIP is older than the phase folder and is not the current test package.

## Phase source and active local copy

Compared .66.a/includes and .66.a/assets with /home/tonic/zmp65-review-20261124/site-66a-subject-test/wp-content/plugins/zmp-competitions/:

- The only extra files in the local server copy are includes/class-zmp-entry-youtube-fields.php and assets/js/zmp-entry-youtube-fields.js.
- Those files are absent from the .66.a phase source and are not referenced by its explicit plugin loader. The phase-loaded class-zmp-youtube.php remains present.
- Apart from those two unreferenced local-copy files, the compared includes and assets trees match. The main plugin entrypoint matches exactly.

| File | .66.a phase source and active local copy SHA-256 | Result |
|---|---|---|
| zmp-competitions.php | 2f748383e511f06d6910d958027132c8942d444512e0fcc2b5ebbbffefb42c26 | MATCH |
| includes/class-zmp-support.php | 49fa8ffd590efd1bb26d51cbe7ad000d33cff807f8260395580243293079e1e3 | MATCH |
| includes/class-zmp-debug.php | c3c0771ff72a35a16bb5af0f6ace079128c6c19f756929cfffa142920ada5a28 | MATCH |
| includes/class-zmp-school-profile-ui.php | b845de01e03ecb68eb634ccb1ed460220340cecf4722bdd64fdf9e35c68d9475 | MATCH |

The .66.a regression fixtures are in the project phase folder under tests/: inherited-fix regression, stale-schema retry, partial-profile-schema fail-closed, and stacked-President first-time setup. Their test results and exact source hashes are recorded in INHERITED_65_FIXES_AND_66A_OPEN_SCOPE_1.2.4.66.a_2026-11-25.md.

## Existing release ZIP

Existing archive: 04_RELEASES/VERSIONS/1.2.4.66.a/ZIP/zmp-competitions-1.2.4.66.a.zip
Archive SHA-256: 9875fc6255d2ca17c76bb33e8f55544747be5d0b7f6bfd8d75ad359a06caded8.

Inside that archive, class-zmp-support.php, class-zmp-debug.php, and class-zmp-school-profile-ui.php have hashes a44ffaf243e185abb32b0e1b4706ef9febee6ec1764710d09ecaf8de60487d81, ed252bef7763db792ae684117586693a688ea952412966f48cbeb2b2f5bcbec6, and ffdc2c810280924e5217cdb4d4083d5b49c6ee02d8194479278caecd0f450f26. Each differs from the current .66.a phase source. This ZIP is stale and is not the current phase-folder build.

## .66.b phase files

The .66.b project folder contains the corresponding carried fixes. Its support, Debugging, and profile UI hashes are 49fa8ffd590efd1bb26d51cbe7ad000d33cff807f8260395580243293079e1e3, f6850be2957ccc285736b7b1b9483c9ce633270ae725f24e09ec21bba95dac6b, and b845de01e03ecb68eb634ccb1ed460220340cecf4722bdd64fdf9e35c68d9475. These are bound to its local regression results in INHERITED_65_FIXES_AND_66B_OPEN_SCOPE_1.2.4.66.b_2026-11-25.md. The local server was returned to .66.a after .66.b checks.

## Scope

Read-only source/archive comparison. No plugin code, database, account, server, browser, or release ZIP was changed. The final .66.a ZIP must be built from the project phase folder after remaining requested checks and compared against that folder before delivery.
