<?php

/*
 * Copyright (C) 2019 ricky <https://github.com/rickyepoderi/runnerupweb>
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

namespace runnerupweb\common;

use runnerupweb\common\Logging;
use runnerupweb\common\UserManager;
use runnerupweb\data\User;

abstract class VersionUpdate {
    
    protected $version;
    
    function __construct($version) {
        $this->version = $version;
    }
    
    /**
     * Getter for the version of the update
     * @return version
     */
    function getVersion() {
        return $this->version;
    }
    
    /**
     * Applies the changes over the database for the version.
     */
    abstract function applyUpdate($db);
}

class VersionUpdate0_1_0 extends VersionUpdate {
    
    function __construct($version) {
        parent::__construct($version);
    }
    
    function applyUpdate($db) {
        try {
            // create user table
            $stmt = $db->prepare("CREATE TABLE user (\n" .
                "login varchar(64) NOT NULL,\n" .
                "password varchar(255) NOT NULL,\n" .
                "firstname varchar(100) DEFAULT NULL,\n" .
                "lastname varchar(100) DEFAULT NULL,\n" .
                "email varchar(100) DEFAULT NULL,\n" .
                "role enum('USER','ADMIN') NOT NULL,\n" .
                "PRIMARY KEY (login))");
            $stmt->execute();
            // create activity table
            $stmt = $db->prepare("CREATE TABLE activity (\n" .
                "id bigint(20) NOT NULL AUTO_INCREMENT,\n" .
                "login varchar(64) NOT NULL,\n" .
                "startTime timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n" .
                "sport varchar(50) NOT NULL,\n" .
                "totalTimeSeconds double NOT NULL,\n" .
                "distanceMeters double NOT NULL,\n" .
                "maximumSpeed double DEFAULT NULL,\n" .
                "calories smallint(6) DEFAULT NULL,\n" .
                "averageHeartRateBpm smallint(6) DEFAULT NULL,\n" .
                 "maximumHeartRateBpm smallint(6) DEFAULT NULL,\n" .
                "notes varchar(2048) DEFAULT NULL,\n" .
                 "filename varchar(512) NOT NULL,\n" .
                "PRIMARY KEY (id, login),\n" .
                "KEY login_idx (login),\n" .
                "CONSTRAINT activity_login_fgk FOREIGN KEY (login) REFERENCES user (login) ON DELETE CASCADE)");
            $stmt->execute();
            // create table for options
            $stmt = $db->prepare("CREATE TABLE user_option (\n" .
                "login varchar(64) NOT NULL,\n" .
                "name varchar(128) NOT NULL,\n" .
                "value varchar(256) NOT NULL,\n" .
                "PRIMARY KEY (login, name),\n" .
                "KEY login_idx (login),\n" .
                "CONSTRAINT user_option_login_fgk FOREIGN KEY (login) REFERENCES user (login) ON DELETE CASCADE)");
            $stmt->execute();
            // create the version and insert the value
            $stmt = $db->prepare("CREATE TABLE version (\n" .
                "version varchar(50) NOT NULL,\n" .
                "applyTime timestamp NOT NULL DEFAULT current_timestamp(),\n" .
                "PRIMARY KEY (version))");
            $stmt->execute();
            $stmt = $db->prepare("INSERT INTO version(version) values(?)");
            $stmt->execute([$this->version]);
            // create admin user
            $admin = User::userWithLogin('admin');
            $admin->setPassword('admin');
            $admin->setRole(User::ADMIN_ROLE);
            $um = UserManager::getUserManager();
            $um->createUser($admin);
            // commit
            $db->commit();
        } catch (Exception $ex) {
            $db->rollback();
            throw $ex;
        }
    }
}

class VersionUpdate0_2_0 extends VersionUpdate {

    function __construct($version) {
        parent::__construct($version);
    }

    function applyUpdate($db) {
        try {
            // create tag_config table
            $stmt = $db->prepare("CREATE TABLE tag_config (\n" .
                "tag varchar(128) NOT NULL,\n" .
                "login varchar(128) NOT NULL,\n" .
                "config varchar(2048) DEFAULT NULL,\n" .
                "provider varchar(128) DEFAULT NULL,\n" .
                "description varchar(2048) DEFAULT NULL,\n" .
                "PRIMARY KEY (tag, login),\n" .
                "KEY tag_config_login_fgk (login),\n" .
                "CONSTRAINT tag_config_login_fgk FOREIGN KEY (login) REFERENCES user (login) ON DELETE CASCADE)");
            $stmt->execute();
            // create tag table
            $stmt = $db->prepare("CREATE TABLE tag (\n" .
                "tag varchar(128) NOT NULL,\n" .
                "id bigint(20) NOT NULL,\n" .
                "login varchar(128) NOT NULL,\n" .
                "PRIMARY KEY (tag, id, login),\n" .
                "KEY tag_activity_fgk (id),\n" .
                "KEY tag_login_fgk (login),\n" .
                "CONSTRAINT tag_activity_fgk FOREIGN KEY (id) REFERENCES activity (id) ON DELETE CASCADE,\n" .
                "CONSTRAINT tag_login_fgk FOREIGN KEY (login) REFERENCES user (login) ON DELETE CASCADE,\n" .
                "CONSTRAINT tag_tag_config_fgk FOREIGN KEY (tag) REFERENCES tag_config (tag) ON DELETE CASCADE)");
            $stmt->execute();
            // upate the version
            $stmt = $db->prepare("INSERT INTO version(version) values(?)");
            $stmt->execute([$this->version]);
            // commit
            $db->commit();
        } catch (Exception $ex) {
            $db->rollback();
            throw $ex;
        }
    }
}

