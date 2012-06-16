<?php
class Website
{
	/*
	 * ATTRIBUTEN:
	 *	$pagevars['title'] - (string) huidige paginatitel, gegenereerd aan de hand van $_REQUEST['p'] in de constructor
	 *	$pagevars['shorttitle'] - (string) kortere paginatitel (zonder Bioscience), gegenereerd aan de hand van $_REQUEST['p'] in de constructor
	 *  $pagevars['file'] - (string) huidig paginabestand (zonder extensie of map)
	 *  $pagevars['errors'] - (array) huidige paginafouten
	 *  $pagevars['debug'] - (bool) geeft aan of alle foutmeldingen weergegeven moeten worden.
	 *  $pagevars['database_object'] - (object) de databaseverbinding, opgeslagen door class_database.php
	 *  $pagevars['site'] - (string) de geladen site, bioscience of phpark
	 *  $pagevars['type'] - (string) het type pagina, "NORMAL", "NOWIDGETS" of "BACKSTAGE"
	 *  $pagevars['local']
	 *  $pagevars['base_uri']
	 *  $pagevars['base_url']
	 *  $sites['name']['setting']
	 *  $translations - (array) bevat alle vertaalzinnen voor de site
	 *  $errorsdisplayed - (bool) of alle foutmeldingen al weergegeven zijn (belangrijk voor fouten die daarna voorkomen)
	 *
	 * METHODES:
	 *	__construct - stelt $pagevars in.
	 *  set_pagevar/get_pagevar - stelt een variabele in in de $pagevars array 
  	 *	echo_* - geven een bepaald deel van de pagina weer
	 *  add_error - voegt een foutmelding toe
	 *  error_count - aantal foutmeldingen
	 *  logged_in - controleert of de gebruiker is ingelogd, zonder formulieren te verwerken.
	 */
	
	protected $pagevars = array();
	
	protected $errorsdisplayed = false;
	
	
	public $translations = array();//oude vertaalarray
	
	function __construct()
	{
		//SITES INSTELLEN
		$this->site_settings();
		
		//VARIABELEN INSTELLEN
		$this->pagevars['errors'] = array();
		$this->pagevars['local'] = ( ($_SERVER['REMOTE_ADDR']=="127.0.0.1") OR (substr($_SERVER['REMOTE_ADDR'],0,8)=="192.168.") );//zijn we lokaal?
		$this->pagevars['debug'] = $this->logged_in(true)||$this->pagevars['local'];
		$this->pagevars['site'] = 'phpark'; //phpark, rkok of bioscience
		$this->pagevars['database_object'] = null;
		
		if($this->pagevars['local'])
		{
			$this->pagevars['base_uri'] = $this->get_sitevar('local_uri');
			$this->pagevars['base_url'] = $this->get_sitevar('local_url');
		}
		else
		{
			$this->pagevars['base_uri'] = $this->get_sitevar('external_uri');
			$this->pagevars['base_url'] = $this->get_sitevar('external_url');
		}

		
		
		//OUDE VERTALINGEN
		if(file_exists($this->get_uri_themes().$this->get_sitevar("theme")."/translations.txt")) //zoek naar thema-specifiek bestand
		{
			$this->translations = file( $this->get_uri_themes().$this->get_sitevar("theme")."/translations.txt" );
			setlocale(LC_ALL, $this->get_sitevar('locales') );
			foreach($this->translations as $id=>$value)
			{
				$this->translations[$id]=trim($value);
			}
		}
		else if(file_exists($this->get_uri_scripts())."translations.txt") //val terug op algemene bestand
		{
			$this->translations = file( $this->get_uri_scripts()."translations.txt" );
			setlocale(LC_ALL, $this->get_sitevar('locales') );
			foreach($this->translations as $id=>$value)
			{
				$this->translations[$id]=trim($value);
			}
		}
		else
		{
			die("Translations file (<code>"+$this->get_uri_scripts()."translations.txt</code>) not found! Corrupted installation?");
		}
		
		//PAGINASPECIFIEKE GEGEVENS OPHALEN
		if(isset($_REQUEST['p'])&&!empty($_REQUEST['p'])&&$_REQUEST['p']!='home')
		{
			$this->pagevars['file'] = $_REQUEST['p'];
			
			//Titel instellen
			$this->pagevars['title']= $this->get_sitevar('title');//begin met alleen de naam van de site...
			$this->pagevars['shorttitle'] = ucfirst(str_replace('_',' ',$_REQUEST['p']));//korte titel
			if($this->get_sitevar('showpage')) $this->pagevars['title'].= ' - '. $this->pagevars['shorttitle'];//...verleng eventueel met paginanaam
			
			if(!file_exists($this->get_uri_modules().$this->pagevars['file'].".inc"))
			{
				$this->pagevars['file'] = 'home';//bestaat pagina niet? dan naar homepage
				$this->add_error("Page '".$this->pagevars['title']."' was not found.");//en laat een foutmelding zien
			}
			
		}
		else
		{
			$this->pagevars['file'] = 'home';
			//Titel instellen
			$this->pagevars['title'] = $this->get_sitevar('hometitle');
			$this->pagevars['shorttitle'] = 'Home';
		}
		
		
		
		//BEPALEN WAT HET UITERLIJK VAN DE PAGINA IS
		switch($this->pagevars['file'])
		{
			case "home":
				$this->pagevars['type'] = "NORMAL";
				break;
			case "view_category":
			case "search":
			case "view_article":
			case "archive":
			case "calendar":
			case "add_comment":
				$this->pagevars['type'] = "NOWIDGETS";
				break;
			default:
				$this->pagevars['type'] = "BACKSTAGE";
				break;
		}
		
		//COMPABILITEIT
		if(!function_exists("lcfirst"))
		{
			require_once("function_lcfirst.php");
		}
		
	}
	
