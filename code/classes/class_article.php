<?php

/**
 * Represents a single article. All data is raw HTML, handle with extreme
 * caution (read: htmlspecialchars)
 */
class Article {

    public $id;
    public $title;
    public $created;
    public $last_edited;
    public $intro;
    public $featured_image;
    public $category;
    public $category_id;
    public $author;
    public $author_id;
    public $pinned;
    public $hidden;
    public $body;
    public $show_comments;
    public $on_calendar;

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
            $sql.= "`artikel_verborgen`, `artikel_inhoud`, `artikel_reacties`, `artikel_verwijsdatum` FROM `artikel` ";
            $sql.= "LEFT JOIN `categorie` USING ( `categorie_id` ) ";
            $sql.= "LEFT JOIN `users` ON `user_id` = `gebruiker_id` ";
            $sql.= "WHERE artikel_id = {$this->id} ";
            $result = $oDatabase->query($sql);
            if ($result && $oDatabase->rows($result) >= 1) {
                $data = $oDatabase->fetch($result);
            } else {
                throw new InvalidArgumentException("Article not found");
            }
        }
        // Set all variables
        if (is_array($data) && count($data) >= 10) {
            $this->title = $data[0];
            $this->created = $data[1];
            $this->last_edited = $data[2];
            $this->intro = $data[3];
            $this->featured_image = $data[4];
            $this->category_id = $data[5];
            $this->category = $data[6];
            $this->author_id = (int) $data[7];
            $this->author = $data[8];
            $this->pinned = (boolean) $data[9];
            $this->hidden = (boolean) $data[10];
            if (count($data) >= 14) {
                $this->body = $data[11];
                $this->show_comments = (boolean) $data[12];
                $this->on_calendar = $data[13];
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
            $sql.="(`categorie_id`, ";
            $sql.="`artikel_titel`, `artikel_intro`, `artikel_gepind`, `artikel_verborgen`, `artikel_reacties`, ";
            $sql.="`artikel_inhoud`, `artikel_afbeelding`, `artikel_verwijsdatum`, `gebruiker_id`, `artikel_gemaakt`  ) VALUES ";
            $sql.="('" . ((int) $this->category_id) . "', ";
            $sql.="'" . $oDB->escape_data($this->title) . "', ";
            $sql.="'" . $oDB->escape_data($this->intro) . "', ";
            $sql.="'" . ((boolean) $this->pinned) . "', ";
            $sql.="'" . ((boolean) $this->hidden) . "', ";
            $sql.="'" . ((boolean) $this->show_comments) . "', ";
            $sql.="'" . $oDB->escape_data($this->body) . "', ";
            $sql.="'" . $oDB->escape_data($this->featured_image) . "', ";
            $sql.="'" . $oDB->escape_data($this->on_calendar) . "', ";
            $sql.="'" . ((int) $this->author_id) . "', ";
            $sql.=" NOW() );";
            if ($oDB->query($sql)) {
                // We now have an id
                $this->id = $oDB->inserted_id();

                return true;
            } else {
                return false;
            }
        } else {
            // Update existing article
            $sql = "UPDATE `artikel` SET ";
            $sql.="`artikel_titel` = '" . $oDB->escape_data($this->title) . "', ";
            $sql.="`categorie_id` = '" . ((int) $this->category_id) . "', ";
            $sql.="`artikel_intro` = '" . $oDB->escape_data($this->intro) . "', ";
            $sql.="`artikel_gepind` = '" . ((boolean) $this->pinned) . "', ";
            $sql.="`artikel_verborgen` = '" . ((boolean) $this->hidden) . "', ";
            $sql.="`artikel_reacties` = '" . ((boolean) $this->show_comments) . "', ";
            $sql.="`artikel_inhoud` = '" . $oDB->escape_data($this->body) . "', ";
            $sql.="`artikel_afbeelding` = '" . $oDB->escape_data($this->featured_image) . "', ";
            $sql.="`artikel_verwijsdatum` = '" . $oDB->escape_data($this->on_calendar) . "', ";
            $sql.="`artikel_bewerkt` = NOW() ";
            $sql.=" WHERE `artikel_id` = {$this->id};";
            if ($oDB->query($sql)) {
                return true;
            } else {
                return false;
            }
        }
    }

}

?>