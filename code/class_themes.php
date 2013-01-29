<?php
class Themes
{
	private $website_object; //bewaart het website-object
	private $widgets_object; //bewaart het widgets-object
	
	public function __construct($oWebsite)
	{
		$this->website_object = $oWebsite;
		$this->widgets_object = new Widgets($oWebsite);
		
		if(file_exists($this->get_uri_theme()."main.php"))
		{
			require($this->get_uri_theme()."main.php");
		}
		else
		{
			die("<code>".$this->get_uri_theme()."main.php</code> was not found! Theme is missing.");
		}
	}
	
	public function echo_accounts_menu()
	{
		$oWebsite = $this->website_object;
		
		//Geef de inloglinks weer
		if($oWebsite->logged_in_staff(true)) 
		{ //admin
			echo '<li><a href="'.$oWebsite->get_url_page("admin").'">'.$oWebsite->t("main.admin").'</a></li>';
		}
		if($oWebsite->logged_in())
		{	//ingelogd
			echo '<li><a href="'.$oWebsite->get_url_page("account_management").'">'.$this->website_object->t("main.account").'</a></li>';
			echo '<li><a href="'.$oWebsite->get_url_page("log_out").'">'.$this->website_object->t("main.log_out").'</a></li>';
		}
		else
		{
			// Not logged in
                        if($oWebsite->get_sitevar("userscancreateaccounts")) {
                            // Show account creation link
                            echo '<li><a href="'.$oWebsite->get_url_page("create_account").'">'.$this->website_object->t("main.create_account").'</a></li>';
                        }
                        echo '<li><a href="'.$oWebsite->get_url_page("log_in").'">'.$this->website_object->t("main.log_in").'</a></li>';	
		}
	}
	
	public function echo_breadcrumbs()
	{
		$oWebsite = $this->website_object;
		
		echo <<<EOT
			<a href="http://www.leiden.edu/" class="first">Leiden University</a>
			<a href="http://www.research.leiden.edu/">Research Portal</a>
			<a href="http://www.research.leiden.edu/research-profiles/">Leiden Research Profiles</a>
			<a href="{$oWebsite->get_url_main()}">Bioscience</a>
EOT;
		//Nog de laatste link?
		if($oWebsite->get_pagevar('file')!='home') 
		{ 
			echo '<a href="#">'.$this->get_page_shorttitle().'</a>';
		}
	}
			
	public function echo_copyright()
	{
		echo $this->website_object->get_sitevar("copyright");
	}
	
	public function echo_menu()
	{
		$oWebsite = $this->website_object;//afkorting
		$oMenu = new Menu($oWebsite,$oWebsite->get_database());
		echo $oMenu->get_menu_top();
		unset($oMenu);
	}
	
	//Geeft de module weer
	public function echo_page()
	{
		$this->website_object->echo_page_content();
	}
	
	//Geeft een zoekformulier weer
	public function echo_search_form()
	{
		$oWebsite=$this->website_object;
		
		//Zoekwoord
		$keyword = "";
		if(isset($_REQUEST['searchbox'])) $keyword =  htmlentities($_REQUEST['searchbox']);
		
		echo '<form id="searchform" name="searchform" action="'.$oWebsite->get_url_main().'" method="get">';
		echo '<input type="hidden" name="p" value="search" />';
		echo '<input type="search" size="21" name="searchbox" id="searchbox" value="'.$keyword.'" />';
		echo '<input type="submit" class="button" value="'.$oWebsite->t("main.search").'" name="searchbutton" id="searchbutton" />';
		echo '</form>';
	}
	
