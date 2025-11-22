<?php

namespace App\Console\Commands\Elastic;

use Illuminate\Console\Command;
use App\Models\Client;
use App\Models\ClientContact;
use App\Models\Credit;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\Quote;
use App\Models\RecurringInvoice;
use App\Models\Task;
use App\Models\Vendor;
use App\Models\VendorContact;
use Elastic\Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\Artisan;

class RebuildElasticIndexes extends Command
{
    protected $signature = 'elastic:rebuild
                            {--model= : Rebuild only a specific model (e.g., Invoice, Client)}
                            {--force : Force the operation without confirmation}
                            {--dry-run : Show what would be done without making changes}';

    protected $description = 'Rebuild Elasticsearch indexes one at a time to minimize production impact';

    protected array $searchableModels = [
        Client::class => 'clients_v2',
        ClientContact::class => 'client_contacts_v2',
        Credit::class => 'credits_v2',
        Expense::class => 'expenses_v2',
        Invoice::class => 'invoices_v2',
        Project::class => 'projects_v2',
        PurchaseOrder::class => 'purchase_orders_v2',
        Quote::class => 'quotes_v2',
        RecurringInvoice::class => 'recurring_invoices_v2',
        Task::class => 'tasks_v2',
        Vendor::class => 'vendors_v2',
        VendorContact::class => 'vendor_contacts_v2',
    ];

    public function handle(): int
    {
        $this->info('===========================================');
        $this->info('  Elasticsearch Index Rebuild (One-by-One)');
        $this->info('===========================================');
        $this->newLine();

        if (!$this->checkElasticsearchConnection()) {
            $this->error('Cannot connect to Elasticsearch. Please check your configuration.');
            return self::FAILURE;
        }

        // Handle single model rebuild
        if ($modelName = $this->option('model')) {
            return $this->rebuildSingleModel($modelName);
        }

        // Dry run mode
        if ($this->option('dry-run')) {
            return $this->performDryRun();
        }

        // Get confirmation for full rebuild
        if (!$this->option('force')) {
            $this->warn('This command will rebuild ALL Elasticsearch indexes ONE AT A TIME:');
            $this->info('  • Each index will be dropped, migrated, and re-imported sequentially');
            $this->info('  • Search will be unavailable for each model during its rebuild');
            $this->info('  • Other models remain searchable while one rebuilds');
            $this->info('  • This minimizes overall downtime for production systems');
            $this->newLine();

            $totalRecords = $this->getTotalRecordCount();
            $this->warn("Total records to re-index: {$totalRecords}");
            $this->newLine();

            if (!$this->confirm('Do you want to rebuild all indexes?', false)) {
                $this->info('Operation cancelled.');
                return self::SUCCESS;
            }
        }

        $this->newLine();

        // Rebuild all models one by one
        $totalModels = count($this->searchableModels);
        $currentModel = 0;
        $startTime = now();

        foreach ($this->searchableModels as $modelClass => $indexName) {
            $currentModel++;
            $modelName = class_basename($modelClass);

            $this->newLine();
            $this->info("[{$currentModel}/{$totalModels}] Rebuilding {$modelName}...");
            $this->line("Index: {$indexName}");

            if (!$this->rebuildIndex($modelClass, $indexName)) {
                $this->error("Failed to rebuild {$modelName}. Stopping.");
                return self::FAILURE;
            }
        }

        $this->newLine();
        $duration = now()->diffForHumans($startTime, true);
        $this->info('✓ All indexes rebuilt successfully!');
        $this->info("Total time: {$duration}");
        return self::SUCCESS;
    }

