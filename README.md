## rCMS

Simple CMS for my website. Requires CKEditor and CKFinder.

![Three screenshots](http://i.imgur.com/LZq5A78.png)

## Installation
You'll need PHP (5.5+), MySQL and Composer.

1. [Download][] or clone this repo.
2. Run `php build.php deploy`
3. Place the contents of the `build/dist/public_html` directory in a location
   accessible from the internet.
4. Place the remaining contents of the `build/dist` directory of this repository
   on the same web server, but in a location not accessible from the internet.
   Preferably, place this directory one level above the public directory.
5. Rename the `config.sample.php` file to `config.php`.
6. Edit/review all the settings in the `config.php` file.
7. Review the `environment.php` file in the *public* directory to make sure that
   it points to the `environment.php` file that was uploaded in step 4.
8. Open the website in your browser. If something went wrong during steps 2, 4
   and 5, the site will tell you. If something went wrong during the other
   steps, you will see some generic error. If everything went correctly, you
   will see a link to create the necessary database tables. Click this link.
9. You will see a few more screens for setting up the default account and theme.
   Follow the on-screen instructions here.

## Configuration
Database settings and file paths can be changed in the `config.php` file. All
other settings can be controlled from the admin interface. A link to the admin
interface appears (depending on your theme) somewhere on the top-right of your
screen when you are logged in.

## CKEditor
It is recommended to install CKEditor.

[Download]: https://github.com/rutgerkok/rCMS/archive/master.zip "Download source code"
