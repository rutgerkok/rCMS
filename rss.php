<?php

namespace Rcms\Core;

use DateTime;

// Correct header
header("Content-type: application/rss+xml");

// Report all errors
error_reporting(E_ALL);

// Valid HTML please
ini_set('arg_separator.output', '&amp;');

// Classloader
spl_autoload_register(function($fullClassName) {
    $class = str_replace('\\', '/', subStr($fullClassName, strLen("Rcms\\")));

    // Try to see if it's a class in the library
    if (file_exists('src/' . $class . '.php')) {
        require_once('src/' . $class . '.php');
    }
});

// Objects
$oWebsite = new Website();
$oArticles = new ArticleRepository($oWebsite);

// Get category
$category_id = $oWebsite->getRequestInt("category");

// Get the data
$articles = $oArticles->getArticlesData($category_id, 15);

// Parse it
$textToDisplay = '';
if ($articles) {
    foreach ($articles as $article) {
        $pubdate = $article->created->format(DateTime::RSS);
        $textToDisplay.="<item>\n";
        $textToDisplay.="  <title>" . htmlSpecialChars($article->title) . "</title>\n";
        $textToDisplay.="  <link>" . $oWebsite->getUrlPage('article', $article->id) . "</link>\n";
        $textToDisplay.="  <description>" . htmlSpecialChars($article->intro) . "</description>\n";
        $textToDisplay.="  <pubDate>" . htmlSpecialChars($pubdate) . "</pubDate>\n";
        $textToDisplay.="  <author>" . htmlSpecialChars($article->author) . "</author>\n";
        $textToDisplay.="  <image>" . htmlSpecialChars($article->featuredImage) . "</image>\n";
        $textToDisplay.="  <category>" . htmlSpecialChars($article->category) . "</category>\n";
        $textToDisplay.="</item>\n\n";
    }
}
unset($article, $articles);

// Show it
echo '<?xml version="1.0" encoding="UTF-8" ?>';
?>

<rss version="2.0">
    <channel>
        <title><?php echo htmlSpecialChars($oWebsite->getConfig()->get('title')) ?></title>
        <link><?php echo htmlSpecialChars($oWebsite->getUrlMain()) ?></link>
        <?php
        echo $textToDisplay;
        ?>
    </channel>
</rss>
