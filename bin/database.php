<?php

/* 
 * Copyright (C) 2019 <https://github.com/rickyepoderi/runnerupweb>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require __DIR__ . '/../bootstrap.php';

use runnerupweb\common\Configuration;
use runnerupweb\common\VersionManager;

class DatabaseCommand {
    
    public function __construct() {
        // initialize the configuration
        Configuration::getConfiguration();
    }
    
    public function usage($error) {
        fwrite(STDERR, "$error\n");
        fwrite(STDERR, "USAGE: database.php OPERATION\n");
        fwrite(STDERR, "OPERATION:\n");
        fwrite(STDERR, " - status: checks the status of the database\n");
        fwrite(STDERR, " - init: Initializes the database to current version\n");
        fwrite(STDERR, " - upgrade: Upggrades the database to current version\n");
        die(1);
    }

    public function execute($argv) {
        if (count($argv) != 2) {
            $this->usage('Invalid arguments');
        }
        $vm = VersionManager::getVersionManager();
        switch ($argv[1]) {
            case 'status':
                $version = $vm->getVersion();
                $lastVersion = $vm->getLastVersion();
                print("Database version: $version\n");
                print("Application version: $lastVersion\n");
                print($vm->upgradeNeeded()? "Upgrade needed\n" : "Correct version\n");
                break;
            case 'init':
                $vm->init();
                print("Initialization done\n");
                break;
            case 'upgrade':
                $vm->upgrade();
                print("Upgrade done\n");
                break;
            default:
                $this->usage("Invalid operation argument: $argv[1]");
        }
    }
}

$database = new DatabaseCommand();
$database->execute($argv);
