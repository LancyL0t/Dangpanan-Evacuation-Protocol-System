<?php
/**
 * ALERT LIST TEMPLATE
 * Variables: $pdf, $alerts
 */

require_once 'reports/_header.php';
renderHeader($pdf, 'Emergency Alerts History');

$headers = ['Type', 'Title', 'Source', 'Area', 'Status'];
$widths = [25, 60, 40, 40, 25];
renderTableHeader($pdf, $headers, $widths);

foreach ($alerts as $a) {
    $status = ($a['is_active'] == 1) ? 'Active' : 'Expired';
    $pdf->Cell($widths[0], 8, strtoupper($a['type']), 1, 0, 'C');
    $pdf->Cell($widths[1], 8, substr($a['title'], 0, 30), 1);
    $pdf->Cell($widths[2], 8, substr($a['source'], 0, 20), 1);
    $pdf->Cell($widths[3], 8, substr($a['affected_area'], 0, 20), 1);
    $pdf->Cell($widths[4], 8, $status, 1, 1, 'C');
}
