<?php

if (!function_exists('count_pdf_pages')) {
    /**
     * Count the number of pages in a PDF file using a resilient, multi-tiered parser.
     *
     * Strategies:
     *   1. Tier 1: Native CLI `pdfinfo` (from poppler-utils) when available.
     *   2. Tier 2: Linearized PDF header inspection (/Linearized ... /N <count>).
     *   3. Tier 3: Uncompressed leaf page (/Type /Page) & root (/Type /Pages /Count <count>) parsing.
     *   4. Tier 4: Decompressed Object Stream (/ObjStm & /FlateDecode) page extraction.
     *
     * @param string $path Absolute path to the PDF file.
     * @return int Page count, or 0 when the file is not a valid PDF.
     */
    function count_pdf_pages(string $path): int
    {
        if (!is_file($path) || !is_readable($path)) {
            return 0;
        }

        $fileSize = (int) @filesize($path);
        if ($fileSize < 10) {
            return 0;
        }

        // Quick check for PDF magic bytes in the first 1024 bytes
        $header = (string) @file_get_contents($path, false, null, 0, 1024);
        if (!str_contains($header, '%PDF-')) {
            return 0;
        }

        // -------------------------------------------------------------
        // Tier 1: Native `pdfinfo` CLI (Poppler) - Ultra Fast & 100% Accurate
        // -------------------------------------------------------------
        if (function_exists('shell_exec') && !in_array('shell_exec', explode(',', (string) ini_get('disable_functions')), true)) {
            try {
                $cmd = (DIRECTORY_SEPARATOR === '\\')
                    ? 'pdfinfo ' . escapeshellarg($path) . ' 2>NUL'
                    : 'pdfinfo ' . escapeshellarg($path) . ' 2>/dev/null';
                
                $output = @shell_exec($cmd);
                if ($output && preg_match('/Pages:\s+(\d+)/i', $output, $matches)) {
                    $pages = (int) $matches[1];
                    if ($pages > 0) {
                        return $pages;
                    }
                }
            } catch (\Throwable $e) {
                // Fall through to pure PHP tiers
            }
        }

        // -------------------------------------------------------------
        // Tier 2: Linearized PDF Header Inspection (/Linearized ... /N <count>)
        // -------------------------------------------------------------
        if (preg_match('/\/Linearized\b.*?\/N\s+(\d+)/s', $header, $linMatches)) {
            $linPages = (int) $linMatches[1];
            if ($linPages > 0) {
                return $linPages;
            }
        }

        // -------------------------------------------------------------
        // Tier 3: Uncompressed Page Objects & Root Page Tree (/Pages ... /Count)
        // -------------------------------------------------------------
        // Read up to 8MB in chunks or full file if smaller
        $maxRead = min($fileSize, 8 * 1024 * 1024);
        $data = (string) @file_get_contents($path, false, null, 0, $maxRead);

        // 3a. Leaf page objects: "/Type /Page" NOT followed by "s" (i.e. not "/Pages")
        $leafCount = preg_match_all('/\/Type\s*\/Page\b(?!s)/', $data, $leafMatches);
        if ($leafCount !== false && $leafCount > 0) {
            return $leafCount;
        }

        // 3b. Root /Pages object with /Count
        if (preg_match_all('/\/Type\s*\/Pages\b[^>]*?\/Count\s+(\d+)/s', $data, $countMatches)
            || preg_match_all('/\/Count\s+(\d+)\s*[^>]*?\/Type\s*\/Pages\b/s', $data, $countMatches)
        ) {
            $maxPages = 0;
            foreach ($countMatches[1] as $c) {
                $maxPages = max($maxPages, (int) $c);
            }
            if ($maxPages > 0) {
                return $maxPages;
            }
        }

        // -------------------------------------------------------------
        // Tier 4: Object Stream (/ObjStm) Decompression (PDF 1.5+)
        // -------------------------------------------------------------
        // Modern PDFs (Word/Canva exports) compress structural objects inside Flate streams
        if (function_exists('gzuncompress') || function_exists('zlib_decode')) {
            $streamMatches = [];
            if (preg_match_all('/<<[^>]*\/ObjStm[^>]*>>\s*stream[\r\n]+(.*?)[\r\n]+endstream/s', $data, $streamMatches)) {
                $decompressedCount = 0;
                foreach ($streamMatches[1] as $rawStream) {
                    $decompressed = null;
                    if (function_exists('gzuncompress')) {
                        $decompressed = @gzuncompress($rawStream);
                    }
                    if ($decompressed === null && function_exists('zlib_decode')) {
                        $decompressed = @zlib_decode($rawStream);
                    }
                    if ($decompressed !== null && $decompressed !== false) {
                        $pCount = preg_match_all('/\/Type\s*\/Page\b(?!s)/', $decompressed, $m);
                        if ($pCount !== false && $pCount > 0) {
                            $decompressedCount += $pCount;
                        } elseif (preg_match('/\/Count\s+(\d+)/', $decompressed, $cntM)) {
                            $decompressedCount = max($decompressedCount, (int) $cntM[1]);
                        }
                    }
                }
                if ($decompressedCount > 0) {
                    return $decompressedCount;
                }
            }
        }

        // Final fallback: Any generic /Count <digit> inside dictionary objects
        if (preg_match_all('/\/Count\s+(\d+)/', $data, $genericCounts)) {
            $maxGeneric = 0;
            foreach ($genericCounts[1] as $c) {
                $maxGeneric = max($maxGeneric, (int) $c);
            }
            if ($maxGeneric > 0) {
                return $maxGeneric;
            }
        }

        return 0;
    }
}

if (!function_exists('is_valid_pdf')) {
    /**
     * Validate that a file is a genuine PDF document.
     *
     * @param string $path     Absolute path to the file.
     * @param string $fileName Original client filename (used for extension check).
     * @return bool
     */
    function is_valid_pdf(string $path, string $fileName = ''): bool
    {
        if (!is_file($path) || !is_readable($path)) {
            return false;
        }

        $fileSize = (int) @filesize($path);
        if ($fileSize < 10) {
            return false;
        }

        $data = (string) @file_get_contents($path, false, null, 0, 1024);
        if (!str_contains($data, '%PDF-')) {
            return false;
        }

        if ($fileName !== '') {
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if ($ext !== 'pdf') {
                return false;
            }
        }

        return true;
    }
}
