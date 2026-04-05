<?php
/**
 * HOST CERTIFICATION TEMPLATE
 * Variables available: $pdf, $user
 */

// --- Design ---
// Thick professional border
$pdf->SetLineWidth(1.5);
$pdf->Rect(10, 10, 277, 190);
$pdf->SetLineWidth(0.5);
$pdf->Rect(12, 12, 273, 186);

// Logo (Temporary)
$logoPath = 'assets/img/LOGO.png';
if (file_exists($logoPath)) {
    $pdf->Image($logoPath, 133, 20, 30);
}

$pdf->Ln(45);
$pdf->SetFont('Arial', 'B', 30);
$pdf->Cell(0, 15, 'CERTIFICATE OF RECOGNITION', 0, 1, 'C');

$pdf->Ln(10);
$pdf->SetFont('Arial', '', 18);
$pdf->Cell(0, 10, 'This is to certify that', 0, 1, 'C');

$pdf->Ln(5);
$pdf->SetFont('Arial', 'B', 28);
$pdf->SetTextColor(0, 51, 102); // Dark Blue
$fullName = strtoupper($user->getFirstName() . ' ' . ($user->getMiddleInitial() ? $user->getMiddleInitial() . '. ' : '') . $user->getLastName());
$pdf->Cell(0, 15, $fullName, 0, 1, 'C');

$pdf->Ln(10);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', '', 16);
$message = "  is an officially verified DANGPANAN Host for Bacolod City, recognized for their commitment to providing safe and secure shelter for fellow citizens during calamities and disasters.";
$pdf->MultiCell(0, 10, $message, 0, 'C');

// Verification Status
$pdf->Ln(10);
if ($user->getIsVerified()) {
    $pdf->SetFont('Arial', 'I', 12);
    $pdf->SetTextColor(0, 128, 0); // Green
    $pdf->Cell(0, 10, "Status: OFFICIALLY VERIFIED", 0, 1, 'C');
}

// Signature line
$pdf->SetTextColor(0, 0, 0);

// Admin Signature Image (New)
$sigPath = 'assets/img/signature.png';
if (file_exists($sigPath)) {
    $pdf->Image($sigPath, 205, 150, 50); // Positioned above the line
}

$pdf->SetFont('Arial', 'B', 12);
$pdf->SetXY(190, 170);
$pdf->Cell(80, 0, '', 'T', 1, 'C'); // Underline
$pdf->SetXY(190, 172);
$pdf->Cell(80, 10, 'City Disaster Administrator', 0, 0, 'C');
