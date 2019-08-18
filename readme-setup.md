# PREREQUISITES
* Webserver with PHP version >= 7.0 (PHP CLI should be available)
* Database including driver for PHP (mysql and sqlite work out of the box)
* (optional) PHP composer installed

# INSTALLATION
* Method 1: via composer (more flexible)
	1. If not existig: create a new laravel project via composer. (this is your laravel root directory from now on)
	`composer create-project laravel/laravel=5.5.* "yourProjectNameHere" --prefer-dist`
	2. Add package "alddesign/crudkit" to the laravel project:
	Run `composer required aldesign/crudkit` (terminal/cmd in the laravel root directory)
	3. Publish Crudkit config and assets
	Run `php artisan vendor:publish --provider="Alddesign\Crudkit\CrudkitServiceProvider" --force` (terminal/cmd in the laravel root directory)

* Method 2: Manual (easier)
	-Extract/put the whole "crudkit" folder (this is your laravel root directory from now on) to your webservers root directory.
	 This is a ready to use package with Crudkit already integrated into Laravel.

# SETUP
1. Setup URL for the application:
	* Method 1 - recommended: 
	Create a url/redirect/link/virtual-host, you name it, that points to the "<laravel-root-directory>/public" folder.
	* Method 2 - not recommended: 
	just access the application via http://yourhost/laravel-root-directory/public
	
	* However: remember this url as your APP_URL!
2. Edit "<laravel-root-directory>/.env" file
	* Set at least these values:
	APP_NAME=Laravel
	APP_DEBUG		true if testing, false in production
	APP_LOG_LEVEL	debug, info, notice, warning, error, critical, alert, emergency. (debug = log everything, emergency = log a few things)
	APP_URL			The Url from Step 2)
	DB_CONNECTION	Database Type (mysql,sqlite,mssql)
	DB_DATABASE		Name of your database/path to the file
	DB_USERNAME		if needed
	DB_PASSWORD		if needed

3. Edit "<laravel-root-directory>/config/crudkit.php"
	* Edit at least the the sections "Admin Login" and "General".
	* Remember the "app_name_url" parameter

4. Open crudkit by opening APP_URL/app_name_url in your browser
	* Example: http://localhost/my-new-crudkit-app
