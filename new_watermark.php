<?php
require('fpdf/fpdf.php');
require('fpdi/src/autoload.php');
require('PHPWord\src\PhpWord\Autoloader.php');
\PhpOffice\PhpWord\Autoloader::register(); // Register the autoloader

use setasign\Fpdi\Fpdi;

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;


session_start();

// 1. Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    die("Error: User not logged in.");
}

// 2. Get parameters from URL
if (!isset($_GET['pdfUrl']) || empty($_GET['pdfUrl'])) {
    die("Error: No PDF URL provided.");
}

$pdfUrl = $_GET['pdfUrl'];
$originalFilename = isset($_GET['actualFilename']) ? $_GET['actualFilename'] : basename($pdfUrl);
$email = isset($_GET['email']) ? urldecode($_GET['email']) : ($_SESSION['email'] ?? '');

// 3. Get user details from session
$firstname = $_SESSION['firstname'] ?? '';
$lastname = $_SESSION['lastname'] ?? '';
// $email = $_SESSION['email'] ?? '';
$username = $firstname . ' ' . $lastname;

// 4. Download PDF function
function downloadPdfFromUrl($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $pdfData = curl_exec($ch);
    
    if ($pdfData === false) {
        die("Error: Unable to download PDF.");
    }

    $tempPdf = tempnam(sys_get_temp_dir(), 'pdf');
    file_put_contents($tempPdf, $pdfData);

    if (mime_content_type($tempPdf) !== 'application/pdf') {
        unlink($tempPdf);
        die("Error: Downloaded file is not a valid PDF.");
    }

    return $tempPdf;
}


// Download DOCX function
function downloadFileFromUrl($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $fileData = curl_exec($ch);
    
    if ($fileData === false) {
        die("Error: Unable to download file.");
    }

    // Save to a temporary file
    $tempFile = tempnam(sys_get_temp_dir(), 'file');
    file_put_contents($tempFile, $fileData);

    // Get MIME type to check file format
    $mimeType = mime_content_type($tempFile);

    if ($mimeType === 'application/pdf') {
        return ['file' => $tempFile, 'type' => 'pdf'];
    } elseif ($mimeType === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
        return ['file' => $tempFile, 'type' => 'docx'];
    } else {
        unlink($tempFile);
        die("Error: Unsupported file type. Only PDF and DOCX are allowed.");
    }
}



// 5. PDF Class with Watermarking
class PDF_Rotate extends Fpdi {
    function setPDFMetadata($title, $author) {
        // These methods must be called BEFORE adding any content
        $this->SetTitle($title);
        $this->SetAuthor($author);
        $this->SetSubject("Downloaded from ailms.shiksak.com");
        $this->SetKeywords("Watermarked PDF");
        
        // For FPDF compatibility
        $this->metadata = [
            'Title' => $title,
            'Author' => $author,
            'Subject' => 'Downloaded from ailms.shiksak.com',
            'Creator' => 'FPDF Watermark System'
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
        list($width, $height) = getimagesize($file);
        if ($x === null) {
            $x = ($this->GetPageWidth() - $w) / 2;
        }
        $this->Image($file, $x, $y, $w, $h);
    }
}

// 6. Main Watermarking Function
function addWatermarkToPdf($inputFile, $username, $email, $imagePath, $originalFilename) {
    $pdf = new PDF_Rotate();
    
    // Set metadata
    $title = pathinfo($originalFilename, PATHINFO_FILENAME);
    $pdf->setPDFMetadata($title, $username);

    $pageCount = $pdf->setSourceFile($inputFile);
    $timestamp = date("Y-m-d H:i:s");
    
    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
        $pdf->AddPage();
        $pageWidth = $pdf->GetPageWidth();
        $pageHeight = $pdf->GetPageHeight();
        
        // Add watermark image
        list($imgWidth, $imgHeight) = getimagesize($imagePath);
        $displayWidth = 80;
        $displayHeight = ($displayWidth / $imgWidth) * $imgHeight;
        $x = ($pageWidth - $displayWidth) / 2;
        $y = ($pageHeight - $displayHeight) / 2 - 10;
        
        $pdf->AddCenteredImage($imagePath, $x, $y, $displayWidth, $displayHeight);
        
        // Import PDF page
        $tplIdx = $pdf->importPage($pageNo);
        $pdf->useTemplate($tplIdx, 0, 0);

        // Add text watermarks
        $textY = $y + $displayHeight + 5;
        $pdf->CenteredText($textY, "Downloaded by $username", 10, '', 150, 150, 150);
        $pdf->CenteredText($textY + 5, "Downloaded on: $timestamp", 10, '', 150, 150, 150);
        
        // Footer text
        $pdf->CenteredText($pageHeight - 15, utf8_decode("Proprietary content. © Great Learning. All Rights Reserved."), 10, '', 150, 150, 150);
        $pdf->CenteredText($pageHeight - 10, "This file is meant for personal use by $email only.", 10, '', 255, 100, 100);
        $pdf->CenteredText($pageHeight - 5, "Sharing or publishing the contents in part or full is liable for legal action.", 10, '', 255, 100, 100);
    }


