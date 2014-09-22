<?php

namespace Rcms\Core;

use Rcms\Core\Repository\Entity;

/**
 * Represents a single category.
 */
class Category extends Entity {

    /**
     * @var int Id of the category.
     */
    protected $id;

    /**
     * @var string Name of the category.
     */
    protected $name;

    public function __construct($id = null, $name = null) {
        if ($id !== null && $name !== null) {
            $this->id = $id;
            $this->name = $name;
            $this->markConstructed();
        }
    }

    /**
     * Gets the id of this category.
     * @return int The id.
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Gets the name of this category.
     * @return string The name.
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Sets the name of this category.
     * @param string $name The name.
     */
    public function setName($name) {
        $this->name = (string) $name;
    }

    /**
     * Gets whether this is the standard category, in which articles with no
     * category are placed. The standard category cannot be removed.
     * @return boolean True if this is the standard category, false otherwise.
     */
    public function isStandardCategory() {
        return $this->id === 1;
    }

}
