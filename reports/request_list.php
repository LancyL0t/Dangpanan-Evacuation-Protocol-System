<?php
/**
 * REQUEST LIST TEMPLATE
 * Variables: $pdf, $requests
 */

require_once 'reports/_header.php';
renderHeader($pdf, 'Shelter Placement Requests');

$headers = ['ID', 'Evacuee', 'Shelter', 'Size', 'Status', 'Date'];
$widths = [15, 50, 50, 15, 30, 30];
renderTableHeader($pdf, $headers, $widths);

foreach ($requests as $r) {
    $pdf->Cell($widths[0], 8, '#' . $r['id'], 1);
    $pdf->Cell($widths[1], 8, $r['first_name'] . ' ' . substr($r['last_name'], 0, 1) . '.', 1);
    $pdf->Cell($widths[2], 8, strtoupper(substr($r['shelter_name'] ?? 'N/A', 0, 25)), 1);
    $pdf->Cell($widths[3], 8, $r['group_size'], 1, 0, 'C');
    $pdf->Cell($widths[4], 8, strtoupper($r['status']), 1, 0, 'C');
    $pdf->Cell($widths[5], 8, date('Y-m-d', strtotime($r['created_at'])), 1, 1, 'C');
}
