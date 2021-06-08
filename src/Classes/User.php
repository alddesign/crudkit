<?php 
declare(strict_types=1);

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
	 * @var RestrictionSet $restrictionSet (default = null, no restrictions)
	 * @internal 
	 */  
	private $restrictionSet = null;
	
	/**
	 * Constructor.
	 * 
	 * See CrudkitServiceProvider documentation for how to define Users.
	 * 
	 * @param string $id The (unique) id of the user
	 * @param string $password The users password
	 * @param RestrictionSet $restrictionSet (optional)
	 * @param Startpage $startpage (optional) 
	 * 
	 * @see \Alddesign\Crudkit\CrudkitServiceProvider
	 * @see RestrictionSet
	 * @see Startpage
	 */
    public function __construct(string $id, string $password, RestrictionSet $restrictionSet = null, Startpage $startpage = null)
    {
		if(dp::e($id))
		{
			dp::crudkitException('User ID must be provided.', __CLASS__, __FUNCTION__);
		}
		
		if(dp::e($password))
		{
			dp::crudkitException('Password must be provided for user "%s".', __CLASS__, __FUNCTION__, $id);
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