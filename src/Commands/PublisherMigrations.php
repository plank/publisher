<?php

namespace Plank\Publisher\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Plank\Publisher\Contracts\Publishable;
use Plank\Publisher\Facades\Publisher;

class PublisherMigrations extends Command
{
    protected $signature = 'publisher:migrations';

    protected $description = 'Create migration files for models implementing the Publishable interface';

    public function handle()
    {
        $publishableModels = Publisher::publishableModels();

        $this->line('Generating Migrations for '.$publishableModels->count().' Publishable Models...');

        $bar = $this->output->createProgressBar($publishableModels->count());

        $publishableModels->each(function ($className) use ($bar) {
            $this->createMigration($className);
            $bar->advance();
        });

        $bar->finish();
    }

    protected function createMigration(Model&Publishable $model)
    {
        $className = 'AddPublishableFieldsTo'.class_basename($model).'Table';

        $migrationName = date('Y_m_d_His').'_'.Str::snake($className).'.php';
        $migrationPath = database_path('migrations/'.$migrationName);
        $migrationContent = $this->getMigrationContent($model);

        File::put($migrationPath, $migrationContent);

        $this->info("Created Migration: {$migrationName}");
    }

    protected function getMigrationContent(Model&Publishable $model)
    {
        $tableName = $model->getTable();
        $hasBeenPublishedColumn = config()->get('publisher.columns.has_been_published', 'has_been_published');
        $draftColumn = config()->get('publisher.columns.draft', 'draft');
        $workflowColumn = config()->get('publisher.columns.workflow', 'status');
        $unpublishedState = $model::workflow()::unpublished()->value;
        $shouldDeleteColumn = config()->get('publisher.columns.should_delete', 'should_delete');

        return <<<EOT
        <?php

        use Illuminate\Database\Migrations\Migration;
        use Illuminate\Database\Schema\Blueprint;
        use Illuminate\Support\Facades\Schema;

        return new class extends Migration {
            public function up(): void
            {
                Schema::table('$tableName', function (Blueprint \$table) {
                    \$table->json('$draftColumn')->nullable();
                    \$table->string('$workflowColumn')->default('$unpublishedState');
                    \$table->boolean('$hasBeenPublishedColumn')->default(false);
                    \$table->boolean('$shouldDeleteColumn')->default(false);
                });
            }

            public function down(): void
            {
                Schema::table('$tableName', function (Blueprint \$table) {
                    \$table->dropColumn('$workflowColumn');
                    \$table->dropColumn('$draftColumn');
                    \$table->dropColumn('$hasBeenPublishedColumn');
                    \$table->dropColumn('$shouldDeleteColumn');
                });
            }
        };
        EOT;
    }
}
