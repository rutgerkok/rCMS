<?php

class ArticleEdit {

    protected $database_object;
    protected $website_object;
    protected $category_object;
    protected $id;
    protected $confirm;
    protected $contents = array();

    //constructor, zet gegevens artikel klaar
    function __construct($oDB, $oCats, Website $oWebsite) {
        $this->database_object = $oDB;
        $this->website_object = $oWebsite;
        $this->category_object = $oCats;

        //ID GOEDZETTEN
        $id = 0;
        if (isset($_REQUEST['id'])) {
            $id = (int) $_REQUEST['id'];
        }
        $this->id = $id;

        //CONFIRM GOEDZETTEN
        $confirm = 0;
        if (isset($_REQUEST['confirm'])) {
            $confirm = (int) $_REQUEST['confirm'];
        }
        $this->confirm = $confirm;
    }

    function delete_article() {
        $oDB = $this->database_object;
        $oWebsite = $this->website_object;
        if ($this->id > 0) {
            if ($this->confirm) { //verwijder categorie
                $sql = 'DELETE FROM `artikel` WHERE artikel_id = ' . $this->id;
                if ($oDB->query($sql) && $oDB->affected_rows() == 1) {
                    $return_value = '<p>' . $oWebsite->t("main.article") . ' ' . $oWebsite->t("editor.is_deleted") . '</p>'; //artikel verwijderd
                    $return_value.= '<p><a href="' . $oWebsite->get_url_main() . '">Home</a></p>';
                    return $return_value;
                } else {
                    $oWebsite->add_error($oWebsite->t("main.article") . ' ' . $oWebsite->t("errors.not_saved")); //artikel kan niet verwijderd worden
                    return '';
                }
            } else { //laat bevestigingsvraag zien
                $sql = "SELECT artikel_titel FROM `artikel` WHERE artikel_id = " . $this->id;
                $result = $oDB->query($sql);

                if ($result) {
                    $result = $oDB->fetch($result);
                    $result = $result[0];

                    $return_value = '<p>Are you sure you want to remove the article \'' . htmlspecialchars($result) . '\'?';
                    $return_value.= ' This action cannot be undone.</p>';
                    $return_value.= '<p><a href="' . $oWebsite->get_url_page('delete_article', $this->id, array("confirm" => 1)) . '">Yes</a>|';
                    $return_value.= '<a href="' . $oWebsite->get_url_main() . '">No</a></p>';
                    return $return_value;
                } else {
                    $oWebsite->add_error('Article was not found.');
                    return '';
                }
            }
        }
    }

}

?>