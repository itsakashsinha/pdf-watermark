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

    function setPDFMetadata($username) {
        // These methods must be called BEFORE adding any content
        // $this->SetTitle($title);
        $this->SetAuthor($username);
        $this->SetSubject("Downloaded from https://libaonline.shiksak.com");
        $this->SetKeywords("Watermarked PDF");
        
        // For FPDF compatibility
        $this->metadata = [
            // 'Title' => $title,
            'Author' => $username,
            'Subject' => 'Downloaded from https://libaonline.shiksak.com',
            'Creator' => 'LIBA'
        ];
    }

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
    $pdf->setPDFMetadata($username);
    
    $pageCount = $pdf->setSourceFile($inputFile);

    // Get the current timestamp
    date_default_timezone_set('Asia/Kolkata');
    $timestamp = date("Y-m-d H:i:s");
    
    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
        $pdf->AddPage();

        // Get PDF dimensions
        $pageWidth = $pdf->GetPageWidth();
        $pageHeight = $pdf->GetPageHeight();

        // Get original dimensions of the imported page
        $tplIdx = $pdf->importPage($pageNo);
        $size = $pdf->getTemplateSize($tplIdx);

        // Scale to maintain aspect ratio
        $scaleX = $pageWidth / $size['width'];
        $scaleY = $pageHeight / $size['height'];
        $scale = min($scaleX, $scaleY);

        // Calculate new width and height
        $newWidth = $size['width'] * $scale;
        $newHeight = $size['height'] * $scale;

        // Center the page
        $posX = ($pageWidth - $newWidth) / 2;
        $posY = ($pageHeight - $newHeight) / 2;

        //  Center watermark image
        list($originalWidth, $originalHeight) = getimagesize($imagePath);
        $imageWidth = 80;
        $imageHeight = ($imageWidth / $originalWidth) * $originalHeight;
        $imageX = $pageWidth / 2 - ($imageWidth / 2);
        $imageY = $pageHeight / 2 - ($imageHeight / 2) - 10;

        $pdf->AddCenteredImage($imagePath, $imageX, $imageY, $imageWidth, $imageHeight);

        // Add the scaled PDF page
        $pdf->useTemplate($tplIdx, $posX, $posY, $newWidth, $newHeight);


        // Add watermark text
        $textY = $imageY + $imageHeight + 5;
        $pdf->CenteredText($textY, "Downloaded by: $username", 10, '', 200, 200, 200);

        $timestampY = $textY + 5;
        $pdf->CenteredText($timestampY, "Downloaded on: $timestamp", 10, '', 200, 200, 200);

        $pdf->CenteredText($pageHeight - 15, utf8_decode("Proprietary content. © LIBA.edu. All Rights Reserved."), 10, '', 200, 200, 200);
        $pdf->CenteredText($pageHeight - 10, "This file is meant for personal use by $email only.", 10, '', 255, 200, 200);
        $pdf->CenteredText($pageHeight - 5, "Sharing or publishing the contents in part or full is liable for legal action.", 10, '', 255, 200, 200);
    }

    // Output the PDF to browser
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="materials.pdf"');
    $pdf->Output('I'); 
}

try {
    $tempPdf = downloadPdfFromUrl($pdfUrl);
    $imagePath = 'logo.png';

    // Initialize Moodle environment to get user details
    // require_once('../config.php'); // Path to Moodle's config.php
    require_once(__DIR__ . '/../config.php');
    define('NO_MOODLE_COOKIES', true); // We don't want cookies
    require_login(); 
    
    global $USER, $DB;
    
    $username = $USER->username ?? null;
    $email = $USER->email ?? null;

    if (!isset($USER->id) || empty($USER->id)) {
        die("Error: No logged-in user detected.");
    }

    if (!$username || !$email) {
        $user = $DB->get_record('user', ['id' => $USER->id], 'username, email');
        if ($user) {
            $username = $user->username;
            $email = $user->email;
        }
    }

    if (!$username || !$email) {
        $username = 'Guest User';
        $email = 'guest@example.com';
    }

    // Apply watermark with user details
    addWatermark($tempPdf, $email, $username, $imagePath);

    // Cleanup
    unlink($tempPdf);
    
} catch (Exception $e) {
    // Fallback to original PDF if watermarking fails
    header("Location: $pdfUrl");
    exit;
}