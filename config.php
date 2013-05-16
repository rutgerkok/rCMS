<?php
/*
 * The main configuration file. It is advisable to move this file one directory
 * up (outside the web root) so that you can be sure that the source can never
 * be viewed. PHP should normally prevent this from happening, but you can never
 * know.
 * 
 * If you do that, create a new config.php at the current location with the
 * following text. This will redirect rCMS.
 * 
 * <?php
 *      require("../config.php");
 * ?>
 * 
 */

// Only execute if rCMS is loaded
if(!isset($this)) die();

$this->config['theme'] =  "rkok";//name of theme directory
$this->config['title'] =  "Test site van rCMS";//title of site
$this->config['hometitle'] =  "Test site van rCMS";
$this->config['copyright'] = "Copyright 2013 - built with rCMS"; // Displayed on the botton of the page
$this->config['locales'] = array('en_US', 'en', 'english'); // Locales - use array('nl_NL', 'nl', 'du','nld','dutch') for Dutch
$this->config['password'] = ""; //Password needed to view your site
$this->config['language'] = "en"; // Directory name of the translations (code/translations/NAME/)
$this->config['userscancreateaccounts'] = true; // Whether visitors can create accounts
$this->config['fancy_urls'] = true; // Enables fancy URLs. Requires a .htaccess file like the one provided.

//DATABASE SETTINGS
$this->config['database_location'] = 'localhost'; // Location of mysql server, for example: 'localhost' or 'mysql://example.net'. Prefix with p: to make connection pernament.
$this->config['database_name'] = 'rkok'; // Name of database, for example 'website' or 'userdatabase'
$this->config['database_user'] = 'root'; // Your database username, for example 'root' or 'username'
$this->config['database_password'] = ''; // Your database password, for example '' or 'rgo93ly69h' <-- that is just a randowm password, which I don't use
$this->config['database_table_prefix'] = ''; // A prefix, if you are having table-name-conflicts

//PATHS
$this->config['uri'] = 'C:/xampp/htdocs/';
$this->config['url'] = 'http://localhost/';

?>