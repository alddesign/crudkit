<?php

namespace Alddesign\Crudkit\Classes;

use Alddesign\Crudkit\Classes\DataProcessor as dp;
use Exception;

class PageStore
{
    private $pageDescriptors = [];

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

    public function addPageDescriptor(PageDescriptor $pageDescriptor)
    {
		if(isset($this->pageDescriptors[$pageDescriptor->getId()]))
		{
			throw new Exception(sprintf('Page store - add: page ID "%s" already exists in page store.', $pageDescriptor->getId()));
		}
	
        $this->pageDescriptors[$pageDescriptor->getId()] = $pageDescriptor;

        return $this;
    }
	
	public function getPageDescriptors()
	{
		return $this->pageDescriptors;
	}

    public function getPageDescriptor(string $pageId = '', bool $errorIfPageNotFound = false)
    {
        if(empty($this->pageDescriptors))
        {
            throw new Exception('PageStore - getPageDescriptor(): No pages added.');
        }

        if(isset($this->pageDescriptors[$pageId]))
        {
            return $this->pageDescriptors[$pageId];
        }
        else 
		{
			if($errorIfPageNotFound)
			{
				throw new Exception(sprintf('PageStore - getPageDescriptor(): page "%s" not found.', $pageId));
			}
            return reset($this->pageDescriptors); //Get first pageDescriptor
        }
    }

    public function getPageMap()
    {
        $pageMap = ['pages' => [], 'category-pages' => []];
                                                                      
        foreach($this->pageDescriptors as $page)
        {	
			if($page->getCategory() !== '' && $page->getCategory() !== null)
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

