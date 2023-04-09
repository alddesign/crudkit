<?php
declare(strict_types=1);
namespace Alddesign\Crudkit\Classes;

use Illuminate\Support\Facades\URL;

/**
 * Definition of a webpage (UI).
 * 
 * Examples for usage are provided in the documentation for CrudkitServiceProvider.
 * Important: all methods marked with "@stackable" (like the constructor and all set methods) can be used like this
 * ```php
 * $page = new PageDesriptor(...)
 * 		->setTitleText(...)
 * 		->addAction(...)
 * 		->...
 * ``` 
 * ...easy
 * @see \Alddesign\Crudkit\CrudkitServiceProvider
 */
class PageDescriptor
{
	/** @internal */ const PAGE_TYPES = ['list', 'card', 'create', 'update', 'chart'];
	
    /** @internal */ private $id = '';
	/** @internal */ private $name = '';
	/** @internal */ private $category = '';
	/** @internal */ private $icon = '';
	/** @internal */ private $titleTexts = [];
	
    /** @internal */ private $table = null;
	/** @internal */ private $summaryColumns = [];
	/** @internal */ private $cardLinkColumns = [];
    /** @internal */ private $menu = false;	
	/** @internal */ private $js = [];
	/** @internal */ private $css = [];
	/** @internal */ private $itemsPerPage = -1;
	
	
	/**
	 * @var Lookup[] $lookups Lookup fields
	 * @internal
	 */
	private $lookups = [];
	
	/**
	 * @var Section[] $sections Displaying fields in separated areas called "Sections." 
	 * @internal
	 */
	private $sections = []; 
	
	/** 
	 * @var Action[] $actions Adding Buttons with special callback functions to listview/cardview
	 * @internal
	 */
	private $actions = []; 
	
    /** @internal */ private $createAllowed = true;
    /** @internal */ private $updateAllowed = true;
    /** @internal */ private $deleteAllowed = true;
	/** @internal */ private $confirmDelete = true;
	/** @internal */ private $exportAllowed = true;
	/** @internal */ private $chartAllowed = true;
	/** @internal */ private $cardAllowed = true;


	/** @var callable[] $callbacks Event callback function. [Key => Event name, Value => Callback function ]
	 * @internal 
	 */
	private $callbacks = [];

	/**
	 * Constructor
	 * 
	 * @param string $name Display name
	 * @param string $id Unique name of the page. Allowed characters: a-z, A-Z, 0-9, "_", "-"
	 * @param TableDescriptor $table Table which is the basis of this page
	 * @param bool $menu Show in menu
	 * @param string $category (optional) Name of the category in the menu where this page will be shown
	 * @return PageDescriptor
	 * @stackable
	 */
    public function __construct(string $name, string $id, TableDescriptor $table, bool $menu = true, string $category = '')
    {
        $this->name = $name;
        $this->id = $id;
		$this->table = $table;
		$this->category = $category;
		$this->menu = $menu;

		if(CHelper::e($id) || !preg_match('/^[a-zA-Z0-9_-]*$/', $id))
		{
			throw new CException('Please provide a valid id for this page. Allowed characters: a-z, A-Z, 0-9, "_", "-"');
		}
		
		if(CHelper::e($name))
		{
			throw new CException('Please provide an id for this page.');
		}
		
		if(CHelper::e($table))
		{
			throw new CException('Please provide a table for this page.');
		}

		$this->summaryColumns = $this->table->getColumns(true);
		$this->cardLinkColumns = isset($this->table->getPrimaryKeyColumns()[0]) ? [$this->table->getPrimaryKeyColumns()[0]] : [];
    }

/* #region GET */
	/** @internal */ public function getName()    			{return $this->name;}
    /** @internal */ public function getId()    			{return $this->id;}
    /** @internal */ public function getCreateAllowed()		{return $this->createAllowed;}
	/** @internal */ public function getUpdateAllowed()    	{return $this->updateAllowed;}
	/** @internal */ public function getDeleteAllowed()   	{return $this->deleteAllowed;}
	/** @internal */ public function getExportAllowed()    	{return $this->exportAllowed;}
	/** @internal */ public function getChartAllowed()    	{return $this->chartAllowed;}
	/** @internal */ public function getCardAllowed()    	{return $this->cardAllowed;}
	/** @internal */ public function getConfirmDelete()		{return $this->confirmDelete;}
	/** @internal */ public function getCategory()			{return $this->category;}
	/** @internal */ public function getIcon()				{return $this->icon;}	
	/** @internal */ public function getSections()			{return $this->sections;}
	/** @internal */ public function getMenu()				{return $this->menu;}
	/** @internal */ public function getItemsPerPage()		{return $this->itemsPerPage >= 0 ? $this->itemsPerPage : config('crudkit.records_per_page', 5);}
	
