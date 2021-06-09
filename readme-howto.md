# HOW TO
...actually build webpages with CRUDKit.
Make sure CRUDKit is set-up an accessible and configured to use the DB you use. If not, go back to [readme-installation](./readme-installation.md) 

## First things first
* You need [PHP](https://www.php.net/) experience to customize CRUDKit.
* From your Laravel root directory navigate to `./vendor/alddesign/crudkit/src/` and open `CrudkitServiceProvider.php`.
* Make a copy of the original `CrudkitServiceProvider.php` - trust me you will need it
* **This is the only file you want to change (except for the config files and .env).**
So make a copy of this file from time to time in case you mess things up.

You build your Webpages by populating these four methods: 
```php
defineTables()
defineRelations() //optional
definePages()
defineUsers() //optional
```
The following chapters will describe how get some code together...

## Demo Database
With CRUDKit there comes a very simple demo database, as well as a `CrudkitServiceProvider.php` with example code to display this demo database.
All you need to do is:
- Make sure Laravel and CRUDKit is configured to use this demo database (should be by default, otherwise see [readme-installation](./readme-installation.md) for details)
- Login to CRUDKit with admin:admin
- Maybe check out and play around with `CrudkitServiceProvider.php`

## Automatic code generation
CRUDKit has a feature that allows you to automatically generate your `CrudkitServiceProvider.php` for the Database you configured.
To do this:
- Make sure your original `CrudkitServiceProvider.php` is untouched. Otherwise this wont work.
- Make sure you correctly configured the desired DB in your .env ([readme-installation](./readme-installation.md) for details)
- Open `http://<APP_URL>/<app_name_url>/auto-generate` in your browser
- If everything is fine, you can download your `CrudkitServiceProvider.auto-generated.php`
- Make a copy of the original file
- Rename the downloaded file to `CrudkitServiceProvider.php` and place in in `./vendor/alddesign/crudkit/src/`.
- Open and login into CRUDKit
- Resolve possible errors

## Writing a custom CrudkitServiceProvider.php
Best practice is to start by studying the demo-database as well as the original `CrudkitServiceProvider.php`. It contains practical examples of all the big features offered by CRUDKit.

## API Documentation
Hence it is no possible to explan everthing here have a look at: [readme-apidoc](./readme-apidoc.md)

## About the frontend...
Once you configured crudkit and wrote some code for your own database, the GUI should be self explaining. As CRUDKit is highly customizable you can change the look and feel and also add or remove features. CRUDKit provides the following features by default:
* Left side menu - show all your tables, a logout button and a theme selector
* List Page - overview that shows the data of multiple records
    * Search records
    * Filter lists
    * Show as diagram
    * XML/CSV export
* Card Page - shows the data of a single record. Card pages normally provide more information. 
    * Create, Read, Update, Delete records (CRUD is the name of the game)
    * Arrange fields into groups
    * Display images

## About your IDE...
I recommend [VSCode](https://code.visualstudio.com/) as i use it to develop CRUDKit. 

As CRUDKit (obviously) runs on PHP and uses Laravel as a framework i recommend following VSCode extensions.

Must haves:
* PHP Intelephense (Intellisense for PHP)
* PHP Debug (for debugging PHP with XDebug directly in VSCode, needs some configuration)
* Laravel Extra Intellisense

Optional Extensions:
* Laravel Blade (Support for Blade, the Laravel syntax for views)
* ENV (Support for the .env Syntax)
* Deploy (A plugin to deploy code locally, to ftp, and so on. Its flexible but needs a decent amount of configuration)
* PHP Getters & Setters
* PHPDoc Generator (the one with the cat)


