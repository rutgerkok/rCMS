<?php
if(!isset($this)) die(); //security

$this->config['theme'] =  "phpark";//name of theme directory
$this->config['title'] =  "Test site van rCMS";//title of site
$this->config['hometitle'] =  "Test site van rCMS";
$this->config['sidebarcategories'] = array(2,5);//categories to display in sidebar
$this->config['locales'] = array('nl_NL', 'nl', 'du','nld','dutch');//locales
$this->config['password'] = "";//password to view your site
$this->config['twitter'] = array("Twitter","Test van twitter",'CMS');//Twitter feed! Title-description-search
$this->config['language'] = "en";
$this->config['userscancreateaccounts'] = true; // Whether visitors can create accounts

//DATABASE SETTINGS
$this->config['database_location'] = 'localhost'; // Location of mysql server, for example: 'localhost' or 'mysql://example.net'
$this->config['database_name'] = 'rkok'; // Name of database, for example 'website' or 'userdatabase'
$this->config['database_user'] = 'root'; // Your database username, for example 'root' or 'username'
$this->config['database_password'] = ''; // Your database password, for example '' or 'rgo93ly69h' <-- that is just a randowm password, which I don't use
$this->config['database_table_prefix'] = ''; // A prefix, if you are having table-name-conflicts

//PATHS
//allows you to split local and remote site base uri en url
$this->config['uri'] = 'C:/xampp/htdocs/';
$this->config['url'] = 'http://localhost/';

?>