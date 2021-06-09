# CRUDKit Installation
## PREREQUISITES
* Webserver with PHP version 7.1 to 7.4 (PHP CLI should be available, and yes. Not test on PHP version 8)
* Database including driver for PHP (mysql and sqlite work out of the box)
* PHP composer installed

## INSTALLATION
1. If not existig: create a new laravel 5.8 project via composer. (this is your laravel root directory from now on)
`composer create-project laravel/laravel=5.8.* "yourProjectNameHere" --prefer-dist`

2. Add package "alddesign/crudkit" to the laravel project:
Run `composer required aldesign/crudkit=dev-master` (terminal/cmd in the laravel root directory)

3. Publish Crudkit config and assets
Run `php artisan vendor:publish --provider="Alddesign\Crudkit\CrudkitServiceProvider"` (terminal/cmd in the laravel root directory)

## SETUP ON WEBSERVER
Setup the URL for the application:
* Method 1 - recommended: 
Create a url/redirect/link/virtual-host, you name it, that points to the "laravel-root-directory/public" folder. Example for local development: http://crudkit.localhost/
* Method 2 - not recommended: 
just access the application via http://yourhost/laravel-root-directory/public
* Remember this url as your **APP_URL**
* How to setup a virtual host on Apache: [Link](https://www.thegeekstuff.com/2011/07/apache-virtual-host/)

## CONFIGURATION
CRUDKit comes with a default configruation that uses its **demo database**. Its highly recommended to start with this configuration as it also contains code examples. **If you use your own database, its not enough just to configure like shown below** - see [readme-howto](./readme-howto.md) afterwards:
1. Edit "laravel-root-directory/.env" file. Set at least these values:
* APP_NAME=Laravel
* APP_DEBUG		true if testing, false in production
* APP_LOG_LEVEL	debug, info, notice, warning, error, critical, alert, emergency. (debug = log everything, emergency = log a few things)
* APP_URL			The Url from the last step
* DB_CONNECTION	Database Type (mysql,sqlite,mssql)

2. Edit "laravel-root-directory/config/database.php". 
* Goto section "connections"
* Edit the settings for the DB_CONNECTION you chose is step 1. 

3. Edit "laravel-root-directory/config/crudkit.php"
* Edit at least the the sections "Admin Login" and "General".
* Remember the **app_name_url** parameter
* You can also set the app_name_url to empty '' if you want no additional url part

## NEXT STEP
Check out how to actually build webpages with crudkit and customize them: [readme-howto](./readme-howto.md) 

## START
* When you think youre good, start crudkit by opening `http://<APP_URL>/<app_name_url>` in your browser.
* Default login: 
    * User: `admin`
    * Password: `admin`