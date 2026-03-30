<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class DocumentTextExtractorService
{
    public function __construct(private ChatGPTServiceV2 $chatGPT)
    {
    }

    public function extract(UploadedFile $file): string
    {
        $mimeType = $file->getMimeType();

        return match ($mimeType) {
            'text/plain' => $this->extractTxt($file),
            'application/pdf' => $this->extractPdf($file),
            default => throw new \InvalidArgumentException("Unsupported file type: {$mimeType}"),
        };
    }

    private function extractTxt(UploadedFile $file): string
    {
        return file_get_contents($file->getRealPath());
    }

    private function extractPdf(UploadedFile $file): string
    {
        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($file->getRealPath());
            $pages = $pdf->getPages();

            $text = '';
            foreach ($pages as $page) {
                $text .= $page->getText() . "\n";
            }

            return trim($text);
        } catch (\Exception $e) {
            Log::warning('PDF parser failed, falling back to OCR', [
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage(),
            ]);

            return $this->extractPdfViaOcr($file);
        }
    }

    private function extractPdfViaOcr(UploadedFile $file): string
    {
        $fileContent = file_get_contents($file->getRealPath());
        $base64Content = base64_encode($fileContent);

        try {
            $response = $this->chatGPT->callOcr($base64Content);
            return $response;
        } catch (\Exception $e) {
            Log::error('OCR extraction failed', [
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
