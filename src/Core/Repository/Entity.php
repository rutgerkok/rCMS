<?php

namespace Rcms\Core\Repository;

use BadMethodCallException;

/**
 * Represents an abstract entity in a database.
 */
abstract class Entity {

    /** Internal const. Value if markConstructed has been called. */
    const INTERNAL_STATE_FINAL = 0;

    /** Internal const. Value after first setField call. */
    const INTERNAL_STATE_SETTING = 1;

    /** Internal const. Value before setField and markConstructed are called. */
    const INTERNAL_STATE_DEFAULT = 2;

    private $state = self::INTERNAL_STATE_DEFAULT;

    /**
     * Called after all fields have been set using the setField method.
     */
    public final function markConstructed() {
        $this->state = self::INTERNAL_STATE_FINAL;
    }

    /**
     * Sets the value of a field on this object.
     * @param Field $field The field to set.
     * @param string|null $value Raw value of the field.
     * @throws BadMethodCallException If the object is already constructed.
     */
    public function setField(Field $field, $value) {
        if ($this->state == self::INTERNAL_STATE_FINAL && $field->getType() != Field::TYPE_PRIMARY_KEY) {
            // Primary key can be changed for insert queries to set the id to
            // something else than 0
            throw new BadMethodCallException("Already constructed");
        }

        $this->state = self::INTERNAL_STATE_SETTING;
        $fieldName = $field->getName();
        $this->$fieldName = $field->deserializeValue($value);
    }

    /**
     * Gets the serialized value of the given field.
     * @param Field $field The field.
     * @return string Serialized value of the field.
     */
    public function getField(Field $field) {
        if (!$this->isConstructed()) {
            throw new \BadMethodCallException("Not yet constructed");
        }
        $fieldName = $field->getName();
        return $field->serializeValue($this->$fieldName);
    }

    /**
     * Gets whether this object is constructed. If setField has been called
     * without markConstructed after that, this object is not yet constructed.
     * @return boolean Whether this object is constructed.
     */
    public final function isConstructed() {
        return $this->state != self::INTERNAL_STATE_SETTING;
    }

}
