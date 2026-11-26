# `.66.a` President role-stack fix ZIP — 2026-11-27

<h1><strong>🔴🔴🔴 ОБАВЕЗНО: ПРОЧИТАЈ ГО ОВА ПРАВИЛО 5 ПАТИ ПРЕД ДА ПОЧНЕШ РАБОТА 🔴🔴🔴</strong></h1>

**СЕКОЈ ЧЛЕН МОРА ДА ИМА `subscriber`. ПРЕТСЕДАТЕЛОТ МОРА ДА ИМА `subscriber` И `zmp_teacher`. АДМИНИСТРАТОРСКИТЕ И ДОЗВОЛЕНИТЕ ЗМП УЛОГИ МОЖЕ ДА СЕ КОМБИНИРААТ И МОРА ДА СЕ ЗАЧУВААТ.**

## Candidate package

- File: `04_RELEASES/VERSIONS/1.2.4.66.a/ZIP/zmp-competitions-1.2.4.66.a-2026-11-27-president-role-stack-fix.zip`
- SHA-256: `23afe3a03b8687383036a10a7508d47ad6c80779023744e11686aa2bc3310ee2`
- Size: 7,113,892 bytes; packaged files: 2,244.
- Archive integrity: **PASS**; package path count equals staged runtime source: **2,244/2,244**.
- Focused eligibility and candidate-list matrix: **21/21 PASS** on the existing local clone (`http://localhost:8111`); full stacked Administrator appointment **PASS**; role/capability preservation and cleanup **PASS**.
- PHP syntax checks: all four changed runtime PHP files **PASS**.

## Scope

Allows an appointee with `administrator`/`manage_options` only when the required `subscriber + zmp_teacher` and the approved role-stack checks pass. The candidate dropdown now includes that account. The form preserves all existing roles. Quick role editing still cannot bypass the mandate process.

## Current limitation

This ZIP corrects the verified eligibility and dropdown rejection and fixes a matching local `TypeError` in the appointment grant path (`$roles` was undefined and the postcondition rejected `manage_options`). The exact production stack was unavailable; owner installation/retry is still needed to confirm the live-site result.

No new server was started. The existing `:8111` local clone was used; production user data was not changed.
