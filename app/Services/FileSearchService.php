<?php
// app/Services/FileSearchService.php

namespace App\Services;

use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;

class FileSearchService
{
    public function searchInPdf($filePath, $searchTerm)
    {
        $parser = new PdfParser();
        $pdf = $parser->parseFile($filePath);
        $text = $pdf->getText();
        
        return $this->highlightMatches($text, $searchTerm);
    }

    
    private function highlightMatches($text, $searchTerm)
    {
        $pattern = '/(' . preg_quote($searchTerm, '/') . ')/i';
        $highlighted = preg_replace($pattern, '<mark>$1</mark>', $text);
        
        return [
            'original' => $text,
            'highlighted' => $highlighted,
            'matches' => preg_match_all($pattern, $text)
        ];
    }
    public function searchInFile($filePath, $searchTerm)
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        switch ($extension) {
            case 'pdf':
                return $this->searchInPdf($filePath, $searchTerm);
            case 'doc':
            case 'docx':
                // return $this->searchInWord($filePath, $searchTerm);
            default:
                throw new \Exception("فرمت فایل پشتیبانی نمی‌شود");
        }
    }
}