<?php
declare(strict_types=1);
namespace Alddesign\Crudkit\Classes;

use \Exception;

/**
 * Custom Exception which indicates its from CRUDKit itself. .
 * @internal
 */
class CException extends Exception
{
	/**
	 * Creates new CrudkitException. Can be usesd like `sprintf()`;
	 * 
	 * @param string $message The message with placeholders like `%s`
	 * @param mixed ...$values
	 */
	public function __construct(string $message, ...$values) 
	{
		$formattedMessage = '[curdkit-exception]' . vsprintf($message, $values);

        // make sure everything is assigned properly
        parent::__construct($formattedMessage);
    }
}