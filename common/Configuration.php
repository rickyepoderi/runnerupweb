<?php

/*
 * Copyright (c) 2016 <https://github.com/rickyepoderi/runnerupweb>
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
use runnerupweb\common\Logging;

/**
 * Class for the configuration file of the application.
 *
 * @author ricky
 */
class Configuration {
    
    /**
     *
     * @var Singleton variable
     */
    static private $configuration;
    
    private $config;
    
    /**
     * Getter for the configuration singleton.
     * 
     * @return Configuration The configuration sigleton
     */
    static public function getConfiguration() {
        if (static::$configuration == null) {
            static::$configuration = new Configuration();
        }
        return static::$configuration;
    }
    
    /**
     * Getter for a specific property in the config file.
     * 
     * @param string $section The section of the property
     * @param string $property The property inthe section
     * @return mixed The value of the section and property specified
     */
    public function getProperty($section, $property) {
        return $this->config[$section][$property];
    }
    
    /**
     * Getter for the whole section of the config file.
     * 
     * @param string $section The section to retrieve
     * @return array The array of properties in the section
     */
    public function getSection($section) {
        return $this->config[$section];
    }
    
    /**
     * Getter for the config property as read in the file.
     * 
     * @return array The complete configuration (parse_ini_file)
     */
    public function getConfig() {
        return $this->config;
    }
    
    private function __construct() {
        $this->config = parse_ini_file(__DIR__ . "/config.ini", true);
        // init all the singletons in the application
        Logging::initLogger($this->config['logging']['directory'],
                $this->config['logging']['level']);
        TCXManager::initTCXManager($this->config['store']['directory'], 
                $this->config['store']['subdirs'], 
                $this->config['store']['schema']);
        UserManager::initUserManager($this->config['database']['url'], 
                $this->config['database']['username'],
                $this->config['database']['password'],
                $this->config['database']['maxrows']);
        ActivityManager::initActivityManager($this->config['database']['url'], 
                $this->config['database']['username'],
                $this->config['database']['password'],
                $this->config['database']['maxrows']);
        UserOptionManager::initUserOptionManager($this->config['database']['url'], 
                $this->config['database']['username'],
                $this->config['database']['password'],
                $this->config['database']['maxrows']);
        TagManager::initTagManager($this->config['database']['url'],
                $this->config['database']['username'],
                $this->config['database']['password'],
                $this->config['database']['maxrows']);
        VersionManager::initVersionManager($this->config['database']['url'], 
                $this->config['database']['username'],
                $this->config['database']['password'],
                $this->config['database']['maxrows']);
    }
}
