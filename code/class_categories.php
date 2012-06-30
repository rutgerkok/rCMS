<?php
class Categories
{
	/*
	 * Klasse geeft tekst terug met <p>!
	 *
	 *
	 */
	
	protected $database_object;
	protected $website_object;
	
	function __construct($oWebsite,$oDB)
	{
		if(!isset($oWebsite->IS_WEBSITE_OBJECT))
		{
			//website object is geen website object, argumenten zijn verkeerd om aangeleverd
			//(voor 5 november 2011 was dat de standaard bij class_authentication, vanaf 24 januari 2012 ook hier)
			$this->database_object = $oWebsite;
			$this->website_object = $oDB;
		}
		else
		{
			$this->database_object = $oDB;
			$this->website_object = $oWebsite;
		}
	}
	
	function get_categories()
	{	//retourneert de categorieeen als array id=>naam
		$oDB = $this->database_object;
		
		$return_array = array();
		
		$result = $oDB->query("SELECT categorie_id,categorie_naam FROM `categorie` ORDER BY categorie_id DESC");
		while(list($id,$name) = $oDB->fetch($result))
		{
			$return_array[$id] = $name;
		}
		unset($result);
		return $return_array;
	}
	
	function get_category_name($id)
	{
		$oWebsite = $this->website_object;
		$oDB = $this->database_object;
		
		$id = (int) $id;
		if($id==0)
		{
			$oWebsite->add_error('Category was not found!');
			return '';
		}
		
		$result = $oDB->query("SELECT categorie_naam FROM `categorie` WHERE categorie_id = $id");
		if($oDB->rows($result)==1)
		{
			$result = $oDB->fetch($result);
			return($result[0]);
		}
		else
		{
			$oWebsite->add_error('Category was not found!');
			return '';
		}
		
	}
	
	function create_category()
	{
		$oWebsite = $this->website_object;
		$oDB = $this->database_object;
		$sql = "INSERT INTO `categorie` (`categorie_naam`) VALUES ('New category');";
		if($oDB->query($sql))
		{
			$id = $oDB->inserted_id();//haal id op van net ingevoegde rij
			
			//geef melding weer
			return <<<EOT
			
			<p>A new category has been created named 'New category'.</p>
			<p>
				<a href="{$oWebsite->get_url_page('rename_category',$id)}">Rename</a>|
				<a href="{$oWebsite->get_url_page('delete_category',$id,array('confirm'=>1))}">Undo</a>
			</p>
			
			
EOT;
		}
		else
		{
			$oWebsite->add_error('Category could not be created. Please try again later.');
			return '';
		}
	}
	
	function rename_category()
	{	//gebruikt id en naam uit $_REQUEST
		
		//STRUCTUUR:
		// kijk eerst of er wel een id is. Zo niet, geef dan '' terug.
		// kijk daarna of er een 
	
		$oWebsite = $this->website_object;
		$oDB = $this->database_object;
		
		if(!isset($_REQUEST['id']))
		{	//is er geen id, breek dan het script af
			return '';
		}
		//als dit deel van het script is bereikt, is wel een id opgegeven
		//sla de id op in $id
			
		$id = (int) $_REQUEST['id'];
		$name = '';
		
		
		if(isset($_REQUEST['name']))
		{	//kijk of de naam goed is
			
			
			$name = $oDB->escape_data( $_REQUEST['name'] );
			
			if($id==0)
			{
				$oWebsite->add_error('Category was not found.');	
				return '';//breek onmiddelijk af
			}
			
			if(strlen($name)<2)
			{
				$oWebsite->add_error('Category name is too short!');
			}
			
			if(strlen($name)>30)
			{
				$oWebsite->add_error('Category name is too long! Maximum length is 30 characters.');
			}
			
			if($oWebsite->error_count()==0)
			{	//het is veilig om te hernoemen
				$sql = "UPDATE `categorie` SET categorie_naam = '$name' WHERE categorie_id = $id";
				if($oDB->query($sql))
				{
					if($oDB->affected_rows()==1)
					{
						return '<p>Category is succesfully renamed.</p>';
					}
					else
					{	//categorie niet gevonden
						$oWebsite->add_error('Category was not found.');	
						return '';//breek onmiddelijk af
					}
				}
				else
				{
					$oWebsite->add_error("Cannot rename category!");
				}
			}
		}
		
		//als dit deel van de methode is bereikt, is er ergens iets misgegaan.
		//als alles is goed gegaan, dan is de functie al eerder afgebroken
		//nu is er echter een probleem, of er is geen naam voor de categorie
		//ingevuld, of de naam is ongeldig, of er is geen geldige id, of de database deed zijn werk niet goed.
		//laat hoe dan ook het formulier zien om een naam in te vullen.
		$oldname =  $this->get_category_name($id);
		if($oldname=='')
		{//categorie niet gevonden
			return '';
		}
		return <<<EOT
		
		<form action="{$oWebsite->get_url_main()}" method="post">
			<p>
				<label for="name"> New name for category '$oldname':</label>
				<input type="text" size="30" id="name" name="name" value="$name" />
				<input type="hidden" name="id" value="$id" />
				<input type="hidden" name="p" value="rename_category" />
				<br />
				<input type="submit" value="Save" class="button" /> 
				<a href="{$oWebsite->get_url_page('rename_category')}" class="button">Cancel</a>
			</p>
		</form>
EOT;
		
		
	}
	
	function delete_category()
	{	//verwijdert de categorie $_REQUEST['id']
		$oWebsite = $this->website_object;
		$oDB = $this->database_object;
		
		if(!isset($_REQUEST['id']))
		{
			return '';
		}
		
		$id = (int) $_REQUEST['id'];
		
		if($id==0)//ongeldig nummer
		{
			$oWebsite->add_error('Category was not found.');
			return '';
		}
		if($id==1||$id==2)//eerste drie categorieen mogen niet verwijderd worden
		{
			$oWebsite->add_error('You cannot delete this category, but it is possible to rename it.');
			return '';
		}
		
		//verwijder categorie, maar laat eerst bevestigingsvraag zien
		if(isset($_REQUEST['confirm'])&&$_REQUEST['confirm']==1)
		{	//verwijder categorie
			$sql = "DELETE FROM `categorie` WHERE `categorie_id` = $id";
			if($oDB->query($sql)&&$oDB->affected_rows()==1)
			{
				//zorg dat artikelen met de net verwijderder categorie ongecategoriseerd worden
				$sql = "UPDATE `artikel` SET `categorie_id` = '1' WHERE `categorie_id` = $id";
				$oDB->query($sql);
				
				//geef melding
				return '<p>Category is removed.</p>';
				
			}
			else
			{
				$oWebsite->add_error('Category could not be removed.');
				return '<p>Category is NOT removed.</p>';
			}
		}
		else
		{	//laat bevestingingsvraag zien
			$cat_name = $this->get_category_name($id);
			
			if(!empty($cat_name))
			{
				$return_value = '<p>Are you sure you want to remove the category \''.$cat_name.'\'?';
				$return_value.= ' This action cannot be undone. Please note that some articles might get uncatogorized.</p>';
				$return_value.= '<p><a href="'.$oWebsite->get_url_page("delete_category",$id,array("confirm"=>1)).'">Yes</a>|';
				$return_value.= '<a href="'.$oWebsite->get_url_page("delete_category").'">No</a></p>';
				return $return_value;
			}
			else
			{
				return '';
			}
		}
	}//einde van methode delete_category
}	
?>