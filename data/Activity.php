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

namespace runnerupweb\data;

/**
 * The Activity class represents a exercise activity after parsing a TCX file.
 * The activity has some summary values (start time, notes, average speed and so 
 * on). It also has the lap information vut only to calculate some values.
 * Lap information is not stored in the ddbb.
 *
 * @author ricky
 */
class Activity implements \JsonSerializable {
    
    private $id;
    private $startTime;
    private $notes;
    private $sport;
    private $totalTimeSeconds;
    private $distanceMeters;
    private $maximumSpeed;
    private $calories;
    private $averageHeartRateBpm;
    private $maximumHeartRateBpm;
    private $filename;
    private $laps;
    
    function __construct() {
        $this->totalTimeSeconds = 0;
        $this->distanceMeters = 0;
        $this->maximumSpeed = 0;
        $this->calories = 0;
        $this->averageHeartRateBpm = 0;
        $this->maximumHeartRateBpm = 0;
        $this->laps = [];
    }
    
    /**
     * Method that construct an activity fron an assoc.
     * @param type $assoc The assoc
     * @return Activity The activity created
     */
    static public function activityFromAssoc($assoc) {
        $activity = new Activity();
        $activity->setId($assoc['id']);
        $activity->setStartTime($assoc['startTime']);
        $activity->setSport($assoc['sport']);
        $activity->setTotalTimeSeconds($assoc['totalTimeSeconds']);
        $activity->setDistanceMeters($assoc['distanceMeters']);
        $activity->setMaximumSpeed($assoc['maximumSpeed']);
        $activity->setCalories($assoc['calories']);
        $activity->setAverageHeartRateBpm($assoc['averageHeartRateBpm']);
        $activity->setMaximumHeartRateBpm($assoc['maximumHeartRateBpm']);
        $activity->setNotes($assoc['notes']);
        $activity->setFilename($assoc['filename']);
        return $activity;
    }

    /**
     * Getter for the id
     * @return int The id
     */
    function getId() {
        return $this->id;
    }
    
    /**
     * Getter for the start time.
     * @return DateTime The start time of the activity
     */
    function getStartTime() {
        return $this->startTime;
    }
    
    /**
     * Getter for the start time but as XML
     * @return string The string representation of the start time
     */
    function getStartTimeAsXML() {
        if ($this->startTime != null) {
            return $this->startTime->format('Y-m-d\TH:i:s\Z');
        } else {
            return null;
        }
    }

    /**
     * Getter for the notes.
     * @return string The notes
     */
    function getNotes() {
        return $this->notes;
    }

    /**
     * Getter for the sport
     * @return string The sport type
     */
    function getSport() {
        return $this->sport;
    }

    /**
     * Getter for the total time
     * @return double
     */
    function getTotalTimeSeconds() {
        return $this->totalTimeSeconds;
    }

    /**
     * Getter for the distance
     * @return double
     */
    function getDistanceMeters() {
        return $this->distanceMeters;
    }

    /**
     * Getter for the maximum speed
     * @return double
     */
    function getMaximumSpeed() {
        return $this->maximumSpeed;
    }

    /**
     * Getter for the calories
     * @return int
     */
    function getCalories() {
        return $this->calories;
    }

    /**
     * Getter for the average heartrate
     * @return int
     */
    function getAverageHeartRateBpm() {
        return $this->averageHeartRateBpm;
    }

    /**
     * Getter for the maximum heartrate
     * @return int
     */
    function getMaximumHeartRateBpm() {
        return $this->maximumHeartRateBpm;
    }
    
    /**
     * Getter for the filename
     * @return string
     */
    function getFilename() {
        return $this->filename;
    }

    /**
     * Getter for the laps
     * @return array
     */
    function getLaps(): array {
        return $this->laps;
    }
    
    /**
     * Setter for the id
     * @param mixed $id
     */
    function setId($id) {
        $this->id = $id + 0;
    }
   
