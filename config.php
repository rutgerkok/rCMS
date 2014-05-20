<?php
/*
 * The main configuration file.
 * 
 */

// Only execute if rCMS is loaded
if(!defined("WEBSITE")) die();

//DATABASE SETTINGS
$this->config['database_location'] = 'localhost'; // Location of mysql server, for example: 'localhost' or 'mysql://example.net'. Prefix with p: to make connection pernament.
$this->config['database_name'] = 'rkok'; // Name of database, for example 'website' or 'userdatabase'
$this->config['database_user'] = 'root'; // Your database username, for example 'root' or 'username'
$this->config['database_password'] = ''; // Your database password, for example '' or 'rgo93ly69h' <-- that is just a randowm password, which I don't use
$this->config['database_table_prefix'] = ''; // A prefix, if you are having table-name-conflicts

//PATHS
$this->config['uri'] = __DIR__ . '/'; // Should be fine
$this->config['url'] = '/'; // Change this to the URL of your site
// CKEditor path. Leave blank to disable CKEditor, leaving you with a simple textfield.
$this->config['ckeditor_url'] = $this->config['url'] . 'ckeditor/';
// CKFinder path. Leave blank to disable CKFinder.
$this->config['ckfinder_url'] = $this->config['url'] . 'ckfinder/';
?>