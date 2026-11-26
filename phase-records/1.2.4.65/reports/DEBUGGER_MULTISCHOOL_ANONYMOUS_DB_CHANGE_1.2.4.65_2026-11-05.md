## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Debugger report — anonymous denial database change — 1.2.4.65

Date: 2026-11-05. Read-only source-path diagnosis; no live test database was accessed, no experiment rerun, no option was restored or edited.

## Bound evidence
- Test fixture: `tests/phase65-multischool-anonymous-denial.php`, SHA-256 `F7412092F0BDC869F2D8E1964E321827BF5593A4CC040CD62A25D14B792126D3`.
- Product profile service: `includes/class-zmp-mentor-school-profile.php`, SHA-256 `8379706AB7C13553DECB45CE43CA35772C5735CB5D442459D15A54268AE4FC3B`.
- Debug logger: `includes/class-zmp-debug.php`, SHA-256 `ED252BEF7763DB792AE684117586693A688EA952412966F48CBEB2B2F5BCBEC6`.
- Test Engineer report: `reports/TEST_ENGINEER_MULTISCHOOL_ANONYMOUS_DENIAL_1.2.4.65_2026-11-05.md`, SHA-256 `D10C0FBCECD33368093BB48148A1C2EC2E76B7424C38E0480270F3DCB4BF0A7B`.

## Product path and expected persistent effect
1. Fixture changes current user only in memory with `wp_set_current_user(0)`, calls `get_employment_directory(0,0)`, then `get_profile(127,17)`, renders the shortcode while anonymous, and restores the original current user. It contains no INSERT/UPDATE/DELETE/option-write call.
2. The anonymous directory service returns its generic `forbidden` error through the service `error()` helper. The owner-profile service does the same when the anonymous viewer does not match owner ID 127.
3. `error()` invokes `debug_event('warning', 'School profile operation failed.', {outcome: failed, error_code: forbidden})`.
4. `ZMP_Debug::log()` sanitizes the context and `append_event()` appends each warning to `ZMP_Debug::OPTION` (`zmp_debug_events`) using `add_option()` or `update_option()`. It caps retained events at 500. The error logger emits only sanitized event fields. The fixture's two expected forbidden warnings are therefore expected to persist in that diagnostic option. The private values and email are not passed into either diagnostic call.
5. The shortcode's anonymous branch returns a static login notice without invoking the directory service. `wp_set_current_user()` does not itself persist a WordPress account/profile change.

## Conclusion
- **Confirmed:** the two service-denial calls can write sanitized warning events to `wp_options.zmp_debug_events`. This is expected product diagnostic behavior, not evidence of profile, school-engagement, or account mutation.
- **No product defect confirmed.** Source-path review found no other database write in the fixture's explicit statements or these anonymous code paths.
- The Test Engineer observed database-export checksums change from `ab10d0c0762e85b129d05e5baaf09af80b3f87398dfe6a4b6ab0d6e0b0b81981` to `a256910b3c3387d7183a3006f9344151236b50bdaefd003887ce6eccb1c6bfc7`, and reported two sanitized expected `forbidden` diagnostics. Since no pre-run option snapshot or export was retained, it is **not possible to prove the exact prior option value or exclude unrelated runtime changes from the checksum alone**. Preserve the existing debug records; do not delete or reconstruct the option speculatively. Data-mutation verification remains **BLOCKED/inconclusive** even though product behavior passed 6/6 and `wp db check` passed before/after.

## Next safe step
Record the expected diagnostic-option write and checksum limitation in the multi-school acceptance evidence. No repeat of this unchanged fixture is warranted. If future runtime work requires exact no-mutation proof, snapshot the exact affected option before invocation and restore that value in a reviewed cleanup path; that is a future bounded test design, not a reason to rewrite current database state.
