<?php

use App\Mail\ResetPassword;
use App\User;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1\FileSearchController3;
use App\Http\Controllers\V1\FileSearchController4;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
//FileSearchController
Route::get('/test', [FileSearchController4::class, 'searchPdf']);
Route::get('/test3', [FileSearchController3::class, 'searchInPdf']);
Route::get('/test-ocr', [FileSearchController3::class, 'searchOCR']);
// Route::get('/pdf/debug', [FileSearchController3::class, 'debugPage']);
function detectBestEncoding($text)
{
    // لیست encoding های معتبر و تست شده
    $encodings = ['UTF-8', 'Windows-1256', 'ISO-8859-6', 'Windows-1252', 'ISO-8859-1'];
    
    // اولویت با UTF-8
    if (mb_check_encoding($text, 'UTF-8')) {
        $converted = $text;
        if (preg_match('/[\x{0600}-\x{06FF}]/u', $converted)) {
            return 'UTF-8';
        }
    }
    
    // تست سایر encoding ها
    foreach ($encodings as $encoding) {
        if ($encoding === 'UTF-8') continue; // قبلاً تست شد
        
        if (mb_check_encoding($text, $encoding)) {
            $converted = mb_convert_encoding($text, 'UTF-8', $encoding);
            if (preg_match('/[\x{0600}-\x{06FF}]/u', $converted)) {
                return $encoding;
            }
        }
    }
    
    // auto detection با handle error
    $detected = @mb_detect_encoding($text, $encodings, true);
    if ($detected && in_array($detected, $encodings)) {
        return $detected;
    }
    
    // Fallback به Windows-1256 برای فارسی
    return 'Windows-1256';
}
Route::get('/test-pdftotext-direct', function() {
    $binPath = 'C:/Program Files/Git/mingw64/bin/pdftotext';
    
    // بررسی وجود فایل
    if (!file_exists($binPath)) {
        return response()->json([
            'error' => 'File not found',
            'path' => $binPath
        ]);
    }

    // تست اجرا
    $output = null;
    $return = null;
    exec('"' . $binPath . '" -v 2>&1', $output, $return);

    return response()->json([
        'path' => $binPath,
        'exists' => file_exists($binPath),
        'executable' => is_executable($binPath),
        'return_code' => $return,
        'output' => $output
    ]);
});
Route::get('/test-encoding-detection', function() {
    try {
        $filePath = public_path('storage/files/test.pdf');
        
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($filePath);
        $rawText = $pdf->getText();
        
        // لیست encoding های معتبر
        $validEncodings = [
            'UTF-8',
            'Windows-1256', 
            'ISO-8859-6',
            'Windows-1252',
            'ISO-8859-1',
            'ASCII'
        ];
        
        $results = [];
        foreach ($validEncodings as $encoding) {
            try {
                $converted = @mb_convert_encoding($rawText, 'UTF-8', $encoding);
                $persianChars = preg_match_all('/[\x{0600}-\x{06FF}]/u', $converted, $matches);
                
                $results[$encoding] = [
                    'persian_chars_count' => $persianChars,
                    'preview' => substr($converted, 0, 200),
                    'is_valid_encoding' => mb_check_encoding($rawText, $encoding)
                ];
            } catch (\Exception $e) {
                $results[$encoding] = [
                    'error' => $e->getMessage(),
                    'is_valid_encoding' => false
                ];
            }
        }
        
        // تست با تابع بهبود یافته
        $bestEncoding = detectBestEncoding($rawText);
        $bestConverted = $bestEncoding ? mb_convert_encoding($rawText, 'UTF-8', $bestEncoding) : $rawText;
        
        return response()->json([
            'raw_text_length' => strlen($rawText),
            'best_encoding' => $bestEncoding,
            'encoding_tests' => $results,
            'best_converted_preview' => substr($bestConverted, 0, 500),
            'contains_persian' => preg_match('/[\x{0600}-\x{06FF}]/u', $bestConverted)
        ]);
        
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()]);
    }
});

