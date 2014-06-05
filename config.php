<?php
/*
 * The main configuration file.
 * 
 */

/**** Database settings ****/

// Location of mysql server, usually 'localhost'
$this->config['database_location'] = 'localhost';

// Name of database, for example 'website'
$this->config['database_name'] = 'rkok';

// Your database username, for example 'root' or 'username'
$this->config['database_user'] = 'root';

// Your database password, for example 'rgo93ly69h'
$this->config['database_password'] = '';

// A prefix. No two installations of rCMS on a single database may have the same
// prefix
$this->config['database_table_prefix'] = 'rcms_'; 

/**** Paths ****/
// Note: the trailing slash is required in each path.

// Where the files of rCMS are stored. The 'src' and 'content' directories must
// be subdirectories of this directory.
// Note: there is usually no need to change this setting. The default setting,
// "__DIR__ . '/';", assumes that rCMS is installed in the same directory as the
// config file.
$this->config['uri'] = __DIR__ . '/';

// Set this the URL of the website. Setting it to '/' will make the browser use
// the home page of the website. If
$this->config['url'] = '/';

// CKEditor path. Leave blank to disable CKEditor, leaving you with a simple
// textfield.
$this->config['ckeditor_url'] = $this->config['url'] . 'ckeditor/';

// CKFinder path. Leave blank to disable CKFinder.
$this->config['ckfinder_url'] = $this->config['url'] . 'ckfinder/';
