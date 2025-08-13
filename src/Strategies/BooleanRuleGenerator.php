<?php

namespace GenerateValidation\Strategies;

use Illuminate\Support\Str;

/**
 * Class BooleanRuleGenerator
 *
 * A validation rule generation strategy for boolean and boolean-like column types.
 * It correctly identifies `boolean`, `bit`, and `tinyint(1)` columns and
 * generates the appropriate `boolean` rule.
 *
 * @package GenerateValidation\Strategies
 */
class BooleanRuleGenerator extends RuleGeneratorAbstract
{
    /**
     * Generates a set of validation rules for a boolean column.
     *
     * @param string $tableName The name of the database table.
     * @param string $columnName The name of the column.
     * @param array $columnDetails The detailed information about the column.
     * @param bool $isUpdate This parameter is not used as boolean rules are consistent for both store and update.
     * @return array An array of validation rules.
     */
    public function generate(string $tableName, string $columnName, array $columnDetails, bool $isUpdate = false): array
    {
        $rules = ['boolean'];
        
        // A. Add `required` or `nullable` rule based on the column's database schema.
        // This logic is straightforward as a boolean value should always be provided if required.
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
     * @param array $columnDetails The detailed information about the column.
     * @return bool True if the column is of a boolean-like type.
     */
    public function canApply(array $columnDetails): bool
    {
        // Checks for standard boolean types as well as `tinyint(1)`, which is a common
        // representation of a boolean in MySQL.
        return in_array($columnDetails['type_name'], ['boolean', 'bit']) ||
               ($columnDetails['type_name'] === 'tinyint' && Str::contains($columnDetails['type'], '(1)'));
    }
}