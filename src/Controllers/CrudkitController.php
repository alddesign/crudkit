<?php
declare(strict_types=1);

namespace Alddesign\Crudkit\Controllers;

use \Exception;
use \DateTime;
use Illuminate\Http\Request;
use Alddesign\Crudkit\Classes\DataProcessor;
use Alddesign\Crudkit\Classes\PageStore;
use Alddesign\Crudkit\Classes\AuthHelper;
use Alddesign\Crudkit\Classes\CHelper;
use Alddesign\Crudkit\Classes\Lookup;
use Alddesign\Crudkit\Classes\SQLColumn;
use Alddesign\Crudkit\Classes\SQLManyToOneColumn;
use \DateTimeZone;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\Serializer\Encoder\XmlEncoder;

/**
 * Crudkit Controller
 * 
 * Contains code for all the endpoints defined in rountes.php. 
 * @internal
 */
class CrudkitController extends \App\Http\Controllers\Controller
{
	#region Main Endpoints
	/** @var string The CRUDKit version */
	private const CRUDKIT_VERSION = 'v1.0.0-rc.1';
	/** @var PageStore All the pages. */
	private $pageStore = null;
	/** @var AuthHelper Holding user/permission related data. */
	private $authHelper = null;
	
	/**
	 * Holds the datetimezone object that matches the value in the config "crudkit.local_timezone"
	 * @var \DateTimeZone
	 * @ignore
	 */
	private $localTimeZone = null;

	/**
	 * Creates a new controller instance
	 */
	public function __construct()
	{
		//Make these variables available in all views
		View::share('texts', CHelper::getTexts());
		View::share('version', self::CRUDKIT_VERSION);

		$this->localTimeZone = new DateTimeZone(config('crudkit.local_timezone', 'UTC'));
	}

	/** 
	 * Set pages and authHelper.
	 * 
	 * @param PageStroe $pageStore
	 * @param AuthHelper $authHelper 
	 */
	public function init(PageStore $pageStore, AuthHelper $authHelper = null)
	{
		$this->pageStore = $pageStore;
		$this->authHelper = CHelper::e($authHelper) ? (new AuthHelper()) : $authHelper;
	}

	/** 
	 * Automatic generation of a CurdkitServiceProvider.php
	 * 
	 * @param  \Illuminate\Http\Request $request
	 */
	public function autoGenerate(Request $request)
	{
		$generator = new \Alddesign\Crudkit\Classes\Generator();
		return $generator->generateServiceProvider();
	}

	/** 
	 * Brings up the startpage or login view, if not logged in
	 * 
	 * @param  \Illuminate\Http\Request $request
	 * @view
	 */
	public function index(Request $request)
	{
		$this->authHelper->checkAuth('', '', true); //### Auth
		$this->authHelper->checkStartpage();
	}

	#endregion

	#region View Endpoints
	/**
	 * Displays records as a summary/list page
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\Response
	 * @view
	 */
	public function listView(Request $request)
	{
		//Load PageId and authenticate
		$pageId 		= request('page-id', '');
		$pageDescriptor = $this->pageStore->getPageDescriptor($pageId, true); //Get Page per name, or first page
		$table = $pageDescriptor->getTable();
		$pageId 		= $pageDescriptor->getId();
		$itemsPerPage 	= $pageDescriptor->getItemsPerPage();
		$this->authHelper->checkAuth('list', $pageId); //check-authentication

		//Load Data from Request
		$pageNumber 		= (int)request('page-number', 1) > 0 ? (int)request('page-number', 1) : 1;
		$searchColumnName 	= !CHelper::e(request('sc', '')) ? request('sc', '') : '';
		$searchText 		= !CHelper::e(request('st', '')) ? request('st', '') : '';
		$resetSearch 		= request('sr', '') === '1';
		$filters 			= CHelper::getFiltersFromRequest($request);

		//Process Request Data
		if ($resetSearch) 
		{
			$pageNumber = 1;
			$searchText = '';
			$searchColumnName = '';
		}

		//Load record with raw data from DB, load Lookups, format record data
		$records = $table->readRecordsRaw($pageNumber, $searchColumnName, $searchText, $filters, true, $itemsPerPage); //ok
		$lookups = $pageDescriptor->getMultipleLookupsCalculated($records['records']);
		$records['records'] = $table->postProcess($records['records'], false, true, true, true, true);

		//Process DB Data
		$paginationUrls = $this->getPaginationUrls($pageNumber, $pageId, $records['total'], $searchText, $searchColumnName, $filters);

		$viewData = 
		[
			'pageType' 				=> 'list',
			'pageId' 				=> $pageId,
			'pageName' 				=> $pageDescriptor->getName(),
			'pageTitleText' 		=> $pageDescriptor->getTitleText('list'),
			'summaryColumns' 		=> $pageDescriptor->getSummaryColumns(false),
			'primaryKeyColumns' 	=> $table->getPrimaryKeyColumns(false),
			'cardPageUrls' 			=> $pageDescriptor->getCardPageUrls($records['records'], $table->getPrimaryKeyColumns(true)),
			'cardPageUrlColumns' 	=> $pageDescriptor->getCardLinkColumns(true),
			'manyToOneUrls' 		=> $pageDescriptor->getManyToOneUrls($records['records']),
			'chartPageUrl' 			=> $pageDescriptor->getChartPageUrl($searchText, $searchColumnName, $filters),
			'exportCsvUrl' 			=> $pageDescriptor->getExportCsvUrl($searchText, $searchColumnName, $filters),
			'exportXmlUrl' 			=> $pageDescriptor->getExportXmlUrl($searchText, $searchColumnName, $filters),
			'actions' 				=> $pageDescriptor->getActions(),
			'lookups'				=> $lookups,
			'records' 				=> $records,
			'hasFilters' 			=> !CHelper::e($filters),
			'filters' 				=> $filters,
			'hasSearch' 			=> ($searchText !== ''),
			'searchText' 			=> $searchText,
			'searchColumnName' 		=> $searchColumnName,
			'recordsPerPage' 		=> $itemsPerPage,
			'pageNumber' 			=> $pageNumber,
			'paginationUrls' 		=> $paginationUrls,
			'chartAllowed' 			=> $pageDescriptor->getChartAllowed(),
			'cardAllowed' 			=> $pageDescriptor->getCardAllowed(),
			'createAllowed' 		=> $pageDescriptor->getCreateAllowed(),
			'exportAllowed' 		=> $pageDescriptor->getExportAllowed(),
			'pageMap' 				=> $this->pageStore->getPageMap(),
			'js'					=> $pageDescriptor->getJs(),
			'css'					=> $pageDescriptor->getCss()
		];

		$pageDescriptor->triggerEvent('onOpenList', $viewData); //event-trigger

		return view('crudkit::list', $viewData);
	}

