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
 * Class that represents a tag configuration.
 *
 * @author ricky
 */
class Tag implements \JsonSerializable {

    private $tag;
    private $auto;

    public function __construct(string $tag, bool $auto = false) {
        $this->tag = $tag;
        $this->auto = $auto;
    }

    public static function tagFromAssoc($assoc): Tag {
        return new Tag($assoc['tag'], $assoc['auto']);
    }

    public function getTag(): string {
        return $this->tag;
    }

    public function isAuto(): ?bool {
        return $this->auto;
    }

    public function jsonSerialize() {
        return [
            'tag' => $this->tag,
            'auto' => $this->auto,
        ];
    }

}