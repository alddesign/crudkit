<?php
/**
 * Class Action
 */
declare(strict_types=1);
namespace Alddesign\Crudkit\Classes;

/**
 * Defines a custom action on a page. 
 * 
 * Actions are buttons on a page which execute user definded functions.
 */
class Action
{
    /** @internal */
    const POSITIONS = ['top','bottom','both', 'before-field', 'after-field', 'to-field'];
    /** @internal */
    const BTNCLASSES = ['default','primary','info','success','danger','warning', 'accent'];

    /** @var string $value The text for the action button itselft */
    public $value = '';
    /** @var string $label The text of the label for the action */
    public $label = '';
    /** @var callable $callback The function to execute */
    public $callback = null;
    /** @var bool $onList Show/hide on list pages */
    public $onList = true;
    /** @var bool $onCard Show/hide on card pages */
    public $onCard = true;
    /** @var bool $onUpdate Show/hide on update pages */
    public $onUpdate = true;
    /** @var string $faIcon The icon for the button (font awesome icon name) */
    public $faIcon = '';
    /** @var string $btnClass Defines the button appearance. Admin LTE button type. '','default','primary','info','success','danger','warning','accent'. '' = no button, just a link. */
    public $btnClass = 'default';
    /** @var string $fieldname The reference fieldname for setPosition() $position */
    public $fieldname = '';
    /** @var string $position 'top','bottom','both', 'before-field', 'after-field', 'to-field'. Use togehter with setFieldname() $fieldname. */
    public $position = '';
    /** @var string $enabled Enableds/disables the action */
    public $enabled = true; 
    /** @var string $visible Show/hide on all pages */
    public $visible = true;
    /** @var array $data Additional data which can be set when creatig the action. (Can be accessed in the callback function)*/
    public $data = [];

    /**
     * Constructor. Params are basically the properies of this class.
     * @param string $value The text for the action button itselft
     * @param string $label The text of the label for the action
     * @param callable $callback The function to execute
     * @param string $position 'top','bottom','both', 'before-field', 'after-field', 'to-field'
     * @param string $fieldname reference fieldname for $position
     * @return Action
     */
    public function __construct(string $value, string $label, callable $callback = null, string $position = 'both', string $fieldname = '')
    {	
        if(!in_array($position, self::POSITIONS, true))
		{
			$position = 'both';
        }
        	
        $this->value = $value;
        $this->label = $label;
        $this->callback = $callback;
        $this->position = $position;
        $this->fieldname = $fieldname;

        return $this;
    }

    #region Set #######################################################################################################################################################
    /**
     * The text for the action itselft
     * @return Action
     */ 
    public function setValue(string $value){$this->value = $value; return $this;}

    /**
     * The text of the label for the action
     * @return Action
     */ 
    public function setLabel(string $label){$this->label = $label; return $this;}

    /**
     * Show/hide on list pages
     * @return Action
     */
    public function setOnList(bool $onList = true){$this->onList = $onList; return $this;}

    /**
     * Show/hide on card pages.
     * @return Action
     */
    public function setOnCard(bool $onCard = true){$this->onCard = $onCard; return $this;}

    /**
     * Show/hides on update pages.
     * @return Action
     */
    public function setOnUpdate(bool $onUpdate = true){$this->onUpdate = $onUpdate; return $this;}

    /**
     * The icon for the button (font awesome icon name)
     * @return Action
     */
    public function setFaIcon(string $faIcon){$this->faIcon = $faIcon;return $this;}

    /**
     * Defines the button appearance. Admin LTE button type. '','default','primary','info','success','danger','warning','accent'. '' = no button, just a link.
     * @return Action
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
     * Enableds/disables the action
     * @return Action
     */
    public function setEnabled(bool $enabled = true){$this->enabled = $enabled;return $this;}

    /**
     * Show/hide on all pages
     * @return Action
     */
    public function setVisible(bool $visible = true){$this->visible = $visible; return $this;}

    /**
     * The callback function for the action
     * @return Action
     */ 
    public function setCallback(callable $callback){$this->callback = $callback;return $this;}

    /**
     * 'top','bottom','both', 'before-field', 'after-field', 'to-field'. Use togehter with setFieldname() $fieldname.
     * @return Action
     */ 
    public function setPosition(string $position){$this->position = $position;return $this;}

     /**
     * The reference fieldname for setPosition() $position
     * @return Action
     */ 
    public function setFieldname(string $fieldname){$this->fieldname = $fieldname;return $this;}

    /**
     * Additional data which can be set when creatig the action. (Can be accessed in the callback function)
     * @return Action
     */
    public function setData($data){$this->data = $data;return $this;}
    #endregion
}
