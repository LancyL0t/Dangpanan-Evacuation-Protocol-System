<?php
/**
 * SYSTEM SUMMARY TEMPLATE
 * Variables: $pdf, $stats, $db, $totalOccupants, $totalCapacity
 */

require_once 'reports/_header.php';

// -- PAGE 1: EXECUTIVE OVERVIEW --
renderHeader($pdf, 'System Overview Summary');

$pdf->SetFont('Arial', 'B', 14);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(0, 12, ' EXECUTIVE SUMMARY', 0, 1, 'L', true);
$pdf->Ln(5);

$pdf->SetFont('Arial', '', 12);
$pdf->Cell(95, 10, 'Total Registered Users: ' . $stats['totalUsers'], 1, 0);
$pdf->Cell(95, 10, 'Active Shelters: ' . $stats['totalShelters'], 1, 1);
$pdf->Cell(95, 10, 'Active Emergency Alerts: ' . $stats['totalAlerts'], 1, 0);
$pdf->Cell(95, 10, 'Current Displaced Persons: ' . $totalOccupants, 1, 1);

$usage = $totalCapacity > 0 ? round(($totalOccupants / $totalCapacity) * 100, 1) : 0;
$pdf->Ln(5);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 12, 'Overall Capacity Utilization: ' . $usage . '%', 1, 1, 'C');

// Shelter Details Table (Top 10)
$pdf->Ln(10);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'SHELTER UTILIZATION (Top 10)', 0, 1);

$shelters = $db->query("SELECT * FROM shelter ORDER BY current_capacity DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
$headers = ['Shelter', 'Max', 'Curr', '%'];
$widths = [110, 25, 25, 30];
renderTableHeader($pdf, $headers, $widths);
foreach ($shelters as $s) {
    $pdf->Cell($widths[0], 8, $s['shelter_name'], 1);
    $pdf->Cell($widths[1], 8, $s['max_capacity'], 1, 0, 'C');
    $pdf->Cell($widths[2], 8, $s['current_capacity'], 1, 0, 'C');
    $p = $s['max_capacity'] > 0 ? round(($s['current_capacity']/$s['max_capacity'])*100) : 0;
    $pdf->Cell($widths[3], 8, $p . '%', 1, 1, 'C');
}

// -- PAGE 2: USER & VERIFICATION STATS --
$pdf->AddPage();
renderHeader($pdf, 'User & Verification Stats');

$roles = $db->query("SELECT role, COUNT(*) as cnt FROM users GROUP BY role")->fetchAll(PDO::FETCH_ASSOC);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'USER ROLE DISTRIBUTION', 0, 1);
foreach ($roles as $r) {
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(50, 10, $r['role'] . ':', 0, 0);
    $pdf->Cell(0, 10, $r['cnt'], 0, 1);
}

$pdf->Ln(10);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'PENDING VERIFICATION QUEUE', 0, 1);
$verify = $db->query("SELECT first_name, last_name, email FROM users WHERE role='Host' AND is_verified=0 LIMIT 15")->fetchAll(PDO::FETCH_ASSOC);
if (empty($verify)) {
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->Cell(0, 10, 'Queue is empty.', 0, 1);
} else {
    renderTableHeader($pdf, ['Name', 'Email'], [90, 100]);
    foreach($verify as $v) {
        $pdf->Cell(90, 8, $v['first_name'] . ' ' . $v['last_name'], 1);
        $pdf->Cell(100, 8, $v['email'], 1, 1);
    }
}

// -- PAGE 3: RECENT ACTIVITY --
$pdf->AddPage();
renderHeader($pdf, 'Recent System Activity');
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'LAST 30 ACTIONS', 0, 1);
$logs = $db->query("SELECT l.*, u.first_name FROM system_logs l LEFT JOIN users u ON l.user_id=u.user_id ORDER BY l.created_at DESC LIMIT 30")->fetchAll(PDO::FETCH_ASSOC);
renderTableHeader($pdf, ['Time', 'User', 'Action'], [40, 40, 110]);
foreach($logs as $l) {
    $pdf->Cell(40, 7, date('H:i:s m-d', strtotime($l['created_at'])), 1);
    $pdf->Cell(40, 7, $l['first_name'] ?: 'System', 1);
    $pdf->Cell(110, 7, substr($l['action'],0,60), 1, 1);
}
