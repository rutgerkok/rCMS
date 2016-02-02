<?php

namespace Rcms\Core;

use BadMethodCallException;
use DateTime;
use Exception;
use Psr\Http\Message\UriInterface;
use RuntimeException;

/**
 * Translations, error messages and success messages.
 */
class Text {

    const DEBUG_MODE = true;

    /**
     * @var UriInterface URL of the site, like http://www.example.com/ .
     * Trailing slash is required.
     */
    private $siteUrl;
    protected $translations;
    private $translationsDir;
    private $errors;
    private $confirmations;
    private $rewriteUrls;
    /**
     * @var UriInterface URL of the directory where the JavaScripts of the site
     * are stored. Trailing slash is required.
     */
    private $javascriptsUrl;

    /**
     * Creates a new Text instance.
     * @param UriInterface $siteUrl URL of the site, like "http://www.example.com/".
     * Trailing slash must be included.
     * @param string $translationsDir Path to the directory with the
     * translation files for the language of the site.
     * @param UriInterface $javascriptsUrl Url of the directory that contains all
     * scripts.
     */
    public function __construct(UriInterface $siteUrl, $translationsDir, UriInterface $javascriptsUrl) {
        $this->siteUrl = $siteUrl;
        $this->translations = array();
        $this->errors = array();
        $this->confirmations = array();
        $this->rewriteUrls = false;
        $this->javascriptsUrl = $javascriptsUrl;

        $this->setTranslationsDirectory($translationsDir);
    }

    /**
     * Updates the translations directory. Used to switch languages later on,
     * for example when the page has connected to the database.
     * @param string $translationsDir The translations directory, trailing slash
     * must be included.
     */
    public function setTranslationsDirectory($translationsDir) {
        $this->translationsDir = (string) $translationsDir;
    }

    /**
     * Sets whether URL rewriting is enabled. If false, links to index.php will be
     * used in getUrlPage, if true, fancier links will be used.
     * @param boolean $value Whether url rewriting is enabled.
     */
    public function setUrlRewrite($value) {
        $this->rewriteUrls = (boolean) $value;
    }

    // Messages and errors

    /**
     * Adds a new error that will be displayed on the top of the page. These
     * errors are intended to inform users, either of mistakes they made, or of
     * system failures. The errors will not be logged by this method.
     * @param string $error The error to add.
     * @param Link $links ... Vararg to add links to this message.
     */
    public function addError($error) {
        // Support varargs
        $links = func_get_args();
        array_shift($links);

        $this->errors[] = $error . $this->toHtmlLinks($links);
    }

    private function toHtmlLinks($links) {
        $returnValue = "";
        foreach ($links as $link) {
            $returnValue.= ' <a class="arrow" href="' . $this->e($link->getUrl()) . '">' . $this->e($link->getText()) . '</a>';
        }
        return $returnValue;
    }

    /**
     * Logs the given exception. The error is not displayed on the site unless
     * debug mode is activated.
     * @param string $message Context information about what failed, like
     * "error while saving article".
     * @param Exception $e The exception.
     */
    public function logException($message, Exception $e) {
        // Very simple error "handling" for now
        $this->logError("Internal exception: " . $message . " <pre>" . $e . "</pre>");
    }

    /**
     * Logs a new error, along with the current stacktrace and other important
     * information. The error is not displayed on the site, unless debug mode is
     * activated. This method should only be used when something goes really
     * wrong, like a failed database connection.
     * @param string $error The error to add.
     */
    public function logError($error) {
        // No logging system implemented yet :(
        if (self::DEBUG_MODE) {
            $this->addError($error);
        }
    }

    /**
     * Adds a message that will be displayed on the top of the page. Messages
     * should be confirmations, like "Article has been saved".
     * @param string $confirmation The message to add.
     * @param Link $links ... Vararg to add links to this message.
     */
    public function addMessage($confirmation) {
        // Support varargs
        $links = func_get_args();
        array_shift($links);

        $this->confirmations[] = $confirmation . $this->toHtmlLinks($links);
    }



    /**
     * Gets the errors that were posted using {@link #addError(string)}.
     * Changing this array is not allowed.
     * @return string[] The errors. Each line may contain HTML tags.
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Gets the messages that were posted using {@link #addMessage(string)}.
     * Changing this array is not allowed.
     * @return string[] The messages. Each line may contain HTML tags.
     */
    public function getConfirmations() {
        return $this->confirmations;
    }

    // Translations

    /**
     * Loads all translations in the given category.
     * @param string $translationCategory Category of the translations, like
     * "main".
     * @throws BadMethodCallException If no such category exists.
     */
    protected function loadTranslations($translationCategory) {
        $translationsFile = $this->translationsDir . "/translations_" . $translationCategory . ".txt";
        if (!file_exists($translationsFile)) {
            throw new BadMethodCallException("Unknown translation category: " . htmlSpecialChars($translationCategory));
        }

        $fileContents = file($translationsFile);
        foreach ($fileContents as $i => $line) {
            if (empty($line)) {
                continue;
            }
            $translation = explode("=", $line, 2);
            if (count($translation) !== 2) {
                // Invalid line
                $lineNumber = $i + 1;
                throw new RuntimeException("Line {$lineNumber} in file {$translationsFile} is not a valid translation: {$line}");
            }
            if ($translation[0] == "_") {
                // Comment
                continue;
            }
            $this->translations[$translationCategory][$translation[0]] = trim($translation[1]);
        }
    }

