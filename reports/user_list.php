<?php
/**
 * USER LIST TEMPLATE
 * Variables: $pdf, $users
 */

require_once 'reports/_header.php';
renderHeader($pdf, 'User Directory Report');

$headers = ['ID', 'Full Name', 'Email', 'Role', 'Status'];
$widths = [15, 60, 65, 25, 25];
renderTableHeader($pdf, $headers, $widths);

foreach ($users as $u) {
    $name = $u['first_name'] . ' ' . $u['last_name'];
    $status = ($u['is_verified'] == 1) ? 'Verified' : 'Unverified';
    $pdf->Cell($widths[0], 8, '#' . $u['user_id'], 1);
    $pdf->Cell($widths[1], 8, substr($name, 0, 30), 1);
    $pdf->Cell($widths[2], 8, substr($u['email'], 0, 35), 1);
    $pdf->Cell($widths[3], 8, $u['role'], 1, 0, 'C');
    $pdf->Cell($widths[4], 8, $status, 1, 1, 'C');
}
