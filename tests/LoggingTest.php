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

use runnerupweb\common\Logging;
use Psr\Log\LogLevel;
use PHPUnit\Framework\TestCase;

/**
 * Description of LoggingTest
 *
 * @author ricky
 */
class LoggingTest extends TestCase {
    
    private function writeMessages() {
        Logging::debug("debug message");
        Logging::debug("debug message", array("lala", 0));
        Logging::info("info message");
        Logging::info("info message", array("lala", 0));
        Logging::warning("warning message");
        Logging::warning("warning message", array("lala", 0));
        Logging::error("error message");
        Logging::error("error message", array("lala", 0));
    }
    
    public function testLoggingDebug() {
        $dir = __DIR__;
        Logging::initForceLogger($dir, LogLevel::DEBUG, array('filename' => 'phpunittest1.log'));
        $this->writeMessages();
        // read the contents
        $file = fopen($dir . "/phpunittest1.log", "r");
        $i = 0;
        while (!feof($file)) {
            $line = fgets($file);
            $i++;
            switch ($i) {
                case 1:
                    $this->assertTrue(strpos($line, '[debug]') !== FALSE);
                    $this->assertTrue(strpos($line, 'debug message') !== FALSE);
                    break;
                case 5:
                    $this->assertTrue(strpos($line, '[info]') !== FALSE);
                    $this->assertTrue(strpos($line, 'info message') !== FALSE);
                    break;
                case 9:
                    $this->assertTrue(strpos($line, '[warning]') !== FALSE);
                    $this->assertTrue(strpos($line, 'warning message') !== FALSE);
                    break;
                case 14:
                    $this->assertTrue(strpos($line, '[error]') !== FALSE);
                    $this->assertTrue(strpos($line, 'error message') !== FALSE);
                    break;
                default:
                    break;
            }
        }
        fclose($file);
        unlink($dir . "/phpunittest1.log");
        $this->assertEquals(4*4 + 1, $i);
    }
    
    public function testLoggingWarning() {
        $dir = __DIR__;
        Logging::initForceLogger($dir, LogLevel::WARNING, array('filename' => 'phpunittest2.log'));
        $this->writeMessages();
        // read the contents
        $file = fopen($dir . "/phpunittest2.log", "r");
        $i = 0;
        while (!feof($file)) {
            $line = fgets($file);
            $i++;
            switch ($i) {
                case 1:
                    $this->assertTrue(strpos($line, '[warning]') !== FALSE);
                    $this->assertTrue(strpos($line, 'warning message') !== FALSE);
                    break;
                case 5:
                    $this->assertTrue(strpos($line, '[error]') !== FALSE);
                    $this->assertTrue(strpos($line, 'error message') !== FALSE);
                    break;
                default:
                    break;
            }
        }
        fclose($file);
        unlink($dir . "/phpunittest2.log");
        $this->assertEquals(4*2 + 1, $i);
    }
    
    
}
