<?php

namespace Rcms\Template;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\Text;
use Zend\Diactoros\Stream;

/**
 * Represents a view. This class produces just an empty page.
 */
abstract class Template {

    /**
     * @var Text $oMessages Used for translations and
     * error/success messages. 
     */
    protected $text;

    public function __construct(Text $text) {
        $this->text = $text;
    }

    /**
     * Renders this view to the provided stream.
     * @param StreamInterface $stream Stream to render to.
     */
    public abstract function writeText(StreamInterface $stream);

    /**
     * @deprecated See writeText
     */
    public function getText() {
        $stream = new Stream("php://temp", "r+");
        $this->writeText($stream);
        $value = (string) $stream;
        $stream->close();
        return $value;
    }

}
