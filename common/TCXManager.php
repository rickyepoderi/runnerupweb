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

use runnerupweb\data\Activity;
use runnerupweb\data\ActivityLap;
use runnerupweb\common\Logging;

/**
 * manager to manage TCX activities in the application. The files are stored in
 * the a path in the FS. Each user has a directory under the activities are stored
 * gzipped. Each file is separated in a second level of directories to spread 
 * files between several directories.
 *
 * @author ricky
 */
class TCXManager {
    
    private $directory;
    private $numberDir;
    private $checkSchema;
    
    static private $tcxManager;
    
    protected function __construct($directory, $numberDir, $checkSchema) {
        $this->directory = $directory;
        $this->numberDir = $numberDir;
        $this->checkSchema = $checkSchema;
    }
    
    /**
     * Init the TCX manager singleton.
     * @param type $directory The directory fto store activities
     * @param type $numberDir The number of firectories in each user
     * @param type $checkSchema Wheter to check the schema using XSD or not
     * @return type The TCX singleton
     */
    static public function initTCXManager($directory, $numberDir, $checkSchema) {
        Logging::initLogger(__DIR__);
        static::$tcxManager = new TCXManager($directory, $numberDir, $checkSchema);
        return static::getTCXManager();
    }
    
    /**
     * Getter for the singleton.
     * @return TCXManager The TCX manager singleton
     */
    static public function getTCXManager() {
        return static::$tcxManager;
    }
    
    //
    // PARSE METHODS
    //
    
    private function parseHeartBeat(\XMLReader $reader, ActivityLap $lap) {
        if ($reader->nodeType === \XMLReader::ELEMENT && 
                ($reader->name === 'AverageHeartRateBpm' || $reader->name === 'MaximumHeartRateBpm')) {
            $name = $reader->name;
            while ($reader->read()) {
                if ($reader->nodeType === \XMLReader::ELEMENT && $reader->name === 'Value') {
                    if ($name === 'AverageHeartRateBpm') {
                        $lap->setAverageHeartRateBpm($reader->readString());
                    } else {
                        $lap->setMaximumHeartRateBpm($reader->readString());
                    }
                } else if ($reader->nodeType === \XMLReader::ELEMENT) {
                    // not interested in this value
                    $reader->next();
                } elseif ($reader->nodeType === \XMLReader::END_ELEMENT && $reader->name === $name) {
                    return;
                }
            }
        }
    }
    
    private function parseLap(\XMLReader $reader) {
        if ($reader->nodeType === \XMLReader::ELEMENT && $reader->name === 'Lap') {
            Logging::debug("parseLap starting...");
            $lap = new ActivityLap();
            // read the start time
            $lap->setStartTime($reader->getAttribute('StartTime'));
            $continue = $reader->read();
            while ($continue) {
                $readnext = true;
                if ($reader->nodeType === \XMLReader::ELEMENT && $reader->name === 'TotalTimeSeconds') {
                    $lap->setTotalTimeSeconds($reader->readString());
                } elseif ($reader->nodeType === \XMLReader::ELEMENT && $reader->name === 'DistanceMeters') {
                    $lap->setDistanceMeters($reader->readString());
                } elseif ($reader->nodeType === \XMLReader::ELEMENT && $reader->name === 'MaximumSpeed') {
                    $lap->setMaximumSpeed($reader->readString());
                } elseif ($reader->nodeType === \XMLReader::ELEMENT && $reader->name === 'Calories') {
                    $lap->setCalories($reader->readString());
                } elseif ($reader->nodeType === \XMLReader::ELEMENT && 
                        ($reader->name === 'AverageHeartRateBpm' || $reader->name === 'MaximumHeartRateBpm')) {
                    $this->parseHeartBeat($reader, $lap);
                } elseif ($reader->nodeType === \XMLReader::ELEMENT && $reader->name === 'Intensity') {
                    $lap->setIntensity($reader->readString());
                } elseif ($reader->nodeType === \XMLReader::ELEMENT && $reader->name === 'TriggerMethod') {
                    $lap->setTriggerMethod($reader->readString());
                } elseif ($reader->nodeType === \XMLReader::ELEMENT && $reader->name === 'Cadence') {
                    $lap->setCadence($reader->readString());
                } else if ($reader->nodeType === \XMLReader::ELEMENT) {
                    // not interested in this value 
                    Logging::debug("going next $reader->name");
                    $continue = $reader->next();
                    $readnext = false;
                } elseif ($reader->nodeType === \XMLReader::END_ELEMENT && $reader->name === 'Lap') {
                    Logging::debug("return ldap...", array($lap));
                    return $lap;
                }
                // read next only if not next()
                if ($readnext) {
                    $continue = $reader->read();
                }
            }
        }
    }
    
