<?php

namespace Rcms\Theme;

use Psr\Http\Message\StreamInterface;

/**
 * Represents a theme: something that displays the page.
 */
abstract class Theme {

    /**
     * Renders the theme to the given stream.
     * @param StreamInterface $stream The stream to render to.
     * @param ThemeElements #elements Elements that can be rendered on the page.
     */
    public abstract function render(StreamInterface $stream, ThemeElements $elements);
}
