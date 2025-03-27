<?php
require('fpdf/fpdf.php');
require('fpdi/src/autoload.php');

use setasign\Fpdi\Fpdi;

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$database = "courses_db";
$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the course ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid course ID.");
}
$courseId = intval($_GET['id']);

// Fetch PDF from database
$sql = "SELECT courseContent FROM courses WHERE courseId = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $courseId);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    die("No PDF found.");
}

$stmt->bind_result($pdfData);
$stmt->fetch();
$stmt->close();
$conn->close();

// Save the PDF temporarily
$tempPdf = tempnam(sys_get_temp_dir(), 'pdf');
file_put_contents($tempPdf, $pdfData);

// Watermark class
// the bottome watermark function
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
        $pdf->CenteredText($pageHeight - 20, utf8_decode("Proprietary content. © Great Learning. All Rights Reserved. Unauthorized use or distribution prohibited"), 10, '', 0, 0, 0);
        $pdf->CenteredText($pageHeight - 15, utf8_decode("This file is meant for personal use by $email only."), 10, '', 255, 0, 0);
        $pdf->CenteredText($pageHeight - 10, utf8_decode("Sharing or publishing the contents in part or full is liable for legal action."), 10, '', 255, 0, 0);
    }

    // Output the PDF to browser instead of saving it
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="watermarked.pdf"');
    $pdf->Output('I'); // 'I' means inline view in browser
}

//diagonal watermark
// class PDF_Rotate extends Fpdi {
//     var $angle = 0;

//     function Rotate($angle, $x = -1, $y = -1) {
//         if ($x == -1) {
//             $x = $this->GetX();
//         }
//         if ($y == -1) {
//             $y = $this->GetY();
//         }
//         if ($this->angle != 0) {
//             $this->_out('Q');
//         }
//         $this->angle = $angle;
//         if ($angle != 0) {
//             $angleRad = deg2rad($angle);
//             $c = cos($angleRad);
//             $s = sin($angleRad);
//             $cx = $x * $this->k;
//             $cy = ($this->h - $y) * $this->k;
//             $this->_out(sprintf('q %.2F %.2F %.2F %.2F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm', $c, $s, -$s, $c, $cx, $cy, -$cx, -$cy));
//         }
//     }

//     function _endpage() {
//         if ($this->angle != 0) {
//             $this->angle = 0;
//             $this->_out('Q');
//         }
//         parent::_endpage();
//     }
// }

//diagonal watermark
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

//         // Set text properties
//         $pdf->SetFont('Arial', '', 10);
//         $pdf->SetTextColor(255, 0, 0); // Red color

//         // Rotate and add multiple watermark lines
//         $pdf->Rotate(45, $pageWidth / 2, $pageHeight / 2);
        
//         // Define watermark lines
//         $watermarkLines = [
//             "Proprietary content. © Great Learning. All Rights Reserved. Unauthorized use or distribution prohibited",
//             "This file is meant for personal use by $email only.",
//             "Sharing or publishing the contents in part or full is liable for legal action."
//         ];

//         // Set starting position
//         $startY = $pageHeight / 2 - 10; // Adjust based on your preference

//         foreach ($watermarkLines as $index => $line) {
//             // first line black and other two are red
//             if($index ==0){
//                 $pdf->SetTextColor(100,100,100);
//             } else{
//                 $pdf->SetTextColor(200,0,0);
//             }

//             $pdf->Text($pageWidth / 4, $startY, $line);
//             $startY += 6; // Move down for the next line
//         }

//         // Reset rotation
//         $pdf->Rotate(0);
//     }

//     // Output the PDF to browser
//     header('Content-Type: application/pdf');
//     header('Content-Disposition: inline; filename="watermarked.pdf"');
//     $pdf->Output('I'); // 'I' means inline view in browser
// }

// Apply watermark
$userEmail = 'testuser@gmail.com'; // Replace with actual user email
addWatermark($tempPdf, $userEmail);

// Cleanup
unlink($tempPdf);
?>
