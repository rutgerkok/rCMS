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

//DATABASE SETTINGS
$this->config['database_location'] = 'localhost';//Location of mysql server, for example: 'localhost' or 'mysql://example.net'
$this->config['database_name'] = 'rkok';//name of database, for example 'website' or 'userdatabase'
$this->config['database_user'] = 'root';//your database username, for example 'root' or 'username'
$this->config['database_password'] = '';//your database password, for example '' or 'rgo93ly69h' <-- that is just a randowm password, which I don't use
$this->config['database_table_prefix'] = '';//a prefix, if you are having table-name-conflicts

//PATHS
//allows you to split local and remote site base uri en url
$this->config['local_uri'] = 'C:/xampp/htdocs/';
$this->config['local_url'] = 'http://localhost/';
$this->config['external_uri'] = '/home/rkok/domains/rutgerkok.nl/public_html/';
$this->config['external_url'] = 'http://www.rutgerkok.nl/';

?>