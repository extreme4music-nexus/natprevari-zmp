<?php
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) { exit(2); }
WP_CLI::line('TEACHER_ROLE=' . (wp_roles()->is_role('zmp_teacher') ? 'present' : 'absent'));
WP_CLI::line('ROOT_WRITABLE=' . (is_writable('/mnt/z/Documents/Natprevari_ZMP') ? 'yes' : 'no'));
WP_CLI::line('CREDENTIAL_FILE_EXISTS=' . (file_exists('/mnt/z/Documents/Natprevari_ZMP/ZMP_PHASE65_TEACHER_SYNTHETIC_LOCAL_ONLY_20261105.txt') ? 'yes' : 'no'));
