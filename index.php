<?php
$inputPDF = 'sample.pdf'; // Original file
$outputPDF = 'watermarked.pdf'; // Watermarked file
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Watermark Preview</title>
</head>
<body>
    <h2>PDF Watermark System</h2>

    <!-- Link to generate watermark -->
    <p><a href="watermark.php">sample.pdf</a></p>

    <?php if (file_exists($outputPDF)) : ?>
        <h3>Watermarked PDF Preview:</h3>
        
        <!-- Embed PDF Preview -->
        <iframe src="<?php echo $outputPDF; ?>"width="900" height="900"></iframe>
    <?php endif; ?>

</body>
</html>

 <?php
$inputPDF = 'sample.pdf'; // Original file
?>


