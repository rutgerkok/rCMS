<?php

namespace Rcms\Extend\Theme;

// Protect against calling this script directly
if (!defined("WEBSITE")) {
    die();
}

use Psr\Http\Message\StreamInterface;
use Rcms\Page\Page;
use Rcms\Theme\Theme;
use Rcms\Theme\ThemeElements;

class TempTheme extends Theme {
    
     public function render(StreamInterface $stream, ThemeElements $elements) {
        $stream->write('
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" >

        <link href="' . $elements->getUrlTheme() . 'temp.css" rel="stylesheet" type="text/css" />
        <title>' . $elements->getHeadTitle() . '</title>
    </head>
    <body>
        <div id="container">
            '); $elements->writePageContent($stream); $stream->write('
        </div><!-- id="container" -->
    </body>
</html>
        ');
    }
}

return new TempTheme();
