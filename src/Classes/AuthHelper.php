<?php
namespace Alddesign\Crudkit\Classes;

use Alddesign\Crudkit\Classes\DataProcessor as DP;
use \Exception;
use Response;

class AuthHelper
{
	private $crudkitUsers = [];
	
	private $onAfterLogin = null; //After successfull login (credentials are valid). Parameters: string $username, string $password, bool $isAdmin
	
    public function __construct(array $crudkitUsers = [])
    {
		if(!dp::e($crudkitUsers))
		{
			foreach($crudkitUsers as $crudkitUser)
			{
				$this->addUser($crudkitUser);
			}
		}
    }
	
	public function addUser(CrudkitUser $crudkitUser)
	{
		$this->crudkitUsers[$crudkitUser->getId()] = $crudkitUser;
	}
	
	public function addUserIdPassword(string $id, string $password, Startpage $startpage = null, RestrictionSet $restrictionSet = null)
	{			
		$this->crudkitUsers[$id] = new CrudkitUser($id, $password, $startpage, $restrictionSet);
		return $this;
	}
	
	public function userHasAccessTo(string $id, string $action, string $pageId)
	{
		if(isset($this->crudkitUsers[$id]))
		{
			return $this->crudkitUsers[$id]->hasAccessTo($action, $pageId);
		}
		
		return false;
	}
	
	public function onAfterLogin(callable $callback)
	{
		$this->onAfterLogin = $callback;
		return $this;
	}
	
	public function executeCallback(string $name)
	{
		$callback = null;
		$name = strtolower($name);
		
		switch($name)
		{
			case 'onafterlogin' : $callback = $this->onAfterLogin; break;
			default : throw new Exception(sprintf('AuthHelper Callback: Ungültiger Callback "%s".', $name)); break;
		}
		if($callback !== null)
		{
			return call_user_func_array($callback, array(session('crudkit-username',''), session('crudkit-admin-user',false)));
		}
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
		throw new Exception('Keine Startseite definiert.');
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
		Response::redirectToAction('\Alddesign\Crudkit\Controllers\AdminPanelController@loginView')
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