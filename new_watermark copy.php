<?php
require('fpdf/fpdf.php');
require('fpdi/src/autoload.php');

use setasign\Fpdi\Fpdi;

session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    die("Error: User not logged in.");
}

// Get PDF URL from GET parameter
if (!isset($_GET['pdfUrl']) || empty($_GET['pdfUrl'])) {
    die("Error: No PDF URL provided.");
}
$pdfUrl = $_GET['pdfUrl'];

// Get user details from session instead of URL
$firstname = $_SESSION['firstname'] ?? '';
$lastname = $_SESSION['lastname'] ?? '';
$email = $_SESSION['email'] ?? '';

function downloadPdfFromUrl($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $pdfData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || $pdfData === false) {
        die("Error: Unable to download the PDF. HTTP Code: $httpCode");
    }

    $tempPdf = tempnam(sys_get_temp_dir(), 'pdf');
    file_put_contents($tempPdf, $pdfData);

    if (mime_content_type($tempPdf) !== 'application/pdf') {
        unlink($tempPdf);
        die("Error: The downloaded file is not a valid PDF.");
    }

    return $tempPdf;
}

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

        if ($x === null) {
            $x = ($pageWidth - $w) / 2;
        }

        $this->Image($file, $x, $y, $w, $h, $type, $link);
    }
}

function addWatermark($inputFile, $email, $username, $imagePath) {
    $pdf = new PDF_Rotate();
    $pageCount = $pdf->setSourceFile($inputFile);
    $timestamp = date("Y-m-d H:i:s");
    
    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
        $pdf->AddPage();
        $pageWidth = $pdf->GetPageWidth();
        $pageHeight = $pdf->GetPageHeight();
        $centerX = $pageWidth / 2;
        $centerY = $pageHeight / 2;

        list($originalWidth, $originalHeight) = getimagesize($imagePath);
        $imageWidth = 80;
        $imageHeight = ($imageWidth / $originalWidth) * $originalHeight;
        $imageX = $centerX - ($imageWidth / 2);
        $imageY = $centerY - ($imageHeight / 2) - 10;

        $pdf->AddCenteredImage($imagePath, $imageX, $imageY, $imageWidth, $imageHeight);
        $tplIdx = $pdf->importPage($pageNo);
        $pdf->useTemplate($tplIdx, 0, 0);

        $textY = $imageY + $imageHeight + 5;
        $pdf->CenteredText($textY, "Downloaded by $username", 10, '', 150, 150, 150);

        $timestampY = $textY + 5;
        $pdf->CenteredText($timestampY, "Downloaded on: $timestamp", 10, '', 150, 150, 150);

        $pdf->CenteredText($pageHeight - 15, utf8_decode("Proprietary content. © Great Learning. All Rights Reserved."), 10, '', 150, 150, 150);
        $pdf->CenteredText($pageHeight - 10, "This file is meant for personal use by $email only.", 10, '', 255, 100, 100);
        $pdf->CenteredText($pageHeight - 5, "Sharing or publishing the contents in part or full is liable for legal action.", 10, '', 255, 100, 100);
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="watermarked.pdf"');
    $pdf->Output('I'); 
}

$tempPdf = downloadPdfFromUrl($pdfUrl);
$userEmail = $email;
$username = $firstname . ' ' . $lastname;
$imagePath = 'newLogo.jpg';

addWatermark($tempPdf, $userEmail, $username, $imagePath);
unlink($tempPdf);
?>