	/**
	 * Retruns on or all actions for this page.
	 * @return Action|Action[]
	 * @internal  
	 */ 
	public function getActions(string $name = '')
	{
		if(!CHelper::e($name))
		{
			if(isset($this->actions[$name])) 
			{
				return $this->actions[$name]; 
			}
			throw new CException('Action "%s" cannot be found on page "%s".', $name, $this->id);
		}
		return $this->actions;
	}

	/**
	 * Get the table to this page.
	 * @return TableDescriptor
	 * @internal
	 */
	public function getTable()
    {
        return $this->table;
    }


	/**
	 * Gets either an array of column names or SqlColumn objects with the list page columns.
	 * @return string[]|SQLColumn[]
	 * @internal
	 */
    public function getSummaryColumns(bool $namesOnly = false)
    {
		if($namesOnly)
		{
			return $this->summaryColumns;
		}
		
        $columns = [];
		$tableColumns = $this->table->getColumns();
        foreach($this->summaryColumns as $columnName)
        {
            $columns[$columnName] = $tableColumns[$columnName];
        }

        return $columns;
    }
	
	/**
	 * Gets either an array of column names or SqlColumn objects with the card link columns.
	 * @return string[]|SQLColumn[]
	 * @internal
	 */
    public function getCardLinkColumns(bool $namesOnly = false)
    {
		if($namesOnly)
		{
			return $this->cardLinkColumns;
		}
		
        $columns = [];
		$tableColumns = $this->table->getColumns();
        foreach($this->cardLinkColumns as $columnName)
        {
            $columns[$columnName] = $tableColumns[$columnName];
        }

        return $columns;
	}
	
	/** @internal */
	public function getTitleText(string $pageType)
	{
		//Exact
		foreach($this->titleTexts as $titleText)
		{
			if(in_array($pageType, $titleText['page-types'], true)) return $titleText['text'];
		}

		//Default
		foreach($this->titleTexts as $titleText)
		{
			if($titleText['page-types'] === []) return $titleText['text'];
		}
		
		return $this->name;
	}

	/**
	 * Gets the Lookup columns and with calculated values.
	 * @param array $recods The source records (for claculating values from raltated table)
	 * 
	 * @return Lookup[]
	 */
	public function getLookupsCalculated(array $record)
	{
		$calculatedLookups = []; 
		foreach($this->lookups as $index => $lookup)
		{
			$calculatedLookup = clone $lookup; //We need to clone the object. In list pages is function is called multiple times, and we would always change the object;
			$calculatedLookup->calculateLookup($record, true);


			$calculatedLookups[$index] = $calculatedLookup;
		}
	
		return $calculatedLookups;
	}

	/**
	 * Gets the Lookup columns and with calculated values for multiple records
	 * @param array $records
	 * @return array
	 */
	public function getMultipleLookupsCalculated(array $records)
	{
		$lookups = [];
		foreach ($records as $id => $record) 
		{
			$lookups[$id] = $this->getLookupsCalculated($record);
		}
		
		return $lookups;
	}

	/** @ignore */
	public function getCardPageUrl(array $primaryKeyValues)
	{
		$urlParameters = CHelper::getUrlParameters($this->id, null, '', '', [], $primaryKeyValues);
		return(URL::action('\Alddesign\Crudkit\Controllers\CrudkitController@cardView', $urlParameters));
	}
	
	/** @ignore */
	public function getCardPageUrls(array $records, array $primaryKeyColumns, bool $singleRecord = false)
	{
		$result = [];

		$records = $singleRecord ? [$records] : $records; //Can now be used in the foreach
		
		foreach($records as $index => $record)
		{
			$urlParameters = CHelper::getUrlParameters($this->id, null, '', '', [], [], $primaryKeyColumns, $record);
			$result[$index] = URL::action('\Alddesign\Crudkit\Controllers\CrudkitController@cardView', $urlParameters);
		}
		
		return $singleRecord ? $result[0] : $result;
	}

