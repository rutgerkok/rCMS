<?php

namespace Rcms\Core\Repository;

use DateTime;

use Rcms\Core\JsonHelper;

/**
 * A field in the database.
 */
class Field {

    const TYPE_INT = 1;
    const TYPE_STRING = 2;
    const TYPE_DATE = 3;
    const TYPE_BOOLEAN = 4;
    const TYPE_JSON = 5;
    const TYPE_PRIMARY_KEY = 6;
    const TYPE_STRING_LOWERCASE = 7;

    /** @var int Type of the field. */
    private $type;

    /** @var string Name of the field in the PHP class. */
    private $nameInClass;

    /** @var string Name of the field in the database. */
    private $nameInDatabase;

    /** @var string Name of another table to join on. */
    private $joinTableName = null;

    /** @var Field Field in this table to use for joining. */
    private $joinUsing = null;

    /** @var Field Field in the other table to use for joining. */
    private $inOtherTable = null;

    public function __construct($type, $nameInClass, $nameInDatabase) {
        $this->type = $type;
        $this->nameInClass = $nameInClass;
        $this->nameInDatabase = $nameInDatabase;
    }

    /**
     * Specifies that this field is in another table.
     * @param string $tableName Table name to join on.
     * @param Field $using Field to join on.
     * @param Field $inOtherTable If the field in the other table has another
     * name, provide a field here that has the name of the other table.
     */
    public function createLink($tableName, Field $using,
            Field $inOtherTable = null) {
        $this->joinTableName = $tableName;
        $this->joinUsing = $using;
        $this->inOtherTable = $inOtherTable? : $using;
    }

    /**
     * Gets the name of this field in the database.
     * @return string The name of this field.
     */
    public function getNameInDatabase() {
        return $this->nameInDatabase;
    }

    /**
     * Gets the name of this field in the appropriate class.
     * @return string The name of this field.
     */
    public function getName() {
        return $this->nameInClass;
    }

    /**
     * Gets the type of this field, which is an int corresponding to one of the
     * constants in this class.
     * @return int Type of this field.
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Transforms the string received from the database to the appropriate type.
     * @param string $string String from the database. May not be null.
     * @return mixed Type appropriate for this field.
     */
    public function deserializeValue($string) {
        switch ($this->type) {
            case self::TYPE_INT:
            case self::TYPE_PRIMARY_KEY:
                return (int) $string;
            case self::TYPE_STRING:
            case self::TYPE_STRING_LOWERCASE:
                return $string;
            case self::TYPE_DATE:
                if ($string === "0000-00-00 00:00:00") {
                    // Legacy "soft" nulls in database
                    return null;
                }
                return DateTime::createFromFormat("Y-m-d H:i:s", $string);
            case self::TYPE_BOOLEAN:
                return (boolean) $string;
            case self::TYPE_JSON:
                return JsonHelper::stringToArray($string);
        }
    }

    /**
     * Transforms the object back to a string that can be inserted into the
     * database.
     *
     * Note that the returned string is not escaped using something like
     * mysql_real_escape_string, this is not needed for parameterized queries.
     *
     * @param mixed $value A PHP object.
     * @return string For in the database.
     */
    public function serializeValue($value) {
        if ($value === null) {
            return null;
        }
        switch ($this->type) {
            case self::TYPE_INT:
            case self::TYPE_PRIMARY_KEY:
                return (int) $value;
            case self::TYPE_JSON:
                return JsonHelper::arrayToString($value);
            case self::TYPE_DATE:
                return $value->format("Y-m-d H:i:s");
            case self::TYPE_STRING_LOWERCASE:
                return strToLower($value);
            default:
                return (string) $value;
        }
    }

    /**
     * Gets whether a SQL join is needed to retrieve this field.
     * @return boolean True if a join is needed, false otherwise.
     */
    public function needsJoin() {
        return $this->joinTableName !== null;
    }

    /**
     * Gets the name of the table this field needs to join on.
     * @return string|null Name of the table, or null if this field doesn't join.
     */
    public function getJoinTableName() {
        return $this->joinTableName;
    }

    /**
     * Gets the field used to perform the join.
     * @return Field|null The field, or null if no join is needed.
     */
    public function getJoinField() {
        return $this->joinUsing;
    }

    /**
     * Gets the field used in the other table to perform the join.
     * @return Field|null The field, or null if no join is needed.
     */
    public function getJoinFieldOtherTable() {
        return $this->inOtherTable;
    }

    /**
     * Same as getNameInDatabae(). Useful for query building:
     * 
     * @return string The name in the database.
     */
    public function __toString() {
        return $this->nameInDatabase;
    }

}
