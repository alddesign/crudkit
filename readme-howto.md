# HOW TO
Actually build webpages with CRUDKit.
Make sure CRUDKit is set-up an accessible and configured to use the DB you use. If not, go back to [readme-installation](./readme-installation.md) 

## First things first
From your Laravel root directory navigate to `./vendor/alddesign/crudkit/src/` and open `CrudkitServiceProvider.php`.
**This is the only file you want to change (except the config file and .env)**
So make copy of this file in case you mess up things.

You build your Webpages by writing code into these four methods: 
```php
defineTables()
defineRelations() //optional
definePages()
defineUsers() //optional
```
You will need some php experience to do this but dont worry, its not that hard (although you can build advanced stuff).
The following chapters will describe how get some code together...

## Demo Database
With Crudkit there comes a very simple Demo database.
Out of the box `CrudkitServiceProvider.php` is populated with example code to display this Demo DB.
All you need to do is:
- Configure Lavarvel to use the Demo DB by editing your .env ([readme-installation](./readme-installation.md) for details)
`DB_CONNECTION=sqlite
DB_DATABASE=</absolute/path/your/laravel/root>/vendor/alddesign/crudkit/src/demo-database/db.sqlite`
-Login to Crudkit with admin:admin
-Maybe check out and play around with `CrudkitServiceProvider.php`

## Automatic code generation
Curdkit has feature that allows you to automatically generate your `CrudkitServiceProvider.php` from the Database you configured.
To do this:
- Make sure your original `CrudkitServiceProvider.php` is untouched! Otherwise this wont work.
- Make sure you correctly configured the desired DB in your .env ([readme-installation](./readme-installation.md) for details)
- Open `http://<APP_URL>/<app_name_url>/auto-generate` in your browser
- If everything is fine, you can download your `CrudkitServiceProvider.auto-generated.php`
- Make a copy of the original file, rename the downloaded file to `CrudkitServiceProvider.php`
- Open and login into Crudkit

## Writing a custom CrudkitServiceProvider.php
...comming soon

## API Documentation
Hence it is no possible to explan everthing here have a look at: [readme-apidoc](./readme-apidoc.md)



