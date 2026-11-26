<?php
if (!defined('ABSPATH')) { exit; }

/** Prevent spreadsheet formula execution when exporting user-controlled text to CSV. */
final class ZMP_Csv_Safety {
    public static function safe_cell($value) {
        if (!is_string($value) || is_numeric($value)) { return $value; }
        if (preg_match('/^[\\x00-\\x20\\x7F]*[=+\\-@]/', $value) === 1) {
            return "'" . $value;
        }
        return $value;
    }

    public static function safe_row($row) {
        if (!is_array($row)) { return array(); }
        foreach ($row as &$value) { $value = self::safe_cell($value); }
        unset($value);
        return $row;
    }
}
