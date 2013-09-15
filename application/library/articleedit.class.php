<?php

class ArticleEdit {

    protected $databaseObject;
    protected $websiteObject;
    protected $categoryObject;
    protected $id;
    protected $confirm;
    protected $contents = array();

    //constructor, zet gegevens artikel klaar
    function __construct($oDB, $oCats, Website $oWebsite) {
        $this->databaseObject = $oDB;
        $this->websiteObject = $oWebsite;
        $this->categoryObject = $oCats;

        //ID GOEDZETTEN
        $id = 0;
        if (isSet($_REQUEST['id'])) {
            $id = (int) $_REQUEST['id'];
        }
        $this->id = $id;

        //CONFIRM GOEDZETTEN
        $confirm = 0;
        if (isSet($_REQUEST['confirm'])) {
            $confirm = (int) $_REQUEST['confirm'];
        }
        $this->confirm = $confirm;
    }

    function deleteArticle() {
        $oDB = $this->databaseObject;
        $oWebsite = $this->websiteObject;
        if ($this->id > 0) {
            if ($this->confirm) { //verwijder categorie
                $sql = 'DELETE FROM `artikel` WHERE artikel_id = ' . $this->id;
                if ($oDB->query($sql) && $oDB->affectedRows() == 1) {
                    $returnValue = '<p>' . $oWebsite->t("main.article") . ' ' . $oWebsite->t("editor.is_deleted") . '</p>'; //artikel verwijderd
                    $returnValue.= '<p><a href="' . $oWebsite->getUrlMain() . '">Home</a></p>';
                    return $returnValue;
                } else {
                    $oWebsite->addError($oWebsite->t("main.article") . ' ' . $oWebsite->t("errors.not_saved")); //artikel kan niet verwijderd worden
                    return '';
                }
            } else { //laat bevestigingsvraag zien
                $sql = "SELECT artikel_titel FROM `artikel` WHERE artikel_id = " . $this->id;
                $result = $oDB->query($sql);

                if ($result) {
                    $result = $oDB->fetchNumeric($result);
                    $result = $result[0];

                    $returnValue = '<p>Are you sure you want to remove the article \'' . htmlSpecialChars($result) . '\'?';
                    $returnValue.= ' This action cannot be undone.</p>';
                    $returnValue.= '<p><a href="' . $oWebsite->getUrlPage('delete_article', $this->id, array("confirm" => 1)) . '">Yes</a>|';
                    $returnValue.= '<a href="' . $oWebsite->getUrlMain() . '">No</a></p>';
                    return $returnValue;
                } else {
                    $oWebsite->addError('Article was not found.');
                    return '';
                }
            }
        }
    }

}

?>