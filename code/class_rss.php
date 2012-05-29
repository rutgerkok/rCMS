<?php //STAAT OP HET PUNT OM VERWIJDERD TE WORDEN
class Rss
{
	/*
	 * Alles met artikelen: laat een overzicht zien van de nieuwste artikelen, of alleen een simpele lijst. Een artikel bekijken is ook mogelijk.
	 * METHODES
	 * - get_articles_list_category($category,$not = false,$metainfo = true,$lines_in_metainfo = false) - laat alle artikelen zien die (niet = arg1) tot de opgegeven categorie(=arg0) behoren. arg2 zorgt voor al dan niet metadata
	 * - get_article($id) - laat het artikel zien met de opgegeven id (=arg0)
	 * - get_articles_search($keywordunprotected,$page) - arg0 zoekwoord, arg1 het paginanummer
	 * - get_articles_archive($year = -1) arg0 het jaar, laat weg voor huidige jaar
	 */
	
	
	protected $website_object;
	protected $database_object;
	protected $category_object;//nodig voor get_articles_archive
	
	function __construct($website_object,$database_object,$category_object=null)
	{
		$this->website_object = $website_object;
		$this->database_object = $database_object;
		$this->category_object = $category_object;
	}
	
	function get_article($id)
	{
		
	}
	
///////////////////////////////////////////		
	function get_articles_list_category($category,$not = false)
	{
		$oDB = $this->database_object; //afkorting
		$oWebsite = $this->website_object; //afkorting
		
		$category = (int) $category;//maak categorie veilig voor in gebruik query;
		
		$sql
		//resultaat ophalen
		if($not)
		{	//alles behalve deze categorie
			$result = $oDB->query("SELECT artikel_id, artikel_titel, artikel_intro, artikel_afbeelding, artikel_gemaakt, artikel_bewerkt, categorie_naam, gebruiker_naam, artikel_gepind, artikel_verborgen FROM `artikel` LEFT JOIN `categorie` USING ( categorie_id ) LEFT JOIN `gebruikers` USING ( gebruiker_id ) WHERE categorie_id !=$category AND artikel_verborgen <= $logged_in ORDER BY artikel_gepind DESC, artikel_id DESC LIMIT 0 , 10");
		}
		else
		{	//niks behalve deze categorie
			$result = $oDB->query("SELECT artikel_id, artikel_titel, artikel_intro, artikel_afbeelding, artikel_gemaakt, artikel_bewerkt, categorie_naam, gebruiker_naam, artikel_gepind, artikel_verborgen FROM `artikel` LEFT JOIN `categorie` USING ( categorie_id ) LEFT JOIN `gebruikers` USING ( gebruiker_id ) WHERE categorie_id = $category AND artikel_verborgen <= $logged_in ORDER BY artikel_gepind DESC, artikel_id DESC LIMIT 0 , 10");
		}
		
		//artikelen ophalen
		if($result&&$oDB->rows($result)>0)
		{
			$return_value = '';
			while(list($id,$title,$intro,$featured_image,$created, $last_edited,$article_category,$author,$pinned,$hidden) = $oDB->fetch($result))
			{
				$pubdate = date('r',strtotime($created));
				$return_value.="<item>\n";
				$return_value.="<title>".htmlspecialchars($title)."</title>\n";
				$return_value.="<link>index.php?p=view_article&amp;id=$id</link>\n";
				$return_value.="<description>".htmlspecialchars($intro)."</description>\n";
				$return_value.="<pubDate>".htmlspecialchars($pubdate)."</pubDate>\n";
				$return_value.="</item>\n\n";
			}
			
			return $return_value;
		}
		else
		{
			return '';
		}
	}
	
	
///////////////////////////////////////////	
	function get_articles_search($keywordunprotected,$page)
	{
		
	}
	
	
}

?>