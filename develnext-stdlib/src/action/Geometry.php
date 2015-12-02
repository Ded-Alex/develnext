<?php
namespace action;
use php\gui\framework\ObjectGroup;

/**
 * Class Geometry
 * @package action
 */
class Geometry
{
    /**
     * @param object $one
     * @param object $two
     * @param string $type
     * @return bool
     */
    static function intersect($one, $two, $type = 'RECTANGLE')
    {
        if ($one instanceof ObjectGroup) {
            foreach ($one->getInstances() as $instance) {
                if (Geometry::intersect($instance, $two, $type)) {
                    return true;
                }
            }

            return false;
        }

        if ($two instanceof ObjectGroup) {
            foreach ($two->getInstances() as $instance) {
                if (Geometry::intersect($one, $instance, $type)) {
                    return true;
                }
            }

            return false;
        }

        list($x, $y) = [$one->x, $one->y];
        list($w, $h) = [$one->width, $one->height];

        $nx = $two->x;
        $ny = $two->y;

        $nw = $two->width;
        $nh = $two->height;

        switch ($type) {
            case 'RECTANGLE':
            default:
                $nCenter = [$nx + round($nw / 2), $ny + round($nh / 2)];
                $center = [$x + round($w / 2), $y + round($h / 2)];

                $_w = abs($center[0] - $nCenter[0]);
                $_h = abs($center[1] - $nCenter[1]);

                $checkW = $_w < ($w / 2 + $nw / 2);
                $checkH = $_h < ($h / 2 + $nh / 2);

                return ($checkW && $checkH);
        }
    }
}