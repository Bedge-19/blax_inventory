<?php

namespace App\Commands;

use App\Models\ProductEmbeddingModel;
use App\Models\ProductModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Generates / refreshes Cohere embeddings for existing products.
 *
 *   php spark products:embed              # only new or changed products
 *   php spark products:embed --all        # force re-embed everything
 *   php spark products:embed --id 12      # a single product
 *   php spark products:embed --limit 10   # cap the number processed
 *   php spark products:embed --dry-run    # show what would be sent, no API calls
 */
class EmbedProducts extends BaseCommand
{
    protected $group       = 'Products';
    protected $name        = 'products:embed';
    protected $description = 'Generate or refresh Cohere Embed v4.0 vectors for marketplace products.';
    protected $usage       = 'products:embed [--all] [--id <id>] [--limit <n>] [--dry-run]';
    protected $arguments   = [];
    protected $options     = [
        '--all'     => 'Re-embed every product even if its searchable text is unchanged.',
        '--id'      => 'Only process a single product id.',
        '--limit'   => 'Maximum number of products to process.',
        '--dry-run' => 'Print the generated embedding documents without calling Cohere.',
    ];

    public function run(array $params)
    {
        $service = service('semanticSearch');
        $config  = $service->config();

        $force  = array_key_exists('all', $params) || CLI::getOption('all') !== null;
        $dryRun = array_key_exists('dry-run', $params) || CLI::getOption('dry-run') !== null;
        $id     = (int) (CLI::getOption('id') ?? 0);
        $limit  = (int) (CLI::getOption('limit') ?? 0);

        CLI::write('Model            : ' . $config->model, 'dark_gray');
        CLI::write('Output dimension : ' . $config->outputDimension, 'dark_gray');
        CLI::write('Batch size       : ' . $config->batchSize, 'dark_gray');

        if (! $dryRun && ! $service->isEnabled()) {
            CLI::error('Cohere is not configured. Set COHERE_API_KEY in .env (and keep cohere.enabled = true).');

            return EXIT_ERROR;
        }

        $productModel   = new ProductModel();
        $embeddingModel = new ProductEmbeddingModel();

        // A model/dimension change makes stored vectors incomparable.
        if (! $dryRun) {
            $purged = $embeddingModel->purgeIncompatible($config->model, $config->outputDimension);
            if ($purged > 0) {
                CLI::write("Removed {$purged} embedding(s) from a previous model/dimension.", 'yellow');
            }
        }

        $rows = $productModel->getEmbeddingSources($id > 0 ? [$id] : [], $limit);

        if ($rows === []) {
            CLI::write('No products found to process.', 'yellow');

            return EXIT_SUCCESS;
        }

        CLI::write('Products found   : ' . count($rows), 'dark_gray');
        CLI::newLine();

        if ($dryRun) {
            foreach ($rows as $row) {
                $document = $service->buildDocument($row);
                CLI::write('#' . $row['id'] . ' ' . $row['name'], 'green');
                CLI::write($document, 'dark_gray');
                CLI::write(str_repeat('-', 60), 'dark_gray');
            }
            CLI::write('Dry run complete. No API calls were made.', 'yellow');

            return EXIT_SUCCESS;
        }

        $started = microtime(true);

        try {
            $stats = $service->syncProducts($rows, $force);
        } catch (\Throwable $e) {
            CLI::error('Embedding run aborted: ' . $e->getMessage());

            return EXIT_ERROR;
        }

        $elapsed = round(microtime(true) - $started, 2);

        CLI::write('Embedded  : ' . $stats['embedded'], 'green');
        CLI::write('Unchanged : ' . $stats['unchanged'], 'dark_gray');
        CLI::write('Failed    : ' . $stats['failed'], $stats['failed'] > 0 ? 'red' : 'dark_gray');
        CLI::write('Elapsed   : ' . $elapsed . 's', 'dark_gray');
        CLI::write('Stored    : ' . $embeddingModel->countEmbedded($config->model, $config->outputDimension), 'dark_gray');

        foreach (array_unique($stats['errors']) as $error) {
            CLI::write('  ! ' . $error, 'red');
        }

        return $stats['failed'] > 0 ? EXIT_ERROR : EXIT_SUCCESS;
    }
}
