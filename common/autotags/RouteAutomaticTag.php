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
use runnerupweb\common\Configuration;
use runnerupweb\common\Logging;
use runnerupweb\data\Activity;
use runnerupweb\data\ActivityLap;
use runnerupweb\data\ActivityTrackpoint;
use runnerupweb\data\TagConfig;


/**
 * Automatica tag based in N points of the route. The idea is getting N
 * points of the activity euqualy spaced and then compare the points
 * with the new activity.
 *
 * @author ricky
 */
class RouteAutomaticTag implements AutomaticTag {

    private function generateExtra(string $value): array {
        return [
            ['name' => 'runnerupweb.route',
             'html' => "<textarea id='route' type='text' required disabled/>",
             'value' => $value]
        ];
    }

    private function foundLapAtDistance(Activity $activity, float $d): int {
        $ini = 0;
        $end = count($activity->getLaps()) - 1;
        $mid = intval(($ini + $end) / 2);
        do {
            Logging::debug("ini: $ini end: $end mid: $mid");
            $firstPoint = $activity->getLaps()[$mid]->getTrackpoints()[0];
            $lastPoint = $activity->getLaps()[$mid]->getTrackpoints()[count($activity->getLaps()[$mid]->getTrackpoints()) - 1];
            Logging::debug("ini: " . $firstPoint->getDistance() . " end: " . $lastPoint->getDistance() . " d: $d");
            if ($d < $firstPoint->getDistance()) {
                $end = $mid - 1;
            } else if ($d > $lastPoint->getDistance()) {
                $ini = $mid + 1;
            } else {
                return $mid;
            }
            $mid = intval(($ini + $end) / 2);
        } while ($ini < $end);
        return $mid;
    }

    private function foundTrackpointAtDistanceInLap(ActivityLap $lap, float $d): int {
        $ini = 0;
        $end = count($lap->getTrackpoints()) - 1;
        $mid = intval(($ini + $end) / 2);
        do {
            Logging::debug("ini: $ini end: $end mid: $mid <" . ($ini < $end));
            if ($d < $lap->getTrackpoints()[$mid]->getDistance()) {
                $end = $mid;
            } else {
                $ini = $mid;
            }
            $mid = intval(($ini + $end) / 2);
        } while ($ini + 1 < $end);
        // get the point nearer to d, ini or end
        if (abs($d - $lap->getTrackpoints()[$ini]->getDistance()) < abs($d - $lap->getTrackpoints()[$end]->getDistance())) {
            return $ini;
        } else {
            return $end;
        }
    }

    private function foundTrackpointAtDistance(Activity $activity, float $d): ?ActivityTrackpoint {
        $lap = $this->foundLapAtDistance($activity, $d);
        Logging::debug("Found lap=$lap");
        $track = $this->foundTrackpointAtDistanceInLap($activity->getLaps()[$lap], $d);
        Logging::debug("Found track=$track");
        return $activity->getLaps()[$lap]->getTrackpoints()[$track];
    }

    private function getActivityPoints(Activity $activity): array {
        $config = Configuration::getConfiguration();
        $total = $activity->getDistanceMeters();
        $distance = floatval($total) / floatval($config->getProperty('tags', 'automatic.route.points'));
        $d = 0.0;
        $points = [];
        array_push($points, $activity->getLaps()[0]->getTrackpoints()[0]);
        for ($i = 1; $i < $config->getProperty('tags', 'automatic.route.points') - 1; $i++) {
            $d = $d + $distance;
            array_push($points, $this->foundTrackpointAtDistance($activity, $d));
        }
        $lastLap = count($activity->getLaps()) - 1;
        $lastPoint =  count($activity->getLaps()[$lastLap]->getTrackpoints()) - 1;
        array_push($points, $activity->getLaps()[$lastLap]->getTrackpoints()[$lastPoint]);
        return $points;
    }

    private function generateConfigFromActivity(Activity $activity): string {
        $points = $this->getActivityPoints($activity);
        $res = [];
        for ($i = 0; $i < count($points); $i++) {
            $p = [];
            $p['d'] = $points[$i]->getDistance();
            $p['lat'] = $points[$i]->getLatitude();
            $p['lon'] = $points[$i]->getLongitude();
            array_push($res, $p);
        }
        return json_encode($res);
    }

    public function generateTagConfigWithExtra(Activity $activity): TagConfig {
        $tagConfig = new TagConfig();
        $tagConfig->setDescription('Route tag based on activity');
        $tagConfig->setProvider(__CLASS__);
        $json = $this->generateConfigFromActivity($activity);
        $tagConfig->setExtra($this->generateExtra($json));
        return $tagConfig;
    }

    public function convertConfigToExtra(TagConfig &$tagConfig): ?string {
        if (!$tagConfig->getConfig()) {
            return 'runnerupweb.autotag.invalid.config';
        }
        $tagConfig->setExtra($this->generateExtra($tagConfig->getConfig()));
        $tagConfig->setConfig(null);
        return null;
    }

    public function convertExtraToConfig(TagConfig &$tagConfig): ?string {
        $extra = $tagConfig->getExtra();
        if (!is_array($extra) || !array_key_exists('runnerupweb.route', $extra)) {
            return 'runnerupweb.autotag.invalid.config';
        }
        $tagConfig->setConfig($extra['runnerupweb.route']);
        return null;
    }

    public static function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float {
        // https://www.movable-type.co.uk/scripts/latlong.html
        // https://andrew.hedges.name/experiments/haversine/
        $lat1r = deg2rad($lat1);
        $lat2r = deg2rad($lat2);
        $dlon = deg2rad($lon2 - $lon1);
        $dlat = deg2rad($lat2 - $lat1);
        $a = pow(sin($dlat/2),2) + cos($lat1r) * cos($lat2r) * pow(sin($dlon/2),2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $d = 6373000.0 * $c;
        return $d;
    }

    private function comparePoints(array $points1, Activity $activity) {
        $config = Configuration::getConfiguration();
        $d1 = $points1[count($points1) - 1]['d'];
        $lastLap = count($activity->getLaps()) - 1;
        $lastPoint =  count($activity->getLaps()[$lastLap]->getTrackpoints()) - 1;
        $d2 = $activity->getLaps()[$lastLap]->getTrackpoints()[$lastPoint]->getDistance();
        $limit = $d1 * (floatval($config->getProperty('tags', 'automatic.route.limit.percent')) / 100.0);
        if (abs($d2 - $d1) > $limit) {
            Logging::debug("distance $d1 - $d1 > $limit");
            return false;
        } else {
            $points2 = $this->getActivityPoints($activity);
            $total = 0.0;
            for ($i = 0; $i < count($points1); $i++) {
                $d = RouteAutomaticTag::calculateDistance(floatval($points1[$i]['lat']), floatval($points1[$i]['lon']),
                        $points2[$i]->getLatitude(), $points2[$i]->getLongitude());
                Logging::debug("distance in $i=$d");
                $total = $total + $d;
            }
            Logging::debug("total=$total < limit=$limit");
            return $total < $limit;
        }
    }

    public function isAssignable(Activity $activity, TagConfig $tagConfig): bool {
        if ($tagConfig->getProvider() !== __CLASS__) {
            return false;
        }
        $points1 = json_decode($tagConfig->getConfig(), true);
        if (!$points1) {
            return false;
        }
        return $this->comparePoints($points1, $activity);
    }

}