Route::get('/test-encoding-detection2', function() {
    try {
        $filePath = public_path('storage/files/test.pdf');
        
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($filePath);
        $rawText = $pdf->getText();
        
        // تست encoding های مختلف
        $encodings = ['UTF-8', 'Windows-1256', 'ISO-8859-6', 'Windows-1252'];
        $results = [];
        
        foreach ($encodings as $encoding) {
            $converted = mb_convert_encoding($rawText, 'UTF-8', $encoding);
            $persianChars = preg_match_all('/[\x{0600}-\x{06FF}]/u', $converted, $matches);
            
            $results[$encoding] = [
                'persian_chars_count' => $persianChars,
                'preview' => substr($converted, 0, 200),
                'valid_encoding' => mb_check_encoding($rawText, $encoding)
            ];
        }
        
        // auto detection
        $autoDetected = mb_detect_encoding($rawText, $encodings, true);
        $autoConverted = $autoDetected ? mb_convert_encoding($rawText, 'UTF-8', $autoDetected) : $rawText;
        
        return response()->json([
            'raw_text_length' => strlen($rawText),
            'auto_detected_encoding' => $autoDetected,
            'encoding_tests' => $results,
            'auto_converted_preview' => substr($autoConverted, 0, 500)
        ]);
        
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()]);
    }
});
function cleanMalformedUtf8($text)
{
    // حذف کاراکترهای کنترل غیرمجاز
    $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);
    
    // جایگزینی کاراکترهای UTF-8 معیوب
    $text = preg_replace_callback(
        '/[\x00-\x7F]|[\xC2-\xDF][\x80-\xBF]|\xE0[\xA0-\xBF][\x80-\xBF]|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}|\xED[\x80-\x9F][\x80-\xBF]|\xF0[\x90-\xBF][\x80-\xBF]{2}|[\xF1-\xF3][\x80-\xBF]{3}|\xF4[\x80-\x8F][\x80-\xBF]{2}/',
        function ($match) {
            return $match[0];
        },
        $text
    );
    
    // حذف sequences معیوب
    $text = iconv('UTF-8', 'UTF-8//IGNORE', $text);
    
    return $text;
}
function safeConvertEncoding($text, $sourceEncoding)
{
    if ($sourceEncoding === 'UTF-8') {
        return $text; // اگر منبع هم UTF-8 است، نیاز به تبدیل نیست
    }
    
    // ابتدا مطمئن شویم متن در encoding منبع معتبر است
    if (!mb_check_encoding($text, $sourceEncoding)) {
        throw new \Exception("Text is not valid $sourceEncoding");
    }
    
    // تبدیل با error handling
    $converted = @iconv($sourceEncoding, 'UTF-8//TRANSLIT//IGNORE', $text);
    if ($converted === false) {
        $converted = @mb_convert_encoding($text, 'UTF-8', $sourceEncoding);
    }
    
    if ($converted === false) {
        throw new \Exception("Failed to convert from $sourceEncoding to UTF-8");
    }
    
    // تمیز کردن نهایی
    return cleanMalformedUtf8($converted);
}
function safeSubstr($text, $start, $length)
{
    if (!mb_check_encoding($text, 'UTF-8')) {
        $text = cleanMalformedUtf8($text);
    }
    
    return mb_substr($text, $start, $length, 'UTF-8');
}

