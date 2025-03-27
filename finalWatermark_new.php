<?php
require('fpdf/fpdf.php');
require('fpdi/src/autoload.php');

use setasign\Fpdi\Fpdi;

// URL of the PDF
// $pdfUrl = "https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf"; // Change this to the actual PDF URL
// $pdfUrl = "https://www.antennahouse.com/hubfs/xsl-fo-sample/pdf/basic-link-1.pdf"; // Change this to the actual PDF URL
// $pdfUrl = "https://ailms.shiksak.com/HMI%20-%20The%20importance%20of%20food%20(1).pdf";
if (!isset($_GET['pdfUrl']) || empty($_GET['pdfUrl'])) {
    die("Error: No PDF URL provided.");
}
// $pdfUrl = urldecode($_GET['pdfUrl']);
$pdfUrl = $_GET['pdfUrl'];

session_start();

// Get user details from URL parameters
$firstname = isset($_GET['firstname']) ? urldecode($_GET['firstname']) : '';
$lastname = isset($_GET['lastname']) ? urldecode($_GET['lastname']) : '';
$email = isset($_GET['email']) ? urldecode($_GET['email']) : '';

// function downloadPdfFromUrl($url) {
//     $pdfData = file_get_contents($url);
    
//     if ($pdfData === false) {
//         die("Error: Unable to download the PDF. Please check the URL.");
//     }

//     // Save the PDF temporarily
//     $tempPdf = tempnam(sys_get_temp_dir(), 'pdf');
//     file_put_contents($tempPdf, $pdfData);

//     // Validate if it's a real PDF file
//     if (mime_content_type($tempPdf) !== 'application/pdf') {
//         unlink($tempPdf); // Delete the invalid file
//         die("Error: The downloaded file is not a valid PDF.");
//     }

//     return $tempPdf;
// }

// using cURL
function downloadPdfFromUrl($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Ignore SSL verification
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Set timeout for response
    $pdfData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || $pdfData === false) {
        die("Error: Unable to download the PDF. HTTP Code: $httpCode");
    }

    // Save the PDF temporarily
    $tempPdf = tempnam(sys_get_temp_dir(), 'pdf');
    file_put_contents($tempPdf, $pdfData);

    // Validate if it's a real PDF file
    if (mime_content_type($tempPdf) !== 'application/pdf') {
        unlink($tempPdf); // Delete the invalid file
        die("Error: The downloaded file is not a valid PDF.");
    }

    return $tempPdf;
}

// function downloadFileFromUrl($url) {
//     $ch = curl_init($url);
//     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//     curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
//     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
//     curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Increased timeout for larger files
//     $fileData = curl_exec($ch);
//     $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
//     curl_close($ch);

//     if ($httpCode !== 200 || $fileData === false) {
//         die("Error: Unable to download the file. HTTP Code: $httpCode");
//     }

//     // Save the file temporarily
//     $tempFile = tempnam(sys_get_temp_dir(), 'downloaded_');
//     file_put_contents($tempFile, $fileData);

//     // Validate file type
//     $mimeType = mime_content_type($tempFile);
//     $allowedTypes = [
//         'application/pdf' => 'pdf',
//         'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
//         'application/msword' => 'doc',
//         'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
//         'application/vnd.ms-powerpoint' => 'ppt'
//     ];

//     if (!array_key_exists($mimeType, $allowedTypes)) {
//         unlink($tempFile);
//         die("Error: Unsupported file type. Detected MIME: $mimeType");
//     }

//     // Get file extension
//     $extension = $allowedTypes[$mimeType];
    
//     // Rename temp file to include correct extension
//     $finalPath = $tempFile . '.' . $extension;
//     rename($tempFile, $finalPath);

//     return [
//         'path' => $finalPath,
//         'type' => $mimeType,
//         'extension' => $extension
//     ];
// }

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
        $pdf->CenteredText($textY, "Downloaded by $username", 10, '', 150, 150, 150);

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

// Example user details
$tempPdf = downloadPdfFromUrl($pdfUrl);

$userEmail = $email;
$username = $firstname . ' ' . $lastname;
$imagePath = 'newLogo.jpg'; // Path to watermark image

addWatermark($tempPdf, $userEmail, $username, $imagePath);

// Cleanup
unlink($tempPdf);
?>
