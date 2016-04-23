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
 * Json response class for User information.
 *
 * @author ricky
 */
class UserResponse extends LoginResponse implements \JsonSerializable {
    
    private $user;
    
    public function __construct(User $user = null) {
        parent::__construct(true);
        $this->user = $user;
    }
    
    /**
     * Constructor of the response using json.
     * @param string $json
     * @return UserResponse
     */
    public static function responseWithJson($json) {
        $val = json_decode($json, true);
        if (array_key_exists('response', $val)) {
            $user = User::userWithAssoc($val['response']);
        } else {
            $user = null;
        }
        $response = new UserResponse($user);
        $response->fromJson($val);
        return $response;
    }
    
    /**
     * Getter for the user in the response.
     * @return User
     */
    public function getUser() {
        return $this->user;
    }
    
    /**
     * 
     * @return mixed
     */
    public function jsonSerialize() {
        // common status response
        $assoc = parent::jsonSerialize();
        // add response with the array of activities
        $assoc['response'] = $this->user->jsonSerialize();
        return $assoc;
    }
}
