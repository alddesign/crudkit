<?php
/** User/permisson handling */
namespace Alddesign\Crudkit\Classes;

use Alddesign\Crudkit\Classes\DataProcessor as dp;
use \Exception;
use Response;

/** 
 * Provides functionality for user/permisson handling.
 */
class AuthHelper
{
	/** @var CurdkitUser[] $crudkitUsers 
	 * @internal 
	 */
	private $crudkitUsers = [];

	/** @var callable[] $callbacks Event callback function. [Key => Event name, Value => Callback function ]
	 * @internal 
	 */
	private $callbacks = [];
	
	/** 
	 * Constructor
	 * 
	 * @param CrudkitUser[] $crudkitUsers (optional)
	 * @internal
	 */
    public function __construct(array $crudkitUsers = [])
    {
		//dp::crudkitException('test',__CLASS__,__FUNCTION__);
		if(!dp::e($crudkitUsers))
		{
			foreach($crudkitUsers as $crudkitUser)
			{
				$this->addUser($crudkitUser);
			}
		}
    }
	
	/**
	 * Adds a new Crudkit user.
	 * @internal
	 */
	public function addCrudkitUser(CrudkitUser $crudkitUser)
	{
		$this->crudkitUsers[$crudkitUser->getId()] = $crudkitUser;
	}
	
	/**
	 * Defines a new User.
	 * 
	 * @param string $id The user ID
	 * @param string $password The password for this user
	 * @param Startpage $startpage (optional) The startpage for this user
	 * @param RestrictionSet $restrictionSet (optional) The restriction set (permissions) for this user
	 */
	public function addUser(string $id, string $password, Startpage $startpage = null, RestrictionSet $restrictionSet = null)
	{			
		$this->crudkitUsers[$id] = new CrudkitUser($id, $password, $startpage, $restrictionSet);
		return $this;
	}
	
	/**
	 * Checks if a certains user has access to a certain page/action
	 * @param string $action ['list', 'card', 'create', 'update', 'delete', 'export', 'chart', '']
	 * @internal
	 */
	public function userHasAccessTo(string $userId, string $action, string $pageId)
	{
		if(isset($this->crudkitUsers[$userId]))
		{
			return $this->crudkitUsers[$userId]->hasAccessTo($action, $pageId);
		}
		
		return false;
	}
	
	/**
	 * Register event handler. Occours after a user has logged in successfully.
	 * 
	 * ```php
	 * $f = function(string $username, bool $isAdmin){...};
	 * authHelper->onAfterLogin($f);
	 * ``` 
	 * @param callable $callback Callback function which is beeing called if this event occours.
	 * @event
	 */
	public function onAfterLogin(callable $callback)
	{
		$this->callbacks['onafterlogin'] = $callback;
		return $this;
	}
	
	/**
	 * Triggers an event.
	 * @param string $name Name of the Event
	 * @internal
	 */
	public function triggerEvent(string $name)
	{
		$name = mb_strtolower($name);
		
		if(!isset($this->callbacks[$name]) || !is_callable($this->callbacks[$name]))
		{
			return;
		}

		return call_user_func_array($this->callbacks[$name], [session('crudkit-username',''), session('crudkit-admin-user',false)]);
	}
	
	// ### STARTPAGE #########################################################################################################################################
	public function checkStartpage()
	{
		$username = session('crudkit-username', '');
	
		//User Level
		if(isset($this->crudkitUsers[$username]) && !empty($this->crudkitUsers[$username]->getStartpage()))
		{
			$this->crudkitUsers[$username]->getStartpage()->redirectTo();
			return;
		}
		
		//Config Level
		$startpage = config('crudkit.startpage', null);
		if(!empty($startpage))
		{
			(new Startpage($startpage['page-id'], $startpage['type'], $startpage['parameters']))->redirectTo();
			return;
		}
		
		//No Startpage
		dp::crudkitException('No startpage defined.', __CLASS__, __FUNCTION__);
	}
	
	// ### LOGIN #############################################################################################################################################
	public function checkAuth(string $action, string $pageId, bool $noPermissionCheck = false, bool $loginAttempt = false)
	{		
		//Login
		if(session('crudkit-logged-in', false) !== true)           
		{
			if($loginAttempt)
			{
				$loginOk = $this->performLogin();
				if(!$loginOk)
				{
					$this->loginFailed(dp::text('wrong_username_password'));
					return false; //Wrong username/password
				}
			}
			else
			{
				$this->loginFailed(dp::text('not_logged_in'));
				return false; //Not logged in.
			}
		}
		
		//Permissions 
		if(!$noPermissionCheck)
		{
			$permissionsOk = $this->checkPermissions($action, $pageId);
			if(!$permissionsOk)
			{
				dp::ex(dp::text('no_permisson'));
				return false; //No. Permission
			}
		}
			
		return true; //Everything ok
	}
	
	private function loginFailed(string $message = '')
	{
		Response::redirectToAction('\Alddesign\Crudkit\Controllers\CrudkitController@loginView')
			->with('crudkit-login-message', $message)
			->with('crudkit-login-message-type', 'danger')
			->send();
	}
	
	private function checkPermissions(string $action, string $pageId)
	{
		if(session('crudkit-admin-user', false) === true)
		{
			return true;
		}
		
		$username = session('crudkit-username', null);
		if(isset($this->crudkitUsers[$username]) && $this->crudkitUsers[$username] instanceof CrudkitUser)
		{
			return($this->crudkitUsers[$username]->hasAccessTo($action, $pageId));
		}
		
		return false;
	}
		
	private function performLogin()
	{

		//Load data from request
		$username = (request('crudkit-username', null) === null) ? '' : request('crudkit-username', null);
		$password = (request('crudkit-password', null) === null) ? '' : request('crudkit-password', null);
			
		// ### Admin Login: ###
		//Load username/password from config
		$adminUsername = config('crudkit.username', null);
		$adminPassword = config('crudkit.password', null);
		if($username === $adminUsername && $password === $adminPassword)
		{
			session()->flush();
			session(['crudkit-logged-in' => true]);
			session(['crudkit-username' => $adminUsername]);
			session(['crudkit-admin-user' => true]);
			
			return true; //Login ok
		}
		
		// ### User Login ###
		if(isset($this->crudkitUsers[$username]) && $this->crudkitUsers[$username] instanceof CrudkitUser)
		{
			$user = $this->crudkitUsers[$username];
			if($user->getId() === $username && $user->getPassword() === $password)
			{
				session()->flush();
				session(['crudkit-logged-in' => true]);
				session(['crudkit-username' => $user->getId()]);
				session(['crudkit-admin-user' => false]);
				
				return true;//Login ok
			}
		}
		
		return false; //Login not ok
	}
}