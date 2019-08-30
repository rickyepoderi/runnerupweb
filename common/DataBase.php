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

/**
 * Simple class that handles connection against the database. The idea
 * is all managers that interact 
 * 
 * @author ricky
 */
abstract class DataBase {
    
    protected $url;
    protected $username;
    protected $password;
    protected $maxrows;
    
    protected function __construct(string $url, string $username, string $password, int $maxrows) {
        $this->url = $url;
        $this->username = $username;
        $this->password = $password;
        $this->maxrows = $maxrows;
    }
    
    protected function getConnection() {
        $db = new \PDO($this->url, $this->username, $this->password, array(\PDO::MYSQL_ATTR_FOUND_ROWS => true));
        $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
        $db->setAttribute(\PDO::ATTR_PERSISTENT, true);
        $db->setAttribute(\PDO::ATTR_AUTOCOMMIT, false);
        $db->beginTransaction();
        return $db;
    }
    
    /**
     * Getter for the max number of rows.
     * 
     * @return int the number of rows to retrieve at maximum
     */
    public function getMaxRows(): int {
        return $this->maxrows;
    }
    
}
