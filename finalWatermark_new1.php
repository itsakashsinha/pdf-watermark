<?php
require('fpdf/fpdf.php');
require('fpdi/src/autoload.php');

use setasign\Fpdi\Fpdi;

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_GET['pdfUrl']) || empty($_GET['pdfUrl'])) {
    die("Error: No PDF URL provided.");
}

$pdfUrl = urldecode($_GET['pdfUrl']);

function downloadPdfFromUrl($url) {
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_USERAGENT => 'Moodle PDF Downloader'
    ]);
    
    // Include Moodle session cookie if available
    if (isset($_COOKIE['MoodleSession'])) {
        curl_setopt($ch, CURLOPT_COOKIE, 'MoodleSession='.$_COOKIE['MoodleSession']);
    }
    
    $pdfData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || $pdfData === false) {
        die("Error: Unable to download the PDF. HTTP Code: $httpCode");
    }

    $tempPdf = tempnam(sys_get_temp_dir(), 'pdf_');
    file_put_contents($tempPdf, $pdfData);

    // Validate PDF
    $fileHandle = fopen($tempPdf, 'r');
    $magicNumber = fread($fileHandle, 4);
    fclose($fileHandle);
    
    if (strpos($magicNumber, '%PDF') !== 0) {
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

    function AddCenteredImage($file, $x, $y, $w = 0, $h = 0) {
        if (file_exists($file)) {
            $this->Image($file, $x, $y, $w, $h);
        }
    }
}

function addWatermark($inputFile, $email, $username, $imagePath) {
    $pdf = new PDF_Rotate();
    $pageCount = $pdf->setSourceFile($inputFile);

    // Get the current timestamp
    $timestamp = date("Y-m-d H:i:s");
    
    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
        $pdf->AddPage();

        // Get PDF dimensions
        $pageWidth = $pdf->GetPageWidth();
        $pageHeight = $pdf->GetPageHeight();

        // Center position
        $centerX = $pageWidth / 2;
        $centerY = $pageHeight / 2;

        // Image dimensions
        list($originalWidth, $originalHeight) = getimagesize($imagePath);
        $imageWidth = 80;
        $imageHeight = ($imageWidth / $originalWidth) * $originalHeight;

        // Center image position
        $imageX = $centerX - ($imageWidth / 2);
        $imageY = $centerY - ($imageHeight / 2) - 10;

        // Add image before importing the PDF page
        $pdf->AddCenteredImage($imagePath, $imageX, $imageY, $imageWidth, $imageHeight);

        // Import the PDF page after adding the image
        $tplIdx = $pdf->importPage($pageNo);
        $pdf->useTemplate($tplIdx, 0, 0);

        // Add "Downloaded by User" text below the image
        $textY = $imageY + $imageHeight + 5;
        $pdf->CenteredText($textY, "Downloaded by: $username", 10, '', 150, 150, 150);

        // Add timestamp below user info
        $timestampY = $textY + 5;
        $pdf->CenteredText($timestampY, "Downloaded on: $timestamp", 10, '', 150, 150, 150);

        // Add email below timestamp
        $emailY = $timestampY + 5;
        $pdf->CenteredText($emailY, "Email: $email", 10, '', 150, 150, 150);

        // Footer text
        $pdf->CenteredText($pageHeight - 15, utf8_decode("Proprietary content. © LIBA.edu. All Rights Reserved."), 10, '', 150, 150, 150);
        $pdf->CenteredText($pageHeight - 10, "This file is meant for personal use by $email only.", 10, '', 255, 100, 100);
        $pdf->CenteredText($pageHeight - 5, "Sharing or publishing the contents in part or full is liable for legal action.", 10, '', 255, 100, 100);
    }

    // Output the PDF to browser
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="watermarked.pdf"');
    $pdf->Output('I'); 
}

try {
    $tempPdf = downloadPdfFromUrl($pdfUrl);
    $imagePath = 'newLogo.jpg';

    // Get user information from session (alternative method)
    session_start();
    $userEmail = $_SESSION['USER']->email ?? $_SESSION['_USER']->email ?? 'user@example.com';
    $username = $_SESSION['USER']->firstname ?? $_SESSION['_USER']->firstname ?? 'Guest';
    
    // If firstname isn't available, try username
    if ($username === 'Guest') {
        $username = $_SESSION['USER']->username ?? $_SESSION['_USER']->username ?? 'Guest User';
    }

    // Apply watermark with user details
    addWatermark($tempPdf, $userEmail, $username, $imagePath);

    // Cleanup
    unlink($tempPdf);
    
} catch (Exception $e) {
    // Fallback to original PDF if watermarking fails
    header("Location: $pdfUrl");
    exit;
}