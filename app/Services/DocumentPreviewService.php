<?php

namespace App\Services;

use DOMDocument;
use DOMNode;
use DOMXPath;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Html as SpreadsheetHtmlWriter;
use ZipArchive;

class DocumentPreviewService
{
    /**
     * Render a file to a self-contained, responsive HTML preview.
     */
    public function render(string $storagePath): string
    {
        $fullPath = $this->resolveFullPath($storagePath);

        if (!$fullPath || !file_exists($fullPath)) {
            return $this->errorPage('File Not Found', 'The requested submission file could not be found in storage.');
        }

        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

        return match ($extension) {
            'docx'         => $this->renderDocx($fullPath),
            'pptx', 'ppt'  => $this->renderPresentation($fullPath, $extension),
            'xlsx', 'xls'  => $this->renderSpreadsheet($fullPath),
            'csv'          => $this->renderCsv($fullPath),
            'txt', 'log', 'json', 'xml', 'sql', 'md' => $this->renderText($fullPath, $extension),
            default        => $this->errorPage('Format Preview', 'Direct HTML preview is not available for .' . $extension . ' files.'),
        };
    }

    /**
     * Resolve absolute disk path from storage path.
     */
    protected function resolveFullPath(string $storagePath): ?string
    {
        if (Storage::disk('public')->exists($storagePath)) {
            return Storage::disk('public')->path($storagePath);
        }

        if (Storage::exists($storagePath)) {
            return Storage::path($storagePath);
        }

        $directPath = public_path('storage/' . ltrim($storagePath, '/'));
        if (file_exists($directPath)) {
            return $directPath;
        }

        $inPublic = public_path(ltrim($storagePath, '/'));
        if (file_exists($inPublic)) {
            return $inPublic;
        }

        if (file_exists($storagePath)) {
            return $storagePath;
        }

        return null;
    }