    private function parseActivity(\XMLReader $reader) {
        if ($reader->nodeType === \XMLReader::ELEMENT && $reader->name === 'Activity') {
            Logging::debug("parseActivity starting...");
            $activity = new Activity();
            // read the type of activity
            $activity->setSport($reader->getAttribute('Sport'));
            // read all the inner attributes
            $continue = $reader->read();
            while ($continue) {
                $readnext = true;
                if ($reader->nodeType === \XMLReader::ELEMENT && $reader->name === 'Id') {
                    $activity->setStartTime($reader->readString());
                } elseif ($reader->nodeType === \XMLReader::ELEMENT && $reader->name === 'Notes') {
                    $activity->setNotes($reader->readString());
                } elseif ($reader->nodeType === \XMLReader::ELEMENT && $reader->name === 'Lap') {
                    $lap = $this->parseLap($reader);
                    $activity->add($lap);
                } else if ($reader->nodeType === \XMLReader::ELEMENT) {
                    // not interested in this value
                    $continue = $reader->next();
                } elseif ($reader->nodeType === \XMLReader::END_ELEMENT && $reader->name === 'Activity') {
                    $activity->calculate();
                    return $activity;
                }
                if ($readnext) {
                    $continue = $reader->read();
                }
            }
            return ;
        }
    }
    
    private function parseActivities(\XMLReader $reader) {
        $activities = [];
        if ($reader->nodeType === \XMLReader::ELEMENT && $reader->name === 'Activities') {
            Logging::debug("parseActivities starting...");
            $continue = $reader->read();
            while ($continue) {
                $readnext = true;
                if ($reader->nodeType === \XMLReader::ELEMENT && $reader->name === 'Activity') {
                    $activity = $this->parseActivity($reader);
                    array_push($activities, $activity);
                } else if ($reader->nodeType === \XMLReader::ELEMENT) {
                    // not interested in this value
                    $continue = $reader->next();
                    $readnext = false;
                } elseif ($reader->nodeType === \XMLReader::END_ELEMENT && $reader->name === 'Activities') {
                    return $activities;
                }
                if ($readnext) {
                    $continue = $reader->read();
                }
            }
        }
    }
    
    private function parseTrainingCenterDatabase(\XMLReader $reader) {
        $activities = null;
        if ($reader->nodeType === \XMLReader::ELEMENT && $reader->name === 'TrainingCenterDatabase') {
            Logging::debug("parseTrainingCenterDatabase starting...");
            while ($reader->read()) {
                if ($reader->nodeType === \XMLReader::ELEMENT && $reader->name === 'Activities') {
                    $activities = $this->parseActivities($reader);
                } elseif ($reader->nodeType === \XMLReader::END_ELEMENT && $reader->name === 'TrainingCenterDatabase') {
                    return $activities;
                }
            }
        }
    }
    
    /**
     * Method that parses a TCX file (for the moment schema validation is
     * disabled cos RunnerUp does not follow the schema completely). The method
     * parses the file and return an array with the activities involved. It
     * returns the array with the activities (\runnerup\data\Actvity) found.
     * 
     * @param String $file The TCX file to parse
     * @return Activity[] The array of activities
     */
    public function parse($file) {
        Logging::debug("parse starting...");
        $reader = new \XMLReader();
        $reader->open($file);
        return $this->parseReader($reader);
    }
    
