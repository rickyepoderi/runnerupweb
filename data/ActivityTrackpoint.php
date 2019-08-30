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

namespace runnerupweb\data;

use runnerupweb\common\Logging;

/**
 * An ActivityTrackpoint.
 *
 * @author ricky
 */
class ActivityTrackpoint {

    private $time;
    private $latitude;
    private $longitude;
    private $altitude;
    private $distance;
    private $heartRate;
    private $cadence;

    function __construct() {
        // noop
    }

    /**
     * Getter for the time
     * @return \DateTime
     */
    function getTime(): \DateTime {
        return $this->time;
    }

    /**
     * Getter for the latitude
     * @return float|null
     */
    function getLatitude(): ?float {
        return $this->latitude;
    }

    /**
     * Getter for longitude
     * @return float|null
     */
    function getLongitude(): ?float {
        return $this->longitude;
    }

    /**
     * Getter for altitude
     * @return float|null
     */
    function getAltitude(): ?float {
        return $this->altitude;
    }

    /**
     * Getter for the float
     * @return float|null
     */
    function getDistance(): ?float {
        return $this->distance;
    }

    /**
     * Getter for the heart rate
     * @return float|null
     */
    function getHeartRate(): ?float {
        return $this->heartRate;
    }

    /**
     * Getter for the cadence
     * @return float|null
     */
    function getCadence(): ?float {
        return $this->cadence;
    }

    /**
     * Setter for time
     * @param \DateTime $time
     * @return void
     */
    function setTime(\DateTime $time): void {
        $this->time = $time;
    }

    function setTimeFromString(string $time): void {
        // convert from XML format
        $time = \DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $time, new \DateTimeZone('UTC'));
        if ($time === false) {
            // try MYSQL format
            $time = \DateTime::createFromFormat('Y-m-d H:i:s', $time, new \DateTimeZone('UTC'));
        }
        if ($time === false) {
            // try full format with timezone
            $time = \DateTime::createFromFormat('Y-m-d\TH:i:sP', $time, new \DateTimeZone('UTC'));
        }
        if ($time !== false) {
            $this->time = $time;
        } else {
            Logging::error("Invalid time format $time");
        }
    }

    /**
     * Setter for the latitude
     * @param float|null $latitude
     */
    function setLatitude(?float $latitude) {
        $this->latitude = $latitude;
    }

    /**
     * setter for the longitude
     * @param float|null $longitude
     */
    function setLongitude(?float $longitude) {
        $this->longitude = $longitude;
    }

    /**
     * setter for the altitude
     * @param float|null $altitude
     */
    function setAltitude(?float $altitude) {
        $this->altitude = $altitude;
    }

    /**
     * setter for the distance
     * @param float|null $distance
     */
    function setDistance(?float $distance) {
        $this->distance = $distance;
    }

    /**
     * setter for the heart rate
     * @param float|null $heartRate
     */
    function setHeartRate(?float $heartRate) {
        $this->heartRate = $heartRate;
    }

    /**
     * setter for the cadence
     * @param float|null $cadence
     */
    function setCadence(?float $cadence) {
        $this->cadence = $cadence;
    }

}
