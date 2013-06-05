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

//DATABASE SETTINGS
$this->config['database_location'] = 'localhost'; // Location of mysql server, for example: 'localhost' or 'mysql://example.net'. Prefix with p: to make connection pernament.
$this->config['database_name'] = 'rkok'; // Name of database, for example 'website' or 'userdatabase'
$this->config['database_user'] = 'root'; // Your database username, for example 'root' or 'username'
$this->config['database_password'] = ''; // Your database password, for example '' or 'rgo93ly69h' <-- that is just a randowm password, which I don't use
$this->config['database_table_prefix'] = ''; // A prefix, if you are having table-name-conflicts

//PATHS
$this->config['uri'] = 'C:/xampp/htdocs/';
$this->config['url'] = 'http://localhost/';
// CKEditor path. Leave blank to leave out CKEditor, leaving you with a simple textfield.
$this->config['ckeditor_url'] = $this->config['url'] . 'ckeditor/';

?>