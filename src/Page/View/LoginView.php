<?php

namespace Rcms\Page\View;

use Rcms\Core\Request;
use Rcms\Core\Text;

/**
 * View for the login screen.
 */
class LoginView extends View {

    /**
     * @var string The error message on top of the login form, may be empty.
     */
    private $errorMessage;
    
    /**
     * @var Request The request object, for re-emitting all request variables.
     */
    private $request;

    /**
     * Creates a new login view.
     * @param Text $text The text instance.
     * @param Request $request The request instance.
     * @param string $errorMessage Message to display on top of the login form
     * in a red box.. Leave blank for no message.
     * This will be displayed on top of the page. Set this to
     * Authentication::LOGGED_OUT_RANK to display no message on top of the
     * page.
     */
    public function __construct(Text $text, Request $request, $errorMessage) {
        parent::__construct($text);
        $this->request = $request;
        $this->errorMessage = $errorMessage;
    }

    public function getText() {
        $text = $this->text;
        $errorMessage = $this->errorMessage;
        
        $formUrl = $this->request->toPsr()->getUri();

        $loginText = $text->t("users.please_log_in");
        $returnValue = "";
        if (!empty($errorMessage)) {
            $returnValue.= <<<EOT
                <div class="error">
                    <p>$errorMessage</p>
                </div>
EOT;
        }
        $returnValue.= <<<EOT
            <div id="login">
                <form method="post" action="{$text->e($formUrl)}">
                    <h3>$loginText</h3>
                    <p>
                        <label for="user">{$text->t('users.username_or_email')}:</label> <br />
                        <input type="text" name="user" id="user" autofocus="autofocus" /> <br />
                        <label for="pass">{$text->t('users.password')}:</label> <br />
                        <input type="password" name="pass" id="pass" /> <br />

                        <input type="submit" value="{$text->t('main.log_in')}" class="button primary_button" />

EOT;
        // Repost all POSTed variables (GET variables will be part of the URL above)
        $postedVars = (array) $this->request->toPsr()->getParsedBody();
        foreach ($postedVars as $key => $value) {
            if ($key != "user" && $key != "pass") {
                $returnValue.= '<input type="hidden" name="' . htmlSpecialChars($key) . '" value="' . htmlSpecialChars($value) . '" />';
            }
        }

        // End form and return it
        $returnValue.= <<<EOT
                    </p>
                </form>
            </div>
EOT;
        return $returnValue;
    }

}