    protected function rebuildSingleModel(string $modelName): int
    {
        // Find the model class
        $modelClass = null;
        foreach ($this->searchableModels as $class => $indexName) {
            if (class_basename($class) === $modelName) {
                $modelClass = $class;
                break;
            }
        }

        if (!$modelClass) {
            $this->error("Model '{$modelName}' not found.");
            $this->info('Available models: ' . implode(', ', array_map('class_basename', array_keys($this->searchableModels))));
            return self::FAILURE;
        }

        $indexName = $this->searchableModels[$modelClass];
        $recordCount = $modelClass::count();

        $this->newLine();
        $this->info("Rebuilding {$modelName}");
        $this->line("Index: {$indexName}");
        $this->line("Records: {$recordCount}");
        $this->newLine();

        if (!$this->option('force') && !$this->option('dry-run')) {
            if (!$this->confirm('Continue with rebuild?', true)) {
                $this->info('Operation cancelled.');
                return self::SUCCESS;
            }
        }

        if ($this->option('dry-run')) {
            $this->info('DRY RUN - Would rebuild:');
            $this->line("  1. Drop index: {$indexName}");
            $this->line("  2. Run elastic:migrate for {$modelName}");
            $this->line("  3. Import {$recordCount} {$modelName} records");
            return self::SUCCESS;
        }

        $startTime = now();

        if ($this->rebuildIndex($modelClass, $indexName)) {
            $duration = now()->diffForHumans($startTime, true);
            $this->newLine();
            $this->info("✓ {$modelName} rebuilt successfully in {$duration}!");
            return self::SUCCESS;
        }

        $this->error("✗ Failed to rebuild {$modelName}");
        return self::FAILURE;
    }

    protected function performDryRun(): int
    {
        $this->info('DRY RUN - The following would be rebuilt:');
        $this->newLine();

        foreach ($this->searchableModels as $modelClass => $indexName) {
            $modelName = class_basename($modelClass);
            $recordCount = $modelClass::count();

            $this->line("• {$modelName}");
            $this->line("  Index: {$indexName}");
            $this->line("  Records: {$recordCount}");
            $this->newLine();
        }

        $this->info('No changes made (dry run mode)');
        return self::SUCCESS;
    }

    protected function rebuildIndex(string $modelClass, string $indexName): bool
    {
        $modelName = class_basename($modelClass);
        $client = $this->getElasticsearchClient();

        try {
            // Step 1: Drop the index
            $this->line("  [1/3] Dropping index {$indexName}...");
            if ($client->indices()->exists(['index' => $indexName])) {
                $client->indices()->delete(['index' => $indexName]);
                $this->info("    ✓ Index dropped");
            } else {
                $this->line("    - Index does not exist (will be created)", 'comment');
            }

            // Step 2: Run migration for this specific index
            $this->line("  [2/3] Running elastic migration...");
            // Note: elastic:migrate recreates all indexes, but only the dropped one will be created
            Artisan::call('elastic:migrate', [], $this->getOutput());
            $this->info("    ✓ Migration completed");

            // Step 3: Import data
            $this->line("  [3/3] Importing {$modelName} data...");
            $recordCount = $modelClass::count();

            if ($recordCount > 0) {
                Artisan::call('scout:import', [
                    'model' => $modelClass,
                ], $this->getOutput());
                $this->info("    ✓ Imported {$recordCount} records");
            } else {
                $this->line("    - No records to import", 'comment');
            }

            return true;

        } catch (\Exception $e) {
            $this->error("    ✗ Error: " . $e->getMessage());
            return false;
        }
    }

    protected function getTotalRecordCount(): int
    {
        $total = 0;
        foreach ($this->searchableModels as $modelClass => $indexName) {
            try {
                $total += $modelClass::count();
            } catch (\Exception $e) {
                // Skip if model fails to count
            }
        }
        return $total;
    }

    protected function checkElasticsearchConnection(): bool
    {
        try {
            $client = $this->getElasticsearchClient();
            $client->ping();
            $this->info('✓ Elasticsearch connection successful');
            return true;
        } catch (\Exception $e) {
            $this->error('✗ Elasticsearch connection failed: ' . $e->getMessage());
            return false;
        }
    }

    protected function getElasticsearchClient()
    {
        return ClientBuilder::fromConfig(config('elastic.client.connections.default'));
    }
}
