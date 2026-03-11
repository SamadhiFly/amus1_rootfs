<?php

namespace App\Utils\Widgets;

/**
 * arrays utils
 *
 * Class Arrays
 * @package App\Utils\Widgets
 */
class Arrays
{
    /**
     * @param mixed<string|array> $data
     * @param boolean $ucfirst
     * @return mixed<string|array>
     */
    public static function convertUnderline($data , $ucfirst = false)
    {
        if (!is_string($data)) {
            $return = array();
            foreach ($data as $key => $str) {
                $key          = preg_replace_callback('/(([-_]+)([A-Za-z]{1}))/i', function ($matches) {
                    if ($matches[2] === '_') {
                        return strtoupper($matches[3]);
                    }
                    return $matches[0];
                }, $key);
                $key          = $ucfirst ? ucfirst($key) : $key;
                $return[$key] = $str;
            }
            return $return;
        } else {
            $data = preg_replace_callback('/(([-_]+)([A-Za-z]{1}))/i', function ($matches) {
                if ($matches[2] === '_') {
                    return strtoupper($matches[3]);
                }
                return $matches[0];
            }, $data);
            return $ucfirst ? ucfirst($data) : $data;
        }
    }

    /**
     * @param mixed<string|array> $data
     * @return mixed<string|array>
     */
    public static function convertModel($data)
    {
        $return = [];
        foreach ($data as $key => $str) {
            $ds = strtolower(preg_replace('/([a-z])([A-Z])/', "$1" . "_" . "$2", $key));
            $return[$ds] = $str;
        }
        return $return;
    }

    /**
     * @param object|array $array
     * @param string $field
     * @return array
     */
    public static function toFlat($array, $field = null)
    {
        $field = $field ?: 'id';
        $rs = array();
        foreach ($array as $t) {
            if(is_array($t)) {
                if(!isset($t[$field])) {
                    continue;
                } else {
                    $rs[] = $t[$field];
                }
            }else {
                if(!isset($t->$field)) {
                    continue;
                } else {
                    $rs[] = $t->$field;
                }
            }
        }
        return $rs;
    }

    /**
     * @param object|array $iterator
     * @param string $fieldKey array index
     * @param string $fieldVal array value
     * @param boolean $isArray
     * @return array
     */
    public static function toKV(&$iterator, $fieldKey = null, $fieldVal = null, $isArray = false)
    {
        $rs = array();
        foreach ($iterator as $t) {
            $k = is_array($t) ? $t[$fieldKey] : $t->$fieldKey;
            $v = is_array($t) ? $t[$fieldVal] : $t->$fieldVal;
            if ($isArray) {
                if (array_key_exists($k, $rs)) {
                    $rs[$k][] = $v;
                } else {
                    $rs[$k] = array($v);
                }
            } else {
                $rs[$k] = $v;
            }
        }
        return $rs;
    }

    /**
     * @param object|array $iterator
     * @param string $fieldKey
     * @param boolean $isArray
     * @return array
     */
    public static function toArrayByNewKey(&$iterator, $fieldKey = null, $isArray = false)
    {
        $rs = array();
        foreach ($iterator as $t) {
            $k = is_array($t) ? $t[$fieldKey] : $t->$fieldKey;
            if ($isArray) {
                if (array_key_exists($k, $rs)) {
                    $rs[$k][] = $t;
                } else {
                    $rs[$k] = array($t);
                }
            } else {
                $rs[$k] = $t;
            }
        }
        return $rs;
    }


    /**
     * @param $array
     * @return string
     */
    public static function toArrayByIndex($array)
    {
        $return = null;
        foreach ($array as $v) {
            if ($v) {
                $return = $v;
                break;
            }
        }
        return $return;
    }

    /**
     * @param $array
     * @return array
     */
    public static function toOnly($array)
    {
        $list = [];
        for ($i = 0; $i < count($array); $i++) {
            foreach ($array[$i] as $k => $v) {
                $list[$i] = $v;
            }
        }
        return $list;
    }

    /**
     * @param object $obj
     * @return array
     */
    public static function objectToArray($obj)
    {
        $_arr = is_object($obj) ? get_object_vars($obj) : $obj;
        foreach ($_arr as $key => $val) {
            $val = (is_array($val)) || is_object($val) ? object_to_array($val) : $val;
            $arr[$key] = $val;
        }
        return $arr;
    }


    /**
     * @param $metaArray
     * @param $isFiltrationZero
     * @return array
     */
    public static function regroupArray($metaArray,$isFiltrationZero = true)
    {
        $result = [];
        foreach ($metaArray as $field => $val){
            if($isFiltrationZero){
                if(!empty(trim($val))){
                    $result[$field] = $val;
                }
            }else{
                if(!empty(trim($val)) || $val !== ''){
                    $result[$field] = $val;
                }
            }
        }
        return $result;
    }


    /**
     * @param $metaArray
     * @param $field
     * @return array
     */
    public static function remDuplicateArray($metaArray,$field)
    {
        $result = [];
        foreach ($metaArray as $key => $val) {
            if(!isset($result[$val[$field]])){
                $result[$val[$field]] = $val;
            }else{
                unset($val[$field]);
            }
        }
        $result = array_values($result);
        return $result;
    }


    /**
     * @param $metaArray
     * @param $field
     * @param $name
     * @return array
     */
    public static function changeFieldArray($metaArray,$field,$name)
    {
        $i = 0;
        $result = [];
        foreach ($metaArray as $key => $val){
            $result[$i] = $val;
            $result[$i]["$name"] = $val[$field];
            unset($result[$i][$field]);
            $i++;
        }
        return $result;
    }


    /**
     * @param $metaArray
     * @param $field
     * @return array
     */
    public static function toUnset($metaArray,$field)
    {
        $result = [];
        foreach ($metaArray as $val){
            unset($val[$field]);
            $result = $val;
        }
        return $result;
    }


    /**
     *
     * @param $metaArray
     * @return array
     */
    public static function toArrayValuesTransitionInt($metaArray)
    {
        $result = [];
        foreach ($metaArray as $key => $val){
            $result[$key] = intval($val);
        }
        return $result;
    }


    /**
     * @param $metaArray
     * @return array
     */
    public static function countDuplicateValue($metaArray)
    {
        $return = [];
        foreach ($metaArray as $v) {
            $return[$v] = isset($return[$v])?($return[$v]+1):1;
        }
        return $return;
    }


    /**
     * @param $metaArray
     * @return array
     */
    public static function duplicateValueGrouping($metaArray)
    {
        $return = [];
        foreach ($metaArray as $v) {
            if(isset($return[$v])) {
                array_push($return[$v],$v);
            } else {
                array_push($return[$v],$v);
            }
        }
        return $return;
    }


    /**
     * @param array $array
     * @return array
     */
    public static function flipByKeys(array $array)
    {
        $result = array_flip($array);
        $return = array_keys($result);
        return $return;
    }

}