	public function set_pagevar($var,$value)
	{
		$this->pagevars[$var] = $value;
		return true;
	}
	
	public function get_pagevar($var)
	{
		return($this->pagevars[$var]);
	}
	
	//geeft een database-object terug
	public function get_database()
	{
		if($this->pagevars["database_object"]==null)
		{
			$this->pagevars["database_object"] = new Database($this);
		}
		return $this->pagevars["database_object"];
	}
	
	public function get_sitevar($var)
	{
		if(isset($this->config[$var])) 
		{
			return($this->config[$var]);
		}
		else
		{
			return false;
		}
	}
	
	
//Alle paden hier
	//Geeft de map van de scripts terug als url
	public function get_url_scripts()
	{
		return $this->pagevars['base_url']."code/";
	}
	
	//Geeft de map van de scripts terug als uri
	public function get_uri_scripts()
	{
		return $this->pagevars['base_uri']."code/";
	}
	
	//Geeft de map van alle modules terug als url
	public function get_url_modules()
	{
		return $this->get_url_scripts()."pages/";
	}
	
	//Geeft de map van alle modules terug als uri
	public function get_uri_modules()
	{
		return $this->get_uri_scripts()."pages/";
	}
	
	//Geeft de map van alle thema's terug als url
	public function get_url_themes()
	{
		return $this->get_url_scripts()."themes/";
	}
	
	//Geeft de map van alle thema's terug als uri
	public function get_uri_themes()
	{
		return $this->get_uri_scripts()."themes/";
	}
	//Geeft de map van alle widgets terug als url
	public function get_url_widgets()
	{
		return $this->get_url_scripts()."widgets/";
	}
	
	//Geeft de map van alle widgets terug als uri
	public function get_uri_widgets()
	{
		return $this->get_uri_scripts()."widgets/";
	}	
	//Geeft de map van alle vertalingen terug als url
	public function get_url_translations()
	{
		return $this->get_url_scripts()."translations/";
	}
	
	//Geeft de map van alle vertalingen terug als uri
	public function get_uri_translations()
	{
		return $this->get_uri_scripts()."translations/";
	}	
//Einde paden

	public function add_error($message,$public_message=false)
	{
		if($this->pagevars['debug']||!$public_message)
		{	//foutmelding alleen weergeven als melding ongevaarlijk is of als debuggen aan is gezet
			$this->pagevars['errors'][count($this->pagevars['errors'])] = $message;
		}
		else
		{
			$this->pagevars['errors'][count($this->pagevars['errors'])] = $public_message;
		}
		if($this->errorsdisplayed)
		{//geef ook nieuwe foutmeldingen weer, als normale al weergegeven zijn
			$this->echo_errors();
		}
	}
	
