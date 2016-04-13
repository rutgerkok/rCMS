<?php

namespace Rcms\Page\Renderer;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

use Rcms\Core\Website;


/**
 * Used to render a webpage on the site.
 */
final class ResponseFactory {


    public function __construct(Website $website) {
        
    }
    
    public function __invoke(RequestInterface $request) {
        
    }

}
