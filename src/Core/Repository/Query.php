<?php

namespace Rcms\Core\Repository;

use BadMethodCallException;
use PDO;
use PDOException;
use PDOStatement;
use Rcms\Core\Database;
use Rcms\Core\Exception\NotFoundException;

/**
 * Description of Query
 */
class Query {

    /**
     * @var Database The database instance.
     */
    private $database;

    /**
     * @var Repository The repository.
     */
    private $repository;

    /**
     * @var string Raw where clausule.
     */
    private $whereRaw;

    /**
     * @var string[] Parameters for raw where clausule.
     */
    private $params;

    /**
     * @var Field[] Fiels to get, indexed by the database name.
     */
    private $fieldMap;

    /**
     * @var int Limits the amount of results to this number. 0 means no limit.
     */
    private $limit = 0;

    /**
     * @var int Offset of the results.
     */
    private $offset = 0;

    /**
     * @var string[] Things to order the results by.
     */
    private $orderByStrings = array();

    function __construct(Database $database, Repository $repository, $whereRaw,
            array $params) {
        $this->database = $database;
        $this->repository = $repository;
        $this->whereRaw = $whereRaw;
        $this->params = $params;
        $this->fieldMap = $this->toFieldMap($repository->getStandardFields());
    }

    /**
     * Deletes all rows matching the query.
     * @return boolean True on success, false if nothing was deleted.
     * @throws PDOException If the query is invalid or the connection is lost.
     */
    public function delete() {
        $sql = "DELETE FROM `" . $this->repository->getTableName() . "`";
        if (!empty($this->whereRaw)) {
            $sql.= " WHERE " . $this->whereRaw;
        }
        $statement = $this->database->prepareQuery($sql);
        return $statement->execute($this->params);
    }

    /**
     * Deletes exactly one row. If no rows or multiple rows where deleted, this
     * method throws an exception. Currenlty the rows will stay deleted, the
     * method doesn't revert the deletion yet in case of multiple deletions.
     * @throws NotFoundException If nothing was deleted.
     * @throws PDOException If two ore more row where deleted (indicates a bug
     * in your code, as well as data loss).
     */
    public function deleteOneOrFail() {
        $sql = "DELETE FROM `" . $this->repository->getTableName() . "`";
        if (!empty($this->whereRaw)) {
            $sql.= " WHERE " . $this->whereRaw;
        }
        $statement = $this->database->prepareQuery($sql);
        $statement->execute($this->params);
        $rowCount = $statement->rowCount();
        if ($rowCount == 0) {
            throw new NotFoundException();
        }
        if ($rowCount > 1) {
            throw new PDOException("Deleted " . $statement->rowCount() . " rows instead of one");
        }
    }

    /**
     * Adds fields that should also be selected, on top of the standard set.
     * Varargs method.
     * @param Field ...$field The fields.
     * @return Query This object.
     */
    public function withExtraFields(Field $field) {
        $this->fieldMap += $this->toFieldMap(func_get_args());
        return $this;
    }

    /**
     * Gets the results from the database.
     * @return array The result as an array of objects.
     * @throws PDOException If the query is invalid or the connection is lost.
     */
    public function select() {
        $sql = $this->getSelectQuery();

        $statement = $this->database->prepareQuery($sql);
        $statement->execute($this->params);

        return $this->toObjects($statement, $this->fieldMap);
    }

    /**
     * Selects a single entry.
     * @return object The entry.
     * @throws NotFoundException If zero or more than one results where found.
     */
    public function selectOneOrFail() {
        $array = $this->select();
        if (count($array) != 1) {
            throw new NotFoundException();
        }
        return $array[0];
    }

    /**
     * Counts the amount of rows by executing a SELECT COUNT(*) query. The WHERE
     * conditions are taken into account, but the limit conditions aren't.
     * @return int The amount of rows.
     */
    public function count() {
        $sql = $this->getCountQuery();

        $statement = $this->database->prepareQuery($sql);
        $statement->execute($this->params);
        $result = (int) $statement->fetchColumn();
        $statement->closeCursor();
        return $result;
    }

    /**
     * Sets a limit for the number of returned results. Once it has been set, it
     * is not possible to remove the limit. Ignored by DELETE queries.
     * @param int $limit The limit.
     * @throws BadMethodCallException If $limit is not a positive integer.
     * @return Query This object.
     */
    public function limit($limit) {
        $limit = (int) $limit;
        if ($limit <= 0) {
            throw new BadMethodCallException("Limit must be higher than zero, was " . $limit);
        }
        $this->limit = $limit;
        return $this;
    }

