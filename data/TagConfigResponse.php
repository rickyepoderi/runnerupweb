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

use runnerupweb\data\TagConfig;

/**
 * Json response class for User information.
 *
 * @author ricky
 */
class TagConfigResponse extends LoginResponse implements \JsonSerializable {

    private $tagConfig;

    public function __construct(?TagConfig $tagConfig = null) {
        parent::__construct(true);
        $this->tagConfig = $tagConfig;
    }

    /**
     * Constructor of the response using json.
     * @param string $json
     * @return UserResponse
     */
    public static function responseWithJson($json) {
        $val = json_decode($json, true);
        if (array_key_exists('response', $val)) {
            $tagConfig = TagConfig::tagConfigFromAssoc($val['response']);
        } else {
            $tagConfig = null;
        }
        $response = new TagConfigResponse($tagConfig);
        $response->fromJson($val);
        return $response;
    }

    /**
     * Getter for the tagConfig in the response.
     * @return TagConfig
     */
    public function getTagConfig(): ?TagConfig {
        return $this->tagConfig;
    }

    /**
     *
     * @return mixed
     */
    public function jsonSerialize() {
        // common status response
        $assoc = parent::jsonSerialize();
        // add response with the array of activities
        $assoc['response'] = $this->tagConfig->jsonSerialize();
        return $assoc;
    }
}