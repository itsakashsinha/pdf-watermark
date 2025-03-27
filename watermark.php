
<?php
require('fpdf/fpdf.php');
require('fpdi/src/autoload.php');

use setasign\Fpdi\Fpdi;

class PDF_Rotate extends Fpdi {
    function CenteredText($y, $txt, $fontSize, $style = '', $r = 0, $g = 0, $b = 0) {
        $this->SetFont('Arial', $style, $fontSize);
        $this->SetTextColor($r, $g, $b);
        $textWidth = $this->GetStringWidth($txt);
        $xCenter = ($this->GetPageWidth() - $textWidth) / 2;
        $this->Text($xCenter, $y, $txt);
    }
}

function addWatermark($inputFile, $email) {
    $pdf = new PDF_Rotate();
    $pageCount = $pdf->setSourceFile($inputFile);

    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
        $pdf->AddPage();
        $tplIdx = $pdf->importPage($pageNo);
        $pdf->useTemplate($tplIdx, 0, 0);

        // Get PDF dimensions
        $pageHeight = $pdf->GetPageHeight();

        // Add watermark text
        $pdf->CenteredText($pageHeight - 35, utf8_decode("Proprietary content. © Great Learning. All Rights Reserved. Unauthorized use or distribution prohibited"), 10, '', 0, 0, 0);
        $pdf->CenteredText($pageHeight - 27, utf8_decode("This file is meant for personal use by $email only."), 10, '', 255, 0, 0);
        $pdf->CenteredText($pageHeight - 19, utf8_decode("Sharing or publishing the contents in part or full is liable for legal action."), 10, '', 255, 0, 0);
    }

    // Output the PDF to browser instead of saving it
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="watermarked.pdf"');
    $pdf->Output('I'); // 'I' means inline view in browser
}

// Example Usage
$inputPDF = 'sample.pdf';
$userEmail = 'example@gmail.com'; // Replace with actual user email

addWatermark($inputPDF, $userEmail);

// require('fpdf/fpdf.php');
// require('fpdi/src/autoload.php');

// use setasign\Fpdi\Fpdi;

// class PDF_Rotate extends Fpdi {
//     function Rotate($angle, $x = -1, $y = -1) {
//         if ($x == -1) $x = $this->GetX();
//         if ($y == -1) $y = $this->GetY();
//         if ($this->angle != 0) $this->_out('Q');
//         $this->angle = $angle;
//         if ($angle != 0) {
//             $angle *= M_PI / 180;
//             $c = cos($angle);
//             $s = sin($angle);
//             $cx = $x * $this->k;
//             $cy = ($this->h - $y) * $this->k;
//             $this->_out(sprintf('q %.2F %.2F %.2F %.2F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm', $c, $s, -$s, $c, $cx, $cy, -$cx, -$cy));
//         }
//     }

//     function CenteredText($y, $txt, $fontSize, $style = '', $r = 0, $g = 0, $b = 0) {
//         $this->SetFont('Arial', $style, $fontSize);
//         $this->SetTextColor($r, $g, $b);
//         $textWidth = $this->GetStringWidth($txt);
//         $xCenter = ($this->GetPageWidth() - $textWidth) / 2;
//         $this->Text($xCenter, $y, $txt);
//     }
// }

// function addWatermark($inputFile, $email) {
//     $pdf = new PDF_Rotate();
//     $pageCount = $pdf->setSourceFile($inputFile);

//     for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
//         $pdf->AddPage();
//         $tplIdx = $pdf->importPage($pageNo);
//         $pdf->useTemplate($tplIdx, 0, 0);

//         // Get PDF dimensions
//         $pageWidth = $pdf->GetPageWidth();
//         $pageHeight = $pdf->GetPageHeight();
//         $centerY = $pageHeight / 2; // Vertical Center
//         $centerX = $pageWidth / 2; // Horizontal Center

//         // Rotate and add watermark text diagonally
//         $pdf->Rotate(45, $centerX, $centerY); // Rotate 45 degrees
//         $pdf->CenteredText($centerY - 10, utf8_decode("Proprietary content. © Great Learning. All Rights Reserved."), 10, '', 0, 0, 0);
//         $pdf->CenteredText($centerY, utf8_decode("This file is meant for personal use by $email only."), 12, '', 255, 0, 0);
//         $pdf->CenteredText($centerY + 10, utf8_decode("Sharing or publishing the contents in part or full is liable for legal action."), 12, '', 255, 0, 0);
//         $pdf->Rotate(0); // Reset rotation
//     }

//     // Output the PDF to browser instead of saving it
//     header('Content-Type: application/pdf');
//     header('Content-Disposition: inline; filename="watermarked.pdf"');
//     $pdf->Output('I'); // 'I' means inline view in browser
// }

// // Example Usage
// $inputPDF = 'sample.pdf';
// $userEmail = 'example@gmail.com'; // Replace with actual user email

// addWatermark($inputPDF, $userEmail);
// ?>
