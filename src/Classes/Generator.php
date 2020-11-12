<?php
/**
 * Class Generator
 */
namespace Alddesign\Crudkit\Classes;

use Alddesign\Crudkit\Classes\DataProcessor as dp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use \DateTime;

/**
 * Automatically generates a "CrudkitServiceProvider.php" file based on the configured database in .env
 * 
 * To use this functionality setup and configur Laravel and Crudkit and open http://<UrlToCrudKit>/auto-gererate
 * @internal
 */
class Generator
{
	/** @ignore */private $tablesCode = '';
	/** @ignore */private $pagesCode 	= '';
	
	/** @ignore */private $indent 	= '';
	/** @ignore */private $reference 	= null;
	
	/** @ignore */const TABLES_START = 	'<CRUDKIT-TABLES-START>';
	/** @ignore */const TABLES_END = 	'<CRUDKIT-TABLES-END>';
	/** @ignore */const PAGES_START = 	'<CRUDKIT-PAGES-START>';
	/** @ignore */const PAGES_END = 	'<CRUDKIT-PAGES-END>';
	/** @ignore */const CRUDKIT_AUTO_GENERATE_COMMENT = '/* !!! The following code has been automatically generated by CRUDKit. !!! */';
	
	/** @internal */
	public function __construct()
    {

    }
	
	/** Generates the CrudkitServiceProvider.php */
	public function generateServiceProvider()
	{		
		\Alddesign\Crudkit\Classes\EnumType::registerDoctrineEnumMapping();
		/** @var Doctrine\DBAL\Schema\Table[] $tables */
		$tables = DB::getDoctrineSchemaManager()->listTables();
		
		//Generate PHP Code
		$this->generateTablesCode($tables);
		$this->generatePagesCode($tables);
		
		//Create output file
		$dateTime = new DateTime();
		$srcFile = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'CrudkitServiceProvider.php';

		//Check if dir exists
		if(!File::exists(storage_path('crudkit')))
		{
			File::makeDirectory(storage_path('crudkit'));
		}		
		$outputFilename = storage_path('crudkit' . DIRECTORY_SEPARATOR . 'CrudkitServiceProvider.auto-generated.php');
		
		//Replace Code
		$lines = file($srcFile);
		$outputContent = "";
		$discardLines = false;
		foreach ($lines as $lineNo => $line) 
		{
			if($discardLines)
			{
				if(strpos($line, self::TABLES_END) !== false)
				{
					$discardLines = false;
					$outputContent .= $this->tablesCode . "\n";
					$outputContent .= $line . "\n";
				}
				if(strpos($line, self::PAGES_END) !== false)
				{
					$discardLines = false;
					$outputContent .= $this->pagesCode . "\n";
					$outputContent .= $line . "\n";
				}
			}
			else
			{
				if(strpos($line, self::TABLES_START) !== false || strpos($line, self::PAGES_START) !== false) 
				{
					$discardLines = true;
					$outputContent .= $line . "\n";
					$outputContent .= "\t\t" . self::CRUDKIT_AUTO_GENERATE_COMMENT . "\n";
				}
				else
				{
					$outputContent .= $line;
				}
			}
		}
		
		file_put_contents($outputFilename, $outputContent);
		
		return response()
			->download($outputFilename)
			->deleteFileAfterSend(true);	
	}
	
	/** 
	 * Generates the PHP code for method defineTalbes() 
	 * @param Doctrine\DBAL\Schema\Table[] $tables
	 */
	private function generateTablesCode(array $tables)
	{
		$this->setReference($this->tablesCode);
		
		$this->i(2);
		$this->l('return');
		$this->l('[');
		
		foreach($tables as $table)
		{
			$columns = $table->getColumns();
			if($table->hasPrimaryKey())
			{
				$primaryKeyColumns = $table->getPrimaryKeyColumns();
			}
			else
			{
				$primaryKeyColumns = array_keys($columns); //if no column is pk, then every column is pk
			}	
			
			//Find auto increment Key
			$autoIncrementKey = 'false';
			foreach($columns as $column)
			{
				if(in_array($column->getName(), $primaryKeyColumns, true) && $column->getAutoincrement())
				{
					$autoIncrementKey = 'true';
				}
			}
			
			//Create Table
			$this->i(3);
			$this->l('\''.$table->getName().'\' => (new TableDescriptor(\''.$table->getName().'\', [\''.implode('\', \'', $primaryKeyColumns).'\'], '.$autoIncrementKey.'))');

			//Add Columns
			$this->i(4);
			foreach($table->getColumns() as $column)
			{
				$this->l('->addColumn(\''.$column->getName().'\', \''.studly_case($column->getName()).'\', \''.$column->getType()->getName().'\', [])');
			}
			$this->l(',');
		}
		
		$this->i(2);
		$this->l('];');
		
		return null;
	}
	
	/** 
	 * Generates the PHP code for method definePages() 
	 * @param Doctrine\DBAL\Schema\Table[] $tables
	*/
	private function generatePagesCode(array $tables)
	{
		$this->setReference($this->pagesCode);
		
		$this->i(2);
		$this->l('return');
		$this->l('[');
		
		foreach($tables as $table)
		{
			$columns = $table->getColumns();
			if($table->hasPrimaryKey())
			{
				$primaryKeyColumns = $table->getPrimaryKeyColumns();
			}
			else
			{
				$primaryKeyColumns = array_keys($columns); //if no column is pk, then every column is pk
			}
			$firstPrimaryKeyColumnName = isset($primaryKeyColumns[0]) ? $primaryKeyColumns[0] : '' ;
			
			//Create Page
			$this->i(3);
			$this->l('\''.$table->getName().'\' => (new PageDescriptor(\''.studly_case($table->getName()).'\', \''.$table->getName().'\', $this->tables[\''.$table->getName().'\']))');
			
			$this->i(4);
			if(!dp::e($firstPrimaryKeyColumnName))
			{
				$this->l('->setCardLinkColumns([\''.$firstPrimaryKeyColumnName.'\'])');
			}
			$this->l(',');
		}
		
		$this->i(2);
		$this->l('];');
		
		return null;
	}
	
	/** Write line */
	private function l(string $value = '')
	{
		$this->reference .= $this->indent . $value . "\n";
	}
	
	/** Sets the indention level */
	private function i(int $level = 0) 
	{
		$this->indent = str_pad('', $level, "\t");
	}

	/** @ignore */
	private function setReference(&$var)
	{
		$this->reference = &$var;
	}
}