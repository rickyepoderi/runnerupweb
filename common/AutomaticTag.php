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

namespace runnerupweb\common;

use runnerupweb\data\Activity;
use runnerupweb\data\TagConfig;

/**
 * The automatic tag is an interface to generate automatic tags. These tags
 * receive a full activity to create the template of the TagConfig that can
 * be customized by the user. Finally the method calculateTags returns the
 * tags automatically assigned by the tag,
 *
 * @author ricky
 */
interface AutomaticTag {
    
    /**
     * method that receives an activity to generate a TagConfig. This tagConfig
     * will be personalized by the user and then used to calculate tags. The extra
     * parameters can contain the extra values for the HTML display.
     * @param Activity $activity The activity used as template
     * @return TagConfig The initial tag config created
     */
    public function generateTagConfigWithExtra(Activity $activity): TagConfig;

    /**
     * Converts the tag configuration to html data in the extra.
     * @param TagConfig $tagConfig The tag config
     * @return string|null null if OK, a error string if error
     */
    public function convertConfigToExtra(TagConfig &$tagConfig): ?string;

    /**
     * Transforms the tag configuration with extra data into a valid
     * tag config with the configuration filled from the extras.
     * @param TagConfig $tagConfig The tag config with extras
     * @return string|null null if OK, a error string if error
     */
    public function convertExtraToConfig(TagConfig &$tagConfig): ?string;

    /**
     * Method that calculate is the tagConfig can be assigned to the activity.
     * config tag.
     * @param Activity $activity
     * @param TagConfig $tagConfig
     * @return bool true if assignable
     */
    public function isAssignable(Activity $activity, TagConfig $tagConfig): bool;
}