	/** @ignore */
	public function getExportCsvUrl(string $searchText = '', string $searchColumnName = '', array $filters = [])
	{
		$urlParameters = CHelper::getUrlParameters($this->id, null,$searchText,$searchColumnName,$filters);
		return(URL::action('\Alddesign\Crudkit\Controllers\CrudkitController@exportRecordsCsv', $urlParameters));
	}

	/** @ignore */
	public function getExportXmlUrl(string $searchText = '', string $searchColumnName = '', array $filters = [])
	{
		$urlParameters = CHelper::getUrlParameters($this->id, null,$searchText,$searchColumnName,$filters);
		return(URL::action('\Alddesign\Crudkit\Controllers\CrudkitController@exportRecordsXml', $urlParameters));
	}

	/** @ignore */
	public function getChartPageUrl(string $searchText = '', string $searchColumnName = '', array $filters = [])
	{
		$urlParameters = CHelper::getUrlParameters($this->id, null,$searchText,$searchColumnName,$filters);
		return(URL::action('\Alddesign\Crudkit\Controllers\CrudkitController@chartView', $urlParameters));
	}

	/** @ignore */
	public function getManyToOneUrls(array $records, bool $singleRecord = false)
	{
		$result = [];
		$records = $singleRecord ? [$records] : $records;
		
		/** @var SQLManyToOneColumn[] $columns */
		$columns = $this->table->getColumns();
		foreach($columns as $column)
		{
			if($column->isManyToOne)
			{
				foreach($records as $index => $record)
				{
					$result[$index][$column->name] = $column->getCardUrl($record);
				}
			}
		}
		
		$result[0] = isset($result[0]) ? $result[0] : $result;
		return $singleRecord ? $result[0] : $result;
	}

	/** @return array */
	public function getJs()
	{
		return $this->js;
	}

	/** @return array */
	public function getCss()
	{
		return $this->css;
	}
/* #endregion */

/* #region SET and ADD methods*/
	/** Allow/deny to create new records (default = true) 
	 * @stackable */
	public function setAllowCreate(bool $value = true)	{ $this->createAllowed = $value;		return $this; }
	/** Allow/deny to update records (default = true) 
	 * @stackable*/
	public function setAllowUpdate(bool $value = true)	{ $this->updateAllowed = $value;		return $this; }
	/** Allow/deny to delete records (default = true) 
	 * @stackable*/
	public function setAllowDelete(bool $value = true)	{ $this->deleteAllowed = $value;		return $this; }
	/** Allow/deny to export records to csv/xml (default = true) 
	 * @stackable*/
	public function setAllowExport(bool $value = true)	{ $this->exportAllowed = $value;		return $this; }
	/** Allow/deny to show records as chart (default = true) 
	 * @stackable */
	public function setAllowChart(bool $value = true)	{ $this->chartAllowed = $value;			return $this; }
	/** Allow/deny to show records as card page (default = true) 
	 * @stackable */
	public function setAllowCard(bool $value = true)	{ $this->cardAllowed = $value;			return $this; }
	/** Show confirmation dialog before deleting a record (default = true) 
	 * @stackable */
	public function setConfirmDelete(bool $value = true) 	{ $this->confirmDelete = $value;	return $this; }
	/** Name of the category in the menu where this page will be shown 
	 * @stackable */
	public function setCategory(string $value = '')			{ $this->category = $value;			return $this; }
	/** Fowt Awesome icon name of this page (visible in menu) 
	 * @stackable */
	public function setIcon(string $value = '')				{ $this->icon = $value;				return $this; }
	/** Show in Menu or not
	 * @stackable */
	public function setMenu(bool $value = true)			{ $this->menu = $value;				return $this; }
	/** Set number of records per page (overrides the crudkit config value). -1 = use curdkit config
	 * @stackable */
	public function setItemsPerPage(int $value = -1)	{ $this->itemsPerPage = $value;		return $this; }

