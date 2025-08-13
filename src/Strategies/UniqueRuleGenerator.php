<?php

namespace GenerateValidation\Strategies;

use Illuminate\Support\Facades\Schema;

/**
 * Class UniqueRuleGenerator
 *
 * A validation rule generation strategy for `unique` and `required/nullable` rules.
 * This class inspects the database schema to identify unique columns and
 * generates the appropriate validation rules for both `store` and `update`
 * operations, correctly handling the exclusion of the current record during updates.
 *
 * @package GenerateValidation\Strategies
 */
class UniqueRuleGenerator extends RuleGeneratorAbstract
{
    /**
     * Generates a set of validation rules, including `unique` and `required/nullable`.
     *
     * @param string $tableName The name of the database table.
     * @param string $columnName The name of the column.
     * @param array $columnDetails The detailed information about the column.
     * @param bool $isUpdate Flag to determine if the context is an update operation.
     * @return array An array of validation rules.
     */
    public function generate(string $tableName, string $columnName, array $columnDetails, bool $isUpdate = false): array
    {
        $rules = [];
        
        // A. Skip generating rules for the primary key column.
        if ($this->isPrimaryKey($tableName, $columnName)) {
            return [];
        }

        // B. Check if the column has a unique index and apply the appropriate rule.
        if ($this->isUnique($tableName, $columnName)) {
            if ($isUpdate) {
                // For updates, the unique rule must ignore the current record.
                // Note: The ID is not available here, so a placeholder is used.
                // The final ID should be passed in the FormRequest.
                $rules[] = "unique:{$tableName},{$columnName},id";
            } else {
                // For store operations, a simple unique rule is sufficient.
                $rules[] = "unique:{$tableName},{$columnName}";
            }
        }

        // C. Add `nullable` or `required` rule based on the database schema.
        // This is a foundational rule that should be applied to most columns.
        if (!$columnDetails['nullable']) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }
        
        return $rules;
    }

    /**
     * Determines if this strategy can be applied to the given column.
     *
     * This strategy is a catch-all that applies to most column types to
     * handle `unique` and `required/nullable` rules.
     *
     * @param array $columnDetails The detailed information about the column.
     * @return bool Always returns true, as this strategy is a fallback for all columns.
     */
    public function canApply(array $columnDetails): bool
    {
        return true;
    }
}