	/**
	 * Displays a singe recorda as a card page
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
		$table = $pageDescriptor->getTable();
		$this->authHelper->checkAuth('card', $pageId); //check-authentication

		//Load Data from Request
		$primaryKeyValues 	= CHelper::getPrimaryKeyValuesFromRequest($request);
		$filters 			= CHelper::getFiltersFromRequest($request);

		//Process Reqest Data
		$deleteUrl				= URL::action('\Alddesign\Crudkit\Controllers\CrudkitController@deleteRecord', CHelper::getUrlParameters($pageId, null, '', '', $filters, $primaryKeyValues));
		$updateUrl				= URL::action('\Alddesign\Crudkit\Controllers\CrudkitController@updateView', CHelper::getUrlParameters($pageId, null, '', '', $filters, $primaryKeyValues));

		//Load record with raw data from DB, load Lookups, format record data
		$record = $table->readRecordRaw($primaryKeyValues, $filters, true); //ok
		$lookups = $pageDescriptor->getLookupsCalculated($record);
		$record = $table->postProcess($record, true);

		$viewData = 
		[
			'pageType' 				=> 'card',
			'pageId' 				=> $pageId,
			'pageTitleText' 		=> $pageDescriptor->getTitleText('card'),
			'updateAllowed' 		=> $pageDescriptor->getUpdateAllowed(),
			'deleteAllowed' 		=> $pageDescriptor->getDeleteAllowed(),
			'confirmDelete'			=> $pageDescriptor->getConfirmDelete(),
			'primaryKeyColumns' 	=> $table->getPrimaryKeyColumns(true),
			'primaryKeyValues' 		=> $primaryKeyValues,
			'deleteUrl'				=> $deleteUrl,
			'updateUrl'				=> $updateUrl,
			'manyToOneUrls' 		=> $pageDescriptor->getManyToOneUrls($record, true),
			'manyToOneValues' 		=> $table->getManyToOneColumnValues($record, true),
			'customAjaxValues'		=> $table->getCustomAjaxValues($record, $pageDescriptor),
			'sections' 				=> $pageDescriptor->getSections(),
			'actions' 				=> $pageDescriptor->getActions(),
			'lookups'				=> $lookups,
			'record' 				=> $record,
			'columns' 				=> $table->getColumns(),
			'pageName' 				=> $pageDescriptor->getName(),
			'pageMap' 				=> $this->pageStore->getPageMap(),
			'js'					=> $pageDescriptor->getJs(),
			'css'					=> $pageDescriptor->getCss()
		];

		$pageDescriptor->triggerEvent('onOpenCard', $viewData); //event-trigger

		return view('crudkit::card', $viewData);
	}

	/**
	 * The view for editing an existing record.
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
		$table = $pageDescriptor->getTable();
		$this->authHelper->checkAuth('update', $pageId); //check-authentication

		//Load Data from Request
		$pageId 			= request('page-id', '');
		$primaryKeyValues 	= CHelper::getPrimaryKeyValuesFromRequest($request);
		$filters 			= CHelper::getFiltersFromRequest($request);

		//Process Request Data
		$columns 	= $table->getColumns();

		//Load Data from DB
		$record = $table->readRecordRaw($primaryKeyValues, $filters, true); //ok
		$lookups = $pageDescriptor->getLookupsCalculated($record);
		$record = $table->postProcess($record, true);

		$viewData =
		[
			'pageType' 				=> 'update',
			'pageId' 				=> $pageId,
			'pageTitleText' 		=> $pageDescriptor->getTitleText('update'),
			'pageName' 				=> $pageDescriptor->getName(),
			'columns' 				=> $columns,
			'sections' 				=> $pageDescriptor->getSections(),
			'primaryKeyColumns' 	=> $table->getPrimaryKeyColumns(true),
			'manyToOneValues' 		=> $table->getManyToOneColumnValues($record),
			'customAjaxValues'		=> $table->getCustomAjaxValues($record, $pageDescriptor),
			'lookups'				=> $lookups,
			'htmlInputAttributes' 	=> $this->getHtmlInputAttributes($columns),
			'pageMap' 				=> $this->pageStore->getPageMap(),
			'record' 				=> $record,
			'js'					=> $pageDescriptor->getJs(),
			'css'					=> $pageDescriptor->getCss()
		];

		$pageDescriptor->triggerEvent('onOpenUpdate', $viewData); //event-trigger_
		
		return view('crudkit::create-update', $viewData);
	}

	/**
	 * The view for creating a new record.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\Response
	 * @view
	 */
	public function createView(Request $request)
	{
		//Load PageId and authenticate
		$pageId 		= request('page-id', '');
		$page = $this->pageStore->getPageDescriptor($pageId, true);
		$table = $page->getTable();
		$this->authHelper->checkAuth('create', $pageId); //check-authentication

		//Load Data from Request
		//*nothing*

		//Process Reqest Data
		$columns 	= $table->getColumns();

		//Create an empty rec
		$emptyRecord = $table->getEmptyRecord();

		$viewData = 
		[
			'pageType' 				=> 'create',
			'pageId' 				=> $pageId,
			'pageTitleText' 		=> $page->getTitleText('create'),
			'pageName' 				=> $page->getName(),
			'columns' 				=> $columns,
			'sections' 				=> $page->getSections(),
			'primaryKeyColumns' 	=> $table->getPrimaryKeyColumns(true),
			'manyToOneValues' 		=> $table->getManyToOneColumnValues($emptyRecord),
			'customAjaxValues'		=> $table->getCustomAjaxValues($emptyRecord, $page),
			'htmlInputAttributes' 	=> $this->getHtmlInputAttributes($columns),
			'pageMap' 				=> $this->pageStore->getPageMap(),
			'record'				=> $emptyRecord,
			'js'					=> $page->getJs(),
			'css'					=> $page->getCss()
		];

		$page->triggerEvent('onOpenCreate', $viewData); //event-trigger

		return view('crudkit::create-update', $viewData);
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
		$filters = CHelper::getFiltersFromRequest($request);

		//Process Request Data
		$filterOperators =
			[
				'=' => '=',
				'>' => '>',
				'<' => '<',
				'!=' => '!=',
				'startswith' => CHelper::text('startswith'),
				'endswith' => CHelper::text('endswith'),
				'contains' => CHelper::text('contains')
			];
		$getChartDataUrlParamters 				= CHelper::getUrlParameters($pageId); //Dont include filters here - JS will load them from DOM. Search is disabled.
		$getChartDataUrlParamters['_token'] 	= csrf_token();
		$getChartDataUrlParamters 				= json_encode((object)$getChartDataUrlParamters);

		//Load Data from DB
		//*nothing* 
		//Data will be loaded dynamically via ajax

		//Process DB Data
		//*nothing*

		$viewData = 
		[
			'pageType' 					=> 'chart',
			'pageId' 					=> $pageId,
			'pageName' 					=> $pageDescriptor->getName(),
			'pageTitleText' 			=> $pageDescriptor->getTitleText('chart'),
			'columns' 					=> $pageDescriptor->getTable()->getColumns(false),
			'primaryKeyColumns' 		=> $pageDescriptor->getTable()->getPrimaryKeyColumns(true),
			'getChartDataUrl' 			=> URL::action('\Alddesign\Crudkit\Controllers\CrudkitController@getChartData'),
			'getChartDataUrlParamters' 	=> $getChartDataUrlParamters,
			'hasFilters' 				=> !CHelper::e($filters),
			'filters' 					=> $filters,
			'filterOperators' 			=> $filterOperators,
			'pageMap' 					=> $this->pageStore->getPageMap(),
			'js'						=> $pageDescriptor->getJs(),
			'css'						=> $pageDescriptor->getCss()
		];

		$pageDescriptor->triggerEvent('onOpenChart', $viewData); //event-trigger			

		return view('crudkit::chart', $viewData);
	}

