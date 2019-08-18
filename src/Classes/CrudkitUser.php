<?php 
namespace Alddesign\Crudkit\Classes;

use Alddesign\Crudkit\Classes;
use Alddesign\Crudkit\Classes\DataProcessor as dp;
use \Exception;

class CrudkitUser
{	
	private $id = '';
	private $password = '';
	private $startpage = null; //Instance of Class Startpage
	private $restrictionSet = null; //Instance of Class RestrictionSet
	
    public function __construct(string $id, string $password, Startpage $startpage = null, RestrictionSet $restrictionSet = null)
    {
		if(dp::e($id))
		{
			dp::ex('CrudkitUser - __construct: user ID must not be empty or null.');
		}
		
		if(dp::e($password))
		{
			dp::ex('CrudkitUser - __construct: password must not be empty or null.');
		}
		
		$this->id = $id;
		$this->password = $password;
		$this->startpage = $startpage;
		$this->restrictionSet = $restrictionSet;
    }
	
	public function setStartpage(Startpage $startpage)
	{
		$this->startpage = $startpage;
	}
	
	public function getStartpage()
	{
		return $this->startpage;
	}
	
	public function setRestrictionSet(RestrictionSet $restrictionSet)
	{
		$this->restrictionSet = $restrictionSet;
	}
	
	public function getRestrictionSet()
	{
		return $this->restrictionSet;
	}
	
	public function getId()
	{
		return((string)$this->id);
	}
	
	public function getPassword()
	{
		return((string)$this->password);
	}

	public function hasAccessTo(string $action, string $pageId)
	{
		return ($this->restrictionSet === null) ? true : ($this->restrictionSet->hasAccessTo($action, $pageId));
	}
	
}