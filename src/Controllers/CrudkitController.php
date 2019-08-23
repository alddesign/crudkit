<?php 
/** Controller */
namespace Alddesign\Crudkit\Controllers;

use \Exception;
use \DateTime;
use Illuminate\Http\Request;
use Illuminate\Contracts\Routing;
use Alddesign\Crudkit\Classes\DataProcessor;
use Alddesign\Crudkit\Classes\DataProcessor as dp;
use Alddesign\Crudkit\Classes\PageStore;
use Alddesign\Crudkit\Classes\AuthHelper;
use Alddesign\Crudkit\Classes\Filter;
use Alddesign\Crudkit\Classes\FilterDefinition;
use \XML_Serializer;

use DB;
use Response;
use URL;
use Session;
use Storage;

/**
 * Crudkit Controller
 * 
 * Main code areas are tagged with "view", "action" and "helper"
 * @internal
 */
class CrudkitController extends \App\Http\Controllers\Controller
{
	/** @var PageStore $pageStore All the pages. */
	private $pageStore = null;
	/** @var AuthHelper $authHelper Holding user/permission related data. */ 
	private $authHelper = null;

    /** Creates a new controller instance */
    public function __construct()
    {
    }
	
	/** 
	 * Setting pages and authHelper.
	 * 
	 * @param PageStroe $pageStore
	 * @param AuthHelper $authHelper 
	 */
	public function init(PageStore $pageStore, AuthHelper $authHelper = null)
	{
		$this->pageStore = $pageStore;
		$this->authHelper = dp::e($authHelper) ? (new AuthHelper()) : $authHelper;
	}
	
	/** 
	 * Starting generation of CurdkitServiceProvider.php
	 * 
     * @param  \Illuminate\Http\Request $request
	 */
	public function autoGenerate(Request $request)
	{
		$generator = new \Alddesign\Crudkit\Classes\Generator();
		return $generator->generateServiceProvider();
	}

	/** 
	 * Brings up the startpage.
	 * 
     * @param  \Illuminate\Http\Request $request
	 * @view
	 */
    public function index(Request $request)
	{
		$this->authHelper->checkAuth('', '', true); //### Auth
		$this->authHelper->checkStartpage();
	}
	
	/**
     * Displays records as summary list page
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
	 * @view
     */
    public function listView(Request $request)
    {	
		//Load PageId and authenticate
		$pageId 		= request('page-id', '');
		$pageDescriptor = $this->pageStore->getPageDescriptor($pageId); //Get Page per name, or first page
		$pageId 		= $pageDescriptor->getId();
		$this->authHelper->checkAuth('list', $pageId); //check-authentication
		
		//Load Data from Request

		$pageNumber 		= (int)request('page-number', 1) > 0 		? (int)request('page-number', 1) 	: 1;
		$searchColumnName 	= !dp::e(request('search-column-name', '')) ? request('search-column-name', '') : '';
		$searchText 		= !dp::e(request('search-text', '')) 		? request('search-text', '') 		: '';
		$resetSearch 		= request('reset-search', '') === 'true';
		$filters 			= $this->getFiltersFromRequest($request);
		
		//Process Request Data
		$qrCode = $this->getQrCodeTag($request);
		if($resetSearch)
		{
			$pageNumber = 1;
			$searchText = '';
			$searchColumnName = '';
		}

		//Load Data from DB
		$records = $pageDescriptor->readRecords($pageNumber, $searchColumnName, $searchText, $filters);
		//Process DB Data
		$paginationUrls = $this->getPaginationUrls($pageNumber, $pageId, $records['total'], $searchText, $searchColumnName, $filters);
		
		$pageDescriptor->triggerEvent('onOpenList', $records['records']); //event-trigger

        return( view('crudkit::list-records', 
		[
			'pageType' 				=> 'list',
            'pageId' 				=> $pageId,
			'pageName' 				=> $pageDescriptor->getName(),
			'pageTitleText' 		=> $pageDescriptor->getTitleText('list'),
			'summaryColumns' 		=> $pageDescriptor->getSummaryColumns(false),
            'primaryKeyColumns' 	=> $pageDescriptor->getTable()->getPrimaryKeyColumns(false),
			'cardPageUrls' 			=> $this::getCardPageUrls($records['records'], $pageDescriptor->getTable()->getPrimaryKeyColumns(true), $pageId),
			'cardPageUrlColumns' 	=> $pageDescriptor->getCardLinkColumns(true),
			'manyToOneUrls' 		=> $this->getRelationUrls($records['records'], $pageDescriptor->getTable()->getColumns(), 'manytoone'),
			'oneToManyUrls' 		=> $this->getRelationUrls($records['records'], $pageDescriptor->getTable()->getColumns(), 'onetomany'),
			'chartPageUrl' 			=> $this->getChartPageUrl($pageId, $searchText, $searchColumnName, $filters),
			'exportCsvUrl' 			=> $this->getExportCsvUrl($pageId, $searchText, $searchColumnName, $filters),
			'exportXmlUrl' 			=> $this->getExportXmlUrl($pageId, $searchText, $searchColumnName, $filters),
			'actions' 				=> $pageDescriptor->getActions(),
			'records' 				=> $records,
			'hasFilters' 			=> !dp::e($filters),
			'filters' 				=> $filters,
			'hasSearch' 			=> ($searchText !== ''),
			'searchText' 			=> $searchText,
			'searchColumnName' 		=> $searchColumnName,
			'recordsPerPage' 		=> (int)config('crudkit.records_per_page', 8),
			'pageNumber' 			=> $pageNumber,
			'paginationUrls' 		=> $paginationUrls,
			'chartAllowed' 			=> $pageDescriptor->getChartAllowed(),
			'createAllowed' 		=> $pageDescriptor->getCreateAllowed(),
			'exportAllowed' 		=> $pageDescriptor->getExportAllowed(),
            'pageMap' 				=> $this->pageStore->getPageMap(),
			'qrCode' 				=> $qrCode,
        ]));
    }

