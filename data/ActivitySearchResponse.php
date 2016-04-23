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

use runnerupweb\data\Activity;

/**
 * Class that represents a activity search response for json encoding.
 * 
 * @author ricky
 */
class ActivitySearchResponse extends LoginResponse implements \JsonSerializable {
    
    private $activities;
    
    /**
     * 
     * @param Activity[] $activities
     */
    public function __construct($activities) {
        parent::__construct(true);
        $this->activities = $activities;
    }
    
    /**
     * Constructor using the json.
     * 
     * @param string $json The json string
     * @return UserSearchResponse The user search response
     */
    public static function responseWithJson($json) {
        $val = json_decode($json, true);
        $activities = [];
        if (array_key_exists('response', $val)) {
            foreach ($val['response'] as $act) {
                $activity = Activity::activityFromAssoc($act);
                array_push($activities, $activity);
            }
        }
        $response = new ActivitySearchResponse($activities);
        $response->fromJson($val);
        return $response;
    }
    
    /**
     * Return the activities array in the response
     * 
     * @return Activity[] The Activity[] array of users
     */
    public function getActivities() {
        return $this->activities;
    }
    
    /**
     * 
     * @return array
     */
    public function jsonSerialize() {
        // common status response
        $assoc = parent::jsonSerialize();
        // add response with the array of activities
        $assoc['response'] = [];
        foreach ($this->activities as $activity) {
            array_push($assoc['response'], $activity->jsonSerialize());
        }
        return $assoc;
    }

}