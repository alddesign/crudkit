<?php
/** 
 * Edit this file to create your application. (Laravel Service Provider) 
 */
declare(strict_types=1);

namespace Alddesign\Crudkit;

use Alddesign\Crudkit\Classes\AjaxOptions;
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
use Alddesign\Crudkit\Classes\DataProcessor;
use Alddesign\Crudkit\Classes\Lookup;

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
	/** @var TableDescriptor[] $tables */
	private $tables		= [];
	/** @var PageDescriptor[] $pages */
	private $pages		= [];
	/** @var User[] $users */
	private $users		= [];
	/** @var AuthHelper $authHelper */
	private $authHelper	= null;
	/** @var PageStore $pageStore */
	private $pageStore	= null;

	#region CRUDKit internal
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
        $this->app->resolving(\Alddesign\Crudkit\Controllers\CrudkitController::class, function ($controller, $app) 
		{
			$this->defineCrudKit();
			$controller->init($this->pageStore, $this->authHelper);
		});		
	}
	
	/** @ignore */
	private function defineCrudKit()
	{
		// Table schema
		$this->tables = $this->defineTables();

		// Relations
		$this->defineRelations();

		// Pages
		$this->pages = $this->definePages();
		$this->pageStore = new PageStore($this->pages);
		$this->defineMenuLinks();
		
		// Users
		$this->authHelper = $this->defineUsers();
		$this->users = $this->authHelper->getUsers();
	}
	#endregion
	
    /**
     * Populate this methods to define the tables plus columns used by your application.
     * @return TableDescriptor[]
     */
	private function defineTables()
	{
		//<CRUDKIT-TABLES-START> !!! Do not remove this line. Otherwise /auto-generate wont work !!!
		//### Example code - works with the demo database ###
		return 
		[
			'author' => (new TableDescriptor('author', ['id'], true))
				->addColumn('id', 'Id', 'integer', ['readonly' => true])
				->addColumn('name', 'Name', 'text', [])
				->addColumn('birthday', 'Birthday', 'date', [])
				->addColumn('active', 'Active', 'bool', [])
				,
			'book' => (new TableDescriptor('book', ['id'], true))
				->addColumn('id', 'Id', 'integer', ['readonly' => true])
				->addColumn('name', 'Name', 'text', [])
				->addColumn('description', 'Description', 'text', [])
				->addColumn('author_id', 'Author id', 'integer', [])
				->addColumn('price', 'Price', 'float', [])
				->addColumn('cover', 'Cover', 'image', []),
			'sale' => ((new TableDescriptor('sale', ['id'], true))
				->addColumn('id', 'Id', 'integer', ['readonly' => true, 'create' => false])
				->addColumn('book_id', 'Book Id', 'integer', [])
				->addColumn('date', 'Date', 'date', []))
		];
		//<CRUDKIT-TABLES-END> !!! Do not remove this line. Otherwise /auto-generate wont work !!!
	}

	/**
     * Populate this methods to define table relations (mostly many to one).
	 * 
     * Use "$this->tables" which you have defined in method defineTables().
	 * 
	 * @see TableDescriptor
     */
	private function defineRelations()
	{	
		//<CRUDKIT-RELATIONS-START> !!! Do not remove this line. Otherwise /auto-generate wont work !!!
		//### Example code - works with the demo database ###
		
		//Column name, reference table name, reference field name
		$this->tables['book']->defineManyToOneColumn('author_id', 'author', 'id', ['name'], 'author'); 

		//Many to one column, but with ajax search for a better user experience
		$saleBookIdAjaxOptions = new AjaxOptions('cover', ['id', 'name'], 3, 5, true);
		$this->tables['sale']->defineManyToOneColumnAjax('book_id', 'book', 'id', ['name'], '', [], false, $saleBookIdAjaxOptions);

		//<CRUDKIT-RELATIONS-END> !!! Do not remove this line. Otherwise /auto-generate wont work !!!
	}

	/**
     * Populate this methods to define the pages (views) according to the already definded tables.
	 * 
	 * @return PageDescriptor[]
	 * @see PageDescriptor 
     */
	private function definePages()
	{
		//<CRUDKIT-PAGES-START> !!! Do not remove this line. Otherwise /auto-generate wont work !!!
		//### Example code - works with the demo database ###

		//Custom action
		$seachCallback = function($record, $pageDescriptor, $action)
		{
			$authorName = urlencode($record['name']);
			header('Location: ' . 'https://wikipedia.org/wiki/Special:Search?search=' . $authorName);
			die();
		};

		//Show prices only to the admin
		$onOpenAuthorListCallback = function(&$pageDescriptor, &$tableDescriptor, &$records)
		{
			if(session('crudkit-userid') !== 'admin')
			{
				$cols = $pageDescriptor->getSummaryColumns(true); //get columns
				unset($cols['price']); //remove price column
				$pageDescriptor->setSummaryColumns($cols); //set columns
			}
		};

		//Main code
		$authorLookup = new Lookup($this->tables['author'], 'name', [new FilterDefinition('id', '=', 'field', 'author_id')], 'lookup', 'Author Name', 'after-field', 'author_id', 'author', true);
		return 
		[
			'author' => (new PageDescriptor('Author', 'author', $this->tables['author']))
				->setCardLinkColumns(['name'])
				->addAction('search-on-wikipedia', 'Search on Wikipedia', 'Search', $seachCallback)
				,
			'book' => (new PageDescriptor('Book', 'book', $this->tables['book']))
				->setCardLinkColumns(['name'])
				->addSection('Additional Data', 'cover', 'price')
				->addLookupColumn('author', $authorLookup)
				->onOpenList($onOpenAuthorListCallback)
				,
			'sale' => (new PageDescriptor('Sale', 'sale', $this->tables['sale']))
				->setCardLinkColumns([])
		];
		//<CRUDKIT-PAGES-END> !!! Do not remove this line. Otherwise /auto-generate wont work !!!
	}
	
	#region Menu links
	/**
	 * Add additional items to the menu and set icons for categories
	 * 
	 * Use $this->pageStore->addMenuLink(...);
	 * 
	 * @see PageStore
	 */
	private function defineMenuLinks()
	{
		//<CRUDKIT-MENU-LINKS-START> !!! Do not remove this line. Otherwise /auto-generate wont work !!!
		

		$jkRowlingBooksFilter = new Filter('author_id', '=', '1');
		//DataProcessor contains useful methods. For example creating url params for a crudkit page
		$urlParams = DataProcessor::getUrlParameters('book', null, '', '', [$jkRowlingBooksFilter], []);
		//action() is a Laravel helper function to create a link to a controller action
		$jkRowlingBooksUrl = action('\Alddesign\Crudkit\Controllers\CrudkitController@listView', $urlParams);

		$this->pageStore->addMenuLink('GitHub', 'https://github.com/alddesign/crudkit', '', '', 'Misc', '');
		$this->pageStore->addMenuLink('JKR Books', $jkRowlingBooksUrl, '', '', 'Misc', '');
		$this->pageStore->setCategoryFaIcon('Misc', 'cogs');
		//<CRUDKIT-MENU-LINKS-END> !!! Do not remove this line. Otherwise /auto-generate wont work !!!
	}
	#endregion
	
	/**
     * Populate this methods to define the Logins for this application.
	 * 
	 * Optional: define RestrictionSet and/or Startpage for each User.
	 * Default login (administrator) is definded in crudkit config.
	 * 
	 * @return AuthHelper
	 * @see CrudkitUser
	 * @see Startpage
	 * @see RestrictionSet
     */
	private function defineUsers()
	{	
		//### Example code - works with the demo database ###

		//<CRUDKIT-USERS-START> !!! Do not remove this line. Otherwise /auto-generate wont work !!!
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

		$this->users = 
		[
			new User('admin2', 'pwd'), 					//has all rights
			new User('jane', 'pwd', $restrictionSet1), 	//restricted
			new User('john', 'pwd', $restrictionSet2) 	//restricted
		];
		
		return new AuthHelper($this->users);
		//<CRUDKIT-USERS-END> !!! Do not remove this line. Otherwise /auto-generate wont work !!!
	}
}


