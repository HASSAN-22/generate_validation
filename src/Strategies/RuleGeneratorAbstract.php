<?php

namespace GenerateValidation\Strategies;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Class RuleGeneratorAbstract
 *
 * This abstract class provides a foundational structure for all validation rule
 * generation strategies. It defines the core contract for generating rules and
 * checking applicability, and includes helper methods for common database schema
 * inspection tasks like extracting column length, checking for unique indexes,
 * and identifying image-related column names.
 *
 * @package GenerateValidation\Strategies
 */
abstract class RuleGeneratorAbstract
{
    /**
     * An array of common column names that are typically used for images.
     * This list helps to infer image-specific validation rules.
     *
     * @var array
     */
    private static array $imageTypes = [
        'image',
        'photo',
        'avatar',
        'thumbnail',
        'picture',
        'logo',
        'icon',
        'cover',
        'background',
        'banner'
    ];

    /**
     * Generates an array of validation rules for a given column.
     *
     * This is the main method for each strategy, where the specific validation
     * rules are defined based on the column's details.
     *
     * @param string $tableName The name of the database table.
     * @param string $columnName The name of the column.
     * @param array $columnDetails The detailed information about the column.
     * @param bool $isUpdate Flag to determine if the context is an update operation.
     * @return array An array of generated validation rules.
     */
    abstract public function generate(string $tableName, string $columnName, array $columnDetails, bool $isUpdate = false): array;

    /**
     * Checks if this strategy can be applied to the given column type.
     *
     * This method is used by the main command to select the correct
     * rule generator for a specific column based on its type or name.
     *
     * @param array $columnDetails The detailed information about the column.
     * @return bool True if the strategy can be applied, otherwise false.
     */
    abstract public function canApply(array $columnDetails): bool;

    /**
     * Extracts the length constraint from a column type string (e.g., "varchar(255)").
     *
     * This helper method is useful for generating `max` validation rules for
     * string and text column types.
     *
     * @param array $columnDetails The detailed information about the column.
     * @return int|null The length of the column, or null if no length is specified.
     */
    protected function extractLength(array $columnDetails): ?int
    {
        $typeName = Str::lower($columnDetails['type_name']);
        
        if (Str::startsWith($typeName, 'varchar') || Str::startsWith($typeName, 'char')) {
            preg_match('/\((.*?)\)/', $columnDetails['type'], $matches);
            return isset($matches[1]) ? (int) $matches[1] : null;
        }

        return match ($typeName) {
            'tinytext' => 255,
            'text' => 65535,
            'mediumtext' => 16777215,
            'longtext' => 4294967295,
            default => null,
        };
    }

    /**
     * Adds a new image type to the list of recognized image-related column names.
     *
     * This method allows for extending the predefined list of image types.
     *
     * @param string $type The new image type to add.
     * @return void
     */
    protected static function addToImageTypes(string $type): void
    {
        self::$imageTypes[] = $type;
    }

    /**
     * Checks if the column name indicates an image type based on the predefined list.
     *
     * This method helps in automatically applying image-specific validation rules
     * like `image`, `mimes`, and `max`.
     *
     * @param array $columnDetails The detailed information about the column.
     * @return bool True if the column name is in the list of image types, otherwise false.
     */
    protected function hasTypeOfImage(array $columnDetails): bool
    {
        return Str::contains($columnDetails['name'], static::$imageTypes);
    }

    /**
     * Checks if a column has a unique index.
     *
     * This is a utility method used by the `UniqueRuleGenerator` to determine
     * whether to apply a `unique` validation rule.
     *
     * @param string $tableName The name of the database table.
     * @param string $columnName The name of the column.
     * @return bool True if the column is part of a unique index, otherwise false.
     */
    protected function isUnique(string $tableName, string $columnName): bool
    {
        $indexes = Schema::getConnection()->getSchemaBuilder()->getIndexes($tableName);
        
        foreach ($indexes as $index) {
            if ($index['unique'] && in_array($columnName, $index['columns'])) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Checks if a column is a primary key.
     *
     * This method is used to prevent validation rules from being applied to the
     * primary key column, which is often managed by the database.
     *
     * @param string $tableName The name of the database table.
     * @param string $columnName The name of the column.
     * @return bool True if the column is the primary key, otherwise false.
     */
    protected function isPrimaryKey(string $tableName, string $columnName): bool
    {
        $indexes = Schema::getConnection()->getSchemaBuilder()->getIndexes($tableName);
        foreach ($indexes as $index) {
            if ($index['primary'] && in_array($columnName, $index['columns'])) {
                return true;
            }
            
        }
        
        return false;
    }
}