<?php

use CodeIgniter\Test\CIUnitTestCase;

class PdfHelperTest extends CIUnitTestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();
        helper('pdf');
        $this->tmpDir = sys_get_temp_dir();
    }

    private function makePdfFixture(array $pageObjects, ?int $rootCount = null): string
    {
        $content = "%PDF-1.4\n";
        foreach ($pageObjects as $objNum) {
            $content .= "{$objNum} 0 obj << /Type /Page /Parent 9 0 R >> endobj\n";
        }
        if ($rootCount !== null) {
            $content .= "9 0 obj << /Type /Pages /Kids [] /Count {$rootCount} >> endobj\n";
        }
        $content .= "trailer << /Root 9 0 R >>\n%%EOF\n";

        $path = tempnam($this->tmpDir, 'pdf_');
        file_put_contents($path, $content);

        return $path;
    }

    public function testCountPagesForStandardPdf(): void
    {
        $path = $this->makePdfFixture([1, 2, 3], 3);

        $this->assertSame(3, count_pdf_pages($path));

        @unlink($path);
    }

    public function testCountPagesFallsBackToRootCountWhenNoLeafObjects(): void
    {
        $path = $this->makePdfFixture([], 4);

        $this->assertSame(4, count_pdf_pages($path));

        @unlink($path);
    }

    public function testCountPagesReturnsZeroForMissingFile(): void
    {
        $this->assertSame(0, count_pdf_pages($this->tmpDir . '/does_not_exist.pdf'));
    }

    public function testCountPagesReturnsZeroForNonPdf(): void
    {
        $path = tempnam($this->tmpDir, 'txt_');
        file_put_contents($path, "Not a PDF at all.\n");

        $this->assertSame(0, count_pdf_pages($path));

        @unlink($path);
    }

    public function testCountPagesDoesNotCountPagesObjectsAsPages(): void
    {
        // Two leaf pages + a nested /Pages object should still be 2.
        $path = $this->makePdfFixture([1, 2], 2);

        $this->assertSame(2, count_pdf_pages($path));

        @unlink($path);
    }

    public function testIsValidPdfAcceptsPdfAndRejectsOthers(): void
    {
        $pdfPath = $this->makePdfFixture([1, 2], 2);
        $this->assertTrue(is_valid_pdf($pdfPath, 'report.pdf'));
        $this->assertFalse(is_valid_pdf($pdfPath, 'report.txt'));
        @unlink($pdfPath);

        $plainTxt = tempnam($this->tmpDir, 'txt_');
        file_put_contents($plainTxt, 'Definitely not a PDF.');
        $this->assertFalse(is_valid_pdf($plainTxt, 'fake.pdf'));
        @unlink($plainTxt);

        $this->assertFalse(is_valid_pdf($this->tmpDir . '/missing.pdf', 'missing.pdf'));
    }
}
