<?php
/*
 * The main configuration file.
 *
 * Save a copy of this file as config.php, then edit the settings in that file.
 */

/**** Database settings ****/

// Location of mysql server, usually 'localhost'
$this->config['database_location'] = 'localhost';

// Name of database, for example 'website'
$this->config['database_name'] = 'website';

// Your database username, for example 'root' or 'username'
$this->config['database_user'] = 'root';

// Your database password, for example 'rgo93ly69h'
$this->config['database_password'] = 'rgo93ly69h';

// A prefix. No two installations of rCMS on a single database may have the same
// prefix
$this->config['database_table_prefix'] = 'rcms_'; 

/**** Paths ****/
// Note: the trailing slash is **required** in each and every path.
// __DIR__  simply represents the folder where this config file is stored.

// The location of the folder with all the extensions. You can point multiple
// sites to the same extensions folder, and still have different active
// extensions in each site.
$this->config['uri_extend'] = __DIR__ . '/extend/';

// The location of the folder with all publicy accessable files. You can move
// this to any location you like on your website, but 
$this->config['uri_web'] = __DIR__ . '/web/';

// Set this the URL of the website. This can for example be
// http://www.example.com/ . You can leave out the protocol and domain parts, so
// /foo/ is equal to to http://www.example.com/foo/ .
// When changing this, also go to that folder and change the environment.php
// file there to point to the enviroment.php in this folder.
$this->config['url_web'] = '/';

// When set to true the index.php part of the links on the site is removed, so
// http://www.example.com/index.php/article/10 turns into
// http://www.example.com/article/10 .
// Requires the .htaccess file with mod_rewrite support enabled.
$this->config['url_rewrite'] = true;

// CKEditor path. Leave blank to disable CKEditor, leaving you with a simple
// textfield.
$this->config['url_ckeditor'] = $this->config['url_web'] . 'ckeditor/';

// CKFinder path. Leave blank to disable CKFinder.
$this->config['url_ckfinder'] = $this->config['url_web'] . 'ckfinder/';

// The location of the src and vendor folders is always __DIR__ . '/src/' and
// __DIR__ . '/vendor/', this cannot be changed.
