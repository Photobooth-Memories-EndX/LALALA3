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
                        if ($shdColor) $tdStyles[] = "background-color: {$shdColor};";
                        $colSpanAttr = $colSpan > 1 ? " colspan=\"{$colSpan}\"" : '';
                        $cellsHtml .= '<td style="' . implode(' ', $tdStyles) . '"' . $colSpanAttr . '>' . $cellContent . '</td>';
                    }
                }
                $rowsHtml .= '<tr>' . $cellsHtml . '</tr>';
            }
        }

        return '<div class="table-responsive my-3"><table class="table table-bordered table-sm docx-table">' . $rowsHtml . '</table></div>';
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
        body { margin: 0; padding: 24px; background: #f8fafc; font-family: monospace; font-size: 13px; line-height: 1.6; }
        pre { background: #ffffff; padding: 24px; border-radius: 8px; border: 1px solid #e2e8f0; white-space: pre-wrap; word-break: break-all; }
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
            --docx-bg: #e2e8f0;
            --docx-paper: #ffffff;
            --docx-text: #1e293b;
        }
        body {
            background-color: var(--docx-bg);
            margin: 0;
            padding: 24px 16px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: var(--docx-text);
            display: flex;
            justify-content: center;
        }
        .docx-paper-sheet {
            background-color: var(--docx-paper);
            width: 100%;
            max-width: 860px;
            min-height: 92vh;
            padding: 56px 64px;
            box-shadow: 0 4px 24px rgba(15, 23, 42, 0.12), 0 1px 3px rgba(15, 23, 42, 0.08);
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            font-size: 11pt;
            line-height: 1.6;
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
        .docx-table {
            border-collapse: collapse;
            width: 100%;
            margin: 16px 0;
            border: 1px solid #cbd5e1;
            font-size: 10.5pt;
        }
        .docx-table th, .docx-table td {
            border: 1px solid #cbd5e1;
            padding: 8px 12px;
            vertical-align: top;
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
            .docx-paper-sheet {
                padding: 28px 24px;
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
        body { margin: 0; padding: 20px; background: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .sheet-wrapper { background: #ffffff; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.06); overflow-x: auto; }
        table { border-collapse: collapse; width: 100%; font-size: 12px; }
        td, th { border: 1px solid #cbd5e1 !important; padding: 6px 10px; }
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
}