	/**
     * Displays records as card page
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
	 * @view
     */
    public function cardView(Request $request)
    {		
		//Load PageId and authenticate
		$pageId 		= request('page-id', '');
		$pageDescriptor = $this->pageStore->getPageDescriptor($pageId, true);
		$this->authHelper->checkAuth('card', $pageId); //check-authentication
		
		//Load Data from Request
		$primaryKeyValues 	= $this->getPrimaryKeyValuesFromRequest($request);
		$filters 			= $this->getFiltersFromRequest($request);
		
		//Process Reqest Data
		$qrCode 				= $this->getQrCodeTag($request);
		$listPageUrl 			= $this->authHelper->userHasAccessTo(session('crudkit-userid'), $pageId, 'list') ? URL::action('\Alddesign\Crudkit\Controllers\CrudkitController@listView', ['page-id' => $pageId]) : '';
		$deleteUrl				= URL::action('\Alddesign\Crudkit\Controllers\CrudkitController@deleteRecord', $this->getUrlParameters($pageId, null, null, null, null, $primaryKeyValues));
		$updateUrl				= URL::action('\Alddesign\Crudkit\Controllers\CrudkitController@updateView', $this->getUrlParameters($pageId, null, null, null, null, $primaryKeyValues));
		
		//Load Data from DB
		$record = $pageDescriptor->readRecord($primaryKeyValues, $filters);
		
		//Process DB Data
		//*nothing*
		
		$pageDescriptor->triggerEvent('onOpenCard', $record); //event-trigger
		
        return view('crudkit::card-record', [
			'pageType' 				=> 'card',
            'pageId' 				=> $pageId,
			'pageTitleText' 		=> $pageDescriptor->getTitleText('card'),
            'updateAllowed' 		=> $pageDescriptor->getUpdateAllowed(),
            'deleteAllowed' 		=> $pageDescriptor->getDeleteAllowed(),
			'confirmDelete'			=> $pageDescriptor->confirmDelete(),
            'primaryKeyColumns' 	=> $pageDescriptor->getTable()->getPrimaryKeyColumns(true),
			'primaryKeyValues' 		=> $primaryKeyValues,
			'deleteUrl'				=> $deleteUrl,
			'updateUrl'				=> $updateUrl,
			'manyToOneUrls' 		=> $this->getRelationUrls($record, $pageDescriptor->getTable()->getColumns(), 'manytoone', true),
			'oneToManyUrls' 		=> $this->getRelationUrls($record, $pageDescriptor->getTable()->getColumns(), 'onetomany', true),
			'listPageUrl' 			=> $listPageUrl,
			'sections' 				=> $pageDescriptor->getSections(),
			'actions' 				=> $pageDescriptor->getActions(),
            'record' 				=> $record,
			'columns' 				=> $pageDescriptor->getTable()->getColumns(),
            'pageName' 				=> $pageDescriptor->getName(),
            'pageMap' 				=> $this->pageStore->getPageMap(),
			'qrCode' 				=> $qrCode
        ]);
    }

    /**
     * Edit record
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
	 * @view
     */
    public function updateView(Request $request)
    {		
		//Load PageId and authenticate
		$pageId 		= request('page-id', '');
		$pageDescriptor = $this->pageStore->getPageDescriptor($pageId, true);
		$this->authHelper->checkAuth('update', $pageId); //check-authentication
		
		//Load Data from Request
        $pageId 			= request('page-id', '');
		$primaryKeyValues 	= $this::getPrimaryKeyValuesFromRequest($request);
		
		//Process Request Data
		$qrCode 	= $this->getQrCodeTag($request);
		$columns 	= $pageDescriptor->getTable()->getColumns();
		
		//Load Data from DB
		$record = $pageDescriptor->readRecord($primaryKeyValues);
		
		//Process DB Data
		//*nothing*
		
		$pageDescriptor->triggerEvent('onOpenUpdateCard', $record); //event-trigger_

        return view('crudkit::create-update-record', [
            'pageType' 				=> 'update',
			'pageId' 				=> $pageId,
			'pageTitleText' 		=> $pageDescriptor->getTitleText('update'),
			'pageName' 				=> $pageDescriptor->getName(),
			'columns' 				=> $columns,
			'sections' 				=> $pageDescriptor->getSections(),
            'primaryKeyColumns' 	=> $pageDescriptor->getTable()->getPrimaryKeyColumns(true),
			'manyToOneValues' 		=> $pageDescriptor->getTable()->getManyToOneColumnValues(),
			'htmlInputAttributes' 	=> $this->getHtmlInputAttributes($columns),
            'pageMap' 				=> $this->pageStore->getPageMap(),
			'record' 				=> $record,
			'qrCode' 				=> $qrCode
        ]);
    }