    /**
     * Method that parses the TCX file but using a string.
     * 
     * @param string $xml The string in XML
     * @return Activity[] 
     */
    public function parseString($xml) {
        Logging::debug("parse starting...");
        $reader = new \XMLReader();
        $reader->XML($xml);
        return $this->parseReader($reader);
    }
    
    private function parseReader(\XMLReader $reader) {
        // RunnerUp does not follows XSD, minor problems involving sequence and restrictions
        // https://github.com/jonasoreland/runnerup/issues/332
        if ($this->checkSchema) {
            $reader->setSchema(__DIR__ . '/../xsd/TrainingCenterDatabasev2.xsd');
        }
        $reader->next();
        $activities = $this->parseTrainingCenterDatabase($reader);
        return $activities;
    }
    
    //
    // SPLIT METHODS
    //
    
    private function copyNodeAttributes(\XMLReader $reader, \XMLWriter $writer) {
        if ($reader->hasAttributes) {
            while($reader->moveToNextAttribute()) {
                $writer->writeAttribute($reader->name, $reader->value);
            }
        }
    }
    
    private function copyNode(\XMLReader $reader, \XMLWriter $writer) {
        switch ($reader->nodeType) {
            case \XMLReader::ELEMENT:
                $writer->startElement($reader->name);
                $empty = $reader->isEmptyElement;
                $this->copyNodeAttributes($reader, $writer);
                if ($empty) {
                    $writer->endElement();
                }
                break;
            case \XMLReader::TEXT:
                $writer->text($reader->value);
                break;
            case \XMLReader::CDATA:
                $writer->writeCdata($reader->value);
                break;
            case \XMLReader::XML_DECLARATION:
            case \XMLReader::PI:
                $writer->writePi($reader->name, $reader->value);
                break;
            case \XMLReader::END_ELEMENT:
                $writer->endElement();
                break;
            case \XMLReader::COMMENT:
                $writer->writeComment($reader->value);
                break;
            default:
                // all other types are not used
                break;
        }
    }
    
    private function copyActivity(\XMLReader $reader, $file) {
        $writer = new \XMLWriter();
        $writer->openUri($file);
        $writer->setIndent(true);
        $writer->setIndentString("  ");
        $writer->startDocument("1.0", "UTF-8");
        $writer->startElement("TrainingCenterDatabase");
        $writer->writeAttribute("xmlns", "http://www.garmin.com/xmlschemas/TrainingCenterDatabase/v2");
        $writer->startElement("Activities");
        if ($reader->nodeType === \XMLReader::ELEMENT && $reader->name === 'Activity') {
            $this->copyNode($reader, $writer);
            while ($reader->read()) {
                $this->copyNode($reader, $writer);
                if ($reader->nodeType === \XMLReader::END_ELEMENT && $reader->name === 'Activity') {
                    break;
                }
            }
        }
        $writer->endElement(); // activities
        $writer->endElement(); // TrainigCenterDatabase
        $writer->endDocument();
    }
    
    private function splitActivities(\XMLReader $reader, $file) {
        $files = [];
        $number = 1;
        if ($reader->nodeType === \XMLReader::ELEMENT && $reader->name === 'Activities') {
            while ($reader->read()) {
                if ($reader->nodeType === \XMLReader::ELEMENT && $reader->name === 'Activity') {
                    $name = $file . "-" . $number++;
                    $this->copyActivity($reader, $name);
                    array_push($files, $name);
                } else if ($reader->nodeType === \XMLReader::ELEMENT) {
                    // not interested in this value
                    $reader->next();
                } elseif ($reader->nodeType === \XMLReader::END_ELEMENT && $reader->name === 'Activities') {
                    return $files;
                }
            }
        }
    }
    
