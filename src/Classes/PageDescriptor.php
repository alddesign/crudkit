<?php
declare(strict_types=1);
namespace Alddesign\Crudkit\Classes;

use Response;
use DB;
use Exception;
use Alddesign\Crudkit\Controllers\AdminPanelController;
use Alddesign\Crudkit\Classes\DataProcessor as dp;

//++AD
// Modified this Class and other files to support multiple primary key columns
//Removed all Log::debug();
//--AD

class PageDescriptor
{
    private $id = '';
	private $name = '';
	private $category = '';
	private $icon = '';
	private $titleTexts = [];
	
    private $table = null;
	private $summaryColumns = [];
	private $cardLinkColumns = [];
	
	/**
	* Displaying fields in separated areas called "Sections." 
	* Format = [['title' => '<title>', 'from' => '<fromField>', 'to' => '<toField>'],[...]]
	*/
	private $sections = []; 
	
	/**
	* Adding Buttons with special callback functions to listview/cardview
	* Format = ['<name>' => ['label' => '<label>', 'column-label' => '<column-label>', 'callback' => callback, 'fa-icon' => '<fa-icon>', 'btn-class' => '<btn-class>', 'on-list' => <bool on-list>, 'on-card' => <bool on-card> ]]
	* Parameters for the callback function are: $record, $pageDescriptor, $action (the action from this array)
	*/
	private $actions = []; 
	
    private $createAllowed = true;
    private $updateAllowed = true;
    private $deleteAllowed = true;
	private $confirmDelete = true;
	private $exportAllowed = true;
	private $chartAllowed = true;	

	//Callbacks
	private $onOpenListCallback = null; //Parameters $pageDescriptor, $tableDescriptor, $records
	private $onOpenCardCallback = null; //Parameters $pageDescriptor, $tableDescriptor, $record
	private $onOpenUpdateCardCallback = null; //Parameters $pageDescriptor, $tableDescriptor, $record
	private $onOpenCreateCardCallback = null; //Parameters $pageDescriptor, $tableDescriptor, $columns
	private $onOpenChartPageCallback = null; //Parameters $pageDescriptor, $tableDescriptor
	private $onBeforeCreateCallback = null; //Parameters $pageDescriptor, $tableDescriptor, $recordData
	private $onAfterCreateCallback = null;//Parameters $pageDescriptor, $tableDescriptor, $primaryKeyValues
	private $onBeforeUpdateCallback = null;//Parameters $pageDescriptor, $tableDescriptor, $recordData
	private $onAfterUpdateCallback = null;//Parameters $pageDescriptor, $tableDescriptor, $primaryKeyValues
	private $onBeforeDeleteCallback = null;//Parameters $pageDescriptor, $tableDescriptor, $primaryKeyValues
	private $onAfterDeleteCallback = null;//Parameters $pageDescriptor, $tableDescriptor, $primaryKeyValues

    public function __construct(string $name, string $id, TableDescriptor $table, string $category = '')
    {
        $this->name = $name;
        $this->id = $id;
		$this->table = $table;
		$this->category = $category;

		if(dp::e($id) || !preg_match('/^[a-zA-Z0-9_-]*$/', $id))
		{
			throw new Exception('Page - constructor: please provide a valid id for this page. Allowed characters: a-z, A-Z, 0-9, "_", "-"');
		}
		
		if(dp::e($name))
		{
			throw new Exception('Page - constructor: please provide a valid name this page.');
		}
		
		if(dp::e($table))
		{
			throw new Exception(sprintf('Page - constructor: please provide a valid table for page "%s".', $id));
		}
    }
	
// ### GET/SET ###################################################################################################################################################################################
 	
	public function getName()
    {
        return $this->name;
    }

    public function getId()
    {
        return $this->id;
    }
	
    public function createAllowed()
    {
        return $this->createAllowed;
    }
    
	public function allowCreate()	
	{ 
		$this->createAllowed = true;
		return $this; 
	}
	
	public function denyCreate()
	{ 
		$this->createAllowed = false; 
		return $this; 
	}

    public function updateAllowed()
    {
        return $this->updateAllowed;
    }
	
	public function allowUpdate()	
	{ 
		$this->updateAllowed = true; 
		return $this; 
	}
	
	public function denyUpdate()	
	{ 
		$this->updateAllowed = false; 
		return $this; 
	}

    public function deleteAllowed()
    {
        return $this->deleteAllowed;
    }
    
	public function allowDelete()	
	{ 
		$this->deleteAllowed = true; 
		return $this; 
	}
	
