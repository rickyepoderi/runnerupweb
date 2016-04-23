<?php

/*
 * Copyright (c) 2016 ricky <https://github.com/rickyepoderi/runnerupweb>
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace runnerupweb\data;

/**
 * Class that represents the user options in the application. All the user
 * options are managed as an array of arrays until the value is reached.
 * The options are accessed using pointed names (section1.section2.option1)
 * that represents one level in the array hierarchy. The option map can be 
 * flatted to array of names.
 *
 * @author ricky
 */
class UserOption implements \JsonSerializable {
    
    private $map;
    
    public function __construct() {
        $this->map = [];
    }
    
    /**
     * Static method to construct the UserOption with json.
     * @param string $json
     * @return UserOption
     */
    public static function userOptionWithJson($json) {
        return UserOption::userOptionWithAssoc(json_decode($json, true));
    }
    
    /**
     * Static method that constructs the UserOption with the assoc value.
     * @param mixed $assoc
     * @return UserOption
     */
    public static function userOptionWithAssoc($assoc) {
        $opts = new UserOption();
        $opts->map = $assoc;
        return $opts;
    }
    
    /**
     * Return the map of options.
     * @return array
     */
    public function getMap() {
        return $this->map;
    }
    
    /**
     * Get a option using the property name.
     * @param string $name The name separated by points
     * @return string|null
     */
    public function get($name) {
        $idxs = explode('.', $name);
        $arr = &$this->map;
        $value = null;
        foreach ($idxs as $k => $v) {
            if (array_key_exists($v, $arr)) {
                // the part already exists in the map
                if ($k === count($idxs) - 1) {
                    $value = $arr[$v];
                } else {
                    $arr = &$arr[$v];
                }
            } else {
                return null;
            }
        }
        if (is_scalar($value)) {
            return $value;
        } else {
            return null;
        }
    }
    
    /**
     * Remove an entry from the options map.
     * @param string $name The name separated by points
     * @return void
     */
    public function remove($name) {
        $idxs = explode('.', $name);
        $arr = &$this->map;
        foreach ($idxs as $k => $v) {
            if (array_key_exists($v, $arr)) {
                // the part already exists in the map
                if ($k === count($idxs) - 1) {
                    unset($arr[$v]);
                } else {
                    $arr = &$arr[$v];
                }
            } else {
                return;
            }
        }
    }
    
    /**
     * Set a new property to the map options
     * @param string $name The name using points
     * @param string $value The new value
     * @return void
     */
    public function set($name, $value) {
        $idxs = explode('.', $name);
        $arr = &$this->map;
        foreach ($idxs as $k => $v) {
            if (array_key_exists($v, $arr)) {
                // the part already exists in the map
                if ($k === count($idxs) - 1) {
                    $arr[$v] = $value;
                } else {
                    $arr = &$arr[$v];
                }
            } else {
                // the part does not exists in the map
                if ($k === count($idxs) - 1) {
                    // create the last part
                    $arr[$v] = $value;
                } else {
                    $arr[$v] = [];
                    $arr = &$arr[$v];
                }
            }
        }
    }
    
    
    /**
     * Check if the options map contains a name.
     * @param string $name The name with points
     * @return boolean true if the name is in the map
     */
    public function contains($name) {
        return $this->get($name) == null;
    }
    
    private function internal_keys(&$array, $prefix, &$result) {
        foreach ($array as $k => $v) {
            if (is_scalar($v)) {
                $result[$prefix . (($prefix === '')? '':'.') . $k] = $v;
            } else {
                $this->internal_keys($v, $prefix . (($prefix === '')? '':'.') . $k, $result);
            }
        }
    }
    
    /**
     * Flat the options map to array of pointed names.
     * @return array The array of keys the map contains in flat format (with dots)
     */
    public function flat() {
        $result = [];
        $this->internal_keys($this->map, '', $result);
        return $result;
    }
    
    /**
     * 
     * @return mixed
     */
    public function jsonSerialize() {
        return $this->map;
    }

}
