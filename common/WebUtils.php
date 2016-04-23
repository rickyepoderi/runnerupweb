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

namespace runnerupweb\common;

use runnerupweb\data\LoginResponse;
use runnerupweb\common\Logging;
use runnerupweb\common\UserManager;

/**
 * Utility class to read parameters in the web services.
 *
 * @author ricky
 */
class WebUtils {
    
    private static function getDateTime($name, $compulsory, $errorCode) {
        $stringVar = filter_input(INPUT_GET, $name);
        Logging::debug("Checking $name with $stringVar");
        if (!$stringVar && $compulsory) {
            exit(json_encode(LoginResponse::responseKo($errorCode, "'$name' parameter is compulsory"), JSON_PRETTY_PRINT));
        }
        if ($stringVar) {
            $dateVar = \DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $stringVar, new \DateTimeZone('UTC'));
            if (!$dateVar) {
                exit(json_encode(LoginResponse::responseKo($errorCode, "'$name' format should be in XML datetime 'Y-m-dTH:i:sZ'"), JSON_PRETTY_PRINT));
            }
            return $dateVar;
        } else {
            return null;
        }
    }
    
    /**
     * Method that parses a compulsory datetime parameter.
     * @param type $name The name of the parameter
     * @param type $errorCode The error code to return if error
     * @return DateTime The datetime read
     */
    public static function getCompulsoryDateTime($name, $errorCode = 1) {
        return WebUtils::getDateTime($name, true, $errorCode);
    }
    
    /**
     * Method that parses a optional datetime parameter.
     * @param type $name The name of the parameter
     * @param type $errorCode The error code to return if error
     * @return DateTime The datetime read
     */
    public static function getOptionalDateTime($name, $errorCode = 1) {
        return WebUtils::getDateTime($name, false, $errorCode);
    }
    
    private static function getOperation($name, $compulsory, $errorCode = 1) {
        $opStr = filter_input(INPUT_GET, $name);
        if ($opStr) {
            switch (strtoupper($opStr)) {
                case 'OP_STARTS_WITH':
                    return UserManager::OP_STARTS_WITH;
                case 'OP_ENDS_WITH':
                    return UserManager::OP_ENDS_WITH;
                case 'OP_CONTAINS':
                    return UserManager::OP_CONTAINS;
                case 'OP_EQUALS':
                    return UserManager::OP_EQUALS;
                default:
                    exit(json_encode(LoginResponse::responseKo($errorCode, "'$name' format is not a valid operation"), JSON_PRETTY_PRINT));
            }
        } else {
            if ($compulsory) {
                exit(json_encode(LoginResponse::responseKo($errorCode, "'$name' parameter is compulsory"), JSON_PRETTY_PRINT));
            } else {
                return -1;
            }
        }
    }
    
    /**
     * Method that retrieves a compulsory operation parameter.
     * @param type $name The name of the parameter
     * @param type $errorCode The error code to return in case of invalid operation
     * @return int The operation index
     */
    public static function getCompulsoryOperation($name, $errorCode = 1) {
        return WebUtils::getOperation($name, true, $errorCode);
    }
    
    /**
     * Method that retrieves an optional operation parameter.
     * @param type $name The name of the parameter
     * @param type $errorCode The error code to return in case of invalid operation
     * @return int The operation index
     */
    public static function getOptionalOperation($name, $errorCode = 1) {
        return WebUtils::getOperation($name, false, $errorCode);
    }
    
    private static function getInt($name, $compulsory, $min, $max, $errorCode) {
        $var = filter_input(INPUT_GET, $name, FILTER_VALIDATE_INT, 
                array("options" => array("min_range" => $min, "max_range" => $max)));
        if ($var === false) {
            exit(json_encode(LoginResponse::responseKo($errorCode, 
                    "'$name' should be an integer between $min and $max"), JSON_PRETTY_PRINT));
        } else if ($var == null && $compulsory) {
            exit(json_encode(LoginResponse::responseKo($errorCode, 
                    "'$name' parameter is compulsory"), JSON_PRETTY_PRINT));
        } else {
            return $var;
        }
    }
    
    /**
     * Method that retrieves a compulsory integer parameter.
     * @param type $name The name of the parameter
     * @param type $errorCode The error to return
     * @param type $min A minimum value
     * @param type $max A maximum value
     * @return int The integer of the parameter
     */
    public static function getCompulsoryInt($name, $errorCode = 1, $min = 1 - PHP_INT_MAX, $max = PHP_INT_MAX) {
        return WebUtils::getInt($name, true, $min, $max, $errorCode);
    }
    
    /**
     * Method that retrieves an optional integer parameter.
     * @param type $name The name of the parameter
     * @param type $errorCode The error to return
     * @param type $min A minimum value
     * @param type $max A maximum value
     * @return int The integer of the parameter
     */
    public static function getOptionalInt($name, $errorCode = 1, $min = 1 - PHP_INT_MAX, $max = PHP_INT_MAX) {
        return WebUtils::getInt($name, false, $min, $max, $errorCode);
    }
    
    private static function getRegExp($name, $regex, $compulsory, $errorCode) {
        $var = filter_input(INPUT_GET, $name, FILTER_VALIDATE_REGEXP, 
                array("options" => array("regexp" => $regex)));
        Logging::debug("Return regexp: ", [$var]);
        if ($var === false) {
            exit(json_encode(LoginResponse::responseKo($errorCode, 
                    "'$name' does not comply regular expression $regex"), JSON_PRETTY_PRINT));
        } else if ($var === null && $compulsory) {
            exit(json_encode(LoginResponse::responseKo($errorCode, 
                    "'$name' parameter is compulsory"), JSON_PRETTY_PRINT));
        } else {
            return $var;
        }
    }
    
    /**
     * Method that retrieves a compulsory login.
     * @param type $name The name of the parameter
     * @param type $errorCode The error code to return in case of invalid login
     * @return string The login
     */
    public static function getCompulsoryLogin($name, $errorCode = 1) {
        return WebUtils::getRegExp($name, '/^[a-zA-Z0-9_\-\.]+$/', true, $errorCode);
    }
    
    /**
     * Method that retrieves an optional login.
     * @param type $name The name of the parameter
     * @param type $errorCode The error code to return in case of invalid login
     * @return string The login
     */
    public static function getOptionalLogin($name, $errorCode = 1) {
        return WebUtils::getRegExp($name, '/^[a-zA-Z0-9_\-\.]*$/', false, $errorCode);
    }
}
