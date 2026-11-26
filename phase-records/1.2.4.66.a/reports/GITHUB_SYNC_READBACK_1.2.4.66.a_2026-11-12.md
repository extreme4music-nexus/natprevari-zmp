# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# GitHub candidate synchronization and readback — ZMP 1.2.4.66.a

Date: 2026-11-12 (Europe/Skopje)

Status: **CANDIDATE SOURCE/PACKAGE SYNC VERIFIED; NOT RELEASED; AUDITOR NOT ACCEPTED**.

- Private repository: `extreme4music-nexus/zmp-competitions`.
- New branch: `version/1.2.4.66.a`.
- Base: verified `.64` commit `b66e70ecdc78010001c270c329298e2f2cbbc920`. Remote `.65` branch was absent; no continuous `.65` lineage is claimed.
- Candidate commit: `067059be2655109d9eb413344e1680bc48e85623` (`chore(release): sync .66.a candidate package and provenance`). No force push; branch was previously absent.
- Remote readback: `git fetch` and `git ls-remote` returned the same commit SHA as local HEAD (`067059be2655109d9eb413344e1680bc48e85623`). The commit tree contains exactly 2,222 files under `zmp-competitions-1.2.4.66.a/`. The archive Git blob ID resolves identically from the commit tree and local ZIP (`a6cf342c05dfc6027012d1ef982d58c52f1a8040`).
- ZIP in branch: `zmp-competitions-1.2.4.66.a.zip`, SHA-256 `9875FC6255D2CA17C76BB33E8F55544747BE5D0B7F6BFD8D75AD359A06CADED8`.
- SHA-256 inventory in branch: `SOURCE_SHA256_1.2.4.66.a.txt`, 2,222 entries; inventory SHA-256 `7F04BF6F9708DDA0AEA329DF9DD5A0A63627919E35ED56794AF095A6226818C2`.
- The Git fetch completed and remote ref readback matched, but the local NAS worktree emitted a post-fetch `multi-pack-index/geometric repack` permission warning. This did not change the verified remote ref or commit identity; it is recorded as a local Git maintenance warning.

The branch is a versioned candidate snapshot, not phase acceptance. Auditor report SHA-256 `84B63D8C190E99A2D87D151BDAC3EF46CE7BA629763F88768DC48E12ABB2ABE6` states **NOT ACCEPTED**. Outstanding items remain as recorded in the current gate report and snapshot.
