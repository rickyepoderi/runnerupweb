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

use runnerupweb\data\Tag;

/**
 * Class that represents a tag list response for json encoding.
 *
 * @author ricky
 */
class TagListResponse extends LoginResponse implements \JsonSerializable {

    private $tags;

    /**
     *
     * @param array $tags
     */
    public function __construct($tags) {
        parent::__construct(true);
        $this->tags = $tags;
    }

    /**
     * Constructor using the json.
     *
     * @param string $json The json string
     * @return TagListResponse The tag list response
     */
    public static function responseWithJson($json) {
        $val = json_decode($json, true);
        $tags = [];
        if (array_key_exists('response', $val)) {
            foreach ($val['response'] as $act) {
                $tag = Tag::tagFromAssoc($act);
                array_push($tags, $tag);
            }
        }
        $response = new TagListResponse($tags);
        $response->fromJson($val);
        return $response;
    }

    /**
     * Return the activities array in the response
     *
     * @return array The tag list
     */
    public function getTags(): array {
        return $this->tags;
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
        foreach ($this->tags as $tag) {
            array_push($assoc['response'], $tag->jsonSerialize());
        }
        return $assoc;
    }

}