<?php

namespace Rcms\Page;

use Rcms\Core\Authentication;
use Rcms\Core\Config;
use Rcms\Core\NotFoundException;
use Rcms\Core\Request;
use Rcms\Core\RequestToken;
use Rcms\Core\Text;
use Rcms\Core\User;
use Rcms\Core\UserRepository;
use Rcms\Core\Validate;
use Rcms\Core\Website;
use Rcms\Template\AccountCreationTemplate;
use Rcms\Template\LoginFormTemplate;

/**
 * Page for creating a new user account.
 */
final class CreateAccountPage extends Page {

    /**
     * @var User The account being created.
     */
    private $newUser;

    /**
     * @var RequestToken Token for protecting the request.
     */
    private $requestToken;

    /**
     * @var bool Set to true when an account is created.
     */
    private $accountCreated = false;

    public function init(Website $website, Request $request) {
        parent::init($website, $request);

        if (!$website->getConfig()->get(Config::OPTION_USER_ACCOUNT_CREATION)
                || $request->hasRank($website, Authentication::RANK_USER)) {
            // Pretend page doesn't exist when account creation is disabled,
            // or when already logged in
            throw new NotFoundException();
        }

        $this->newUser = $this->handleUserRequest($website, $request);

        $this->requestToken = RequestToken::generateNew();
        $this->requestToken->saveToSession();
    }

    private function handleUserRequest(Website $website, Request $request) {
        $username = $request->getRequestString("creating_username", "");
        $displayName = $request->getRequestString("creating_display_name", "");
        $password1 = $request->getRequestString("creating_password1", "");
        $password2 = $request->getRequestString("creating_password2", "");
        $email = $request->getRequestString("creating_email", "");

        $newUser = User::createNewUser($username, $displayName, $password1);
        $newUser->setEmail($email);

        $text = $website->getText();
        $userRepo = new UserRepository($website->getDatabase());
        if (Validate::requestToken($request) && $this->validateInput($newUser, $password1, $password2, $userRepo, $text)) {
            $userRepo->save($newUser);
            $this->accountCreated = true;
            $text->addMessage($text->t("users.create.done"));
        }

        return $newUser;
    }

    private function validateInput(User $user, $password1, $password2, UserRepository $userRepo, Text $text) {
        $valid = true;

        if (!Validate::username($user->getUsername())) {
            $valid = false;
            $text->addError($text->t("users.the_username") . " " . Validate::getLastError($text));
        }
        if (!Validate::displayName($user->getDisplayName())) {
            $valid = false;
            $text->addError($text->t("users.the_display_name") . " " . Validate::getLastError($text));
        }
        if (!Validate::password($password1, $password2)) {
            $valid = false;
            $text->addError($text->t("users.the_password") . " " . Validate::getLastError($text));
        }
        if (!Validate::email($user->getEmail())) {
            $valid = false;
            $text->addError($text->t("users.the_email") . " " . Validate::getLastError($text));
        }
        if ($userRepo->isUsernameInUse($user->getUsername())) {
            // User with that name already exists
            $valid = false;
            $text->addError($text->tReplaced("errors.already_in_use_on_this_site", $text->t("users.the_username")));
        }
        if (!empty($user->getEmail()) && $userRepo->isEmailInUse($user->getEmail())) {
            // User with that email already exists
            $valid = false;
            $text->addError($text->tReplaced("errors.already_in_use_on_this_site", $text->t("users.the_email")));
        }
        return $valid;
    }

    public function getMinimumRank() {
        return Authentication::RANK_LOGGED_OUT;
    }

    public function getPageTitle(Text $text) {
        return $text->t("users.create.title");
    }

    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }

    public function getTemplate(Text $text) {
        if ($this->accountCreated) {
            return new LoginFormTemplate($text, $text->getUrlPage("login"), [], "", false);
        }
        return new AccountCreationTemplate($text, $this->newUser, $this->requestToken);
    }

}
