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
use runnerupweb\common\Logging;
use runnerupweb\common\DataBase;
use runnerupweb\common\TCXManager;
use runnerupweb\data\Activity;

/**
 * The ActivityManager class manages activities in database and inside the
 * TCX folder where activities are stored for a user. The class contains basic
 * CRUD methods to control activities inside the application.
 * 
 * CREATE TABLE `activity` (
 *   `id` bigint(20) NOT NULL AUTO_INCREMENT,
 *   `login` varchar(64) NOT NULL,
 *   `startTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 *   `sport` varchar(50) NOT NULL,
 *   `totalTimeSeconds` double NOT NULL,
 *   `distanceMeters` double NOT NULL,
 *   `maximumSpeed` double DEFAULT NULL,
 *   `calories` smallint(6) DEFAULT NULL,
 *   `averageHeartRateBpm` smallint(6) DEFAULT NULL,
 *   `maximumHeartRateBpm` smallint(6) DEFAULT NULL,
 *   `notes` varchar(2048) DEFAULT NULL,
 *   `filename` varchar(512) NOT NULL,
 *   PRIMARY KEY (`id`, `login`),
 *   KEY `login_idx` (`login`),
 *   CONSTRAINT `activity_login_fgk` FOREIGN KEY (`login`) REFERENCES `user` (`login`) ON DELETE CASCADE
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8
 *
 * @author ricky
 */
class ActivityManager extends DataBase {
    
    static private $activityManager;
     
    protected function __construct($url, $username, $password, $maxrows) {
        parent::__construct($url, $username, $password, $maxrows);
    }
    
    /**
     * Initializer for the singleton.
     * @param type $url The URL to the database
     * @param type $username The username to connect to the ddbb
     * @param type $password The password of the user
     * @param type $maxrows Maximum rows to select
     * @return ActivityManager The singleton
     */
    static public function initActivityManager($url, $username, $password, $maxrows) {
        static::$activityManager = new ActivityManager($url, $username, $password, $maxrows);
        return static::getActivityManager();
    }
    
    /**
     * Getter for the singleton.
     * @return ActivityManager
     */
    static public function getActivityManager() {
        return static::$activityManager;
    }
    
