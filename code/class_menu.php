<?php
class Menu
{

	protected $website_object;
	protected $database_object;
	protected $categories_object;

	function __construct(Website $oWebsite,Database $oDB,$oCats=null)
	{
		$this->database_object = $oDB;
		$this->website_object = $oWebsite;
		$this->categories_object = $oCats? $oCats: new Categories($oDB,$oWebsite);
	}

	function get_items()
	{	//haalt een 2d-array met items op van de sidebar
		$oDB = $this->database_object;

		$sql = "SELECT `menuitem_id`,`menuitem_url`,`menuitem_naam` FROM `menuitem` ORDER BY `menuitem_naam`";

		$result = $oDB->query($sql);
		if($oDB->rows($result)>0)
		{
			$return_value = array();
			while(list($id,$url,$text) = $oDB->fetch($result))
			{
				$return_value[$id] = array($url,$text);
			}
			return $return_value;
		}
		else
		{
			return null;
		}
	}

	function get_menu_search($keyword)
	{
		$oDB = $this->database_object;
		$oWebsite = $this->website_object;

		$keyword = $oDB->escape_data($keyword);//maak zoekwoord veilig voor in gebruik query;

		$sql = "SELECT `menuitem_id`,`menuitem_url`,`menuitem_naam` FROM `menuitem` WHERE `menuitem_url` LIKE '%$keyword%' OR `menuitem_naam` LIKE '%$keyword%'";

		$result = $oDB->query($sql);
		if($oDB->rows($result)>0)
		{
			$return_value = '<p>';
			while(list($id,$url,$text) = $oDB->fetch($result))
			{
				$return_value.= "<a href=\"$url\">$text</a><br />";
			}
			$return_value.="</p>";
			return $return_value;
		}
		else
		{
			return '<p><em>'.$oWebsite->t("errors.nothing_found").'.</em></p>';//niets gevonden
		}

	}

	function get_menu_top()
	{	//geeft het menu boven aan de site ZONDER <UL>
		$oWebsite = $this->website_object;
		$oCats = $this->categories_object;

		$items=$oCats->get_categories();
		$return_value = "\n";

		if($items)
		{
			$return_value.='<li><a href="'.$oWebsite->get_url_main().'">'. $oWebsite->t("main.home") .'</a></li>';
				
			foreach($items as $id=>$cat_name)
			{
				if($id==1) continue; //geef niet alle categorie�n weer
				$return_value.='<li>';
				$return_value.='<a href="'.$oWebsite->get_url_page("category",$id);
				$return_value.='">';
				$return_value.=htmlentities($cat_name);//naam
				$return_value.='</a>';
				$return_value.="</li>\n";
			}
				
		}
		else
		{	//nog geen menu gevonden
			//link om de database te installeren (en een om naar de homepage te gaan)
			$return_value.='<li><a href="'.$oWebsite->get_url_main().'">Home</a></li>';
			$return_value.='<li><a href="'.$oWebsite->get_url_page("installing_database").'">Install database</a></li>';
		}

		return $return_value;
	}

	function get_menu_sidebar()
	{	//geeft het menu aan de zijkant van de site kant-en-klaar
		$oWebsite = $this->website_object;

		$items=$this->get_items();
		$logged_in = $this->website_object->logged_in_staff(false);

		$return_value = '<ul>';

		if($items)
		{
			foreach($items as $id=>$link)
			{
				$return_value.='<li>';
				$return_value.='<a  target="_blank" href="';
				$return_value.=htmlentities($link[0]);//url
				$return_value.='">';
				$return_value.=htmlentities($link[1]);//tekst
				$return_value.='</a> ';

				//bewerk- en verwijderlinks
				if($logged_in)
				{
					$return_value.='<a class="arrow" href="'.$oWebsite->get_url_page("change_link",$id).'">'.$oWebsite->t("main.edit").'</a> ';
					$return_value.='<a class="arrow" href="'.$oWebsite->get_url_page("delete_link",$id).'">'.$oWebsite->t("main.delete").'</a> ';
				}


				$return_value.="</li>\n";
			}
				
		}
		$return_value.= '</ul>';
		if($logged_in) $return_value.= '<p><a href="'.$oWebsite->get_url_page("create_link").'" class="arrow">'.$oWebsite->t("links.create").'</a></p>';

		return $return_value;
	}

	function get_menuitem_name($id)
	{
		$oWebsite = $this->website_object;
		$oDB = $this->database_object;

		$id = (int) $id;
		if($id==0)
		{
			return '';
		}

		$result = $oDB->query("SELECT menuitem_naam FROM `menuitem` WHERE menuitem_id = $id");
		if($oDB->rows($result)==1)
		{
			$result = $oDB->fetch($result);
			return($result[0]);
		}
		else
		{
			return '';
		}

	}

	function get_menuitem_url($id)
	{
		$oWebsite = $this->website_object;
		$oDB = $this->database_object;

		$id = (int) $id;
		if($id==0)
		{
			return '';
		}

		$result = $oDB->query("SELECT menuitem_url FROM `menuitem` WHERE menuitem_id = $id");
		if($oDB->rows($result)==1)
		{
			$result = $oDB->fetch($result);
			return($result[0]);
		}
		else
		{
			return '';
		}

	}