	//Geeft de widgets weer. Geldige area's: 0, 1, 2, ... , 100 (voor backstage), 101, 102, ...
	public function echo_widgets($area)
	{	//nu nog hard-coded, maar later moet hier een mooi systeem komen
	
		$oWebsite = $this->website_object;//afkorting
		$oDB = $oWebsite->get_database();
		
		///////////NIEUWE WIDGETCODE
		/*
		$oWidgets = $this->widgets_object;
		$oWidgets->echo_widgets_sidebar($area);
		///////////
		*/
		
		///////////OUDE WIDGETCODE
		if($area==0)
		{
			//ARTIKELEN
			$oArticles = new Articles($oWebsite,$oDB);
			$oCategories = new Categories($oWebsite,$oDB);
			
			foreach($oWebsite->get_sitevar("sidebarcategories") as $category)
			{
				echo "<h2>".$oCategories->get_category_name($category)."</h2>";
				echo $oArticles->get_articles_list_category($category,false,false,4);//opgegeven categorie, alleen die categorie, geen metadata, max 4 artikelen
			}
			
			unset($oWebsite,$oDB,$oArticles,$oCategories); //opruiming
		}
		if($area==1)
		{
			//LINKS
				echo "<h2>{$oWebsite->t('main.link')}</h2>";
				$oMenu = new Menu($oWebsite,$oDB);
				echo $oMenu->get_menu_sidebar();
							
			//KALENDER
				//Geeft bij phpark kalender weer
				if($oWebsite->get_sitevar('theme')=='phpark')
				{
					$oCal = new Calendar($oWebsite,$oDB);
					echo '<h3>'.$oWebsite->t("calendar.calendar_for").' '.strftime('%B').' '.date('Y').'</h3>';//huidige maand en jaar
					echo $oCal->get_calendar(291);
					echo "\n".'<p> <a class="arrow" href="'.$oWebsite->get_url_page("calendar").'">'.$oWebsite->t("calendar.calendar_for_twelve_months").'</a> </p>';//link voor jaarkalender
				}
				unset($oDB,$oCategories,$oMenu,$oArticles,$oCal);
			
			//TWEETS
				if($oWebsite->get_sitevar('twitter')!="")
				{
					$twitter = $oWebsite->get_sitevar('twitter');
					echo <<<TWITTER
						<script src="http://widgets.twimg.com/j/2/widget.js"></script>
						<script>
							new TWTR.Widget({
							  version: 2,
							  type: 'search',
							  search: '{$twitter[2]}',
							  interval: 30000,
							  title: '{$twitter[1]}',
							  subject: '{$twitter[0]}',
							  width: 290,
							  height: 300,
							  theme: {
							    shell: {
							      background: '#9a9a79',
							      color: '#ffffff'
							    },
							    tweets: {
							      background: '#d6d6c3',
							      color: '#444444',
							      links: '#6b966b'
							    }
							  },
							  features: {
							    scrollbar: false,
							    loop: true,
							    live: true,
							    behavior: 'default'
							  }
							}).render().start();
						</script>
TWITTER;
				} 
		}
		///////////*/
		
	}
	
	
	
	//Geeft de titel terug die boven aan de pagina, in de header, moet worden weergegeven. De paginatitel zit ingesloten in echo_page()
	public function get_page_title()
	{
		return $this->website_object->get_pagevar('title');
	}
	
	//Geeft de titel terug die boven aan de pagina, in de header, moet worden weergegeven. De paginatitel zit ingesloten in echo_page()
	public function get_page_shorttitle()
	{
		return $this->website_object->get_pagevar('shorttitle');
	}
	
	//Geeft het type pagina terug, "NORMAL", "NOWIDGETS" of "BACKSTAGE"
	public function get_page_type()
	{
		return $this->website_object->get_pagevar('type');
	}
	
	//Geeft de naam van de site terug
	public function get_site_title()
	{
		return $this->website_object->get_sitevar('title');
	}
	
	//Geeft de map van de scripts terug als uri
	public function get_uri_scripts()
	{
		return $this->website_object->get_uri_scripts();
	}
	
	public function get_url_scripts()
	{
		return $this->website_object->get_url_scripts();
	}
	
	public function get_uri_theme()
	{
		return $this->website_object->get_uri_themes().$this->website_object->get_sitevar("theme")."/";
	}
	
	public function get_url_theme()
	{
		return $this->website_object->get_url_themes().$this->website_object->get_sitevar("theme")."/";
	}
}
?>