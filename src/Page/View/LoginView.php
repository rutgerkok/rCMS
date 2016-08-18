<?php

namespace Rcms\Page\View;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
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
     * @var UriInterface The location where the login form should go.
     */
    private $destination;

    /**
     * @var array Variables that were sent in the request body.
     */
    private $postVars;

    /**
     * @var bool True when a "Create account" link must be shown.
     */
    private $showCreateAccountLinks;

    /**
     * Creates a new login view.
     * @param Text $text The text instance.
     * @param Request $request The request instance.
     * @param string $errorMessage Message to display on top of the login form
     * in a red box. Leave blank for no message.
     * @param bool $showCreateAccountLink Set to true when a "Create account"
     * link must be shown.
     */
    public function __construct(Text $text, UriInterface $destination,
            array $postVars, $errorMessage, $showCreateAccountLink) {
        parent::__construct($text);
        $this->destination = $destination;
        $this->postVars = $postVars;
        $this->errorMessage = $errorMessage;
        $this->showCreateAccountLinks = (bool) $showCreateAccountLink;
    }

    public function writeText(StreamInterface $stream) {
        $text = $this->text;
        $errorMessage = $this->errorMessage;

        $formUrl = $this->destination;

        $loginText = $text->t("users.please_log_in");
        if (!empty($errorMessage)) {
            $stream->write(<<<EOT
                <div class="error">
                    <p>$errorMessage</p>
                </div>
EOT
            );
        }
        $stream->write(<<<EOT
            <div id="login">
                <form method="post" action="{$text->e($formUrl)}">
                    <h3>$loginText</h3>
                    <p>
                        <label for="user">{$text->t('users.username_or_email')}:</label> <br />
                        <input type="text" name="user" id="user" autofocus="autofocus" /> <br />
                        <label for="pass">{$text->t('users.password')}:</label> <br />
                        <input type="password" name="pass" id="pass" /> <br />

                        <input type="submit" value="{$text->t('main.log_in')}" class="button primary_button" />

EOT
        );
        // Repost all POSTed variables (GET variables will be part of the URL above)
        foreach ($this->postVars as $key => $value) {
            if ($key != "user" && $key != "pass") {
                $stream->write('<input type="hidden" name="' . $text->e($key) . '" value="' . $text->e($value) . '" />');
            }
        }

        // End form and return it
        $stream->write(<<<EOT
                    </p>
                </form>
EOT
        );
        if ($this->showCreateAccountLinks) {
            $stream->write(<<<HTML
                <p>
                    <a class="arrow" href="{$text->url("create_account")}">
                        {$text->t("main.create_account")}
                    </a>
                </p>
HTML
        );
        }
        $stream->write("</div>");
    }

}
