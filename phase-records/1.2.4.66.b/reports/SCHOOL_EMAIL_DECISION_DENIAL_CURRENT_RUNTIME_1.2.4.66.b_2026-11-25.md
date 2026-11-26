# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Училишна службена е-пошта: одбивање на одлука без овластување — .66.b — 2026-11-25

## Околина
Постојниот изолиран WordPress 127.0.0.1:8103, база zmp66b_isolated, префикс z66a_. Кандидатскиот и активниот runtime class-zmp-school-email-access.php имаат ист hash: d8855bc141d5e1f854b751a10420eb90d04af9b6cbcd2f7a3e0da7664be796ce.

## Проверени случаи
Создадено е едно synthetic pending барање меѓу двајца synthetic наставници со заедничко активно училиште и резервирана .example.test адреса.
- Обичен наставник, со точен кориснички ID и обид за approve: одбиен со forbidden; pending редот и audit-настаните непроменети.
- Корисник со zmp_president + subscriber, но без активен мандат: одбиен со forbidden; pending редот и audit-настаните непроменети.
- Administrator со невалиден request-bound nonce на реалниот handle_decision() callback: одбиен со безбедносна проверка; нема одлука или нов audit-настан.
- Финален cleanup го отстрани synthetic request, events, профили, ангажмани, ментори и корисници. Постојниот активен мандат 58/116 остана ист. Дијагностичката опција беше вратена на точните почетни бајти и атрибути. wp db check помина.

Резултат: 5/5 PASS. Fixture: tests/school-email-decision-denial-current-runtime.php; SHA-256 617c50da529ced2b4f978369a19de0e22809a177f9de1f30a6db09eab86b5880. Првиот harness обид запре пред мутација затоа што не поставил WP-CLI корисник; по поставување на локален admin предуслов, финалното извршување помина.

Ова покрива само одлука за pending барање. Поднесување, откривање на веќе одобрена адреса, други негативни улоги/nonce и UI остануваат отворени.