	/** 
	 * Defines which columns are shown in which order on list pages.
	 * 
	 * By default all columns are shown in the order they are defined in the table.
	 *  
	 * @param string[] $summaryColumnNames Array of column names.
	 * @stackable
	 * 
	 * @return PageDescriptor
	 */
    public function setSummaryColumns(array $summaryColumnNames)
    {
		$columnsNotFound = array_diff($summaryColumnNames, $this->table->getColumns(true));
		
		if(!CHelper::e($columnsNotFound))
        {
            throw new CException('Following summary columns were not found on page "%s" (table "%s"): "%s"', $this->id, $this->table->getName(), implode(', ',$columnsNotFound));
        }

		//$this->summaryColumns = ['id' => 'id', 'description' => 'description'];
		$this->summaryColumns = array_combine($summaryColumnNames, $summaryColumnNames);//This is easier to access, than a numeric index.

        return $this;
	}

	/** 
	 * Defines which columns are shown as link form the list page to the card page.
	 * By the default its the first column of the table´s primary key.
	 * 
	 * @param string[] $cardLinkColumnNames Array of column names
	 * @return PageDescriptor
	 * @stackable
	 */
	public function setCardLinkColumns(array $cardLinkColumnNames)
    {
		$columnsNotFound = array_diff($cardLinkColumnNames, array_keys($this->table->getColumns()));
		
		if(!CHelper::e($columnsNotFound))
        {
            throw new CException('Page - set card link columns: following card link columns were not found on page "%s" (table "%s"): "%s": ', $this->id, $this->table->getName(), implode(', ',$columnsNotFound));
        }
		
		$this->cardLinkColumns = $cardLinkColumnNames;
		
        return $this;
    }
	
	/**
	 * Defines the title text for specific page types
	 * 
	 * ```php
	 * $pageDescriptor
	 * ->setTitleText('Book'); //For all pages
	 * ->setTitleText('New Book', ['create']); //Specific page
	 * ```
	 * @param string $text The text to display
	 * @param string[] $pageTypes (optional) specifies on which page types ('list', 'card', 'create', 'update', 'chart') this text will be shown.
	 * @return PageDescriptor
	 * @stackable
	 */
	public function setTitleText(string $text, array $pageTypes = [])
	{
		if($pageTypes !== [])
		{
			$pageTypesNotFound = array_diff($pageTypes, self::PAGE_TYPES);
			if($pageTypesNotFound !== [])
			{
				throw new CException('Page - add title text: invalid page type(s) "%s" provided. Page "%s"', implode(', ', $pageTypesNotFound), $this->id);
			}
		}
		
		$this->titleTexts[] = ['text' => $text, 'page-types' => $pageTypes];
		
		return $this;
	}

	/**
	 * @param string $id a identifier for this lookup on the (unique per page)
	 * @param Lookup $lookup The lookup object
	 * 
	 * @return PageDescriptor
	 * @stackable
	 */
	public function addLookupColumn(string $id, Lookup $lookup)
	{
		if(CHelper::e($id))
		{
			throw new CException('Please provide a id form the lookup.');
		}
		
		if(isset($this->lookups[$id]))
		{
			throw new CException('Lookup id "%s" already exists on page "%s"!', $id, $this->id);
		}

		if(!$this->table->hasColumn($lookup->fieldname))
		{
			throw new CException('Cannot add lookup field. Column "%s" doesnt exist in primary table "%s".', $lookup->fieldname, $this->table->getName());
		}

		$this->lookups[$id] = $lookup;		

		return $this;
	}
	
