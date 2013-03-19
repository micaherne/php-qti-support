PHP-QTI Support
===============

This project provides a demo and support for the PHP QTI 2.1 library I am currently developing at https://github.com/micaherne/php-qti.

Contents
--------

1. Demo: This provides a very basic web application demonstrating the library
2. Implementation Status document: An Excel spreadsheet with some more or less up-to-date information on what elements are supported, and to what extent.

Installation
------------

The project uses Composer to manage dependencies and autoloading.

1. Install Composer (https://getcomposer.org/)
2. Clone php-qti-support into the document root of a PHP-enabled web server, and inside the root directory do `composer update`
3. Browse to (for example) http://localhost/php-qti-support/demo/index.php

Some functionality (e.g. response template processing) assumes that a copy of the QTI 2.1 Final spec and example bundle
has been unzipped in the root folder (creating a qtiv2p1 folder)

Using the Demo
--------------

The demo app allows you to upload an item file (XML only, content packages are not yet supported) and play it.

The workflow is for demo purposes only and treats all items as adaptive, and shows all variable values.

Each item is stored in its own folder within the data root (/demo by default), and any local images required
can be copied into this folder