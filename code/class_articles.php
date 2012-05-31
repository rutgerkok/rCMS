<?php
class Articles
{
	/*
	 * Alles met artikelen: laat een overzicht zien van de nieuwste artikelen, of alleen een simpele lijst. Een artikel bekijken is ook mogelijk.
	 * METHODES
	 * - get_article_data($id) - geeft een array terug met informatie over het opgegeven artikel
	 * - get_articles_data($where_clausule) - geeft een array terug met informatie over de opgegeven artikelen (max 10 artikelen worden teruggegeven)
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
	
	function get_article_data($id)
	{
		$oDB = $this->database_object; //afkorting
		$oWebsite = $this->website_object; //afkorting
		
		$id = (int) $id;//maak id veilig voor in gebruik query;
		
		$sql = "SELECT artikel_titel, artikel_gemaakt, artikel_bewerkt, ";
		$sql.= "artikel_intro, artikel_afbeelding, artikel_inhoud, ";
		$sql.= "categorie_naam, gebruiker_naam, artikel_gepind, ";
		$sql.= "artikel_verborgen, artikel_reacties FROM `artikel` ";
		$sql.= "LEFT JOIN `categorie` USING ( categorie_id ) ";
		$sql.= "LEFT JOIN `gebruikers` USING ( gebruiker_id) ";
		$sql.= "WHERE artikel_id = $id ";
		
		$result = $oDB->query($sql);
		
		if($result&&$oDB->rows($result)==1)
		{
			//$title,$created, $last_edited,$intro,$featured_image,$body,$article_category, $author, $pinned, $hidden, $comments
			return $oDB->fetch($result);
		}
		else
		{
			return null;
		}
	}
	
	function get_articles_data($where_clausule="",$limit=9,$start=0)
	{	//WAARSCHUWING: ZORG DAT ER NIKS GEVAARLIJKS STAAT IN DE $where_clausule
		$oDB = $this->database_object; //afkorting
		$oWebsite = $this->website_object; //afkorting
		$logged_in = $oWebsite->logged_in(); //ingelogd? (nodig om de juiste artikelen op te halen)
		$limit = (int) $limit; //stuk veiliger
	
		$sql = "SELECT artikel_id, artikel_titel, artikel_intro, ";//haal id, titel, intro ...
		$sql.= "artikel_afbeelding, artikel_gemaakt, artikel_bewerkt, ";//... afbeelding, datums, ...
		$sql.= "categorie_naam, gebruiker_naam, artikel_gepind, artikel_verborgen ";//... categorie, auteur, gepind en verborgen op
		$sql.= "FROM `artikel` ";//uit de tabel artikel
		$sql.= "LEFT JOIN `categorie` USING ( categorie_id ) ";//join 1
		$sql.= "LEFT JOIN `gebruikers` USING ( gebruiker_id ) ";//join 2
		$sql.= "WHERE artikel_verborgen <= $logged_in ";//verborgen artikelen niet voor niet ingelogde gebruikers
		if(!empty($where_clausule)) $sql.= "AND $where_clausule ";//nog een extra voorwaarde?
		$sql.= "ORDER BY artikel_gepind DESC, artikel_gemaakt DESC ";//sorteren
		$sql.= "LIMIT $start , $limit";//nooit teveel
		
		$result = $oDB->query($sql);
		if($result&&$oDB->rows($result)>0)
		{
			while($row = $oDB->fetch($result))
			{
				$return_value[] = $row;
			}
			return $return_value;
		}
		else
		{
			return null;
		}
		
	}
	
	function get_article($id)
	{
		$oDB = $this->database_object; //afkorting
		$oWebsite = $this->website_object; //afkorting
		
		$id = (int) $id;//maak id veilig voor in gebruik query;
		$logged_in = $oWebsite->logged_in();
		$return_value = '';
		$article_data = $this->get_article_data($id);
		
		if($article_data!=null)
		{	
			list($title,$created, $last_edited,$intro,$featured_image,$body,$article_category, $author, $pinned, $hidden, $comments) = $article_data;
			unset($article_data);//ruimt op
			
			if(!$hidden || $logged_in)
			{
				
				$return_value.= "<h2>$title</h2>";
			
				$return_value.= '<div class="artikelzijkolom">';
					//zijbalk
					if(!empty($featured_image)) $return_value.= "<p><img src=\"$featured_image\" alt=\"$title\" /></p>";
					$return_value.= '<p class="meta">';
					$return_value.= $oWebsite->translations[37]." <br />&nbsp;&nbsp;&nbsp;".$created;
					if($last_edited) $return_value.= " <br />  ".$oWebsite->translations[38]." <br />&nbsp;&nbsp;&nbsp;".$last_edited . "";
					$return_value.= " <br /> ".$oWebsite->translations[12].": ".$article_category;
					$return_value.= " <br /> ".$oWebsite->translations[39].": $author";//auteur
					if($pinned) $return_value.= "<br />" . $oWebsite->translations[23] . " ";//gepind
					if($hidden) $return_value.= "<br />" . $oWebsite->translations[48];//verborgen
					if($logged_in && $comments) $return_value.= "<br />" . $oWebsite->translations[68];//reacties
					$return_value.= '</p>';
					if($logged_in) $return_value.= "<p style=\"clear:both\">";
					if($logged_in) $return_value.= '&nbsp;&nbsp;&nbsp;<a class="arrow" href="index.php?p=edit_article&amp;id='.$id.'">'.$oWebsite->translations[0].'</a>&nbsp;&nbsp;'. //edit
								'<a class="arrow" href="index.php?p=delete_article&amp;id='.$id.'">'.$oWebsite->translations[1].'</a>'; //delete
					if($logged_in) $return_value.= "</p>";
					if($comments) $return_value.= <<<EOT
					<!-- AddThis Button BEGIN -->
					<!-- AddThis Button BEGIN -->
					<div class="addthis_toolbox addthis_default_style ">
					<a class="addthis_button_facebook_like"></a>
					<br />
					<br />
					<a class="addthis_button_tweet"></a>
					<br />
					<br />
					<a class="addthis_button_google_plusone" g:plusone:size="medium"></a>
					</div>
					<script type="text/javascript" src="http://s7.addthis.com/js/250/addthis_widget.js#pubid=xa-4ee269aa4314962e"></script>
					<!-- AddThis Button END -->
EOT;
				$return_value.= '</div>';
				
				
				
				$return_value.= '<div class="artikel">';
					//artikel
					if($logged_in && $hidden) $return_value.= '<p class="meta">'.$oWebsite->translations[50]."<br /> \n".$oWebsite->translations[49].'</p>';
					$return_value.= '<p class="intro">'.$intro.'</p>';
					$return_value.= $body;
					//reacties
					if($comments)
					{
						
						$sql = "SELECT reactie_id, reactie_email, reactie_naam, reactie_gemaakt, reactie_inhoud, gebruiker_naam FROM `reacties` LEFT JOIN `gebruikers` USING ( gebruiker_id ) WHERE artikel_id = $id";
						$result = $oDB->query($sql);
						if($oDB->rows($result)>0)
						{	//geef reacties weer
							$return_value.="<h3 class=\"notable\">".$oWebsite->translations[65]." (".$oDB->rows($result).")</h3>";
							$return_value.="<p><a href=\"index.php?p=add_comment&amp;id=$id\" class=\"arrow\">".$oWebsite->translations[70]."</a></p>";//link: reageer
							
							while(list($comment_id,$comment_email,$comment_name,$comment_date,$comment,$account_name)=$oDB->fetch($result)) //geef alle reacties weer
							{	//geef reactie weer
								$comment_date = str_replace(' 0',' ',strftime("%A %d %B %Y %X",strtotime($comment_date)));
								if(empty($comment_name)) $comment_name = $account_name;//ingelogde gebruikers correct weergeven
								$return_value.= "<h3>$comment_name ($comment_date)</h3>";//naam en datum
								$return_value.= "<p>";
								if($logged_in && !empty($comment_email)) $return_value.= "<a href=\"mailto:$comment_email\">$comment_email</a> &nbsp;&nbsp;&nbsp;";//mail
								if($logged_in) $return_value.= "<a class=\"arrow\" href=\"index.php?p=delete_comment&amp;id=$comment_id\">{$oWebsite->translations[1]}</a> </p>";//verwijder
								$return_value.= "<p>".nl2br($comment)."</p>";
							}
							
							$return_value.="<p><a href=\"index.php?p=add_comment&amp;id=$id\" class=\"arrow\">".$oWebsite->translations[70]."</a></p>";//link: reageer
						}
						else
						{
							$return_value.="<h3 class=\"notable\">".$oWebsite->translations[65]."</h3>";
							$return_value.= "<p><em>".$oWebsite->translations[69]."</em></p>";//geen reacties gevonden
							$return_value.="<p><a href=\"index.php?p=add_comment&amp;id=$id\" class=\"arrow\">".$oWebsite->translations[70]."</a></p>";//link: reageer
						}
					}
				$return_value.= '</div>';
				
			}
			else
			{
				$oWebsite->add_error('Article is not public.');
			}
		}
		else
		{
			$oWebsite->add_error('Article does not exist.');
		}
		
		return $return_value;
	}
	
///////////////////////////////////////////	
	
		
	function get_articles_list_category($categories,$not = false,$metainfo = true,$limit = 9)
	{
		$oDB = $this->database_object; //afkorting
		$oWebsite = $this->website_object; //afkorting
		$logged_in = $oWebsite->logged_in();//ingelogd? (nodig voor links om te bewerken)
		
		if(!is_array($categories))
		{	//maak een array
			$category_id = $categories;
			unset($categories);
			$categories[0] = $category_id;
		}
		
		$where_clausule = '';
		foreach($categories as $count=>$category_id)
		{
			$category_id = (int) $category_id;//beveiliging
			
			if($not)
			{	//alles behalve deze categorie
				if($count>0) $where_clausule.=" AND ";
				$where_clausule.="categorie_id != $category_id";
			}
			else
			{	//niks behalve deze categorie
				if($count>0) $where_clausule.=" OR ";
				$where_clausule.="categorie_id = $category_id";
			}
		}
		
		//haal resultaten op
		$result = $this->get_articles_data($where_clausule,$limit);
			
		//verwerk resultaten
		if($result)
		{
			$return_value = '';
			$category = ($not)? 0:$categories[0];
			
			
			if($logged_in)
			{
				if($not)
					{$return_value.= '<p><a href="index.php?p=edit_article&amp;id=0" class="arrow">'.$oWebsite->translations[9].'</a></p>';}
				else
					{$return_value.= '<p><a href="index.php?p=edit_article&amp;id=0&amp;article_category='.$category.'" class="arrow">'.$oWebsite->translations[9].'</a></p>';}
			}
			foreach($result as $row)
			{
				list($id,$title,$intro,$featured_image,$created, $last_edited,$article_category,$author,$pinned,$hidden) = $row;
				$return_value.= "\n\n<div class=\"artikelintro\">";
				$return_value.= "<h3>$title</h3>";
				if($metainfo) $return_value.= '<p class="meta">';
				if($metainfo) $return_value.= $oWebsite->translations[37]." ".$created .' - ';//gemaakt op
				if($metainfo && $last_edited) $return_value.= lcfirst($oWebsite->translations[38])." ".$last_edited . '<br />';//laatst bewerkt op
				if($metainfo) $return_value.= $oWebsite->translations[12].": ".$article_category;//categorie
				if($metainfo) $return_value.= " - ".$oWebsite->translations[39].": $author";//auteur
				if($metainfo && $pinned) $return_value.= " - ".$oWebsite->translations[23];//vastgepind?
				if($metainfo && $hidden) $return_value.= " - ".$oWebsite->translations[48];//verborgen?
				if($metainfo) $return_value.= '</p>';
				
				$return_value.= '<p >';
				if(!empty($featured_image)) $return_value.= "<img src=\"$featured_image\" alt=\"$title\" style=\"float:left;max-width:100px\" />";
				$return_value.= $intro;
				
				$return_value.= "<br />";
				$return_value.= '<a class="arrow" href="index.php?p=view_article&amp;id='.$id.'">'.$oWebsite->translations[8].'</a>';
				if($logged_in) $return_value.= '&nbsp;&nbsp;&nbsp;<a class="arrow" href="index.php?p=edit_article&amp;id='.$id.'">'.$oWebsite->translations[0].'</a>&nbsp;&nbsp;'. //edit
							'<a class="arrow" href="index.php?p=delete_article&amp;id='.$id.'">'.$oWebsite->translations[1].'</a>'; //delete
				$return_value.= "</p>";
				$return_value.= "</div>";
			}
			
			if($logged_in)
			{
				if($not)
					{$return_value.= '<p> <a href="index.php?p=edit_article&amp;id=0" class="arrow">'.$oWebsite->translations[9].'</a></p>';}//maak nieuw artikel
				else
					{$return_value.= '<p> <a href="index.php?p=edit_article&amp;id=0&amp;article_category='.$category.'" class="arrow">'.$oWebsite->translations[9].'</a></p>';}//maak nieuw artikel in categorie
			}
			$return_value.='<p><a href="index.php?p=archive&amp;year='.date('Y').'&amp;cat='.$category.'" class="arrow">'.$oWebsite->translations[36].'</a></p>';//archief
			return $return_value;
		}
		else
		{
			
			if($logged_in)
			{
				if($not)
					{ return('<p> <a href="index.php?wide=1&amp;p=edit_article&amp;id=0" class="arrow">'.$oWebsite->translations[9].'</a></p>'); }//maak nieuw artikel
				else
					{ return('<p> <a href="index.php?wide=1&amp;p=edit_article&amp;id=0&amp;article_category='.$category.'" class="arrow">'.$oWebsite->translations[9].'</a></p>'); }//maak nieuw artikel in categorie
			}
			else
			{
				return '';
			}
		}
	}
	
	
///////////////////////////////////////////	
	function get_articles_search($keywordunprotected,$page)
	{
		$oDB = $this->database_object; //afkorting
		$oWebsite = $this->website_object; //afkorting
		$logged_in = $oWebsite->logged_in();//ingelogd? (nodig voor links om te bewerken)
		$metainfo = true;//waarom zou je anders willen?
		$articles_per_page = 5;//vijf resultaten per pagina
		$start = ($page-1)*$articles_per_page;
		
		$keyword = $oDB->escape_data($keywordunprotected);//maak zoekwoord veilig voor in gebruik query;
		
		if(strlen($keyword)<3)
		{
			$oWebsite->add_error('Search term is too short!');
			return '<p>Please search again. Minimum length is three characters.</p>';
		}
		
		
		//aantal ophalen
		$sql = "SELECT count(*) FROM `artikel` WHERE artikel_titel LIKE '%$keyword%' OR artikel_intro LIKE '%$keyword%' OR artikel_inhoud LIKE '%$keyword%' ";
		$result = $oDB->query($sql);
		$result = $oDB->fetch($result);
		$resultcount = (int) $result[0];
		
		
		
		//resultaat ophalen
		$result = $this->get_articles_data(
					"(artikel_titel LIKE '%$keyword%' OR artikel_intro LIKE '%$keyword%' OR artikel_inhoud LIKE '%$keyword%')",
					$articles_per_page,
					$start
				);
	
		//artikelen ophalen
		$return_value = '';

		if($result)
		{
			//Geef aantal resultaten weer
			$return_value.= ($resultcount==1)? "<p>".$oWebsite->translations[101]."</p>": "<p>".str_replace('*',$resultcount,$oWebsite->translations[100])."</p>";
			
			$i = 0;//vijf keer artikel weergeven, zesde keer alleen linkje met meer
			
			//paginanavigatie
			$return_value.= '<p class="lijn">';
			if($page>1) $return_value.= ' <a class="arrow" href="index.php?p=search&amp;searchbox='.urlencode($keywordunprotected).'&amp;page='.($page-1).'">'.$oWebsite->translations[103].'</a> ';//vorige pagina
			$return_value.= $oWebsite->translations[10].' '.$page.' '.$oWebsite->translations[102].' '.ceil($resultcount/$articles_per_page);//pagina X van Y
			if($resultcount>$start+$articles_per_page) $return_value.= ' <a class="arrow" href="index.php?p=search&amp;searchbox='.urlencode($keywordunprotected).'&amp;page='.($page+1).'">'.$oWebsite->translations[104].'</a>';//volgende pagina
			$return_value.= '</p>';
			
			foreach($result as $row)
			{
				list($id,$title,$intro,$featured_image,$created, $last_edited,$article_category,$author,$pinned,$hidden) = $row;
				$return_value.= "\n\n<div class=\"artikelintro\">";
				$return_value.= "<h3>$title</h3>";
				if($metainfo) $return_value.= '<p class="meta">';
				if($metainfo) $return_value.= $oWebsite->translations[37]." ".$created .' - ';//gemaakt op
				if($metainfo && $last_edited) $return_value.= lcfirst($oWebsite->translations[38])." ".$last_edited . '<br />';//laatst bewerkt op
				if($metainfo) $return_value.= $oWebsite->translations[12].": ".$article_category;//categorie
				if($metainfo) $return_value.= " - ".$oWebsite->translations[39].": $author";//auteur
				if($metainfo && $pinned) $return_value.= " - ".$oWebsite->translations[23];//vastgepind?
				if($metainfo && $hidden) $return_value.= " - ".$oWebsite->translations[48];//verborgen?
				if($metainfo) $return_value.= '</p>';
				
				$return_value.= '<p >';
				if(!empty($featured_image)) $return_value.= "<img src=\"$featured_image\" alt=\"$title\" style=\"float:left;max-width:100px\" />";
				$return_value.= $intro;
				
				$return_value.= "<br />";
				$return_value.= '<a class="arrow" href="index.php?p=view_article&amp;id='.$id.'">'.$oWebsite->translations[8].'</a>';
				if($logged_in) $return_value.= '&nbsp;&nbsp;&nbsp;<a class="arrow" href="index.php?p=edit_article&amp;id='.$id.'">'.$oWebsite->translations[0].'</a>&nbsp;&nbsp;'. //edit
							'<a class="arrow" href="index.php?p=delete_article&amp;id='.$id.'">'.$oWebsite->translations[1].'</a>'; //delete
				$return_value.= "</p>";
				$return_value.= "</div>";
			}
			
			//paginanavigatie
			$return_value.= '<p class="lijn">';
			if($page>1) $return_value.= ' <a class="arrow" href="index.php?p=search&amp;searchbox='.urlencode($keywordunprotected).'&amp;page='.($page-1).'">'.$oWebsite->translations[103].'</a> ';//vorige pagina
			$return_value.= $oWebsite->translations[10].' '.$page.' '.$oWebsite->translations[102].' '.ceil($resultcount/$articles_per_page);//pagina X van Y
			if($resultcount>$start+$articles_per_page) $return_value.= ' <a class="arrow" href="index.php?p=search&amp;searchbox='.urlencode($keywordunprotected).'&amp;page='.($page+1).'">'.$oWebsite->translations[104].'</a>';//volgende pagina
			$return_value.= '</p>';
		}
		else
		{
			$return_value.='<p><em>'.$oWebsite->translations[86].'.</em></p>';//niets gevonden
		}
		
		return $return_value;
	}
///////////////////////////////////////////	
	function get_articles_archive($year = 0,$cat_display=0)
	{
		$oDB = $this->database_object; //afkorting
		$oWebsite = $this->website_object; //afkorting
		$oCats = $this->category_object; //afkorting
		
		$events=false;
		
		$logged_in = $oWebsite->logged_in();//ingelogd? (nodig voor links om te bewerken)
		$return_value = '';
		
		//CATEGORIE BEPALEN (bepaal welke categorieen moeten worden weergegeven)
		$cat_display = (int) $cat_display;
		
		//where clausule voor query
		$where = "`categorie_id`=$cat_display";
		if($cat_display==0)
		{
			$where = "1";//alles
		}
		//lijstje met categorieën maken
		$cat_list = $oCats->get_categories();
		
		//JAREN BEPALEN
		$startyear = date('Y');//vooralsnog, als de database zometeen iets anders meldt, wordt dat jaar gebruikt
		$endyear = date('Y');
		
		$year = (int) $year;
		if($year==0) { $year = (int) date('Y'); }
		
		
		$sql = "SELECT YEAR(`artikel_gemaakt`) FROM artikel WHERE $where ORDER BY `artikel_gemaakt` LIMIT 0,1";
		$result = $oDB->query($sql);
		
		if($result)
		{
			$result = $oDB->fetch($result);
			$result = $result[0];
			if($result>0) $startyear = $result;
		}
	
		//MENUBALK WEERGEVEN
		$return_value.= '<p class="lijn">';
		//categorie
			$return_value .= '<span style="width:5.5em;display:block;float:left">'.$oWebsite->translations[12].':</span> ';
			if($cat_display==0)
			{
				$return_value .= '<strong>'.$oWebsite->translations[41].' '.strtolower($oWebsite->translations[16]).'</strong> ';
			}
			else
			{
				$return_value .= '<a href="index.php?p=archive&amp;year='.$year.'&amp;events=0">'.$oWebsite->translations[41].' '.strtolower($oWebsite->translations[16]).'</a> ';
			}
			foreach($cat_list as $id=>$name)
			{
				if($id==$cat_display)
				{
					$return_value .= "<strong>$name</strong> ";
				}
				else
				{
					$return_value .= "<a href=\"index.php?p=archive&amp;year=$year&amp;cat=$id\">$name</a> ";
				}
			}
			
		//jaren
			$return_value .= '<br /><span style="width:5.5em;display:block;float:left">'.$oWebsite->translations[40].':</span>';
			for($i=$startyear;$i<=$endyear;$i++)
			{
				if($i==$year)
				{
					$return_value .= '<strong>'.$i.'</strong> ';
				}
				else
				{
					$return_value .= '<a href="index.php?p=archive&amp;cat='.$cat_display.'&amp;year='.$i.'">'.$i.'</a> ';
				}
			}

		$return_value.= '</p>';
		
		//RESULTATEN OPHALEN
		$sql= "SELECT artikel_id, artikel_titel, categorie_naam, MONTH(artikel_gemaakt) FROM `artikel` LEFT JOIN `categorie` USING ( categorie_id ) WHERE $where AND YEAR(`artikel_gemaakt`)=$year ORDER BY `artikel_gemaakt`";
		$result = $oDB->query($sql);
		if($result&&$oDB->rows($result)>0)
		{
			$lastmonth = -1;
			while(list($id,$title,$category,$month)=$oDB->fetch($result))
			{
				if($month!=$lastmonth)
				{	//nieuwe maand, start nieuwe alinea
					if(isset($tablestarted)) $return_value.="</table>\n";
					
					$return_value.="<br /><table style=\"width:100%;background:white\">\n";
					$return_value.="<tr><th colspan=\"3\">".ucfirst(strftime('%B',mktime(0,0,0,$month,1,$year)))."</th></tr>\n";
					$lastmonth = $month;
					$tablestarted = true;
				}
				
				
				if($logged_in)
				{
					 $return_value.="<tr> <td style=\"width:71%\"> <a class=\"arrow\" href=\"index.php?p=view_article&amp;id=$id\"> $title </a> </td>";
					 $return_value.= '<td style=\"width:16%\"> <a class="arrow" href="index.php?p=edit_article&amp;id='.$id.'">'.$oWebsite->translations[0].'</a>&nbsp; '.
								'<a class="arrow" href="index.php?p=delete_article&amp;id='.$id.'">'.$oWebsite->translations[1].'</a> </td>';
				}
				else
				{
					$return_value.="<tr><td style=\"width:87%\" colspan=\"2\"> <a class=\"arrow\" href=\"index.php?p=view_article&amp;id=$id\">$title</a> </td>";
				}
				$return_value.="<td style=\"width:12%\">$category</td>\n";				
				$return_value.="</tr>\n";
				
				
			}
			$return_value.='</table>';
		}
		else
		{
			if($year==date('Y')&&$cat_display==0)
			{
				$searchyear=$year-1;//niks gevonden? zoek dan in het vorige jaar of in alle categorieen
				$return_value.='<p>No articles were found in '.$year.'. <a href="index.php?p=archive&amp;year='.(date('Y')-1).'">Search all categories in '.(date('Y')-1).'</a>.</p>';
				
			}
			else
			{
				$return_value.='<p>No articles were found in '.$year.' in this category. <a href="index.php?p=archive&amp;year='.date('Y').'">Search all categories in '.date('Y').'</a>.</p>';
			}
			
		}
		return $return_value;
	}
	
	
}

?>