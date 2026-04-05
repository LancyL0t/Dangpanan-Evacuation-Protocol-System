<?php
/**
 * VERIFICATION QUEUE TEMPLATE
 * Variables: $pdf, $queue
 */

require_once 'reports/_header.php';
renderHeader($pdf, 'Host Verification Queue');

if (empty($queue)) {
    $pdf->Cell(0, 10, 'No hosts currently awaiting verification.', 0, 1, 'C');
} else {
    $headers = ['Name', 'Email', 'Phone', 'Applied Date'];
    $widths = [60, 60, 40, 30];
    renderTableHeader($pdf, $headers, $widths);
    foreach ($queue as $u) {
        $pdf->Cell($widths[0], 8, $u['first_name'] . ' ' . $u['last_name'], 1);
        $pdf->Cell($widths[1], 8, $u['email'], 1);
        $pdf->Cell($widths[2], 8, $u['phone_number'] ?: 'N/A', 1);
        $pdf->Cell($widths[3], 8, date('Y-m-d', strtotime($u['created_at'])), 1, 1, 'C');
    }
}
