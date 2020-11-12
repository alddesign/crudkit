<?php
/**
 * Class PageStore
 */
declare(strict_types=1);
namespace Alddesign\Crudkit\Classes;

use Alddesign\Crudkit\Classes\DataProcessor as dp;

/**
 * Object to store an access all the pages (bundeling)
 */
class PageStore
{
    private $pageDescriptors = [];

    /**
     * Constructor
     * @param PageDescriptor[] $pageDescriptors
     */
    public function __construct(array $pageDescriptors = [])
    {
        if(!dp::e($pageDescriptors))
		{
			foreach($pageDescriptors as $pageDescriptor)
			{
				$this->addPageDescriptor($pageDescriptor);
			}
		}
    }

    /**
     * Adds a single page.
     * @param PageDescriptor[] $pageDescriptor
     */
    public function addPageDescriptor(PageDescriptor $pageDescriptor)
    {
		if(isset($this->pageDescriptors[$pageDescriptor->getId()]))
		{
			dp::crudkitException('Page store - add: page ID "%s" already exists in page store.', __CLASS__, __FUNCTION__, $pageDescriptor->getId());
		}
	
        $this->pageDescriptors[$pageDescriptor->getId()] = $pageDescriptor;

        return $this;
    }
    
    /**
     * Gets all pages
     * @return PageDescriptor[] 
     */
	public function getPageDescriptors()
	{
		return $this->pageDescriptors;
	}

    /**
     * Gets a page
     * @param string $pageId ID of the Page
     * @param bool $errorIfPageNotFound Throw an Error if the page is not found
     * @return PageDescriptor
     */
    public function getPageDescriptor(string $pageId = '', bool $errorIfPageNotFound = false)
    {
        if(empty($this->pageDescriptors))
        {
            dp::crudkitException('No pages found.', __CLASS__, __FUNCTION__);
        }

        if(isset($this->pageDescriptors[$pageId]))
        {
            return $this->pageDescriptors[$pageId];
        }
        else 
		{
			if($errorIfPageNotFound)
			{
				dp::crudkitException('Page "%s" not found.', __CLASS__, __FUNCTION__. $pageId);
			}
            return reset($this->pageDescriptors); //Get first pageDescriptor
        }
    }

    /**
     * Gets an array of pages with and without categories.
     * @return string[]
     */
    public function getPageMap()
    {
        $pageMap = ['pages' => [], 'category-pages' => []];
                                                                      
        foreach($this->pageDescriptors as $page)
        {	
			if(!dp::e($page->getCategory()))
			{
				$pageMap['category-pages'][$page->getCategory()][$page->getId()] = $page->getName();
			}
			else
			{
				$pageMap['pages'][$page->getId()] = $page->getName();
			}
        }

        return $pageMap;
    }

}

