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

use runnerupweb\data\UserOption;

/**
 * The UserOptionResponse is the json response for UserOption.
 *
 * @author ricky
 */
class UserOptionResponse extends LoginResponse implements \JsonSerializable {
    
    /**
     *
     * @var UserOption 
     */
    private $uo;
    
    public function __construct(UserOption $uo = null) {
        parent::__construct(true);
        $this->uo = $uo;
    }
    
    /**
     * Constructs the response from the json data.
     * @param string $json
     * @return UserOptionResponse
     */
    public static function responseWithJson($json) {
        $val = json_decode($json, true);
        $opts = UserOption::userOptionWithAssoc($val['response']);
        $response = new UserOptionResponse($opts);
        $response->fromJson($val);
        return $response;
    }
    
    /**
     * return the options inside the response.
     * @return UserOption
     */
    public function getOptions() {
        return $this->uo;
    }
    
    /**
     * 
     * @return mixed
     */
    public function jsonSerialize() {
        // common status response
        $assoc = parent::jsonSerialize();
        // add response with the array of activities
        $assoc['response'] = $this->uo->jsonSerialize();
        return $assoc;
    }
}