	public function denyDelete()
	{ 
		$this->deleteAllowed = false; 
		return $this; 
	}

	public function exportAllowed()
    {
        return $this->exportAllowed;
    }
	
    public function allowExport()	
	{ 
		$this->exportAllowed = true; 
		return $this; 
	}
	
	public function denyExport()
	{ 
		$this->exportAllowed = false; 
		return $this; 
	}
	
	public function chartAllowed()
    {
        return $this->chartAllowed;
    }
    
	public function allowChart()	
	{ 
		$this->chartAllowed = true; 
		return $this; 
	}
	
	public function denyChart()		
	{ 
		$this->chartAllowed = false; 
		return $this; 
	}
	
	public function getConfirmDelete()
	{
		return $this->confirmDelete;
	}
	
	public function confirmDelete(bool $value = true)
	{
		$this->confirmDelete = $value;
	}
	
	public function setCategory(string $category = null)
	{
		$this->category = $category;
		return $this;
	}
	
	public function getCategory()
	{
		return $this->category;
	}
	
	public function setIcon(string $icon = null)
	{
		$this->icon = $icon;
		return $this;
	}
	
	public function getIcon()
	{
		return $this->icon;
	}	
	
	/**
	* @param pageTypes specifies on which page types ('list', 'card', 'create', 'update', 'chart') this text will be shown.
	*/
	public function addTitleText(string $text, array $pageTypes = [])
	{
		if($pageTypes !== [])
		{
			$pageTypesNotFound = array_diff($pageTypes, ['list', 'card', 'create', 'update', 'chart']);
			if($pageTypesNotFound !== [])
			{
				throw new Exception(sprintf('Page - add title text: invalid page type(s) "%s" provided. Page "%s"', implode($pageTypesNotFound, ', '), $this->id));
			}
		}
		
		$this->titleTexts[] = ['text' => $text, 'page-types' => $pageTypes];
		
		return $this;
	}
	
	public function getTitleText(string $pageType)
	{
		foreach($this->titleTexts as $titleText)
		{
			//Empty ['pageTypes'] means: for all pages
			if($titleText['page-types'] === [] || in_array($pageType, $titleText['page-types'], true))
			{
				return $titleText['text'];
			}
		}
		
		return '';
	}
	
	/**
	* Adds a button with a custom action to list/card pages.
	*
	* @param string $name Unique name of this link
	* @param string $label Label of the link
	* @param string $columnLabel Label of the column in list view
	* @param string $toTable Linked to table id (crudkit table id)
	* @param string $toPage Like to page id (crudkit page id)
	* @param FilterDefinition[] $filterDefinitions Array of FilterDefinition describing the relation.
	* @param bool $onList Show on list page
	* @param bool $onCard Show on card page
	* @api
	*/
	public function addOneToManyLink(string $name, string $label, string $columnLabel, string $toTable, string $toPage, array $filterDefinitions, bool $onList = true, bool $onCard = true)
	{
		$callback = function($record, $pageDescriptor, $action)
		{
			$c = 0;
			$urlParameters = [];
			$urlParameters['page-id'] = $action['to-page'];
			if(!dp::e($action['filter-definitions']))
			{
				foreach($action['filter-definitions'] as $index => $filterDefinition)
				{
					$filter = $filterDefinition->toFilter($record);
					$filter->appendToUrlParams($urlParameters, $index);
				}
			}
			
			return Response::redirectToAction('\Alddesign\Crudkit\Controllers\AdminPanelController@listView', $urlParameters);
		};
		
		$this->addAction($name, $label, $columnLabel, $callback, $onList, $onCard, '', 'primary', 'both', false);
		
		//Additional data for this type of action:
		$this->actions[$name]['to-table'] = $toTable;
		$this->actions[$name]['to-page'] = $toPage;
		$this->actions[$name]['filter-definitions'] = $filterDefinitions;
		
		return $this;
	}
	