	/**
	 * Add a button with a custom action to this page.
	 *
	 * Be creative, but take care. Your can write you own php code and use all of crudkits api to manipulate data, etc... (see apidoc-dev):
	 * ```php
	 * $callback = function($record, $pageDescriptor, $action) { mail('ceo@mydomain.com', 'Book info', 'Check out our new book: '. $record["name"]); };
	 * $pageDescriptor->addAction('mail', 'Send book-info mail', 'Mail', $callback, true, true, envelope, 'info');
	 * //Next thing: drop table studends;
	 * ```
	 * @param string $id a identifier for this action (unique per page)
	 * @param string $label Label of the button
	 * @param string $columnLabel Label of the column in list view
	 * @param callable $callback Callback function to execute when pressing the button. This callback has $record, $pageDescriptor, $action(this is what you definde here) as parameters.
	 * @param bool $onList (optional) Show on list page
	 * @param bool $onCard (optional) Show on card page
	 * @param string $faIcon (optional) Icon for the Button. (Font Awesome icon name)
	 * @param string $btnClass (optional) ''|'default'|'primary'|'info'|'success'|'danger'|'warning'. (Admin LTE Button class)
	 * @param string $position (optional) 'top'|'bottom'|'both'|'before-field'|'after-field'|'to-field'. Position on card pages.
	 * @param string $fieldname (optional) The reference fieldname for $postion ('before-field'|'after-field'|'to-field')
	 * @param bool $enabled (optional) If the button is enabled
	 * @param bool $visible (optional) If the button is visible
	 * @return PageDescriptor
	 * @stackable
	*/
	public function addAction(string $id, string $label, string $columnLabel, callable $callback, bool $onList = true, bool $onCard = true, string $faIcon = '', string $btnClass = '', string $position = '', string $fieldname = '', bool $enabled = true, bool $visible = true, $data = [])
	{

		$action = (new Action($label, $columnLabel, $callback,  $position, $fieldname))
		->setOnCard($onCard)
		->setOnList($onList)
		->setFaIcon($faIcon)
		->setBtnClass($btnClass)
		->setEnabled($enabled)
		->setVisible($visible)
		->setData($data);

		$this->addActionObject($id, $action);
		return $this;
	}

	/**
	 * Add a button with a custom action to this page.
	 * 
	 * @param string $id a identifier for this action (unique per page)
	 * @param Action $action The Action
	 * @return PageDescriptor
	 * @stackable
	 */
	public function addActionObject(string $id, Action $action)
	{
		//Callback functions parameters: $record, $pageDescriptor, $action
		if(CHelper::e($id))
		{
			throw new CException('Please provide a id for the action.');
		}
		
		if(isset($this->actions[$id]))
		{
			throw new CException('Action id "%s" already exists on page "%s"!', $id, $this->id);
		}
		
		$this->actions[$id] = $action;
		return $this;
	}
	
	/**
	 * Adds a section (foldable area with a title) to the card page.
	 * 
	 * Make sure multiple sections do no overlap. Order of from/toColumnName are interchangeable.
	 * 
	 * @param string $title The title to display (unique)
	 * @param string $fromColumnName
	 * @param string $toColumnName
	 * @param bool $collapsedByDefault
	 * @return PageDescriptor
	 */
	public function addSection(string $title, string $fromColumnName, string $toColumnName = '', bool $collapsedByDefault = true)
	{
		//Test if columns exists
		$columns = $this->table->getColumns(true);
		if(CHelper::e($columns))
		{
			throw new CException('No columns found in table "%s".', $this->table->getName());
		}
		
		//Test if Columns exist
		if(!in_array($fromColumnName, $columns, true))
		{
			throw new CException('From-column "%s" was not found in table "%s".', $fromColumnName, $this->table->getName());
		}
		if(!CHelper::e($toColumnName) && !in_array($toColumnName, $columns, true))
		{
			throw new CException('To-column "%s" was not found in table "%s".', $toColumnName, $this->table->getName());
		}
		
		//Get Last Column, if not specified
		$toColumnName = CHelper::e($toColumnName) ? end($columns) : $toColumnName;
		
		//Swap if order is wrong
		$fromIndex = array_search($fromColumnName, array_keys($columns));
		$toIndex = array_search($toColumnName, array_keys($columns));
		if($fromIndex > $toIndex)
		{
			CHelper::swap($fromIndex, $toIndex);
			CHelper::swap($fromColumnName, $toColumnName);
		}
		
		//Test crossings of sections
		foreach($this->sections as $section)
		{
			$fromIndex2 = array_search($section->from, array_keys($columns));
			$toIndex2 = array_search($section->to, array_keys($columns));
			if(($fromIndex >= $fromIndex2 && $fromIndex <= $toIndex2) || 
			   ($toIndex >= $fromIndex2 && $toIndex <= $fromIndex2) ||
			   ($fromIndex <= $fromIndex2 && $toIndex >= $toIndex2))
			{
				throw new CException('Sections "%s" and "%s" overlap each other.', $section['title'], $title);
			}
		}
		
		//Finally ok:
		$this->sections[] = new Section($title, $fromColumnName, $toColumnName, $collapsedByDefault);
		
		return $this;
	}

