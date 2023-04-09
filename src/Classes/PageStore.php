<?php
/**
 * Class PageStore
 */
declare(strict_types=1);
namespace Alddesign\Crudkit\Classes;

use Illuminate\Support\Facades\URL;
use Psy\VersionUpdater\Checker;

/**
 * Object to store an access all the pages (bundeling)
 */
class PageStore
{
    /** @var PageDescriptor[] */
    private $pageDescriptors = [];
    private $menuLinks = [];
    private $categoryIcons = [];

    /**
     * Constructor
     * @param PageDescriptor[] $pageDescriptors
     */
    public function __construct(array $pageDescriptors = [])
    {
        if(!CHelper::e($pageDescriptors))
		{
			foreach($pageDescriptors as $pageDescriptor)
			{
				$this->addPageDescriptor($pageDescriptor);
			}
		}
    }


    /**
     * @param string $name The name of the link
     * @param string $url 
     * @param string $position 'before'|'after'|'' (use empty if there is no reference page - for example if its the only link present)
     * @param string $pageId The id of the page in combination with $position sets the position of the menu link
     * @param string $category Name of the category. A category groups one or more links in a named submenu.
     * @param string $id If this id matches a crudkit pageId, then the link will be marked as active on the page with this id
     * 
     * @return PageStore
     */
    public function addMenuLink(string $name, string $url, string $position = '', string $pageId = '', string $category = '', string $id = '')
    {
        $id = $id === '' ? $name : $id;
        $this->menuLinks[$name] = ['customLink' => true, 'name' => $name, 'id' => $id, 'url' => $url, 'position' => $position, 'pageId' => $pageId, 'category' => $category]; 
        return $this;
    }

    /**
     * @param string $category The name of the category
     * @param string $faIcon The name of the font awesome icon
     * 
     * @return PageStore
     */
    public function setCategoryFaIcon(string $category, string $faIcon)
    {
        $this->categoryIcons[$category] = $faIcon;
        return $this;
    }

    /**
     * Adds a single page.
     * @param PageDescriptor $pageDescriptor
     */
    public function addPageDescriptor(PageDescriptor $pageDescriptor)
    {
		if(isset($this->pageDescriptors[$pageDescriptor->getId()]))
		{
			throw new CException('Page store - add: page ID "%s" already exists in page store.', $pageDescriptor->getId());
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
            throw new CException('No pages found.');
        }

        if(isset($this->pageDescriptors[$pageId]))
        {
            return $this->pageDescriptors[$pageId];
        }
        else 
		{
			if($errorIfPageNotFound)
			{
				throw new CException('Page "%s" not found.', $pageId);
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
            if($page->getMenu())
            {
                $pageId = $page->getId();
                $category = $page->getCategory();
                $entry =
                [
                    'id' => $pageId,
                    'name' => $page->getName(),
                    'url' => URL::action('\Alddesign\Crudkit\Controllers\CrudkitController@listView', ['page-id' => $page->getId()])
                ];

                if(!CHelper::e($category))
                {
                    $this->insertMenuLink($pageMap, $pageId, 'before', $category);
                    $pageMap['category-pages'][$page->getCategory()][] = $entry;
                    $this->insertMenuLink($pageMap, $pageId, 'after', $category);
                }
                else
                {
                    $this->insertMenuLink($pageMap, $pageId, 'before', '');
                    $pageMap['pages'][] = $entry;
                    $this->insertMenuLink($pageMap, $pageId, 'after', '');
                }
            }
        }

        //Links with no page reference
        $this->insertMenuLink($pageMap, '','','',true);

        foreach($pageMap['category-pages'] as $category => $categoryPage)
        {
            $pageMap['category-icons'][$category] = isset($this->categoryIcons[$category]) ? $this->categoryIcons[$category] : 'list';
        }

        return $pageMap;
    }

    private function insertMenuLink(array &$pageMap, string $pageId, string $position, string $category, bool $direct = false)
    {
        foreach($this->menuLinks as $menuLink)
        {
            $category = $direct ? $menuLink['category'] : $category;
            if($menuLink['pageId'] === $pageId && $menuLink['position'] === $position && $menuLink['category'] === $category)
            {
                if(!CHelper::e($category))
                {
                    $pageMap['category-pages'][$category][] = $menuLink;
                }
                else
                {
                    $pageMap['pages'][] = $menuLink;
                }
            }
        }
    }

}

