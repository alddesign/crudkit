<?php
/**
 * Class AjaxOptions
{
 */
declare(strict_types=1);
namespace Alddesign\Crudkit\Classes;

use Alddesign\Crudkit\Classes\DataProcessor as dp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class AjaxOptions
{

	/** @var int */
	public $minInputLength = 1;
	/** @var string */
	public $imageFieldname = '';
	/** @var string[] */
	public $searchFieldnames = [];
	/** @var int */
	public $maxResults = 0;
	/** @var bool */
	public $fullSearch = false;
	/** @var int */
	public $maxImageWidth = 0;
	/** @var int */
	public $inputTimeout = 0;
	
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

