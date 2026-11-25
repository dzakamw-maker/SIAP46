<?php

namespace App\Services;

use XMLReader;
use ZipArchive;

class SimpleSpreadsheetReader
{
    /**
     * Membaca file spreadsheet (.xlsx atau .csv) menjadi array baris data.
     *
     * @return array<int, array<int, string>>
     */
    public static function read(string $filePath, ?string $originalExtension = null): array
    {
        $ext = strtolower($originalExtension ?: pathinfo($filePath, PATHINFO_EXTENSION));

        if ($ext === 'csv' || $ext === 'txt') {
            return self::readCsv($filePath);
        }

        if ($ext === 'xlsx') {
            return self::readXlsx($filePath);
        }

        if (self::isZipFile($filePath)) {
            return self::readXlsx($filePath);
        }

        return self::readCsv($filePath);
    }

    /**
     * Membaca file CSV dengan deteksi otomatis delimiter (koma atau titik koma).
     *
     * @return array<int, array<int, string>>
     */
    public static function readCsv(string $filePath): array
    {
        $rows = [];
        if (! file_exists($filePath)) {
            return [];
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return [];
        }

        // Hapus UTF-8 BOM jika ada
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }

        $lines = preg_split('/\r\n|\r|\n/', trim($content));
        if (empty($lines)) {
            return [];
        }