	/**
	 * View for displaying a message.  
	 * These are the request parameters:
	 * * title: The title for the message windows
	 * * message: The text for message itself
	 * * message-html: The text for message itself with html encluded
	 * * type: For the theme of the message. Valid types are 'success', 'warning', 'info', 'danger'
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\Response
	 * @view
	 */
	public function messageView(Request $request)
	{
		//Loading from GET request or otherwise flash session data
		$title 			= request('title', '');
		$title 			= $title === '' ? session('title', '') : $title;
		$message 		= request('message', '');
		$message		= $message === '' ? session('message', '') : $message;
		$messageHtml 	= request('message-html', '');
		$messageHtml	= $messageHtml === '' ? session('messageHtml', '') : $messageHtml;
		$type 			= request('type', '');
		$type			= $type === '' ? session('type', '') : $type;
		$pageName 		= 'Information';
		$pageMap 		= $this->authHelper->isLoggedIn() ? $this->pageStore->getPageMap() : [];

		//Valid options are: 'success','warning','info','danger'
		$type = in_array($type, ['success', 'warning', 'info', 'danger'], true) ? $type : 'info';


		switch ($type) {
			case 'success':
				$pageName = 'Erfolgreich';
				break;
			case 'warning':
				$pageName = 'Warnung';
				break;
			case 'info':
				$pageName = 'Information';
				break;
			case 'danger':
				$pageName = 'Fehler';
				break;
			default:
				$pageName = 'Information';
		}

		return view(
			'crudkit::message',
			[
				'pageType' 		=> 'message',
				'title' 		=> $title,
				'message' 		=> $message,
				'messageHtml'	=> $messageHtml,
				'pageTitleText' => '',
				'type' 			=> $type,
				'pageId' 		=> '___MESSAGE___',
				'pageName' 		=> $pageName,
				'pageMap' 		=> $pageMap
			]
		);
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
		$this->authHelper->checkCookies(); //for them skins

		$loginMessage 		= session('crudkit-login-message', '');
		$loginMessageType 	= session('crudkit-login-message-type', '');
		session()->forget('crudkit-login-message');
		session()->forget('crudkit-login-message-type');

		return view(
			'crudkit::login',
			[
				'pageType' 			=> 'login',
				'loginMessage' 		=> $loginMessage,
				'loginMessageType' 	=> $loginMessageType,
				'pageTitleText' 	=> '',
				'pageId' 			=> '___LOGIN___',
				'pageName' 			=> 'Login',
				'pageMap' 			=> []
			]
		);
	}
	#endregion

