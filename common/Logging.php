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
use Psr\Log\LogLevel;
use Katzgrau\KLogger\Logger;


/**
 * Logging class in the application.
 *
 * @author ricky
 */
class Logging {
    
    /**
     *
     * @var the singleton instance for the logging using klogger
     */
    private static $instance;
    
    public static function initLogger($logDirectory, $logLevelThreshold = LogLevel::DEBUG, array $options = array()) {
        if (static::$instance == null) {
            static::initForceLogger($logDirectory, $logLevelThreshold, $options);
        }
    }
    
    public static function initForceLogger($logDirectory, $logLevelThreshold = LogLevel::DEBUG, array $options = array()) {
        static::$instance = new Logger($logDirectory, $logLevelThreshold, $options);
    }
    
    /**
     * General logging method. Better use the specific function.
     * 
     * @param string $level The level to use (ERROR, WARNING, INFO DEBUG)
     * @param string $msg The message to log
     * @param array $context The context variables to show
     */
    public static function log($level, $msg, array $context = array()) {
        $bt = debug_backtrace();
        // shift twice to jump this current class
        array_shift($bt);
        $caller = array_shift($bt);
        static::$instance->log($level, 
                $caller['file'] . ":" . $caller['line'] . " " . $msg, 
                $context);
    }
    
    /**
     * Debug an error message.
     * @param string $msg The message
     * @param array $context The context variable
     */
    public static function error($msg, array $context = array()) {
        static::log(LogLevel::ERROR, $msg, $context);
    }
    
    /**
     * Debug a warning message.
     * @param string $msg The message
     * @param array $context The context variable
     */
    public static function warning($msg, array $context = array()) {
        static::log(LogLevel::WARNING, $msg, $context);
    }
    
    /**
     * Debug an info message.
     * @param string $msg The message
     * @param array $context The context variable
     */
    public static function info($msg, array $context = array()) {
        static::log(LogLevel::INFO, $msg, $context);
    }
    
    /**
     * Debug an debug message.
     * @param string $msg The message
     * @param array $context The context variable
     */
    public static function debug($msg, array $context = array()) {
        static::log(LogLevel::DEBUG, $msg, $context);
    }
}
