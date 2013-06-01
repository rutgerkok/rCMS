<?php
// Correct header
header("Content-type: application/rss+xml");

// Site settings
session_start();
ini_set('arg_separator.output', '&amp;');

function __autoload($klasse) {
    // Load classes automatically
    require_once('code/classes/class_' . strtolower($klasse) . '.php');
}

// Objects
$oWebsite = new Website();
$oArticles = new Articles($oWebsite);

// Get category
$category_id = $oWebsite->get_request_int("category");

// Get the data
$articles = $oArticles->get_articles_data($category_id, 15);

// Parse it
$text_to_display = '';
if ($articles) {
    foreach ($articles as $article) {
        $pubdate = date('r', strtotime($article->created));
        $text_to_display.="<item>\n";
        $text_to_display.="  <title>" . htmlspecialchars($article->title) . "</title>\n";
        $text_to_display.="  <link>" . $oWebsite->get_url_page('article', $article->id) . "</link>\n";
        $text_to_display.="  <description>" . htmlspecialchars($article->intro) . "</description>\n";
        $text_to_display.="  <pubDate>" . $pubdate . "</pubDate>\n";
        $text_to_display.="  <author>" . htmlspecialchars($article->author) . "</author>\n";
        $text_to_display.="  <image>" . $article->featured_image . "</image>\n";
        $text_to_display.="  <category>" . $article->category . "</category>\n";
        $text_to_display.="</item>\n\n";
    }
}
unset($article, $articles);

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
