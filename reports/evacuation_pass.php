<?php
/**
 * EVACUATION PASS TEMPLATE
 * Variables: $pdf, $request
 */

// Header
$pdf->SetFillColor(255, 230, 230);
$pdf->Rect(0, 0, 100, 25, 'F');

// Logo
$logoPath = 'assets/img/LOGO.png';
if (file_exists($logoPath)) {
    $pdf->Image($logoPath, 5, 5, 15);
}

$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(200, 0, 0); // Red
$pdf->SetXY(20, 10);
$pdf->Cell(75, 5, 'DANGPANAN EVACUATION PASS', 0, 1, 'C');

$pdf->Ln(15);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 5, 'SHELTER ASSIGNED:', 0, 1, 'C');
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, strtoupper($request['shelter_name'] ?? 'N/A'), 0, 1, 'C');

$pdf->SetFont('Arial', '', 9);
$pdf->MultiCell(0, 5, $request['location'] ?? 'N/A', 0, 'C');

$pdf->Ln(5);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 5, 'APPROVAL CODE:', 0, 1, 'C');

// Large boxed approval code
$pdf->SetFont('Arial', 'B', 32);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(80, 25, $request['approval_code'], 1, 1, 'C', true);

// Group Size
$pdf->Ln(5);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 5, 'GROUP SIZE: ' . ($request['group_size'] ?? 1), 0, 1, 'C');

// QR Code (Public API)
$qrData = $request['approval_code'];
$qrUrl = "https://api.qrserver.com/v1/create-qr-code/?data=" . urlencode($qrData) . "&size=100x100";

// Position QR code
$pdf->Image($qrUrl, 35, 110, 30, 30, 'PNG');

$pdf->SetFont('Arial', 'I', 8);
$pdf->SetY(142);
$pdf->Cell(0, 5, 'Please present this pass to the Host for check-in.', 0, 0, 'C');