    /**
     * Setter for the start time
     * @param mixed $startTime
     */
    function setStartTime($startTime) {
        if ($startTime instanceof \DateTime) {
            $this->startTime = $startTime;
        } else {
            // convert from XML format
            $time = \DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $startTime, new \DateTimeZone('UTC'));
            if ($time === false) {
                // try MYSQL format
                $time = \DateTime::createFromFormat('Y-m-d H:i:s', $startTime, new \DateTimeZone('UTC'));
            }
            if ($time === false) {
                // try full format with timezone
                $time = \DateTime::createFromFormat('Y-m-d\TH:i:sP', $startTime, new \DateTimeZone('UTC'));
            }
            if ($time !== false) {
                $this->startTime = $time;
            } else {
                Logging::error("Invalid time format $startTime");
            }
        }
    }

    /**
     * Setter for the notes.
     * @param string $notes
     */
    function setNotes($notes) {
        $this->notes = $notes;
    }

    /**
     * Setter for the sport type.
     * @param string $sport
     */
    function setSport($sport) {
        $this->sport = $sport;
    }

    /**
     * Setter for the total time seconds.
     * @param mixed $totalTimeSeconds
     */
    function setTotalTimeSeconds($totalTimeSeconds) {
        $this->totalTimeSeconds = doubleval($totalTimeSeconds);
    }

    /**
     * Setter for the distance meters.
     * @param mixed $distanceMeters
     */
    function setDistanceMeters($distanceMeters) {
        $this->distanceMeters = doubleval($distanceMeters);
    }

    /**
     * Setter for the maximum speed
     * @param mixed $maximumSpeed
     */
    function setMaximumSpeed($maximumSpeed) {
        $this->maximumSpeed = doubleval($maximumSpeed);
    }

    /**
     * Setter for the calories.
     * @param mixed $calories
     */
    function setCalories($calories) {
        $this->calories = intval($calories);
    }

    /**
     * Setter for the average heartrate.
     * @param mixed $averageHeartRateBpm
     */
    function setAverageHeartRateBpm($averageHeartRateBpm) {
        $this->averageHeartRateBpm = intval($averageHeartRateBpm);
    }

    /**
     * Setter for the maximum heartrate.
     * @param mixed $maximumHeartRateBpm
     */
    function setMaximumHeartRateBpm($maximumHeartRateBpm) {
        $this->maximumHeartRateBpm = intval($maximumHeartRateBpm);
    }
    
    /**
     * Setter for the filename.
     * @param string $filename
     */
    function setFilename($filename) {
        $this->filename = $filename;
    }

    /**
     * Setter for the laps
     * @param array|null laps
     */
    function setLaps(?array $laps): void {
        $this->laps = $laps;
    }

    /**
     * Add another lap to the activity.
     * @param \runnerupweb\data\ActivityLap $lap
     * @return type
     */
    public function add(ActivityLap $lap) {
        return array_push($this->laps, $lap);
    }
    
    /**
     * Clear all the laps.
     */
    public function clear() {
        $this->laps = [];
    }
    
    /**
     * Method that calculates all the data based on laps.
     */
    public function calculate() {
        $averageHeartRateBpm = 0.0;
        foreach ($this->laps as $lap) {
            $this->totalTimeSeconds += $lap->getTotalTimeSeconds();
            $this->distanceMeters += $lap->getDistanceMeters();
            if ($lap->getMaximumSpeed() > $this->maximumSpeed) {
                $this->maximumSpeed = $lap->getMaximumSpeed();
            }
            $this->calories += $lap->getCalories();
            $averageHeartRateBpm += (doubleval($lap->getAverageHeartRateBpm()) * $lap->getTotalTimeSeconds());
            if ($lap->getMaximumHeartRateBpm() > $this->maximumHeartRateBpm) {
                $this->maximumHeartRateBpm = $lap->getMaximumHeartRateBpm();
            }
        }
        $this->averageHeartRateBpm = intval($averageHeartRateBpm / $this->totalTimeSeconds);
    }
    
    /**
     * 
     * @return assoc
     */
    public function jsonSerialize() {
        return [
            'id' => $this->id,
            'startTime' => $this->startTime->format('Y-m-d\TH:i:s\Z'),
            'notes' => $this->notes,
            'sport' => $this->sport,
            'totalTimeSeconds' => $this->totalTimeSeconds,
            'distanceMeters' => $this->distanceMeters,
            'maximumSpeed' => $this->maximumSpeed,
            'calories' => $this->calories,
            'averageHeartRateBpm' => $this->averageHeartRateBpm,
            'maximumHeartRateBpm' => $this->maximumHeartRateBpm,
            'filename' => $this->filename,
        ];
    }
    
}
