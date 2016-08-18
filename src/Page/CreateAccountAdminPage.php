<?php

namespace Rcms\Page;

use Rcms\Core\Authentication;
use Rcms\Core\Link;
use Rcms\Core\Request;
use Rcms\Core\RequestToken;
use Rcms\Core\Text;
use Rcms\Core\User;
use Rcms\Core\UserRepository;
use Rcms\Core\Validate;
use Rcms\Core\Website;
use Rcms\Page\View\AdminAccountCreationView;
use Rcms\Page\View\EmptyView;

/**
 * Page for creating a new user account.
 */
final class CreateAccountAdminPage extends Page {

    /**
     * @var User The account being created.
     */
    private $newUser;

    /**
     * @var RequestToken Token for protecting the request.
     */
    private $requestToken;

    /**
     * @var string[] All rank translation strings, indexed by rank id.
     */
    private $allRanks = array();

    /**
     * @var bool Set to true when an account is created.
     */
    private $accountCreated = false;

    public function init(Website $website, Request $request) {
        parent::init($website, $request);

        $this->newUser = $this->handleUserRequest($website, $request);

        $this->allRanks = $website->getAuth()->getRanks();

        $this->requestToken = RequestToken::generateNew();
        $this->requestToken->saveToSession();
    }

    private function handleUserRequest(Website $website, Request $request) {
        $username = $request->getRequestString("creating_username", "");
        $displayName = $request->getRequestString("creating_display_name", "");
        $password = $request->getRequestString("creating_password", "");
        $email = $request->getRequestString("creating_email", "");
        $rank = $request->getRequestInt("creating_rank", 0);

        $newUser = User::createNewUser($username, $displayName, $password);
        $newUser->setEmail($email);
        $newUser->setRank($rank);

        $text = $website->getText();
        $userRepo = new UserRepository($website->getDatabase());
        if (Validate::requestToken($request) && $this->validateInput($newUser, $password, $website->getAuth(), $userRepo, $text)) {
            $userRepo->save($newUser);
            $this->accountCreated = true;
            $text->addMessage($text->t("users.create.other.done"),
                    Link::of($text->getUrlPage("create_account_admin"), $text->t("users.create_another")),
                    Link::of($text->getUrlPage("account_management"), $text->t("main.account_management")));
        }

        return $newUser;
    }

    private function validateInput(User $user, $password, Authentication $auth, UserRepository $userRepo, Text $text) {
        $valid = true;

        if (!Validate::username($user->getUsername())) {
            $valid = false;
            $text->addError($text->t("users.the_username") . " " . Validate::getLastError($text));
        }
        if (!Validate::displayName($user->getDisplayName())) {
            $valid = false;
            $text->addError($text->t("users.the_display_name") . " " . Validate::getLastError($text));
        }
        if (!Validate::password($password, $password)) {
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
        if (!$auth->isValidRankForAccounts($user->getRank())) {
            // Invlaid rank
            $valid = false;
            $text->addError($text->t("users.the_rank") . " " . $text->t("errors.is_invalid"));
        }
        return $valid;
    }

    public function getMinimumRank() {
        return Authentication::RANK_ADMIN;
    }

    public function getPageTitle(Text $text) {
        return $text->t("users.create.title");
    }

    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }

    public function getView(Text $text) {
        if ($this->accountCreated) {
            return new EmptyView($text);
        }
        return new AdminAccountCreationView($text, $this->newUser, $this->allRanks, $this->requestToken);
    }

}