    /**
     * Sets an offset for the results. Ignored by DELETE queries.
     * @param int $offset The offset.
     * @throws BadMethodCallException If $offset is not an integer or smaller
     * than zero.
     * @return Query This object.
     */
    public function offset($offset) {
        if (!is_int($offset) || $offset < 0) {
            throw new BadMethodCallException("Offset must be a positive or zero");
        }
        $this->offset = $offset;
        return $this;
    }

    /**
     * Orders the results in ascending order based on the value of this field.
     * @param Field $field The field to sort on.
     * @throws BadMethodCallException If {@link Field#needsJoin()} returns true.
     * @return Query This object.
     */
    public function orderAscending(Field $field) {
        if ($field->needsJoin()) {
            throw new BadMethodCallException("Field " . $field->getName() . " needs a join, so cannot be used for sorting");
        }
        $this->orderByStrings[] = '`' . $field->getNameInDatabase() . '`';
        return $this;
    }

    /**
     * Orders the results in descending order based on the value of this field.
     * @param Field $field The field to sort on.
     * @throws BadMethodCallException If {@link Field#needsJoin()} returns true.
     * @return Query This object.
     */
    public function orderDescending(Field $field) {
        if ($field->needsJoin()) {
            throw new BadMethodCallException("Field " . $field->getName() . " needs a join, so cannot be used for sorting");
        }
        $this->orderByStrings[] = '`' . $field->getNameInDatabase() . "` DESC";
        return $this;
    }

    // Private methods below

    /**
     * Gets a map where the name of each field in the database is mapped to the
     * Field instance. (`fieldName => Field`)
     * @param Field[] $fields The fields to map.
     * @return Field[] The fields, mapped to the name of each field in the
     * database.
     */
    private function toFieldMap(array $fields) {
        $map = array();
        foreach ($fields as $field) {
            $map[$field->getNameInDatabase()] = $field;
        }
        return $map;
    }

    private function getSelectQuery() {
        $sql = "SELECT " . $this->getFieldNames() . " FROM `" . $this->repository->getTableName() . "`";
        $sql.= $this->getJoinString();
        if (!empty($this->whereRaw)) {
            $sql.= " WHERE " . $this->whereRaw;
        }
        $sql.= $this->getOrderByString();
        if ($this->limit > 0) {
            $sql.= " LIMIT " . $this->offset . ", " . $this->limit;
        }
        return $sql;
    }

    private function getCountQuery() {
        $sql = "SELECT COUNT(*) FROM " . $this->repository->getTableName();
        if (!empty($this->whereRaw)) {
            $sql.= " WHERE " . $this->whereRaw;
        }
        return $sql;
    }

    private function getFieldNames() {
        $fieldNames = "";
        foreach ($this->fieldMap as $field) {
            $fieldNames.= "`" . $field->getNameInDatabase() . "`, ";
        }
        return subStr($fieldNames, 0, -2);
    }

    private function getJoinString() {
        $joinedTables = array();
        $joins = "";
        foreach ($this->fieldMap as $field) {
            if (!$field->needsJoin()) {
                continue;
            }

            $joinFieldName = $field->getJoinField()->getNameInDatabase();
            $joinFieldNameOther = $field->getJoinFieldOtherTable()->getNameInDatabase();
            $joinTableName = $field->getJoinTableName();

            // Prevent duplicate joins
            if (array_search($joinTableName, $joinedTables) !== false) {
                // Already joining on that table
                continue;
            }
            $joinedTables[] = $joinTableName;

            $joins.= " LEFT JOIN `" . $joinTableName . "`";
            if ($joinFieldName === $joinFieldNameOther) {
                $joins.= " USING (`" . $joinFieldName . "`)";
            } else {
                $joins.= " ON `" . $joinFieldName . "` = `" . $joinFieldNameOther . "`";
            }
        }
        return $joins;
    }

    private function getOrderByString() {
        if (empty($this->orderByStrings)) {
            return "";
        }
        return " ORDER BY " . join(", ", $this->orderByStrings);
    }

    private function toObjects(PDOStatement $statement, array $fieldMap) {
        $objects = array();

        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $object = $this->repository->createEmptyObject();

            foreach ($row as $fieldName => $value) {
                if ($value === null) {
                    continue;
                }
                $field = $fieldMap[$fieldName];
                $object->setField($field, $value);
            }

            $object->markConstructed();
            $objects[] = $object;
        }

        return $objects;
    }

}
