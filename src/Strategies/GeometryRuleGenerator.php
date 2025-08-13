<?php

namespace GenerateValidation\Strategies;

use Illuminate\Support\Str;

/**
 * Class GeometryRuleGenerator
 *
 * A validation rule generation strategy for GIS (Geographic Information System)
 * column types. It generates specific `regex` rules to validate spatial data
 * formats like POINT, POLYGON, etc., based on the column's type.
 *
 * @package GenerateValidation\Strategies
 */
class GeometryRuleGenerator extends RuleGeneratorAbstract
{
    /**
     * An array of regex rules for specific geometry types.
     *
     * These regular expressions are used to validate the WKT (Well-Known Text)
     * format of spatial data.
     *
     * @var array
     */
    protected array $geometryRules = [
        'point' => 'regex:/^POINT\s?\(\s?-?\d+(\.\d+)?\s+-?\d+(\.\d+)?\s?\)$/i',
        'linestring' => 'regex:/^LINESTRING\s?\((\s?-?\d+(\.\d+)?\s+-?\d+(\.\d+)?\s?,?)+\)$/i',
        'polygon' => 'regex:/^POLYGON\s?\(\(.+\)\)$/i',
        'multipoint' => 'regex:/^MULTIPOINT\s?\((.+)\)$/i',
        'multilinestring' => 'regex:/^MULTILINESTRING\s?\(\(.+\)\)$/i',
        'multipolygon' => 'regex:/^MULTIPOLYGON\s?\(\(\(.+\)\)\)$/i',
        'geometrycollection' => 'regex:/^GEOMETRYCOLLECTION\s?\((.+)\)$/i',
    ];

    /**
     * Generates a set of validation rules for a geometry column.
     *
     * @param string $tableName The name of the database table.
     * @param string $columnName The name of the column.
     * @param array $columnDetails The detailed information about the column.
     * @param bool $isUpdate This parameter is not used as geometry rules are consistent.
     * @return array An array of validation rules.
     */
    public function generate(string $tableName, string $columnName, array $columnDetails, bool $isUpdate = false): array
    {
        $rules = [];

        // A. Add `required` or `nullable` rule based on the column's database schema.
        if (!$columnDetails['nullable']) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        // B. Determine the specific geometry type from the column's full type string.
        $geometryType = Str::before(Str::upper($columnDetails['type']), '(');

        // C. Apply the specific regex rule for the geometry type.
        // If a specific rule is not found, a generic rule is used.
        if (isset($this->geometryRules[Str::lower($geometryType)])) {
            $rules[] = $this->geometryRules[Str::lower($geometryType)];
        } else {
            $rules[] = 'regex:/^(POINT|LINESTRING|POLYGON|MULTIPOINT|MULTILINESTRING|MULTIPOLYGON|GEOMETRYCOLLECTION)\s?\(.*\)$/i';
        }

        return $rules;
    }

    /**
     * Determines if this strategy can be applied to the given column.
     *
     * @param array $columnDetails The detailed information about the column.
     * @return bool True if the column is of a geometry-related type.
     */
    public function canApply(array $columnDetails): bool
    {
        return in_array($columnDetails['type_name'], [
            'geometry',
            'point',
            'linestring',
            'polygon',
            'multipoint',
            'multilinestring',
            'multipolygon',
            'geometrycollection'
        ]);
    }
}