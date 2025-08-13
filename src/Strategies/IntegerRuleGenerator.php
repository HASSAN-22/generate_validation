<?php

namespace GenerateValidation\Strategies;

use Illuminate\Support\Str;

/**
 * Class IntegerRuleGenerator
 *
 * A validation rule generation strategy for integer column types.
 * This class generates `integer` rules along with `min` and `max` constraints
 * based on the specific integer type (e.g., `tinyint`, `bigint`) and whether it's
 * signed or unsigned. It also includes special handling for `tinyint(1)` to treat it as a boolean.
 *
 * @package GenerateValidation\Strategies
 */
class IntegerRuleGenerator extends RuleGeneratorAbstract
{
    /**
     * Custom rules for specific column names, overriding inferred rules.
     *
     * @var array
     */
    protected array $columnRules = [
        'age' => ['min:0', 'max:120'],
        'quantity' => ['min:0'],
        'count' => ['min:0'],
        'stock' => ['min:0'],
    ];

    /**
     * Generates a set of validation rules for an integer column.
     *
     * @param string $tableName The name of the database table.
     * @param string $columnName The name of the column.
     * @param array $columnDetails The detailed information about the column.
     * @param bool $isUpdate This parameter is not used as integer rules are consistent.
     * @return array An array of validation rules.
     */
    public function generate(string $tableName, string $columnName, array $columnDetails, bool $isUpdate = false): array
    {
        $typeName = Str::lower($columnDetails['type_name']);
      
        // A. Special handling for tinyint(1) which is a common representation for a boolean in MySQL.
        if ($this->isTinyIntBoolean($columnDetails)) {
            $rules[] = 'boolean';
            // B. Add nullability rules.
            if (!$columnDetails['nullable']) {
                $rules[] = 'required';
            } else {
                $rules[] = 'nullable';
            }
            return $rules;
        }

        $rules = ['integer'];

        // C. Add `required` or `nullable` rule based on the column's database schema.
        if (!$columnDetails['nullable']) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }
        
        // D. Apply custom rules if they exist for the specific column name.
        if (isset($this->columnRules[$columnName])) {
            $rules = array_merge($rules, $this->columnRules[$columnName]);
        }
        // E. Otherwise, determine `min` and `max` based on the integer type.
        else {
            if (Str::contains($columnDetails['type'], 'unsigned')) {
                $rules[] = 'min:0';
                // Set max value based on unsigned integer type
                if ($typeName === 'tinyint') {
                    $rules[] = 'max:255';
                } elseif ($typeName === 'smallint') {
                    $rules[] = 'max:65535';
                } elseif ($typeName === 'mediumint') {
                    $rules[] = 'max:16777215';
                } elseif ($typeName === 'int' || $typeName === 'integer') {
                    $rules[] = 'max:4294967295';
                } elseif ($typeName === 'bigint') {
                    $rules[] = 'max:18446744073709551615';
                }
            } else {
                // Set min and max values based on signed integer type
                if ($typeName === 'tinyint') {
                    $rules[] = 'min:-128';
                    $rules[] = 'max:127';
                } elseif ($typeName === 'smallint') {
                    $rules[] = 'min:-32768';
                    $rules[] = 'max:32767';
                } elseif ($typeName === 'mediumint') {
                    $rules[] = 'min:-8388608';
                    $rules[] = 'max:8388607';
                } elseif ($typeName === 'int' || $typeName === 'integer' || $typeName === 'serial') {
                    $rules[] = 'min:-2147483648';
                    $rules[] = 'max:2147483647';
                } elseif ($typeName === 'bigint') {
                    $rules[] = 'min:-9223372036854775808';
                    $rules[] = 'max:9223372036854775807';
                }
            }
        }
        
        return $rules;
    }

    /**
     * Determines if this strategy can be applied to the given column.
     *
     * @param array $columnDetails The detailed information about the column.
     * @return bool True if the column is of an integer-like type.
     */
    public function canApply(array $columnDetails): bool
    {
        return in_array($columnDetails['type_name'], ['int', 'integer', 'tinyint', 'smallint', 'mediumint', 'bigint', 'serial']);
    }

    /**
     * Checks if a tinyint column is being used to represent a boolean.
     *
     * @param array $columnDetails The detailed information about the column.
     * @return bool True if the column is a tinyint(1).
     */
    protected function isTinyIntBoolean(array $columnDetails): bool
    {
        return $columnDetails['type_name'] === 'tinyint' && Str::contains($columnDetails['type'], '(1)');
    }
}