<?php
// app/Services/FileSearchService.php

namespace App\Services;
use Spatie\PdfToText\Pdf;
use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use Morilog\Jalali\Jalalian;
use Normalizer;
// class FileSearchService
// {
// public function searchInPdf($filePath, $searchTerm)
// {
//     $parser = new PdfParser();
//     $pdf = $parser->parseFile($filePath);
//     $text = $pdf->getText();

//     return $this->highlightMatches($text, $searchTerm);
// }


// private function highlightMatches($text, $searchTerm)
// {
//     $pattern = '/(' . preg_quote($searchTerm, '/') . ')/i';
//     $highlighted = preg_replace($pattern, '<mark>$1</mark>', $text);

//     return [
//         'original' => $text,
//         'highlighted' => $highlighted,
//         'matches' => preg_match_all($pattern, $text)
//     ];
// }
// public function searchInFile($filePath, $searchTerm)
// {
//     $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

//     switch ($extension) {
//         case 'pdf':
//             return $this->searchInPdf($filePath, $searchTerm);
//         case 'doc':
//         case 'docx':
//             // return $this->searchInWord($filePath, $searchTerm);
//         default:
//             throw new \Exception("فرمت فایل پشتیبانی نمی‌شود");
//     }
// }
// }
class FileSearchService
{
    private $parser;

    public function __construct()
    {
        $this->parser = new PdfParser();
    }
    private function normalizePersianText($text)
    {
        // تابع نرمال‌سازی بالا را اینجا قرار دهید
        // ...
    }

    public function searchInPdf($filePath, $searchTerm)
    {
        try {
        // استفاده از pdftotext که دقت بالاتری دارد
        $text = (new Pdf())
            ->setPdf($filePath)
            ->setBinPath('C:/Git/mingw64/bin/pdftotext')
            ->setOptions(['-enc', 'UTF-8'])
            ->text();

        $normalizedText = normalizer_normalize($text, \Normalizer::NFC);
        $normalizedSearch = normalizer_normalize($searchTerm, \Normalizer::NFC);

        // جستجو با الگوی بهتر
        preg_match_all('/.{0,50}' . preg_quote($normalizedSearch, '/') . '.{0,50}/iu', 
                      $normalizedText, $matches);

        return [
            'found' => !empty($matches[0]),
            'matches' => $matches[0],
            'count' => count($matches[0])
        ];

    } catch (\Exception $e) {
        throw new \Exception("Error processing PDF: " . $e->getMessage());
    }
        // try {
        //     $parser = new PdfParser();
        //     $pdf = $parser->parseFile($filePath);
        //     //$text = mb_convert_encoding($pdf->getText(), 'UTF-8', 'auto');

        //     // نرمال‌سازی برای جستجوی بهتر
        //     // $normalizedText = normalizer_normalize($text, Normalizer::NFC);
        //     $normalizedSearch = normalizer_normalize($searchTerm, Normalizer::NFC);

        //     //return mb_stripos($normalizedText, $normalizedSearch) !==false ;
        //     $pages = $pdf->getPages();
        //     $text = '';
        //     $results = [];

        //     foreach ($pages as $pageNumber => $page) {
        //         $pageText = $page->getText();
        //         $encoding = mb_detect_encoding($pageText, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        //         $text .= $encoding !== 'UTF-8' ? mb_convert_encoding($pageText, 'UTF-8', $encoding) : $pageText;
                // $encodededTextPage = mb_convert_encoding($text, 'UTF-8', 'auto');
                // $normalizedTextPage = normalizer_normalize($encodededTextPage, Normalizer::NFC);
                // dd($normalizedTextPage);
                // if (stripos($normalizedTextPage, $normalizedSearch) !== false) {
                //     // همه موارد پیدا شده در صفحه
                //     preg_match_all('/(.{0,30}' . preg_quote($normalizedSearch, '/') . '.{0,30})/iu', $normalizedTextPage, $matches);

                //     $results[] = [
                //         'page' => $pageNumber + 1,
                //         'matches' => $matches[0], // بخشی از متن اطراف کلمه
                //     ];
                // }
            // }
        //     $text = preg_replace('/[^\x{0600}-\x{06FF}\x{0000}-\x{007F}]/u', ' ', $text);
        // $text = preg_replace('/\s+/', ' ', $text);

        // $normalizedText = normalizer_normalize($text, \Normalizer::NFC);
        // $normalizedSearch = normalizer_normalize($searchTerm, \Normalizer::NFC);

        // // جستجوی پیشرفته‌تر
        // $pattern = '/\b(?:[^\s]+\s){0,3}' . preg_quote($normalizedSearch, '/') . '(?:\s[^\s]+){0,3}\b/iu';
        // preg_match_all($pattern, $normalizedText, $matches);

        // return [
        //     'found' => !empty($matches[0]),
        //     'matches' => array_slice($matches[0], 0, 10), // 10 مورد اول
        //     'total_count' => count($matches[0])
        // ];

            // return response()->json([
            //     'keyword' => $normalizedSearch,
            //     'results' => $results,
            // ]);

            // $pdf = $this->parser->parseFile($filePath);
            // $text = $pdf->getText();

            // // نرمال‌سازی متن
            // $normalizedText = normalizer_normalize($text, Normalizer::NFC);
            // $normalizedText = $this->normalizePersianText($text);
            // $normalizedSearch = $this->normalizePersianText($searchTerm);

            // جستجو با حساسیت به حروف
            // return mb_stripos($normalizedText, $normalizedSearch) !== false;

        // } catch (\Exception $e) {
        //     throw new \Exception("Error processing PDF: " . $e->getMessage());
        // }
    }


    // تابع برای پیدا کردن تمام موقعیت‌های کلمه
    public function findAllOccurrences($filePath, $searchTerm)
    {
        $pdf = $this->parser->parseFile($filePath);
        $text = $pdf->getText();
        $normalizedText = $this->normalizePersianText($text);
        $normalizedSearch = $this->normalizePersianText($searchTerm);

        $positions = [];
        $offset = 0;

        while (($pos = mb_stripos($normalizedText, $normalizedSearch, $offset)) !== false) {
            $positions[] = $pos;
            $offset = $pos + mb_strlen($normalizedSearch);
        }

        return $positions;
    }
}