        // Deteksi delimiter dari baris pertama (koma, titik koma, tab)
        $firstLine = $lines[0];
        $delimiters = [';', ',', "\t"];
        $chosenDelim = ',';
        $maxCount = 0;
        foreach ($delimiters as $d) {
            $count = substr_count($firstLine, $d);
            if ($count > $maxCount) {
                $maxCount = $count;
                $chosenDelim = $d;
            }
        }

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $data = str_getcsv($line, $chosenDelim);
            $rows[] = array_map(fn ($val) => trim((string) $val), $data);
        }

        return $rows;
    }

    /**
     * Membaca file Excel .xlsx secara streaming dengan XMLReader untuk performa sangat cepat & hemat memori.
     *
     * @return array<int, array<int, string>>
     */
    public static function readXlsx(string $filePath): array
    {
        if (! class_exists('ZipArchive') || ! class_exists('XMLReader')) {
            return self::readCsv($filePath);
        }

        $zip = new ZipArchive;
        if ($zip->open($filePath) !== true) {
            return [];
        }

        // 1. Ekstrak shared strings jika ada (streaming via XMLReader)
        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml !== false) {
            $xml = new XMLReader;
            $xml->xml($sharedXml);
            $currentStr = '';
            $inT = false;
            while ($xml->read()) {
                if ($xml->nodeType === XMLReader::ELEMENT) {
                    if ($xml->name === 'si') {
                        $currentStr = '';
                    } elseif ($xml->name === 't') {
                        $inT = true;
                    }
                } elseif ($xml->nodeType === XMLReader::TEXT || $xml->nodeType === XMLReader::SIGNIFICANT_WHITESPACE) {
                    if ($inT) {
                        $currentStr .= $xml->value;
                    }
                } elseif ($xml->nodeType === XMLReader::END_ELEMENT) {
                    if ($xml->name === 't') {
                        $inT = false;
                    } elseif ($xml->name === 'si') {
                        $sharedStrings[] = $currentStr;
                    }
                }
            }
            $xml->close();
        }

        // 2. Dapatkan seluruh worksheet dalam workbook (mendukung multi-sheet)
        $sheetFiles = self::getWorksheetFiles($zip);

        $rows = [];
        foreach ($sheetFiles as $sheetInfo) {
            $sheetXml = $zip->getFromName($sheetInfo['path']);
            if ($sheetXml === false) {
                continue;
            }

            // Jika sheet memiliki nama (misal nama tab "MPLB XI-1"), sematkan penanda rombel
            if (! empty($sheetInfo['name'])) {
                $rows[] = ['Rombel', ': '.$sheetInfo['name']];
            }

            $sheetRows = self::parseWorksheetXml($sheetXml, $sharedStrings);
            foreach ($sheetRows as $r) {
                $rows[] = $r;
            }
        }

        $zip->close();

        return $rows;
    }

    /**
     * Parse konten XML worksheet menjadi array baris sel.
     *
     * @param  array<int, string>  $sharedStrings
     * @return array<int, array<int, string>>
     */
    private static function parseWorksheetXml(string $sheetXml, array $sharedStrings): array
    {
        $xml = new XMLReader;
        $xml->xml($sheetXml);

        $rows = [];
        $currentRow = [];
        $currentCellIndex = 0;
        $currentCellType = '';
        $currentCellValue = '';
        $inV = false;
        $inInlineT = false;

        while ($xml->read()) {
            if ($xml->nodeType === XMLReader::ELEMENT) {
                if ($xml->name === 'row') {
                    $currentRow = [];
                    $currentCellIndex = 0;
                } elseif ($xml->name === 'c') {
                    $r = $xml->getAttribute('r');
                    if ($r) {
                        $colLetters = preg_replace('/[^A-Z]/', '', strtoupper($r));
                        $colIndex = 0;
                        for ($i = 0; $i < strlen($colLetters); $i++) {
                            $colIndex = $colIndex * 26 + (ord($colLetters[$i]) - ord('A') + 1);
                        }
                        $currentCellIndex = max(0, $colIndex - 1);
                    }
                    $currentCellType = (string) $xml->getAttribute('t');
                    $currentCellValue = '';
                } elseif ($xml->name === 'v') {
                    $inV = true;
                } elseif ($xml->name === 't' && $xml->depth > 2) {
                    $inInlineT = true;
                }
            } elseif ($xml->nodeType === XMLReader::TEXT || $xml->nodeType === XMLReader::SIGNIFICANT_WHITESPACE) {
                if ($inV || $inInlineT) {
                    $currentCellValue .= $xml->value;
                }
            } elseif ($xml->nodeType === XMLReader::END_ELEMENT) {
                if ($xml->name === 'v') {
                    $inV = false;
                } elseif ($xml->name === 't') {
                    $inInlineT = false;
                } elseif ($xml->name === 'c') {
                    // Batasi kolom sampai index 35 (A s.d. AJ) agar tidak memproses jutaan kolom kosong akibat style Excel
                    if ($currentCellIndex <= 35) {
                        $val = trim($currentCellValue);
                        if ($currentCellType === 's') {
                            $val = $sharedStrings[(int) $val] ?? '';
                        }
                        $currentRow[$currentCellIndex] = $val;
                    }
                    $currentCellIndex++;
                } elseif ($xml->name === 'row') {
                    if (! empty($currentRow)) {
                        $hasContent = false;
                        foreach ($currentRow as $v) {
                            if ($v !== '') {
                                $hasContent = true;
                                break;
                            }
                        }
                        if ($hasContent) {
                            $maxIdx = max(array_keys($currentRow));
                            $completeRow = [];
                            for ($c = 0; $c <= $maxIdx; $c++) {
                                $completeRow[] = $currentRow[$c] ?? '';
                            }
                            $rows[] = $completeRow;
                        }
                    }
                }
            }
        }

        $xml->close();

        return $rows;
    }

    /**
     * Dapatkan daftar seluruh file worksheet yang ada di dalam workbook .xlsx beserta namanya.
     *
     * @return array<int, array{name: string, path: string}>
     */
    private static function getWorksheetFiles(ZipArchive $zip): array
    {
        $sheets = [];

        $workbookRelsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
        $relMap = [];
        if ($workbookRelsXml !== false) {
            $xml = new XMLReader;
            $xml->xml($workbookRelsXml);
            while ($xml->read()) {
                if ($xml->nodeType === XMLReader::ELEMENT && $xml->name === 'Relationship') {
                    $id = $xml->getAttribute('Id');
                    $target = $xml->getAttribute('Target');
                    if ($id && $target) {
                        $targetPath = str_replace('\\', '/', ltrim($target, '/'));
                        if (! str_starts_with($targetPath, 'xl/')) {
                            $targetPath = 'xl/'.$targetPath;
                        }
                        $relMap[$id] = $targetPath;
                    }
                }
            }
            $xml->close();
        }

        $workbookXml = $zip->getFromName('xl/workbook.xml');
        if ($workbookXml !== false && ! empty($relMap)) {
            $xml = new XMLReader;
            $xml->xml($workbookXml);
            while ($xml->read()) {
                if ($xml->nodeType === XMLReader::ELEMENT && $xml->name === 'sheet') {
                    $name = (string) $xml->getAttribute('name');
                    $rId = (string) ($xml->getAttribute('r:id') ?: $xml->getAttribute('id'));
                    if (! $rId) {
                        if ($xml->moveToFirstAttribute()) {
                            do {
                                if ($xml->localName === 'id' || $xml->name === 'r:id') {
                                    $rId = (string) $xml->value;
                                    break;
                                }
                            } while ($xml->moveToNextAttribute());
                            $xml->moveToElement();
                        }
                    }
                    if ($rId && isset($relMap[$rId])) {
                        $sheets[] = [
                            'name' => $name,
                            'path' => $relMap[$rId],
                        ];
                    }
                }
            }
            $xml->close();
        }

        if (! empty($sheets)) {
            return $sheets;
        }

        // Fallback: scan seluruh file xl/worksheets/*.xml secara natural order
        $sheetPaths = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if ($stat && preg_match('#^xl/worksheets/sheet\d+\.xml$#i', $stat['name'])) {
                $sheetPaths[] = $stat['name'];
            }
        }
        natsort($sheetPaths);

        foreach ($sheetPaths as $p) {
            $sheets[] = [
                'name' => '',
                'path' => $p,
            ];
        }

        return $sheets;
    }

    private static function isZipFile(string $filePath): bool
    {
        $handle = @fopen($filePath, 'rb');
        if (! $handle) {
            return false;
        }
        $header = fread($handle, 4);
        fclose($handle);

        return $header === "PK\x03\x04";
    }
}