	function create_menuitem()
	{
		$oWebsite = $this->website_object;
		$oDB = $this->database_object;
		$sql = "INSERT INTO `menuitem` (`menuitem_naam`,`menuitem_url`,`menuitem_type`) VALUES ('(".$oWebsite->t("links.new_link").")','http://www.example.com/','4');";
		if($oDB->query($sql))
		{
			$id = $oDB->inserted_id();//haal id op van net ingevoegde rij
				
			//geef melding weer
			return <<<EOT
		
			<p>A new link has been created named '(New research group)'.</p>
			<p>
				<a href="{$oWebsite->get_url_page("change_link",$id)}">Change link</a>|
				<a href="{$oWebsite->get_url_page("delete_link",$id,array("confirm"=>1))}">Undo</a>
			</p>
EOT;
		}
		else
		{
			$oWebsite->add_error('Link could not be created. Please try again later.');
			return '';
		}
	}

	function change_menuitem()
	{	//gebruikt id, naam, url en type uit $_REQUEST


		$oWebsite = $this->website_object;
		$oDB = $this->database_object;

		if(!isset($_REQUEST['id']))
		{	//is er geen id, breek dan het script af
			return '';
		}
		//als dit deel van het script is bereikt, is wel een id opgegeven
		//sla de id op in $id
			
		$id = (int) $_REQUEST['id'];

		if($id==0)
		{
			$oWebsite->add_error('Category was not found.');
			return '';//breek onmiddelijk af
		}

		$name = '';
		$url = '';
		$type = 1;

		//haal oude en naam url alvast op
		$name = $this->get_menuitem_name($id);
		$url  = $this->get_menuitem_url($id);

		//haal deze alvast op zodat ze verderop ook beschikbaar zijn als een van beiden ontbreekt
		if(isset($_REQUEST['name'])) $name = $oDB->escape_data( $_REQUEST['name'] );
		if(isset($_REQUEST['url'])) $url = $oDB->escape_data( $_REQUEST['url'] );

		if(isset($_REQUEST['name'])&&isset($_REQUEST['url']))
		{
				
			//kijk of de naam goed is
			if(strlen($name)<2)
			{
				$oWebsite->add_error('Link name is too short!');
			}
				
			if(strlen($name)>50)
			{
				$oWebsite->add_error('Link name is too long! Maximum length is 50 characters.');
			}
				
			//kijk of de url goed is
			if(strlen($url)<2)
			{
				$oWebsite->add_error('Link url is too short!');
			}
				
			if(strlen($url)>200)
			{
				$oWebsite->add_error('Link url is too long! Maximum length is 200 characters. Maybe you could use '.
						'<a href="http://tinyurl.com/create.php?url='.urlencode($url).'">a url shortener</a>.');
			}
				
				
			//veranderen?
			if($oWebsite->error_count()==0)
			{	//het is veilig om te veranderen
				$sql = "UPDATE menuitem SET menuitem_naam = '$name', menuitem_url = '$url' WHERE menuitem_id = $id";
				if($oDB->query($sql))
				{
					if($oDB->affected_rows()==1)
					{
						return '<p>Link is succesfully changed.</p>';
					}
					else
					{	//menuitem niet gevonden
						$oWebsite->add_error("Link is not changed. Did you change anything?");
						return '';//breek onmiddelijk af
					}
				}
				else
				{
					$oWebsite->add_error("Cannot change link!");
				}
			}
		}

		//als dit deel van de methode is bereikt, is er ergens iets misgegaan.
		//als alles is goed gegaan, dan is de functie al eerder afgebroken
		//nu is er echter een probleem: er zijn geen (geldige) gegevens,
		//of de database deed zijn werk niet goed.
		//laat hoe dan ook het formulier zien om een link in te vullen.
		$oldname =  $this->get_menuitem_name($id);
		if($oldname=='')
		{//menuitem niet gevonden
			return '';
		}


		return <<<EOT

		<form action="{$oWebsite->get_url_main()}" method="post">
			<p>
				<label for="name">New name for link '$oldname':</label><br />
				<input type="text" size="30" id="name" name="name" value="$name" /><br />
				<label for="url">Url: (including http://)</label><br />
				<input type="url" size="30" id="url" name="url" value="$url" /><br />

				<input type="hidden" name="id" value="$id" />
				<input type="hidden" name="p" value="change_link" />
				<br />
				<input type="submit" value="Save" class="button" />
				<a href="{$oWebsite->get_url_page("change_link")}" class="button">Cancel</a>
			</p>
		</form>
EOT;


	}

	function delete_menuitem()
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
			$oWebsite->add_error('Research group was not found.');
			return '';
		}

		//verwijder menuitem, maar laat eerst bevestigingsvraag zien
		if(isset($_REQUEST['confirm'])&&$_REQUEST['confirm']==1)
		{	//verwijder categorie
			$sql = "DELETE FROM `menuitem` WHERE `menuitem_id` = $id";
			if($oDB->query($sql)&&$oDB->affected_rows()==1)
			{
				return '<p>Research group is removed.</p>';
			}
			else
			{
				$oWebsite->add_error('Research group could not be removed.');
				return '<p>Research group is NOT removed.</p>';
			}
		}
		else
		{	//laat bevestingingsvraag zien
			$menuitem_name = $this->get_menuitem_name($id);
				
			if(!empty($menuitem_name))
			{
				$return_value = '<p>Are you sure you want to remove the link \''.$menuitem_name.'\'?';
				$return_value.= ' This action cannot be undone.</p>';
				$return_value.= '<p><a href="'.$oWebsite->get_url_page("delete_link",$id,array("confirm"=>1)).'">Yes</a>|';
				$return_value.= '<a href="'.$oWebsite->get_url_page("delete_link").'">No</a></p>';
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