    /**
     * Add record
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
	 * @view
     */
    public function createView(Request $request)
    {
		//Load PageId and authenticate
		$pageId 		= request('page-id', '');
		$pageDescriptor = $this->pageStore->getPageDescriptor($pageId, true);
		$this->authHelper->checkAuth('create', $pageId); //check-authentication
		
		//Load Data from Request
        //*nothing*
        
		//Process Reqest Data
		$columns 	= $pageDescriptor->getTable()->getColumns();
		$qrCode 	= $this->getQrCodeTag($request);
		
		//Load Data from DB
		//*nothing*
		
		//Process DB Data
		//*nothing*
		
		$pageDescriptor->triggerEvent('onOpenCreateCard', $columns); //event-trigger
		
        return view('crudkit::create-update-record', [
            'pageType' 				=> 'create',
			'pageId' 				=> $pageId,
			'pageTitleText' 		=> $pageDescriptor->getTitleText('create'),
			'pageName' 				=> $pageDescriptor->getName(),
			'columns' 				=> $columns,
			'sections' 				=> $pageDescriptor->getSections(),
            'primaryKeyColumns' 	=> $pageDescriptor->getTable()->getPrimaryKeyColumns(true),
			'htmlInputAttributes' 	=> $this->getHtmlInputAttributes($columns),
            'pageMap' 				=> $this->pageStore->getPageMap(),
			'qrCode' 				=> $qrCode
        ]);
    }
	
	/**
     * Displays records as a chart
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
	 * @view
     */
    public function chartView(Request $request)
    {				
		//Load PageId and authenticate
		$pageId 		= request('page-id', '');
		$pageDescriptor = $this->pageStore->getPageDescriptor($pageId, true);
		$this->authHelper->checkAuth('chart', $pageId); //check-authentication
		
		//Load Data from Request
		$filters = $this->getFiltersFromRequest($request);
		
		//Process Request Data
		$filterOperators =
		[
			'=' => '=', 
			'>' => '>', 
			'<' => '<', 
			'!=' => '!=', 
			'startswith' => dp::text('startswith'), 
			'endswith' => dp::text('endswith'), 
			'contains' => dp::text('contains')
		];
		$listPageUrl 							= $this->authHelper->userHasAccessTo(session('crudkit-userid'), $pageId, 'list') ? URL::action('\Alddesign\Crudkit\Controllers\CrudkitController@listView', ['page-id' => $pageId]) : '';
		$getChartDataUrlParamters 				= $this->getUrlParameters($pageId); //Dont include filters here - JS will load them from DOM. Search is disabled.
		$getChartDataUrlParamters['_token'] 	= csrf_token();
		$getChartDataUrlParamters 				= json_encode((object)$getChartDataUrlParamters);
		
		//Load Data from DB
		//*nothing* 
		//Data will be loaded dynamically via ajax
		
		//Process DB Data
		//*nothing*
		
		$pageDescriptor->triggerEvent('onOpenChartPage'); //event-trigger			
		
        return view('crudkit::chart-records', 
		[
			'pageType' 					=> 'chart',
            'pageId' 					=> $pageId,
			'pageName' 					=> $pageDescriptor->getName(),
			'pageTitleText' 			=> $pageDescriptor->getTitleText('chart'),
			'columns' 					=> $pageDescriptor->getTable()->getColumns(false),
            'primaryKeyColumns' 		=> $pageDescriptor->getTable()->getPrimaryKeyColumns(true),
			'listPageUrl' 				=> $listPageUrl,
			'getChartDataUrl' 			=> URL::action('\Alddesign\Crudkit\Controllers\CrudkitController@getChartData'),
			'getChartDataUrlParamters' 	=> $getChartDataUrlParamters,
			'hasFilters' 				=> !dp::e($filters),
			'filters' 					=> $filters,
			'filterOperators' 			=> $filterOperators,
            'pageMap' 					=> $this->pageStore->getPageMap()
        ]);
    }
	
	/**
     * Message page
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
	 * @view
	 */
    public function messageView(Request $request)
    {
		$title 		= request('title', '');
        $message 	= request('message', '');
		$type 		= request('type', 'info');
		$pageName 	= 'Information';
		
		//Valid options are: 'success','warning','info','danger'
		if(!in_array($type, ['success','warning','info','danger'], true))
		{
			$type = 'info';
		}
		
		switch($type)
		{
			case 'success' 	: $pageName = 'Erfolgreich'; break;
			case 'warning' 	: $pageName = 'Warnung'; break;
			case 'info' 	: $pageName = 'Information'; break;
			case 'danger' 	: $pageName = 'Fehler'; break;
			default 		: $pageName = 'Information';
		}		

        return view('crudkit::message', 
		[
			'pageType' 		=> 'message',
			'title' 		=> $title,
			'message' 		=> $message,
			'pageTitleText' => '',
			'messageType' 	=> $type,
            'pageId' 		=> '___MESSAGE___',
            'pageName' 		=> $pageName,
            'pageMap' 		=> $this->pageStore->getPageMap()
        ]);
    }

