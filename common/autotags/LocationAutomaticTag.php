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
use runnerupweb\common\autotags\RouteAutomaticTag;
use runnerupweb\data\Activity;
use runnerupweb\data\TagConfig;

/**
 * The LocationAutomaticTag is just a tag based on the first trackpoint in the
 * tag. The first point is got from the activity. Just lat, long and a
 * distance radius. By default 10 km radius is assigned.
 *
 * @author ricky
 */
class LocationAutomaticTag implements AutomaticTag {

    private function generateExtra(float $lat, float $lon, float $distance): array {
        return [
            ['name' => 'runnerupweb.Latitude',
             'html' => "<input id='latitude' type='number' step='any' required/>",
             'value' => $lat],
            ['name' => 'runnerupweb.Longitude',
             'html' => "<input id='longitude' type='number' step='any' required/>",
             'value' => $lon],
            ['name' => 'runnerupweb.Distance',
             'html' => "<input id='distance' type='number' step='any' required/>",
             'value' => $distance],
        ];
    }

    public function generateTagConfigWithExtra(Activity $activity): TagConfig {
        $tagConfig = new TagConfig();
        $tagConfig->setDescription('Location tag based on activity');
        $tagConfig->setProvider(__CLASS__);
        $firstPoint = $activity->getLaps()[0]->getTrackpoints()[0];
        $tagConfig->setExtra($this->generateExtra($firstPoint->getLatitude(), $firstPoint->getLongitude(), 10000));
        return $tagConfig;
    }

    public function convertConfigToExtra(TagConfig &$tagConfig): ?string {
        if (!$tagConfig->getConfig()) {
            return 'runnerupweb.autotag.invalid.config';
        }
        $json = json_decode($tagConfig->getConfig(), true);
        if (!$json || !array_key_exists('lat', $json)
                || !array_key_exists('lon', $json)
                || !array_key_exists('d', $json)) {
            return 'runnerupweb.autotag.invalid.config';
        }
        $tagConfig->setExtra($this->generateExtra($json['lat'], $json['lon'], $json['d']));
        $tagConfig->setConfig(null);
        return null;
    }

    public function convertExtraToConfig(TagConfig &$tagConfig): ?string {
        $extra = $tagConfig->getExtra();
        if (!is_array($extra) || !array_key_exists('runnerupweb.Latitude', $extra)
                || !array_key_exists('runnerupweb.Longitude', $extra)
                || !array_key_exists('runnerupweb.Distance', $extra)) {
            return 'runnerupweb.autotag.invalid.config';
        }
        $json = [
            'lat' => $extra['runnerupweb.Latitude'],
            'lon' => $extra['runnerupweb.Longitude'],
            'd' => $extra['runnerupweb.Distance']
        ];
        $tagConfig->setConfig(json_encode($json));
        return null;
    }

    public function isAssignable(Activity $activity, TagConfig $tagConfig): bool {
        if ($tagConfig->getProvider() !== __CLASS__) {
            return false;
        }
        $point1 = json_decode($tagConfig->getConfig(), true);
        $point2 = $activity->getLaps()[0]->getTrackpoints()[0];
        $d = RouteAutomaticTag::calculatedistance(floatval($point1['lat']), floatval($point1['lon']),
                $point2->getLatitude(), $point2->getLongitude());
        return $d < floatval($point1['d']);
    }

}
