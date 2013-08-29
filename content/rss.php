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
$category_id = $oWebsite->getRequestInt("category");

// Get the data
$articles = $oArticles->getArticlesData($category_id, 15);

// Parse it
$textToDisplay = '';
if ($articles) {
    foreach ($articles as $article) {
        $pubdate = date('r', strtotime($article->created));
        $textToDisplay.="<item>\n";
        $textToDisplay.="  <title>" . htmlSpecialChars($article->title) . "</title>\n";
        $textToDisplay.="  <link>" . $oWebsite->getUrlPage('article', $article->id) . "</link>\n";
        $textToDisplay.="  <description>" . htmlSpecialChars($article->intro) . "</description>\n";
        $textToDisplay.="  <pubDate>" . $pubdate . "</pubDate>\n";
        $textToDisplay.="  <author>" . htmlSpecialChars($article->author) . "</author>\n";
        $textToDisplay.="  <image>" . $article->featuredImage . "</image>\n";
        $textToDisplay.="  <category>" . $article->category . "</category>\n";
        $textToDisplay.="</item>\n\n";
    }
}
unset($article, $articles);

// Show it
echo '<?xml version="1.0" encoding="UTF-8" ?>';
?>

<rss version="2.0">
    <channel>
        <title><?php echo $oWebsite->getSiteSetting('title') ?></title>
        <link><?php echo $oWebsite->getUrlMain() ?></link>
<?php
echo $textToDisplay;
?>
    </channel>
</rss>