	/**
     * Login page
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
	 * @view
	 */
	public function loginView(Request $request)
	{		
		$loginMessage 		= session('crudkit-login-message', '');
		$loginMessageType 	= session('crudkit-login-message-type', '');
		session()->forget('crudkit-login-message');
		session()->forget('crudkit-login-message-type');

		return view('crudkit::login', 
		[
			'pageType' 			=> 'login',
			'loginMessage' 		=> $loginMessage,
			'loginMessageType' 	=> $loginMessageType,
			'pageTitleText' 	=> '',
            'pageId' 			=> '___LOGIN___',
			'pageName' 			=> 'Login',
            'pageMap' 			=> []
        ]);
	}
	
	// ### ACTIONS ###################################################################################################################################################################################
    /**
     * Deletes a record from the database
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
	 * @action
     */
    public function deleteRecord(Request $request)
    {
		//Load Data from Request
        $pageId = request('page-id', null);
		$this->authHelper->checkAuth('delete', $pageId); //check-authentication

		//Prepare Reqest Data
		$primaryKeyValues = $this::getPrimaryKeyValuesFromRequest($request);
        $pageDescriptor = $this->pageStore->getPageDescriptor($pageId, true);

		//Delete
		$pageDescriptor->triggerEvent('onBeforeDelete', $primaryKeyValues); //event-trigger
        $pageDescriptor->delete($primaryKeyValues);
		$pageDescriptor->triggerEvent('onAfterDelete', $primaryKeyValues); //event-trigger

		//Redirect
        return Response::redirectToAction('\Alddesign\Crudkit\Controllers\CrudkitController@listView', ['page-id' => $pageId]);
    }

    /**
     * Creates a record
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
	 * @action
     */
    public function createRecord(Request $request)
    {
		//Load Data from Request
        $pageId = request('page-id', '');
		$this->authHelper->checkAuth('create', $pageId); //check-authentication
		
		//Prepare Reqest Data
        $pageDescriptor = $this->pageStore->getPageDescriptor($pageId, true);
		$recordData = (new DataProcessor($pageDescriptor->getTable()))->preProcess($request->all(), 'create');

		//Insert
		$pageDescriptor->triggerEvent('onBeforeCreate', $recordData); //event-trigger
        $primaryKeyValues = $pageDescriptor->create($recordData);
		$pageDescriptor->triggerEvent('onAfterCreate', $primaryKeyValues); //event-trigger
		
		//Redirect
		$url = $this::getCardPageUrl($primaryKeyValues, $pageId);
		return Response::redirectTo($url);
    }

    /**
     * Updates a record
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
	 * @action
     */
    public function updateRecord(Request $request)
    {
		//Load Data from Request
        $pageId = request('page-id', null);
		$this->authHelper->checkAuth('update', $pageId); //check-authentication
		$requestData = $request->all();
		$primaryKeyValues = $this::getPrimaryKeyValuesFromRequest($request);
		
		//Prepare Reqest Data
        $pageDescriptor = $this->pageStore->getPageDescriptor($pageId, true);
		$dp = new DataProcessor($pageDescriptor->getTable());
		$recordData = $dp->preProcess($requestData, 'update');
		
		//Update
		$pageDescriptor->triggerEvent('onBeforeUpdate', $recordData); //event-trigger
		$primaryKeyValues = $pageDescriptor->update($primaryKeyValues, $recordData);
		$pageDescriptor->triggerEvent('onAfterUpdate', $primaryKeyValues); //event-trigger
		
		//Redirect
		$url = $this::getCardPageUrl($primaryKeyValues, $pageId);
		return Response::redirectTo($url);
    }

    /**
     * Performs the login
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
	 * @action
     */
	public function login(Request $request)
	{
		//Load Data from Request
		$loginAttempt = request('crudkit-login-attempt', '') === '1';
		
		session()->flush();
		$this->authHelper->checkAuth('', '', true, $loginAttempt); //check-authentication	
		$this->authHelper->triggerEvent('onafterlogin');

		return Response::redirectToAction('\Alddesign\Crudkit\Controllers\CrudkitController@index');
	}
	
	/**
     * Performs the logout
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
	 * @action
     */
	public function logout(Request $request)
	{
		session()->flush();
		
		return Response::redirectToAction('\Alddesign\Crudkit\Controllers\CrudkitController@loginView')
				->with('curdkit-login-message','Logout erfolgreich.')
				->with('curdkit-login-message-type', 'success');
	}
	
	/**
     * Executes a predefined callback action for a specific page (action button)
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
	 * @action
     */
	public function action(Request $request)
	{		
		//Load Request Data
		$pageId = request('page-id', null);
		$this->authHelper->checkAuth('', $pageId); //check-authentication
		
		$actionName = request('action-name', null);
		if($actionName === null)
		{
			throw new Exception('Parameter action-name muss einen Wert haben!');
		}
		
		//Prepare Request Data
        $pageDescriptor = $this->pageStore->getPageDescriptor($pageId, true);
		$action = $pageDescriptor->getActions($actionName);
		$primaryKeyValues = $this::getPrimaryKeyValuesFromRequest($request);
		$record = $pageDescriptor->readRecord($primaryKeyValues);
		
		$result = call_user_func($action['callback'], $record, $pageDescriptor, $action); //event-trigger
		if($result instanceof \Illuminate\Http\RedirectResponse)
		{
			return $result;
		}
		
		return Response::redirectToAction('\Alddesign\Crudkit\Controllers\CrudkitController@index');
	}
	
