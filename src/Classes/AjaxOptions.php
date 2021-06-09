<?php
/**
 * Class AjaxOptions
 */
declare(strict_types=1);
namespace Alddesign\Crudkit\Classes;


/**
 * Provides parameters for the behavoir of ajax powered columns. (Custom ajax columns or many to one ajax columns)
 */
class AjaxOptions
{

	/** @var int $minInputLength The min. amount of characters to type before the ajax call is fired */
	public $minInputLength = 1;
	/** @var string $imageFieldname The name of a table field that is used to display an image in the result list */
	public $imageFieldname = '';
	/** @var string[] $searchFieldnames The names of the table fields that are used for the searching records */
	public $searchFieldnames = [];
	/** @var int $maxResults The max. number of results to return, 0 = unlimited */
	public $maxResults = 0;
	/** @var bool $fullSearch true = %term% (contains), false = term% (starts with) */
	public $fullSearch = false;
	/** @var int $maxImageWidth The max. with of the images in the result list (will be croped down to this) */
	public $maxImageWidth = 0;
	/** @var int $inputTimeout The amount of time without user input, before the ajax call is fired [milliseconds] */
	public $inputTimeout = 0;
	
    /**
     * @param string $imageFieldname The name of a table field that is used to display an image in the result list
     * @param string[] $searchFieldnames The names of the table fields that are used for the searching records
     * @param int $minInputLength The min. amount of characters to type before the ajax call is fired
     * @param int $maxResults The max. number of results to return, 0 = unlimited
     * @param bool $fullSearch true = %term% (contains), false = term% (starts with)
     * @param int $maxImageWidth The max. with of the images in the result list (will be croped down to this)
     * @param int $inputTimeout The amount of time without user input, before the ajax call is fired [milliseconds]
     */
    public function __construct(string $imageFieldname = '', array $searchFieldnames = [], int $minInputLength = 1, int $maxResults = 50, bool $fullSearch = false, int $maxImageWidth = 100, int $inputTimeout = 300)
    {
		$minInputLength = $minInputLength > 0 ? $minInputLength : 1;

		$this->imageFieldname = $imageFieldname;
		$this->searchFieldnames = $searchFieldnames;
		$this->maxResults = $maxResults;
		$this->minInputLength = $minInputLength;
		$this->fullSearch = $fullSearch;
		$this->maxImageWidth = $maxImageWidth;
		$this->inputTimeout = $inputTimeout;
    }
}

