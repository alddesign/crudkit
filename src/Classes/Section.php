<?php
/**
 * Class Section
 */

declare(strict_types=1);
namespace Alddesign\Crudkit\Classes;

/** 
 * Defines a section on a Card page. 
 * 
 * Sections are a group of fields (from - to) on a page. 
 * 
 * Each Section is labeled with a title an can be folded (show/hide). Add Sections to a PageDescriptor Object via ->addSection()
 * @see PageDescriptor
 * @internal
 */
class Section
{
    public $title = "";
    public $from = "";
    public $to = "";
    public $collapsed = true;

    /**
     * Constructor
     * 
     * @param string $title The title to display (unique)
     * @param string $from From fieldname
     * @param string $to To fieldname
     */
    public function __construct(string $title, string $from, string $to, bool $collapsedByDefault = true)
    {		
      $this->title = $title;
      $this->from = $from;
      $this->to = $to;
      $this->collapsed = $collapsedByDefault;
    }
}