	/**
     * Exports a page as CSV (including filters and search but). Ignores limitatin by pagination, 
     *
     * @param  \Illuminate\Http\Request $request
	 * @action
     */
	public function exportRecordsCsv(Request $request)
	{
		//Load Data from Request
		$pageId = request('page-id', '');
		$this->authHelper->checkAuth('export', $pageId); //check-authentication
		$searchText = request('search-text', '');
		$searchColumnName = request('search-column-name', '');
		
		//Prepare Request Data
        $pageDescriptor = $this->pageStore->getPageDescriptor($pageId, true); //Get Page per name, or first page
		$filters = $this->getFiltersFromRequest($request);
		$columns = [];
		if(config('crudkit.export_all_columns', false))
		{
			$columns = $pageDescriptor->getTable()->getColumns();
		}
		else
		{
			$columns = $pageDescriptor->getSummaryColumns();
		}
	
		//Load Data from DB
		$records = $pageDescriptor->readRecords(0, $searchColumnName, $searchText, $filters, false)['records'];

		//Create File
		$dateTime = new DateTime();
		$filename = $pageId.'_'.$dateTime->format('Ymd').'_'.$dateTime->getTimestamp().'.csv';
		$filepath = storage_path('app'. DIRECTORY_SEPARATOR .$filename);
		
		//Write CSV
		$fileHandle = fopen($filepath, 'w');
		if(config('crudkit.csv_export_with_bom', false))
		{
			fwrite($fileHandle, chr(239) . chr(187) . chr(191)); //Add BOM for Windows... https://stackoverflow.com/questions/5601904/encoding-a-string-as-utf-8-with-bom-in-php
		}
		fputcsv($fileHandle, [dp::text('crudkit_csv_export')], ';');
		fputcsv($fileHandle, [dp::text('page'), $pageDescriptor->getName()], ';');
		fputcsv($fileHandle, [dp::text('page_id'), $pageDescriptor->getId()], ';');
		fputcsv($fileHandle, [dp::text('date'), $dateTime->format('d.m.Y')], ';');
		fputcsv($fileHandle, [dp::text('time'), $dateTime->format('H:i:s')], ';');
		if($searchText !== '')
		{
			fputcsv($fileHandle, [''], ';');
			fputcsv($fileHandle, [dp::text('restricted_by_search')], ';');
			fputcsv($fileHandle, [dp::text('column'), $searchColumnName], ';');
			fputcsv($fileHandle, [dp::text('search_term'), $searchText], ';');
		}
		if(!dp::e($filters))
		{
			fputcsv($fileHandle, [''], ';');
			fputcsv($fileHandle, [dp::text('restricted_by_filter'), dp::text('column'),dp::text('operator'),dp::text('value')], ';');
			foreach($filters as $index => $filter)
			{
				fputcsv($fileHandle, [dp::text('filter') . ' ' . ($index + 1), sprintf('%s (%s)', $columns[$filter->field]->getLabel(), $filter->field), $filter->operator, $filter->value], ';');
			}
		}

		fputcsv($fileHandle, [''], ';');
	
		//Header
		$header = [];
		foreach($columns as $name => $column)
		{
			$header[] = $column->getLabel();
		}
		fputcsv($fileHandle, $header, ';');
		

		//Lines
		$line = [];
		$exportEnumLabels = config('crudkit.export_enum_label', false);
		foreach($records as $index => $record)
		{
			$line = [];
			foreach($columns as $name => $column)
			{
				$line[] = ($column->type === 'enum' && $exportEnumLabels) ? $column->options['enum'][$record[$name]] : $record[$name];
			}
			
			fputcsv($fileHandle, $line, ';');
		}

		fclose($fileHandle);
		
		//Download
		return response()->download($filepath)->deleteFileAfterSend(true);	
	}
	
	/**
     * Exports a page as XML (including filters and search but). Ignores limitatin by pagination, 
     *
     * @param  \Illuminate\Http\Request $request
	 * @action
     */
	public function exportRecordsXml(Request $request)
	{
		//Load Data from Request
		$pageId = request('page-id', '');
		$this->authHelper->checkAuth('export', $pageId); //check-authentication
		$searchText = request('search-text', '');
		$searchColumnName = request('search-column-name', '');
		
		//Prepare Request Data
		$dateTime = new DateTime();
        $pageDescriptor = $this->pageStore->getPageDescriptor($pageId, true); //Get Page per name, or first page
		$filters = $this->getFiltersFromRequest($request);
		$columns = [];
		if(config('crudkit.export_all_columns', false))
		{
			$columns = $pageDescriptor->getTable()->getColumns();
		}
		else
		{
			$columns = $pageDescriptor->getSummaryColumns();
		}
	
		//Load Data from DB
		$records = $pageDescriptor->readRecords(0, $searchColumnName, $searchText, $filters, false)['records'];
		
		//Header data
		$data = [];
		$data['title'] = dp::text('crudkit_xml_export');
		$data['page'] = $pageDescriptor->getName();
		$data['page_id'] = $pageDescriptor->getId();
		$data['date'] = $dateTime->format('d.m.Y');
		$data['time'] = $dateTime->format('H:i:s');
		$data['record_count'] = count($records);
		if($searchText !== '')
		{
			$data['search'] = ['column' => $searchColumnName, 'search_term' =>$searchText];
		}
		if(!dp::e($filters))
		{
			$data['filters'] = [];
			foreach($filters as $index => $filter)
			{
				$data['filters']['filter'.$index] = $filter;
			}
		}
		
		//Record data
		$recordData = [];
		$exportEnumLabels = config('crudkit.export_enum_label', false);
		foreach($records as $index => $record)
		{
			$recordData[$index] = [];
			foreach($columns as $column)
			{
				$recordData[$index][$column->name] = ($column->type === 'enum' && $exportEnumLabels) ? $column->options['enum'][$record[$column->name]] : $record[$column->name];
			}
		}
		$data['records'] = $recordData;
		
		//Create XML
		$XmlSerializer = new \Alddesign\Crudkit\Classes\XmlSerializer();
		$XmlSerializer->indent = "\t";
		$XmlSerializer->defaultNodeName = 'record';
		$xml = $XmlSerializer->generateXmlFromArray($data);
		
		//Write to file
		$filename = $pageId.'_'.$dateTime->format('Ymd').'_'.$dateTime->getTimestamp().'.xml';
		$outputFilename = storage_path('app'. DIRECTORY_SEPARATOR .$filename);
		file_put_contents($outputFilename, $xml);
		
		//Download
		return response()
			->download($outputFilename)
			->deleteFileAfterSend(true);
		
	}
	
