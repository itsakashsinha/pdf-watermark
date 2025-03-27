<?php
require('fpdf/fpdf.php');
require('fpdi/src/autoload.php');

use setasign\Fpdi\Fpdi;

// Get the PDF URL from the query parameter
if (!isset($_GET['pdfUrl']) || empty($_GET['pdfUrl'])) {
    die("Error: No PDF URL provided.");
}

// Decode and sanitize the URL
$pdfUrl = urldecode($_GET['pdfUrl']);
$pdfUrl = filter_var($pdfUrl, FILTER_SANITIZE_URL);

// Debug output - remove this in production
error_log("Received URL: " . $pdfUrl);

// More flexible URL validation
if (empty($pdfUrl) || !preg_match('~^(https?|ftp)://~i', $pdfUrl)) {
    die("Error: Invalid URL format. Please provide a complete URL starting with http:// or https://");
}
// Rest of your existing code remains the same...
// [Keep all the existing functions and class definitions]
function downloadPdfFromUrl($url) {
    // Debug output - remove in production
    error_log("Attempting to download from URL: " . $url);

    $ch = curl_init($url);
    
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_USERAGENT => 'Mozilla/5.0',
        CURLOPT_HTTPHEADER => ['Accept: application/pdf'],
        CURLOPT_VERBOSE => true // For debugging
    ]);

    $pdfData = curl_exec($ch);
    
    if ($pdfData === false) {
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        curl_close($ch);
        die("cURL Error ($errno): $error");
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL); // Get final URL after redirects
    curl_close($ch);

    // Debug output
    error_log("HTTP Code: $httpCode");
    error_log("Effective URL: $effectiveUrl");

    if ($httpCode !== 200) {
        die("Error: Unable to download the PDF. HTTP Code: $httpCode");
    }

    $tempPdf = tempnam(sys_get_temp_dir(), 'pdf');
    file_put_contents($tempPdf, $pdfData);

    // Validate PDF content
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $tempPdf);
    finfo_close($finfo);

    if ($mimeType !== 'application/pdf') {
        unlink($tempPdf);
        die("Error: The downloaded file is not a valid PDF. Detected MIME type: $mimeType");
    }

    return $tempPdf;
}

// Download the PDF file from the URL
// $tempPdf = tempnam(sys_get_temp_dir(), 'pdf');
// file_put_contents($tempPdf, file_get_contents($pdfUrl));

// Watermark class
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

// Function to add watermark
function addWatermark($inputFile, $email, $username, $imagePath) {
    $pdf = new PDF_Rotate();
    $pageCount = $pdf->setSourceFile($inputFile);

    // Get the current timestamp
    $timestamp = date("Y-m-d H:i:s"); // Format: YYYY-MM-DD HH:MM:SS
    
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

        // ✅ Add "Downloaded by User" text below the image (Grey color)
        $textY = $imageY + $imageHeight + 5; // Position text slightly below the image
        $pdf->CenteredText($textY, "Downloaded by User", 10, '', 150, 150, 150);

        // ✅ Add timestamp below "Downloaded by User" (Grey color)
        $timestampY = $textY + 5; // Position timestamp slightly below the text
        $pdf->CenteredText($timestampY, "Downloaded on: $timestamp", 10, '', 150, 150, 150);

        // Watermark text
        $pdf->CenteredText($pageHeight - 15, utf8_decode("Proprietary content. © Great Learning. All Rights Reserved."), 10, '', 150, 150, 150);
        $pdf->CenteredText($pageHeight - 10, "This file is meant for personal use by $email only.", 10, '', 255, 100, 100);
        $pdf->CenteredText($pageHeight - 5, "Sharing or publishing the contents in part or full is liable for legal action.", 10, '', 255, 100, 100);
    }

    // Output the PDF to browser
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="watermarked.pdf"');
    $pdf->Output('I'); 
}
// Example user details (you might want to get these from session or database)
try {
    $tempPdf = downloadPdfFromUrl($pdfUrl);
    
    // Example user details - replace with actual user data in production
    $userEmail = 'testuser@gmail.com';
    $username = 'John Doe';
    $imagePath = 'newLogo.jpg';
    
    if (!file_exists($imagePath)) {
        throw new Exception("Watermark image not found at: $imagePath");
    }

    addWatermark($tempPdf, $userEmail, $username, $imagePath);
    
    if (file_exists($tempPdf)) {
        unlink($tempPdf);
    }
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>