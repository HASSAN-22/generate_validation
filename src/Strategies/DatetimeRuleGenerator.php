<?php

namespace GenerateValidation\Strategies;

/**
 * Class DatetimeRuleGenerator
 *
 * A validation rule generation strategy for `datetime`, `time`, and `year` column types.
 * This class generates the appropriate format and value rules based on the column type.
 *
 * @package GenerateValidation\Strategies
 */
class DatetimeRuleGenerator extends RuleGeneratorAbstract
{
    /**
     * Generates a set of validation rules for various date and time-related column types.
     *
     * @param string $tableName The name of the database table.
     * @param string $columnName The name of the column.
     * @param array $columnDetails The detailed information about the column.
     * @param bool $isUpdate This parameter is not used as these rules are consistent for both store and update.
     * @return array An array of validation rules.
     */
    public function generate(string $tableName, string $columnName, array $columnDetails, bool $isUpdate = false): array
    {
        $rules = [];
        $typeName = $columnDetails['type_name'];

        // A. Add specific format or value rules based on the column type.
        if ($typeName === 'datetime') {
            $rules[] = 'date_format:Y-m-d H:i:s';
        } elseif ($typeName === 'time') {
            $rules[] = 'date_format:H:i:s';
        } elseif ($typeName === 'year') {
            $rules[] = 'digits:4';
            $rules[] = 'numeric';
            $rules[] = 'between:1901,2155'; // The valid range for MySQL YEAR type.
        }
        
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
     * @return bool True if the column is of a date/time-related type.
     */
    public function canApply(array $columnDetails): bool
    {   
        return in_array($columnDetails['type_name'], ['datetime', 'time', 'year']);
    }
}