	/**
     * Creates Chart data based on the page, filters and search in form ob a JSON object.
	 * 
	 * Called via Javascript.
     *
     * @param  \Illuminate\Http\Request $request
	 * @return string Chart data as json object (for javascript)
	 * @action
     */
	public function getChartData(Request $request)
	{
		//Load Data from Request
		$pageId = request('page-id', '');
		$this->authHelper->checkAuth('', $pageId); //check-authentication
		$searchText = request('search-text', '');
		$searchColumnName = request('search-column-name', '');
		$filters = $this->getFiltersFromRequest($request);
		$xAxisColumn = request('x-axis-column', '');
		$yAxisColumn = request('y-axis-column', '');
		$yAxisAggreation = request('y-axis-aggregation', '');
		
		//Prepare Request Data
        $pageDescriptor = $this->pageStore->getPageDescriptor($pageId, true);
		if
		(
			!in_array($xAxisColumn, $pageDescriptor->getTable()->getColumns(true), true) || 
			!in_array($xAxisColumn, $pageDescriptor->getTable()->getColumns(true), true) || 
			!in_array($yAxisAggreation, ['count','sum','avg','min','max'], true)
		)
		{
			throw new Exception('Get Chart Data: invalid request.');
		}
		$records = $pageDescriptor->readRecords(0, $searchColumnName, $searchText, $filters, true, true)['records'];

		//Prepare chart data
		$labels = []; //Grouping on xAxis
		$values = []; //Values with aggreation on yAxis
		$axisLabels = [];
		
		$avgHelper = [];
		$minHelper = [];
		$maxHelper = [];
		$xAxisValue;
		$yAxisValue;
		$yAxisValueIsNumber;
		$index;
		
		foreach($records as $c1 => $record)
		{
			$xAxisValue = strval($record[$xAxisColumn]);
			$yAxisValue = $record[$yAxisColumn];
			$yAxisValueIsNumber = is_numeric($yAxisValue) && preg_match('/^[0-9]+[e|E][0-9]+$/', strval($yAxisValue)) === 0; //We dont want "1e223" to be numeric. Check php.org
			$yAxisValue = $yAxisValueIsNumber ? floatval($yAxisValue) : $yAxisValue;
			
			if(!in_array($xAxisValue, $labels, true))
			{
				$labels[] = $xAxisValue;
				$values[] = 0.0;
				$avgHelper[] = 0;
				$minHelper[] = null;
				$maxHelper[] = null;
			}
			$index = array_search($xAxisValue, $labels, true);
			
			//Aggregation
			switch($yAxisAggreation)
			{
				case 'count' : 
					$values[$index] += 1.0; 
					break;
				case 'sum' : 
					$values[$index] += $yAxisValueIsNumber ? $yAxisValue : 0.0; 
					break;
				case 'avg' : 
					$avgHelper[$index] += 1;
					$values[$index] += $yAxisValueIsNumber ? $yAxisValue : 0.0;
					if($yAxisValueIsNumber && $c1 === (count($records)-1)) //Last round, calculate average value
					{ 
						foreach($values as $c2 => $val)
						{
							$values[$c2] /= $avgHelper[$c2];
						}
					}
					break;
				case 'min' 	: 
					if($yAxisValueIsNumber && ($yAxisValue < $minHelper[$index] || $minHelper[$index] === null))
					{ 
						$values[$index] = $yAxisValue;
						$minHelper[$index] = $yAxisValue;
					}
					break;
				case 'max' 	: 
					if($yAxisValueIsNumber && ($yAxisValue > $maxHelper[$index] || $maxHelper[$index] === null))
					{ 
						$values[$index] = $yAxisValue;
						$maxHelper[$index] = $yAxisValue;
					}
					break;
			}
		}
		
		//Replace empty label
		$emptyPos = array_search('', $labels, true);

		if($emptyPos !== false && isset($labels[$emptyPos]))
		{
			$labels[$emptyPos] = dp::text('x_axis_empty');
		}
		
		
		$columns = $pageDescriptor->getTable()->getColumns(false);
		$axisLabels['x'] = $columns[$xAxisColumn]->label;
		switch($yAxisAggreation)
		{
			case 'count'	: $axisLabels['y'] = dp::text('aggregation_count'); break;
			case 'sum' 		: $axisLabels['y'] = sprintf('%s (%s)', dp::text('aggregation_sum'), $columns[$yAxisColumn]->label); break;
			case 'avg' 		: $axisLabels['y'] = sprintf('%s (%s)', dp::text('aggregation_avg'), $columns[$yAxisColumn]->label); break;
			case 'min' 		: $axisLabels['y'] = sprintf('%s (%s)', dp::text('aggregation_min'), $columns[$yAxisColumn]->label); break;
			case 'max' 		: $axisLabels['y'] = sprintf('%s (%s)', dp::text('aggregation_max'), $columns[$yAxisColumn]->label); break;
		}
		
		return(json_encode((object)
		[
			'labels' => $labels, 
			'values' => $values, 
			'axisLabels' => ((object)$axisLabels), 
			'title' => dp::text('crudkit_diagram_view')
		]));
	}
	