    /**
     * Gives the localized message with the given key.
     * @param string $key The translation key, must be lowercase.
     * @return string The translation.
     * @throws BadMethodCallException If no such translation exists.
     */
    public function t($key) {
        $keys = explode(".", $key, 2);

        // Check if file is loaded
        if (!isSet($this->translations[$keys[0]])) { //al geladen
            $this->loadTranslations($keys[0]);
        }

        // Return the translation
        if (isSet($this->translations[$keys[0]][$keys[1]])) {
            return $this->translations[$keys[0]][$keys[1]];
        }

        throw new BadMethodCallException("Unknown translation: " . htmlSpecialChars($key));
    }

    /**
     * Gives the localized message with the given key. The {0} in the message is
     * replaced with the translation of the replacement key.
     * @param string $key The translation key.
     * @param string $replacementKey Key of the translation for the replacement.
     * @param string $lowercase True if translation of the replacement key must
     * be converted to lowercase.
     * @return string The translation.
     * @throws BadMethodCallException If no translataion with the key or
     * replacement key exists.
     */
    public function tReplacedKey($key, $replacementKey, $lowercase = false) {
        if ($lowercase) {
            return str_replace("{0}", strToLower($this->t($replacementKey)), $this->t($key));
        } else {
            return str_replace("{0}", $this->t($replacementKey), $this->t($key));
        }
    }

    /**
     * Gives the localized message with the given key. The {0}, {1}, etc. in the
     * messages will be replaced with the replacements.
     * @param string $key The translation key.
     * @param string... $replacements The replacements in the translation. You
     * may use varargs or pass a string[] array.
     * @return string The translation.
     * @throws BadMethodCallExcepition If the translation key doesn't exist.
     */
    public function tReplaced($key, $replacements) {
        $translated = $this->t($key);

        // Support varargs
        if (!is_array($replacements)) {
            $replacements = array_slice(func_get_args(), 1);
        }

        $i = 0;
        foreach ($replacements as $replacement) {
            $translated = str_replace(
                    '{' . $i . '}', $replacement, $translated);
            $i++;
        }

        return $translated;
    }

    /**
     * Escapes the string for HTML. Single quotes are escaped too.
     *
     * Note: spaces are not escaped. The code `<input value=$text>` is
     * still unsafe, use `<input value="$text">` instead.
     * @param string $string Plain text string.
     * @return string String that can be inserted in HTML>
     */
    public function e($string) {
        return htmlspecialchars($string, ENT_QUOTES);
    }

    /**
     * Creates an URL to the given page.
     * @param string $pageName Name of the page, like "edit_article".
     * @param string|string[]|null $params Parameters of the page, appear in URL
     * as subdirectories. `getUrlPage("foo", ["this", "that"])` -> 
     * `foo/this/that`. You can pass one string, or an array of strings. You can
     * also pass null to skip this parameter.
     * @param array $args Array of key/value pairs that should be used as the
     * query string. `["foo" => "bar"]`  gives `?foo=bar` at the end of the URL.
     * @return UriInterface The url.
     */
    public function getUrlPage($pageName, $params = null, $args = array()) {
        $url = $this->getUrlMain();
        if (!$this->rewriteUrls) {
            $url = $url->withPath($url->getPath() . "index.php/{$pageName}/");
        } else {
            $url = $url->withPath($url->getPath() . $pageName . '/');
        }
        if ($params !== null) {
            if (is_array($params)) {
                $url = $url->withPath($url->getPath() . implode('/', $params));
            } else {
                $url = $url->withPath($url->getPath() . $params);
            }
        }
        if (count($args) > 0) {
            $queryParts = array();
            foreach ($args as $key => $value) {
                $queryParts[] = urlencode($key) . '=' . urlencode($value);
            }
            $url = $url->withQuery(implode('&', $queryParts));
        }
        return $url;
    }

    /**
     * Gets the main site URL, like "http://www.example.com/".
     * @return UriInterface The main site url.
     */
    public function getUrlMain() {
        return $this->siteUrl;
    }

    /**
     * Gets the URL to the specified JavaScript file.
     * @param string $name Name of the script, without the .js.
     * @return UriInterface The url.
     */
    public function getUrlJavascript($name) {
        return $this->javascriptsUrl->withPath(
                $this->javascriptsUrl->getPath() . $name . ".js");
    }

    /**
     * Gets a formatted date/time, ready for presenting to the user.
     * @param DateTime $dateTime The date/time.
     * @return string The formatted date/time.
     */
    public function formatDateTime(DateTime $dateTime) {
        return strFTime($this->t("calendar.format.date_time"), $dateTime->format('U'));
    }

}
