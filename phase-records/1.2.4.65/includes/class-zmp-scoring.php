<?php
if (!defined('ABSPATH')) {
    exit;
}

class ZMP_Scoring {

    /**
     * Пресметка на награда според освоените поени (MK / SQ)
     */
    public static function get_award_details($points, $lang = 'mk') {
        $points = floatval($points);

        if ($points <= 60.00) {
            return array(
                'key'      => 'participation',
                'name_mk'  => 'Диплома за учество',
                'name_sq'  => 'Diplomë për pjesëmarrje',
                'label'    => ($lang === 'sq') ? 'Diplomë për pjesëmarrje' : 'Диплома за учество',
                'badge'    => 'secondary'
            );
        } elseif ($points <= 75.00) {
            return array(
                'key'      => 'third_prize',
                'name_mk'  => 'Трета награда',
                'name_sq'  => 'Çmimi i tretë',
                'label'    => ($lang === 'sq') ? 'Çmimi i tretë' : 'Трета награда',
                'badge'    => 'bronze'
            );
        } elseif ($points <= 90.00) {
            return array(
                'key'      => 'second_prize',
                'name_mk'  => 'Втора награда',
                'name_sq'  => 'Çmimi i dytë',
                'label'    => ($lang === 'sq') ? 'Çmimi i dytë' : 'Втора награда',
                'badge'    => 'silver'
            );
        } else { // 91 - 100
            return array(
                'key'      => 'first_prize',
                'name_mk'  => 'Прва награда',
                'name_sq'  => 'Çmimi i parë',
                'label'    => ($lang === 'sq') ? 'Çmimi i parë' : 'Прва награда',
                'badge'    => 'gold'
            );
        }
    }

    /**
     * Автоматска пресметка на пласман за следно ниво според правилата:
     * - Училишно -> Општинско: Топ 3 ученици од секое училиште во неговата општина/регион
     * - Општинско -> Регионално: Топ 3 ученици од секоја општина во нејзиниот регион
     * - Регионално -> Државно: највисоко рангираните (вклучувајќи изедначувања) од секој регион со најмалку 96 бодови
     */
    public static function process_phase_qualifications($phase, $competition_id = 0) {
        global $wpdb;
        $table_attempts = $wpdb->prefix . 'zmp_attempts';
        $table_students = $wpdb->prefix . 'zmp_students';
        $competition_id = absint($competition_id);
        if (!$competition_id) { $competition_id = absint($wpdb->get_var("SELECT id FROM {$wpdb->prefix}zmp_competitions WHERE is_active=1 ORDER BY id DESC LIMIT 1")); }
        if (!$competition_id || !in_array($phase, array('school','municipal','regional'), true)) { return false; }

        $wpdb->query($wpdb->prepare("UPDATE $table_attempts SET qualified_next_stage=0 WHERE competition_id=%d AND phase=%s", $competition_id, $phase));
        for ($grade = 6; $grade <= 9; $grade++) {
            if ($phase === 'school') {
                $groups = $wpdb->get_results($wpdb->prepare(
                    "SELECT DISTINCT s.region,s.municipality,s.school_name FROM $table_students s JOIN $table_attempts a ON s.id=a.student_id WHERE a.competition_id=%d AND a.phase=%s AND a.grade=%d AND a.status='completed'",
                    $competition_id, $phase, $grade
                ));
                foreach ($groups as $group) {
                    if (!ZMP_Regions::valid_region($group->region)) { continue; }
                    $rows = $wpdb->get_results($wpdb->prepare(
                        "SELECT a.id,a.student_id FROM $table_attempts a JOIN $table_students s ON s.id=a.student_id WHERE a.competition_id=%d AND a.phase=%s AND a.grade=%d AND a.status='completed' AND s.region=%s AND s.municipality=%s AND s.school_name=%s ORDER BY a.total_points DESC,a.completed_at ASC,a.id ASC LIMIT 3",
                        $competition_id, $phase, $grade, $group->region, $group->municipality, $group->school_name
                    ));
                    foreach ($rows as $row) {
                        $wpdb->update($table_attempts, array('qualified_next_stage'=>1), array('id'=>$row->id));
                        $wpdb->update($table_students, array('current_phase'=>'municipal','qualified_for_next'=>1), array('id'=>$row->student_id));
                    }
                }
            } elseif ($phase === 'municipal') {
                $groups = $wpdb->get_results($wpdb->prepare(
                    "SELECT DISTINCT s.region,s.municipality FROM $table_students s JOIN $table_attempts a ON s.id=a.student_id WHERE a.competition_id=%d AND a.phase=%s AND a.grade=%d AND a.status='completed'",
                    $competition_id, $phase, $grade
                ));
                foreach ($groups as $group) {
                    if (!ZMP_Regions::valid_region($group->region)) { continue; }
                    $rows = $wpdb->get_results($wpdb->prepare(
                        "SELECT a.id,a.student_id FROM $table_attempts a JOIN $table_students s ON s.id=a.student_id WHERE a.competition_id=%d AND a.phase=%s AND a.grade=%d AND a.status='completed' AND s.region=%s AND s.municipality=%s ORDER BY a.total_points DESC,a.completed_at ASC,a.id ASC LIMIT 3",
                        $competition_id, $phase, $grade, $group->region, $group->municipality
                    ));
                    foreach ($rows as $row) {
                        $wpdb->update($table_attempts, array('qualified_next_stage'=>1), array('id'=>$row->id));
                        $wpdb->update($table_students, array('current_phase'=>'regional','qualified_for_next'=>1), array('id'=>$row->student_id));
                    }
                }
            } else {
                // Every region can send its top score for this grade; ties within the region share the place.
                $regions = $wpdb->get_col($wpdb->prepare(
                    "SELECT DISTINCT s.region FROM $table_students s JOIN $table_attempts a ON s.id=a.student_id WHERE a.competition_id=%d AND a.phase=%s AND a.grade=%d AND a.status='completed' AND s.region<>''",
                    $competition_id, $phase, $grade
                ));
                foreach ((array)$regions as $region) {
                    if (!ZMP_Regions::valid_region($region)) { continue; }
                    $top_score = $wpdb->get_var($wpdb->prepare(
                        "SELECT MAX(a.total_points) FROM $table_attempts a JOIN $table_students s ON s.id=a.student_id WHERE a.competition_id=%d AND a.phase=%s AND a.grade=%d AND a.status='completed' AND s.region=%s",
                        $competition_id, $phase, $grade, $region
                    ));
                    if ((float)$top_score < 96) { continue; }
                    $rows = $wpdb->get_results($wpdb->prepare(
                        "SELECT a.id,a.student_id FROM $table_attempts a JOIN $table_students s ON s.id=a.student_id WHERE a.competition_id=%d AND a.phase=%s AND a.grade=%d AND a.status='completed' AND s.region=%s AND a.total_points=%f ORDER BY a.completed_at ASC,a.id ASC",
                        $competition_id, $phase, $grade, $region, (float)$top_score
                    ));
                    foreach ((array)$rows as $row) {
                        $wpdb->update($table_attempts, array('qualified_next_stage'=>1), array('id'=>$row->id));
                        $wpdb->update($table_students, array('current_phase'=>'state','qualified_for_next'=>1), array('id'=>$row->student_id));
                    }
                }
            }
        }
        return true;
    }