	// ### HELPER METHODS #############################################################################################################################################
	/** @ignore */
	private function getQrCodeTag(Request $request)
	{
		if(!config('crudkit.show_qrcode', false))
		{
			return '';
		}

		$size = (int) round(strlen($request->fullUrl()) * ((float)config('crudkit.qrcode_size', 1.0)) * 2);
		$margin = (int) round($size / 64);

		$renderer = new \BaconQrCode\Renderer\Image\Png();
		$renderer->setWidth($size);
		$renderer->setHeight($size);
		$renderer->setMargin($margin);
		$renderer->setBackgroundColor(new \BaconQrCode\Renderer\Color\Rgb(255,255,255));
		
		$writer = new \BaconQrCode\Writer($renderer);
		return('<img src="data:image/png;base64,' . base64_encode($writer->writeString($request->fullUrl())) . '" id="crudkit-qrcode" />');
	}
	
	/** @ignore */
	private function getPrimaryKeyValuesFromRequest(Request $request)
	{
		$primaryKeyValues = [];
		$index = 0;
		
		while($request->has('pk-'.$index))
		{
			$primaryKeyValues[] = $request->input(sprintf('pk-%d', $index));
			$index = $index + 1;
		}
		
		return $primaryKeyValues;
	}
	
	/** @ignore */
	private function getFiltersFromRequest(Request $request)
	{
		$filters = [];
		$index = 0;
		
		while($request->has('ff-'.$index))
		{
			$filters[] = new \Alddesign\Crudkit\Classes\Filter(request('ff-'.$index), request('fo-'.$index), request('fv-'.$index));
			$index = $index + 1;
		}
		
		return $filters;
	}
	
	/** @ignore */
	private function getCardPageUrl(array $primaryKeyValues, string $pageId)
	{
		$urlParameters = $this->getUrlParameters($pageId, null, '', '', [], $primaryKeyValues);
		return(URL::action('\Alddesign\Crudkit\Controllers\CrudkitController@cardView', $urlParameters));
	}
	
	/** @ignore */
	private function getCardPageUrls(array $records, array $primaryKeyColumns, string $pageId, bool $singleRecord = false)
	{
		$result = [];
		
		$records = $singleRecord ? [$records] : $records; //Can now be used in the foreach
		
		foreach($records as $index => $record)
		{
			$urlParameters = $this->getUrlParameters($pageId, null, '', '', [], [], $primaryKeyColumns, $record);
			$result[$index] = URL::action('\Alddesign\Crudkit\Controllers\CrudkitController@cardView', $urlParameters);
		}
		
		return $singleRecord ? $result[0] : $result;
	}
	
	/** @ignore */
	private function getRelationUrls(array $records, array $columns, string $relationType, bool $singleRecord = false)
	{
		$result = [];
		$records = $singleRecord ? [$records] : $records;
		
		foreach($columns as $column)
		{
			if($relationType === $column->getRelationType())
			{
				foreach($records as $index => $record)
				{
					$result[$index][$column->getName()] = ($relationType === 'manytoone') ? $column->getCardUrl($record, $this->pageStore) : $column->getListUrl($record, $this->pageStore);
				}
			}
		}
		
		$result[0] = isset($result[0]) ? $result[0] : $result;
		return $singleRecord ? $result[0] : $result;
	}

	/** @ignore */
	private function getExportCsvUrl(string $pageId, string $searchText = '', string $searchColumnName = '', array $filters = [])
	{
		$urlParameters = $this->getUrlParameters($pageId,null,$searchText,$searchColumnName,$filters);
		return(URL::action('\Alddesign\Crudkit\Controllers\CrudkitController@exportRecordsCsv', $urlParameters));
	}

	/** @ignore */
	private function getExportXmlUrl(string $pageId, string $searchText = '', string $searchColumnName = '', array $filters = [])
	{
		$urlParameters = $this->getUrlParameters($pageId,null,$searchText,$searchColumnName,$filters);
		return(URL::action('\Alddesign\Crudkit\Controllers\CrudkitController@exportRecordsXml', $urlParameters));
	}

