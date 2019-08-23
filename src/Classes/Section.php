<?php
declare(strict_types=1);
namespace Alddesign\Crudkit\Classes;

/** 
 * Defines a section on a Card page. 
 * 
 * Sections are a groud of fields (from - to)
 * @internal 
 */
class Section
{
    public $title = "";
    public $from = "";
    public $to = "";

    /**
     * Constructor
     * 
     * @param string $title
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
