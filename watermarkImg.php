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
$sql = "SELECT courseContent FROM courses WHERE courseId = ?"; // should be changed 
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

    function AddCenteredImage($file, $x, $y, $w = 0, $h = 0, $type = '', $link = ''){
        list($width, $height) = getimagesize($file);
        $pageWidth = $this->GetPageWidth();

        // Calculate center position if x is not set
        if ($x === null) {
            $x = ($pageWidth - $w) / 2;
        }

        $this->Image($file, $x, $y, $w, $h, $type, $link);
    }
}

function addWatermark($inputFile, $email, $username, $imagePath) {
    $pdf = new PDF_Rotate();
    $pageCount = $pdf->setSourceFile($inputFile);

    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
        $pdf->AddPage();

        // Get PDF dimensions
        $pageWidth = $pdf->GetPageWidth();
        $pageHeight = $pdf->GetPageHeight();

        // Calculate the center of the page
        $centerX = $pageWidth / 2;
        $centerY = $pageHeight / 2;

        // Image dimensions (adjust these as needed)
        list($originalWidth, $originalHeight) = getimagesize($imagePath);
        $imageWidth = 80;
        $imageHeight = ($imageWidth / $originalWidth) * $originalHeight;

        // Position the image in the center
        $imageX = $centerX - ($imageWidth / 2); // Center horizontally
        $imageY = $centerY - ($imageHeight / 2) - 10; // Center vertically, adjust to move image up

        // Add centered image BEFORE importing the PDF page
        $pdf->AddCenteredImage($imagePath, $imageX, $imageY, $imageWidth, $imageHeight);

        // Import the PDF page *after* adding the image
        $tplIdx = $pdf->importPage($pageNo);
        $pdf->useTemplate($tplIdx, 0, 0);

        // Text below the image
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(200, 200, 200); // Black color
        $text = "Downloaded by - " . $username;
        $textWidth = $pdf->GetStringWidth($text);
        $textX = $centerX - ($textWidth / 2); // Center horizontally
        $textY = $imageY + $imageHeight + 5; // Position below the image

        $pdf->Text($textX, $textY, $text);

        // Add watermark text
        $pdf->CenteredText($pageHeight - 15, utf8_decode("Proprietary content. © Great Learning. All Rights Reserved. Unauthorized use or distribution prohibited"), 10, '', 150, 150, 150);
        $pdf->CenteredText($pageHeight - 10, utf8_decode("This file is meant for personal use by $email only."), 10, '', 255, 100, 100);
        $pdf->CenteredText($pageHeight - 5, utf8_decode("Sharing or publishing the contents in part or full is liable for legal action."), 10, '', 255, 100, 100);
    }

    // Output the PDF to browser instead of saving it
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="watermarked.pdf"');
    $pdf->Output('I'); // 'I' means inline view in browser
}

$userEmail = 'testuser@gmail.com'; // Replace with actual user email
$username = 'John Doe'; // Replace with actual username
$imagePath = 'newLogo.jpg'; // Replace with the path to your image file

addWatermark($tempPdf, $userEmail, $username, $imagePath);

// Cleanup
unlink($tempPdf);
?>