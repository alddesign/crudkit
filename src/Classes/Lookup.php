<?php
/**
 * Class Lookup
 */
declare(strict_types=1);
namespace Alddesign\Crudkit\Classes;

use Illuminate\Support\Facades\URL;
use Alddesign\Crudkit\Classes\DataProcessor as dp;

/**
 * A lookup is a calculated field with values from other tables or fixed values
 */
class Lookup
{
    /** @internal */
    const POSITIONS = ['before-field', 'after-field', 'to-field'];
    const VALUETYPES = ['lookup', 'lookup-all', 'const', 'count', 'sum'];
    const BTNCLASSES = ['default','primary','info','success','danger','warning', 'accent'];

    public $value = '';
    public $label = '';
    public $onList = true;
    public $onCard = true;
    public $onUpdate = true;
    public $faIcon = '';
    public $btnClass = 'default';
    public $position = '';
    public $fieldname = '';
    public $enabled = true; 
    public $visible = true;
    /** @var TableDescriptor the reference table for the lookup */
    private $table = null;
    public $tableFieldname = '';
    public $pageId = '';
    public $cardPage = false;
    /** @var FilterDefinition[] fitlers for the reference talbe */
    public $filterDefinitions = [];
    public $valueType = 'lookup';
    public $drillDownLink = '';
    private $overrideDrillDownLink = false;
    public $drillDownTarget = '_self';
    public $lookupAllSeparator = ', ';

    /**
     * Creates a Lookup 
     * 
     * @param TableDescriptor $table the reference table for the lookup
     * @param string $tableFieldname the fieldname in the recerence table
     * @param array $filterDefinitions fitlers for the reference talbe
     * @param string $valueType Defindes whats the value of the label (that is displayed)'lookup', 'lookup-all', 'const', 'count', 'sum'
     * @param string $label The text of the label for the action
     * @param string $position Position on card page. 'before-field', 'after-field', 'to-field'
     * @param string $fieldname The reference field for $position
     * @param string $page Specifiy a page for drillDown 
     * @param bool $cardPage Shows the related record in a card page (fitlers have to return exactly one record!)
     * @param string $value The value for $valueType 'const'
     * 
     * @return Lookup
     * @stackable
     */
    public function __construct(TableDescriptor $table, string $tableFieldname, array $filterDefinitions, string $valueType, string $label, string $position, string $fieldname, string $pageId = '', bool $cardPage = false, string $value = '')
    {	
        if(!in_array($position, self::POSITIONS, true))
		{
			$position = 'before-field';
        }
        if(!in_array($valueType, self::VALUETYPES, true))
		{
			$valueType = 'lookup';
        }

        $this->value = $value;
        $this->label = $label;
        $this->position = $position;
        $this->fieldname = $fieldname;

        $this->table = $table;
        $this->tableFieldname = $tableFieldname;
        $this->filterDefinitions = $filterDefinitions;
        $this->valueType = $valueType;
        $this->cardPage = $cardPage;
        $this->btnClass = '';
        $this->pageId = $pageId;

        return $this;
    }

    /**
     * Calculates the values of the lookup field
     * 
     * @param array $record The source record
     * @return void
     */
    public function calculateLookup(array $record, bool $calculateLink = true)
    {
        if($calculateLink)
        {
            $this->drillDownLink = $this->getDrillDownLink($record);
        }

        if($this->valueType === 'const')
        {
            return; //value is already $this->value;
        }

        $filters = [];
        if(!dp::e($this->filterDefinitions))
        {
            foreach($this->filterDefinitions as $index => $filterDefinition)
            {
                $filters[] = $filterDefinition->toFilter($record);
            }
        }

        $records = $this->table->readRecords(0, '', '', $filters,true, true, true, true, true)['records'];
        

        if($this->valueType === 'count')
        {
            $this->value = count($records);
            return;
        }

        if(!isset($records[0])) 
        {
            $this->value =  null;
            return;
        }

        if($this->valueType === 'lookup')
        {
            $this->value =  isset($records[0][$this->tableFieldname]) ? $records[0][$this->tableFieldname] : null;
            return;
        }

        if($this->valueType === 'lookup-all')
        {
            $values = [];
            foreach($records as $record)
            {
                if(isset($record[$this->tableFieldname]))
                {
                    $values[] = $record[$this->tableFieldname];
                }
            }
            $this->value = implode($this->lookupAllSeparator, $values);
            return;
        }

        //sum
        $sum = 0;
        $c = 0;
        foreach($records as $record)
        {
            $c += 1;
            $sum += isset($record[$this->tableFieldname]) ? $record[$this->tableFieldname] : 0;
        }
        $this->value = $sum;
    }

