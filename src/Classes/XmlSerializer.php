<?php
namespace Alddesign\Crudkit\Classes;

/** @ignore */
class XmlSerializer 
{
	private $rootNodeName = 'nodes';
	private $defaultNodeName = '';
	private $indent = '';
	private $encoding = 'UTF-8';
	
	public function __construct(string $defaultNodeName = 'record')
	{
		$this->defaultNodeName = $defaultNodeName;
		$this->indent = "\t";
	}
	
    public function generateXmlFromObject(stdClass $object) 
	{
        $array = get_object_vars($object);
        return $this->generateXmlFromArray($array);
    }

    public function generateXmlFromArray(array $array) 
	{
        $xml = '<?xml version="1.0" encoding="' . $this->encoding . '"?>' . PHP_EOL;

        $xml .= '<' . $this->rootNodeName . '>';
        $xml .= $this->generateXml($array);
        $xml .= PHP_EOL . '</' . $this->rootNodeName . '>';

        return $xml;
    }

    private function generateXml(array $array, int $level = 1) 
	{
        $xml = '';
		$line = '';
		$indent = str_pad('', $level, $this->indent);

		foreach ($array as $key => $value) 
		{
			$line = '';
			$key = is_numeric($key) ? $this->defaultNodeName . (string)$key : $key;
			
			if(is_array($value) || is_object($value)) //subnodes
			{
				$line .= PHP_EOL . $indent . '<' . $key . '>';
				$line .= $this->generateXml($value, $level + 1);
				$line .= PHP_EOL . $indent . '</' . $key . '>';
			}
			else //value
			{
				$line .= PHP_EOL . $indent . '<' . $key . '>' . htmlspecialchars($value, ENT_QUOTES) . '</' . $key . '>';
			}
			
			$xml .= $line;
		}

        return $xml;
    }

}