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

    private static string $dateFormat = 'Y-m-d H:i:s';
    private static string $timeFormat = 'H:i:s';
    private static string $yearFormat = 'Y';

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
            $rules[] = 'date_format:' . self::$dateFormat;
        } elseif ($typeName === 'time') {
            $rules[] = 'date_format:' . self::$timeFormat;
        } elseif ($typeName === 'year') {
            $rules[] = 'digits:4';
            $rules[] = 'numeric';
            $rules[] = 'between:' . self::$yearFormat; // The valid range for MySQL YEAR type.
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

    public static function setDateFormat(string $format = 'Y-m-d H:i:s'): void
    {
        self::$dateFormat = $format;
    }

    public static function setTimeFormat(string $format = 'H:i:s'): void
    {
        self::$timeFormat = $format;
    }

    public static function setYearFormat(string $start = '1901', string $end='2155'): void
    {
        self::$yearFormat = $start . ',' . $end;
    }
}