<?php
/**
 * Class EnumType
 */
namespace Alddesign\Crudkit\Classes;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

use Illuminate\Support\Facades\DB;

/**
 * Creating a custom enum type for integration with doctrine/dbal
 * 
 * @link http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/cookbook/custom-mapping-types.html
 * @link http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/cookbook/mysql-enums.html
 * @internal
 */
class EnumType extends Type
{
    const TYPENAME = 'enum';
    protected $values = array();
	
	/**
     * Creating custom type to deal with ENUM.
	 * @return void
	 */
	public static function registerDoctrineEnumMapping()
	{
		if(!Type::hasType('enum'))
		{
			Type::addType('enum', 'Alddesign\Crudkit\Classes\EnumType');
			DB::getDoctrineConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'enum');
		}
	}

	/** return the SQL used to create your column type. To create a portable column type, use the $platform. */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        $values = array_map(function($val) { return "'".$val."'"; }, $this->values);

        return "ENUM(".implode(", ", $values).")";
    }

	/** This is executed when the value is read from the database. Make your conversions here, optionally using the $platform. */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return $value;
    }

	/** This is executed when the value is written to the database. Make your conversions here, optionally using the $platform. */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (!in_array($value, $this->values)) {
            throw new \InvalidArgumentException("Invalid '".self::TYPENAME."' value.");
        }
        return $value;
    }

    public function getName()
    {
        return self::TYPENAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }
}