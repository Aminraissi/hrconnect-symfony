<?php
namespace App\Service;


use Smalot\PdfParser\Parser as PdfParser;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mime\MimeTypes;
use thiagoalessio\TesseractOCR\TesseractOCR;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class FileContentChecker
{
    private PdfParser $pdfParser;
    private ParameterBagInterface $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->pdfParser = new PdfParser();
        $this->params = $params;
    }

    public function containsOneOfRequiredTexts(UploadedFile $file, array $requiredTexts): bool
    {
        $mimeTypes = new MimeTypes();
        $mimeType = $mimeTypes->guessMimeType($file->getPathname());

        $debug = $this->params->get('kernel.debug');

        try {
            $text = '';
            if ($mimeType === 'application/pdf') {
                $text = $this->extractTextFromPdf($file);
            } elseif (str_starts_with($mimeType, 'image/')) {
                $text = $this->extractTextFromImage($file);
            } else {
                throw new \InvalidArgumentException('Type de fichier non supporté.');
            }

            if ($debug) {
                file_put_contents(
                    $this->params->get('kernel.logs_dir').'/ocr_debug.txt',
                    "Contenu extrait:\n$text\n\n",
                    FILE_APPEND
                );
            }

            foreach ($requiredTexts as $requiredText) {
                if (mb_stripos($text, $requiredText) !== false) {
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            if ($debug) {
                file_put_contents(
                    $this->params->get('kernel.logs_dir').'/ocr_errors.txt',
                    "Erreur: " . $e->getMessage() . "\n",
                    FILE_APPEND
                );
            }
            return false;
        }
    }

    private function extractTextFromPdf(UploadedFile $file): string
    {
        $pdf = $this->pdfParser->parseFile($file->getPathname());
        return $pdf->getText();
    }

    private function extractTextFromImage(UploadedFile $file): string
    {
        return (new TesseractOCR($file->getPathname()))
            ->lang('fra')
            ->psm(6)
            ->oem(1)
            ->run();
    }
}

