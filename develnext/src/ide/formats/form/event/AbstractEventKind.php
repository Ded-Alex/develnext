<?php
namespace ide\formats\form\event;

/**
 * Class AbstractEventKind
 * @package ide\formats\form\event
 */
abstract class AbstractEventKind
{
    /**
     * @return array
     */
    abstract public function getArguments();

    /**
     * @return array
     */
    public function getParamVariants()
    {
        return [];
    }

    final public function findParamName($param)
    {
        $variants = $this->getParamVariants();

        $func = function ($variants) use ($param, &$func) {
            foreach ($variants as $name => $value) {
                if ($value === '-') continue;

                if (is_array($value)) {
                    if ($result = $func($value)) {
                        return $result;
                    }

                    continue;
                }

                if ($value == $param) {
                    return $name;
                }
            }

            return null;
        };

        return $func($variants);
    }

    static function make(array $params)
    {
        $code = $params['code'];
        $name = $params['name'];
        $description = $params['description'];

        $icon = $params['icon'];
        $kind = $params['kind'];

        $kind = "ide\\formats\\form\\event\\{$kind}Kind";

        $kind = new $kind();

        return [
            'code'        => $code,
            'name'        => $name,
            'description' => $description,
            'icon'        => $icon,
            'kind'        => $kind,
        ];
    }
}