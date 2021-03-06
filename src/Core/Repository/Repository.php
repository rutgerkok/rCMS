<?php

namespace Rcms\Core\Repository;

use PDO;
use PDOException;
use InvalidArgumentException;
use Rcms\Core\NotFoundException;
use Rcms\Core\Repository\Entity;

/**
 * An abstract repository. Classes extending this class define the database
 * scheme simply by implementing the abstract methods. The methods on this class
 * will then allow you to select, update, delete and count rows.
 *
 * Some querying examples:
 *
 * ```
 * $repo->where($this->idField, '=', 3)->selectOneOrFail();
 * $repo->where($this->categoryIdField, '=', $category->getId())
 *      ->withAllFiels()->limit(5)->orderDescending($this->dateField)->select();
 * $repo->where($this->categoryIdField, '=', $category->getId())->count();
 * $repo->where($this->idField, '=', 2)->deleteOneOrFail();
 * $repo->saveEntity($user);
 * ```
 */
abstract class Repository {

    /**
     * @var PDO The database.
     */
    protected $pdo;

    protected function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Creates a query that tests the given field for the given value.
     * @param Field $field The field to test.
     * @param string $operator The operator, must be '&lt;', '&gt;' or '='.
     * @param mixed $value The value to test for.
     * @return Query The query.
     * @throws InvalidArgumentException When the operator is invalid.
     */
    protected function where(Field $field, $operator, $value) {
        if ($operator === '=' || $operator === '<' || $operator === '>') {
            return $this->whereRaw('`' . $field->getNameInDatabase() . '` ' . $operator . ' :value', [":value" => $field->serializeValue($value)]);
        }
        throw new InvalidArgumentException("Invalid operator: " . $operator);
    }

    /**
     * Creates a query with no WHERE clausule, selecting all rows.
     * @return Query The query.
     */
    protected function all() {
        return $this->whereRaw("", []);
    }

    /**
     * Gets the standard fields that will be selected. It is possible to specify
     * other fields to select, these are just the defaults.
     *
     * The default implementation just returns {@link #getAllFields()}. If you
     * don't need all that data in each select query consider overriding this
     * method.
     * @return Field[] The standard fields.
     */
    public function getStandardFields() {
        return $this->getAllFields();
    }

    /**
     * Gets all fields in the database table. Used to save articles.
     * @return Field[] All fields.
     */
    public abstract function getAllFields();

    /**
     * Creates a single empty object. This object will be populated with data
     * from the database.
     * @return Entity An empty object.
     */
    public abstract function createEmptyObject();

    /**
     * Gets the primary key of this table.
     * @return Field The primary key.
     */
    public abstract function getPrimaryKey();

    /**
     * Gets the name of the table.
     * @return string The name.
     */
    public abstract function getTableName();

    /**
     * Uses a raw WHERE query. The $sql may be left blank to avoid filtering,
     * but it is better to just call {@link #all()}.
     *
     * Implementation note: all other database methods delegate to this method.
     * Subclasses may override this method to add additonial default parameters,
     * like adding a default result limit, or implementing soft-deletion.
     *
     * Warning: the $sql must be hardcoded or at least properly escaped,
     * otherwise SQL injection becomes possible.
     * @param string $sql The WHERE query, may be left blank.
     * @param string[] $params The parameters for the $sql parameter, key => value.
     * @return Query The query.
     */
    protected function whereRaw($sql, $params) {
        return new Query($this->pdo, $this, $sql, $params);
    }

    /**
     * Saves an entity to the database. If the id is 0 it is inserted and the id
     * is updated. If the id is not 0 the existing entity in the database with
     * that id will be overwritten.
     * @param Entity $entity Entity to save.
     * @param array $fields Fields to save. By default, it uses {@link #getAllFields()}.
     * @throws NotFoundException If the id is larger than 0 and no entity with
     * the given id exists in the database.
     * @throws InvalidArgumentException If `$this->canBeSaved($entity)` returns
     * false.
     * @throws PDOException When a database error occurs.
     */
    protected function saveEntity(Entity $entity, array $fields = []) {
        if (!$this->canBeSaved($entity)) {
            throw new InvalidArgumentException("entity cannot be saved yet");
        }
        if (empty($fields)) {
            $fields = $this->getAllFields();
        }
        $id = (int) $entity->getField($this->getPrimaryKey());
        if ($id > 0) {
            // Update
            $this->updateEntity($entity, $fields);
        } else {
            // Insert
            $this->insertEntity($entity, $fields);
        }
    }

    /**
     * Gets whether all conditions are met to safely place this object in the
     * database. When this method returns false, required fields are not yet
     * filled in, or fields have invalid contents.
     *
     * Subclasses should override this method to do validation.
     * @param Entity $entity The entity to check.
     * @return boolean True if the object can safely be persisted, false
     * otherwise.
     */
    protected function canBeSaved(Entity $entity) {
        return $entity->isConstructed();
    }

    /**
     * Updates a single entity in the database. Does nothing if the entity does
     * not exist yet in the database.
     * @param Entity $entity Entity to update.
     * @param Field[] $fields Fiels to update.
     */
    private function updateEntity(Entity $entity, array $fields) {
        $sql = "UPDATE `{$this->getTableName()}` SET ";
        $primaryKey = $this->getPrimaryKey();

        $i = 0;
        $fieldInstructions = "";
        $params = [];
        foreach ($fields as $field) {
            if ($field->needsJoin() || $field == $primaryKey) {
                continue;
            }
            $fieldInstructions.= "`{$field->getNameInDatabase()}` = :value$i, ";
            $params[":value$i"] = $entity->getField($field);
            $i++;
        }
        $sql.= subStr($fieldInstructions, 0, -2);

        $sql.= " WHERE `{$primaryKey->getNameInDatabase()}` = :primaryKey";
        $params[":primaryKey"] = $entity->getField($primaryKey);

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
    }

    /**
     * Inserts a new entity into the database.
     * @param Entity $entity Entity to insert.
     * @param Field[] $fields Fields to insert of that entity.
     */
    private function insertEntity(Entity $entity, array $fields) {
        $primaryKey = $this->getPrimaryKey();

        // Remove fields that use a join
        $fields = array_filter($fields, function ($field) use ($primaryKey) {
            return !$field->needsJoin() && $field != $primaryKey;
        });

        $fieldNames = [];
        $fieldMarkers = [];
        $fieldValues = [];
        foreach ($fields as $field) {
            $fieldNames[] = "`{$field->getNameInDatabase()}`";
            $fieldMarkers[] = '?';
            $fieldValues[] = $entity->getField($field);
        }

        $sql = "INSERT INTO `{$this->getTableName()}` (" . join(", ", $fieldNames) . ") ";
        $sql.= "VALUES(" . join(", ", $fieldMarkers) . ")";

        $this->pdo->prepare($sql)->execute($fieldValues);

        $id = $this->pdo->lastInsertId();
        $entity->setField($primaryKey, $id);
    }

}