// تابع برای تشخیص خودکار بهترین encoding
function autoDetectEncoding($text)
{
    $encodings = ['Windows-1256', 'ISO-8859-6', 'Windows-1252', 'UTF-8'];
    
    foreach ($encodings as $encoding) {
        try {
            $converted = safeConvertEncoding($text, $encoding);
            $persianChars = preg_match_all('/[\x{0600}-\x{06FF}]/u', $converted);
            
            if ($persianChars > 0) {
                return [
                    'encoding' => $encoding,
                    'text' => $converted,
                    'score' => $persianChars
                ];
            }
        } catch (\Exception $e) {
            continue;
        }
    }
    
    // Fallback: return original text with UTF-8 cleaning
    return [
        'encoding' => 'UTF-8',
        'text' => cleanMalformedUtf8($text),
        'score' => 0
    ];
}
Route::get('/test-encoding-detection-safe', function() {
    try {
        $filePath = public_path('storage/files/test.pdf');
        
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($filePath);
        $rawText = $pdf->getText();
        
        // تمیز کردن کاراکترهای UTF-8 معیوب قبل از پردازش
        $cleanedText = cleanMalformedUtf8($rawText);
        
        // لیست encoding های معتبر
        $validEncodings = ['UTF-8', 'Windows-1256', 'ISO-8859-6', 'Windows-1252'];
        
        $results = [];
        $bestResult = ['encoding' => null, 'persian_chars' => 0, 'text' => ''];
        
        foreach ($validEncodings as $encoding) {
            try {
                $converted = safeConvertEncoding($cleanedText, $encoding);
                $persianChars = preg_match_all('/[\x{0600}-\x{06FF}]/u', $converted, $matches);
                
                $results[$encoding] = [
                    'persian_chars_count' => $persianChars,
                    'preview' => safeSubstr($converted, 0, 200),
                    'is_valid' => mb_check_encoding($converted, 'UTF-8')
                ];
                
                // پیدا کردن بهترین نتیجه
                if ($persianChars > $bestResult['persian_chars']) {
                    $bestResult = [
                        'encoding' => $encoding,
                        'persian_chars' => $persianChars,
                        'text' => $converted
                    ];
                    }
                
            } catch (\Exception $e) {
                $results[$encoding] = ['error' => $e->getMessage()];
            }
        }
        
        return response()->json([
            'raw_text_length' => strlen($rawText),
            'cleaned_text_length' => strlen($cleanedText),
            'best_encoding' => $bestResult['encoding'],
            'best_persian_chars' => $bestResult['persian_chars'],
            'best_preview' => safeSubstr($bestResult['text'], 0, 500),
            'encoding_tests' => $results,
            'is_valid_utf8' => mb_check_encoding($bestResult['text'], 'UTF-8')
        ]);
        
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()]);
    }
});
function searchInPdf($filePath, $searchTerm)
{
    try {
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($filePath);
        $text = $pdf->getText();
        
        // تمیز کردن متن UTF-8
        $cleanText = cleanUtf8Text($text);
        
        // نرمال‌سازی فارسی
        $normalizedText = normalizePersianText($cleanText);
        $normalizedSearch = normalizePersianText($searchTerm);
        
        // جستجو
        return mb_stripos($normalizedText, $normalizedSearch) !== false;
        
    } catch (\Exception $e) {
        throw new \Exception("Error processing PDF: " . $e->getMessage());
    }
}
function cleanUtf8Text($text)
{
    // حذف کاراکترهای کنترل غیرضروری
    $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', ' ', $text);
    
    // حذف کاراکترهای غیر قابل چاپ
    $text = preg_replace('/[^\x{0600}-\x{06FF}\x{0000}-\x{007F}\s\.\,\;\:\!\\?\(\)\[\]\{\}\d]/u', ' ', $text);
    
    // جایگزینی multiple spaces و tabs
    $text = preg_replace('/\s+/', ' ', $text);
    $text = str_replace("\t", ' ', $text);
    
    return trim($text);
}
function normalizePersianText($text)
{
    // جایگزینی کاراکترهای مشابه فارسی
    $replacements = [
        'ك' => 'ک', 'ي' => 'ی', 'ة' => 'ه', 'ؤ' => 'و',
        'أ' => 'ا', 'إ' => 'ا', 'ٱ' => 'ا', 'ۂ' => 'ه',
        'ۃ' => 'ه', 'ۀ' => 'ه', 'ﭘ' => 'پ', 'ﭙ' => 'پ',
        'ﭽ' => 'چ', 'ﭼ' => 'چ', 'ﮊ' => 'ژ', 'ﮋ' => 'ژ',
        'ﮔ' => 'گ', 'ﮎ' => 'ک', 'ﮏ' => 'ک', 'ﮐ' => 'ک',
        'ﮑ' => 'ک', 'ﮒ' => 'گ', 'ﮓ' => 'گ', 'ﮕ' => 'گ',
        'ﮖ' => 'گ', 'ﯼ' => 'ی', 'ﯽ' => 'ی', 'ﯾ' => 'ی',
        'ﯿ' => 'ی',
    ];
    
    $text = strtr($text, $replacements);
    
    // نرمال‌سازی فاصله‌ها
    $text = preg_replace('/\s+/', ' ', $text);
    
    return trim($text);
}
Route::get('/test-final-search', function() {
    try {
        $filePath = public_path('storage/files/test.pdf');
        $searchTerm = 'کد'; // از متن نمونه شما
        
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($filePath);
        $text = $pdf->getText();
        
        $cleanText = cleanUtf8Text($text);
        $normalizedText = normalizePersianText($cleanText);
        $normalizedSearch = normalizePersianText($searchTerm);
        
        $position = mb_stripos($normalizedText, $normalizedSearch);
        
        // محاسبه صحیح snippet
        $snippet = '';
        if ($position !== false) {
            $start = max(0, $position - 30);
            $length = min(60 + mb_strlen($searchTerm), mb_strlen($normalizedText) - $start);
            $snippet = mb_substr($normalizedText, $start, $length);
            
            // اضافه کردن ... اگر در ابتدا یا انتها بریده شده
            if ($start > 0) {
                $snippet = '...' . $snippet;
            }
            if ($start + $length < mb_strlen($normalizedText)) {
                $snippet = $snippet . '...';
            }
        }
        
        return response()->json([
            'success' => true,
            'found' => $position !== false,
            'position' => $position,
            'search_term' => $searchTerm,
            'text_preview' => $snippet ?: 'Not found',
            'text_length' => mb_strlen($normalizedText),
            'persian_chars_count' => preg_match_all('/[\x{0600}-\x{06FF}]/u', $normalizedText),
            'search_term_length' => mb_strlen($normalizedSearch)
        ]);
        
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()]);
    }
});
Route::get('/test-search-multiple', function() {
    try {
        $filePath = public_path('storage/files/test.pdf');
        $searchTerm = 'کد';
        
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($filePath);
        $text = $pdf->getText();
        
        $cleanText = cleanUtf8Text($text);
        $normalizedText = normalizePersianText($cleanText);
        $normalizedSearch = normalizePersianText($searchTerm);
        
        // پیدا کردن همه matches
        $matches = [];
        $offset = 0;
        
        while (($position = mb_stripos($normalizedText, $normalizedSearch, $offset)) !== false) {
            $start = max(0, $position - 30);
            $end = min($position + 30 + mb_strlen($normalizedSearch), mb_strlen($normalizedText));
            $length = $end - $start;
            
            $snippet = mb_substr($normalizedText, $start, $length);
            
            // اضافه کردن ...
            if ($start > 0) $snippet = '...' . $snippet;
            if ($end < mb_strlen($normalizedText)) $snippet = $snippet . '...';
             $matches[] = [
                'position' => $position,
                'snippet' => $snippet,
                'exact_match' => mb_substr($normalizedText, $position, mb_strlen($normalizedSearch))
            ];
            
            $offset = $position + mb_strlen($normalizedSearch);
        }
        
        return response()->json([
            'success' => true,
            'search_term' => $searchTerm,
            'total_matches' => count($matches),
            'matches' => array_slice($matches, 0, 5), // 5 مورد اول
            'text_length' => mb_strlen($normalizedText)
        ]);
        
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()]);
    }
});
function cleanSnippet($text)
{
    // حذف کاراکترهای کنترل
    $text = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $text);
    
    // حذف کاراکترهای غیر قابل چاپ
    $text = preg_replace('/[^\x{0600}-\x{06FF}\x{0000}-\x{007F}\s\.\,\;\:\!\\?\(\)]/u', ' ', $text);
    
    // جایگزینی multiple spaces
    $text = preg_replace('/\s+/', ' ', $text);
    
    return trim($text);
}
function highlightSearchTerm($snippet, $searchTerm)
{
    $pattern = '/' . preg_quote($searchTerm, '/') . '/ui';
    return preg_replace($pattern, '**$0**', $snippet);
}
Route::get('/test-final-search2', function() {
    try {
        $filePath = public_path('storage/files/test.pdf');
        $searchTerm = 'کد';
        
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($filePath);
        $text = $pdf->getText();
        
        $cleanText = cleanUtf8Text($text);
        $normalizedText = normalizePersianText($cleanText);
        $normalizedSearch = normalizePersianText($searchTerm);
        
        $position = mb_stripos($normalizedText, $normalizedSearch);
        
        if ($position === false) {
            return response()->json([
                'success' => true,
                'found' => false,
                'search_term' => $searchTerm,
                'message' => 'عبارت یافت نشد'
            ]);
        }
        // محاسبه snippet با اطمینان از valid UTF-8
        $start = max(0, $position - 25);
        $snippetLength = min(50 + mb_strlen($normalizedSearch), mb_strlen($normalizedText) - $start);
        
        $snippet = mb_substr($normalizedText, $start, $snippetLength, 'UTF-8');
        
        // تمیز کردن نهایی snippet
        $snippet =cleanSnippet($snippet);
        
        // هایلایت کردن عبارت پیدا شده در snippet
        $highlightedSnippet = highlightSearchTerm($snippet, $normalizedSearch);
        
        return response()->json([
            'success' => true,
            'found' => true,
            'position' => $position,
            'search_term' => $searchTerm,
            'snippet' => $snippet,
            'highlighted_snippet' => $highlightedSnippet,
            'text_length' => mb_strlen($normalizedText),
            'encoding' => mb_detect_encoding($snippet)
        ]);
        
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()]);
    }
});
Route::get('/test-debug-search', function() {
    try {
        $filePath = public_path('storage/files/test.pdf');
        $searchTerm = 'کد';
        
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($filePath);
        $rawText = $pdf->getText();
        
        // نمایش اطلاعات کامل
        return response()->json([
            'raw_text_length' => strlen($rawText),
            'raw_first_100' => substr($rawText, 0, 100),
            'raw_last_100' => substr($rawText, -100),
            'is_valid_utf8' => mb_check_encoding($rawText, 'UTF-8'),
            'detected_encoding' => mb_detect_encoding($rawText),
            'persian_chars_raw' => preg_match_all('/[\x{0600}-\x{06FF}]/u', $rawText),
            
            // بعد از تمیز کردن
            'clean_text' => cleanUtf8Text($rawText),
            'clean_first_100' => substr(cleanUtf8Text($rawText), 0, 100),
            'normalized_text' => normalizePersianText(cleanUtf8Text($rawText)),
            'normalized_first_100' => substr(normalizePersianText(cleanUtf8Text($rawText)), 0, 100)
        ]);
        
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()]);
    }
});
   Route::get('/test-hex-analysis', function() {
    try {
        $filePath = public_path('storage/files/test.pdf');
        
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($filePath);
        $text = $pdf->getText();
        
        // تحلیل hex первых 100 کاراکتر
        $first100 = substr($text, 0, 100);
        $hexAnalysis = [];
        
        for ($i = 0; $i < strlen($first100); $i++) {
            $char = $first100[$i];
            $hex = bin2hex($char);
            $hexAnalysis[] = [
                'position' => $i,
                'char' => $char,
                'hex' => $hex,
                'is_printable' => ctype_print($char),
                'is_persian' => preg_match('/[\x{0600}-\x{06FF}]/u', $char)
            ];
        }
        
        return response()->json([
            'hex_analysis' => $hexAnalysis,
            'full_text_sample' => substr($text, 0, 200)
        ]);
        
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()]);
    }
});   
Route::get('/test-complete-search', function() {
    try {
        $filePath = public_path('storage/files/test.pdf');
        $searchTerm = 'کد';
        
        // لاگ کامل فرآیند
        $log = [];
        
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($filePath);
        $rawText = $pdf->getText();
        
        $log['raw_text_info'] = [
            'length' => strlen($rawText),
            'first_50_chars' => substr($rawText, 0, 50),
            'is_utf8' => mb_check_encoding($rawText, 'UTF-8')
        ];
        
        // تمیز کردن
        $cleanText = cleanUtf8Text($rawText);
        $log['clean_text_info'] = [
            'length' => strlen($cleanText),
            'first_50_chars' => substr($cleanText, 0, 50)
        ];
        
        // نرمال‌سازی
        $normalizedText = normalizePersianText($cleanText);
        $normalizedSearch = normalizePersianText($searchTerm);
        $log['normalized_text_info'] = [
            'length' => mb_strlen($normalizedText),
            'first_50_chars' => mb_substr($normalizedText, 0, 50),
            'search_term' => $normalizedSearch
        ];
        
        // جستجو
        $position = mb_stripos($normalizedText, $normalizedSearch);
        
        if ($position !== false) {
            $snippet = mb_substr($normalizedText, max(0, $position - 20), 40, 'UTF-8');
            $log['search_result'] = [
                'found' => true,
                'position' => $position,
                'snippet' => $snippet
            ];
        } else {
            $log['search_result'] = ['found' => false];
        }
        
        return response()->json($log);
        
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()]);
    }
});
function getPersianSnippet($text, $position, $searchTerm, $contextChars = 30)
{
    // محاسبه محدوده snippet
    $start = max(0, $position - $contextChars);
    $end = min(mb_strlen($text), $position + mb_strlen($searchTerm) + $contextChars);
    $length = $end - $start;
    
    // استخراج snippet
    $snippet = mb_substr($text, $start, $length, 'UTF-8');
    
    // اضافه کردن نشانه‌های برش
    if ($start > 0) {
        $snippet = '...' . $snippet;
    }
    if ($end < mb_strlen($text)) {
        $snippet = $snippet . '...';
    }
    
    // هایلایت کردن عبارت جستجو
    $snippet = highlightPersianText($snippet, $searchTerm);
    
    return $snippet;
}
function highlightPersianText($text, $searchTerm)
{
    $pattern = '/' . preg_quote($searchTerm, '/') . '/ui';
    return preg_replace($pattern, '>>>$0<<<', $text);
}
function cleanPersianSnippet($snippet)
{
    // حذف کاراکترهای کنترل
    $snippet = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $snippet);
    
    // حذف کاراکترهای غیر فارسی/لاتین
    $snippet = preg_replace('/[^\x{0600}-\x{06FF}\x{0000}-\x{007F}\s\.\,\;\:\!\\?\(\)\-]/u', ' ', $snippet);
    
    // جایگزینی multiple spaces
    $snippet = preg_replace('/\s+/', ' ', $snippet);
    
    return trim($snippet);
}
Route::get('/test-final-search-corrected', function() {
    try {
        $filePath = public_path('storage/files/test.pdf');
        $searchTerm = 'کد';
        
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($filePath);
        $text = $pdf->getText();
        
        $cleanText = cleanUtf8Text($text);
        $normalizedText = normalizePersianText($cleanText);
        $normalizedSearch = normalizePersianText($searchTerm);
        
        $position = mb_stripos($normalizedText, $normalizedSearch);
        
        if ($position === false) {
            return response()->json([
                'success' => true,
                'found' => false,
                'search_term' => $searchTerm
            ]);
        }
        
        // ایجاد snippet با جهت‌دهی صحیح فارسی
        $snippet = getPersianSnippet($normalizedText, $position, $normalizedSearch, 30);
        return response()->json([
            'success' => true,
            'found' => true,
            'position' => $position,
            'search_term' => $searchTerm,
            'snippet' => $snippet,
            'snippet_clean' => cleanPersianSnippet($snippet),
            'text_sample' => mb_substr($normalizedText, 0, 100) . '...',
            'text_length' => mb_strlen($normalizedText)
        ]);
        
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()]);
    }
});  
