<?php
declare(strict_types=1);
namespace Alddesign\Crudkit\Classes;

/** 
 * Defines a section on a Card page. 
 * 
 * Sections are a group of fields (from - to). Each Section is labeled with a title an can be openend/closed on UI.
 * Add Sections to a PageDescriptor Object via ->addSection()
 */
class Section
{
    public $title = "";
    public $from = "";
    public $to = "";

    /**
     * Constructor
     * 
     * @param string $title The title to display (unique)
     * @param string $from From fieldname
     * @param string $to To fieldname
     */
    public function __construct(string $title, string $from, string $to)
    {		
      $this->title = $title;
      $this->from = $from;
      $this->to = $to;
    }
}
