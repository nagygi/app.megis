<?php

namespace App\Services\Materials;

use App\Exceptions\UnreadableMaterialException;
use PhpOffice\PhpWord\Element\AbstractContainer;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextBreak;
use PhpOffice\PhpWord\IOFactory;
use Smalot\PdfParser\Parser as PdfParser;
use Throwable;

/**
 * Plain text from an uploaded document (plan 3.3): txt and md as they are,
 * docx with PHPWord, pdf with pdfparser. ".doc" is refused with a request
 * for docx.
 */
class TextExtractor
{
    public function extract(string $path, string $extension): string
    {
        $extension = strtolower($extension);

        try {
            $text = match ($extension) {
                'txt', 'md' => $this->plainText($path),
                'docx' => $this->docx($path),
                'pdf' => (new PdfParser)->parseFile($path)->getText(),
                'doc' => throw new UnreadableMaterialException(__('materials.doc_not_supported')),
                default => throw new UnreadableMaterialException(__('materials.unsupported', ['extension' => $extension])),
            };
        } catch (UnreadableMaterialException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw new UnreadableMaterialException(__('materials.unreadable'));
        }

        $text = trim(preg_replace("/\n{3,}/", "\n\n", str_replace(["\r\n", "\r"], "\n", $text)) ?? '');

        if ($text === '') {
            throw new UnreadableMaterialException(__('materials.empty'));
        }

        return $text;
    }

    /**
     * UTF-8 as it is; older Hungarian files are often Windows-1250.
     */
    private function plainText(string $path): string
    {
        $content = (string) file_get_contents($path);
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;

        // mbstring has no Windows-1250; iconv does.
        return mb_check_encoding($content, 'UTF-8') ? $content : (string) iconv('Windows-1250', 'UTF-8//IGNORE', $content);
    }

    private function docx(string $path): string
    {
        $paragraphs = [];

        foreach (IOFactory::load($path, 'Word2007')->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                $paragraphs[] = $this->elementText($element);
            }
        }

        return implode("\n", $paragraphs);
    }

    private function elementText(mixed $element): string
    {
        return match (true) {
            $element instanceof Text => $element->getText(),
            $element instanceof TextBreak => "\n",
            $element instanceof AbstractContainer => implode('', array_map(fn ($child) => $this->elementText($child), $element->getElements())),
            method_exists($element, 'getText') && is_string($element->getText()) => $element->getText(),
            default => '',
        };
    }
}
