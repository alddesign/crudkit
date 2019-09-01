<?php 
namespace Alddesign\Crudkit\Classes;

use Alddesign\Crudkit\Classes;
use Alddesign\Crudkit\Classes\DataProcessor as dp;
use \Exception;

/** Represents a user account (login) */ 
class User
{	
	/** @internal */ private $id = '';
	/** @internal */ private $password = '';
	/** @internal */ private $fullname = '';
 	/**
	 * @var Startpage $startpage 
	 * @internal 
	 */ 
	private $startpage = null;
	/**
	 * @var RestrictionSet $restrictionSet 
	 * @internal 
	 */  
	private $restrictionSet = null;
	
    public function __construct(string $id, string $password, RestrictionSet $restrictionSet = null, Startpage $startpage = null)
    {
		if(dp::e($id))
		{
			dp::crudkitException('User ID must not be empty or null.', __CLASS__, __FUNCTION__);
		}
		
		if(dp::e($password))
		{
			dp::crudkitException('Password must not be empty or null.', __CLASS__, __FUNCTION__);
		}
		
		$this->id = $id;
		$this->password = $password;
		$this->startpage = $startpage;
		$this->restrictionSet = $restrictionSet;
    }
	
	/** @internal */
	public function setStartpage(Startpage $startpage)
	{
		$this->startpage = $startpage;
	}
	
	/** @internal */
	public function getStartpage()
	{
		return $this->startpage;
	}

	/** @internal */
	public function setRestrictionSet(RestrictionSet $restrictionSet)
	{
		$this->restrictionSet = $restrictionSet;
	}
	
	/** @internal */
	public function getRestrictionSet()
	{
		return $this->restrictionSet;
	}

	/** @internal */
	public function getId()
	{
		return((string)$this->id);
	}
	
	/** @internal */
	public function getPassword()
	{
		return((string)$this->password);
	}

	/** @internal */
	public function hasAccessTo(string $action, string $pageId)
	{
		return ($this->restrictionSet === null) ? true : ($this->restrictionSet->hasAccessTo($action, $pageId));
	}
	
}