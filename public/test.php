<?php
// بررسی فعال بودن exec

$pdftoppm = '"C:\\poppler-25.07.0\\Library\\bin\\pdftoppm.exe"';

// مسیر فایل PDF ورودی (باید وجود داشته باشد)
$pdfPath = 'C:\\xampp\\htdocs\\dms-back\\test.pdf';

// مسیر فولدر خروجی (باید فولدر موجود و قابل نوشتن باشد)
$outputFolder = 'C:\\xampp\\htdocs\\dms-back\\output';

// نام پایه برای فایل‌های خروجی (pdftoppm شماره صفحات را اضافه می‌کند)
$outputBaseName = $outputFolder . '\\output';

// ایجاد فولدر خروجی در صورت وجود نداشتن
if (!file_exists($outputFolder)) {
    mkdir($outputFolder, 0777, true);
}

// اجرای دستور pdftoppm برای تبدیل PDF به PNG
$command = $pdftoppm . " -png \"$pdfPath\" \"$outputBaseName\" 2>&1";
exec($command, $output, $return_var);

// نمایش نتیجه
echo "<strong>Command:</strong> $command<br>";
echo "<strong>Return code:</strong> $return_var<br>";
echo "<strong>Output:</strong><br>";
echo implode("<br>", $output);

// نمایش فایل‌های خروجی ایجاد شده
$files = glob($outputFolder . '\\output-*.png');
if (!empty($files)) {
    echo "<br><br><strong>Files created:</strong><br>";
    foreach ($files as $file) {
        echo basename($file) . "<br>";
    }
} else {
    echo "<br>No files were created.";
}
?>