	#region Api Endpoints
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
		$primaryKeyValues = CHelper::getPrimaryKeyValuesFromRequest($request);
		$pageDescriptor = $this->pageStore->getPageDescriptor($pageId, true);
		$table = $pageDescriptor->getTable();

		//Delete
		$pageDescriptor->triggerEvent('onBeforeDelete', $primaryKeyValues); //event-trigger
		$table->deleteRecord($primaryKeyValues);
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
		$table = $pageDescriptor->getTable();
		$recordData = (new DataProcessor($table))->preProcess($request->all(), true);

		//Insert
		$pageDescriptor->triggerEvent('onBeforeCreate', $recordData); //event-trigger
		$primaryKeyValues = $table->createRecord($recordData);
		$pageDescriptor->triggerEvent('onAfterCreate', $primaryKeyValues); //event-trigger

		//Redirect
		$url = $pageDescriptor->getCardPageUrl($primaryKeyValues);
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
		$primaryKeyValues = CHelper::getPrimaryKeyValuesFromRequest($request);

		//Prepare Reqest Data
		$pageDescriptor = $this->pageStore->getPageDescriptor($pageId, true);
		$table = $pageDescriptor->getTable();
		$dataPorcessor = new DataProcessor($table);
		$recordData = $dataPorcessor->preProcess($requestData, false);

		//Update
		$pageDescriptor->triggerEvent('onBeforeUpdate', $recordData); //event-trigger
		$primaryKeyValues = $table->updateRecord($primaryKeyValues, $recordData);
		$pageDescriptor->triggerEvent('onAfterUpdate', $primaryKeyValues); //event-trigger

