<?php

namespace Rcms\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use Rcms\Core\RequestToken;
use Rcms\Core\UserSession;
use Rcms\Core\Website;

/**
 * Piece of middleware that scans the cookies, session and POST variables for an
 * active login. If found, the user is added to the request attributes.
 */
class Authenticator {
    

    
    /**
     * @var UserSession The user's session.
     */
    private $userSession;
    
    public function __construct(Website $website) {
        $this->userSession = new UserSession($website);
    }

    public function __invoke(ServerRequestInterface $request,
        ResponseInterface $response, callable $next = null) {

        $response = $this->withStartedSession($response);
        $user = null;
        $post = $request->getParsedBody();

        if (isSet($post["user"]) && isSet($post["pass"]) && isSet($post[RequestToken::FIELD_NAME])) {
            // POST login
            $login = (string) $post["user"];
            $pass = (string) $post["pass"];
            $sessionToken = RequestToken::fromSession();
            $formToken = RequestToken::fromString($post[RequestToken::FIELD_NAME]);
            if ($sessionToken->matches($formToken)) {
                // Only login if request came from this site
                $user = $this->userSession->doPasswordLogin($login, $pass);
            }
            if ($user) { // Login successful, set login cookie
                $response = $this->userSession->addRememberMeCookie($user, $response);
            }
        }
        if ($user === null) {
            // SESSION login
            $user = $this->userSession->getUserFromSession($request);
        }
        if ($user === null) {
            // COOKIE login
            $user = $this->userSession->doCookieLogin($request);
            if ($user) { // Refresh cookie
                $response = $this->userSession->addRememberMeCookie($user, $response);
            } else {
                $response = $this->userSession->deleteRememberMeCookie($response);
            }
        }

        $request = $request->withAttribute("user", $user);
        $response = $this->userSession->withUserInSession($response, $user);
        return $next? $next($request, $response) : $response;
    }
    
    private function withStartedSession(ResponseInterface $response) {
        // Start the session with manual cookie injection
        session_start([
            'use_cookies' => false,
            'use_only_cookies' => true
        ]);

        $cookieName = session_name();
        $cookieValue = session_id();
        $path = ini_get('session.cookie_path');
        $cookieInstruction = "$cookieName=$cookieValue; path=$path";
        return $response->withAddedHeader("Set-Cookie", $cookieInstruction);
    }

    
 

}