    /**
     * Gets the link for the related page (card or list)
     * 
     * @param array $record The reference record
     * 
     * @return string
     */
    public function getDrillDownLink(array $record)
	{
        if($this->overrideDrillDownLink)
        {
            return $this->drillDownLink;
        }

        if(dp::e($this->pageId))
        {
            return '';
        }

        $urlParameters = [];
        $urlParameters['page-id'] = $this->pageId;
        if(!dp::e($this->filterDefinitions))
        {
            foreach($this->filterDefinitions as $index => $filterDefinition)
            {
                $filter = $filterDefinition->toFilter($record);
                $filter->appendToUrlParams($urlParameters, $index);
            }
        }
        
        if($this->cardPage)
        {
            return URL::action('\Alddesign\Crudkit\Controllers\CrudkitController@cardView', $urlParameters);
        }
        else
        {
            return URL::action('\Alddesign\Crudkit\Controllers\CrudkitController@listView', $urlParameters);
        }
	}

    #region Set #######################################################################################################################################################
    /**
     * Set the value of the action/lookup on pages
     * @return Lookup
     */ 
    public function setValue(string $value){$this->value = $value; return $this;}

    /**
     * Set the label of the action/lookup on pages
     * @return Lookup
     */ 
    public function setLabel(string $label){$this->label = $label; return $this;}

    /**
     * Show/hide on list pages.
     * @return Lookup
     */
    public function setOnList(bool $onList = true){$this->onList = $onList; return $this;}

    /**
     * Show/hide on card pages.
     * @return Lookup
     */
    public function setOnCard(bool $onCard = true){$this->onCard = $onCard; return $this;}

    /**
     * Show/hides on update pages.
     * @return Lookup
     */
    public function setOnUpdate(bool $onUpdate = true){$this->onUpdate = $onUpdate; return $this;}

    /**
     * Sets the icon for the button (font awesome icon name)
     * @return Lookup
     */
    public function setFaIcon(string $faIcon){$this->faIcon = $faIcon;return $this;}

    /**
     * Defines the button appearance.
     * 
     * Admin LTE button type. ''|'default'|'primary'|'info'|'success'|'danger'|'warning'|'accent'
     * @stackable
     */
    public function setBtnClass(string $btnClass = 'default')
    {
        if(!in_array($btnClass, self::BTNCLASSES, true) && $btnClass !== '')
		{
			$btnClass = 'default';
        }
        
        $this->btnClass = $btnClass;
        return $this;
    }

    /**
     * Set reference fieldname for $position
     * @return Lookup
     */ 
    public function setFieldname(string $fieldname){$this->fieldname = $fieldname;return $this;}


    /**
     * Enableds/disables the action
     * @return Lookup
     * @stackable
     */
    public function setEnabled(bool $enabled = true){$this->enabled = $enabled;return $this;}

    /**
     * Show/hide.
     * @return Lookup
     * @stackable
     */
    public function setVisible(bool $visible = true){$this->visible = $visible; return $this;}

    /**
     * Set the reference table
     * @return Lookup
     */ 
    public function setTable(TableDescriptor $table){$this->table = $table; return $this;}

    /**
     * Set the fieldname of the reference table (for lookup)
     * @return Lookup
     */ 
    public function setTableFieldname(string $tableFieldname)
    {$this->tableFieldname = $tableFieldname;return $this;}

    /**
     * Set the position of the lookup 'before-field', 'after-field', 'to-field'
     * @return Lookup
     */ 
    public function setPosition(string $position){$this->position = $position; return $this;}

    /**
     * Set the drilldown pageId
     * @return Lookup
     */ 
    public function setPageId(string $pageId){$this->pageId = $pageId; return $this;}

    /**
     * Set if the drilldown Page is a cardpage
     * @return Lookup
     */ 
    public function setCardPage(bool $cardPage){$this->cardPage = $cardPage;return $this;}

    /**
     * Add a filter definitinon for the reference table
     * @return Lookup
     */ 
    public function addFilterDefinitiona(FilterDefinition $filterDefinition){$this->filterDefinitions[] = $filterDefinition; return $this;}

    /**
     * Set the value type lookup', 'lookup-all', 'const', 'count', 'sum'
     * @return Lookup
     */ 
    public function setValueType(string $valueType){$this->valueType = $valueType; return $this;}

    /**
     * Set the target for the drilldown link (_blank, _self, _parent, _top). Default is _self
     * @return Lookup
     */ 
    public function setDrillDownTarget(string $drillDownTarget){$this->drillDownTarget = $drillDownTarget; return $this;}

    /**
     * Set the drilldown link maunally
     * @param string $drillDownLink
     * @param bool $override If this is set to true, the lookup will always take the link you provide instead of calculating one base on the table and filters
     * @return Lookup
     */ 
    public function setDrillDownLink(string $drillDownLink, bool $override = true){$this->drillDownLink = $drillDownLink; $this->overrideDrillDownLink = $override; return $this;}

    /**
     * Set the separator string for $valueType
     * @return Lookup
     */ 
    public function setLookupAllSeparator(string $lookupAllSeparator){$this->lookupAllSeparator = $lookupAllSeparator; return $this;}
    #endregion
}
