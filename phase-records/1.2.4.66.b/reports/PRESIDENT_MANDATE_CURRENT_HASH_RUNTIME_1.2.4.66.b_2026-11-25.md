## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Претседателски мандати — тековен `.66.b` код на локалниот runtime — 2026-11-25

## Околина
Проверено на постојниот изолиран WordPress `127.0.0.1:8103`, база `zmp66b_isolated`, префикс `z66a_`; runtime-от е усогласен со кандидатот 69/69 source-файлови. Постојниот активен мандат `id=58`, `user_id=116` не беше изменет. Production и надворешни услуги не беа допрени.

## Резултат
- `admin_post_zmp_president_mandate_appoint`: PASS — синтетички subscriber доби закажан мандат по активниот, еден `appointed` audit-настан и ја задржа subscriber улогата. Корисникот, мандатот и audit-редот се исчистени.
- `admin_post_zmp_president_mandate_cancel`: PASS — закажаниот синтетички мандат е отповикан со очекуваниот actor/reason и `appointed`/`revoked` настани; fixture е исчистен.
- `admin_post_zmp_president_mandate_revoke`: PASS — активниот синтетички мандат е отповикан; President улогата е отстранета, subscriber вратен, audit-настаните точни; fixture е исчистен.
- Сите fixtures пријавија exact cleanup; не остана тест-мандат или корисник. `wp db check` помина.
- WP-CLI redirect warning е очекуван од handler-ите со `wp_safe_redirect()`/`exit`; fixtures ги извршија shutdown assertions и излегоа со 0.

## Тековни хешеви
- Admin handler: `69828f4de4a29772d985e4957c87cec61a0e06925bd95cd0710f20646b7c85e6`
- Mandate service: `42bcba1b7be30beceb38c150da2e7b40dc3cc1aaef90d0c88239c0bc89b292dd`
- Appointment fixture: `e17f0cc860d9485a2d4b4b0122234af52f9a89c4f7dcb6420f2720c083517c38`
- Scheduled cancellation fixture: `be02046d65bef161f1dff666f2f66adf6ee13edf484785649426633cb60dc117`
- Active revocation fixture: `2339cdccf2555f7f4bd4eac092b38838ed670dda80eb22f8a2b226a9d4041b39`

Ова го потврдува позитивниот callback тек на тековниот код, но не е визуелен админ-преглед и го затвора само наведениот appointment role/nonce no-mutation пар; не ги затвора другите негативни варијанти, визуелниот админ-преглед или целиот фазен gate.

## Негативни права и no-mutation — дополнителен PASS
Тековниот appointment handler беше повикан со два негативни случаи: subscriber со валиден nonce и administrator со невалиден nonce. Двата беа одбиени; вкупниот број mandate и audit редови остана непроменет (1/1 пред и по секој случај). Fixture-от пријави exact cleanup; read-only проверката потврди дека нема останат synthetic denial user. `wp db check` помина. Fixture SHA-256: `8358536eea62d9b52cf41864cad2c693750f67dda9dfbf72c815b0455bde51d5`.

