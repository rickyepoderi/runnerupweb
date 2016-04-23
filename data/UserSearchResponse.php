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

use runnerupweb\data\User;

/**
 * Json response for a User search.
 *
 * @author ricky
 */
class UserSearchResponse extends LoginResponse implements \JsonSerializable {
    
    private $users;
    
    /**
     * 
     * @param User[] $users
     */
    public function __construct($users = null) {
        parent::__construct(true);
        $this->users = $users;
    }
    
    /**
     * Constructor using the json.
     * 
     * @param string $json The json string
     * @return UserSearchResponse The user search response
     */
    public static function responseWithJson($json) {
        $val = json_decode($json, true);
        $users = [];
        if (array_key_exists('response', $val)) {
            foreach ($val['response'] as $u) {
                $user = User::userWithAssoc($u);
                array_push($users, $user);
            }
        }
        $response = new UserSearchResponse($users);
        $response->fromJson($val);
        return $response;
    }
    
    /**
     * Return the users array in the response
     * 
     * @return array The User[] array of users
     */
    public function getUsers() {
        return $this->users;
    }
    
    /**
     * 
     * @return mixed
     */
    public function jsonSerialize() {
        // common status response
        $assoc = parent::jsonSerialize();
        // add response with the array of activities
        $assoc['response'] = [];
        foreach ($this->users as $user) {
            array_push($assoc['response'], $user->jsonSerialize());
        }
        return $assoc;
    }
}
