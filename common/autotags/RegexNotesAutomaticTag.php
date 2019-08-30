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

namespace runnerupweb\common\autotags;

use runnerupweb\common\AutomaticTag;
use runnerupweb\data\Activity;
use runnerupweb\data\TagConfig;

/**
 * Simple automatic tag that uses the activity notes to create a tag. Uses
 * a regex to check if the activity notes contains that regexp.
 *
 * @author ricky
 */
class RegexNotesAutomaticTag implements AutomaticTag {

    private function generateExtra(string $value): array {
        return [
            ['name' => 'runnerupweb.regex',
             'html' => "<input id='regex' type='text' required/>",
             'value' => $value]
        ];
    }

    /**
     * Creates a tag config with the sport type. The tag name is the sport name
     * in lower case and the config is just the sport type.
     * @param Activity $activity
     * @return TagConfig
     */
    public function generateTagConfigWithExtra(Activity $activity): TagConfig {
        $tagConfig = TagConfig::tagConfigWithDescription(null, 'Regex automatic tag');
        $tagConfig->setExtra($this->generateExtra('/' . $activity->getNotes() . '/i'));
        $tagConfig->setProvider(__CLASS__);
        return $tagConfig;
    }

    /**
     * Converts the tag configuration to html data in the extra.
     * @param TagConfig $tagConfig The tag config
     * @return string|null null if OK, a error string if error
     */
    
    public function convertConfigToExtra(TagConfig &$tagConfig): ?string {
        if (!$tagConfig->getConfig()) {
            return 'runnerupweb.autotag.invalid.config';
        }
        $tagConfig->setExtra($this->generateExtra($tagConfig->getConfig()));
        $tagConfig->setConfig(null);
        return null;
    }

    /**
     * Transforms the tag configuration with extra data into a valid
     * tag config with the configuration filled from the extras.
     * @param TagConfig $tagConfig The tag config with extras
     * @return string|null null if OK, a error string if error
     */
    public function convertExtraToConfig(TagConfig &$tagConfig): ?string {
        $extra = $tagConfig->getExtra();
        if (!is_array($extra) || !array_key_exists('runnerupweb.regex', $extra)) {
            return 'runnerupweb.autotag.invalid.config';
        }
        $tagConfig->setConfig($extra['runnerupweb.regex']);
        return null;
    }

    /**
     * It just returns true if the notes matches the regex.
     * @param Activity $activity
     * @param TagConfig $tagConfig
     * @return bool true if the tag config is assignable to the activity
     */
    public function isAssignable(Activity $activity, TagConfig $tagConfig): bool {
        if ($tagConfig->getProvider() !== __CLASS__) {
            return false;
        }
        if (preg_match($tagConfig->getConfig(), $activity->getNotes())) {
            return true;
        } else {
            return false;
        }
    }
}