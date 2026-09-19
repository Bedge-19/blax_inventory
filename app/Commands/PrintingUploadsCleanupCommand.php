<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class PrintingUploadsCleanupCommand extends BaseCommand
{
    protected $group       = 'Printing';
    protected $name        = 'printing:cleanup-abandoned';
    protected $description = 'Clean up orphaned printing uploads and reference photos older than 24 hours.';

    protected $usage       = 'printing:cleanup-abandoned [options]';
    protected $arguments   = [];
    protected $options     = [
        '--dry-run' => 'Simulate cleanup without deleting any files.',
        '--hours'   => 'Age threshold in hours for abandoned files (default: 24).',
    ];

    public function run(array $params)
    {
        $dryRun = array_key_exists('dry-run', $params) || CLI::getOption('dry-run');
        $hours  = (int) (CLI::getOption('hours') ?? 24);
        if ($hours <= 0) {
            $hours = 24;
        }

        $cutoffTime = time() - ($hours * 3600);
        $cutoffDateStr = date('Y-m-d H:i:s', $cutoffTime);

        CLI::write("Scanning for orphaned printing uploads older than {$hours} hours (before {$cutoffDateStr})...", 'cyan');
        if ($dryRun) {
            CLI::write('[DRY-RUN MODE] No files will actually be deleted.', 'yellow');
        }

        $db = \Config\Database::connect();
        $deletedDocuments = 0;
        $deletedAttachments = 0;

        // 1. Scan primary printing documents directory
        $docsDir = WRITEPATH . 'uploads/printing';
        if (is_dir($docsDir)) {
            $files = scandir($docsDir);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..' || $file === '.gitkeep' || $file === 'index.html') {
                    continue;
                }

                $filePath = $docsDir . DIRECTORY_SEPARATOR . $file;
                if (!is_file($filePath)) {
                    continue;
                }

                $mtime = filemtime($filePath);
                if ($mtime !== false && $mtime < $cutoffTime) {
                    // Check if file is associated with any confirmed printing request
                    $matched = $db->table('printing_requests')
                        ->groupStart()
                            ->like('file_url', $file)
                            ->orWhere('file_url', $file)
                        ->groupEnd()
                        ->countAllResults();

                    if ($matched === 0) {
                        if ($dryRun) {
                            CLI::write("Would delete document: {$file}", 'light_gray');
                        } else {
                            if (@unlink($filePath)) {
                                $deletedDocuments++;
                                CLI::write("Deleted orphaned document: {$file}", 'green');
                            } else {
                                CLI::error("Failed to delete file: {$filePath}");
                            }
                        }
                    }
                }
            }
        }

        // 2. Scan reference photos/attachments directory
        $attachmentsDir = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'printing_attachments';
        if (is_dir($attachmentsDir)) {
            $files = scandir($attachmentsDir);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..' || $file === '.gitkeep' || $file === 'index.html') {
                    continue;
                }

                $filePath = $attachmentsDir . DIRECTORY_SEPARATOR . $file;
                if (!is_file($filePath)) {
                    continue;
                }

                $mtime = filemtime($filePath);
                if ($mtime !== false && $mtime < $cutoffTime) {
                    $matched = $db->table('printing_request_attachments')
                        ->like('image_url', $file)
                        ->countAllResults();

                    if ($matched === 0) {
                        if ($dryRun) {
                            CLI::write("Would delete attachment: {$file}", 'light_gray');
                        } else {
                            if (@unlink($filePath)) {
                                $deletedAttachments++;
                                CLI::write("Deleted orphaned attachment: {$file}", 'green');
                            } else {
                                CLI::error("Failed to delete attachment: {$filePath}");
                            }
                        }
                    }
                }
            }
        }

        CLI::write("Cleanup completed. Deleted {$deletedDocuments} document(s) and {$deletedAttachments} attachment(s).", 'green');
        return EXIT_SUCCESS;
    }
}
