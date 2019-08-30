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

require_once __DIR__ . '/../common/TCXManager.php';
require_once __DIR__ . '/../data/Activity.php';
require_once __DIR__ . '/../data/ActivityLap.php';

use runnerupweb\common\TCXManager;
use runnerupweb\common\Logging;
use PHPUnit\Framework\TestCase;

/**
 * Description of LoggingTest
 *
 * @author ricky
 */
class TCXManagerTest extends TestCase {

    public static function setUpBeforeClass(): void {
        Logging::initForceLogger("/tmp");
        TCXManager::initTCXManager("/tmp", 100, false);
    }

    public static function tearDownAfterClass(): void {
        // noop
    }
    
    public function testParse(): void {
        $tcx = TCXManager::getTCXManager();
        $activities = $tcx->parse(__DIR__ . '/test1.xml');
        $this->assertEquals(sizeof($activities), 1);
        $activity = $activities[0];
        $this->assertEquals($activity->getStartTimeAsXML(), "2015-09-19T18:25:00Z");
        $this->assertEquals($activity->getNotes(), "Notes...");
        $this->assertEquals($activity->getSport(), "Running");
        $this->assertEquals($activity->getTotalTimeSeconds(), 1500.0);
        $this->assertEquals($activity->getDistanceMeters(), 4300.0);
        $this->assertEquals($activity->getMaximumSpeed(), 33.7);
        $this->assertEquals($activity->getCalories(), 250);
        $this->assertEquals($activity->getAverageHeartRateBpm(), 85);
        $this->assertEquals($activity->getMaximumHeartRateBpm(), 90);
    }
    
    public function testSplit(): void {
        $tcx = TCXManager::getTCXManager();
        $files = $tcx->split(__DIR__ . '/test1.xml');
        $this->assertEquals(sizeof($files), 1);
        $activities = $tcx->parse($files[0]);
        $this->assertEquals(sizeof($activities), 1);
        $activity = $activities[0];
        $this->assertEquals($activity->getStartTimeAsXML(), "2015-09-19T18:25:00Z");
        $this->assertEquals($activity->getNotes(), "Notes...");
        $this->assertEquals($activity->getSport(), "Running");
        $this->assertEquals($activity->getTotalTimeSeconds(), 1500.0);
        $this->assertEquals($activity->getDistanceMeters(), 4300.0);
        $this->assertEquals($activity->getMaximumSpeed(), 33.7);
        $this->assertEquals($activity->getCalories(), 250);
        $this->assertEquals($activity->getAverageHeartRateBpm(), 85);
        $this->assertEquals($activity->getMaximumHeartRateBpm(), 90);
        $this->assertTrue(unlink($files[0]));
    }
    
    public function testStorage(): void {
        $tcx = TCXManager::getTCXManager();
        copy(__DIR__ . '/test1.xml', "/tmp/1.xml");
        $tcx->store("ricky", 1, "/tmp/1.xml");
        $this->assertEquals("/tmp/ricky/001/1.tcx", $tcx->get("ricky", 1));
        $tcx->delete("ricky", 1);
        $this->assertNull($tcx->get("ricky", 1));
        $this->assertTrue(rmdir("/tmp/ricky/001"));
        $this->assertTrue(rmdir("/tmp/ricky"));
    }

    public function testParse2(): void {
        $tcx = TCXManager::getTCXManager();
        $activities = $tcx->parse(__DIR__ . '/runnerup.tcx');
        $this->assertEquals(count($activities), 1);
        $this->assertEquals(3, count($activities[0]->getLaps()));
        $this->assertEquals(801, count($activities[0]->getLaps()[0]->getTrackpoints()));
        $this->assertEquals(792, count($activities[0]->getLaps()[1]->getTrackpoints()));
        $this->assertEquals(181, count($activities[0]->getLaps()[2]->getTrackpoints()));
        $this->assertNotNull($activities[0]->getLaps()[0]->getTrackpoints()[0]->getTime());
        $this->assertNotNull($activities[0]->getLaps()[0]->getTrackpoints()[0]->getLatitude());
        $this->assertNotNull($activities[0]->getLaps()[0]->getTrackpoints()[0]->getLongitude());
        $this->assertNotNull($activities[0]->getLaps()[0]->getTrackpoints()[0]->getAltitude());
        $this->assertNotNull($activities[0]->getLaps()[0]->getTrackpoints()[0]->getHeartRate());
    }
    
}
