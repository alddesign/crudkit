<?php
/**
 * Class PageDescriptor
 */
declare(strict_types=1);
namespace Alddesign\Crudkit\Classes;

use Illuminate\Support\Facades\Response;
use Alddesign\Crudkit\Classes\DataProcessor as dp;

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
	 * @param string $category (optional) Name of the category in the menu where this page will be shown
	 * @stackable
	 */
    public function __construct(string $name, string $id, TableDescriptor $table, string $category = '')
    {
        $this->name = $name;
        $this->id = $id;
		$this->table = $table;
		$this->category = $category;

		if(dp::e($id) || !preg_match('/^[a-zA-Z0-9_-]*$/', $id))
		{
			dp::crudkitException('Please provide a valid id for this page. Allowed characters: a-z, A-Z, 0-9, "_", "-"', __CLASS__, __FUNCTION__);
		}
		
		if(dp::e($name))
		{
			dp::crudkitException('Please provide an id for this page.', __CLASS__, __FUNCTION__);
		}
		
		if(dp::e($table))
		{
			dp::crudkitException('Please provide a table for this page.', __CLASS__, __FUNCTION__);
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
	/** @internal */ public function getConfirmDelete()		{return $this->confirmDelete;}
	/** @internal */ public function getCategory()			{return $this->category;}
	/** @internal */ public function getIcon()				{return $this->icon;}	
	/** @internal */ public function getSections()			{return $this->sections;}
	
	/**
	 * Retruns on or all actions for this page.
	 * @return Action|Action[]
	 * @internal  
	 */ 
	public function getActions(string $name = '')
	{
		if(!dp::e($name))
		{
			if(isset($this->actions[$name])) 
			{
				return $this->actions[$name]; 
			}
			dp::crudkitException('Action "%s" cannot be found on page "%s".', __CLASS__, __FUNCTION__, $name, $this->id);
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
/* #endregion */

/* #region SET and ADD methods*/
	/** Allow/deny to create new records (default = true) 
	 * @stackable */
	public function setAllowCreate(bool $value = true)	{ $this->createAllowed = true;		return $this; }
	/** Allow/deny to update records (default = true) 
	 * @stackable*/
	public function setAllowUpdate(bool $value = true)	{ $this->updateAllowed = true;		return $this; }
	/** Allow/deny to delete records (default = true) 
	 * @stackable*/
	public function setAllowDelete(bool $value = true)	{ $this->deleteAllowed = true;		return $this; }
	/** Allow/deny to export records to csv/xml (default = true) 
	 * @stackable*/
	public function setAllowExport(bool $value = true)	{ $this->exportAllowed = true;		return $this; }
	/** Allow/deny to show records as chart (default = true) 
	 * @stackable */
	public function setAllowChart(bool $value = true)	{ $this->chartAllowed = true;		return $this; }
	/** Show confirmation dialog before deleting a record (default = true) 
	 * @stackable */
	public function setConfirmDelete(bool $value = true) 	{ $this->confirmDelete = $value;	return $this; }
	/** Name of the category in the menu where this page will be shown 
	 * @stackable */
	public function setCategory(string $value = '')			{ $this->category = $value;			return $this; }
	/** Fowt Awesome icon name of this page (visible in menu) 
	 * @stackable */
	public function setIcon(string $value = '')				{ $this->icon = $value;				return $this; }

	/** 
	 * Defines which columns are shown in which order on list pages (defaul = all, order = as defined in tables). 
	 * @param string[] $summaryColumnNames
	 * @stackable
	 */
    public function setSummaryColumns(array $summaryColumnNames)
    {
		$columnsNotFound = array_diff($summaryColumnNames, $this->table->getColumns(true));
		
		if(!dp::e($columnsNotFound))
        {
            dp::crudkitException('Following summary columns were not found on page "%s" (table "%s"): "%s"', __CLASS__, __FUNCTION__, $this->id, $this->table->getName(), implode(', ',$columnsNotFound));
        }

		//$this->summaryColumns = ['id' => 'id', 'description' => 'description'];
		$this->summaryColumns = array_combine($summaryColumnNames, $summaryColumnNames);//This is easier to access, than a numeric index.

        return $this;
	}

	/** 
	 * Defines which columns are shown as link form list to card page (defaul = first column of Tables PK)
	 * 
	 * ```php
	 * $pageDescriptor->setCardLinkColumns(["id", "name"]);
	 * ```
	 * @param string[] $cardLinkColumnNames
	 * @stackable
	 */
	public function setCardLinkColumns(array $cardLinkColumnNames)
    {
		$columnsNotFound = array_diff($cardLinkColumnNames, array_keys($this->table->getColumns()));
		
		if(!dp::e($columnsNotFound))
        {
            dp::crudkitException('Page - set card link columns: following card link columns were not found on page "%s" (table "%s"): "%s": ', __CLASS__, __FUNCTION__, $this->id, $this->table->getName(), implode(', ',$columnsNotFound));
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
	 * @return PageDescriptor Returns $this
	 * @stackable
	 */
	public function setTitleText(string $text, array $pageTypes = [])
	{
		if($pageTypes !== [])
		{
			$pageTypesNotFound = array_diff($pageTypes, self::PAGE_TYPES);
			if($pageTypesNotFound !== [])
			{
				dp::crudkitException('Page - add title text: invalid page type(s) "%s" provided. Page "%s"', __CLASS__, __FUNCTION__, implode(', ', $pageTypesNotFound), $this->id);
			}
		}
		
		$this->titleTexts[] = ['text' => $text, 'page-types' => $pageTypes];
		
		return $this;
	}
	
	/**
	 * Adds a button to the page that acts as a link to related records.
	 * 
	 * Example: link from author(one) to his books(many):
	 * ```php
	 * $pageDescriptor->addOneToManyLink('author-books', 'Books', 'Book Link', 'book', [new FilterDefinition('author_id', '=', 'field', 'id')], true, true);
	 * //Think in sql: select * from book where author_id = author.id;
	 * ```
	 * @param string $name Unique name of this link
	 * @param string $label Label of the link
	 * @param string $columnLabel Label of the column in list view
	 * @param string $toTable Linked to table id (crudkit table id)
	 * @param string $toPage Like to page id (crudkit page id)
	 * @param FilterDefinition[] $filterDefinitions Array of FilterDefinition describing the relation.
	 * @param bool $onList Show on list page
	 * @param bool $onCard Show on card page
	 * @stackable
	 */
	public function addOneToManyLink(string $name, string $label, string $columnLabel, string $toTable, string $toPage, array $filterDefinitions, bool $onList = true, bool $onCard = true)
	{
		$callback = function($record, $pageDescriptor, $action)
		{
			$c = 0;
			$urlParameters = [];
			$urlParameters['page-id'] = $action->data['to-page'];
			if(!dp::e($action['filter-definitions']))
			{
				foreach($action->data['filter-definitions'] as $index => $filterDefinition)
				{
					$filter = $filterDefinition->toFilter($record);
					$filter->appendToUrlParams($urlParameters, $index);
				}
			}
			
			return Response::redirectToAction('\Alddesign\Crudkit\Controllers\CrudkitController@listView', $urlParameters);
		};
		
		$this->addAction($name, $label, $columnLabel, $callback, $onList, $onCard, '', 'primary', 'both', false);
		
		//Additional data for this type of action:
		$this->actions[$name]->data['to-table'] = $toTable;
		$this->actions[$name]->data['to-page'] = $toPage;
		$this->actions[$name]->data['filter-definitions'] = $filterDefinitions;
		
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
	 * @param string $name Unique name of this action
	 * @param string $label Label of the button
	 * @param string $columnLabel Label of the column in list view
	 * @param callable $callback Callback function to execute when pressing the button. This callback has $record, $pageDescriptor, $action(this is what you definde here) as parameters.
	 * @param bool $onList (optional) Show on list page
	 * @param bool $onCard (optional) Show on card page
	 * @param string $faIcon (optional) Icon for the Button. (Font Awesome icon name)
	 * @param string $btnClass (optional) ''|'default'|'primary'|'info'|'success'|'danger'|'warning'. (Admin LTE Button class)
	 * @param string $position (optional) 'top'|'bottom'|'both'. Position on card pages.
	 * @param bool $enabled (optional) Enabled by default
	 * @stackable
	*/
	public function addAction(string $name, string $label, string $columnLabel, callable $callback, bool $onList = true, bool $onCard = true, string $faIcon = '', string $btnClass = '', string $position = '', bool $enabled = true)
	{
		$this->addActionObject($name, new Action($name, $label, $columnLabel, $callback, $onList, $onCard, $faIcon, $btnClass, $position, $enabled));
		return $this;
	}

	/**
	 * Add a button with a custom action to this page.
	 * 
	 * @param string $name Unique name of this action
	 * @param Action $action The Action
	 * @stackable
	 */
	public function addActionObject(string $name, Action $action)
	{
		//Callback functions parameters: $record, $pageDescriptor, $action
		if(dp::e($name))
		{
			dp::crudkitException('Provide a name form the action.', __CLASS__, __FUNCTION__);
		}
		
		if(isset($this->actions[$name]))
		{
			dp::crudkitException('Action "%s" already exists on page "%s"!', __CLASS__, __FUNCTION__, $name, $this->id);
		}
		
		$this->actions[$name] = $action;
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
	 */
	public function addSection(string $title, string $fromColumnName, string $toColumnName = '')
	{
		//Test if columns exists
		$columns = $this->table->getColumns(true);
		if(dp::e($columns))
		{
			dp::crudkitException('No columns found in table "%s".', __CLASS__, __FUNCTION__, $this->table->getName());
		}
		
		//Test if Columns exist
		if(!in_array($fromColumnName, $columns, true))
		{
			dp::crudkitException('From-column "%s" was not found in table "%s".', __CLASS__, __FUNCTION__, $fromColumnName, $this->table->getName());
		}
		if(!dp::e($toColumnName) && !in_array($toColumnName, $columns, true))
		{
			dp::crudkitException('To-column "%s" was not found in table "%s".', __CLASS__, __FUNCTION__, $fromColumnName, $this->table->getName());
		}
		
		//Get Last Column, if not specified
		$toColumnName = dp::e($toColumnName) ? end($columns) : $toColumnName;
		
		//Swap if order is wrong
		$fromIndex = array_search($fromColumnName, array_keys($columns));
		$toIndex = array_search($toColumnName, array_keys($columns));
		if($fromIndex > $toIndex)
		{
			dp::swap($fromIndex, $toIndex);
			dp::swap($fromColumnName, $toColumnName);
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
				dp::crudkitException('Sections "%s" and "%s" overlap each other.', __CLASS__, __FUNCTION__, $section['title'], $title);
			}
		}
		
		//Finally ok:
		$this->sections[] = new Section($title, $fromColumnName, $toColumnName);
		
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
	 * Register event handler. Occours when a list page is beeing openend.
	 * 
	 * ```php
	 * //Reference parametes (&) can be modified inside the function. Very powerfull.
	 * $f = function(&$pageDescriptor, &$tableDescriptor, &$records){...};
	 * authHelper->onAfterLogin($f);
	 * ``` 
	 * @param callable $callback Callback function which is beeing called if this event occours.
	 * @event
	 */
	public function onOpenList(callable $callback)
	{
		$this->callbacks['onopenlist'] = $callback;
		return $this;
	}
	
	/**
	 * Register event handler. Occours when a card page is beeing openend.
	 * 
	 * ```php
	 * //Reference parametes (&) can be modified inside the function. Very powerfull.
	 * $f = function(&$pageDescriptor, &$tableDescriptor, &$record){...};
	 * authHelper->onAfterLogin($f);
	 * ``` 
	 * @param callable $callback Callback function which is beeing called if this event occours.
	 * @event
	 */
	public function onOpenCard(callable $callback)
	{
		$this->callbacks['onopencard'] = $callback;
		return $this;
	}
	
	/**
	 * Register event handler. Occours when a card page (for creating a new record) is beeing openend.
	 * 
	 * ```php
	 * //Reference parametes (&) can be modified inside the function. Very powerfull.
	 * $f = function(&$pageDescriptor, &$tableDescriptor, &$columns){...};
	 * authHelper->onAfterLogin($f);
	 * ``` 
	 * @param callable $callback Callback function which is beeing called if this event occours.
	 * @event
	 */
	public function onOpenCreateCard(callable $callback)
	{
		$this->callbacks['onopencreatecard'] = $callback;;
		return $this;
	}
	
	/**
	 * Register event handler. Occours when a card page (for editing a record) is beeing openend..
	 * 
	 * ```php
	 * //Reference parametes (&) can be modified inside the function. Very powerfull.
	 * $f = function(&$pageDescriptor, &$tableDescriptor, &$record){...};
	 * authHelper->onAfterLogin($f);
	 * ``` 
	 * @param callable $callback Callback function which is beeing called if this event occours.
	 * @event
	 */
	public function onOpenUpdateCard(callable $callback)
	{
		$this->callbacks['onopenupdatecard'] = $callback;
		return $this;
	}

	/**
	 * Register event handler. Occours when a page is beeing openend as a Chart.
	 * ```php
	 * //Reference parametes (&) can be modified inside the function. Very powerfull.
	 * $f = function(&$pageDescriptor, &$tableDescriptor){...};
	 * authHelper->onAfterLogin($f);
	 * ``` 
	 * @param callable $callback Callback function which is beeing called if this event occours.
	 * @event
	 */
	public function onOpenChartPage(callable $callback)
	{
		$this->callbacks['onopenchartpage'] = $callback;
		return $this;
	}
	
	/**
	 * Register event handler. Occours before a record is beeing inserted into the database.
	 * 
	 * ```php
	 * //Reference parametes (&) can be modified inside the function. Very powerfull.
	 * $f = function(&$pageDescriptor, &$tableDescriptor, &$recordData){...};
	 * authHelper->onAfterLogin($f);
	 * ``` 
	 * @param callable $callback Callback function which is beeing called if this event occours.
	 * @event
	 */
	public function onBeforeCreate(callable $callback)
	{
		$this->callbacks['onbeforecreate'] = $callback;
		return $this;
	}
	
	/**
	 * Register event handler. Occours after a record is beeing inserted into the database.
	 * 
	 * ```php
	 * //Reference parametes (&) can be modified inside the function. Very powerfull.
	 * $f = function(&$pageDescriptor, &$tableDescriptor, $primaryKeyValues){...};
	 * authHelper->onAfterLogin($f);
	 * ``` 
	 * @param callable $callback Callback function which is beeing called if this event occours.
	 * @event
	 */
	public function onAfterCreate(callable $callback)
	{
		$this->callbacks['onaftercreate'] = $callback;
		return $this;
	}
	
	/**
	 * Register event handler. Occours before a record is beeing updated.
	 * 
	 * ```php
	 * //Reference parametes (&) can be modified inside the function. Very powerfull.
	 * $f = function(&$pageDescriptor, &$tableDescriptor, &$recordData){...};
	 * authHelper->onAfterLogin($f);
	 * ``` 
	 * @param callable $callback Callback function which is beeing called if this event occours.
	 * @event
	 */
	public function onBeforeUpdate(callable $callback)
	{
		$this->callbacks['onbeforeupdate'] = $callback;
		return $this;
	}
	
	/**
	 * Register event handler. Occours after a record is beeing updated.
	 * 
	 * ```php
	 * //Reference parametes (&) can be modified inside the function. Very powerfull.
	 * $f = function(&$pageDescriptor, &$tableDescriptor, &$primaryKeyValues){...};
	 * authHelper->onAfterLogin($f);
	 * ``` 
	 * @param callable $callback Callback function which is beeing called if this event occours.
	 * @event
	 */
	public function onAfterUpdate(callable $callback)
	{
		$this->callbacks['onafterupdate'] = $callback;
		return $this;
	}
	
	/**
	 * Register event handler. Occours before a record is beeing deleted from the database.
	 * 
	 * ```php
	 * //Reference parametes (&) can be modified inside the function. Very powerfull.
	 * $f = function(&$pageDescriptor, &$tableDescriptor, &$primaryKeyValues){...};
	 * authHelper->onAfterLogin($f);
	 * ``` 
	 * @param callable $callback Callback function which is beeing called if this event occours.
	 * @event
	 */
	public function onBeforeDelete(callable $callback)
	{
		$this->callbacks['onbeforedelete'] = $callback;
		return $this;
	}
	
	/**
	 * Register event handler. Occours after a record is beeing deleted from the database.
	 * 
	 * ```php
	 * //Reference parametes (&) can be modified inside the function. Very powerfull.
	 * $f = function(&$pageDescriptor, &$tableDescriptor, &$primaryKeyValues){...};
	 * authHelper->onAfterLogin($f);
	 * ``` 
	 * @param callable $callback Callback function which is beeing called if this event occours.
	 * @event
	 */
	public function onAfterDelete(callable $callback)
	{
		$this->callbacks['onafterdelete'] = $callback;
		return $this;
	}
/* #endregion */

/* #region CRUD */
	/** 
	 * Creates a record in the table of this page
	 * @param array $recordData (preprocessed)
	 * @return array The primary key values
	 * @internal
	 */
	public function create(array $recordData)
	{
		return $this->getTable()->createRecord($recordData);
	}

	/** 
	 * Updates a record from the table of this page
	 * @param array $primaryKeyValues (preprocessed)
	 * @param array $columnValues (preprocessed)
	 * @return array The (maybe) new primary key values
	 * @internal
	 */
	public function update(array $primaryKeyValues, array $columnValues)
	{
		return $this->table->updateRecord($primaryKeyValues, $columnValues);
	}

	/**
	 * Deletes a record from the table of this page
	 * @param array $primaryKeyValues (preprocessed)
	 * @return bool Success
	 */
	public function delete(array $primaryKeyValues)
	{
		return $this->getTable()->deleteRecord($primaryKeyValues);
	}

	/**
	 * Reads a record from the table of this page
	 * @param array $primaryKeyValues
	 * @param Filter[] $filters
	 * @return array Array with a single element
	 * @internal
	 */
    public function readRecord(array $primaryKeyValues, array $filters = [])
	{
		return $this->table->readRecord($primaryKeyValues, $filters);
	}
	
	/**
	 * Reads multiple records from the table of this page
	 * @param int $pageNumber Pagination offset
	 * @param string $searchColumnName The column name to apply the $searchText (if existing)
	 * @param string $searchText The search text (if existing)
	 * @param Filter[] $filters
	 * @param bool $trimText Trim text >50 chars
	 * @param bool $rawData Get the data unprocessed from the DB (watch out here!)
	 * @internal
	 */
	public function readRecords(int $pageNumber = 1, string $searchColumnName = null, string $searchText = null, array $filters = [], bool $trimText = true, bool $rawData = false)
	{
		return $this->table->readRecords($pageNumber, $searchColumnName, $searchText, $filters, $trimText, $rawData);
	}
/* #endregion */
}
