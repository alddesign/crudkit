# CRUDKit Installation
## PREREQUISITES
* **Apache 2**
* **PHP >= 8.0.2** (PHP CLI should be available)
* **A Database** (for example the included demo database)
* Optional: your specific DB driver for PHP (mysql and sqlite work out of the box)
* **PHP composer**

## INSTALLATION
1. If not existig: create a new laravel 9 project via composer. (this is your laravel root directory from now on)
`composer create-project laravel/laravel=9.* "YourProjectName" --prefer-dist`

2. Add package "alddesign/crudkit" to the laravel project:
Run `composer require alddesign/crudkit=dev-master` (terminal/cmd in the laravel root directory)

3. Publish Crudkit config and assets
Run `php artisan vendor:publish --provider="Alddesign\Crudkit\CrudkitServiceProvider" --force` (terminal/cmd in the laravel root directory)

## SETUP ON WEBSERVER
Setup the URL for the application:
* Method 1 - recommended:  
Create a url/redirect/link/virtual-host, you name it, that points to the "laravel-root-directory/public" folder. It is best practice to isolate clients from the rest of the system. How to setup a virtual host on Apache: [Link](https://www.thegeekstuff.com/2011/07/apache-virtual-host/)
* Method 2 - not recommended:  
just access the application via http://yourhost/laravel-root-directory/public
* Remember this url as your **APP_URL**
* Here for example: **APP_URL=http://crudkit.localhost/**


## CONFIGURATION
CRUDKit comes with a default configruation that uses its **demo database**. Its highly recommended to start with this configuration as it also contains code examples. **If you use your own database, its not enough just to configure like shown below** - see [readme-howto](./readme-howto.md) afterwards:
1. Edit `laravel-root-directory/.env`. Edit at least following values:
* APP_NAME=Laravel
* APP_DEBUG		true if testing, false in production
* APP_LOG_LEVEL	debug, info, notice, warning, error, critical, alert, emergency. (debug = log everything, emergency = log a few things)
* APP_URL			The Url from the last step
* DB_CONNECTION	Database Type (mysql,sqlite,mssql)
* DB_* set all the additional connection information you need for your DB (sqlite needs only the path, mysql needs host, port, user,...)

2. Edit `laravel-root-directory/config/crudkit.php`. More information can be found in the comments provided in that file. 
* Edit at least the the sections "Admin Login" and "General".
* Edit and remember the **app_name_url** parameter 
* You can also set the app_name_url to empty `''` if you want no additional url parts
* Here for our example: **'app_name_url'=> 'app'**

## START
* All done!
* Start crudkit by opening `http://<APP_URL>/<app_name_url>` in your browser.
* Here for our example: **http://crudkit.localhost/app**
* Default login: 
    * User: `admin`
    * Password: `admin`

## NEXT STEPs
Check out how to actually build webpages with crudkit and customize them: [readme-howto](./readme-howto.md) 
