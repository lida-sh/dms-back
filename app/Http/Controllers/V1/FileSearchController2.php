<?php

namespace App\Http\Controllers\V1;
use App\Procedure;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FileSearchService;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\V1\Admin\ApiController;
use Smalot\PdfParser\Parser;
class FileSearchController2 extends ApiController
{
   public function searchInPdf($filePath, $searchTerm)
{
    try {
        $parser = new Parser();
        $pdf = $parser->parseFile($filePath);
        
        // استخراج متن با encoding detection پیشرفته
        $text = $this->extractTextWithProperEncoding($pdf);
        
        // تمیز کردن و نرمال‌سازی
        $cleanText = $this->cleanPersianText($text);
        $normalizedSearch = $this->normalizePersianText($searchTerm);
        
        // جستجوی پیشرفته
        return $this->advancedPersianSearch($cleanText, $normalizedSearch);
        
    } catch (\Exception $e) {
        throw new \Exception("Error processing PDF: " . $e->getMessage());
    }
}
private function extractTextWithProperEncoding($pdf)
{
    $text = '';
    $pages = $pdf->getPages();
    
    foreach ($pages as $page) {
        $pageText = $page->getText();
        
        // تشخیص encoding با الگوریتم پیشرفته
        $encoding = $this->detectEncoding($pageText);
        
        if ($encoding && $encoding !== 'UTF-8') {
            $pageText = mb_convert_encoding($pageText, 'UTF-8', $encoding);
        }
        
        $text .= $pageText . "\n";
    }
    
    return $text;
}
private function detectEncoding($text)
{
    // لیست encoding های ممکن برای فارسی
    $encodings = [
        'UTF-8',
        'Windows-1256', // Arabic/Farsi Windows
        'ISO-8859-6',   // Arabic/Farsi ISO
        'Windows-1252', // Western European
        'ISO-8859-1',   // Latin-1
        'CP1256',       // Arabic/Farsi
    ];
    
    // اولویت با UTF-8
    if (mb_check_encoding($text, 'UTF-8')) {
        return 'UTF-8';
    }
    
    // تست سایر encoding ها
    foreach ($encodings as $encoding) {
        if (mb_check_encoding($text, $encoding)) {
            // تأیید با pattern فارسی
            $converted = mb_convert_encoding($text, 'UTF-8', $encoding);
            if (preg_match('/[\x{0600}-\x{06FF}]/u', $converted)) {
                return $encoding;
            }
        }
    }
    
    // Fallback: auto detection
    $detected = mb_detect_encoding($text, $encodings, true);
    return $detected ?: 'Windows-1256'; // default assumption
}
private function cleanPersianText($text)
{
    // حذف کاراکترهای کنترل و غیر قابل چاپ
    $text = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $text);
    
    // حذف کاراکترهای غیر فارسی/لاتین
    $text = preg_replace('/[^\x{0600}-\x{06FF}\x{0000}-\x{007F}\s\.\,\;\:\!\\?\(\)\[\]\{\}\d]/u', ' ', $text);
    
    // جایگزینی multiple spaces
    $text = preg_replace('/\s+/', ' ', $text);
    
    // حذف فاصله‌های اضافه از ابتدا و انتها
    $text = trim($text);
    
    return $text;
}
private function normalizePersianText($text)
{
    // اگر intl فعال است از normalizer استفاده کن
    if (extension_loaded('intl') && function_exists('normalizer_normalize')) {
        $text = normalizer_normalize($text, \Normalizer::NFC);
    }
    
    // جایگزینی کاراکترهای مشابه فارسی
    $persianReplacements = [
        'ك' => 'ک', 'ي' => 'ی', 'ة' => 'ه', 'ؤ' => 'و',
        'أ' => 'ا', 'إ' => 'ا', 'ٱ' => 'ا', 'ۂ' => 'ه',
        'ۃ' => 'ه', 'ۀ' => 'ه', 'ﭘ' => 'پ', 'ﭙ' => 'پ',
        'ﭽ' => 'چ', 'ﭼ' => 'چ', 'ﮊ' => 'ژ', 'ﮋ' => 'ژ',
        'ﮔ' => 'گ', 'ﮎ' => 'ک', 'ﮏ' => 'ک', 'ﮐ' => 'ک',
        'ﮑ' => 'ک', 'ﮒ' => 'گ', 'ﮓ' => 'گ', 'ﮕ' => 'گ',
        'ﮖ' => 'گ', 'ﯼ' => 'ی', 'ﯽ' => 'ی', 'ﯾ' => 'ی',
        'ﯿ' => 'ی', 'ﹻ' => 'ی', 'ﹺ' => 'ی', 'ﹼ' => 'ی',
    ];
    
    $text = strtr($text, $persianReplacements);
    
    // نرمال‌سازی فاصله‌ها
    $text = preg_replace('/\s+/', ' ', $text);
    $text = trim($text);
    
    return $text;
}
private function advancedPersianSearch($text, $searchTerm)
{
    $results = [];
    $searchLength = mb_strlen($searchTerm);
    
    // برای هر کاراکتر در متن جستجو کن
    for ($i = 0; $i < mb_strlen($text); $i++) {
        $matchScore = 0;
        $matchedChars = '';
        
        // بررسی match برای هر کاراکتر از searchTerm
        for ($j = 0; $j < $searchLength; $j++) {
            if ($i + $j >= mb_strlen($text)) break;
            
            $textChar = mb_substr($text, $i + $j, 1);
            $searchChar = mb_substr($searchTerm, $j, 1);
            
            if ($textChar === $searchChar) {
                $matchScore++;
                $matchedChars .= $textChar;
            }
        }
         // اگر match قابل قبول است
        if ($matchScore >= max(3, $searchLength * 0.6)) { // حداقل 3 کاراکتر یا 60%匹配
            $start = max(0, $i - 20);
            $end = min(mb_strlen($text), $i + $searchLength + 20);
            $snippet = mb_substr($text, $start, $end - $start);
            
            $results[] = [
                'position' => $i,
                'score' => $matchScore / $searchLength,
                'matched_chars' => $matchedChars,
                'snippet' => '...' . $snippet . '...',
                'full_match' => $matchScore === $searchLength
            ];
            
            $i += $searchLength; // skip ahead
        }
    }
    
    return [
        'found' => !empty($results),
        'results' => $results,
        'total_matches' => count($results),
        'perfect_matches' => count(array_filter($results, fn($r) => $r['full_match']))
    ];
}
 
    
}