		//Redirect
		$url = $pageDescriptor->getCardPageUrl($primaryKeyValues);
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
			->with('curdkit-login-message', 'Logout erfolgreich.')
			->with('curdkit-login-message-type', 'success');
	}
	public function setTheme(Request $request)
	{
		$lifetimeDays = 90;
		$lifetimeInMinutes = $lifetimeDays * 24 * 60;
		
		$skin = request('skin', 'blue');
		$accent = request('accent', 'blue');

		$skinCookie = cookie('crudkit-skin', $skin, $lifetimeInMinutes);
		$accentCookie = cookie('crudkit-accent', $accent, $lifetimeInMinutes);

		return Redirect::back()
		->withCookie($skinCookie)
		->withCookie($accentCookie); //to the same page
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
		if ($actionName === null) {
			throw new Exception('Parameter action-name muss einen Wert haben!');
		}

		//Prepare Request Data
		$pageDescriptor = $this->pageStore->getPageDescriptor($pageId, true);
		$table = $pageDescriptor->getTable();
		$action = $pageDescriptor->getActions($actionName);
		$primaryKeyValues = CHelper::getPrimaryKeyValuesFromRequest($request);

		//Read Data
		$record = $table->readRecordRaw($primaryKeyValues); //ok
		$record = $table->postProcess($record, true);

		$result = call_user_func($action->callback, $record, $pageDescriptor, $action); //event-trigger
		if ($result instanceof \Illuminate\Http\RedirectResponse) {
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
		$searchText = request('st', '');
		$searchColumnName = request('sc', '');

		//Prepare Request Data
		$pageDescriptor = $this->pageStore->getPageDescriptor($pageId, true); //Get Page per name, or first page
		$table = $pageDescriptor->getTable();
		$filters = CHelper::getFiltersFromRequest($request);
		/** @var SQLColumn[] */
		$columns = $pageDescriptor->getTable()->getColumns();
		$summaryColumns = $pageDescriptor->getSummaryColumns(true);


		//Load record with raw data from DB, load Lookups, format record data
		//!! Data is not trimmed
		//!! Blob fields and images will show only the size in KB
		$records = $table->readRecordsRaw(0, $searchColumnName, $searchText, $filters, false); //ok
		$lookups = [];
		foreach ($records as $id => $record) 
		{
			$lookups[$id] = $pageDescriptor->getLookupsCalculated($record);
		}
		$records = $table->postProcess($records, false, true, true, true, true);

		//Config values
		$exportAll = config('crudkit.export_all_columns', false);
		$exportEnumLabel = config('crudkit.export_enum_label', false);
		$exportLookups = config('crudkit.export_lookups', false);
		$s = config('crudkit.csv_export_field_separator', ';');

		//Create File
		$dateTimeUtc = new DateTime('now', new DateTimeZone('UTC'));
		$dateTimeLocal = new DateTime('now', $this->localTimeZone);
		$filenameReal = $pageId . '_' . $dateTimeLocal->format('Y-m-d_H-i-s') . '_' . $dateTimeLocal->getTimestamp() . '.csv';
		$filenameTemp = uniqid() . '.csv';
		$filepath = storage_path('app' . DIRECTORY_SEPARATOR . $filenameTemp);
		$fileHandle = fopen($filepath, 'w');

		#region Write File info
		if (config('crudkit.csv_export_with_bom', false)) 
		{
			fwrite($fileHandle, chr(239) . chr(187) . chr(191)); //Add BOM for Windows... https://stackoverflow.com/questions/5601904/encoding-a-string-as-utf-8-with-bom-in-php
		}
		fputcsv($fileHandle, ['src', 'crudkit_csv_export'], $s);
		fputcsv($fileHandle, ['src_description', 'This CSV file was exported from CRUDKit. Join us as Github: alddesign/crudkit'], $s);
		fputcsv($fileHandle, ['exported_page', $pageDescriptor->getName()], $s);
		fputcsv($fileHandle, ['exported_page_id', $pageDescriptor->getId()], $s);
		fputcsv($fileHandle, ['export_datetime_utc', $dateTimeUtc->format('Y-m-d\TH:i:s')], $s);
		fputcsv($fileHandle, ['export_datetime_local', $dateTimeLocal->format('Y-m-d\TH:i:s')], $s);
		fputcsv($fileHandle, ['local_datetime', $this->localTimeZone->getName()], $s);
		if ($searchText !== '') {
			fputcsv($fileHandle, [''], $s);
			fputcsv($fileHandle, ['restricted_by_search'], $s);
			fputcsv($fileHandle, ['column', $searchColumnName], $s);
			fputcsv($fileHandle, ['search_term', $searchText], $s);
		}
		if (!CHelper::e($filters)) {
			fputcsv($fileHandle, [''], $s);
			fputcsv($fileHandle, ['restricted_by_filter', 'column', 'operator', 'value'], $s);
			foreach ($filters as $index => $filter) 
			{
				fputcsv($fileHandle, ['filter' . '_' . $index, sprintf('%s (%s)', $columns[$filter->field]->label, $filter->field), $filter->operator, $filter->value], $s);
			}
		}

		fputcsv($fileHandle, ['{{{data-start}}}'], $s);
		#endregion

		#region Write header row
		$header = [];
		foreach ($columns as $name => $column) 
		{
			if($exportAll || in_array($name, $summaryColumns))
			{
				$this->csvLookup(true, $exportLookups, $lookups, 'before-field', $name, $header); 
				$header[] = $column->label; 
				$this->csvLookup(true, $exportLookups, $lookups, 'after-field', $name, $header); 
			}	
		}
		fputcsv($fileHandle, $header, $s);
		#endregion

		#region Write Lines
		$line = [];
		foreach ($records as $index => $record) 
		{
			$line = [];
			foreach ($columns as $name => $column) 
			{
				if($exportAll || in_array($name, $summaryColumns))
				{
					$this->csvLookup(false, $exportLookups, $lookups[$index], 'before-field', $name, $line); 
					
					switch($column->type)
					{
						case 'enum' : $value = $exportEnumLabel ?  $column->options['enum'][$record[$name]] : $record[$name]; break;
						default		: $value = $record[$name];
					}
					$line[] = $value;

					$this->csvLookup(false, $exportLookups, $lookups[$index], 'after-field', $name, $line); 
				}
			}

			fputcsv($fileHandle, $line, $s);
		}
		#endregion

		fclose($fileHandle);

		//Download
		return response()->download($filepath, $filenameReal)->deleteFileAfterSend(false);
	}

	private function csvLookup(bool $header, bool $exportLookups, array $lookups, string $position, string $columnName, array &$line)
	{
		if(!$exportLookups || ($header && !isset($lookups[0])))
		{
			return;
		}

		$lookupHelper = $header ? $lookups[0] : $lookups;
		foreach($lookupHelper as $lookup)
		{
			/** @var Lookup $lookup */
			$lookup;
			$lookupPos = $lookup->position === 'to-field' ? 'after-field' : $lookup->position;

			if($lookup->fieldname === $columnName && $lookupPos === $position && $lookup->visible)
			{
				$line[] = $header ? $lookup->label . ' (Lookup)' : $lookup->value;
			}
		}
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
		$searchText = request('st', '');
		$searchColumnName = request('sc', '');

		//Prepare Request Data
		$dateTimeUtc = new DateTime('now', new DateTimeZone('UTC'));
		$dateTimeLocal = new DateTime('now', $this->localTimeZone);
		$pageDescriptor = $this->pageStore->getPageDescriptor($pageId, true);
		$table = $pageDescriptor->getTable();
		$filters = CHelper::getFiltersFromRequest($request);
		$columns = [];
		if (config('crudkit.export_all_columns', false)) 
		{
			$columns = $table->getColumns();
		} else 
		{
			$columns = $pageDescriptor->getSummaryColumns();
		}

		//Load Data from DB
		$records = $table->readRecordsRaw(0, $searchColumnName, $searchText, $filters, false); //ok
		$records = $table->postProcess($records);

		//Header data
		$data = [];
		$data['src'] = 'crudkit_xml_export';
		$data['src_description'] = 'This XML file was exported from CRUDKit. Join us as Github: alddesign/crudkit';
		$data['exported_page'] = $pageDescriptor->getName();
		$data['exported_page_id'] = $pageDescriptor->getId();
		$data['export_datetime_utc'] = $dateTimeUtc->format('Y-m-d\TH:i:s');
		$data['export_datetime_local'] = $dateTimeLocal->format('Y-m-d\TH:i:s');
		$data['local_timezone'] = $this->localTimeZone->getName();
		$data['record_count'] = count($records);
		if ($searchText !== '') {
			$data['search'] = ['column' => $searchColumnName, 'search_term' => $searchText];
		}
		if (!CHelper::e($filters)) {
			$data['filters'] = [];
			foreach ($filters as $index => $filter) {
				$data['filters']['filter' . $index] = $filter;
			}
		}

		//Record data
		$exportEnumLabels = config('crudkit.export_enum_label', false);
		foreach ($records as $index => $record) 
		{
			foreach ($columns as $column) 
			{
				switch($column->type)
				{
					case 'enum' : $records[$index][$column->name] =  $exportEnumLabels ?  $column->options['enum'][$record[$column->name]] : $record[$column->name]; break;
					default : $records[$index][$column->name] = $record[$column->name];
				}
			}
		}

		//Prepare data structure so it looks nice in xml:
		$data['records'] = ['record' => $records];
		unset($records); //free memory

		//Make xml
		$context = 
		[
			XmlEncoder::FORMAT_OUTPUT => true, //nice output with newlines and indention
			XmlEncoder::ENCODING => 'utf-8', 
			XmlEncoder::ROOT_NODE_NAME => 'data'
		];
		$xmlEncoder = new XmlEncoder($context);
		$xml = $xmlEncoder->encode($data, 'xml');
		unset($data); //free momory

		//Write to file
		$filename = $pageId . '_' . $dateTimeLocal->format('Y-m-d_H-i-s') . '_' . $dateTimeLocal->getTimestamp() . '.xml';
		$outputFilename = storage_path('app' . DIRECTORY_SEPARATOR . $filename);
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
		$searchText = request('st', '');
		$searchColumnName = request('sc', '');
		$filters = CHelper::getFiltersFromRequest($request);
		$xAxisColumn = request('x-axis-column', '');
		$yAxisColumn = request('y-axis-column', '');
		$yAxisAggreation = request('y-axis-aggregation', '');

		//Prepare Request Data
		$pageDescriptor = $this->pageStore->getPageDescriptor($pageId, true);
		$table = $pageDescriptor->getTable();
		if (
			!in_array($xAxisColumn, $pageDescriptor->getTable()->getColumns(true), true) ||
			!in_array($xAxisColumn, $pageDescriptor->getTable()->getColumns(true), true) ||
			!in_array($yAxisAggreation, ['count', 'sum', 'avg', 'min', 'max'], true)
		) {
			throw new Exception('Get Chart Data: invalid request.');
		}
		$records = $table->readRecordsRaw(0, $searchColumnName, $searchText, $filters); //ok
		$records = $table->postProcess($records);
		
		//Prepare chart data
		$labels = []; //Grouping on xAxis
		$values = []; //Values with aggreation on yAxis
		$axisLabels = [];

		$avgHelper = [];
		$minHelper = [];
		$maxHelper = [];
		$xAxisValue = '';
		$yAxisValue = '';
		$yAxisValueIsNumber = false;
		$index = null;

		foreach ($records as $c1 => $record) {
			$xAxisValue = strval($record[$xAxisColumn]);
			$yAxisValue = $record[$yAxisColumn];
			$yAxisValueIsNumber = is_numeric($yAxisValue) && preg_match('/^[0-9]+[e|E][0-9]+$/', strval($yAxisValue)) === 0; //We dont want "1e223" to be numeric. Check php.org
			$yAxisValue = $yAxisValueIsNumber ? floatval($yAxisValue) : $yAxisValue;

			if (!in_array($xAxisValue, $labels, true)) {
				$labels[] = $xAxisValue;
				$values[] = 0.0;
				$avgHelper[] = 0;
				$minHelper[] = null;
				$maxHelper[] = null;
			}
			$index = array_search($xAxisValue, $labels, true);

			//Aggregation
			switch ($yAxisAggreation) {
				case 'count':
					$values[$index] += 1.0;
					break;
				case 'sum':
					$values[$index] += $yAxisValueIsNumber ? $yAxisValue : 0.0;
					break;
				case 'avg':
					$avgHelper[$index] += 1;
					$values[$index] += $yAxisValueIsNumber ? $yAxisValue : 0.0;
					if ($yAxisValueIsNumber && $c1 === (count($records) - 1)) //Last round, calculate average value
					{
						foreach ($values as $c2 => $val) {
							$values[$c2] /= $avgHelper[$c2];
						}
					}
					break;
				case 'min':
					if ($yAxisValueIsNumber && ($yAxisValue < $minHelper[$index] || $minHelper[$index] === null)) {
						$values[$index] = $yAxisValue;
						$minHelper[$index] = $yAxisValue;
					}
					break;
				case 'max':
					if ($yAxisValueIsNumber && ($yAxisValue > $maxHelper[$index] || $maxHelper[$index] === null)) {
						$values[$index] = $yAxisValue;
						$maxHelper[$index] = $yAxisValue;
					}
					break;
			}
		}

		//Replace empty label
		$emptyPos = array_search('', $labels, true);

		if ($emptyPos !== false && isset($labels[$emptyPos])) {
			$labels[$emptyPos] = CHelper::text('x_axis_empty');
		}


		$columns = $pageDescriptor->getTable()->getColumns(false);
		$axisLabels['x'] = $columns[$xAxisColumn]->label;
		switch ($yAxisAggreation) {
			case 'count':
				$axisLabels['y'] = CHelper::text('aggregation_count');
				break;
			case 'sum':
				$axisLabels['y'] = sprintf('%s (%s)', CHelper::text('aggregation_sum'), $columns[$yAxisColumn]->label);
				break;
			case 'avg':
				$axisLabels['y'] = sprintf('%s (%s)', CHelper::text('aggregation_avg'), $columns[$yAxisColumn]->label);
				break;
			case 'min':
				$axisLabels['y'] = sprintf('%s (%s)', CHelper::text('aggregation_min'), $columns[$yAxisColumn]->label);
				break;
			case 'max':
				$axisLabels['y'] = sprintf('%s (%s)', CHelper::text('aggregation_max'), $columns[$yAxisColumn]->label);
				break;
		}

		return (json_encode((object)
		[
			'labels' => $labels,
			'values' => $values,
			'axisLabels' => ((object)$axisLabels),
			'title' => CHelper::text('crudkit_diagram_view')
		]));
	}
	#endregion

	#region Helpers
	/** @ignore */


	/** @ignore */
	private function getPaginationUrls(int $pageNumber, string $pageId, int $recordCount, string $searchText = '', string $searchColumnName = '', array $filters = [])
	{
		$limit = config('crudkit.pagination_limit', 1) >= 1 ? config('crudkit.pagination_limit', 1) : 1;
		$result = [];
		$recordsPerPage = (int)config('crudkit.records_per_page', 8);

		// ### Definie all Urls Parametres ###
		$params = CHelper::getUrlParameters($pageId, ($pageNumber - 1), $searchText, $searchColumnName, $filters);

		// ### Create Urls ###
		//Previous
		$result['previous'] = URL::action('\Alddesign\Crudkit\Controllers\CrudkitController@listView', $params);

		//Pages
		$result['pages'] = [];
		for ($c = 1; (($c - 1) * $recordsPerPage) < $recordCount; $c++) {
			$params['page-number'] = $c;

			if (abs($pageNumber - $c) > $limit) //Dot Filler
			{
				if ($c >= $pageNumber) {
					$result['afterdot'] = true;
				} else {
					$result['predot'] = true;
				}
			} else //Pages
			{
				$result['pages'][$c] = URL::action('\Alddesign\Crudkit\Controllers\CrudkitController@listView', $params);
			}

			if ($c === 1) //First / Last
			{
				$result['first'] = URL::action('\Alddesign\Crudkit\Controllers\CrudkitController@listView', $params);
			}
			if (($c * $recordsPerPage) >= $recordCount) //Last
			{
				$result['last'] = URL::action('\Alddesign\Crudkit\Controllers\CrudkitController@listView', $params);
			}
		}

		//Next
		$params['page-number'] = $pageNumber + 1;
		$result['next'] = URL::action('\Alddesign\Crudkit\Controllers\CrudkitController@listView', $params);

		return $result;
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

		foreach ($columns as $column) {
			$attributes = '';

			$attributes .= (!empty($column->options['required']) && ($column->options['required'] == true)) ? ' required' : '';
			$attributes .= (!empty($column->options['readonly']) && ($column->options['readonly'] == true))  ? ' readonly' : '';

			if (in_array($column->type, ['integer', 'decimal'], true)) {
				$attributes .= !empty($column->options['min']) ? sprintf(' min="%s"', $column->options['min']) : '';
				$attributes .= !empty($column->options['max']) ? sprintf(' max="%s"', $column->options['max']) : '';
				$attributes .= !empty($column->options['step']) ? sprintf(' step="%s"', $column->options['step']) : '';
			}
			if (in_array($column->type, ['text', 'textlong'], true)) {
				$attributes .= !empty($column->options['min']) ? sprintf(' minlength="%s"', $column->options['min']) : '';
				$attributes .= !empty($column->options['max']) ? sprintf(' maxlength="%s"', $column->options['max']) : '';
			}

			$htmlInputAttributes[$column->name] = $attributes;
		}

		return ($htmlInputAttributes);
	}
	#endregion

	#region Ajax Api Endpoints
	public function ajaxManyToOne()
	{
		//Stop too much input
		if(request('stop', '') === '1')
		{
			return CHelper::getAjaxResult([]);
		}

		$pageId = request('pageId', '');
		$columnName = request('columnName', '');
		$input = strval(request('input', ''));

		if(!isset($this->pageStore->getPageDescriptors()[$pageId]))
		{
			return CHelper::getAjaxErrorResult('Invalid pageId "%s".', $pageId);
		}
		$page = $this->pageStore->getPageDescriptors()[$pageId];

		if(!isset($page->getTable()->getColumns()[$columnName]))
		{
			return CHelper::getAjaxErrorResult('Invalid columnName "%s" (not found in talbe "%s").', $columnName, $page->getTable()->getName());
		}
		/** @var SQLManyToOneColumn */
		$manyToOneColumn = $page->getTable()->getColumns()[$columnName];
		if(!$manyToOneColumn->isManyToOne || !$manyToOneColumn->ajax)
		{
			return CHelper::getAjaxErrorResult('Column "%s" is not a ManyToOne/Ajax column.', $columnName);
		}
		$ajaxOptions = $manyToOneColumn->getAjaxOptions();
		
		//Read Records
		/** @var \Illuminate\Database\Query\Builder $query */
		$query = DB::table($manyToOneColumn->toTableName);
		$query->select($manyToOneColumn->getColumnsForSelect(true));
		$fullSearch = $ajaxOptions->fullSearch ? '%' : '';
		foreach($ajaxOptions->searchFieldnames as $f)
		{
			$query->orWhere($f, 'like', $fullSearch.$input.'%');
		}
		if($ajaxOptions->maxResults > 0)
		{
			$query->limit($ajaxOptions->maxResults);
		}
		$records = $query->get();

		//Build result
		$data = [];
		$emptyImage = CHelper::binaryStringToBase64Png(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mM88x8AAp0BzdNtlUkAAAAASUVORK5CYII='), $ajaxOptions->maxImageWidth);
		foreach($records as $record)
		{
			$record = (array)$record;
			$id = isset($record[$manyToOneColumn->columnName]) ? strval($record[$manyToOneColumn->columnName]) : '';
			$textparts = [];
			foreach($manyToOneColumn->secondaryColumnNames as $index => $name)
			{
				$textparts[$name] = isset($record[$name]) ? strval($record[$name]) : '';
			}
			$text = implode(' ', $textparts);
			$img = '';
			if(!CHelper::e($ajaxOptions->imageFieldname))
			{
				if(isset($record[$ajaxOptions->imageFieldname]) && !CHelper::e($record[$ajaxOptions->imageFieldname]))
				{
					$img = CHelper::binaryStringToBase64Png($record[$ajaxOptions->imageFieldname], $ajaxOptions->maxImageWidth);
				}
				else
				{
					$img = $emptyImage;
				}
			}
			$img = $img !== '' ? 'data:image;base64,' . $img : '';

			//Make sure to return only strings
			$data[] = (object)['id' => $id, 'text' => $text, 'textparts' => $textparts, 'img' => $img];
		}

		return CHelper::getAjaxResult($data);
	}

	public function ajaxCustom()
	{
		//Stop too much input
		if(request('stop', '') === '1')
		{
			return CHelper::getAjaxResult([]);
		}
		
		$pageId = request('pageId', '');
		$columnName = request('columnName', '');
		$input = strval(request('input', ''));

		if(!isset($this->pageStore->getPageDescriptors()[$pageId]))
		{
			return CHelper::getAjaxErrorResult('','Invalid pageId "%s".', $pageId);
		}
		$page = $this->pageStore->getPageDescriptors()[$pageId];

		if(!isset($page->getTable()->getColumns()[$columnName]))
		{
			return CHelper::getAjaxErrorResult('','Invalid columnName "%s" (not found in talbe "%s").', $columnName, $page->getTable()->getName());
		}
		$table = $page->getTable();

		/** @var SQLColumn */
		$column = null;
		$column = $table->getColumns()[$columnName];
		if(!$column->isCustomAjax)
		{
			return CHelper::getAjaxErrorResult('','Column "%s" is not a Custom Ajax column.', $columnName);
		}
		
		$results = [];
		$column->triggerOnSearchEvent($results, $input, $table, $page, $column); //Trigger the event

		return $results;
	}
	#endregion
}