    /**
     * Convert DOCX to high-fidelity HTML document.
     */
    public function renderDocx(string $fullPath): string
    {
        try {
            $zip = new ZipArchive();
            if ($zip->open($fullPath) !== true) {
                return $this->errorPage('DOCX Error', 'Could not open DOCX archive.');
            }

            // 1. Map relationships to image targets
            $relationships = [];
            $relsXml = $zip->getFromName('word/_rels/document.xml.rels');
            if ($relsXml) {
                $domRels = new DOMDocument();
                @$domRels->loadXML($relsXml, LIBXML_NOERROR | LIBXML_NOWARNING);
                foreach ($domRels->getElementsByTagName('Relationship') as $rel) {
                    $id = $rel->getAttribute('Id');
                    $target = $rel->getAttribute('Target');
                    $type = $rel->getAttribute('Type');
                    if (str_contains($type, '/image')) {
                        $targetPath = str_starts_with($target, 'media/')
                            ? 'word/' . $target
                            : (str_starts_with($target, '/') ? ltrim($target, '/') : 'word/' . $target);
                        $relationships[$id] = $targetPath;
                    }
                }
            }

            // 2. Extract embedded images as Base64
            $imagesBase64 = [];
            foreach ($relationships as $rId => $zipPath) {
                $imgData = $zip->getFromName($zipPath);
                if ($imgData !== false) {
                    $ext = strtolower(pathinfo($zipPath, PATHINFO_EXTENSION));
                    $mime = match ($ext) {
                        'jpg', 'jpeg' => 'image/jpeg',
                        'png'         => 'image/png',
                        'gif'         => 'image/gif',
                        'webp'        => 'image/webp',
                        'svg'         => 'image/svg+xml',
                        default       => 'image/png',
                    };
                    $imagesBase64[$rId] = 'data:' . $mime . ';base64,' . base64_encode($imgData);
                }
            }

            // 3. Read word/document.xml
            $docXml = $zip->getFromName('word/document.xml');
            $zip->close();

            if (!$docXml) {
                return $this->errorPage('DOCX Empty', 'The document does not contain word/document.xml content.');
            }

            $dom = new DOMDocument();
            @$dom->loadXML($docXml, LIBXML_NOERROR | LIBXML_NOWARNING);
            $body = $dom->getElementsByTagName('body')->item(0);
            if (!$body) {
                return $this->errorPage('DOCX Empty', 'No document body found.');
            }

            $bodyHtml = '';
            foreach ($body->childNodes as $node) {
                $bodyHtml .= $this->renderDocxNode($node, $imagesBase64);
            }

            $fileName = basename($fullPath);

            return $this->wrapInDocxViewer($bodyHtml, $fileName);
        } catch (\Throwable $e) {
            Log::error('DOCX Preview generation failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return $this->errorPage('DOCX Error', 'Unable to parse document: ' . htmlspecialchars($e->getMessage()));
        }
    }

    protected function renderDocxNode(DOMNode $node, array $imagesBase64): string
    {
        $name = $node->localName ?? $node->nodeName;

        if ($name === 'p') {
            return $this->renderDocxParagraph($node, $imagesBase64);
        } elseif ($name === 'tbl') {
            return $this->renderDocxTable($node, $imagesBase64);
        }

        return '';
    }

    protected function renderDocxParagraph(DOMNode $pNode, array $imagesBase64): string
    {
        $align = '';
        $isHeading = null;
        $isList = false;

        foreach ($pNode->childNodes as $child) {
            if (($child->localName ?? $child->nodeName) === 'pPr') {
                foreach ($child->childNodes as $pPrChild) {
                    $prName = $pPrChild->localName ?? $pPrChild->nodeName;
                    if ($prName === 'jc') {
                        $val = $pPrChild->getAttribute('w:val') ?: $pPrChild->getAttribute('val');
                        if ($val === 'center') $align = 'center';
                        elseif ($val === 'right') $align = 'right';
                        elseif ($val === 'both') $align = 'justify';
                    } elseif ($prName === 'pStyle') {
                        $styleVal = $pPrChild->getAttribute('w:val') ?: $pPrChild->getAttribute('val');
                        if (preg_match('/heading\s*(\d)/i', $styleVal, $m)) {
                            $isHeading = min(6, max(1, (int)$m[1]));
                        } elseif (stripos($styleVal, 'title') !== false) {
                            $isHeading = 1;
                        } elseif (stripos($styleVal, 'subtitle') !== false) {
                            $isHeading = 3;
                        }
                    } elseif ($prName === 'numPr') {
                        $isList = true;
                    }
                }
            }
        }

        $inlineHtml = '';
        foreach ($pNode->childNodes as $child) {
            $cName = $child->localName ?? $child->nodeName;
            if ($cName === 'r') {
                $inlineHtml .= $this->renderDocxRun($child, $imagesBase64);
            } elseif ($cName === 'hyperlink') {
                $linkText = '';
                foreach ($child->childNodes as $hChild) {
                    if (($hChild->localName ?? $hChild->nodeName) === 'r') {
                        $linkText .= $this->renderDocxRun($hChild, $imagesBase64);
                    }
                }
                $inlineHtml .= '<span class="text-primary text-decoration-underline">' . $linkText . '</span>';
            }
        }

        if (trim(strip_tags($inlineHtml)) === '' && !str_contains($inlineHtml, '<img')) {
            return '<p class="docx-empty-p">&nbsp;</p>';
        }

        $styles = [];
        if ($align) $styles[] = "text-align: {$align};";
        $styleAttr = !empty($styles) ? ' style="' . implode(' ', $styles) . '"' : '';

        if ($isHeading) {
            $hTag = 'h' . $isHeading;
            return "<{$hTag} class=\"docx-heading docx-h{$isHeading}\"{$styleAttr}>{$inlineHtml}</{$hTag}>";
        }

        if ($isList) {
            return "<div class=\"docx-list-item\"{$styleAttr}><span class=\"docx-bullet\">&bull;</span><div class=\"docx-list-content\">{$inlineHtml}</div></div>";
        }

        return "<p class=\"docx-p\"{$styleAttr}>{$inlineHtml}</p>";
    }

    protected function renderDocxRun(DOMNode $rNode, array $imagesBase64): string
    {
        $isBold = false;
        $isItalic = false;
        $isUnderline = false;
        $isStrike = false;
        $color = null;
        $highlight = null;
        $fontSize = null;

        $runHtml = '';

        foreach ($rNode->childNodes as $child) {
            $cName = $child->localName ?? $child->nodeName;
            if ($cName === 'rPr') {
                foreach ($child->childNodes as $prChild) {
                    $prName = $prChild->localName ?? $prChild->nodeName;
                    if ($prName === 'b') $isBold = true;
                    elseif ($prName === 'i') $isItalic = true;
                    elseif ($prName === 'u') $isUnderline = true;
                    elseif ($prName === 'strike') $isStrike = true;
                    elseif ($prName === 'color') {
                        $cVal = $prChild->getAttribute('w:val') ?: $prChild->getAttribute('val');
                        if ($cVal && $cVal !== 'auto') {
                            $color = '#' . ltrim($cVal, '#');
                        }
                    } elseif ($prName === 'highlight') {
                        $hVal = $prChild->getAttribute('w:val') ?: $prChild->getAttribute('val');
                        if ($hVal && $hVal !== 'none') $highlight = $hVal;
                    } elseif ($prName === 'sz') {
                        $szVal = (int) ($prChild->getAttribute('w:val') ?: $prChild->getAttribute('val'));
                        if ($szVal > 0) $fontSize = round($szVal / 2) . 'pt';
                    }
                }
            } elseif ($cName === 't') {
                $runHtml .= htmlspecialchars($child->textContent, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            } elseif ($cName === 'br') {
                $runHtml .= '<br>';
            } elseif ($cName === 'tab') {
                $runHtml .= '&emsp;';
            } elseif ($cName === 'drawing' || $cName === 'pict') {
                $runHtml .= $this->renderDocxImage($child, $imagesBase64);
            }
        }

        if ($runHtml === '') return '';

        $styles = [];
        if ($color) $styles[] = "color: {$color};";
        if ($highlight) $styles[] = "background-color: {$highlight};";
        if ($fontSize) $styles[] = "font-size: {$fontSize};";

        if ($isBold) $runHtml = "<strong>{$runHtml}</strong>";
        if ($isItalic) $runHtml = "<em>{$runHtml}</em>";
        if ($isUnderline) $runHtml = "<u>{$runHtml}</u>";
        if ($isStrike) $runHtml = "<s>{$runHtml}</s>";

        if (!empty($styles)) {
            $runHtml = '<span style="' . implode(' ', $styles) . '">' . $runHtml . '</span>';
        }

        return $runHtml;
    }

    protected function renderDocxImage(DOMNode $drawingNode, array $imagesBase64): string
    {
        $xpath = new DOMXPath($drawingNode->ownerDocument);
        $blips = $xpath->query('.//*[local-name()="blip"]', $drawingNode);
        if ($blips->length > 0) {
            $blip = $blips->item(0);
            $rId = $blip->getAttribute('r:embed') ?: $blip->getAttribute('embed');
            if ($rId && isset($imagesBase64[$rId])) {
                return '<div class="docx-img-wrap"><img src="' . $imagesBase64[$rId] . '" class="docx-img" alt="Document Graphic"></div>';
            }
        }
        return '';
    }

    protected function renderDocxTable(DOMNode $tblNode, array $imagesBase64): string
    {
        $rowsHtml = '';
        foreach ($tblNode->childNodes as $child) {
            $cName = $child->localName ?? $child->nodeName;
            if ($cName === 'tr') {
                $cellsHtml = '';
                foreach ($child->childNodes as $tc) {
                    if (($tc->localName ?? $tc->nodeName) === 'tc') {
                        $cellContent = '';
                        $colSpan = 1;
                        $shdColor = null;

                        foreach ($tc->childNodes as $tcChild) {
                            $tcChildName = $tcChild->localName ?? $tcChild->nodeName;
                            if ($tcChildName === 'tcPr') {
                                foreach ($tcChild->childNodes as $tcPrChild) {
                                    $prName = $tcPrChild->localName ?? $tcPrChild->nodeName;
                                    if ($prName === 'gridSpan') {
                                        $colSpan = (int) ($tcPrChild->getAttribute('w:val') ?: $tcPrChild->getAttribute('val')) ?: 1;
                                    } elseif ($prName === 'shd') {
                                        $sVal = $tcPrChild->getAttribute('w:fill') ?: $tcPrChild->getAttribute('fill');
                                        if ($sVal && $sVal !== 'auto') {
                                            $shdColor = '#' . ltrim($sVal, '#');
                                        }
                                    }
                                }
                            } elseif ($tcChildName === 'p') {
                                $cellContent .= $this->renderDocxParagraph($tcChild, $imagesBase64);
                            } elseif ($tcChildName === 'tbl') {
                                $cellContent .= $this->renderDocxTable($tcChild, $imagesBase64);
                            }
                        }

                        $tdStyles = ['padding: 8px 12px;', 'border: 1px solid #cbd5e1;', 'vertical-align: top;'];
                        if ($shdColor) {
                            $tdStyles[] = "background-color: {$shdColor};";
                        }
                        $colSpanAttr = $colSpan > 1 ? " colspan=\"{$colSpan}\"" : '';
                        $cellsHtml .= '<td style="' . implode(' ', $tdStyles) . '"' . $colSpanAttr . '>' . $cellContent . '</td>';
                    }
                }
                $rowsHtml .= '<tr>' . $cellsHtml . '</tr>';
            }
        }

        return '<div class="docx-table-wrapper my-3"><table class="table table-bordered table-sm docx-table mb-0">' . $rowsHtml . '</table></div>';
    }

    /**
     * Convert Spreadsheet (XLSX, XLS) to HTML.
     */
    public function renderSpreadsheet(string $fullPath): string
    {
        try {
            $spreadsheet = IOFactory::load($fullPath);
            $writer = new SpreadsheetHtmlWriter($spreadsheet);
            $html = $writer->generateHtmlAll();

            $fileName = basename($fullPath);

            return $this->wrapInSpreadsheetViewer($html, $fileName);
        } catch (\Throwable $e) {
            Log::error('Spreadsheet Preview failed: ' . $e->getMessage());
            return $this->errorPage('Spreadsheet Error', 'Unable to parse spreadsheet: ' . htmlspecialchars($e->getMessage()));
        }
    }

    /**
     * Render CSV to a clean table view.
     */
    public function renderCsv(string $fullPath): string
    {
        if (!file_exists($fullPath) || !($handle = fopen($fullPath, 'r'))) {
            return $this->errorPage('CSV Error', 'Could not open CSV file.');
        }

        $rows = [];
        $header = null;
        $maxRows = 500;
        $count = 0;

        while (($data = fgetcsv($handle, 10000, ',')) !== false && $count < $maxRows) {
            if ($header === null) {
                $header = $data;
            } else {
                $rows[] = $data;
            }
            $count++;
        }
        fclose($handle);

        $tableHtml = '<div class="table-responsive"><table class="table table-bordered table-striped table-hover table-sm" style="font-size: 12px;">';
        if ($header) {
            $tableHtml .= '<thead class="table-light"><tr>';
            foreach ($header as $col) {
                $tableHtml .= '<th>' . htmlspecialchars($col) . '</th>';
            }
            $tableHtml .= '</tr></thead>';
        }
        $tableHtml .= '<tbody>';
        foreach ($rows as $row) {
            $tableHtml .= '<tr>';
            foreach ($row as $cell) {
                $tableHtml .= '<td>' . htmlspecialchars($cell) . '</td>';
            }
            $tableHtml .= '</tr>';
        }
        $tableHtml .= '</tbody></table></div>';

        $fileName = basename($fullPath);
        return $this->wrapInSpreadsheetViewer($tableHtml, $fileName);
    }

    /**
     * Render plain text or code files.
     */
    public function renderText(string $fullPath, string $ext): string
    {
        $content = file_get_contents($fullPath);
        $escaped = htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $fileName = basename($fullPath);

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{$fileName}</title>
    <link rel="stylesheet" href="/Tabler/dist/css/tabler.min.css">
    <style>
        html { height: 100%; overflow-y: auto; }
        body { margin: 0; padding: 16px; background: #f8fafc; font-family: monospace; font-size: 13px; line-height: 1.6; min-height: 100%; }
        pre { background: #ffffff; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; white-space: pre-wrap; word-break: break-all; margin: 0; }
        html::-webkit-scrollbar { width: 8px; }
        html::-webkit-scrollbar-track { background: #f1f5f9; }
        html::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        html::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body>
    <pre><code>{$escaped}</code></pre>
</body>
</html>
HTML;
    }

    /**
     * Wrap DOCX content in a Word-processor document canvas.
     */
    protected function wrapInDocxViewer(string $bodyHtml, string $fileName): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{$fileName}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/Tabler/dist/css/tabler.min.css">
    <style>
        :root {
            --docx-bg: #f8fafc;
            --docx-paper: #ffffff;
            --docx-text: #1e293b;
        }
        *, *::before, *::after {
            box-sizing: border-box;
        }
        html {
            background-color: var(--docx-bg);
            height: 100%;
            overflow-y: auto;
        }
        body {
            background-color: var(--docx-bg);
            margin: 0;
            padding: 20px 16px 28px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: var(--docx-text);
            min-height: 100%;
            -webkit-text-size-adjust: 100%;
        }
        .docx-paper-sheet {
            background-color: var(--docx-paper);
            width: 100%;
            max-width: 960px;
            margin: 0 auto;
            height: auto;
            min-height: auto;
            padding: 32px 40px;
            box-shadow: 0 2px 12px rgba(15, 23, 42, 0.08);
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            font-size: 11pt;
            line-height: 1.6;
            overflow: visible;
        }
        /* Clean, proportional scrollbar matching document */
        html::-webkit-scrollbar {
            width: 8px;
        }
        html::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        html::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        html::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        .docx-heading {
            font-weight: 700;
            color: #0f172a;
            margin-top: 1.25rem;
            margin-bottom: 0.65rem;
            line-height: 1.3;
        }
        .docx-h1 { font-size: 18pt; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; }
        .docx-h2 { font-size: 15pt; }
        .docx-h3 { font-size: 13pt; }
        .docx-p {
            margin-bottom: 0.75rem;
            color: #334155;
        }
        .docx-empty-p {
            margin-bottom: 0.5rem;
            min-height: 1.2em;
        }
        .docx-list-item {
            display: flex;
            align-items: baseline;
            gap: 8px;
            margin-bottom: 4px;
        }
        .docx-bullet {
            color: #0284c7;
            font-size: 14px;
            flex-shrink: 0;
        }
        .docx-list-content {
            flex: 1;
        }
        .docx-table-wrapper {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin: 20px 0;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background-color: #ffffff;
        }
        .docx-table {
            border-collapse: collapse;
            width: 100%;
            margin: 0;
            font-size: 10.5pt;
            background-color: #ffffff;
        }
        .docx-table th, .docx-table td {
            border: 1px solid #cbd5e1;
            padding: 8px 12px;
            vertical-align: top;
            word-break: break-word;
            overflow-wrap: break-word;
            background-color: inherit;
        }
        .docx-table td > :last-child,
        .docx-table th > :last-child {
            margin-bottom: 0;
        }
        .docx-img-wrap {
            text-align: center;
            margin: 18px 0;
        }
        .docx-img {
            max-width: 100%;
            max-height: 540px;
            object-fit: contain;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        @media (max-width: 768px) {
            body {
                padding: 16px 8px 32px;
            }
            .docx-paper-sheet {
                padding: 24px 20px;
                border-radius: 4px;
            }
        }
    </style>
</head>
<body>
    <article class="docx-paper-sheet">
        {$bodyHtml}
    </article>
</body>
</html>
HTML;
    }

    /**
     * Wrap spreadsheet content in responsive viewer.
     */
    protected function wrapInSpreadsheetViewer(string $html, string $fileName): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{$fileName}</title>
    <link rel="stylesheet" href="/Tabler/dist/css/tabler.min.css">
    <style>
        html { height: 100%; overflow-y: auto; }
        body { margin: 0; padding: 16px; background: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; min-height: 100%; }
        .sheet-wrapper { background: #ffffff; padding: 16px; border-radius: 8px; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.06); overflow-x: auto; }
        table { border-collapse: collapse; width: 100%; font-size: 12px; }
        td, th { border: 1px solid #cbd5e1 !important; padding: 6px 10px; }
        html::-webkit-scrollbar { width: 8px; }
        html::-webkit-scrollbar-track { background: #f1f5f9; }
        html::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        html::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body>
    <div class="sheet-wrapper">
        {$html}
    </div>
</body>
</html>
HTML;
    }

    /**
     * Fallback error page.
     */
    /**
     * Render PowerPoint presentation (PPTX / PPT) to high-fidelity slide deck.
     * Uses PowerPoint COM (native Microsoft PowerPoint export) when available,
     * with graceful fallback to XML-based rendering.
     */
    public function renderPresentation(string $fullPath, string $extension): string
    {
        // 1. Try PowerPoint COM (native Microsoft Office high-fidelity rendering)
        if (class_exists('COM') && strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            try {
                $comHtml = $this->renderPresentationWithCom($fullPath, $extension);
                if ($comHtml !== null) {
                    return $comHtml;
                }
            } catch (\Throwable $e) {
                Log::warning('PowerPoint COM rendering failed, falling back to XML: ' . $e->getMessage());
            }
        }

        // 2. Fallback to XML-based presentation parsing
        if ($extension === 'pptx') {
            return $this->renderPptxXml($fullPath);
        }

        return $this->renderLegacyPpt($fullPath);
    }

    /**
     * Public alias for backwards compatibility and direct invocation.
     */
    public function renderPptx(string $fullPath): string
    {
        return $this->renderPresentation($fullPath, 'pptx');
    }

    /**
     * High-fidelity PowerPoint COM presentation renderer.
     * Converts the presentation via Microsoft PowerPoint to vector PDF,
     * caching the result and rendering pixel-perfect slides with PDF.js.
     */
    public function renderPresentationWithCom(string $fullPath, string $extension): ?string
    {
        $cacheDir = storage_path('app/pptx-cache');
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0775, true);
        }

        $hash = md5_file($fullPath);
        if (!$hash) {
            $hash = md5($fullPath . filemtime($fullPath));
        }

        $pdfFile = $cacheDir . DIRECTORY_SEPARATOR . $hash . '.pdf';
        $metaFile = $cacheDir . DIRECTORY_SEPARATOR . $hash . '.json';

        $totalSlides = 1;
        $aspectRatio = '16 / 9';

        if (file_exists($pdfFile) && file_exists($metaFile) && filesize($pdfFile) > 0) {
            $meta = json_decode(file_get_contents($metaFile), true);
            $totalSlides = (int) ($meta['slides'] ?? 1);
            if (!empty($meta['aspect_ratio'])) {
                $aspectRatio = $meta['aspect_ratio'];
            }
        } else {
            $ppt = null;
            $pres = null;
            try {
                $ppt = new \COM('PowerPoint.Application');
                $ppt->Visible = 1;

                $pres = $ppt->Presentations->Open(
                    str_replace('/', '\\', $fullPath),
                    1, // ReadOnly
                    0, // Untitled
                    1  // WithWindow
                );

                $totalSlides = (int) $pres->Slides->Count;
                $slideW = (float) $pres->PageSetup->SlideWidth;
                $slideH = (float) $pres->PageSetup->SlideHeight;
                if ($slideW > 0 && $slideH > 0) {
                    $aspectRatio = round($slideW, 2) . ' / ' . round($slideH, 2);
                }

                // ppSaveAsPDF = 32
                $pres->SaveAs(str_replace('/', '\\', $pdfFile), 32);
            } finally {
                if ($pres) {
                    try { $pres->Close(); } catch (\Throwable $e) {}
                }
                if ($ppt) {
                    try { $ppt->Quit(); } catch (\Throwable $e) {}
                }
                unset($pres, $ppt);
            }

            if (file_exists($pdfFile) && filesize($pdfFile) > 0) {
                file_put_contents($metaFile, json_encode([
                    'slides'       => $totalSlides,
                    'width'        => $slideW ?? 720,
                    'height'       => $slideH ?? 540,
                    'aspect_ratio' => $aspectRatio,
                ]));
            }
        }

        if (!file_exists($pdfFile) || filesize($pdfFile) === 0) {
            return null;
        }

        $pdfBytes = file_get_contents($pdfFile);
        $base64Pdf = base64_encode($pdfBytes);
        $fileName = basename($fullPath);

        return $this->wrapInPdfPresentationViewer($base64Pdf, $fileName, $totalSlides, $aspectRatio);
    }

    /**
     * Convert PPTX presentation to responsive interactive HTML slide deck via XML parsing.
     */
    public function renderPptxXml(string $fullPath): string
    {
        try {
            $zip = new ZipArchive();
            if ($zip->open($fullPath) !== true) {
                return $this->errorPage('PPTX Error', 'Could not open PowerPoint archive.');
            }

            // 1. Read slide dimensions from presentation.xml
            $cx = 9906000;
            $cy = 6858000;
            $presXml = $zip->getFromName('ppt/presentation.xml');
            if ($presXml) {
                $domPres = new DOMDocument();
                @$domPres->loadXML($presXml, LIBXML_NOERROR | LIBXML_NOWARNING);
                $xpathPres = new DOMXPath($domPres);
                $sldSzNodes = $xpathPres->query('//*[local-name()="sldSz"]');
                if ($sldSzNodes->length > 0) {
                    $szNode = $sldSzNodes->item(0);
                    $cx = (int) ($szNode->getAttribute('cx') ?: 9906000);
                    $cy = (int) ($szNode->getAttribute('cy') ?: 6858000);
                }
            }
            if ($cx <= 0) $cx = 9906000;
            if ($cy <= 0) $cy = 6858000;

            // 2. Map presentation relationships
            $relsMap = [];
            $presRelsXml = $zip->getFromName('ppt/_rels/presentation.xml.rels');
            if ($presRelsXml) {
                $domRels = new DOMDocument();
                @$domRels->loadXML($presRelsXml, LIBXML_NOERROR | LIBXML_NOWARNING);
                foreach ($domRels->getElementsByTagName('Relationship') as $rel) {
                    $relsMap[$rel->getAttribute('Id')] = $rel->getAttribute('Target');
                }
            }

            // 3. Resolve slide order from sldIdLst
            $orderedSlides = [];
            if (isset($xpathPres)) {
                $sldIdNodes = $xpathPres->query('//*[local-name()="sldId"]');
                foreach ($sldIdNodes as $sldId) {
                    $rId = $sldId->getAttribute('r:id') ?: $sldId->getAttribute('id');
                    if (!$rId) {
                        foreach ($sldId->attributes as $attr) {
                            if (isset($relsMap[$attr->value])) {
                                $rId = $attr->value;
                                break;
                            }
                        }
                    }
                    if ($rId && isset($relsMap[$rId])) {
                        $target = $relsMap[$rId];
                        if (!str_starts_with($target, 'ppt/')) {
                            $target = 'ppt/' . ltrim($target, '/');
                        }
                        $orderedSlides[] = $target;
                    }
                }
            }

            // Fallback: scan ppt/slides/slide*.xml if list empty
            if (empty($orderedSlides)) {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $stat = $zip->statIndex($i);
                    if ($stat && preg_match('#^ppt/slides/slide\d+\.xml$#i', $stat['name'])) {
                        $orderedSlides[] = $stat['name'];
                    }
                }
                natsort($orderedSlides);
                $orderedSlides = array_values($orderedSlides);
            }

            if (empty($orderedSlides)) {
                $zip->close();
                return $this->errorPage('PPTX Empty', 'The presentation does not contain any slides.');
            }

            $slideCards = [];
            $totalSlides = count($orderedSlides);

            foreach ($orderedSlides as $idx => $slidePath) {
                $sXml = $zip->getFromName($slidePath);
                if (!$sXml) continue;

                $domSlide = new DOMDocument();
                @$domSlide->loadXML($sXml, LIBXML_NOERROR | LIBXML_NOWARNING);
                $xpath = new DOMXPath($domSlide);

                // Slide images mapping from rels
                $slideRelsPath = dirname($slidePath) . '/_rels/' . basename($slidePath) . '.rels';
                $slideImages = [];
                if ($zip->locateName($slideRelsPath) !== false) {
                    $domSRels = new DOMDocument();
                    @$domSRels->loadXML($zip->getFromName($slideRelsPath), LIBXML_NOERROR | LIBXML_NOWARNING);
                    foreach ($domSRels->getElementsByTagName('Relationship') as $rel) {
                        $rId = $rel->getAttribute('Id');
                        $type = $rel->getAttribute('Type');
                        $target = $rel->getAttribute('Target');
                        if (str_contains($type, '/image') || str_contains($type, 'photo') || str_contains($type, 'media')) {
                            $normTarget = dirname($slidePath) . '/' . $target;
                            $parts = explode('/', str_replace('\\', '/', $normTarget));
                            $safeParts = [];
                            foreach ($parts as $p) {
                                if ($p === '..') array_pop($safeParts);
                                elseif ($p !== '.' && $p !== '') $safeParts[] = $p;
                            }
                            $cleanTarget = implode('/', $safeParts);
                            $imgData = $zip->getFromName($cleanTarget);
                            if ($imgData !== false) {
                                $ext = strtolower(pathinfo($cleanTarget, PATHINFO_EXTENSION));
                                $mime = match ($ext) {
                                    'jpg', 'jpeg' => 'image/jpeg',
                                    'png'         => 'image/png',
                                    'gif'         => 'image/gif',
                                    'webp'        => 'image/webp',
                                    'svg'         => 'image/svg+xml',
                                    default       => 'image/png',
                                };
                                $slideImages[$rId] = 'data:' . $mime . ';base64,' . base64_encode($imgData);
                            }
                        }
                    }
                }

                // Slide background color
                $slideBg = null;
                $bgClrNode = $xpath->query('.//*[local-name()="bg"]//*[local-name()="solidFill"]/*[local-name()="srgbClr"]')->item(0);
                if ($bgClrNode && $bgClrNode->getAttribute('val')) {
                    $slideBg = '#' . $bgClrNode->getAttribute('val');
                }

                $elementsHtml = $this->renderPptxSlideElements($xpath, $cx, $cy, $slideImages);

                $slideNum = $idx + 1;
                $canvasStyle = "aspect-ratio: {$cx} / {$cy};";
                if ($slideBg) {
                    $canvasStyle .= " background-color: {$slideBg};";
                }

                $slideCards[] = <<<CARD
<div class="pptx-slide-card" id="slide-{$slideNum}" data-slide-num="{$slideNum}">
    <div class="pptx-slide-header">
        <span class="pptx-slide-badge">Slide {$slideNum} of {$totalSlides}</span>
    </div>
    <div class="pptx-slide-canvas" style="{$canvasStyle}">
        {$elementsHtml}
    </div>
</div>
CARD;
            }

            $zip->close();
            $fileName = basename($fullPath);
            return $this->wrapInPresentationViewer(implode("\n", $slideCards), $fileName, $totalSlides, $cx, $cy);
        } catch (\Throwable $e) {
            Log::error('PPTX Preview generation failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return $this->errorPage('PPTX Error', 'Unable to parse presentation: ' . htmlspecialchars($e->getMessage()));
        }
    }

    /**
     * Render shapes, text, images, and tables of a single PPTX slide.
     */
    protected function renderPptxSlideElements(DOMXPath $xpath, int $cx, int $cy, array $slideImages): string
    {
        $html = '';

        // 1. Shapes and Textboxes (sp)
        $spNodes = $xpath->query('.//*[local-name()="spTree"]//*[local-name()="sp"]');
        foreach ($spNodes as $sp) {
            $offNode = $xpath->query('.//*[local-name()="xfrm"]/*[local-name()="off"]', $sp)->item(0);
            $extNode = $xpath->query('.//*[local-name()="xfrm"]/*[local-name()="ext"]', $sp)->item(0);
            if (!$offNode || !$extNode) continue;

            $sx = (int) $offNode->getAttribute('x');
            $sy = (int) $offNode->getAttribute('y');
            $sw = (int) $extNode->getAttribute('cx');
            $sh = (int) $extNode->getAttribute('cy');

            // Fill color & border
            $bgColor = null;
            $borderColor = null;
            $srgbNode = $xpath->query('.//*[local-name()="spPr"]/*[local-name()="solidFill"]/*[local-name()="srgbClr"]', $sp)->item(0);
            if ($srgbNode && $srgbNode->getAttribute('val')) {
                $bgColor = '#' . $srgbNode->getAttribute('val');
            }
            $lnClrNode = $xpath->query('.//*[local-name()="spPr"]/*[local-name()="ln"]/*[local-name()="solidFill"]/*[local-name()="srgbClr"]', $sp)->item(0);
            if ($lnClrNode && $lnClrNode->getAttribute('val')) {
                $borderColor = '#' . $lnClrNode->getAttribute('val');
            }

            // Paragraphs & Text
            $pNodes = $xpath->query('.//*[local-name()="txBody"]/*[local-name()="p"]', $sp);
            $pHtml = '';
            foreach ($pNodes as $p) {
                $pPr = $xpath->query('./*[local-name()="pPr"]', $p)->item(0);
                $align = 'left';
                $isBullet = false;
                if ($pPr) {
                    $algnAttr = $pPr->getAttribute('algn');
                    if ($algnAttr === 'ctr') $align = 'center';
                    elseif ($algnAttr === 'r') $align = 'right';
                    elseif ($algnAttr === 'just') $align = 'justify';

                    $buChar = $xpath->query('./*[local-name()="buChar"]', $pPr)->item(0);
                    $lvl = (int) $pPr->getAttribute('lvl');
                    if ($buChar || $lvl > 0) $isBullet = true;
                }

                $runHtml = $isBullet ? '<span class="pptx-bullet">&bull;</span> ' : '';
                foreach ($p->childNodes as $child) {
                    $cName = $child->localName ?? $child->nodeName;
                    if ($cName === 'r') {
                        $rPr = $xpath->query('./*[local-name()="rPr"]', $child)->item(0);
                        $isB = $rPr && $rPr->getAttribute('b') === '1';
                        $isI = $rPr && $rPr->getAttribute('i') === '1';
                        $isU = $rPr && $rPr->getAttribute('u') === 'sng';
                        $isS = $rPr && $rPr->getAttribute('strike') === 'sngStrike';
                        $sz = $rPr ? (int) $rPr->getAttribute('sz') : 0;
                        $fontClr = null;
                        if ($rPr) {
                            $clrNode = $xpath->query('.//*[local-name()="srgbClr"]', $rPr)->item(0);
                            if ($clrNode && $clrNode->getAttribute('val')) {
                                $fontClr = '#' . $clrNode->getAttribute('val');
                            }
                        }

                        $tNode = $xpath->query('./*[local-name()="t"]', $child)->item(0);
                        $tText = $tNode ? htmlspecialchars($tNode->textContent, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : '';
                        if ($tText !== '') {
                            if ($isB) $tText = "<strong>{$tText}</strong>";
                            if ($isI) $tText = "<em>{$tText}</em>";
                            if ($isU) $tText = "<u>{$tText}</u>";
                            if ($isS) $tText = "<s>{$tText}</s>";

                            $rStyles = [];
                            if ($sz > 0) {
                                $pt = $sz / 100;
                                $rStyles[] = "font-size: calc({$pt} * 0.13cqw);";
                            }
                            if ($fontClr) {
                                $rStyles[] = "color: {$fontClr};";
                            }
                            if (!empty($rStyles)) {
                                $tText = '<span style="' . implode(' ', $rStyles) . '">' . $tText . '</span>';
                            }
                            $runHtml .= $tText;
                        }
                    } elseif ($cName === 'br') {
                        $runHtml .= '<br>';
                    }
                }

                if ($runHtml !== '') {
                    $pHtml .= '<div style="text-align: ' . $align . '; line-height: 1.35; margin-bottom: 2px;">' . $runHtml . '</div>';
                }
            }

            $lPct = number_format(($sx / $cx) * 100, 3, '.', '');
            $tPct = number_format(($sy / $cy) * 100, 3, '.', '');
            $wPct = number_format(($sw / $cx) * 100, 3, '.', '');
            $hPct = number_format(($sh / $cy) * 100, 3, '.', '');

            $styles = [
                "position: absolute;",
                "left: {$lPct}%;",
                "top: {$tPct}%;",
                "width: {$wPct}%;",
                "min-height: {$hPct}%;",
                "box-sizing: border-box;",
                "overflow: hidden;",
            ];
            if ($bgColor) {
                $styles[] = "background-color: {$bgColor};";
                $styles[] = "border-radius: 4px; padding: 4px 6px;";
            }
            if ($borderColor) {
                $styles[] = "border: 1px solid {$borderColor};";
            }
            $styleStr = implode(' ', $styles);
            $html .= "<div class=\"pptx-shape\" style=\"{$styleStr}\">{$pHtml}</div>\n";
        }

        // 2. Pictures (pic)
        $picNodes = $xpath->query('.//*[local-name()="spTree"]//*[local-name()="pic"]');
        foreach ($picNodes as $pic) {
            $offNode = $xpath->query('.//*[local-name()="xfrm"]/*[local-name()="off"]', $pic)->item(0);
            $extNode = $xpath->query('.//*[local-name()="xfrm"]/*[local-name()="ext"]', $pic)->item(0);
            $blipNode = $xpath->query('.//*[local-name()="blip"]', $pic)->item(0);
            if (!$offNode || !$extNode || !$blipNode) continue;

            $rId = $blipNode->getAttribute('r:embed') ?: $blipNode->getAttribute('embed');
            if (!$rId || !isset($slideImages[$rId])) continue;

            $px = (int) $offNode->getAttribute('x');
            $py = (int) $offNode->getAttribute('y');
            $pw = (int) $extNode->getAttribute('cx');
            $ph = (int) $extNode->getAttribute('cy');

            $lPct = number_format(($px / $cx) * 100, 3, '.', '');
            $tPct = number_format(($py / $cy) * 100, 3, '.', '');
            $wPct = number_format(($pw / $cx) * 100, 3, '.', '');
            $hPct = number_format(($ph / $cy) * 100, 3, '.', '');

            $style = "position: absolute; left: {$lPct}%; top: {$tPct}%; width: {$wPct}%; height: {$hPct}%;";
            $imgSrc = $slideImages[$rId];
            $html .= "<div class=\"pptx-pic\" style=\"{$style}\"><img src=\"{$imgSrc}\" style=\"width:100%; height:100%; object-fit:contain; display:block;\" alt=\"Presentation Graphic\"></div>\n";
        }

        // 3. Tables (graphicFrame with a:tbl)
        $tblNodes = $xpath->query('.//*[local-name()="spTree"]//*[local-name()="tbl"]');
        foreach ($tblNodes as $tbl) {
            $frame = $tbl->parentNode;
            while ($frame && ($frame->localName ?? $frame->nodeName) !== 'graphicFrame') {
                $frame = $frame->parentNode;
            }
            if (!$frame) continue;

            $offNode = $xpath->query('.//*[local-name()="xfrm"]/*[local-name()="off"]', $frame)->item(0);
            $extNode = $xpath->query('.//*[local-name()="xfrm"]/*[local-name()="ext"]', $frame)->item(0);
            if (!$offNode || !$extNode) continue;

            $tx = (int) $offNode->getAttribute('x');
            $ty = (int) $offNode->getAttribute('y');
            $tw = (int) $extNode->getAttribute('cx');

            $lPct = number_format(($tx / $cx) * 100, 3, '.', '');
            $tPct = number_format(($ty / $cy) * 100, 3, '.', '');
            $wPct = number_format(($tw / $cx) * 100, 3, '.', '');

            $rowsHtml = '';
            $trNodes = $xpath->query('./*[local-name()="tr"]', $tbl);
            foreach ($trNodes as $tr) {
                $cellsHtml = '';
                $tcNodes = $xpath->query('./*[local-name()="tc"]', $tr);
                foreach ($tcNodes as $tc) {
                    $cellText = '';
                    $pNodes = $xpath->query('.//*[local-name()="txBody"]//*[local-name()="p"]', $tc);
                    foreach ($pNodes as $p) {
                        $pText = '';
                        $tNodes = $xpath->query('.//*[local-name()="t"]', $p);
                        foreach ($tNodes as $t) {
                            $pText .= htmlspecialchars($t->textContent, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                        }
                        if ($pText !== '') {
                            $cellText .= '<div>' . $pText . '</div>';
                        }
                    }
                    $cellsHtml .= '<td style="padding: 4px 8px; border: 1px solid #cbd5e1; font-size: calc(14 * 0.13cqw);">' . $cellText . '</td>';
                }
                $rowsHtml .= '<tr>' . $cellsHtml . '</tr>';
            }

            $style = "position: absolute; left: {$lPct}%; top: {$tPct}%; width: {$wPct}%; overflow: auto;";
            $html .= "<div class=\"pptx-table-wrap\" style=\"{$style}\"><table style=\"width:100%; border-collapse:collapse; background:#ffffff;\">{$rowsHtml}</table></div>\n";
        }

        return $html;
    }

    /**
     * Render legacy binary PPT by extracting text streams and presenting slide blocks.
     */
    public function renderLegacyPpt(string $fullPath): string
    {
        $raw = @file_get_contents($fullPath);
        if ($raw === false || strlen($raw) === 0) {
            return $this->errorPage('PPT Error', 'Could not read PPT file.');
        }

        $textBlocks = [];
        if (preg_match_all('/(?:[\x20-\x7E]\x00){4,}/', $raw, $matches)) {
            foreach ($matches[0] as $match) {
                $converted = @mb_convert_encoding($match, 'UTF-8', 'UTF-16LE');
                $trimmed = trim($converted);
                if (strlen($trimmed) >= 3 && !preg_match('/^(Font|Courier|Arial|Times|Calibri|Segoe|Microsoft|PowerPoint|Slide|Normal)/i', $trimmed)) {
                    $textBlocks[] = htmlspecialchars($trimmed, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                }
            }
        }

        $textBlocks = array_values(array_unique($textBlocks));
        $fileName = basename($fullPath);

        $chunkSize = 6;
        $chunks = array_chunk($textBlocks, $chunkSize);
        if (empty($chunks)) {
            $chunks = [['(No readable text found in legacy presentation format.)']];
        }

        $slideCards = [];
        $totalSlides = count($chunks);
        foreach ($chunks as $idx => $chunk) {
            $slideNum = $idx + 1;
            $itemsHtml = '';
            foreach ($chunk as $item) {
                $itemsHtml .= '<div class="pptx-legacy-item mb-2" style="font-size: 14px; line-height: 1.5; color: #1e293b;">&bull; ' . $item . '</div>';
            }

            $slideCards[] = <<<CARD
<div class="pptx-slide-card" id="slide-{$slideNum}" data-slide-num="{$slideNum}">
    <div class="pptx-slide-header">
        <span class="pptx-slide-badge">Slide {$slideNum} of {$totalSlides} (Legacy PPT)</span>
    </div>
    <div class="pptx-slide-canvas p-4" style="aspect-ratio: 16 / 9; background: #ffffff; display: flex; flex-direction: column; justify-content: center;">
        <div class="p-3 bg-light rounded-3">
            {$itemsHtml}
        </div>
    </div>
</div>
CARD;
        }

        return $this->wrapInPresentationViewer(implode("\n", $slideCards), $fileName, $totalSlides, 16, 9);
    }

    /**
     * Wrap slides into an interactive, responsive presentation viewer canvas.
     */
    protected function wrapInPresentationViewer(string $slidesHtml, string $fileName, int $totalSlides, int $cx, int $cy): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{$fileName}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/Tabler/dist/css/tabler.min.css">
    <style>
        :root {
            --viewer-bg: #1e293b;
            --slide-border: #334155;
            --slide-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
        }
        *, *::before, *::after { box-sizing: border-box; }
        html, body {
            margin: 0; padding: 0; height: 100%;
            background: var(--viewer-bg);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        /* Top Navigation Toolbar */
        .pptx-toolbar {
            height: 48px;
            background: #0f172a;
            border-bottom: 1px solid #334155;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 16px;
            color: #f8fafc;
            flex-shrink: 0;
            z-index: 100;
        }
        .pptx-btn {
            background: #1e293b;
            border: 1px solid #475569;
            color: #f8fafc;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.15s ease;
        }
        .pptx-btn:hover {
            background: #334155;
            border-color: #64748b;
            color: #ffffff;
        }
        .pptx-btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }
        .pptx-title {
            font-weight: 600;
            font-size: 13px;
            max-width: 280px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        /* Presentation Viewport */
        .pptx-viewport {
            flex: 1 1 auto;
            overflow-y: auto;
            overflow-x: auto;
            padding: 24px 16px 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
            scrollbar-width: thin;
            scrollbar-color: #475569 #0f172a;
        }
        .pptx-viewport::-webkit-scrollbar { width: 8px; height: 8px; }
        .pptx-viewport::-webkit-scrollbar-track { background: #0f172a; }
        .pptx-viewport::-webkit-scrollbar-thumb { background: #475569; border-radius: 4px; }
        .pptx-viewport::-webkit-scrollbar-thumb:hover { background: #64748b; }

        .pptx-deck-container {
            width: 100%;
            max-width: 920px;
            display: flex;
            flex-direction: column;
            gap: 24px;
            transition: transform 0.2s ease;
            transform-origin: top center;
        }

        /* Slide Card */
        .pptx-slide-card {
            width: 100%;
            background: transparent;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .pptx-slide-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 4px;
        }
        .pptx-slide-badge {
            font-size: 11px;
            font-weight: 600;
            color: #94a3b8;
            background: rgba(15, 23, 42, 0.6);
            padding: 2px 8px;
            border-radius: 4px;
            border: 1px solid #334155;
        }
        .pptx-slide-canvas {
            position: relative;
            width: 100%;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: var(--slide-shadow);
            border: 1px solid var(--slide-border);
            overflow: hidden;
            container-type: inline-size;
        }
        .pptx-bullet {
            color: #0284c7;
            font-size: 13px;
        }
        /* Single slide mode styles */
        body.single-mode .pptx-slide-card {
            display: none !important;
        }
        body.single-mode .pptx-slide-card.active {
            display: flex !important;
        }
    </style>
</head>
<body>
    <div class="pptx-toolbar">
        <div class="pptx-title" title="{$fileName}">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#f97316" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 4m0 1a1 1 0 0 1 1 -1h16a1 1 0 0 1 1 1v10a1 1 0 0 1 -1 1h-16a1 1 0 0 1 -1 -1z"/><path d="M12 16v4"/><path d="M9 20h6"/><path d="M8 12l3 -3l2 2l3 -3"/></svg>
            <span>{$fileName}</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="pptx-btn" id="btnPrevSlide" title="Previous Slide (Left Arrow)">
                &larr; Prev
            </button>
            <span class="badge bg-dark text-light border border-secondary px-2.5 py-1" style="font-size: 12px;">
                Slide <span id="currentSlideDisplay">1</span> / {$totalSlides}
            </span>
            <button type="button" class="pptx-btn" id="btnNextSlide" title="Next Slide (Right Arrow)">
                Next &rarr;
            </button>
        </div>
        <div class="d-flex align-items-center gap-1.5">
            <button type="button" class="pptx-btn" id="btnToggleMode" title="Toggle Single Slide vs Continuous View">
                <span id="modeLabel">Single Slide</span>
            </button>
            <button type="button" class="pptx-btn" id="btnZoomOut" title="Zoom Out">-</button>
            <button type="button" class="pptx-btn" id="btnZoomReset" title="Reset Zoom">100%</button>
            <button type="button" class="pptx-btn" id="btnZoomIn" title="Zoom In">+</button>
        </div>
    </div>

    <div class="pptx-viewport" id="pptxViewport">
        <div class="pptx-deck-container" id="pptxDeck">
            {$slidesHtml}
        </div>
    </div>

    <script>
    (function () {
        var totalSlides = {$totalSlides};
        var currentSlide = 1;
        var isSingleMode = false;
        var currentZoom = 1.0;

        var displayEl = document.getElementById('currentSlideDisplay');
        var deckEl = document.getElementById('pptxDeck');
        var viewportEl = document.getElementById('pptxViewport');
        var btnPrev = document.getElementById('btnPrevSlide');
        var btnNext = document.getElementById('btnNextSlide');
        var btnToggle = document.getElementById('btnToggleMode');
        var modeLabel = document.getElementById('modeLabel');

        function updateSlideDisplay(num) {
            currentSlide = Math.max(1, Math.min(totalSlides, num));
            if (displayEl) displayEl.textContent = currentSlide;
            if (btnPrev) btnPrev.disabled = (currentSlide <= 1);
            if (btnNext) btnNext.disabled = (currentSlide >= totalSlides);

            if (isSingleMode) {
                var cards = document.querySelectorAll('.pptx-slide-card');
                cards.forEach(function (card, i) {
                    if (i + 1 === currentSlide) {
                        card.classList.add('active');
                    } else {
                        card.classList.remove('active');
                    }
                });
            }
        }

        function scrollToSlide(num) {
            updateSlideDisplay(num);
            if (isSingleMode) {
                viewportEl.scrollTop = 0;
            } else {
                var target = document.getElementById('slide-' + num);
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        }

        if (btnPrev) {
            btnPrev.addEventListener('click', function () {
                if (currentSlide > 1) scrollToSlide(currentSlide - 1);
            });
        }
        if (btnNext) {
            btnNext.addEventListener('click', function () {
                if (currentSlide < totalSlides) scrollToSlide(currentSlide + 1);
            });
        }

        // Toggle Single vs Continuous Scroll
        if (btnToggle) {
            btnToggle.addEventListener('click', function () {
                isSingleMode = !isSingleMode;
                if (isSingleMode) {
                    document.body.classList.add('single-mode');
                    modeLabel.textContent = 'Continuous';
                } else {
                    document.body.classList.remove('single-mode');
                    modeLabel.textContent = 'Single Slide';
                }
                scrollToSlide(currentSlide);
            });
        }

        // Zoom controls
        function applyZoom(z) {
            currentZoom = Math.max(0.5, Math.min(2.0, z));
            if (deckEl) deckEl.style.transform = 'scale(' + currentZoom + ')';
            var resetBtn = document.getElementById('btnZoomReset');
            if (resetBtn) resetBtn.textContent = Math.round(currentZoom * 100) + '%';
        }
        var btnZoomIn = document.getElementById('btnZoomIn');
        var btnZoomOut = document.getElementById('btnZoomOut');
        var btnZoomReset = document.getElementById('btnZoomReset');
        if (btnZoomIn) btnZoomIn.addEventListener('click', function () { applyZoom(currentZoom + 0.15); });
        if (btnZoomOut) btnZoomOut.addEventListener('click', function () { applyZoom(currentZoom - 0.15); });
        if (btnZoomReset) btnZoomReset.addEventListener('click', function () { applyZoom(1.0); });

        // Keyboard arrow navigation
        window.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowRight' || e.key === 'PageDown' || e.key === ' ') {
                if (currentSlide < totalSlides) {
                    e.preventDefault();
                    scrollToSlide(currentSlide + 1);
                }
            } else if (e.key === 'ArrowLeft' || e.key === 'PageUp') {
                if (currentSlide > 1) {
                    e.preventDefault();
                    scrollToSlide(currentSlide - 1);
                }
            }
        });

        // IntersectionObserver for continuous scroll slide index tracking
        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function (entries) {
                if (isSingleMode) return;
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        var sNum = parseInt(entry.target.getAttribute('data-slide-num'), 10);
                        if (sNum && !isNaN(sNum)) {
                            currentSlide = sNum;
                            if (displayEl) displayEl.textContent = currentSlide;
                            if (btnPrev) btnPrev.disabled = (currentSlide <= 1);
                            if (btnNext) btnNext.disabled = (currentSlide >= totalSlides);
                        }
                    }
                });
            }, { root: viewportEl, threshold: 0.5 });

            document.querySelectorAll('.pptx-slide-card').forEach(function (card) {
                observer.observe(card);
            });
        }

        updateSlideDisplay(1);
    })();
    </script>
</body>
</html>
HTML;
    }


    protected function errorPage(string $title, string $message): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{$title}</title>
    <link rel="stylesheet" href="/Tabler/dist/css/tabler.min.css">
    <style>
        body { display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; background: #f8fafc; font-family: sans-serif; }
        .card { max-width: 440px; padding: 32px; text-align: center; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 16px rgba(0,0,0,0.06); background: white; }
    </style>
</head>
<body>
    <div class="card">
        <h4 class="text-danger fw-bold mb-2">{$title}</h4>
        <p class="text-muted small mb-0">{$message}</p>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Wrap PDF slides into an interactive, pixel-perfect presentation viewer using PDF.js.
     */
    protected function wrapInPdfPresentationViewer(string $base64Pdf, string $fileName, int $totalSlides, string $aspectRatio): string
    {
        $slideCards = [];
        for ($i = 1; $i <= $totalSlides; $i++) {
            $slideCards[] = <<<CARD
<div class="pptx-slide-card" id="slide-{$i}" data-slide-num="{$i}">
    <div class="pptx-slide-header">
        <span class="pptx-slide-badge">Slide {$i} of {$totalSlides}</span>
    </div>
    <div class="pptx-slide-canvas" style="aspect-ratio: {$aspectRatio};">
        <canvas id="slide-canvas-{$i}"></canvas>
        <div class="slide-spinner" id="spinner-{$i}">
            <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
            <span>Loading slide {$i}...</span>
        </div>
    </div>
</div>
CARD;
        }

        $slidesHtml = implode("\n", $slideCards);

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{$fileName}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/Tabler/dist/css/tabler.min.css">
    <script src="/js/pdf.min.js"></script>
    <style>
        :root {
            --viewer-bg: #1e293b;
            --slide-border: #334155;
            --slide-shadow: 0 8px 24px rgba(0, 0, 0, 0.35);
        }
        *, *::before, *::after { box-sizing: border-box; }
        html, body {
            margin: 0; padding: 0; height: 100%;
            background: var(--viewer-bg);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        /* Top Navigation Toolbar */
        .pptx-toolbar {
            height: 48px;
            background: #0f172a;
            border-bottom: 1px solid #334155;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 16px;
            color: #f8fafc;
            flex-shrink: 0;
            z-index: 100;
        }
        .pptx-btn {
            background: #1e293b;
            border: 1px solid #475569;
            color: #f8fafc;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.15s ease;
        }
        .pptx-btn:hover {
            background: #334155;
            border-color: #64748b;
            color: #ffffff;
        }
        .pptx-btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }
        .pptx-title {
            font-weight: 600;
            font-size: 13px;
            max-width: 280px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        /* Presentation Viewport */
        .pptx-viewport {
            flex: 1 1 auto;
            overflow-y: auto;
            overflow-x: auto;
            padding: 24px 16px 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
            scrollbar-width: thin;
            scrollbar-color: #475569 #0f172a;
        }
        .pptx-viewport::-webkit-scrollbar { width: 8px; height: 8px; }
        .pptx-viewport::-webkit-scrollbar-track { background: #0f172a; }
        .pptx-viewport::-webkit-scrollbar-thumb { background: #475569; border-radius: 4px; }
        .pptx-viewport::-webkit-scrollbar-thumb:hover { background: #64748b; }

        .pptx-deck-container {
            width: 100%;
            max-width: 960px;
            display: flex;
            flex-direction: column;
            gap: 24px;
            transition: transform 0.2s ease;
            transform-origin: top center;
        }

        /* Slide Card */
        .pptx-slide-card {
            width: 100%;
            background: transparent;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .pptx-slide-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 4px;
        }
        .pptx-slide-badge {
            font-size: 11px;
            font-weight: 600;
            color: #94a3b8;
            background: rgba(15, 23, 42, 0.6);
            padding: 2px 8px;
            border-radius: 4px;
            border: 1px solid #334155;
        }
        .pptx-slide-canvas {
            position: relative;
            width: 100%;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: var(--slide-shadow);
            border: 1px solid var(--slide-border);
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .pptx-slide-canvas canvas {
            display: block;
            width: 100% !important;
            height: 100% !important;
            object-fit: contain;
        }
        .slide-spinner {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            display: flex;
            align-items: center;
            gap: 8px;
            color: #64748b;
            font-size: 12px;
        }
        /* Single slide mode styles */
        body.single-mode .pptx-slide-card {
            display: none !important;
        }
        body.single-mode .pptx-slide-card.active {
            display: flex !important;
        }
    </style>
</head>
<body>
    <div class="pptx-toolbar">
        <div class="pptx-title" title="{$fileName}">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#f97316" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 4m0 1a1 1 0 0 1 1 -1h16a1 1 0 0 1 1 1v10a1 1 0 0 1 -1 1h-16a1 1 0 0 1 -1 -1z"/><path d="M12 16v4"/><path d="M9 20h6"/><path d="M8 12l3 -3l2 2l3 -3"/></svg>
            <span>{$fileName}</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="pptx-btn" id="btnPrevSlide" title="Previous Slide (Left Arrow)">
                &larr; Prev
            </button>
            <span class="badge bg-dark text-light border border-secondary px-2.5 py-1" style="font-size: 12px;">
                Slide <span id="currentSlideDisplay">1</span> / {$totalSlides}
            </span>
            <button type="button" class="pptx-btn" id="btnNextSlide" title="Next Slide (Right Arrow)">
                Next &rarr;
            </button>
        </div>
        <div class="d-flex align-items-center gap-1.5">
            <button type="button" class="pptx-btn" id="btnToggleMode" title="Toggle Single Slide vs Continuous View">
                <span id="modeLabel">Single Slide</span>
            </button>
            <button type="button" class="pptx-btn" id="btnZoomOut" title="Zoom Out">-</button>
            <button type="button" class="pptx-btn" id="btnZoomReset" title="Reset Zoom">100%</button>
            <button type="button" class="pptx-btn" id="btnZoomIn" title="Zoom In">+</button>
        </div>
    </div>

    <div class="pptx-viewport" id="pptxViewport">
        <div class="pptx-deck-container" id="pptxDeck">
            {$slidesHtml}
        </div>
    </div>

    <script>
    (function () {
        var totalSlides = {$totalSlides};
        var currentSlide = 1;
        var isSingleMode = false;
        var currentZoom = 1.0;
        var pdfDoc = null;
        var renderedPages = {};

        var displayEl = document.getElementById('currentSlideDisplay');
        var deckEl = document.getElementById('pptxDeck');
        var viewportEl = document.getElementById('pptxViewport');
        var btnPrev = document.getElementById('btnPrevSlide');
        var btnNext = document.getElementById('btnNextSlide');
        var btnToggle = document.getElementById('btnToggleMode');
        var modeLabel = document.getElementById('modeLabel');

        if (typeof pdfjsLib !== 'undefined') {
            pdfjsLib.GlobalWorkerOptions.workerSrc = '/js/pdf.worker.min.js';

            var base64Data = "{$base64Pdf}";
            var rawData = atob(base64Data);
            var rawLen = rawData.length;
            var bytes = new Uint8Array(rawLen);
            for (var i = 0; i < rawLen; i++) {
                bytes[i] = rawData.charCodeAt(i);
            }

            pdfjsLib.getDocument({ data: bytes }).promise.then(function (pdf) {
                pdfDoc = pdf;
                totalSlides = pdf.numPages;

                renderSlidePage(1);
                setupObserver();
            }).catch(function (err) {
                console.error('PDF.js render error:', err);
            });
        }

        function renderSlidePage(pageNum) {
            if (!pdfDoc || renderedPages[pageNum]) return;
            renderedPages[pageNum] = true;

            pdfDoc.getPage(pageNum).then(function (page) {
                var canvas = document.getElementById('slide-canvas-' + pageNum);
                var spinner = document.getElementById('spinner-' + pageNum);
                if (!canvas) return;

                var dpr = window.devicePixelRatio || 1.5;
                var scale = Math.max(1.5, Math.min(2.5, dpr * 1.5));
                var viewport = page.getViewport({ scale: scale });

                var ctx = canvas.getContext('2d');
                canvas.width = viewport.width;
                canvas.height = viewport.height;

                page.render({
                    canvasContext: ctx,
                    viewport: viewport
                }).promise.then(function () {
                    if (spinner) spinner.style.display = 'none';
                });
            });
        }

        function setupObserver() {
            if ('IntersectionObserver' in window) {
                var observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            var num = parseInt(entry.target.getAttribute('data-slide-num'), 10);
                            if (num) {
                                renderSlidePage(num);
                                if (!isSingleMode) {
                                    updateSlideDisplay(num);
                                }
                            }
                        }
                    });
                }, { root: viewportEl, threshold: 0.2 });

                document.querySelectorAll('.pptx-slide-card').forEach(function (card) {
                    observer.observe(card);
                });
            } else {
                for (var p = 1; p <= totalSlides; p++) {
                    renderSlidePage(p);
                }
            }
        }

        function updateSlideDisplay(num) {
            currentSlide = Math.max(1, Math.min(totalSlides, num));
            if (displayEl) displayEl.textContent = currentSlide;
            if (btnPrev) btnPrev.disabled = (currentSlide <= 1);
            if (btnNext) btnNext.disabled = (currentSlide >= totalSlides);

            if (isSingleMode) {
                var cards = document.querySelectorAll('.pptx-slide-card');
                cards.forEach(function (card, i) {
                    if (i + 1 === currentSlide) {
                        card.classList.add('active');
                        renderSlidePage(i + 1);
                    } else {
                        card.classList.remove('active');
                    }
                });
            }
        }

        function scrollToSlide(num) {
            updateSlideDisplay(num);
            renderSlidePage(num);
            if (isSingleMode) {
                viewportEl.scrollTop = 0;
            } else {
                var target = document.getElementById('slide-' + num);
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        }

        if (btnPrev) {
            btnPrev.addEventListener('click', function () {
                if (currentSlide > 1) scrollToSlide(currentSlide - 1);
            });
        }
        if (btnNext) {
            btnNext.addEventListener('click', function () {
                if (currentSlide < totalSlides) scrollToSlide(currentSlide + 1);
            });
        }

        if (btnToggle) {
            btnToggle.addEventListener('click', function () {
                isSingleMode = !isSingleMode;
                if (isSingleMode) {
                    document.body.classList.add('single-mode');
                    modeLabel.textContent = 'Continuous';
                } else {
                    document.body.classList.remove('single-mode');
                    modeLabel.textContent = 'Single Slide';
                }
                scrollToSlide(currentSlide);
            });
        }

        // Zoom controls
        function applyZoom(z) {
            currentZoom = Math.max(0.5, Math.min(2.0, z));
            if (deckEl) deckEl.style.transform = 'scale(' + currentZoom + ')';
            var resetBtn = document.getElementById('btnZoomReset');
            if (resetBtn) resetBtn.textContent = Math.round(currentZoom * 100) + '%';
        }
        var btnZoomIn = document.getElementById('btnZoomIn');
        var btnZoomOut = document.getElementById('btnZoomOut');
        var btnZoomReset = document.getElementById('btnZoomReset');
        if (btnZoomIn) btnZoomIn.addEventListener('click', function () { applyZoom(currentZoom + 0.15); });
        if (btnZoomOut) btnZoomOut.addEventListener('click', function () { applyZoom(currentZoom - 0.15); });
        if (btnZoomReset) btnZoomReset.addEventListener('click', function () { applyZoom(1.0); });

        // Keyboard arrow navigation
        window.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowRight' || e.key === 'PageDown' || e.key === ' ') {
                if (currentSlide < totalSlides) {
                    e.preventDefault();
                    scrollToSlide(currentSlide + 1);
                }
            } else if (e.key === 'ArrowLeft' || e.key === 'PageUp') {
                if (currentSlide > 1) {
                    e.preventDefault();
                    scrollToSlide(currentSlide - 1);
                }
            } else if (e.key === 'Home') {
                e.preventDefault();
                scrollToSlide(1);
            } else if (e.key === 'End') {
                e.preventDefault();
                scrollToSlide(totalSlides);
            }
        });

        updateSlideDisplay(1);
    })();
    </script>
</body>
</html>
HTML;
    }
}
