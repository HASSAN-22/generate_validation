<?php

namespace GenerateValidation\Strategies;

use Illuminate\Support\Str;

/**
 * Class EnumRuleGenerator
 *
 * A validation rule generation strategy for `ENUM` column types.
 * This class parses the enum values from the database schema and generates an
 * `in:` rule to validate that the input value is one of the allowed options.
 *
 * @package GenerateValidation\Strategies
 */
class EnumRuleGenerator extends RuleGeneratorAbstract
{
    /**
     * Generates a set of validation rules for an ENUM column.
     *
     * @param string $tableName The name of the database table.
     * @param string $columnName The name of the column.
     * @param array $columnDetails The detailed information about the column.
     * @param bool $isUpdate This parameter is not used as ENUM rules are consistent for both store and update.
     * @return array An array of validation rules.
     */
    public function generate(string $tableName, string $columnName, array $columnDetails, bool $isUpdate = false): array
    {
        $rules = [];
        
        // A. Extract the enum values from the column type string.
        preg_match('/enum\((.*?)\)/', $columnDetails['type'], $matches);
        
        if (isset($matches[1])) {
            // Remove single quotes and explode the string into an array of values.
            $enum_values = explode(',', str_replace("'", "", $matches[1]));
            $rules[] = 'in:' . implode(',', $enum_values);
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
     * @return bool True if the column is of type `enum`.
     */
    public function canApply(array $columnDetails): bool
    {
        return $columnDetails['type_name'] === 'enum';
    }
}