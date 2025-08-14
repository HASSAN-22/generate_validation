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

    private string $modelName;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:validation 
        {--model= : The name of the model for which to generate validation} 
        {--ignore= : Optional, columns to ignore, comma-separated}';


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
        $this->modelName = $this->option('model');
        $ignoreColumns = $this->option('ignore');
        $modelClass = "App\\Models\\{$this->modelName}";

        if (!class_exists($modelClass)) {
            $this->error("Model '{$this->modelName}' does not exist.");
            return;
        }

        $model = new $modelClass;
        $tableName = $model->getTable();
        $columns = Schema::getColumnListing($tableName);
        
        $generatedRules = $this->generateRules($columns, $tableName, explode(',', $ignoreColumns));

        $requestName = "{$this->modelName}Request";
        
        $requestStub = file_get_contents(__DIR__ . '/stubs/request.stub');
        
        $storeRulesString = $this->formatRulesForStub($generatedRules['store']);
        $updateRulesString = $this->formatRulesForStub($generatedRules['update']);

        $requestContent = str_replace(
            ['{{ modelName }}', '{{ storeRules }}', '{{ updateRules }}'],
            [$this->modelName, $storeRulesString, $updateRulesString],
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
     * @param array $ignoreColumns
     * @return array
     * @throws Throwable
     */
    protected function generateRules(array $columns, string $tableName, array $ignoreColumns): array
    {
        $storeRules = $this->generateRuleSet($columns, $tableName, false, $ignoreColumns);
        $updateRules = $this->generateRuleSet($columns, $tableName, true, $ignoreColumns);

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
     * @param array $ignoreColumns
     * @return array
     */
    protected function generateRuleSet(array $columns, string $tableName, bool $isUpdate, array $ignoreColumns): array
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
        
        $columns = array_values(array_diff($columns, array_values($ignoreColumns)));
        
        $customRules = config('generate_validation.custom_rules.' . $tableName, []);
        if(!empty($customRules)){
            foreach($ignoreColumns as $ignoreColumn){
                unset($customRules[$ignoreColumn]);
            }
            $columns = array_values(array_diff($columns, array_keys($customRules)));
        }
        
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

            if(!empty($customRules)){
                foreach($customRules as $c=>$value){
                    $columnRules = [];
                    $generatedRules = !is_array($value) ? explode('|',$value) : $value;
                    $columnRules = array_merge($columnRules, $generatedRules);
                    $this->addForeignKeyExistsRule($c, $columnRules, $rules);
                }
                $columnRules = [];
                $customRules = [];
            }
            
            foreach ($strategies as $strategy) {
                if ($strategy->canApply($columnDetails)) {
                   $generatedRules = $strategy->generate($tableName, $columnName, $columnDetails, $isUpdate);
                    $columnRules = array_merge($columnRules, $generatedRules);
                }
            }

            $this->addForeignKeyExistsRule($columnName, $columnRules, $rules);
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
     * Adds an "exists" validation rule for foreign key columns ending with "_id".
     *
     * This method checks if the given column name indicates a foreign key
     * (ending with "_id") and if the referenced table exists in the database.
     * If so, it appends an "exists:{table},id" validation rule to the column rules.
     * The resulting rules are stored in the provided $rules array, ensuring uniqueness.
     *
     * @param string $columnName   The name of the database column to check.
     * @param array  $columnRules  The current validation rules for the given column.
     * @param array  $rules        Reference to the main array holding all validation rules.
     *
     * @return void
     */
    protected function addForeignKeyExistsRule($columnName, $columnRules, &$rules)
    {
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