<?php
/**
 * Shared Header/Helper for all PDF Reports
 * This ensures consistent branding and typography.
 */

function renderHeader($pdf, $title) {
    $logoPath = 'assets/img/LOGO.png';
    if (file_exists($logoPath)) {
        $pdf->Image($logoPath, 10, 10, 20);
    }
    
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->SetTextColor(0, 51, 102);
    $pdf->Cell(0, 10, strtoupper($title), 0, 1, 'R');
    
    $pdf->SetFont('Arial', 'I', 9);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(0, 5, 'DANGPANAN Disaster Coordination System | ' . date('Y-m-d H:i:s'), 0, 1, 'R');
    
    $pdf->Ln(10);
    $pdf->SetDrawColor(200, 200, 200);
    
    // Check orientation for line width
    // Landscape A4 width is 297mm
    // Portrait A4 width is 210mm
    $lineWidth = ($pdf->GetPageWidth() - 20); 
    $pdf->Line(10, 30, $lineWidth + 10, 30);
    $pdf->Ln(5);
}

function renderTableHeader($pdf, $headers, $widths) {
    $pdf->SetFillColor(0, 51, 102);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 10);
    for($i=0; $i<count($headers); $i++) {
        $pdf->Cell($widths[$i], 10, $headers[$i], 1, 0, 'C', true);
    }
    $pdf->Ln();
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Arial', '', 9);
}
