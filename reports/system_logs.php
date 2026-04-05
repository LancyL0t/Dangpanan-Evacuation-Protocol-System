<?php
/**
 * SYSTEM LOGS TEMPLATE
 * Variables: $pdf, $logs
 */

require_once 'reports/_header.php';
renderHeader($pdf, 'System Activity Log');

$headers = ['Timestamp', 'User', 'Action'];
$widths = [45, 45, 100];
renderTableHeader($pdf, $headers, $widths);

foreach ($logs as $l) {
    $pdf->Cell($widths[0], 7, $l['created_at'], 1);
    $pdf->Cell($widths[1], 7, $l['user_name'] ?: 'System', 1);
    $pdf->Cell($widths[2], 7, substr($l['action'], 0, 65), 1, 1);
}
