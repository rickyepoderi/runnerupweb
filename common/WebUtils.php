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
use runnerupweb\data\User;
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
            Logging::warning("'$name' parameter is compulsory");
            exit(json_encode(LoginResponse::responseKo($errorCode, "runnerupweb.date.missing"), JSON_PRETTY_PRINT));
        }
        if ($stringVar) {
            $dateVar = \DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $stringVar, new \DateTimeZone('UTC'));
            if (!$dateVar) {
                Logging::warning("'$name' format should be in XML datetime 'Y-m-dTH:i:sZ'");
                exit(json_encode(LoginResponse::responseKo($errorCode, "runnerupweb.date.invalid"), JSON_PRETTY_PRINT));
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
                    exit(json_encode(LoginResponse::responseKo($errorCode, "runnerupweb.invalid.operation"), JSON_PRETTY_PRINT));
            }
        } else {
            if ($compulsory) {
                Logging::warning("'$name' parameter is compulsory");
                exit(json_encode(LoginResponse::responseKo($errorCode, "runnerupweb.operation.missing"), JSON_PRETTY_PRINT));
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
            Logging::warning("'$name' should be an integer between $min and $max");
            exit(json_encode(LoginResponse::responseKo($errorCode, 
                    "runnerupweb.int.invalid"), JSON_PRETTY_PRINT));
        } else if ($var == null && $compulsory) {
            Logging::warning("'$name' parameter is compulsory");
            exit(json_encode(LoginResponse::responseKo($errorCode, 
                    "runnerupweb.int.missing"), JSON_PRETTY_PRINT));
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
            Logging::warning("'$name' does not comply regular expression $regex");
            exit(json_encode(LoginResponse::responseKo($errorCode, 
                    "runnerupweb.regexp.invalid"), JSON_PRETTY_PRINT));
        } else if ($var === null && $compulsory) {
            Logging::warning("'$name' parameter is compulsory");
            exit(json_encode(LoginResponse::responseKo($errorCode, 
                    "runnerupweb.regexp.missing"), JSON_PRETTY_PRINT));
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

    private static function getString($name, $compulsory, $errorCode = 1, $maxLength = 128) {
        $var = filter_input(INPUT_GET, $name);
        if ((is_null($var) || $var === '') && $compulsory) {
            Logging::warning("'$name' parameter is compulsory");
            exit(json_encode(LoginResponse::responseKo($errorCode,
                    "runnerupweb.string.missing"), JSON_PRETTY_PRINT));
        } else if ((is_null($var) || $var === '') && !$compulsory) {
            return null;
        } else if (strlen($var) > $maxLength) {
            Logging::warning("'$name' parameter is too long");
            exit(json_encode(LoginResponse::responseKo($errorCode,
                    "runnerupweb.string.long"), JSON_PRETTY_PRINT));
        } else {
            return $var;
        }
    }

    /**
     * Method that checks if a string parameter is sent.
     * @param type $name The name of the parameter
     * @param type $errorCode The error code
     * @param type $maxLength Max length of the string
     */
    public static function getCompulsoryString($name, $errorCode = 1, $maxLength = 128) {
        return WebUtils::getString($name, true, $errorCode, $maxLength);
    }

    /**
     * Method that checks if a string parameter is sent.
     * @param type $name
     * @param type $errorCode
     * @param type $maxLength
     * @return type
     */
    public static function getOptionalString($name, $errorCode = 1, $maxLength = 128) {
        return WebUtils::getString($name, false, $errorCode, $maxLength);
    }

    private static function getStringInValues($name, $values, $compulsory, $errorCode = 1) {
        $var = filter_input(INPUT_GET, $name);
        if (is_null($var) && $compulsory) {
            Logging::warning("'$name' parameter is compulsory");
            exit(json_encode(LoginResponse::responseKo($errorCode,
                    "runnerupweb.string.missing"), JSON_PRETTY_PRINT));
        } else if (is_null($var) && !$compulsory) {
            return null;
        } else if (!in_array($var, $values, true)) {
            Logging::warning("'$name' parameter not values " . implode(" ", $values));
            exit(json_encode(LoginResponse::responseKo($errorCode,
                    "runnerupweb.string.not.in.values"), JSON_PRETTY_PRINT));
        } else {
            return $var;
        }
    }

    /**
     * Method that checks if a string parameter is sent.
     * @param type $name The name of the parameter
     * @param type $values The correct values
     * @param type $errorCode The error code
     */
    public static function getCompulsoryStringInValues($name, $values, $errorCode = 1) {
        return  WebUtils::getStringInValues($name, $values, true, $errorCode);
    }

    /**
     *
     * @param type $name
     * @param type $errorCode
     * @return type
     */
    public static function getBooleanDefaultFalse($name, $errorCode = 1) {
        return filter_var(WebUtils::getOptionalString($name), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Check the session is OK and the user is logged in.
     * 
     * @param bool|null $addJsonHeader
     * @return User|null
     */
    public static function checkUserSession(string $validMethod, ?bool $addJsonHeader = true): ?User {
        // add a header protection for everything
        header("Content-Security-Policy: default-src 'self'");
        // check the method is OK
        if ($validMethod !== $_SERVER['REQUEST_METHOD']) {
            header(filter_input(INPUT_SERVER, 'SERVER_PROTOCOL') . ' 405 Method Not Allowed');
            exit;
        }
        // init the configuration
        $config = Configuration::getConfiguration();
        // start the session
        session_start();
        if (session_status() != PHP_SESSION_ACTIVE || !array_key_exists('login', $_SESSION) || !($_SESSION['login'] instanceof User) || $_SESSION['login']->getLogin() == null) {
            // user not authenticated => 403
            Logging::debug("User not authenticated!");
            header(filter_input(INPUT_SERVER, 'SERVER_PROTOCOL') . ' 403 Forbidden');
            exit;
        } else {
            // check activity
            if (!isset($_SESSION['LAST_ACTIVITY']) || (time() - $_SESSION['LAST_ACTIVITY'] > $config->getProperty('web', 'session.timeout'))) {
                Logging::debug("Session expired for " . $_SESSION['login']->getLogin());
                session_unset();     // unset $_SESSION variable for the run-time
                session_destroy();   // destroy session data in storage
                header(filter_input(INPUT_SERVER, 'SERVER_PROTOCOL') . ' 403 Forbidden');
                exit;
            } else {
                // refresh and put a variable with the user
                $_SESSION['LAST_ACTIVITY'] = time(); // update last activity time stamp
                if ($addJsonHeader) {
                    header('Content-type: application/json');
                }
                return $_SESSION['login'];
            }
        }
    }

    /**
     * Check the session is OK for an admin.
     * @param string $validMethod
     * @param bool|null $addJsonHeader
     * @return User|null
     */
    public static function checkAdminSession(string $validMethod, ?bool $addJsonHeader = true): ?User {
        $user = WebUtils::checkUserSession($validMethod);
        if ($user->getRole() !== 'ADMIN') {
            // user not admin => 403
            Logging::debug("User is not an admin!");
            header(filter_input(INPUT_SERVER, 'SERVER_PROTOCOL') . ' 403 Forbidden');
            exit;
        }
        return $user;
    }

}