	/** @ignore */
	private function getChartPageUrl(string $pageId, string $searchText = '', string $searchColumnName = '', array $filters = [])
	{
		$urlParameters = $this->getUrlParameters($pageId,null,$searchText,$searchColumnName,$filters);
		return(URL::action('\Alddesign\Crudkit\Controllers\CrudkitController@chartView', $urlParameters));
	}

	/** @ignore */
	private function getPaginationUrls(int $pageNumber, string $pageId, int $recordCount, string $searchText = '', string $searchColumnName = '', array $filters = [])
	{
		$limit = config('crudkit.pagination_limit', 1) >= 1 ? config('crudkit.pagination_limit', 1) : 1;
		$result = [];
		$recordsPerPage = (int)config('crudkit.records_per_page', 8);
		
		// ### Definie all Urls Parametres ###
		$params = $this->getUrlParameters($pageId,($pageNumber - 1),$searchText,$searchColumnName,$filters);
		
		// ### Create Urls ###
		//Previous
		$result['previous'] = URL::action('\Alddesign\Crudkit\Controllers\CrudkitController@listView', $params);
		
		//Pages
		$result['pages'] = [];
		for($c = 1; (($c - 1) * $recordsPerPage) < $recordCount; $c++)
		{
			$params['page-number'] = $c;
			
			if(abs($pageNumber - $c) > $limit) //Dot Filler
			{
				if($c >= $pageNumber)
				{
					$result['afterdot'] = true;
				}
				else
				{
					$result['predot'] = true;
				}
			}
			else //Pages
			{
				$result['pages'][$c] = URL::action('\Alddesign\Crudkit\Controllers\CrudkitController@listView', $params);
			}
			
			if($c === 1) //First / Last
			{
				$result['first'] = URL::action('\Alddesign\Crudkit\Controllers\CrudkitController@listView', $params);
			}
			if(($c * $recordsPerPage) >= $recordCount) //Last
			{
				$result['last'] = URL::action('\Alddesign\Crudkit\Controllers\CrudkitController@listView', $params);
			}
		}
		
		//Next
		$params['page-number'] = $pageNumber + 1;
		$result['next'] = URL::action('\Alddesign\Crudkit\Controllers\CrudkitController@listView', $params);
		
		return $result;
	}

	/** @ignore */
	private function getUrlParameters(string $pageId, int $pageNumber = null, string $searchText = null, string $searchColumnName = null, $filters = [], array $primaryKeyValues = null, array $primaryKeyColumns = null, array $record = null)
	{
		$urlParameters = [];
		$urlParameters['page-id'] = $pageId; 
		if(!dp::e($pageNumber))
		{ 
			$urlParameters['page-number'] = $pageNumber; 
		}
		if(!dp::e($searchText))			
		{ 
			$urlParameters['search-text'] = $searchText; 
		} 
		if(!dp::e($searchColumnName))
		{ 
			$urlParameters['search-column-name'] = $searchColumnName; 
		}
		
		if(!dp::e($filters))
		{
			foreach($filters as $index => $filter)
			{
				$urlParameters['ff-'.$index] = $filter->field;
				$urlParameters['fo-'.$index] = $filter->operator;
				$urlParameters['fv-'.$index] = $filter->value;
			}
		}
		
		if(!dp::e($primaryKeyValues))
		{
			foreach($primaryKeyValues as $primaryKeyNumber => $primaryKeyValue)
			{
				$urlParameters['pk-'.((int)$primaryKeyNumber)] = $primaryKeyValue;
			}
		}
		
		if(!dp::e($primaryKeyColumns))
		{
			foreach($primaryKeyColumns as $primaryKeyNumber => $primaryKeyColumn)
			{
				$urlParameters['pk-'.((int)$primaryKeyNumber)] = $record[$primaryKeyColumn]; 
			}
		}
		
		return $urlParameters;
	}
	
    /**
     * Fetches all columns (no data) an crates necessary html <input> attributes. Such as "readonly".
     *
     * @param  \Alddesign\Crudkit\Classes\SQLColumn[] $columns 
     * @return string $htmlInputAttributes
	 * @helper
     */
	private function getHtmlInputAttributes(array $columns)
	{
		$htmlInputAttributes = [];
		
		foreach($columns as $column)
		{
			$attributes = '';
			
			$attributes .= (!empty($column->options['required']) && ($column->options['required'] == true)) ? ' required' : '';
			$attributes .= (!empty($column->options['readonly']) && ($column->options['readonly'] == true))  ? ' readonly' : '';
						
			if(in_array($column->type, ['integer', 'decimal'], true))
			{
				$attributes .= !empty($column->options['min']) ? sprintf(' min="%s"', $column->options['min']) : '';
				$attributes .= !empty($column->options['max']) ? sprintf(' max="%s"', $column->options['max']) : '';
				$attributes .= !empty($column->options['step']) ? sprintf(' step="%s"', $column->options['step']) : '';
			}
			if(in_array($column->type, ['text', 'email', 'textlong'], true))
			{
				$attributes .= !empty($column->options['min']) ? sprintf(' minlength="%s"', $column->options['min']) : '';
				$attributes .= !empty($column->options['max']) ? sprintf(' maxlength="%s"', $column->options['max']) : '';
			}
			
			$htmlInputAttributes[$column->name] = $attributes;
		}
		
		return($htmlInputAttributes);
	}
}
