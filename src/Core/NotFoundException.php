<?php

namespace Rcms\Core;

use Exception;

/**
 * Thrown when an element is not found. When a page throws this, a 404 page is
 * rendered instead.
 */
class NotFoundException extends Exception {

    public function __construct() {
        parent::__construct("Not found", 0, null);
    }

}
