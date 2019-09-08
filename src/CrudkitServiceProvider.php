<?php
/** Edit this file to create your application. (Laravel Service Provider) */

namespace Alddesign\Crudkit;

use Alddesign\Crudkit\Classes\AuthHelper;
use Alddesign\Crudkit\Classes\User;
use Alddesign\Crudkit\Classes\RestrictionSet;
use Alddesign\Crudkit\Classes\RestrictionSetEntry;
use Alddesign\Crudkit\Classes\Startpage;
use Alddesign\Crudkit\Classes\TableDescriptor;
use Alddesign\Crudkit\Classes\PageDescriptor;
use Alddesign\Crudkit\Classes\PageStore;
use Alddesign\Crudkit\Classes\FilterDefinition;
use Alddesign\Crudkit\Classes\Filter;
use Alddesign\Crudkit\Classes\DataProcessor as dp;

/**
 * This class is used to create your application.
 * 
 * Populate the following methods to build your application:
 *
 * defineTables()
 * 
 * defineRelations()
 * 
 * definePages()
 * 
 * defineUsers()
*/
class CrudkitServiceProvider extends \Illuminate\Support\ServiceProvider
{
	/** @ignore */private $tables		= [];
	/** @ignore */private $pages		= [];
	/** @ignore */private $users		= [];
	/** @ignore */private $authHelper	= null;

	/** @ignore */
    public function boot()
    {
		//This will be called when you run: php artisan vendor:publish --provider="Alddesign\Crudkit\CrudkitServiceProvider"
		$this->publishes([__DIR__.'/config/crudkit.php' 		=> config_path('crudkit.php'),], 		'config');
		$this->publishes([__DIR__.'/config/crudkit-texts.php' 	=> config_path('crudkit-texts.php'),], 	'config');
		$this->publishes([__DIR__.'/config/crudkit-db.php' 		=> config_path('crudkit-db.php'),], 	'config');
		$this->publishes([__DIR__.'/assets'						=> public_path(),], 					'public');

		$this->loadRoutesFrom(__DIR__.'/routes.php');
		$this->loadViewsFrom(__DIR__.'/views', 'crudkit');
    }

	/** @ignore */
    public function register()
    {
		
        $this->app->resolving(\Alddesign\Crudkit\Controllers\CrudkitController::class, function ($adminPanel, $app) 
		{
			// Table schema
			$this->tables = $this->defineTables();
			
			// Relations
			$this->defineRelations();

            // Pages
            $this->pages = $this->definePages();
			
			// Users
			$this->authHelper = $this->defineUsers();
			$this->users = $this->authHelper->getUsers();

			//Go!
            $this->start($adminPanel);
        });		
    }
	
    /**
     * Populate this methods to define the tables plus columns used by your application.
	 * @example ".\CrudkitServiceProvider.php" 80 22 Example
     * @return TableDescriptor[]
     */
	private function defineTables()
	{
		//### Example code - works with the demo database ###
		//<CRUDKIT-TABLES-START> !!! Do not remove this line. Otherwise /auto-generate wont work !!! 	
		return 
		[
			'author' => (new TableDescriptor('author', ['id'], true))
				->addColumn('id', 'Id', 'integer', ['readonly' => true])
				->addColumn('name', 'Name', 'text', [])
				->addColumn('birthday', 'Birthday', 'date', [])
				->addColumn('active', 'Active', 'bool', [])
				,
			'book' => (new TableDescriptor('book', ['id'], true))
				->addColumn('id', 'Id', 'integer', [])
				->addColumn('name', 'Name', 'text', [])
				->addColumn('description', 'Description', 'text', [])
				->addColumn('author_id', 'Author id', 'integer', [])
				->addColumn('price', 'Price', 'float', [])
				->addColumn('cover', 'Cover', 'image', [])
				,
		];
		//<CRUDKIT-TABLES-END> !!! Do not remove this line. Otherwise /auto-generate wont work !!!
	}

