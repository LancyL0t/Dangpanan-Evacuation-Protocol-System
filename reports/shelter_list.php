<?php
/**
 * SHELTER LIST TEMPLATE
 * Variables: $pdf, $shelters
 */

require_once 'reports/_header.php';
renderHeader($pdf, 'Shelter Directory Report');

$headers = ['ID', 'Shelter Name', 'Location', 'Capacity', 'Host', 'Status'];
$widths  = [15, 60, 60, 20, 25, 17];

// Center the table automatically using Left Margin
$tableWidth = array_sum($widths);
$centerX    = ($pdf->GetPageWidth() - $tableWidth) / 2;
$pdf->SetLeftMargin($centerX);
$pdf->SetX($centerX);

renderTableHeader($pdf, $headers, $widths);

foreach ($shelters as $s) {
    // Basic formatting
    $initial    = substr($s['first_name'] ?? '', 0, 1);
    $hostName   = $s['first_name'] ? "{$initial}. {$s['last_name']}" : 'N/A';
    $statusText = ($s['is_active'] == 1) ? 'Active' : 'Inactive';
    $occupancy  = "{$s['current_capacity']}/{$s['max_capacity']}";

    // Draw row cells
    $pdf->Cell($widths[0], 8, '#' . $s['shelter_id'], 1);
    $pdf->Cell($widths[1], 8, substr($s['shelter_name'], 0, 30), 1);
    $pdf->Cell($widths[2], 8, substr($s['location'], 0, 30), 1);
    $pdf->Cell($widths[3], 8, $occupancy, 1, 0, 'C');
    $pdf->Cell($widths[4], 8, $hostName, 1);
    $pdf->Cell($widths[5], 8, $statusText, 1, 1, 'C');
}