    // VERIFICATION CODE GOES HERE (right before Output)
    error_log("Attempting to set metadata:");
    error_log("Title: " . $title);
    error_log("Author: " . $username);
    error_log("Subject: Downloaded from ailms.shiksak.com");
    

    // Output with original filename
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $originalFilename . '"');

    
    $pdf->Output('I');
}


function addWatermarkToDocx($inputFile, $username, $email, $imagePath, $originalFilename) {
    $phpWord = IOFactory::load($inputFile);
    $section = $phpWord->getSections()[0]; // Get first section
    $timestamp = date("Y-m-d H:i:s");

    // 1. **Set Metadata (Equivalent to setPDFMetadata)**
    $phpWord->getDocInfo()->setCreator($username);
    $phpWord->getDocInfo()->setTitle(pathinfo($originalFilename, PATHINFO_FILENAME));
    $phpWord->getDocInfo()->setDescription("Downloaded from ailms.shiksak.com");
    $phpWord->getDocInfo()->setKeywords("Watermarked DOCX");

    // 2. **Add Centered Watermark Image (Equivalent to AddCenteredImage)**
    $header = $section->addHeader();
    // $header->addImage(
    //     $imagePath,
    //     [
    //         'width' => 80,  // Matches the PDF image width
    //         'height' => 50, // Adjusted height
    //         'wrappingStyle' => 'behind',
    //         'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
    //     ]
    // );

    $header->addImage(
        $imagePath,
        [
            'width' => 300,
            'height' => 200,
            'wrappingStyle' => 'behind',
            'positioning' => 'absolute',
            'posHorizontalRel' => 'page',
            'posVerticalRel' => 'page',
            'posHorizontal' => 'center',
            'posVertical' => 'center'
        ]
    );

    // 3. **Add Centered Text Watermark (Equivalent to CenteredText)**
    $textRun = $header->addTextRun([
        'alignment' => 'center',
        'positioning' => 'absolute',
        'posHorizontalRel' => 'page',
        'posVerticalRel' => 'page',
        'posHorizontal' => 'center',
        'posVertical' => 'center',
        'posVerticalOffset' => 30 // Moves text below the image
    ]);
    $textRun->addText("Downloaded by $username, on: $timestamp", ['size' => 10, 'color' => '808080']);
    $section->addTextBreak();
    // $textRun = $section->addTextRun(['alignment' => 'center']);
    // $textRun->addText("Downloaded on: $timestamp", ['size' => 10, 'color' => '808080']);

    // 4. **Add Footer with Legal Notice (Equivalent to Footer Section in PDF)**
    $footer = $section->addFooter();
    // $footer->addText("Proprietary content. © Great Learning. All Rights Reserved.", ['size' => 10, 'color' => '808080'], ['alignment' => 'center']);
    // $footer->addText("This file is meant for personal use by $email only.", ['size' => 10, 'color' => 'FF0000']);
    // $footer->addText("Sharing or publishing the contents in part or full is liable for legal action.", ['size' => 10, 'color' => 'FF0000']);


    // $textRun = $footer->addTextRun(['alignment' => 'center', 'spacing' => 80], ['alignment' => 'center']); // Reduced spacing
    // $textRun->addText("Proprietary content. © Great Learning. All Rights Reserved.", ['size' => 10, 'color' => '808080']);
    // $textRun->addText("This file is meant for personal use by $email only.", ['size' => 10, 'color' => 'FF0000']);
    // $textRun->addText(" Sharing or publishing the contents in part or full is liable for legal action.", ['size' => 10, 'color' => 'FF0000']);
    $footer->addTextRun(['alignment' => 'center'])->addText("Proprietary content. © Great Learning. All Rights Reserved.", ['size' => 10, 'color' => '808080']);
    $footer->addTextRun(['alignment' => 'center'])->addText("This file is meant for personal use by $email only.", ['size' => 10, 'color' => 'FF0000']);
    $footer->addTextRun(['alignment' => 'center'])->addText("Sharing or publishing the contents in part or full is liable for legal action.", ['size' => 10, 'color' => 'FF0000']);


    // 5. **Save the modified document**
    $outputFile = sys_get_temp_dir() . '/' . $originalFilename;
    $phpWordWriter = IOFactory::createWriter($phpWord, 'Word2007');
    $phpWordWriter->save($outputFile);

    // Serve the modified file
    header("Content-Description: File Transfer");
    header("Content-Disposition: attachment; filename=\"$originalFilename\"");
    header("Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document");
    readfile($outputFile);

    // Cleanup
    unlink($outputFile);
}

// 7. Execute the Process

try {
    // Download and check file type
    $fileInfo = downloadFileFromUrl($pdfUrl);
    $filePath = $fileInfo['file'];
    $fileType = $fileInfo['type'];
    $imagePath = 'liba-logo.png';

    if ($fileType === 'pdf') {
        addWatermarkToPdf($filePath, $username, $email, $imagePath, $originalFilename);
    } elseif ($fileType === 'docx') {
        addWatermarkToDocx($filePath, $username, $email, $imagePath, $originalFilename);
    } else {
        throw new Exception("Unsupported file type.");
    }

    // Cleanup
    unlink($filePath);
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}