	/**
     * Populate this methods to define table relations (mostly many to one).
	 * 
     * Use "$this->tables" which you have defined in method defineTables().
	 * 
	 * @example ".\CrudkitServiceProvider.php" 112 6 Example
	 * @see TableDescriptor
     */
	private function defineRelations()
	{
		//### Example code - works with the demo database ###
		$this->tables['book']
		->defineManyToOneColumn('author_id', 'author', 'id'); //Column name, reference table name, reference field name
	}

	/**
     * Populate this methods to define the pages (views) according to the already definded tables.
	 * 
	 * @example ".\CrudkitServiceProvider.php" 126 40 Example with usage of page events and custom actions:
	 * @return PageDescriptor[]
	 * @see PageDescriptor 
     */
	private function definePages()
	{
		//### Example code - works with the demo database ###
		//<CRUDKIT-PAGES-START> !!! Do not remove this line. Otherwise /auto-generate wont work !!!
		
		//Custom action
		$seachCallback = function($record, $pageDescriptor, $action)
		{
			$authorName = urlencode($record['name']);
			header('Location: ' . 'https://wikipedia.org/wiki/Special:Search?search=' . $authorName);
			die();
		};

		//Show prices only to the CEO
		$onOpenAuthorListCallback = function(&$pageDescriptor, &$tableDescriptor, &$records)
		{
			if(session('crudkit-userid') !== 'CEO')
			{
				$cols = $pageDescriptor->getSummaryColumns(true); //get columns
				unset($cols['price']); //remove price column
				$pageDescriptor->setSummaryColumns($cols); //set columns
			}
		};
		//dp::xout($this->tables['book']->getColumns(true));
		//Main code
		return 
		[
			'author' => (new PageDescriptor('Author', 'author', $this->tables['author']))
				->setCardLinkColumns(['id'])
				->addAction('search-on-wikipedia', 'Search on Wikipedia', 'Search', $seachCallback)
				,
			'book' => (new PageDescriptor('Book', 'book', $this->tables['book']))
				->setCardLinkColumns(['id'])
				->addSection('Additional Data', 'cover', 'price')
				->addOneToManyLink('author', 'Author', 'Author', 'author', 'author', [(new FilterDefinition('id', '=', 'field', 'author_id'))])
				->onOpenList($onOpenAuthorListCallback)
				,
		];
		//<CRUDKIT-PAGES-END> !!! Do not remove this line. Otherwise /auto-generate wont work !!!
	}
	
	/**
     * Populate this methods to define the Logins for this application.
	 * 
	 * Optional: define RestrictionSet and/or Startpage for each User.
	 * Default login (administrator) is definded in crudkit config.
	 * 
	 * @example ".\CrudkitServiceProvider.php" 179 34 Example
	 * @return AuthHelper
	 * @see CrudkitUser
	 * @see Startpage
	 * @see RestrictionSet
     */
	private function defineUsers()
	{	
		//### Example code - works with the demo database ###

		//allow everything except: -updating/delete books, -access to authors in general
		$restrictionSet1 = 
		new RestrictionSet
		('allow-all', 
			[	
				new RestrictionSetEntry('update', 'book'), 
				new RestrictionSetEntry('delete', 'book'),
				new RestrictionSetEntry('', 'author')
			]
		);

		//deny everything except: -viewing list of books and authors
		$restrictionSet2 = 
		new RestrictionSet
		('deny-all', 
			[	
				new RestrictionSetEntry('list', 'book'), 
				new RestrictionSetEntry('list', 'author')
			]
		);

		$users = 
		[
			new User('admin2', 'M0stS@ecurPwd4This1'), //has all rights
			new User('janedoe', 'P@ssw0rd', $restrictionSet1), //restricted
			new User('johndoe', 'jd123', $restrictionSet2) //restricted
		];
		
		return new AuthHelper($users);
	}
	
	/** @ignore */
	private function start($adminPanel)
	{
		$pageStore 	= new PageStore($this->pages);

		$adminPanel->init($pageStore, $this->authHelper);
	}
}