    private function splitTrainingCenterDatabase(\XMLReader $reader, $file) {
        $files = null;
        if ($reader->nodeType === \XMLReader::ELEMENT && $reader->name === 'TrainingCenterDatabase') {
            while ($reader->read()) {
                if ($reader->nodeType === \XMLReader::ELEMENT && $reader->name === 'Activities') {
                    $files = $activities = $this->splitActivities($reader, $file);
                } elseif ($reader->nodeType === \XMLReader::END_ELEMENT && $reader->name === 'TrainingCenterDatabase') {
                    return $files;
                }
            }
        }
    }
    
    /**
     * This method is used to split a TCX received file if the file contains
     * more than one activity. Remember the idea is the web only manages
     * one activity at a time. So if a TCX file with more than one activity
     * is uploaded the file is splited to as many files as activities inside
     * the original file. It returns the array with the files names. The schema
     * is necer checked cos it should be done a parse before.
     * 
     * @param String $file The TCX file to split in several files, each file with
     *        only one activity.
     */
    public function split($file) {
        $reader = new \XMLReader();
        $reader->open($file);
        // no need to check schema cos parse method is called before
        $reader->next();
        return $this->splitTrainingCenterDatabase($reader, $file);
    }
    
    //
    // STORE METHODS
    //
    
    private function calculateDirectory($username, $id) {
        $dir = sprintf("%0" . strlen((string) $this->numberDir) ."d", $id % $this->numberDir);
        return $this->directory . "/" . $username . "/" . $dir;
    }
    
    private function calculateUserDirectory($username) {
        return $this->directory . "/" . $username;
    }
    
    private function compressFile($src, $dst) {
        $ok = false;
        $out = gzopen($dst, 'wb');
        $in = fopen($src, 'rb');
        if ($out && $in) {
            while (!feof($in)) {
                gzwrite($out, fread($in, 1024 * 512));
            }
            fclose($in);
            gzclose($out);
            $ok = true;
        }
        return $ok;
    }

    /**
     * Method that store inside the configured directory the specified
     * TCX file. The file should contain only one activity corresponding
     * to the ID stored in the database. The file source is not deleted, the
     * file is compressed to the destination store folder.
     * 
     * @param string $username the username where TCX belongs to
     * @param int $id The ID assigned inside the database
     * @param string $file The file containing the TCX file
     */
    public function store($username, $id, $file) {
        $dir = $this->calculateDirectory($username, $id);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0750, true)) {
                throw new \RuntimeException("Error creating the directory in the store path!");
            }
        }
        if (!$this->compressFile($file, $dir . "/" . $id . ".tcx")) {
            throw new \RuntimeException("Error creating the file in the store path!");
        }
    }
    
    /**
     * Delete a previous stored file with a TCX activity.
     * 
     * @param string $username the username where TCX belongs to
     * @param int $id The identifier associated in the database
     */
    public function delete($username, $id) {
        $dir = $this->calculateDirectory($username, $id);
        unlink($dir . "/" . $id . ".tcx");
    }
    
    /**
     * Function to delete a user directory path and all the files on it.
     * 
     * @param string $path The directory path to delete
     * @return void 
     */
    public function removeDirectory($path) {
        $files = glob($path . '/*');
        foreach ($files as $file) {
            is_dir($file) ? $this->removeDirectory($file) : unlink($file);
        }
        rmdir($path);
        return;
    }

    /**
     * Deletes the complete structure of upload activities for a specified 
     * user.
     * 
     * @param string $username
     */
    public function deleteUserActivities($username) {
        $dir = $this->calculateUserDirectory($username);
        $this->removeDirectory($dir);
    }
    
    /**
     * Retrieve the TCX file name of a previous stored database. It returns
     * the file name that containcs the TCX or null.
     * 
     * @param string $username the username where TCX belongs to
     * @param int $id The id associated to the activity in the database
     */
    public function get($username, $id) {
        $dir = $this->calculateDirectory($username, $id);
        if (is_readable($dir . "/" . $id . ".tcx")) {
            return $dir . "/" . $id . ".tcx";
        } else {
            return null;
        }
    }

}
