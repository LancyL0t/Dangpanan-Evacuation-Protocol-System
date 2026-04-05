<?php
/**
 * OCCUPANT LIST TEMPLATE
 * Variables: $pdf, $occupants
 */

require_once 'reports/_header.php';
renderHeader($pdf, 'Active Shelter Occupants');

// 1. Table Setup
$headers = ['Shelter Name', 'Occupant Name', 'Group', 'Checked In'];
$widths = [65, 60, 20, 50];

// 2. Automagically Center the Table
$tableWidth = array_sum($widths);
$centerX    = ($pdf->GetPageWidth() - $tableWidth) / 2;
$pdf->SetLeftMargin($centerX);
$pdf->SetX($centerX);

// 3. Draw Header
renderTableHeader($pdf, $headers, $widths);

// 4. Draw Rows
foreach ($occupants as $o) {
    // Format Name to "J. Doe"
    $initial  = substr($o['first_name'] ?? '', 0, 1);
    $fullName = $o['first_name'] ? "{$initial}. {$o['last_name']}" : 'N/A';
    
    // Format Date for better readability
    $checkInDate = date('M j, Y - g:i A', strtotime($o['checked_in_at']));

    $pdf->Cell($widths[0], 8, substr($o['shelter_name'], 0, 32), 1);
    $pdf->Cell($widths[1], 8, $fullName, 1);
    $pdf->Cell($widths[2], 8, $o['group_size'], 1, 0, 'C');
    $pdf->Cell($widths[3], 8, $checkInDate, 1, 1, 'C');
}
