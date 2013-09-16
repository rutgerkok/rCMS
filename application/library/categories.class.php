<?php

class Categories {

    protected $databaseObject;
    protected $websiteObject;

    function __construct(Website $oWebsite, Database $oDatabase = null) {
        $this->websiteObject = $oWebsite;
        $this->databaseObject = $oDatabase;
        if (!$this->databaseObject) {
            $this->databaseObject = $oWebsite->getDatabase();
        }
    }

    function getCategories() { //retourneert de categorieeen als array id=>naam
        $oDB = $this->databaseObject;

        $return_array = array();

        $result = $oDB->query("SELECT categorie_id,categorie_naam FROM `categorie` ORDER BY categorie_id DESC");
        while (list($id, $name) = $oDB->fetchNumeric($result)) {
            $return_array[$id] = $name;
        }
        unset($result);
        return $return_array;
    }

    /**
     * Returns the name of the category with the given id. Does a database call
     * for this. Returns an empty string when the category isn't found.
     * @param int $id Id of the category.
     * @return string Name of the category, emtpy if not found.
     */
    function getCategoryName($id) {
        $oDB = $this->databaseObject;

        $id = (int) $id;

        $result = $oDB->query("SELECT categorie_naam FROM `categorie` WHERE categorie_id = $id");
        if ($oDB->rows($result) == 1) {
            $result = $oDB->fetchNumeric($result);
            return($result[0]);
        } else {
            return '';
        }
    }

    // Below this line all methods should be rewritten to remove any HTML output

    function createCategory() {
        $oWebsite = $this->websiteObject;
        $oDB = $this->databaseObject;
        $sql = "INSERT INTO `categorie` (`categorie_naam`) VALUES ('New category');";
        if ($oDB->query($sql)) {
            $id = $oDB->getLastInsertedId(); //haal id op van net ingevoegde rij
            //geef melding weer
            return <<<EOT
			
			<p>A new category has been created named 'New category'.</p>
			<p>
				<a href="{$oWebsite->getUrlPage('rename_category', $id)}">Rename</a>|
				<a href="{$oWebsite->getUrlPage('delete_category', $id, array('confirm' => 1))}">Undo</a>
			</p>
			
			
EOT;
        } else {
            $oWebsite->addError('Category could not be created. Please try again later.');
            return '';
        }
    }

    function renameCategory() { //gebruikt id en naam uit $_REQUEST
        //STRUCTUUR:
        // kijk eerst of er wel een id is. Zo niet, geef dan '' terug.
        // kijk daarna of er een 
        $oWebsite = $this->websiteObject;
        $oDB = $this->databaseObject;

        if (!isSet($_REQUEST['id'])) { //is er geen id, breek dan het script af
            return '';
        }
        //als dit deel van het script is bereikt, is wel een id opgegeven
        //sla de id op in $id

        $id = (int) $_REQUEST['id'];
        $name = '';


        if (isSet($_REQUEST['name'])) { //kijk of de naam goed is
            $name = $oDB->escapeData($_REQUEST['name']);

            if ($id == 0) {
                $oWebsite->addError('Category was not found.');
                return ''; //breek onmiddelijk af
            }

            if (strLen($name) < 2) {
                $oWebsite->addError('Category name is too short!');
            }

            if (strLen($name) > 30) {
                $oWebsite->addError('Category name is too long! Maximum length is 30 characters.');
            }

            if ($oWebsite->getErrorCount() == 0) { //het is veilig om te hernoemen
                $sql = "UPDATE `categorie` SET categorie_naam = '$name' WHERE categorie_id = $id";
                if ($oDB->query($sql)) {
                    if ($oDB->affectedRows() == 1) {
                        return '<p>Category is succesfully renamed.</p>';
                    } else { //categorie niet gevonden
                        $oWebsite->addError('Category was not found.');
                        return ''; //breek onmiddelijk af
                    }
                } else {
                    $oWebsite->addError("Cannot rename category!");
                }
            }
        }

        //als dit deel van de methode is bereikt, is er ergens iets misgegaan.
        //als alles is goed gegaan, dan is de functie al eerder afgebroken
        //nu is er echter een probleem, of er is geen naam voor de categorie
        //ingevuld, of de naam is ongeldig, of er is geen geldige id, of de database deed zijn werk niet goed.
        //laat hoe dan ook het formulier zien om een naam in te vullen.
        $oldname = $this->getCategoryName($id);
        if ($oldname == '') {//categorie niet gevonden
            return '';
        }
        return <<<EOT
		
		<form action="{$oWebsite->getUrlMain()}" method="post">
			<p>
				<label for="name"> New name for category '$oldname':</label>
				<input type="text" size="30" id="name" name="name" value="$name" />
				<input type="hidden" name="id" value="$id" />
				<input type="hidden" name="p" value="rename_category" />
				<br />
				<input type="submit" value="Save" class="button primary_button" /> 
				<a href="{$oWebsite->getUrlPage('rename_category')}" class="button">Cancel</a>
			</p>
		</form>
EOT;
    }

    function deleteCategory() { //verwijdert de categorie $_REQUEST['id']
        $oWebsite = $this->websiteObject;
        $oDB = $this->databaseObject;

        if (!isSet($_REQUEST['id'])) {
            return '';
        }

        $id = (int) $_REQUEST['id'];

        if ($id == 0) {//ongeldig nummer
            $oWebsite->addError('Category was not found.');
            return '';
        }
        if ($id == 1) { //"No category" category cannot be removed
            $oWebsite->addError('You cannot delete this category, but it is possible to rename it.');
            return '';
        }

        //verwijder categorie, maar laat eerst bevestigingsvraag zien
        if (isSet($_REQUEST['confirm']) && $_REQUEST['confirm'] == 1) { //verwijder categorie
            $sql = "DELETE FROM `categorie` WHERE `categorie_id` = $id";
            if ($oDB->query($sql) && $oDB->affectedRows() == 1) {
                //zorg dat artikelen met de net verwijderder categorie ongecategoriseerd worden
                $sql = "UPDATE `artikel` SET `categorie_id` = '1' WHERE `categorie_id` = $id";
                $oDB->query($sql);

                //geef melding
                return '<p>Category is removed.</p>';
            } else {
                $oWebsite->addError('Category could not be removed.');
                return '<p>Category is NOT removed.</p>';
            }
        } else { //laat bevestingingsvraag zien
            $cat_name = $this->getCategoryName($id);

            if (!empty($cat_name)) {
                $returnValue = '<p>Are you sure you want to remove the category \'' . $cat_name . '\'?';
                $returnValue.= ' This action cannot be undone. Please note that some articles might get uncatogorized.</p>';
                $returnValue.= '<p><a href="' . $oWebsite->getUrlPage("delete_category", $id, array("confirm" => 1)) . '">Yes</a>|';
                $returnValue.= '<a href="' . $oWebsite->getUrlPage("delete_category") . '">No</a></p>';
                return $returnValue;
            } else {
                return '';
            }
        }
    }

}

?>