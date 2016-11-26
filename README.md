## rCMS

Simple CMS for my website. Requires CKEditor and CKFinder.

![Three screenshots](http://i.imgur.com/LZq5A78.png)

## Installation
You'll need PHP (5.5+), MySQL and Composer.

1. [Download][] or clone this repo.
2. Make sure [Composer][] is installed. Run `composer install --no-dev` in the root directory of this project.
2. Place the contents of the `./web` directory in a location accessible from the internet.
3. Place the remaining contents of the root directory of this repository in a location not accessible from the internet.
4. Copy the file `config.sample.php`, save it in the same directory as `config.php`.
5. Edit/review all the settings in the `config.php` file.
6. Open the website in your browser. If something went wrong during steps 2, 4
   and 5, the site will tell you. If something went wrong during the other
   steps, you will see some generic error. If everything went correctly, you
   will see a link to create the necessary database tables. Click this link.
7. You will see a few more screens for setting up the default account and theme.
   Follow the on-screen instructions here.

## Configuration
Database settings and file paths can be changed in the `config.php` file. All
other settings can be controlled from the admin interface. A link to the admin
interface appears (depending on your theme) somewhere on the top-right of your
screen when you are logged in.

## CKEditor
It is recommended to install CKEditor.

[Download]: https://github.com/rutgerkok/rCMS/archive/master.zip "Download source code"
[Composer]: https://getcomposer.org/
