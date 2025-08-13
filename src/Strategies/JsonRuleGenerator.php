<?php

namespace GenerateValidation\Strategies;

/**
 * Class JsonRuleGenerator
 *
 * A validation rule generation strategy for `JSON` column types.
 * This class generates the `json` validation rule, which ensures that the
 * input is a valid JSON string.
 *
 * @package GenerateValidation\Strategies
 */
class JsonRuleGenerator extends RuleGeneratorAbstract
{
    /**
     * Generates a set of validation rules for a JSON column.
     *
     * @param string $tableName The name of the database table.
     * @param string $columnName The name of the column.
     * @param array $columnDetails The detailed information about the column.
     * @param bool $isUpdate This parameter is not used as JSON rules are consistent for both store and update.
     * @return array An array of validation rules.
     */
    public function generate(string $tableName, string $columnName, array $columnDetails, bool $isUpdate = false): array
    {
        // A. The primary rule to validate that the input is a valid JSON string.
        $rules = ['json'];
        
        // B. Add `required` or `nullable` rule based on the column's database schema.
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
     * @return bool True if the column is of type `json`.
     */
    public function canApply(array $columnDetails): bool
    {
        return $columnDetails['type_name'] === 'json';
    }
}