	/**
	* Add a button with a custom action to list/card pages.
	* @param string $name Unique name of this action
	* @param string $label Label of the button
	* @param string $columnLabel Label of the column in list view
	* @param callable $callback Callback function to execute when pressing the button. This callback has $record, $pageDescriptor, $action as parameters.
	* @param bool $onList Show on list page
	* @param bool $onCard Show on card page
	* @param string $faIcon Icon for the Button. (Font Awesome icon name)
	* @param string $btnClass ''|'default'|'primary'|'info'|'success'|'danger'|'warning'. (Admin LTE Button class)
	* @param string $position 'top'|'bottom'|'both'. Position on card pages.
	* @param bool $disabled 
	*/
	public function addAction($name, string $label, string $columnLabel, callable $callback, bool $onList = true, bool $onCard = true, string $faIcon = '', string $btnClass = '', string $position = '', bool $disabled = false)
	{
		//Callback functions parameters: $record, $pageDescriptor, $action
		if(dp::e($name))
		{
			throw new Exception('Page - add action: Provide a name form the action.');
		}
		
		if(isset($this->actions[$name]))
		{
			throw new Exception(sprintf('Page - add action: aktion "%s" already exists on page "%s"!', $name, $this->id));
		}
		
		if(!in_array($position, ['top','bottom','both'], true))
		{
			$position = 'both';
		}
		
		$this->actions[$name] = 
		[
			'label' => $label,
			'column-label' => $columnLabel,
			'callback' => $callback,
			'on-list' => $onList,
			'on-card' => $onCard,
			'fa-icon' => $faIcon,
			'btn-class' => $btnClass,
			'position' => $position,
			'disabled' => $disabled
		];
		
		return $this;
	}
	
	public function removeAction(string $name)
	{
		if(!isset($this->actions[$name]))
		{
			throw new Exception(sprintf('Remove action failed: action "%s" cannot be found on page "%s".', $name, $this->id));
		}
		
		unset($this->actions[$name]);
	}
	
	public function getActions(string $name = '')
	{
		if(!dp::e($name))
		{
			if(isset($this->actions[$name])) 
			{
				return $this->actions[$name]; 
			}
			throw new Exception(sprintf('Get action failed: action "%s" cannot be found on page "%s".', $name, $this->id));
		}
		return $this->actions;
	}
	
	//Call after you have set the tabel
	public function addSection(string $title, string $fromColumnName, string $toColumnName = '', string $titleText = '')
	{
		//Test if columns exists
		$columns = $this->table->getColumns(true);
		if(dp::e($columns))
		{
			throw new Exception(sprintf('Add secion: no columns found in table "%s".', $this->table->getName()));
		}
		
		//Test if Columns exist
		if(!in_array($fromColumnName, $columns, true))
		{
			throw new Exception(sprintf('Add section: from-column "%s" was not found in table "%s".', $fromColumnName, $this->table->getName()));
		}
		if(!dp::e($toColumnName) && !in_array($toColumnName, $columns, true))
		{
			throw new Exception(sprintf('Add section: to-column "%s" was not found in table "%s".', $toColumnName, $this->table->getName()));
		}
		
		//Get Last Column, if not specified
		$toColumnName = dp::e($toColumnName) ? $columns[count($columns)-1] : $toColumnName;
		
		//Test order
		$from = array_search($fromColumnName, $columns);
		$to = array_search($toColumnName, $columns);
		if($from >= $to)
		{
			throw new Exception(sprintf('Add section: from-column "%s" has to be before to-column "%s".', $fromColumnName, $toColumnName));
		}
		
		//Test crossings
		foreach($this->sections as $section)
		{
			$xfrom = array_search($section['from'], $columns);
			$xto = array_search($section['to'], $columns);
			if(
			   ($from >= $xfrom && $from <= $xto) || 
			   ($to >= $xfrom && $to <= $xfrom) ||
			   ($from <= $xfrom && $to >= $xto)
			  )
			{
				throw new Exception(sprintf('Add section: sections "%s" and "%s" overlap each other.', $section['title'], $title));
			}
		}
		
		//Finally ok:
		$this->sections[] = 
		[
			'title' => $title, 
			'from' => $fromColumnName, 
			'to' => $toColumnName,
			'title-text' => $titleText
		];
		
		return $this;
		
	}
	
	public function getSections()
	{
		return $this->sections;
	}

// ### CURD Functions ###################################################################################################################################################################################
    public function create($recordData)
    {
		return $this->getTable()->createRecord($recordData);
    }

    public function update(array $primaryKeyValues, array $columnValues)
    {
		 return $this->table->updateRecord($primaryKeyValues, $columnValues);
    }

