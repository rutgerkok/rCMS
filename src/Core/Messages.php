<?php

namespace Rcms\Core;

use BadMethodCallException;

/**
 * Translations, error messages and success messages.
 */
class Messages {
    
    const DEBUG_MODE = false;

    protected $translations;
    private $translationsDir;
    private $errors;
    private $messages;

    public function __construct() {
        $this->translations = array();
        $this->errors = array();
        $this->messages = array();
    }
    
    /**
     * Updates the translations directory. Must be called prior to calling any
     * of the translation methods.
     * @param string $translationsDir The translations directory, trailing slash
     * must be included.
     */
    public function setTranslationsDirectory($translationsDir) {
        $this->translationsDir = $translationsDir;
    }

    // Messages and errors

    /**
     * Adds a new error that will be displayed on the top of the page. Errors
     * should notify users about mistakes they made, or about technical failures
     * on the website. Expect all messages here to be publicy visible.
     * @param string $error The error to add.
     */
    public function addError($error) {
        $this->errors[] = $error;
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
     * @param string $message The message to add.
     */
    public function addMessage($message) {
        $this->messages[] = $message;
    }

    /**
     * Gets the errors that were posted using {@link #addError(string)}.
     * Changing this array is not allowed.
     * @see #addError(string)
     * @return string[] The errors.
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Gets the messages that were posted using {@link #addMessage(string)}.
     * Changing this array is not allowed.
     * @return string[] The messages.
     */
    public function getMessages() {
        return $this->messages;
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
        foreach ($fileContents as $line) {
            $translation = explode("=", $line, 2);
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
                    '{' . $i . '}', 
                    $replacement,
                    $translated);
            $i++;
        }

        return $translated;
    }

}
