<?php

namespace Rcms\Core;

use PDO;

use Rcms\Core\Exception\NotFoundException;
use Rcms\Core\Repository\Field;
use Rcms\Core\Repository\Repository;

class CategoryRepository extends Repository {

    const TABLE_NAME = "categorie";

    protected $databaseObject;
    protected $website;
    protected $idField;
    protected $nameField;

    public function __construct(Website $website, PDO $oDatabase = null) {
        parent::__construct($oDatabase? : $website->getDatabase());

        $this->website = $website;
        $this->databaseObject = $oDatabase;
        if (!$this->databaseObject) {
            $this->databaseObject = $website->getDatabase();
        }

        $this->idField = new Field(Field::TYPE_PRIMARY_KEY, "id", "categorie_id");
        $this->nameField = new Field(Field::TYPE_STRING, "name", "categorie_naam");
    }

    /**
     * Gets all categories.
     * @return Category[] All categories.
     */
    public function getCategories() {
        return $this->all()->orderDescending($this->idField)->select();
    }
    
    /**
     * Gets an array of links to all categories.
     * @param Text $text The text object, of URL structure.
     * @return Link[] The array of links.
     */
    public function getCategoryLinks(Text $text) {
        $categories = $this->getCategories();

        $links = [];
        foreach ($categories as $category) {
            if ($category->isStandardCategory()) {
                continue; // Don't display "No categories"
            }
            $links[] = Link::of(
                            $text->getUrlPage("category", $category->getId()), $category->getName()
            );
        }
        
        return $links;
    }

    /**
     * Gets all catgories in an array, category id => category name.
     * @return array The array.
     */
    public function getCategoriesArray() {
        $categoryArray = $this->getCategories();
        $returnArray = [];
        foreach ($categoryArray as $category) {
            $returnArray[$category->getId()] = $category->getName();
        }
        return $returnArray;
    }

    public function getAllFields() {
        return [$this->idField, $this->nameField];
    }

    public function getTableName() {
        return self::TABLE_NAME;
    }

    public function getPrimaryKey() {
        return $this->idField;
    }

    public function createEmptyObject() {
        return new Category();
    }

    /**
     * Gets the category.
     * @param int $id Id of the category.
     * @return Category The category.
     * @throws NotFoundException If the category is not found.
     */
    public function getCategory($id) {
        return $this->where($this->idField, '=', $id)->selectOneOrFail();
    }

    /**
     * Returns the name of the category with the given id. Does a database call
     * for this. Returns an empty string when the category isn't found.
     * @param int $id Id of the category.
     * @return string Name of the category, emtpy if not found.
     */
    function getCategoryName($id) {
        try {
            return $this->where($this->getPrimaryKey(), '=', $id)->selectOneOrFail()->getName();
        } catch (NotFoundException $e) {
            return "";
        }
    }

    // Below this line all methods should be rewritten to remove any HTML output

    function createCategory() {
        $website = $this->website;
        $category = new Category(0, "New category");
        $this->saveEntity($category);
        $id = $category->getId();
        //geef melding weer
        return <<<EOT
            <p>A new category has been created named 'New category'.</p>
            <p>
                    <a href="{$website->getUrlPage('rename_category', $id)}">Rename</a>|
                    <a href="{$website->getUrlPage('delete_category', $id, ['confirm' => 1])}">Undo</a>
            </p>	
EOT;
    }

    function renameCategory() { //gebruikt id en naam uit $_REQUEST
        //STRUCTUUR:
        // kijk eerst of er wel een id is. Zo niet, geef dan '' terug.
        // kijk daarna of er een 
        $website = $this->website;
        $oDB = $this->databaseObject;

        if (!isSet($_REQUEST['id'])) { //is er geen id, breek dan het script af
            return '';
        }
        //als dit deel van het script is bereikt, is wel een id opgegeven
        //sla de id op in $id

        $id = (int) $_REQUEST['id'];
        $name = '';


        if (isSet($_REQUEST['name'])) { //kijk of de naam goed is
            $name = $_REQUEST['name'];

            if ($id == 0) {
                $website->addError('Category was not found.');
                return ''; //breek onmiddelijk af
            }

            if (strLen($name) < 2) {
                $website->addError('Category name is too short!');
            }

            if (strLen($name) > 30) {
                $website->addError('Category name is too long! Maximum length is 30 characters.');
            }

            if ($website->getErrorCount() == 0) { //het is veilig om te hernoemen
                try {
                    $category = $this->getCategory($id);
                    $category->setName($name);
                    $this->saveEntity($category);
                    return '<p>Category is succesfully renamed.</p>';
                } catch (NotFoundException $e) {
                    $website->addError('Category was not found.');
                    return ''; //breek onmiddelijk af
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
		
		<form action="{$website->getUrlMain()}" method="post">
			<p>
				<label for="name"> New name for category '$oldname':</label>
				<input type="text" size="30" id="name" name="name" value="$name" />
				<input type="hidden" name="id" value="$id" />
				<input type="hidden" name="p" value="rename_category" />
				<br />
				<input type="submit" value="Save" class="button primary_button" /> 
				<a href="{$website->getUrlPage('rename_category')}" class="button">Cancel</a>
			</p>
		</form>
EOT;
    }

    function deleteCategory() { //verwijdert de categorie $_REQUEST['id']
        $website = $this->website;
        $oDB = $this->databaseObject;

        if (!isSet($_REQUEST['id'])) {
            return '';
        }

        $id = (int) $_REQUEST['id'];

        if ($id == 0) {//ongeldig nummer
            $website->addError('Category was not found.');
            return '';
        }
        if ($id == 1) { //"No category" category cannot be removed
            $website->addError('You cannot delete this category, but it is possible to rename it.');
            return '';
        }

        //verwijder categorie, maar laat eerst bevestigingsvraag zien
        if (isSet($_REQUEST['confirm']) && $_REQUEST['confirm'] == 1) { //verwijder categorie
            $this->where($this->idField, '=', $id)->deleteOneOrFail();
            $articles = new ArticleRepository($website);
            //zorg dat artikelen met de net verwijderder categorie ongecategoriseerd worden
            $articles->changeCategories($id, 1);

            //geef melding
            return '<p>Category is removed.</p>';
        } else { //laat bevestingingsvraag zien
            $cat_name = $this->getCategoryName($id);

            if (!empty($cat_name)) {
                $returnValue = '<p>Are you sure you want to remove the category \'' . $cat_name . '\'?';
                $returnValue.= ' This action cannot be undone. Please note that some articles might get uncatogorized.</p>';
                $returnValue.= '<p><a href="' . $website->getUrlPage("delete_category", $id, ["confirm" => 1]) . '">Yes</a>|';
                $returnValue.= '<a href="' . $website->getUrlPage("delete_category") . '">No</a></p>';
                return $returnValue;
            } else {
                return '';
            }
        }
    }

}
