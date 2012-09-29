<?php
// Site settings
error_reporting(E_ALL);
session_start();
ini_set('arg_separator.output','&amp;'); 
function __autoload($klasse)
{	//automatisch laden van klassen
	require_once('code/class_'.strtolower($klasse).'.php');
}

// Objects
$oWebsite = new Website();
$oArticles = new Articles($oWebsite,$oWebsite->get_database());

// Get category
$category = isset($_REQUEST['category'])? (int) $_REQUEST['category']: -1;

// Show everthing from that category (not=0) or everything but that category (not=1)
if(isset($_REQUEST['not']))
{
	$not = ($_REQUEST['not']==1)? 1 : 0;
}
else
{
	$not = 1;
}

// Get the data
$result = $oArticles->get_articles_data();

// Parse it
$text_to_display = '';
if($result)
{
	foreach($result as $row)
	{
		list($id,$title,$intro,$featured_image,$created, $last_edited,$article_category,$author,$pinned,$hidden) = $row;
		$pubdate = date('r',strtotime($created));
		$text_to_display.="<item>\n";
		$text_to_display.="  <title>".htmlentities($title)."</title>\n";
		$text_to_display.="  <link>".$oWebsite->get_url_page('article',$id)."</link>\n";
		$text_to_display.="  <description>".htmlentities($intro)."</description>\n";
		$text_to_display.="  <pubDate>".$pubdate."</pubDate>\n";
		$text_to_display.="</item>\n\n";
	}
}

// Show it
echo '<?xml version="1.0" encoding="UTF-8" ?>';
?>

<rss version="2.0">
	<channel>
		<title><?php echo $oWebsite->get_sitevar('title') ?></title>
		<link><?php echo $oWebsite->get_url_main() ?></link>
		<?php
			echo $text_to_display;
		?>
	</channel>
</rss>
