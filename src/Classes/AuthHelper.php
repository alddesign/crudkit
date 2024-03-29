<?php
declare(strict_types=1);
namespace Alddesign\Crudkit\Classes;

use Illuminate\Support\Facades\Response;

/** Provides functionality for user/permisson handling. */
class AuthHelper
{
	/** 
	 * @var User[] $users 
	 * @internal 
	 */
	private $users = [];

	/** 
	 * @var callable[] $callbacks Event callback function. [Key => Event name, Value => Callback function ]
	 * @internal 
	 */
	private $callbacks = [];
	
	/** 
	 * Constructor
	 * 
	 * @param User[] $users (optional)
	 * @return AuthHelper
	 * @stackable
	 */
    public function __construct(array $users = [])
    {
		foreach($users as $user)
		{
			if(gettype($user) !== 'object' || get_class($user) !== 'Alddesign\Crudkit\Classes\User')
			{
				throw new CException('Array of "Alddesign\Crudkit\Classes\User" expected.');
			}
		}

		$this->users = $users;
		return $this;
    }
	
	/**
	 * Defines a new User.
	 * 
	 * @param User $user The user Object.
	 * @return AuthHelper
	 * @stackable
	 */
	public function addUserObject(User $user)
	{
		$this->users[$user->getId()] = $user;
		return $this;
	}

	/**
	 * Gets all users
	 * @return CurdkitUser[]
	 */
	public function getUsers()
	{
		return $this->users;
	}
	
	/**
	 * Defines a new User.
	 * 
	 * @param string $id The user ID
	 * @param string $password The password for this user
	 * @param RestrictionSet $restrictionSet (optional) The restriction set (permissions) for this user
	 * @param Startpage $startpage (optional) The startpage for this user
	 * @return AuthHelper
	 * @stackable
	 */
	public function addUser(string $id, string $password, RestrictionSet $restrictionSet = null, Startpage $startpage = null)
	{			
		$this->users[$id] = new User($id, $password, $startpage, $restrictionSet);
		return $this;
	}
	
	/**
	 * Checks if a certains user has access to a certain page/action
	 * @param string $action ['list', 'card', 'create', 'update', 'delete', 'export', 'chart', '']
	 * @return bool
	 * @internal
	 */
	public function userHasAccessTo(string $action, string $pageId)
	{
		if($this->isAdmin())
		{
			return true;
		}

		$userId = $this->getUserId();
		if($this->isLoggedIn() && isset($this->users[$userId]))
		{
			return $this->users[$userId]->hasAccessTo($action, $pageId);
		}
		
		return false;
	}

	
	/**
	 * Checks if the user is logged in.
	 * 
	 * @return bool
	 */
	public function isLoggedIn()
	{
		return session('crudkit-logged-in', false) === true;
	}

	
	/**
	 * Checks if the user is an admin user (and if the user is logged in)
	 * @return bool
	 */
	public function isAdmin()
	{
		return session('crudkit-admin-user', false) === true;
	}

	
	/**
	 * Gets the current user id. Returns '' if not logged in.
	 * @return string
	 */
	public function getUserId()
	{
		$userId = session('crudkit-userid', '');
		return gettype($userId) === 'string' ? $userId : '';
	}
	
	/**
	 * Register event handler. Occours after a user has logged in successfully.
	 * 
	 * ```php
	 * $f = function(string $username, bool $isAdmin){...};
	 * authHelper->onAfterLogin($f);
	 * ``` 
	 * @param callable $callback Callback function which is beeing called if this event occours.
	 * @return AuthHelper
	 * @event
	 * @stackable
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

		return call_user_func_array($this->callbacks[$name], [session('crudkit-userid',''), session('crudkit-admin-user',false)]);
	}
	
	#region Startpage ----------------------------------------------------------------------------------------------------------
	/** @internal */
	public function checkStartpage()
	{
		$username = session('crudkit-userid', '');
	
		//User Level
		if(isset($this->users[$username]) && !empty($this->users[$username]->getStartpage()))
		{
			$this->users[$username]->getStartpage()->redirectTo();
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
		throw new CException('No startpage defined.');
	}
	#endregion

	#region Login --------------------------------------------------------------------------------------------------------------
	/**
	 * Checks if there is cookie data for skin/accent. If yes, override crudkit config.
	 * 
	 * @return void
	 */
	public function checkCookies()
	{
		if(config('crudkit.theme_selector', false))
		{
			//Skiiiin
			$skin = request()->cookie('crudkit-skin', '');
			$accent = request()->cookie('crudkit-accent', '');
			if(!CHelper::e($skin))
			{
				config(['crudkit.skin' => $skin]);
			}
			if(!CHelper::e($accent))
			{
				config(['crudkit.accent' => $accent]);
			} 
		}
	}

	/**
	 * Checks if user is logged in and his permissions to the current page/action. 
	 * @internal 
	 */
	public function checkAuth(string $action, string $pageId, bool $noPermissionCheck = false, bool $loginAttempt = false)
	{	
		$this->checkCookies();

		//Login
		if(session('crudkit-logged-in', false) !== true)           
		{
			if($loginAttempt)
			{
				$loginOk = $this->performLogin();
				if(!$loginOk)
				{
					$this->loginFailed(CHelper::text('wrong_username_password'));
					return false; //Wrong username/password
				}
			}
			else
			{
				$this->loginFailed(CHelper::text('not_logged_in'));
				return false; //Not logged in.
			}
		}
		
		//Permissions 
		if(!$noPermissionCheck)
		{
			$permissionsOk = $this->checkPermissions($action, $pageId);
			if(!$permissionsOk)
			{
				throw new CException(CHelper::text('no_permisson'));
			}
		}
			
		return true; //Everything ok
	}
	
	/** @internal */
	private function loginFailed(string $message = '')
	{	
		Response::redirectToAction('\Alddesign\Crudkit\Controllers\CrudkitController@loginView')
			->with('crudkit-login-message', $message)
			->with('crudkit-login-message-type', 'danger')
			->send();
	}
	
	/** @internal */
	private function checkPermissions(string $action, string $pageId)
	{
		if(session('crudkit-admin-user', false) === true)
		{
			return true;
		}
		
		$username = session('crudkit-userid', null);
		if(isset($this->users[$username]) && $this->users[$username] instanceof User)
		{
			return($this->users[$username]->hasAccessTo($action, $pageId));
		}
		
		return false;
	}
	
	/** @internal */
	private function performLogin()
	{
		//Load data from request
		$username = (request('crudkit-userid', null) === null) ? '' : request('crudkit-userid', null);
		$password = (request('crudkit-password', null) === null) ? '' : request('crudkit-password', null);
			
		// ### Admin Login: ###
		//Load username/password from config
		$adminUsername = config('crudkit.username', null);
		$adminPassword = config('crudkit.password', null);
		if($username === $adminUsername && $password === $adminPassword)
		{
			session()->flush();
			session()->start();
			session(['crudkit-logged-in' => true]);
			session(['crudkit-userid' => $adminUsername]);
			session(['crudkit-admin-user' => true]);
			
			return true; //Login ok
		}
		
		// ### User Login ###
		if(isset($this->users[$username]) && $this->users[$username] instanceof User)
		{
			$user = $this->users[$username];
			if($user->getId() === $username && $user->getPassword() === $password)
			{
				session()->flush();
				session()->start();
				session(['crudkit-logged-in' => true]);
				session(['crudkit-userid' => $user->getId()]);
				session(['crudkit-admin-user' => false]);
				
				return true;//Login ok
			}
		}
		
		return false; //Login not ok
	}
	#endregion
}