# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Independent review — admin CSS cache-key fix — `.66.a`

**Date:** 2026-11-12  
**Role:** Code Reviewer (read-only)  
**Verdict:** **STATIC REVIEW PASS for the cache-key change at the exact hashes below.**

## Exact reviewed hashes

- `zmp-competitions.php` — SHA-256 `E97C178329FCAD3662508D7D492C4CFEA729A7F5B927CD73A829206DBF03BA69`
- `assets/css/zmp-admin.css` — SHA-256 `A3BD3F9DF6AA91C5973A79AED5254BBCA0AC6AC4E4D982B81F1DD482A490BC83`

## Review result

`zmp_competitions_admin_assets()` builds the filesystem path from `ZMP_PLUGIN_DIR`, which is defined with WordPress `plugin_dir_path(__FILE__)`, and uses the existing `ZMP_PLUGIN_URL` asset URL. The stylesheet handle, dependency list, and enqueue scope remain unchanged. When the CSS file exists, its modification time becomes the style version so a changed stylesheet gets a new cache key; if it is absent, the existing `ZMP_VERSION` value remains the fallback. The `is_file()` guard avoids calling `filemtime()` for a missing asset. This change is limited to admin CSS cache invalidation and does not alter script versions or public assets.

No path or compatibility defect was found by static inspection for the supported WordPress filesystem/path conventions. The exact-hash CSS file is unchanged from the reviewed mobile actions CSS candidate.

## Limits

No browser/network cache behavior, asset serving, runtime, test, or UI was exercised. This is a bounded static review of the enqueue change only; it is not a whole-phase or release acceptance verdict.