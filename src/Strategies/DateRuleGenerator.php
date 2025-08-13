<?php

namespace GenerateValidation\Strategies;

/**
 * Class DateRuleGenerator
 *
 * A validation rule generation strategy for columns of type `date`.
 * This class generates standard date validation rules, including format and
 * nullability based on the database schema.
 *
 * @package GenerateValidation\Strategies
 */
class DateRuleGenerator extends RuleGeneratorAbstract
{
    /**
     * Generates a set of validation rules for a date column.
     *
     * @param string $tableName The name of the database table.
     * @param string $columnName The name of the column.
     * @param array $columnDetails The detailed information about the column.
     * @param bool $isUpdate This parameter is not used as date rules are consistent for both store and update.
     * @return array An array of validation rules.
     */
    public function generate(string $tableName, string $columnName, array $columnDetails, bool $isUpdate = false): array
    {
        // Add the primary date format rule to ensure the input matches the standard Y-m-d format.
        $rules = ['date_format:Y-m-d'];
        
        // Add `required` or `nullable` rule based on the column's database schema.
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
     * @return bool True if the column is of type `date`.
     */
    public function canApply(array $columnDetails): bool
    {
        return $columnDetails['type_name'] === 'date';
    }
}