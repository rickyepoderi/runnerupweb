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
 * Simple DistanceAutomaticTag tag.
 *
 * @author ricky
 */
class DistanceAutomaticTag implements AutomaticTag {

    private function generateExtra(float $greater, float $less): array {
        return [
            ['name' => 'runnerupweb.GreaterThanOrEqueal',
             'html' => "<input id='greaterThanOrEquals' type='number' step='any' required/>",
             'value' => $greater],
            ['name' => 'runnerupweb.lessThan',
             'html' => "<input id='lessThan' type='number' step='any' required/>",
             'value' => $less],
        ];
    }

    public function generateTagConfigWithExtra(Activity $activity): TagConfig {
        $tagConfig = new TagConfig();
        $tagConfig->setDescription('distance limit tag');
        $tagConfig->setProvider(__CLASS__);
        $distance = floatval($activity->getDistanceMeters());
        $tagConfig->setExtra($this->generateExtra($distance, 100000.0));
        return $tagConfig;
    }

    public function convertConfigToExtra(TagConfig &$tagConfig): ?string {
        if (!$tagConfig->getConfig()) {
            return 'runnerupweb.autotag.invalid.config';
        }
        $json = json_decode($tagConfig->getConfig(), true);
        if (!$json || !array_key_exists('greater', $json)
                || !array_key_exists('less', $json)) {
            return 'runnerupweb.autotag.invalid.config';
        }
        $tagConfig->setExtra($this->generateExtra($json['greater'], $json['less']));
        $tagConfig->setConfig(null);
        return null;
    }

    public function convertExtraToConfig(TagConfig &$tagConfig): ?string {
        $extra = $tagConfig->getExtra();
        if (!is_array($extra) || !array_key_exists('runnerupweb.GreaterThanOrEqueal', $extra)
                || !array_key_exists('runnerupweb.lessThan', $extra)) {
            return 'runnerupweb.autotag.invalid.config';
        }
        $json = [
            'greater' => $extra['runnerupweb.GreaterThanOrEqueal'],
            'less' => $extra['runnerupweb.lessThan']
        ];
        $tagConfig->setConfig(json_encode($json));
        return null;
    }

    public function isAssignable(Activity $activity, TagConfig $tagConfig): bool {
        if ($tagConfig->getProvider() !== __CLASS__) {
            return false;
        }
        $config = json_decode($tagConfig->getConfig(), true);
        $distanceAct = floatval($activity->getDistanceMeters());
        return floatval($config['greater']) <= $distanceAct && $distanceAct < floatval($config['less']);
    }
}
