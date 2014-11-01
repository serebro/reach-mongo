<?php

namespace Reach\Mongo\Query;

use Reach\Mongo\Query;

/**
 * Class Geo
 * @link    http://docs.mongodb.org/manual/reference/operator/query-geospatial/
 * @package Reach\Mongo\Query
 */
class Geo extends Query
{

    const ATTR_LOCATION = 'loc';

    /**
     * @param float  $x
     * @param float  $y
     * @param string $attribute
     * @param bool   $is_sphere
     * @return $this
     */
    public function near($x, $y, $attribute = self::ATTR_LOCATION, $is_sphere = false)
    {
        return $this;
    }

    /**
     * @param        $x1
     * @param        $y1
     * @param        $x2
     * @param        $y2
     * @param string $attribute
     * @return $this
     */
    public function withinBox($x1, $y1, $x2, $y2, $attribute = self::ATTR_LOCATION)
    {
        return $this;
    }

    /**
     * @param array  $polygon [[$x1, $y1],
     *                        [$x1, $y2],
     *                        [$x2, $y2],
     *                        [$x2, $y1]]
     * @param string $attribute
     * @param bool   $is_sphere
     * @return $this
     */
    public function withinPolygon(array $polygon, $attribute = self::ATTR_LOCATION, $is_sphere = false)
    {
        return $this;
    }

    public function withinCenter($x1, $y1, $r)
    {
        return $this;
    }

    public function withinCenterSphere($x1, $y1, $r)
    {
        return $this;
    }
}
