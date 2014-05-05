<?php

/**
 * Represents a single article. All data is raw HTML, handle with extreme
 * caution (read: htmlSpecialChars)
 */
class Article {

    public $id;
    public $title;
    public $created;
    public $lastEdited;
    public $intro;
    public $featuredImage;
    public $category;
    public $categoryId;
    public $author;
    public $authorId;
    public $pinned;
    public $hidden;
    public $body;
    public $showComments;
    /** @var DateTime|null Date for calendar. */
    public $onCalendar;

    /**
     * Creates a new article object.
     * @param int $id The id of the article.
     * @param Database|array $data The data of the article, or the database to
     * fetch it from.
     * @throws InvalidArgumentException If no article with that id exists.
     */
    public function __construct($id, $data) {
        $id = (int) $id;
        $this->id = $id;
        if ($data instanceof Database) {
            // Fetch from database
            $oDatabase = $data;
            $sql = "SELECT `artikel_titel`, `artikel_gemaakt`, `artikel_bewerkt`, ";
            $sql.= "`artikel_intro`, `artikel_afbeelding`, ";
            $sql.= "`categorie_id`, `categorie_naam`, `user_id`, `user_display_name`, `artikel_gepind`, ";
            $sql.= "`artikel_verborgen`, `artikel_verwijsdatum`, `artikel_inhoud`, `artikel_reacties` FROM `artikel` ";
            $sql.= "LEFT JOIN `categorie` USING ( `categorie_id` ) ";
            $sql.= "LEFT JOIN `users` ON `user_id` = `gebruiker_id` ";
            $sql.= "WHERE artikel_id = {$this->id} ";
            $result = $oDatabase->query($sql);
            if ($result && $oDatabase->rows($result) >= 1) {
                $data = $oDatabase->fetchNumeric($result);
            } else {
                throw new InvalidArgumentException("Article not found");
            }
        }
        // Set all variables
        if (is_array($data) && count($data) >= 10) {
            $this->title = $data[0];
            $this->created = $data[1];
            $this->lastEdited = $data[2];
            $this->intro = $data[3];
            $this->featuredImage = $data[4];
            $this->categoryId = $data[5];
            $this->category = $data[6];
            $this->authorId = (int) $data[7];
            $this->author = $data[8];
            $this->pinned = (boolean) $data[9];
            $this->hidden = (boolean) $data[10];
            if (count($data) >= 12) {
                 $this->onCalendar = Database::toDateTime($data[11]);
            }
            if (count($data) >= 14) {
                $this->body = $data[12];
                $this->showComments = (boolean) $data[13];
            }
        }
    }

    /**
     * Saves this article. Make sure that the data is correct, otherwise the
     * data will just be casted/escaped which may have unintended side effects.
     * @param Database $oDB The database to save to.
     * @return boolean Whether the data was successfully saved.
     */
    public function save(Database $oDB) {
        if ($this->id == 0) {
            // New article
            $sql = "INSERT INTO `artikel` ";
            $sql.= "(`categorie_id`, ";
            $sql.= "`artikel_titel`, `artikel_intro`, `artikel_gepind`, `artikel_verborgen`, `artikel_reacties`, ";
            $sql.= "`artikel_inhoud`, `artikel_afbeelding`, `artikel_verwijsdatum`, `gebruiker_id`, `artikel_gemaakt`  ) VALUES ";
            $sql.= "('" . ((int) $this->categoryId) . "', ";
            $sql.= "'" . $oDB->escapeData($this->title) . "', ";
            $sql.= "'" . $oDB->escapeData($this->intro) . "', ";
            $sql.= "'" . ((boolean) $this->pinned) . "', ";
            $sql.= "'" . ((boolean) $this->hidden) . "', ";
            $sql.= "'" . ((boolean) $this->showComments) . "', ";
            $sql.= "'" . $oDB->escapeData($this->body) . "', ";
            $sql.= "'" . $oDB->escapeData($this->featuredImage) . "', ";
            $sql.= "'" . $oDB->escapeData($oDB->dateTimeToString($this->onCalendar)) . "', ";
            $sql.= "'" . ((int) $this->authorId) . "', ";
            $sql.= " NOW() );";
            if ($oDB->query($sql)) {
                // We now have an id
                $this->id = $oDB->getLastInsertedId();

                return true;
            } else {
                return false;
            }
        } else {
            // Update existing article
            $sql = "UPDATE `artikel` SET ";
            $sql.= "`artikel_titel` = '" . $oDB->escapeData($this->title) . "', ";
            $sql.= "`categorie_id` = '" . ((int) $this->categoryId) . "', ";
            $sql.= "`artikel_intro` = '" . $oDB->escapeData($this->intro) . "', ";
            $sql.= "`artikel_gepind` = '" . ((boolean) $this->pinned) . "', ";
            $sql.= "`artikel_verborgen` = '" . ((boolean) $this->hidden) . "', ";
            $sql.= "`artikel_reacties` = '" . ((boolean) $this->showComments) . "', ";
            $sql.= "`artikel_inhoud` = '" . $oDB->escapeData($this->body) . "', ";
            $sql.= "`artikel_afbeelding` = '" . $oDB->escapeData($this->featuredImage) . "', ";
            $sql.= "`artikel_verwijsdatum` = '" . $oDB->escapeData($oDB->dateTimeToString($this->onCalendar)) . "', ";
            $sql.= "`artikel_bewerkt` = NOW() ";
            $sql.= " WHERE `artikel_id` = " . ((int) $this->id);
            if ($oDB->query($sql)) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Pernamently deletes the article.
     * @param Database $oDatabase The database to delete the article from.
     * @return Whether the article was deleted.
     */
    public function delete(Database $oDatabase) {
        $sql = "DELETE FROM `artikel` ";
        $sql.= "WHERE `artikel_id` = " . ((int) $this->id);
        if ($oDatabase->query($sql)) {
            // Reset article id
            $this->id = 0;
            return true;
        } else {
            return false;
        }
    }

}