	/**
	 * Adds a custom JS file to pages.
	 * 
	 * Hint: when programming in JS you can get data from the global var "curdkit"
	 * 
	 * @param string $url Absolute or relative URL to the JS file
	 * @param array $pageTypes Array of page type names fore the JS file to add
	 * @param bool $isAssetUrl Indicates if its a URL to the Laravel asset directory (/public)
	 * 
	 * @return PageDescriptor
	 */
	public function addJs(string $url, array $pageTypes = [], bool $isAssetUrl = true)
	{
		$url = $isAssetUrl ? asset($url) : $url;

		$this->js[] = ['url' => $url, 'pageTypes' => $pageTypes]; 
		return $this;
	}

	/**
	 * Adds a custom CSS file to pages.
	 * 
	 * @param string $url Absolute or relative URL to the CSS file
	 * @param array $pageTypes Array of page type names fore the CSS file to add
	 * @param bool $isAssetUrl Indicates if its a URL to the Laravel asset directory (/public)
	 * 
	 * @return PageDescriptor
	 */
	public function addCss(string $url, array $pageTypes = [], bool $isAssetUrl = true)
	{
		$url = $isAssetUrl ? asset($url) : $url;

		$this->css[] = ['url' => $url, 'pageTypes' => $pageTypes]; 
		return $this;
	}
/* #endregion */
	
/* #region EVENTS */
	/**
	 * Triggers an event.
	 * @param string $name Name of the Event
	 * @param mixed $param1 Additional parameter
	 * @internal
	 */
	public function triggerEvent(string $name, &$param1 = null)
	{
		$name = mb_strtolower($name);
		
		if(!isset($this->callbacks[$name]) || !is_callable($this->callbacks[$name]))
		{
			return;
		}
			
		return call_user_func_array($this->callbacks[$name], array(&$this, &$this->table, &$param1));
	}
	
	/**
	 * Register event handler. Occours before a list page is beeing opened.
	 * 
	 * ```php
	 * //Reference parametes (&) can be modified inside the function. Very powerfull.
	 * //$viewData is an array with all the data sent to the view. Too much to explain. $viewData['records'] for example is interesting
	 * $callback = function(&$pageDescriptor, &$tableDescriptor, &$viewData){...};
	 * $pageDescriptor->onOpenList($callback);
	 * ``` 
	 * @param callable $callback
	 * @event
	 */
	public function onOpenList(callable $callback)
	{
		$this->callbacks['onopenlist'] = $callback;
		return $this;
	}
	
	/**
	 * Register event handler. Occours before a card page is beeing openend.
	 * 
	 * ```php
	 * //Reference parametes (&) can be modified inside the function. Very powerfull.
	 * //$viewData is an array with all the data sent to the view. Too much to explain. $viewData['record'] for example is interesting
	 * $callback = function(&$pageDescriptor, &$tableDescriptor, &$viewData){...};
	 * $pageDescriptor->onOpenCard($callback);
	 * ``` 
	 * @param callable $callback
	 * @event
	 */
	public function onOpenCard(callable $callback)
	{
		$this->callbacks['onopencard'] = $callback;
		return $this;
	}
	
	/**
	 * Register event handler. Occours before a create page is beeing opened.
	 * 
	 * ```php
	 * //Reference parametes (&) can be modified inside the function. Very powerfull.
	 * //$viewData is an array with all the data sent to the view. Too much to explain. $viewData['record'] for example is interesting
	 * $callback = function(&$pageDescriptor, &$tableDescriptor, &$viewData){...};
	 * $pageDescriptor->onOpenCreate($callback);
	 * ``` 
	 * @param callable $callback
	 * @event
	 */
	public function onOpenCreate(callable $callback)
	{
		$this->callbacks['onopencreate'] = $callback;;
		return $this;
	}
	
	/**
	 * Register event handler. Occours before a update page is beeing opened.
	 * 
	 * ```php
	 * //Reference parametes (&) can be modified inside the function. Very powerfull.
	 * //$viewData is an array with all the data sent to the view. Too much to explain. $viewData['record'] for example is interesting
	 * $callback = function(&$pageDescriptor, &$tableDescriptor, &$viewData){...};
	 * $pageDescriptor->onOpenUpdate($callback);
	 * ``` 
	 * @param callable $callback
	 * @event
	 */
	public function onOpenUpdate(callable $callback)
	{
		$this->callbacks['onopenupdate'] = $callback;
		return $this;
	}

