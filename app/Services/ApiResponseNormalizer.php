<?php

namespace App\Services;

class ApiResponseNormalizer
{
    // Recursively convert associative arrays to objects and list arrays to collections
    public static function normalize($data)
    {
        if (is_null($data)) return null;

        if (is_array($data)) {
            // associative or sequential?
            if (self::isList($data)) {
                return collect(array_map(function ($item) {
                    return self::normalize($item);
                }, $data));
            }

            $obj = new \stdClass();
            foreach ($data as $k => $v) {
                $obj->{$k} = self::normalize($v);
            }
            return $obj;
        }

        return $data;
    }

    protected static function isList(array $arr)
    {
        if (empty($arr)) return false;
        $keys = array_keys($arr);
        return $keys === array_keys($keys);
    }
}