	public function error_count()
	{
		return count($this->pagevars['errors']);
	}
	
	public function error_clear_all()
	{
		unset($this->pagevars['errors']);
		$this->pagevars['errors'] = array();	
	}
	
	public function echo_errors()
	{	//geeft alle foutmeldingen weer
		$this->errorsdisplayed = true;
		
		$errors = count($this->pagevars['errors']);//totaal aantal foutmeldingen
		if($errors==0)
		{
			return true;
		}
		elseif($errors==1)
		{
			echo '<div class="fout"><h3>'.$this->t("errors.erroroccured").'</h3>';
			echo $this->pagevars['errors'][0];
			echo '</div>';
		}
		else
		{
			echo '<div class="fout">';
			echo "   <h3>".str_replace("#",$errors,$this->t('errors.errorsoccured'))."</h3>";
			echo '   <p>';
			echo '      <ul>';
			foreach($this->pagevars['errors'] as $nr=>$error)
			{
				echo '<li>'.$error.'</li>';
			}
			echo '      </ul>';
			echo '	 </p>';
			echo '</div>';
		}
		$this->error_clear_all();
		return true;
	}
	
	function has_access()
	{	//kijkt of site mag worden geladen
		$access = false;
		if($this->get_sitevar('password')=="") $access = true; 
		if(isset($_POST['key']) && $this->get_sitevar('password')==$_POST['key'] ) $access = true; 
		if(isset($_GET['key']) && $this->get_sitevar('password')==$_GET['key'] ) $access = true; 
		if(isset($_COOKIE['key']) && $this->get_sitevar('password')==$_COOKIE['key'] ) $access = true; 
		
		return $access;
	}
	
	//Laat de gehele pagina zien
	public function echo_page()
	{
		if($this->has_access())
		{	//geef de pagina weer
			setcookie("key", $this->get_sitevar('password'), time()+3600*24*365,"/");
			$this->add_error('test');
			$this->add_error('test2');
			new Themes($this);
		}
		else
		{	//laat inlogscherm zien
			require($this->get_uri_scripts().'login_page.php');
		}
	}
	
	public function echo_page_content()
	{	//geeft de hoofdpagina weer
		if($this->has_access())
		{
			if(file_exists($this->get_uri_modules().$this->pagevars['file'].".inc"))
			{	//voeg de module in als die bestaat (al gecheckt in constructor)
				include($this->get_uri_modules().$this->pagevars['file'].".inc");
			}
		}		
	}
	
	public function logged_in($admin=false)
	{
		if(
			isset($_SESSION['user'])&&
			isset($_SESSION['pass'])&&
			isset($_SESSION['email'])&&
			isset($_SESSION['admin'])&&
			$_SESSION['admin']>=$admin)
		{	//ingelogd met voldoende rechten
			return 1;
		}
		else
		{
			return 0;
		}
	}
	
	public function site_settings()
	{
		//SITES INSTELLEN
		if(file_exists('config.php'))
		{
			require('config.php');
		}
		else
		{
			echo "<code>config.php</code> was not found! Place it next to your index.php";
			die();
		}
	}
	
	public function t($key)
	{
		$keys = explode(".",$key,2);
		if(isset($this->translations[$keys[0]]))
		{	//al geladen
			return $this->translations[$keys[0]][$keys[1]];
		}
		else
		{	//moet nog geladen worden
			$translations_file = $this->get_uri_translations().$this->get_sitevar('language')."/translations_".$keys[0].".txt";
			if(file_exists($translations_file))
			{	//laad
				$file_contents = file($translations_file);
				foreach($file_contents as $line)
				{
					$translation = explode("=",$line,2);
					$this->translations[$keys[0]][$translation[0]] = $translation[1];
				}
				unset($file_contents);
				
				//en geef juiste waarde terug
				return $this->translations[$keys[0]][$keys[1]];
			}
			else
			{	//foutmelding
				echo "<code>$translations_file</code> was not found!";
				die();
			}
		}
		
	}
}
?>