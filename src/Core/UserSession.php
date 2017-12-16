<?php

namespace Rcms\Core;

use DateInterval;
use DateTimeImmutable;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Manages user sessions: cookies and session variables.
 */
class UserSession {
    
    const AUTH_COOKIE = "remember_me";
    
    private $website;

    public function __construct(Website $website) {
        $this->website = $website;
    }
    
    public function doPasswordLogin($login, $pass) {
        try { 
            $user = $this->website->getUserRepository()->getByNameOrEmail($login);
            if (!$user->verifyPassword($pass)) {
                throw new NotFoundException(); // Invalid password
            }
            if ($user->getStatus() == User::STATUS_BANNED) {
                // Show banned message
                $text = $this->website->getText();
                $text->addError($text->tReplaced("users.status.banned.your_account", $user->getStatusText()));
                return null; // Silently reject login
            } else if (!$user->canLogIn()) {
                throw new NotFoundException(); // Pretend account does not exist
            }

            // Warn for weak passwords
            if ($user->isWeakPassword($pass)) {
                $text = $this->website->getText();
                $text->addError($text->t("users.your_password_is_insecure"), Link::of(
                                $text->getUrlPage("edit_password"), $text->t("users.password.edit")));
            }

            // Login successful
            $this->rehashPasswordIfNeeded($user, $pass);
            $this->updateLoginDate($user);
            $this->website->getUserRepository()->save($user);
            return $user;
        } catch (NotFoundException $e) {
            // Invalid username/email or password
            $text = $this->website->getText();
            $text->addError($text->t("errors.invalid_login_credentials"));
            return null; 
        }
    }
    
    /**
     * Restores the session from a provided cookie value.
     * @param ServerRequestInterface $request Request that stores the cookie
     * @return User|null The user, or null if the cookie contains no valid session.
     */
    public function doCookieLogin(ServerRequestInterface $request) {
        $cookies = $request->getCookieParams();
        if (!isSet($cookies[self::AUTH_COOKIE])) {
            return null; // No cookie
        }
        $cookie = (string) $cookies[self::AUTH_COOKIE];
        
        $cookieSplit = explode('||', $cookie);
        if (count($cookieSplit) !== 3) {
            return null; // Invalid cookie
        }

        try {
            $user = $this->website->getUserRepository()->getById($cookieSplit[0]);
            if (!$user->canLogIn()) {
                return null; // Banned or deleted
            }

            $storedHash = $cookieSplit[1];
            $expires = $cookieSplit[2];
            $verificationString = $expires . "||" . $user->getPasswordHashed();
            if (!password_verify($verificationString, $storedHash)) {
                return null; // Probably changed password
            }

            // Login successful
            $this->updateLoginDate($user);
            $this->website->getUserRepository()->save($user);
            return $user;
        } catch (NotFoundException $e) {
            return null; // User with id not found
        }
    }
    
    /**
     * Gets the user stored in the current session.
     * @param ServerRequestInterface $userId The id.
     * @return User|null The user, or null if no user exists with that id, or
     * if the user has been banned/deleted.
     */
    public function getUserFromSession(ServerRequestInterface $requesr) {
        if (!isSet($_SESSION["user_id"])) {
            return null;
        }
        $userId = $_SESSION["user_id"];
        try {
            
            $user = $this->website->getUserRepository()->getById($userId);
            if (!$user->canLogIn()) {
                return null;
            }
            return $user;
        } catch (NotFoundException $e) {
            // User has been deleted/banned
            return null;
        }
    }
    
    private function rehashPasswordIfNeeded(User $user, $password) {
        if ($user->passwordNeedsRehash()) {
            $user->setPassword($password);
            // The password gets saved when the login data is set
        }
    }
    
    private function updateLoginDate(User $user) {
        $user->setLastLogin(new DateTimeImmutable());
    }
    
    /**
     * Logs a user out. Cookies and session variables will be cleared.
     * @param ResponseInterface $response The response object, used for deleting
     * cookies.
     * @return ResponseInterface Modified response.
     */
    public function logout(ResponseInterface $response) {
        $response = $this->withUserInSession($response, null);
        return $this->deleteRememberMeCookie($response);
    }

    /**
     * Stores the user in the session.
     * @param ResponseInterface $response For setting up a new session (not used
     * yet, so reserved for future use).
     * @param User $user The user, or null to erase the user from the session.
     */
    public function withUserInSession(ResponseInterface $response, User $user = null) {
        if ($user) {
            $_SESSION["user_id"] = $user->getId();
            $_SESSION["moderator"] = $user->hasRank(Ranks::MODERATOR);
        } else {
            unset($_SESSION["user_id"]);
            unset($_SESSION["moderator"]);
        }
        return $response;
    }
    
    private function getCookiePath() {
        $siteUrl = $this->website->getText()->getUrlMain();
        if (empty($siteUrl->getPath())) {
            return '/';
        }
        return $siteUrl->getPath();
    }
    
    /**
     * Adds a cookie that makes the user signed in for the coming 30 days.
     * @param User $user The user.
     * @param ResponseInterface $response The response, for setting cookies.
     * @return ResponseInterface Response with cookie set.
     */
    public function addRememberMeCookie(User $user, ResponseInterface $response) {
        $path = $this->getCookiePath();
        $expires = (new DateTimeImmutable)->add(new DateInterval('P30D'))->format('r');
        $cookieName = self::AUTH_COOKIE;
        $hash = password_hash($expires . "||" . $user->getPasswordHashed(), PASSWORD_DEFAULT);

        $cookieValue = urlencode($user->getId() . "||" . $hash . "||" . $expires);
        $cookieInstruction = "$cookieName=$cookieValue; expires=$expires; path=$path";
        return $response->withAddedHeader("Set-Cookie", $cookieInstruction);
    }

    /**
     * Deletes the "remember me" cookie. The user will have to log in again when
     * the PHP session expires.
     * @param ResponseInterface $response The response.
     * @return ResponseInterface The response, with an instruction to delete the
     * "remember me" cookie.
     */
    public function deleteRememberMeCookie(ResponseInterface $response) {
        $cookieName = self::AUTH_COOKIE;
        $path = $this->getCookiePath();
        $cookieInstruction = "$cookieName=deleted; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=$path";
        return $response->withAddedHeader("Set-Cookie", $cookieInstruction);
    }

   

}
