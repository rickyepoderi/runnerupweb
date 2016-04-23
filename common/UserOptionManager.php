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

use runnerupweb\common\Configuration;
use runnerupweb\common\DataBase;
use runnerupweb\common\Logging;
use runnerupweb\data\UserOption;

/**
 * The USER_OPTION table contains the options for user (preferred distance
 * unit, map type,...). The idea is that the options are generic strings
 * with the following format: "unit.distance", "map.type",... The number
 * of words is free and the values is a plain text.
 * 
 * CREATE TABLE `user_option` (
 *   `login` varchar(64) NOT NULL,
 *   `name` varchar(128) NOT NULL,
 *   `value` varchar(256) NOT NULL,
 *   PRIMARY KEY (`login`, `name`),
 *   KEY `login_idx` (`login`),
 *   CONSTRAINT `user_option_login_fgk` FOREIGN KEY (`login`) REFERENCES `user` (`login`) ON DELETE CASCADE
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
 *
 * @author ricky
 */
class UserOptionManager extends DataBase {
    
    static private $userOptionManager;
    
    protected function __construct($url, $username, $password, $maxrows) {
        parent::__construct($url, $username, $password, $maxrows);
    }
    
    /**
     * Initializer for the singleton.
     * @param type $url The url to connect to the ddbb
     * @param type $username The username 
     * @param type $password The password
     * @param type $maxrows The maximum number of rows to return
     * @return UserOptionManager The singleton
     */
    static public function initUserOptionManager($url, $username, $password, $maxrows) {
        static::$userOptionManager = new UserOptionManager($url, $username, $password, $maxrows);
        return static::getUserOptionManager();
    }
    
    /**
     * 
     * @return UserOptionManager The singleton
     */
    static public function getUserOptionManager() {
        return static::$userOptionManager;
    }
    
    /**
     * @return UserOption The all user options but the value is the
     *                    javascript input to show in the page
     */
    public function getDefinitions() {
        $config = Configuration::getConfiguration();
        $opts = new UserOption();
        foreach ($config->getSection("options") as $name => $value) {
            $opts->set($name, $value);
        }
        return $opts;
    }
    
    /**
     * Method that checks the options exist in the definitions.
     * @param UserOption $opts The options to check 
     * @return bool true if valid, false if not
     */
    public function check($opts) {
        $defs = $this->getDefinitions();
        foreach ($opts->flat() as $key => $value) {
            if ($defs->get($key) === null) {
                Logging::debug("Option not valid: " . $key);
                return false;
            }
        }
        return true;
    }
    
    /**
     * Function that gets the options for a user.
     * 
     * @param string $login The username to get options for
     * @return UserOption The User options for this user
     */
    public function get($login) {
        $db = $this->getConnection();
        try {
            $stmt = $db->prepare("SELECT name, value FROM user_option WHERE login = ?");
            $stmt->execute([$login]);
            $opts = new UserOption();
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            while ($row != null) {
                $opts->set($row['name'], $row['value']);
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            }
            $db->commit();
            return $opts;
        } catch (Exception $ex) {
            $db->rollback();
            throw $ex;
        }
    }
    
    /**
     * Function that stores the options of a user.
     * @param string $login The user to set options
     * @param UserOption $opts The options to set
     */
    public function set($login, $opts) {
        $db = $this->getConnection();
        try {
            // cprepare the three possible operations
            $insert = $db->prepare("INSERT INTO user_option(login, name, value) values(?, ?, ?)");
            $update = $db->prepare("UPDATE user_option SET value = ? WHERE login = ? AND name = ?");
            $delete = $db->prepare("DELETE FROM user_option WHERE login = ? AND name = ?");
            // flat the new options
            $new = $opts->flat();
            // read the old options from ddbb
            $old = [];
            $stmt = $db->prepare("SELECT name, value FROM user_option WHERE login = ?");
            $stmt->execute([$login]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            while ($row != null) {
                $old[$row['name']] = $row['value'];
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            }
            // compare the new to the old to create / update
            foreach ($new as $k => $v) {
                if (!array_key_exists($k, $old)) {
                    // insert the new value into the table
                    Logging::debug("Inserting $k => $v \n");
                    $insert->execute([$login, $k, $v]);
                } elseif ($v !== $old[$k]) {
                    // exists but is different
                    Logging::debug("Updating $k => $v \n");
                    $update->execute([$v, $login, $k]);
                }
            }
            // compare old to new to perform deletes
            foreach ($old as $k => $v) {
                if (!array_key_exists($k, $new)) {
                    Logging::debug("Deleting $k => $v \n");
                    $delete->execute([$login, $k]);
                }
            }
            $db->commit();
        } catch (Exception $ex) {
            $db->rollback();
            throw $ex;
        }
    }
    
}
