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
 * The activity lap is the information that is parsed for every lap in the
 * TCX parsing. The lap information is not stored in the ddbb and it is just
 * used to calculate Activity information.
 *
 * @author ricky
 */
class ActivityLap {
    
    private $totalTimeSeconds;
    private $distanceMeters;
    private $maximumSpeed;
    private $calories;
    private $averageHeartRateBpm;
    private $maximumHeartRateBpm;
    private $cadence;
    private $intensity;
    private $triggerMethod;
    private $startTime;
    
    function __construct() {
        // noop
    }
    
    
    /**
     * Getter for the total time seconds.
     * @return double
     */
    function getTotalTimeSeconds() {
        return $this->totalTimeSeconds;
    }

    /**
     * Getter for the distance meters.
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
     * Getter for the calories.
     * @return int
     */
    function getCalories() {
        return $this->calories;
    }

    /**
     * Getter for the average heartrate.
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
     * Getter for the cadence.
     * @return int
     */
    function getCadence() {
        return $this->cadence;
    }

    /**
     * Getter for the intensity.
     * @return string
     */
    function getIntensity() {
        return $this->intensity;
    }

    /**
     * Getter for the start time.
     * @return DateTime
     */
    function getStartTime() {
        return $this->startTime;
    }

    /**
     * Setter for the total time seconds
     * @param mixed $totalTimeSeconds
     */
    function setTotalTimeSeconds($totalTimeSeconds) {
       $this->totalTimeSeconds = doubleval($totalTimeSeconds);
    }

    /**
     * Setter for the distance meters
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
     * Setter for the calories
     * @param mixed $calories
     */
    function setCalories($calories) {
        $this->calories = intval($calories);
    }

    /**
     * Setter for the average heartrate
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
     * Setter for the cadence.
     * @param mixed $cadence
     */
    function setCadence($cadence) {
        $this->cadence = intval($cadence);
    }

    /**
     * Setter for the intensity.
     * @param string $intensity
     */
    function setIntensity($intensity) {
        $this->intensity = $intensity;
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
            $this->startTime = \DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $startTime, new \DateTimeZone('UTC'));
        }
    }
    
    /**
     * Setter for the trigger method
     * @return string
     */
    function getTriggerMethod() {
        return $this->triggerMethod;
    }

    /**
     * Setter for the trigger method
     * @param string $triggerMethod
     */
    function setTriggerMethod($triggerMethod) {
        $this->triggerMethod = $triggerMethod;
    }

}
