<?php

namespace Rcms\Template;

use Psr\Http\Message\StreamInterface;

/**
 * A view that outputs nothing. This can be useful when there is already other
 * output in the form of an error (see Text->addError).
 */
class EmptyTemplate extends Template {

    public function writeText(StreamInterface $stream) {
        // Empty!
    }

}
