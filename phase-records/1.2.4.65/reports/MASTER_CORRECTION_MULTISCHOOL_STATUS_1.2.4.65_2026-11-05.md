# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Master Reviewer additive correction — multi-school status — 1.2.4.65

Date: 2026-11-05  
Review type: Read-only evidence reconciliation. This correction does not edit the existing Master reassessment, product source, tests, or site data; it does not replace a UI Reviewer or Test Engineer verdict.

## Disposition

The multi-school acceptance point remains **PARTIAL PASS / OPEN — NOT READY TO CLOSE**. This correction resolves documentation contradictions and narrows claims to their evidence. It does not close the populated-directory UI requirement, convert the anonymous fixture's database delta to a no-mutation PASS, or make a whole-phase acceptance decision.

Preserve President photo/consent PASSes and all their existing evidence. Preserve the `.64` composite runtime **FAIL 115/120**; its scoped passing assertions do not convert the composite result to PASS.

## Evidence-bound current status

| Criterion | Current status | Bound evidence and scope |
|---|---|---|
| Anonymous directory/profile service and shortcode | **PASS 6/6 (behavior only)** | Test Engineer report SHA-256 `D10C0FBCECD33368093BB48148A1C2EC2E76B7424C38E0480270F3DCB4BF0A7B`; fixture `F7412092F0BDC869F2D8E1964E321827BF5593A4CC040CD62A25D14B792126D3`; service `8379706AB7C13553DECB45CE43CA35772C5735CB5D442459D15A54268AE4FC3B`; renderer `255A9673C082F3472DCDEF676DC80E8F63C70AF68B14CD2AC56919F936FD7042`. It covers anonymous service denial, login notice, no school/employment fields and restoration of in-memory user context. This supersedes the existing Master table's **NOT RUN** row for anonymous access; it does not establish exact database no-mutation. |
| Anonymous direct `GET /wp-admin/profile.php` | **PASS, narrow HTTP boundary only** | Test Engineer report SHA-256 `92E28EF3527BD4F6C1ECFD4BE74DC14F1DFEDC5584AB227E12A8B1B7426AC69F`: unauthenticated request returned 302 to `/wp-login.php`, with no private/profile/school/employment markers and observed `Cache-Control: no-store, must-revalidate, no-cache, max-age=0, private`. `Pragma` and `X-Frame-Options` were absent on this response. This does not test an authenticated Teacher boundary, framing policy, or the directory service's own cache headers. |
| Anonymous fixture exact database no-mutation | **INCONCLUSIVE / OPEN** | The anonymous test report records different pre/post full-export checksums (`ab10d0c0…b81981` and `a256910b…c6bfc7`) and no retained pre-run database/option preimage. Debugger report SHA-256 `8FA095C5E2374B301F266891FAE8EAAE4BF74037BC6F00C9A07794EE28326166` traces two expected sanitized denial events to `zmp_debug_events`, but cannot prove that they were the only changes. Keep those diagnostics; do not claim no-mutation or restore speculatively. |
| Employment-directory browser UI | **NOT RUN — required acceptance remains open** | Profile acceptance report SHA-256 `652B2E7E11F97590F5BFCEC955C2623D4835256F09D6E9C53CC0684F0E612B03` records that temporary local page #37 contains `[zmp_employment_directory]` and rendered only as Administrator, showing an empty linked-schools state. This confirms shortcode route presence only. It does not show populated Teacher rows, privacy projection in a browser, or responsive/keyboard behavior. The independent UI report SHA-256 `C37C54ABF0F9C0A35F7D0828A25CAA817708E223CBFE29BE0B6D6C91628B0151` remains a prior blocked/not-run review; it is not superseded by the Admin empty-state observation. |
| AJAX / REST / search / export | **N/A by current source inventory** | Independent Code Review report SHA-256 `009817045F296DEFC77171EC22307942F3BAEA238D5A9C3E53AB604EAD96713A` confirms no feature-specific profile/directory AJAX or REST route and no directory search/export handler. School-email admin-post routes are separate criteria. |
| Directory cache transport | **NOT RUN** | Source maps private/no-store/no-cache behavior. The anonymous profile GET's observed WordPress response header is not transport evidence for the directory response. |
| Per-school profile, employment/status, visibility, school scope and role/privacy runtime | **PASS, scoped** | Preserve the exact-hash source and independent Test Engineer runtime review (`reports/TEST_ENGINEER_MULTISCHOOL_DIRECTORY_SCOPE_1.2.4.65_2026-11-05.md`, SHA-256 `887919A3D5A2D6930187C4B58951EA0B9AB60D38172C2E8E41E0041CD5461E9F`, PASS 24/24), plus the actual Teacher profile save/reload evidence in the profile acceptance report. This does not replace the required rendered directory UI. |

## Page #37 and browser observation

The Lead's current report records temporary WordPress page #37 with the exact employment-directory shortcode and the Administrator's empty linked-schools state. This is a Lead observation, not an independent UI review. No Teacher-populated page content was seen. Page #37 remains a disposable local fixture pending the required UI check and cleanup; do not treat its existence as acceptance or alter other pages.

The latest root snapshot SHA-256 `18BC10E7A31AF5C72BF72BEE53779BE8B6B926CF8398826D5D852D1BE9D75534` records the fresh-session check: despite the Owner's intent to leave a Teacher tab open, the task-owned profile check still identified the Administrator. Do not infer Teacher authentication from intent.

## Corrections to earlier current-status wording

Apply this artifact additively when reconciling the current ledger:

1. Replace the Master reassessment's table status **“Anonymous access denied — NOT RUN”** with **“Anonymous service/shortcode behavior PASS 6/6; exact database no-mutation INCONCLUSIVE”**, citing the bound Test Engineer and Debugger reports above.
2. Replace the privacy map's current Direct Profile URL wording **“direct HTTP denial not separately exercised in `.65`”** with **“unauthenticated direct profile GET PASS: 302 to login, no private markers; authenticated role denial remains unverified.”** Keep its cache row scoped: directory transport headers remain untested.
3. Keep the Master aggregate/direct-route matrix open only for the unresolved authenticated role boundary and applicable runtime checks. Retain the independent source inventory's AJAX/REST/search/export N/A disposition; do not label those surfaces NOT RUN merely because no route exists.
4. The profile acceptance report's opening functional-status paragraph should say **“PARTIAL PASS / OPEN”** and point to its later Teacher save/reload and independent directory 24/24 evidence. The original NOT RUN text is historical and should not imply those accepted checks never occurred.
5. The current directory UI status remains **NOT RUN**: page #37 is observed only as Administrator with no linked-school data; populated Teacher desktop/mobile/keyboard review is still required.

No product defect is inferred from the missing Teacher session. Do not rerun unchanged accepted profile/source/service checks, do not alter President photo/consent records, and do not use this correction as a whole-phase review or Auditor verdict. `.65` remains in progress and not ready for Auditor.
