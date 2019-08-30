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
class TagConfig implements \JsonSerializable {

    private $tag;
    private $config;
    private $provider;
    private $description;
    private $extra;

    /**
     * static constructor for tag name and description
     * @param string $tag
     * @param string|null $description
     * @return \runnerupweb\data\TagConfig
     */
    public static function tagConfigWithDescription(?string $tag, ?string $description): TagConfig {
        $tagConfig = new TagConfig();
        $tagConfig->tag = $tag;
        $tagConfig->description = $description;
        return $tagConfig;
    }

    /**
     * Static method to create a user from the assoc values.
     * @param mixed $assoc The assoc with the values
     * @return User The user
     */
    public static function tagConfigFromAssoc($assoc): TagConfig {
        $tagConfig = new TagConfig();
        $tagConfig->tag = isset($assoc['tag'])? $assoc['tag']:null;
        $tagConfig->config = isset($assoc['config'])? $assoc['config']:null;
        $tagConfig->provider = isset($assoc['provider'])? $assoc['provider']:null;
        $tagConfig->description = isset($assoc['description'])? $assoc['description']:null;
        $tagConfig->extra = isset($assoc['extra'])? $assoc['extra']:null;
        return $tagConfig;
    }

    /**
     * Static method to create a tag config using the json representation.
     * @param string $json The json code
     * @return TagConfig
     */
    public static function tagConfigWithJson(string $json): TagConfig {
        $val = json_decode($json, true);
        $tagConfig = TagConfig::tagConfigFromAssoc($val);
        if (in_array('extra', $val)) {
            $tagConfig->setExtra($val['extra']);
        }
        return $tagConfig;
    }

    /**
     * Getter for the tag name
     * @return string|null
     */
    public function getTag(): ?string {
        return $this->tag;
    }

    /**
     * Setter for the tag.
     * @param string $tag
     * @return void
     */
    public function setTag(string $tag): void {
        $this->tag = $tag;
    }

    /**
     * Getter fo rthe config
     * @return string|null
     */
    public function getConfig(): ?string {
        return $this->config;
    }

    /**
     * Setter for the config
     * @param string|null $config
     * @return void
     */
    public function setConfig(?string $config): void {
        $this->config = $config;
    }

    /**
     * Getter for the provider
     * @return string|null
     */
    public function getProvider(): ?string {
        return $this->provider;
    }

    /**
     * Setter for the provider
     * @param string|null $provider
     * @return void
     */
    public function setProvider(?string $provider): void {
        $this->provider = $provider;
    }

    /**
     * Returns if the tag config is an auto (provider set) tag config.
     * @return bool
     */
    public function isAuto(): bool {
        return !is_null($this->provider);
    }

    /**
     * Getter for the description
     * @return string
     */
    public function getDescription(): ?string {
        return $this->description;
    }

    /**
     * Setter for the description
     * @param string|null $description
     * @return void
     */
    public function setDescription(?string $description): void {
        $this->description = $description;
    }

    /**
     * Setter for the extra parameter.
     * @param array $extra
     * @return void
     */
    public function setExtra(array $extra): void {
        $this->extra = $extra;
    }

    /**
     * Setter for the extra
     * @return array|null
     */
    public function getExtra(): ?array {
        return $this->extra;
    }

    /**
     * Checks if it is OK
     */
    function check(): bool {
        $res = !is_null($this->tag) && strlen($this->getTag()) > 0 && strlen($this->tag) <= 128;
        Logging::debug("res: " . $res);
        return $res;
    }
    
    /**
     * Serialize into json
     * @return array
     */
    public function jsonSerialize() {
        $res = [
            'tag' => $this->tag,
            'config' => $this->config,
            'provider' => $this->provider,
            'description' => $this->description,
        ];
        if ($this->extra) {
            $res['extra'] = $this->extra;
        }
        return $res;
    }

}