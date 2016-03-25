<?php
namespace ide\autocomplete\php;

use ide\autocomplete\AutoCompleteTypeLoader;
use php\lib\Str;

/**
 * Class PhpAutoCompleteLoader
 * @package ide\autocomplete\php
 */
class PhpAutoCompleteLoader extends AutoCompleteTypeLoader
{
    /**
     * @var PhpAnyAutoCompleteType
     */
    protected $anyType;
    protected $variableType;
    protected $thisType;
    protected $eventType;

    /**
     * @var array
     */
    protected $reflectionTypes = [];

    /**
     * PhpAutoCompleteLoader constructor.
     */
    public function __construct()
    {
        $this->anyType = new PhpAnyAutoCompleteType();
        $this->variableType = new PhpAnyAutoCompleteType('~variable');
        $this->thisType = new ThisAutoCompleteType();
        $this->eventType = new EventVariableAutoCompleteType();
    }

    public function load($name)
    {
        switch ($name) {
            case '~any':
                return $this->anyType;
            case '~variable':
                return $this->variableType;
            case '~this':
                return $this->thisType;
            case '~event':
                return $this->eventType;

            default:
                if (str::startsWith($name, '~this ')) {
                    return new ThisObjectAutoCompleteType(Str::sub($name, 6));
                }

                if (str::startsWith($name, '~static ')) {
                    return new StaticAccessAutoCompleteType(str::sub($name, 8));
                }

                if (str::startsWith($name, '~dynamic ')) {
                    return new DynamicAccessAutoCompleteType(str::sub($name, 9));
                }

                if (class_exists($name)) {
                    if ($type = $this->reflectionTypes[$name]) {
                        return $type;
                    }

                    return $this->reflectionTypes[$name] = new ReflectionClassAutoCompleteType($name);
                }
        }

        return null;
    }
}