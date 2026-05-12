<?php

namespace App\Support\Xlsx;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

/**
 * Reads Unbeaten Track starter town .xlsx (README sheet + * Towns sheet with columns A–N).
 * No external packages — uses ZipArchive + SimpleXML.
 */
final class TownStarterWorkbookReader
{
    private const NS_MAIN = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    private const NS_REL = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    private const NS_PKG = 'http://schemas.openxmlformats.org/package/2006/relationships';

    /**
     * @return list<array<string, mixed>> rows keyed by header slug (town, region, approx_pop, …)
     */
    public function readDataRows(string $absolutePath): array
    {
        if (! is_readable($absolutePath)) {
            throw new RuntimeException("Cannot read file: {$absolutePath}");
        }

        $zip = new ZipArchive;
        if ($zip->open($absolutePath) !== true) {
            throw new RuntimeException("Not a valid .xlsx zip: {$absolutePath}");
        }

        try {
            $workbookXml = $zip->getFromName('xl/workbook.xml');
            if ($workbookXml === false) {
                throw new RuntimeException('Missing xl/workbook.xml');
            }

            $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
            if ($relsXml === false) {
                throw new RuntimeException('Missing xl/_rels/workbook.xml.rels');
            }

            $sheetPath = $this->resolveTownsSheetPath($workbookXml, $relsXml);
            $sheetXml = $zip->getFromName($sheetPath);
            if ($sheetXml === false) {
                throw new RuntimeException("Missing worksheet: {$sheetPath}");
            }

            $sharedStrings = $this->loadSharedStrings($zip);

            return $this->parseSheetRows($sheetXml, $sharedStrings);
        } finally {
            $zip->close();
        }
    }

    private function resolveTownsSheetPath(string $workbookXml, string $relsXml): string
    {
        $wb = new SimpleXMLElement($workbookXml);
        $wb->registerXPathNamespace('m', self::NS_MAIN);
        $wb->registerXPathNamespace('r', self::NS_REL);

        $rels = new SimpleXMLElement($relsXml);
        $rels->registerXPathNamespace('p', self::NS_PKG);

        $idToTarget = [];
        foreach ($rels->Relationship as $rel) {
            $id = (string) $rel['Id'];
            $target = (string) $rel['Target'];
            $idToTarget[$id] = $target;
        }

        foreach ($wb->sheets->sheet as $sheet) {
            $name = (string) $sheet['name'];
            if (stripos($name, 'Towns') === false) {
                continue;
            }

            $rid = (string) $sheet->attributes(self::NS_REL)['id'];
            if ($rid === '' || ! isset($idToTarget[$rid])) {
                continue;
            }

            $target = $idToTarget[$rid];
            $target = ltrim($target, '/');
            if (str_starts_with($target, 'xl/')) {
                return $target;
            }

            return 'xl/'.$target;
        }

        throw new RuntimeException('No worksheet with "Towns" in its name was found.');
    }

    /**
     * @return list<string>
     */
    private function loadSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $sx = new SimpleXMLElement($xml);
        $sx->registerXPathNamespace('m', self::NS_MAIN);
        $out = [];

        foreach ($sx->si as $si) {
            $si->registerXPathNamespace('m', self::NS_MAIN);
            $parts = [];
            foreach ($si->xpath('.//m:t') ?: [] as $t) {
                $parts[] = (string) $t;
            }
            $out[] = implode('', $parts);
        }

        return $out;
    }

    /**
     * @param  list<string>  $sharedStrings
     * @return list<array<string, mixed>>
     */
    private function parseSheetRows(string $sheetXml, array $sharedStrings): array
    {
        $sheet = new SimpleXMLElement($sheetXml);
        $sheetData = $sheet->children(self::NS_MAIN)->sheetData;
        if ($sheetData === null) {
            return [];
        }

        $rows = [];
        foreach ($sheetData->row as $row) {
            $rows[] = $row;
        }

        if ($rows === []) {
            return [];
        }

        $headerRow = array_shift($rows);
        $headers = $this->rowToAssocByColumn($headerRow, $sharedStrings);
        $headerSlugs = [];
        foreach ($headers as $col => $label) {
            $label = trim((string) $label);
            if ($label === '') {
                continue;
            }
            $headerSlugs[$col] = $this->slugHeader($label);
        }

        $data = [];
        foreach ($rows as $rowEl) {
            $cells = $this->rowToAssocByColumn($rowEl, $sharedStrings);
            $assoc = [];
            foreach ($headerSlugs as $col => $slug) {
                $assoc[$slug] = $cells[$col] ?? null;
            }

            $town = isset($assoc['town']) ? trim((string) $assoc['town']) : '';
            if ($town === '') {
                continue;
            }

            $data[] = $assoc;
        }

        return $data;
    }

    /**
     * @return array<string, string|int|float|null> column letter => raw cell value
     */
    private function rowToAssocByColumn(SimpleXMLElement $row, array $sharedStrings): array
    {
        $row->registerXPathNamespace('m', self::NS_MAIN);
        $out = [];

        foreach ($row->children(self::NS_MAIN) as $child) {
            if ($child->getName() !== 'c') {
                continue;
            }

            $attrs = $child->attributes();
            $ref = (string) ($attrs['r'] ?? '');
            if (! preg_match('/^([A-Z]+)/', $ref, $m)) {
                continue;
            }
            $col = $m[1];
            $out[$col] = $this->cellValue($child, $sharedStrings);
        }

        return $out;
    }

    /**
     * @param  list<string>  $sharedStrings
     */
    private function cellValue(SimpleXMLElement $c, array $sharedStrings): string|int|float|null
    {
        $attrs = $c->attributes();
        $type = (string) ($attrs['t'] ?? '');

        if ($type === 'inlineStr') {
            $c->registerXPathNamespace('m', self::NS_MAIN);
            $parts = [];
            foreach ($c->xpath('.//m:t') ?: [] as $t) {
                $parts[] = (string) $t;
            }

            return implode('', $parts);
        }

        if ($type === 's') {
            $idx = (int) $c->v;

            return $sharedStrings[$idx] ?? '';
        }

        if ($type === 'b') {
            return ((string) $c->v) === '1';
        }

        if (isset($c->v)) {
            $raw = (string) $c->v;
            if ($raw === '') {
                return null;
            }
            if (is_numeric($raw)) {
                return str_contains($raw, '.') ? (float) $raw : (int) $raw;
            }

            return $raw;
        }

        return null;
    }

    private function slugHeader(string $label): string
    {
        $slug = strtolower(preg_replace('/[^A-Za-z0-9]+/', '_', $label) ?? '');

        return trim($slug, '_');
    }
}
