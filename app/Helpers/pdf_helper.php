<?php

if (!function_exists('count_pdf_pages')) {
    /**
     * Count the number of pages in a PDF file using a lightweight,
     * dependency-free parser.
     *
     * Strategy:
     *   1. Verify the file starts with the %PDF- header.
     *   2. Count leaf page objects (/Type /Page, not /Type /Pages).
     *   3. Fall back to the /Count value on the root /Pages object when no
     *      leaf page objects can be matched.
     *
     * @param string $path Absolute path to the PDF file.
     * @return int Page count, or 0 when the file is not a valid PDF.
     */
    function count_pdf_pages(string $path): int
    {
        if (!is_file($path) || !is_readable($path)) {
            return 0;
        }

        $data = (string) file_get_contents($path);
        if (strlen($data) < 8 || strpos($data, '%PDF-') !== 0) {
            return 0;
        }

        // Leaf page objects: "/Type /Page" NOT followed by "s" (i.e. not "/Pages").
        $pageCount = preg_match_all('/\/Type\s*\/Page\b(?!s)/', $data, $matches);

        if ($pageCount === false) {
            return 0;
        }

        if ($pageCount > 0) {
            return $pageCount;
        }

        // Fallback: /Count N on a /Pages object.
        if (preg_match_all('/\/Type\s*\/Pages\b[^>]*?\/Count\s+(\d+)/s', $data, $countMatches)
            || preg_match_all('/\/Count\s+(\d+)\s*[^>]*?\/Type\s*\/Pages\b/s', $data, $countMatches)
        ) {
            $max = 0;
            foreach ($countMatches[1] as $c) {
                $max = max($max, (int) $c);
            }
            return $max;
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

        $data = (string) file_get_contents($path, false, null, 0, 1024);
        if (strpos($data, '%PDF-') !== 0) {
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