    /**
     * Извоз на резултати во Excel (CSV формат со UTF-8 BOM поддршка за кирилица и албански карактери)
     */
    public static function export_to_excel_csv($phase = 'all', $grade = 'all', $region = '', $municipality = '', $school = '') {
        global $wpdb;
        $table_attempts = $wpdb->prefix . 'zmp_attempts';
        $table_students = $wpdb->prefix . 'zmp_students';
        $table_mentors  = $wpdb->prefix . 'zmp_mentors';

        $where = array("1=1");
        $params = array();

        if ($phase !== 'all') {
            $where[] = "a.phase = %s";
            $params[] = $phase;
        }
        if ($grade !== 'all') {
            $where[] = "a.grade = %d";
            $params[] = intval($grade);
        }
        if (ZMP_Regions::valid_region($region)) { $where[] = 's.region=%s'; $params[] = sanitize_key($region); }
        if ($municipality !== '') { $where[] = 's.municipality=%s'; $params[] = sanitize_text_field($municipality); }
        if ($school !== '') { $where[] = 's.school_name LIKE %s'; $params[] = '%' . $wpdb->esc_like(sanitize_text_field($school)) . '%'; }

        $where_sql = implode(' AND ', $where);
        $query = "SELECT a.*, s.first_name, s.last_name, s.grade, s.language, s.school_name, s.municipality, s.region, s.access_code, m.full_name as mentor_name, m.email as mentor_email, m.phone as mentor_phone 
                  FROM $table_attempts a 
                  JOIN $table_students s ON s.id = a.student_id 
                  LEFT JOIN $table_mentors m ON m.id = s.mentor_id 
                  WHERE $where_sql 
                  ORDER BY a.phase ASC, a.grade ASC, a.total_points DESC";

        if (!empty($params)) {
            $results = $wpdb->get_results($wpdb->prepare($query, $params));
        } else {
            $results = $wpdb->get_results($query);
        }

        $filename = "ZMP_Rezultati_" . date('Y-m-d_His') . ".csv";

        // Поставување HTTP headers за симнување
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // Додавање на UTF-8 BOM за Excel автоматски да препознае кирилица (македонски) и албански букви (ë, ç)
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Колони
        fputcsv($output, array(
            'Ранг / Шифра',
            'Име на ученик',
            'Презиме на ученик',
            'Одделение',
            'Јазик',
            'Училиште',
            'Регион',
            'Општина',
            'Натпреварувачко ниво на натпревар',
            'Поени (макс 100)',
            'Освоена награда / Диплома',
            'Пласман во следно ниво',
            'Наставник / Ментор',
            'Е-пошта на ментор',
            'Телефон на ментор',
            'Датум и време на завршување'
        ));

        $phase_names = array(
            'school'    => 'Училишно натпреварувачко ниво',
            'municipal' => 'Општинско натпреварувачко ниво',
            'regional'  => 'Регионално натпреварувачко ниво',
            'state'     => 'Државно натпреварувачко ниво'
        );

        foreach ($results as $row) {
            $award = self::get_award_details($row->total_points, 'mk');
            $qualified = ($row->qualified_next_stage == 1) ? 'ДА' : 'НЕ';
            $phase_name = isset($phase_names[$row->phase]) ? $phase_names[$row->phase] : $row->phase;

            fputcsv($output, ZMP_Csv_Safety::safe_row(array(
                $row->access_code,
                $row->first_name,
                $row->last_name,
                $row->grade . ' одделение',
                strtoupper($row->language),
                $row->school_name,
                ZMP_Regions::label($row->region),
                $row->municipality,
                $phase_name,
                number_format($row->total_points, 2),
                $award['name_mk'],
                $qualified,
                $row->mentor_name,
                $row->mentor_email,
                $row->mentor_phone,
                $row->completed_at
            )));
        }

        fclose($output);
        exit;
    }
}
