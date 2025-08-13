<?php

namespace GenerateValidation\Strategies;

use Illuminate\Support\Str;

/**
 * Class BlobRuleGenerator
 *
 * A validation rule generation strategy for BLOB and binary column types.
 * This class handles file-specific validation rules like `file`, `image`, `mimes`, and `max`.
 * It also dynamically sets the `required` or `nullable` rule based on the context (store/update).
 *
 * @package GenerateValidation\Strategies
 */
class BlobRuleGenerator extends RuleGeneratorAbstract
{
    /**
     * The maximum file size in kilobytes (default is 0, which means dynamic).
     *
     * @var int
     */
    private static int $maxSize = 0;

    /**
     * An array of allowed MIME types.
     *
     * @var array
     */
    private static array $mimes = ['jpeg', 'png', 'jpg', 'gif', 'svg'];
    
    /**
     * An array of column names that are typically used for images.
     *
     * @var array
     */
    protected array $imageColumnTypes = ['image', 'avatar', 'photo', 'picture'];

    /**
     * Generates a set of validation rules for a given column.
     *
     * @param string $tableName The name of the database table.
     * @param string $columnName The name of the column.
     * @param array $columnDetails The detailed information about the column.
     * @param bool $isUpdate Flag to determine if the context is an update operation.
     * @return array An array of validation rules.
     */
    public function generate(string $tableName, string $columnName, array $columnDetails, bool $isUpdate = false): array
    {
        $rules = [];
        
        // A. Set `required` or `nullable` rule based on context and database schema.
        // During an update, files can be optional unless the database column is not nullable.
        // The rule will be 'nullable' if it's an update, unless the column is not nullable in the database.
        // Otherwise, the default database `nullable` property is used.
        if ($isUpdate) {
            $rules[] = 'nullable';
        } elseif (!$isUpdate && !$columnDetails['nullable']) {
            $rules[] = 'required';
        } elseif ($columnDetails['nullable']) {
            $rules[] = 'nullable';
        }
        
        // B. Add file-type specific rules.
        // Checks if the column name suggests it's an image.
        if($this->hasTypeOfImage($columnDetails)){
            $rules[] = 'image';
            self::$maxSize = 2048; // Sets a default max size for images.
        } else {
            $rules[] = 'file';
        }

        // C. Add MIME type validation.
        $rules[] = 'mimes:' . implode(',', self::$mimes);

        // D. Add maximum file size validation.
        // Uses a custom size if set, otherwise determines size from column type.
        if(self::$maxSize > 0) {
            $rules[] = 'max:' . self::$maxSize;
        } else {
            $maxSize = $this->getMaxSize($columnDetails['type_name']);
            if ($maxSize) {
                $rules[] = 'max:' . floor($maxSize / 1024); // Convert bytes to kilobytes.
            }
        }

        return $rules;
    }

    /**
     * Sets a custom maximum file size for all BLOB/file columns.
     *
     * @param int $size The maximum file size in kilobytes.
     * @return self
     */
    public static function setMaxSize(int $size)
    {
        self::$maxSize = $size;
        return new self();
    }

    /**
     * Sets or merges the allowed MIME types for file validation.
     *
     * @param array $mimes An array of MIME types.
     * @param string $action 'replace' to overwrite, anything else to merge.
     * @return self
     */
    public static function setMimes(array $mimes, string $action = 'replace')
    {
        if ($action === 'replace') {
            self::$mimes = $mimes;
        } else {
            self::$mimes = array_merge(self::$mimes, $mimes);
        }
        return new self();
    }

    /**
     * Determines if this strategy can be applied to the given column.
     *
     * @param array $columnDetails
     * @return bool
     */
    public function canApply(array $columnDetails): bool
    {
        return (in_array($columnDetails['type_name'], ['binary', 'varbinary', 'blob', 'tinyblob', 'mediumblob', 'longblob'])) || $this->hasTypeOfImage($columnDetails);
    }
    
    /**
     * Checks if a column name suggests it holds an image.
     *
     * @param array $columnDetails
     * @return bool
     */
    protected function hasTypeOfImage(array $columnDetails): bool
    {
        $columnName = $columnDetails['name'];
        return Str::contains($columnName, $this->imageColumnTypes);
    }

    /**
     * Gets the maximum size of a BLOB type in bytes.
     *
     * @param string $typeName The BLOB type name (e.g., 'mediumblob').
     * @return int|null The maximum size in bytes, or null if not a recognized type.
     */
    protected function getMaxSize(string $typeName): ?int
    {
        switch ($typeName) {
            case 'tinyblob':
                return 255;
            case 'blob':
            case 'binary':
            case 'varbinary':
                return 65535;
            case 'mediumblob':
                return 16777215;
            case 'longblob':
                return 4294967295;
            default:
                return null;
        }
    }
}