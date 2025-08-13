<?php

namespace GenerateValidation\Strategies;

use Illuminate\Support\Str;

/**
 * Class FloatRuleGenerator
 *
 * A validation rule generation strategy for floating-point column types such as
 * `decimal`, `float`, and `double`. It generates `numeric` rules and
 * infers additional rules like `min` and `max` based on the column's name.
 *
 * @package GenerateValidation\Strategies
 */
class FloatRuleGenerator extends RuleGeneratorAbstract
{
    /**
     * Custom rules for specific column names, overriding inferred rules.
     *
     * @var array
     */
    protected array $columnRules = [
        'age' => ['min:0', 'max:120'],
        'percent' => ['min:0', 'max:100'],
    ];

    /**
     * Generates a set of validation rules for a floating-point column.
     *
     * @param string $tableName The name of the database table.
     * @param string $columnName The name of the column.
     * @param array $columnDetails The detailed information about the column.
     * @param bool $isUpdate This parameter is not used as float rules are consistent.
     * @return array An array of validation rules.
     */
    public function generate(string $tableName, string $columnName, array $columnDetails, bool $isUpdate = false): array
    {
        $rules = ['numeric'];

        // A. Add `required` or `nullable` rule based on the column's database schema.
        if (!$columnDetails['nullable']) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }
        
        // B. Apply custom rules if they exist for the specific column name.
        if (isset($this->columnRules[$columnName])) {
            $rules = array_merge($rules, $this->columnRules[$columnName]);
        }
        // C. Otherwise, infer rules based on common naming conventions.
        else {
            $inferredRules = $this->getInferredRules($columnName);
            if (!empty($inferredRules)) {
                $rules = array_merge($rules, $inferredRules);
            }
        }

        return $rules;
    }

    /**
     * Determines if this strategy can be applied to the given column.
     *
     * @param array $columnDetails The detailed information about the column.
     * @return bool True if the column is of a floating-point type.
     */
    public function canApply(array $columnDetails): bool
    {
        return in_array($columnDetails['type_name'], ['decimal', 'float', 'double', 'real']);
    }

    /**
     * Infers additional validation rules based on the column name.
     *
     * This method looks for common keywords in the column name to suggest
     * rules like minimum and maximum values.
     *
     * @param string $columnName The name of the column.
     * @return array An array of inferred rules.
     */
    protected function getInferredRules(string $columnName): array
    {
        $rules = [];
        $lowerColumnName = Str::lower($columnName);

        // A. Infer `min:0` for price-related fields.
        if (Str::contains($lowerColumnName, ['price', 'amount', 'total', 'cost', 'salary', 'discount', 'rate'])) {
            $rules[] = 'min:0';
        }

        // B. Infer `min:0` and `max:100` for percentage fields.
        if (Str::contains($lowerColumnName, ['percent'])) {
            $rules[] = 'min:0';
            $rules[] = 'max:100';
        }

        // C. Infer age-related rules for the 'age' column.
        if (Str::contains($lowerColumnName, ['age'])) {
            $rules[] = 'min:0';
            $rules[] = 'max:120';
        }

        return $rules;
    }
}