/**
 * CREATE TABLE version (
 *   version varchar(50) NOT NULL,
 *   applyTime timestamp NOT NULL DEFAULT current_timestamp(),
 *   PRIMARY KEY (version)
 * );
 */
class VersionManager extends DataBase {

    static private $versionManager;
    static public $versions;
    
    protected function __construct($url, $username, $password, $maxrows) {
        parent::__construct($url, $username, $password, $maxrows);
    }
    
    /**
     * Initializer for the singleton.
     * @param string $url The URL to the database
     * @param string $username The username to connect to the ddbb
     * @param string $password The password of the user
     * @param int $maxrows Maximum rows to select
     * @return VersionManager The singleton
     */
    static public function initVersionManager($url, $username, $password, $maxrows) {
        static::$versionManager = new VersionManager($url, $username, $password, $maxrows);
        return static::getVersionManager();
    }
    
    /**
     * Getter for the singleton.
     * @return VersionManager
     */
    static public function getVersionManager() {
        return static::$versionManager;
    }
    
    /**
     * Returns the version in the database (format "x.y.z").
     * @return string The version in string format x.y.z
     * @throws \runnerupweb\common\Exception
     */
    public function getVersion() {
        $db = $this->getConnection();
        try {
            $stmt = $db->prepare("SELECT version, applyTime FROM version ORDER BY version DESC LIMIT ?");
            $stmt->execute([1]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            $version = null;
            if ($row != null) {
                $version = $row['version'];
            }
            $db->commit();
            return $version;
        } catch (Exception $ex) {
            $db->rollback();
            throw $ex;
        }
    }
    
    /**
     * Check if upgrade is needed comparing versions (formar x.y.z).
     * @param string $currentVersion The current version in database
     * @param string $lastVersion The last version in the upgrade array
     * @return bool true if current < last
     */
    public function upgradeNeededForVersions($currentVersion, $lastVersion) {
        $currentInfo = explode(".", $currentVersion);
        $lastInfo = explode(".", $lastVersion);
        return intval($currentInfo[0]) < intval($lastInfo[0]) ||
                intval($currentInfo[1]) < intval($lastInfo[1]) ||
                intval($currentInfo[2]) < intval($lastInfo[2]);
    }
    
    /**
     * Returns the last version of the application
     * @return string The last version
     */
    public function getLastVersion() {
        $lastKey = array_keys(VersionManager::$versions)[count(VersionManager::$versions) - 1];
        return VersionManager::$versions[$lastKey]->getVersion();
    }
    
    /**
     * Returns if the current database needs an upgrade
     * @return bool true if needed
     */
    public function upgradeNeeded() {
        $currentVersion = $this->getVersion();
        $lastVersion = $this->getLastVersion();
        return $this->upgradeNeededForVersions($currentVersion, $lastVersion);
    }
    
    /**
     * Uprades from that version.
     * @param string $version The initial version to upgrade
     */
    private function upgradeFromVersion($version) {
        foreach (VersionManager::$versions as $newVersion => $upgrade) {
            if ($this->upgradeNeededForVersions($version, $newVersion)) {
                $db = $this->getConnection();
                Logging::info("Upgrading version to $newVersion");
                $upgrade->applyUpdate($db);
            }
        } 
    }
    
    /**
     * Initializes the database to be used for runnerupweb
     * @throws Exception Some error
     */
    public function init() {
        // check if version table exists
        try {
            $version = $this->getVersion();
        } catch (\PDOException $ex) {
            Logging::debug("Version could not be loaded: " . $ex->getMessage());
            $version = "0.0.0";
        }
        if ($version != "0.0.0") {
            throw new \Exception("Database seems to be already initialized with version $version");
        }
        $this->upgradeFromVersion($version);
    }
    
    /**
     * Performs an upgrade from the current version
     * @throws Exception Some error
     */
    public function upgrade() {
        $version = $this->getVersion();
        if (!$this->upgradeNeeded()) {
           throw new \Exception("Upgrade not needed, version at last level $version"); 
        }
        $this->upgradeFromVersion($version);
    }
}

VersionManager::$versions = array(
  "0.1.0" => new VersionUpdate0_1_0("0.1.0"),
  "0.2.0" => new VersionUpdate0_2_0("0.2.0"),
  // more version with changes here
);