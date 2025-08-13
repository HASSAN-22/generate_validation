<?php

namespace GenerateValidation\Strategies;

/**
 * Class TimestampRuleGenerator
 *
 * A validation rule generation strategy for `timestamp` column types.
 * This class generates the appropriate `date_format` rule to ensure the input
 * matches the standard `Y-m-d H:i:s` format, which is common for timestamps.
 *
 * @package GenerateValidation\Strategies
 */
class TimestampRuleGenerator extends RuleGeneratorAbstract
{
    /**
     * Generates a set of validation rules for a timestamp column.
     *
     * @param string $tableName The name of the database table.
     * @param string $columnName The name of the column.
     * @param array $columnDetails The detailed information about the column.
     * @param bool $isUpdate This parameter is not used as timestamp rules are consistent for both store and update.
     * @return array An array of validation rules.
     */
    public function generate(string $tableName, string $columnName, array $columnDetails, bool $isUpdate = false): array
    {
        // A. The primary rule to validate the input format for a timestamp.
        $rules = ['date_format:Y-m-d H:i:s'];

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
     * @return bool True if the column is of type `timestamp`.
     */
    public function canApply(array $columnDetails): bool
    {
        return $columnDetails['type_name'] === 'timestamp';
    }
}