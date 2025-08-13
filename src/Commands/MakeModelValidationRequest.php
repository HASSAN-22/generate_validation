<?php

namespace GenerateValidation\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use GenerateValidation\Strategies\StringRuleGenerator;
use GenerateValidation\Strategies\IntegerRuleGenerator;
use GenerateValidation\Strategies\FloatRuleGenerator;
use GenerateValidation\Strategies\DateRuleGenerator;
use GenerateValidation\Strategies\DatetimeRuleGenerator;
use GenerateValidation\Strategies\BooleanRuleGenerator;
use GenerateValidation\Strategies\EnumRuleGenerator;
use GenerateValidation\Strategies\TimestampRuleGenerator;
use GenerateValidation\Strategies\JsonRuleGenerator;
use GenerateValidation\Strategies\BlobRuleGenerator;
use GenerateValidation\Strategies\GeometryRuleGenerator;
use GenerateValidation\Strategies\UniqueRuleGenerator;
use Throwable;

/**
 * Class MakeModelValidationRequest
 *
 * A custom artisan command to create a Form Request class with validation rules
 * automatically generated from the specified model's database schema.
 *
 * @package GenerateValidation\Commands
 */
class MakeModelValidationRequest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:validation {model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a Form Request with validation rules generated from a model.';

    /**
     * Execute the console command.
     *
     * @return void
     * @throws Throwable
     */
    public function handle(): void
    {
        $modelName = $this->argument('model');
        $modelClass = "App\\Models\\{$modelName}";

        if (!class_exists($modelClass)) {
            $this->error("Model '{$modelName}' does not exist.");
            return;
        }

        $model = new $modelClass;
        $tableName = $model->getTable();
        $columns = Schema::getColumnListing($tableName);
        
        $generatedRules = $this->generateRules($columns, $tableName);

        $requestName = "{$modelName}Request";
        
        $requestStub = file_get_contents(__DIR__ . '/stubs/request.stub');
        
        $storeRulesString = $this->formatRulesForStub($generatedRules['store']);
        $updateRulesString = $this->formatRulesForStub($generatedRules['update']);

        $requestContent = str_replace(
            ['{{ modelName }}', '{{ storeRules }}', '{{ updateRules }}'],
            [$modelName, $storeRulesString, $updateRulesString],
            $requestStub
        );
        
        $requestPath = app_path("Http/Requests/{$requestName}.php");
        if (!is_dir(dirname($requestPath))) {
            mkdir(dirname($requestPath), 0755, true);
        }
        
        file_put_contents($requestPath, $requestContent);
        $this->info("Form Request '{$requestName}' created successfully!");
    }


    /**
     * Generates a complete set of validation rules for both store and update operations.
     *
     * @param array $columns
     * @param string $tableName
     * @return array
     * @throws Throwable
     */
    protected function generateRules(array $columns, string $tableName): array
    {
        $storeRules = $this->generateRuleSet($columns, $tableName, false);
        $updateRules = $this->generateRuleSet($columns, $tableName, true);

        return [
            'store' => $storeRules,
            'update' => $updateRules,
        ];
    }
    
    /**
     * Generates a single set of rules for a given context (store or update).
     *
     * @param array $columns
     * @param string $tableName
     * @param bool $isUpdate
     * @return array
     */
    protected function generateRuleSet(array $columns, string $tableName, bool $isUpdate): array
    {
        $rules = [];
        $schemaBuilder = Schema::getConnection()->getSchemaBuilder();
        $allColumnsDetails = $schemaBuilder->getColumns($tableName);
        
        // A list of all available rule generation strategies.
        $strategies = [
            new UniqueRuleGenerator(),
            new BooleanRuleGenerator(),
            new EnumRuleGenerator(),
            new JsonRuleGenerator(),
            new StringRuleGenerator(),
            new IntegerRuleGenerator(),
            new FloatRuleGenerator(),
            new TimestampRuleGenerator(),
            new DateRuleGenerator(),
            new DatetimeRuleGenerator(),
            new BlobRuleGenerator(),
            new GeometryRuleGenerator(),
        ];

        foreach ($columns as $columnName) {
            // Skips common Laravel columns that do not require validation.
            if (in_array($columnName, ['id', 'created_at', 'updated_at'])) {
                continue;
            }

            $columnDetails = collect($allColumnsDetails)->firstWhere('name', $columnName);
            
            if (!$columnDetails) {
                continue;
            }
            
            $columnRules = [];
            
            foreach ($strategies as $strategy) {
                if ($strategy->canApply($columnDetails)) {
                    $generatedRules = $strategy->generate($tableName, $columnName, $columnDetails, $isUpdate);
                    $columnRules = array_merge($columnRules, $generatedRules);
                }
            }
            
            // Automatically adds an 'exists' rule for foreign keys.
            if (Str::endsWith($columnName, '_id')) {
                $referencedTable = Str::plural(Str::before($columnName, '_id'));
                if (Schema::hasTable($referencedTable)) {
                    $columnRules[] = "exists:{$referencedTable},id";
                }
            }
            
            if (!empty($columnRules)) {
                $rules[$columnName] = array_unique($columnRules);
            }
        }
        
        $finalRules = [];
        foreach ($rules as $columnName => $columnRules) {
            $uniqueRules = array_unique($columnRules);
            
            // Resolves conflicts between 'required' and 'nullable' for update operations.
            if (in_array('required', $uniqueRules) && in_array('nullable', $uniqueRules) && $isUpdate) {
                $uniqueRules = array_diff($uniqueRules, ['required']);
            }
            
            $finalRules[$columnName] = $uniqueRules;
        }
        
        return $finalRules;
    }

    /**
     * Formats an array of rules into a string suitable for the stub file.
     *
     * @param array $rules
     * @return string
     */
    protected function formatRulesForStub(array $rules): string
    {
        $formattedRules = [];
        foreach ($rules as $columnName => $columnRules) {
            $rulesString = "'" . implode("', '", $columnRules) . "'";
            $formattedRules[] = "'{$columnName}' => [{$rulesString}]";
        }
        return implode(',' . PHP_EOL . '            ', $formattedRules);
    }
}