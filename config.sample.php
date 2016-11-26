<?php
// This is the main configuration file. This is a PHP file. That means that the
// first line must start with <?php, that all characters after // are ignored by
// the website and that all literal values must 'quoted'.



// DATABASE SETTINGS

// The website needs to store all content somewhere. rCMS uses a MySQL
// (or MariaDB) server for this. If you are not sure about these values, ask
// your hosting provider for the MySQL access settings.

// Location of mysql server, usually 'localhost'
$this->config['database_location'] = 'localhost';

// Name of database, for example 'website'
$this->config['database_name'] = 'website';

// Your database username, for example 'root' or 'username'
$this->config['database_user'] = 'root';

// Your database password, for example 'rgo93ly69h'
$this->config['database_password'] = 'rgo93ly69h';

// A prefix used for all tables in the database. If you want to run mutliple
// instances of rCMS on the same database, you'll need to use a different prefix
// for both of them.
$this->config['database_table_prefix'] = 'rcms_'; 



// PATHS

// The website needs to know where all the files are stored. The values here
// depend on how you installed rCMS.

// Note: the trailing slash is **required** in each and every path.
// __DIR__  simply represents the folder where this config file is stored. It is
// not a literal value, so it must not be quoted. The dot (.) is used to stitch
// two values together, for example  __DIR__ . '/web/' stitches the directory of
// this config file together with the literal path '/web/'.

// The internal location (on the web server) of the web directory: this is the
// folder with all publicly accessible files.
//
// If (and only if) the web directory is not placed in the same directory as
// this config file, you'll need to open up the environment.php file in the web
// directory (not the environment.php file in this directory) and follow the
// instructions there.
$this->config['uri_web'] = __DIR__ . '/web/';

// Set this to where the website can be found on the internet. It must be the
// public URL to the web directory (see above).
$this->config['url_web'] = '/';

// The location of the folder with all the extensions.
$this->config['uri_extend'] = __DIR__ . '/extend/';

// When set to true the index.php part of the links on the site is removed, so
// http://www.example.com/index.php/article/10 turns into
// http://www.example.com/article/10 .
//
// The web server needs additional configuration for this. If you are running
// Apache with .htaccess and mod_rewrite support enabled, it will work
// automatically. If none of the links work on the website, set this to false.
//
// Note that true and false are not literal values, but constants, so don't put
// quotes around them.
$this->config['url_rewrite'] = true;

// For a fancies text editor, we use CKFinder.
//
// New users can leave this setting alone. More advanced users can create a
// better editor by going to ckeditor.com and installing it themselves on their
// web server. You can download any edition you want, but I recommend to
// install the standard edition plus the Enhanced Image, Upload Image and
// Upload File plugins. If you know HTML (or are interested in learning it), you
// should also add the Source Dialog plugin.
//
// After you have downloaded it, extract it to a location accessible from the
// internet, and modify this value to point to that location instead.
$this->config['url_ckeditor'] = '//cdn.ckeditor.com/4.6.0/standard/';

// CKFinder path. Leave blank to disable CKFinder.
$this->config['url_ckfinder'] = '';

// The location of the src and vendor folders is always __DIR__ . '/src/' and
// __DIR__ . '/vendor/', this cannot be changed.
