<?php

namespace GenerateValidation\Strategies;

use Illuminate\Support\Str;

/**
 * Class StringRuleGenerator
 *
 * A validation rule generation strategy for string-based column types.
 * This class generates `string` rules and infers `max` length based on
 * the column's type and definition. It also handles nullability and
 * skips image-related columns to avoid rule conflicts.
 *
 * @package GenerateValidation\Strategies
 */
class StringRuleGenerator extends RuleGeneratorAbstract
{
    /**
     * Generates a set of validation rules for a string column.
     *
     * @param string $tableName The name of the database table.
     * @param string $columnName The name of the column.
     * @param array $columnDetails The detailed information about the column.
     * @param bool $isUpdate This parameter is not used as string rules are consistent.
     * @return array An array of validation rules.
     */
    public function generate(string $tableName, string $columnName, array $columnDetails, bool $isUpdate = false): array
    {
        // A. Avoid generating string rules for columns that are identified as images.
        // This prevents conflicts with file-specific validation strategies.
        if ($this->hasTypeOfImage($columnDetails)) {
            return [];
        }

        $rules = ['string'];

        // B. Add `required` or `nullable` rule based on the column's database schema.
        if (!$columnDetails['nullable']) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        // C. Extract and add a `max` rule based on the column's defined length.
        $length = $this->extractLength($columnDetails);
        if ($length) {
            $rules[] = 'max:' . $length;
        }

        return $rules;
    }

    /**
     * Determines if this strategy can be applied to the given column.
     *
     * @param array $columnDetails The detailed information about the column.
     * @return bool True if the column is of a string-based type.
     */
    public function canApply(array $columnDetails): bool
    {
        return in_array($columnDetails['type_name'], ['varchar', 'char', 'tinytext', 'text', 'mediumtext', 'longtext']);
    }
}