    /**
     * Method that inserts a new activity inside the database. This method
     * just manages the database, file store is not updated. It returns the
     * assigned id by the database.
     * 
     * @param PDO $db The database connection
     * @param \runnerupweb\common\Activity $activity
     * @param string $username The username 
     */
    protected function create(\PDO $db, Activity $activity, $username) {
        $stmt = $db->prepare("INSERT INTO activity(login, startTime, sport, totalTimeSeconds, distanceMeters, "
                . "maximumSpeed, calories, averageHeartRateBpm, maximumHeartRateBpm, notes, filename) "
                . "VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$username, date('Y-m-d H:i:s', $activity->getStartTime()->getTimestamp()),
            $activity->getSport(), $activity->getTotalTimeSeconds(), $activity->getDistanceMeters(),
            $activity->getMaximumSpeed(), $activity->getCalories(), $activity->getAverageHeartRateBpm(),
            $activity->getMaximumHeartRateBpm(), $activity->getNotes(), $activity->getFilename()]);
        // recover the id assigned
        $id = $db->lastInsertId();
        return $id;
    }

    /**
     * Method that parses the TCX file and stores inside the database and the
     * folder where all TCX files for a user are placed. The file passed
     * as argument can be moved to the store (because it is usually uploaded
     * and it does not matter). So please backup it before calling if you
     * do not want to be lost.
     * 
     * @param string username The username storing the file
     * @param string $file The file containing the TCX file uploaded.
     * @param string $filename The filename as sent by the application
     */
    public function storeActivities($username, $file, $filename) {
        $db = $this->getConnection();
        try {
            // first parse the TCX file in order to get all the activities inside it
            $tcx = TCXManager::getTCXManager();
            $activities = $tcx->parse($file);
            $files = [$file];
            if (sizeof($activities) > 1) {
                // split the files in several files cos there are several activities in the file
                $files = $tcx->split($file);
            }
            // now we have the activities and the files, store in database and then in the shared folder
            $i = 0;
            foreach ($activities as $activity) {
                $activity->setFilename($filename);
                $id = $this->create($db, $activity, $username);
                $activity->setId($id);
                $tcx->store($username, $activity->getId(), $files[$i++]);
            }
            // remove the temporary files if it is the case
            if (sizeof($activities) > 1) {
                foreach ($files as $f) {
                    unlink($f);
                }
            }
            $db->commit();
            return $activities;
        } catch (Exception $ex) {
            $db->rollback();
            throw $ex;
        }
    }
    
    /**
     * Method that return an activity using the username and the id.
     * 
     * @param string $username The username
     * @param string $id The id 
     * @return Activity The activity associated to the username and id
     */
    public function getActivity($username, $id) {
        $db = $this->getConnection();
        $activity = null;
        try {
            $stmt = $db->prepare("SELECT id, startTime, sport, totalTimeSeconds, distanceMeters, maximumSpeed, calories, "
                    . "averageHeartRateBpm, maximumHeartRateBpm, notes, filename FROM activity WHERE id = ? AND login = ?");
            $stmt->execute([$id, $username]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($row != null) {
                $activity = Activity::activityFromAssoc($row);
            }
            $db->commit();
            return $activity;
        } catch (Exception $ex) {
            $db->rollback();
            throw $ex;
        }
    }
    
    /**
     * Method that returns the file associated a username and an activity id.
     * It just calls to the TCXManager but is put here to do all the operation 
     * through the ActivityManager.
     * 
     * @param type $username The username
     * @param type $id The id of the file
     * @return string The file or null
     */
    public function getActivityFile($username, $id) {
        $tcx = TCXManager::getTCXManager();
        return $tcx->get($username, $id);
    }
    
    /**
     * Method that removes the row of the entity in the database and the
     * associated activity file.
     * 
     * @param type $username
     * @param type $id
     * @return boolean true if deleted one row, false otherwise
     */
    public function deleteActivity($username, $id) {
        $db = $this->getConnection();
        try {
            $stmt = $db->prepare("DELETE FROM activity WHERE id = ? AND login = ?");
            $stmt->execute([$id, $username]);
            if ($stmt->rowCount() === 1) {
                $tcx = TCXManager::getTCXManager();
                $tcx->delete($username, $id);
            }
            $db->commit();
            return $stmt->rowCount() === 1;
        } catch (Exception $ex) {
            Logging::error("Error deleting activity", array($ex));
            $db->rollback();
            throw $ex;
        }
    }
    
    /**
     * Delete all activities of a user and the user directory
     * where the activities are stored.
     * 
     * @param string $username
     * @return void
     * @throws \runnerupweb\common\Exception
     */
    public function deleteUserActivities($username) {
        $db = $this->getConnection();
        try {
            $stmt = $db->prepare("DELETE FROM activity WHERE login = ?");
            $stmt->execute([$username]);
            Logging::debug("Deleted " . $stmt->rowCount() . " activities for user " . $username);
            $tcx = TCXManager::getTCXManager();
            $tcx->deleteUserActivities($username);
            $db->commit();
            return $stmt->rowCount() === 1;
        } catch (Exception $ex) {
            Logging::error("Error deleting activity", array($ex));
            $db->rollback();
            throw $ex;
        }
    }
    
    /**
     * Return a list of activities of a user between date. The final date is
     * optional to list all activities until the moment.
     * 
     * @param string $username The username to search
     * @param \DateTime $start Compulsory to filter activities after this date
     * @param \DateTime $end Optional, to filter activities before this date
     * @param int $offset for paged searches (default to 0)
     * @param int $limit for pages searches (default to 0 => transformed to maxrows)
     * @return Activity[] an array of activities found between dates
     */
    public function searchActivities($username, \DateTime $start, \DateTime $end = null,
            $offset = null, $limit = null) {
        Logging::debug("searchActivityes $username $offset $limit");
        $limit = ($limit == null)? $this->maxrows : $limit;
        $offset = ($offset == null)? 0 : $offset;
        $res = [];
        $db = $this->getConnection();
        try {
            $sql = "SELECT id, startTime, sport, totalTimeSeconds, distanceMeters, maximumSpeed, calories, "
                . "averageHeartRateBpm, maximumHeartRateBpm, notes, filename FROM activity WHERE login = ? AND startTime > ?";
            if ($end != null) {
                $sql = $sql . " AND startTime < ?";
            }
            $sql = $sql . " ORDER BY startTime DESC, id LIMIT ? OFFSET ?";
            $stmt = $db->prepare($sql);
            if ($end != null) {
                $stmt->execute([$username, $start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s'), $limit, $offset]);
            } else {
                $stmt->execute([$username, $start->format('Y-m-d H:i:s'), $limit, $offset]);
            }
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            while ($row != null) {
                $activity = Activity::activityFromAssoc($row);
                array_push($res, $activity);
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            }
            $db->commit();
            return $res;
        } catch (Exception $ex) {
            $db->rollback();
            throw $ex;
        }
    }
}
