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
 * defineRelations()
 * definePages()
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
	 * @example ".\examples\defineTables.example.php"
     * @return TableDescriptor[]
     */
	private function defineTables()
	{
		//### Example code - works with the demo database ################################################################################
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
				->addColumn('author_id', 'AuthorId', 'integer', [])
				->addColumn('cover', 'Cover', 'image', [])
				->addColumn('price', 'Price', 'float', [])
				,
		];
		//<CRUDKIT-TABLES-END> !!! Do not remove this line. Otherwise /auto-generate wont work !!!
	}

	/**
     * Populate this methods to define table relations (1 to N and N ot 1).
	 * 
     * Use "$this->tables" which you defined in method defineTables().
	 * 
	 * @example ".\examples\defineRelations.example.php"
	 * @see TableDescriptor
     */
	private function defineRelations()
	{
		//### Example code - works with the demo database ################################################################################	
	}

	/**
     * Populate this methods to define the pages (views) according to the already definded tables.
	 * 
	 * @example ".\examples\definePages.example.php"
	 * @return PageDescriptor[]
	 * @see PageDescriptor 
     */
	private function definePages()
	{
		//### Example code - works with the demo database ################################################################################
		//<CRUDKIT-PAGES-START> !!! Do not remove this line. Otherwise /auto-generate wont work !!!
		return 
		[
			'author' => (new PageDescriptor('Author', 'author', $this->tables['author']))
				->setSummaryColumnsAll()
				->setCardLinkColumns(['id'])
				,
			'book' => (new PageDescriptor('Book', 'book', $this->tables['book']))
				->setSummaryColumnsAll()
				->setCardLinkColumns(['id'])
				->addOneToManyLink('authors', 'Autor', 'Autor', 'author', 'author', [(new FilterDefinition('id', '=', 'field', 'author_id'))])
				,
		];
		//<CRUDKIT-PAGES-END> !!! Do not remove this line. Otherwise /auto-generate wont work !!!
	}
	
	/**
     * Populate this methods to define the Logins for this application.
	 * Optional: define RestrictionSet and/or Startpage for each User.
	 * Default login (administrator) is definded in crudkit config.
	 * 
	 * @example ".\examples\defineUsers.example.php"
	 * @return AuthHelper
	 * @see CrudkitUser
	 * @see Startpage
	 * @see RestrictionSet
     */
	private function defineUsers()
	{	
		//### Example code - works with the demo database ################################################################################
		/*
		allow everything except 
		-updating/delete books
		-access to authors in general
		*/
		$restrictionSet1 = 
		new RestrictionSet
		('allow-all', 
			[	
				new RestrictionSetEntry('update', 'book'), 
				new RestrictionSetEntry('delete', 'book'),
				new RestrictionSetEntry('', 'author')
			]
		);

		/*
		deny everything except 
		-viewing list of books and authors
		*/
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
			new User('the-admin', 'M0stS@ecurPwd4This1'), //has all rights
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


