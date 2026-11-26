<?php
if (!defined('ABSPATH')) {
    exit;
}

class ZMP_PDF {

    /**
     * Генерирање на официјален PDF документ за запечатено плико за еден или повеќе ученици
     */
    public static function render_envelope_pdf($students = array(), $auto_print = true) {
        if (empty($students)) {
            wp_die('Нема пронајдено податоци за ученик.');
        }

        ?>
        <!DOCTYPE html>
        <html lang="mk">
        <head>
            <meta charset="UTF-8">
            <title>ЗМП - Кодови за запечатено плико (PDF)</title>
            <style>
                @page {
                    size: A4;
                    margin: 12mm;
                }
                * {
                    box-sizing: border-box;
                }
                body {
                    font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
                    color: #1e293b;
                    background: #ffffff;
                    margin: 0;
                    padding: 0;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
                .no-print-bar {
                    background: #1e3a8a;
                    color: #ffffff;
                    padding: 12px 20px;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 20px;
                    border-radius: 6px;
                }
                .no-print-bar button {
                    background: #f59e0b;
                    color: #0f172a;
                    border: none;
                    padding: 10px 20px;
                    font-size: 15px;
                    font-weight: bold;
                    border-radius: 6px;
                    cursor: pointer;
                }
                .slips-grid {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 15px;
                }
                .slip-card {
                    border: 2px dashed #475569;
                    border-radius: 10px;
                    padding: 16px;
                    background: #ffffff;
                    position: relative;
                    page-break-inside: avoid;
                }
                .slip-header {
                    border-bottom: 2px solid #2563eb;
                    padding-bottom: 8px;
                    margin-bottom: 12px;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }
                .slip-logo {
                    font-size: 26px;
                }
                .slip-title h4 {
                    margin: 0;
                    font-size: 13px;
                    color: #1e3a8a;
                    text-transform: uppercase;
                    line-height: 1.2;
                }
                .slip-title p {
                    margin: 3px 0 0 0;
                    font-size: 11px;
                    color: #64748b;
                }
                .slip-body {
                    font-size: 13px;
                    margin-bottom: 12px;
                }
                .slip-row {
                    margin-bottom: 5px;
                    display: flex;
                    justify-content: space-between;
                }
                .slip-row strong {
                    color: #334155;
                }
                .slip-creds-box {
                    background: #f1f5f9;
                    border: 1.5px solid #cbd5e1;
                    border-radius: 8px;
                    padding: 10px 14px;
                    text-align: center;
                    margin: 10px 0;
                }
                .cred-label {
                    font-size: 11px;
                    color: #475569;
                    text-transform: uppercase;
                    font-weight: 600;
                }
                .cred-code {
                    font-size: 18px;
                    font-weight: 800;
                    color: #0f172a;
                    letter-spacing: 1px;
                    font-family: monospace;
                    margin-bottom: 6px;
                }
                .cred-pin {
                    font-size: 16px;
                    font-weight: 800;
                    color: #0284c7;
                    font-family: monospace;
                }
                .slip-instructions {
                    font-size: 10.5px;
                    color: #dc2626;
                    font-weight: 600;
                    background: #fef2f2;
                    border-left: 3px solid #dc2626;
                    padding: 6px 8px;
                    margin-top: 8px;
                }
                .slip-footer {
                    margin-top: 8px;
                    font-size: 10px;
                    color: #94a3b8;
                    display: flex;
                    justify-content: space-between;
                }
                @media print {
                    .no-print-bar {
                        display: none !important;
                    }
                    body {
                        margin: 0;
                    }
                    .slip-card {
                        border-color: #64748b;
                    }
                }
            </style>
        </head>
        <body>
            <div class="no-print-bar">
                <div>
                    <strong>📄 Официјален PDF документ за запечатено плико</strong> (https://muzika.org.mk)
                    <br><small>Кликнете на копчето десно за печатење или зачувување како PDF фајл (Save as PDF).</small>
                </div>
                <button onclick="window.print()">🖨️ Зачувај како PDF / Печати</button>
            </div>

            <div class="slips-grid">
                <?php foreach ($students as $st): ?>
                    <div class="slip-card">
                        <div class="slip-header">
                            <div class="slip-logo">🎵</div>
                            <div class="slip-title">
                                <h4>Акредитирано здружение на музички педагози на Македонија</h4>
                                <p>Натпревар по музичко образование | https://muzika.org.mk</p>
                            </div>
                        </div>

                        <div class="slip-body">
                            <div class="slip-row">
                                <span>Ученик:</span>
                                <strong><?php echo esc_html($st->first_name . ' ' . $st->last_name); ?></strong>
                            </div>
                            <div class="slip-row">
                                <span>Одделение:</span>
                                <strong><?php echo esc_html($st->grade); ?>. одделение (<?php echo strtoupper(esc_html($st->language)); ?>)</strong>
                            </div>
                            <div class="slip-row">
                                <span>Училиште:</span>
                                <span><?php echo esc_html($st->school_name); ?></span>
                            </div>
                            <div class="slip-row">
                                <span>Општина / Регион:</span>
                                <span><?php echo esc_html($st->municipality); ?></span>
                            </div>
                        </div>

                        <div class="slip-creds-box">
                            <div class="cred-label">Корисничко име / Шифра (Kodi i përdoruesit):</div>
                            <div class="cred-code"><?php echo esc_html($st->access_code); ?></div>
                            <div class="cred-label">Лозинка / ПИН (Fjalëkalimi):</div>
                            <div class="cred-pin"><?php echo esc_html(ZMP_Credentials::reveal_pin($st->access_pin)); ?></div>
                        </div>

                        <div class="slip-instructions">
                            🔒 ДОЗВОЛЕНО ДА СЕ ОТВОРИ ИСКЛУЧИВО ВО ПЛИКО НЕПОСРЕДНО ПРЕД ПОЧЕТОКОТ НА ТЕСТОТ!
                        </div>

                        <div class="slip-footer">
                            <span>Најава на: https://muzika.org.mk</span>
                            <span>Натпреварувачко ниво: <?php echo esc_html(ZMP_Category_Jury::phase_label($st->current_phase)); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($auto_print): ?>
                <script>
                    window.addEventListener('load', function() {
                        // Автоматски отвори дијалог за печатење / Зачувување во PDF
                        setTimeout(function() {
                            window.print();
                        }, 500);
                    });
                </script>
            <?php endif; ?>
        </body>
        </html>
        <?php
        exit;
    }
}