	/**
	 * Register event handler. Occours before a page is beeing openend as a Chart.
	 * ```php
	 * //Reference parametes (&) can be modified inside the function. Very powerfull.
	 * //$viewData is an array with all the data sent to the view. Too much to explain. $viewData['record'] for example is interesting
	 * $callback = function(&$pageDescriptor, &$tableDescriptor, &$viewData){...};
	 * $pageDescriptor->onOpenChart($callback);
	 * ``` 
	 * @param callable $callback
	 * @event
	 */
	public function onOpenChart(callable $callback)
	{
		$this->callbacks['onopenchart'] = $callback;
		return $this;
	}
	
	/**
	 * Register event handler. Occours before a record will be inserted into the database.
	 * 
	 * ```php
	 * //Reference parametes (&) can be modified inside the function. Very powerfull.
	 * $callback = function(&$pageDescriptor, &$tableDescriptor, &$records){...};
	 * $pageDescriptor->onBeforeCreate($callback);
	 * ``` 
	 * @param callable $callback
	 * @event
	 */
	public function onBeforeCreate(callable $callback)
	{
		$this->callbacks['onbeforecreate'] = $callback;
		return $this;
	}
	
	/**
	 * Register event handler. Occours after a record has been inserted into the database.
	 * 
	 * ```php
	 * //Reference parametes (&) can be modified inside the function. Very powerfull.
	 * $callback = function(&$pageDescriptor, &$tableDescriptor, &$records){...};
	 * $pageDescriptor->onAfterCreate($callback);
	 * ``` 
	 * @param callable $callback
	 * @event
	 */
	public function onAfterCreate(callable $callback)
	{
		$this->callbacks['onaftercreate'] = $callback;
		return $this;
	}
	
	/**
	 * Register event handler. Occours before a record will be updated in the database.
	 * 
	 * ```php
	 * //Reference parametes (&) can be modified inside the function. Very powerfull.
	 * $callback = function(&$pageDescriptor, &$tableDescriptor, &$records){...};
	 * $pageDescriptor->onBeforeUpdate($callback);
	 * ``` 
	 * @param callable $callback
	 * @event
	 */
	public function onBeforeUpdate(callable $callback)
	{
		$this->callbacks['onbeforeupdate'] = $callback;
		return $this;
	}
	
	/**
	 * Register event handler. Occours after a record has been updated in the database.
	 * 
	 * ```php
	 * //Reference parametes (&) can be modified inside the function. Very powerfull.
	 * $callback = function(&$pageDescriptor, &$tableDescriptor, &$records){...};
	 * $pageDescriptor->onAfterUpdate($callback);
	 * ``` 
	 * @param callable $callback
	 * @event
	 */
	public function onAfterUpdate(callable $callback)
	{
		$this->callbacks['onafterupdate'] = $callback;
		return $this;
	}
	
	/**
	 * Register event handler. Occours before a record will be deleted from the database.
	 * 
	 * ```php
	 * //Reference parametes (&) can be modified inside the function. Very powerfull.
	 * $callback = function(&$pageDescriptor, &$tableDescriptor, &$records){...};
	 * $pageDescriptor->onBeforeDelete($callback);
	 * ``` 
	 * @param callable $callback
	 * @event
	 */
	public function onBeforeDelete(callable $callback)
	{
		$this->callbacks['onbeforedelete'] = $callback;
		return $this;
	}
	
	/**
	 * Register event handler. Occours after a record has been deleted from the database.
	 * 
	 * ```php
	 * //Reference parametes (&) can be modified inside the function. Very powerfull.
	 * $callback = function(&$pageDescriptor, &$tableDescriptor, &$records){...};
	 * $pageDescriptor->onAfterDelete($callback);
	 * ``` 
	 * @param callable $callback Callback function which is beeing called if this event occours.
	 * @event
	 */
	public function onAfterDelete(callable $callback)
	{
		$this->callbacks['onafterdelete'] = $callback;
		return $this;
	}
}