    public function delete(array $primaryKeyValues)
    {
		$this->getTable()->deleteRecord($primaryKeyValues);
    }
	
// ### CALLBACKS #########################################################################################
	public function executeCallback(string $name, &$param1 = null)
	{
		$callback = null;
		$name = strtolower($name);
			
		switch($name)
		{
			case 'onopenlist' 		: $callback = $this->onOpenListCallback; break;
			case 'onopencard' 		: $callback = $this->onOpenCardCallback; break;
			case 'onopencreatecard' : $callback = $this->onOpenCreateCardCallback; break;
			case 'onopenupdatecard' : $callback = $this->onOpenUpdateCardCallback; break;
			case 'onopenchartpage'	: $callback = $this->onOpenChartPageCallback; break;
			case 'onbeforecreate' 	: $callback = $this->onBeforeCreateCallback; break;
			case 'onaftercreate' 	: $callback = $this->onAfterCreateCallback; break;
			case 'onbeforeupdate' 	: $callback = $this->onBeforeUpdateCallback; break;
			case 'onafterupdate' 	: $callback = $this->onAfterUpdateCallback; break;
			case 'onbeforedelete' 	: $callback = $this->onBeforeDeleteCallback; break;
			case 'onafterdelete' 	: $callback = $this->onAfterDeleteCallback; break;
			default : throw new Exception(sprintf('Page - execute callback: invalid callback "%s".', $name)); break;
		}
		
		if(!dp::e($callback))
		{
			call_user_func_array($callback, array(&$this, &$this->table, &$param1));
		}
	}
	public function onOpenList(callable $callback)
	{
		$this->onOpenListCallback = $callback;
		return $this;
	}
	
	public function onOpenCard(callable $callback)
	{
		$this->onOpenCardCallback = $callback;
		return $this;
	}
	
	public function onOpenCreateCard(callable $callback)
	{
		$this->onOpenCreateCardCallback = $callback;
		return $this;
	}
	
	public function onOpenUpdateCard(callable $callback)
	{
		$this->onOpenUpdateCardCallback = $callback;
		return $this;
	}
	
	public function onOpenChartPage(callable $callback)
	{
		$this->onOpenChartPageCallback = $callback;
		return $this;
	}
	
	public function onBeforeCreate(callable $callback)
	{
		$this->onBeforeCreateCallback = $callback;
		return $this;
	}
	
	public function onAfterCreate(callable $callback)
	{
		$this->onAfterCreateCallback = $callback;
		return $this;
	}
	
	public function onBeforeUpdate(callable $callback)
	{
		$this->onBeforeUpdateCallback = $callback;
		return $this;
	}
	
	public function onAfterUpdate(callable $callback)
	{
		$this->onAfterUpdateCallback = $callback;
		return $this;
	}
	
	public function onBeforeDelete(callable $callback)
	{
		$this->onBeforeDeleteCallback = $callback;
		return $this;
	}
	
	public function onAfterDelete(callable $callback)
	{
		$this->onAfterDeleteCallback = $callback;
		return $this;
	}
	
// ### SCHEMA ############################################################################################
    public function getTable()
    {
        return $this->table;
    }
	
	public function setSummaryColumnsAll()
	{
		$this->summaryColumns = $this->table->getColumns(true);
		
		return $this;
	}

    public function setSummaryColumns(array $summaryColumns)
    {
		$columnsNotFound = array_diff($summaryColumns, $this->table->getColumns(true));
		
		if(!dp::e($columnsNotFound))
        {
            throw new Exception(sprintf('Page - set summary columns: following summary columns were not found on page "%s" (table "%s"): "%s"', $this->id, $this->table->getName(), implode(', ',$columnsNotFound)));
        }
		
		$this->summaryColumns = $summaryColumns;
		
        return $this;
    }

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
	
	public function setCardLinkColumns(array $cardLinkColumns)
    {
		$columnsNotFound = array_diff($cardLinkColumns, array_keys($this->table->getColumns()));
		
		if(!dp::e($columnsNotFound))
        {
            throw new Exception(sprinft('Page - set card link columns: following card link columns were not found on page "%s" (table "%s"): "%s": ', $this->id, $this->table->getName(), implode(', ',$columnsNotFound)));
        }
		
		$this->cardLinkColumns = $cardLinkColumns;
		
        return $this;
    }

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
            $columns[$column->name] = $tableColumns[$columnName];
        }

        return $columns;
    }
	
// ### DATA ############################################################################################		
	/**
	* @param [] primaryKeyValues Array with value(s) of the primary key field(s) 
	* @param Filter[] filters
	* @package Data
	*/
    public function readRecord(array $primaryKeyValues, array $filters = [])
	{
		return $this->table->readRecord($primaryKeyValues, $filters);
	}
	
	/**
	* @param Filter[] filters
	*/
	public function readRecords(int $pageNumber = 1, string $searchColumnName = null, string $searchText = null, array $filters = [], bool $trimText = true, bool $rawData = false)
	{
		return $this->table->readRecords($pageNumber, $searchColumnName, $searchText, $filters, $trimText, $rawData);
	}
}
