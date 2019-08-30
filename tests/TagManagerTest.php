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

use runnerupweb\common\ActivityManager;
use runnerupweb\common\UserManager;
use runnerupweb\common\TagManager;
use runnerupweb\common\autotags\SportActivityAutomaticTag;
use runnerupweb\common\autotags\RegexNotesAutomaticTag;
use runnerupweb\common\autotags\RouteAutomaticTag;
use runnerupweb\common\autotags\LocationAutomaticTag;
use runnerupweb\common\autotags\AverageSpeedAutomaticTag;
use runnerupweb\common\autotags\DistanceAutomaticTag;
use runnerupweb\data\User;
use runnerupweb\data\TagConfig;
use runnerupweb\common\Configuration;
use PHPUnit\Framework\TestCase;

class TagManagerTest extends TestCase {

    public static function setUpBeforeClass(): void {
        Configuration::getConfiguration();
    }

    public static function tearDownAfterClass(): void {
        // noop
    }

    private function createUser(): User {
        $user = User::userWithLogin('testtm');
        $user->setPassword('testtm');
        $user->setFirstname('testtm');
        $user->setLastname('testtm');
        $user->setEmail('testtm@lala.com');
        $user->setRole(User::USER_ROLE);
        return $user;
    }

    private function createTagConfig(): TagConfig {
        $tagConfig = TagConfig::tagConfigWithDescription('tag-test', 'tag-test-description');
        $tagConfig->setConfig('tag-test-config');
        $tagConfig->setProvider('tag-test-provider');
        return $tagConfig;
    }

    private function isInArray(string $tag, bool $auto, array $tags): bool {
        foreach ($tags as $t) {
            if ($t->getTag() === $tag && $t->isAuto() === $auto) {
                return true;
            }
        }
        return false;
    }

    private function checkTagConfigs(TagConfig $tc1, TagConfig $tc2): void {
        $this->assertEquals($tc1->getTag(), $tc2->getTag());
        if (!is_null($tc1->getDescription())) {
            $this->assertEquals($tc1->getDescription(), $tc2->getDescription());
        } else {
            $this->assertNull($tc2->getDescription());
        }
        if (!is_null($tc1->getProvider())) {
            $this->assertEquals($tc1->getProvider(), $tc2->getProvider());
        } else {
            $this->assertNull($tc2->getProvider());
        }
        if (!is_null($tc1->getConfig())) {
            $this->assertEquals($tc1->getConfig(), $tc2->getConfig());
        } else {
            $this->assertNull($tc2->getConfig());
        }
    }

    public function testTagConfig() {
        $um = UserManager::getUserManager();
        $tm = TagManager::getTagManager();
        $user = $this->createUser();
        $um->createUser($user);
        $tagConfig = $this->createTagConfig();
        // create
        $tc = $tm->createTagConfig($user->getLogin(), $tagConfig);
        $this->checkTagConfigs($tagConfig, $tc);
        // get and check is ok
        $tc = $tm->getTagConfig($user->getLogin(), $tagConfig->getTag());
        $this->assertNotNull($tc);
        $this->checkTagConfigs($tagConfig, $tc);
        // update
        $tagConfig->setDescription('tag-test-description-2');
        $tagConfig->setConfig('tag-test-config-2');
        $tagConfig->setProvider('tag-test-provider-2');
        $tc = $tm->updateTagConfig($user->getLogin(), $tagConfig);
        $this->checkTagConfigs($tagConfig, $tc);
        // get and check again
        $tc = $tm->getTagConfig($user->getLogin(), $tagConfig->getTag());
        $this->assertNotNull($tc);
        $this->checkTagConfigs($tagConfig, $tc);
        // get all tag configs
        $tcs = $tm->getTagConfigsForUser($user->getLogin());
        $this->assertEquals(1, count($tcs));
        $this->checkTagConfigs($tagConfig, $tcs[0]);
        // get all tag configs automatic
        $tcs = $tm->getTagConfigsForUser($user->getLogin(), true);
        $this->assertEquals(1, count($tcs));
        $this->checkTagConfigs($tagConfig, $tcs[0]);
        // delete
        $this->assertTrue($tm->deleteTagConfig($user->getLogin(), $tagConfig->getTag()));
        // get null
        $tc = $tm->getTagConfig($user->getLogin(), $tagConfig->getTag());
        $this->assertNull($tc);
        // delete the user
        $this->assertTrue($um->deleteUser($user->getLogin()));
    }

    public function testCommonTags() {
        $um = UserManager::getUserManager();
        $am = ActivityManager::getActivityManager();
        $tm = TagManager::getTagManager();
        // create the user and the activity
        $user = $this->createUser();
        $um->createUser($user);
        $activities = $am->storeActivities($user->getLogin(), __DIR__ . '/runnerup.tcx', 'runnerup.tcx');
        $this->assertEquals(1, sizeof($activities));
        $activity = $activities[0];
        $tags = $tm->getActivityTags($user->getLogin(), $activity->getId());
        $this->assertEquals(0, count($tags));
        // create the tag configs
        $tm->createTagConfig($user->getLogin(), TagConfig::tagConfigWithDescription('tag-one', 'tag-one'));
        $tm->createTagConfig($user->getLogin(), TagConfig::tagConfigWithDescription('tag-two', 'tag-two'));
        // get all tag configs
        $tags = $tm->getAllTags($user->getLogin());
        $this->assertEquals(2, count($tags));
        $this->assertTrue($this->isInArray('tag-one', false, $tags));
        $this->assertTrue($this->isInArray('tag-two', false, $tags));
        // assign a tag
        $this->assertTrue($tm->assignTagToActivity($user->getLogin(), $activity->getId(), "tag-one"));
        $tags = $tm->getActivityTags($user->getLogin(), $activity->getId());
        $this->assertEquals(1, count($tags));
        $this->assertTrue($this->isInArray('tag-one', false, $tags));
        // assign a second tag
        $this->assertTrue($tm->assignTagToActivity($user->getLogin(), $activity->getId(), "tag-two"));
        $tags = $tm->getActivityTags($user->getLogin(), $activity->getId());
        $this->assertEquals(2, count($tags));
        $this->assertTrue($this->isInArray('tag-one', false, $tags));
        $this->assertTrue($this->isInArray('tag-two', false, $tags));
        // remove first tag
        $this->assertTrue($tm->removeTagFromActivity($user->getLogin(), $activity->getId(), "tag-one"));
        $tags = $tm->getActivityTags($user->getLogin(), $activity->getId());
        $this->assertEquals(1, count($tags));
        $this->assertTrue($this->isInArray('tag-two', false, $tags));
        // remove second tag
        $this->assertTrue($tm->removeTagFromActivity($user->getLogin(), $activity->getId(), "tag-two"));
        $tags = $tm->getActivityTags($user->getLogin(), $activity->getId());
        $this->assertEquals(0, count($tags));
        // remove the configs
        $this->assertTrue($tm->deleteTagConfig($user->getLogin(), 'tag-one'));
        $tags = $tm->getAllTags($user->getLogin());
        $this->assertEquals(1, count($tags));
        $this->assertTrue($this->isInArray('tag-two', false, $tags));
        $this->assertTrue($tm->deleteTagConfig($user->getLogin(), 'tag-two'));
        $tags = $tm->getAllTags($user->getLogin());
        $this->assertEquals(0, count($tags));
        // removethe user and the activity
        $this->assertTrue($am->deleteUserActivities($user->getLogin()));
        $this->assertTrue($um->deleteUser($user->getLogin()));
    }

    public function testSportActivityAutomaticTag() {
        $um = UserManager::getUserManager();
        $am = ActivityManager::getActivityManager();
        $tm = TagManager::getTagManager();
        // create the user
        $user = $this->createUser();
        $um->createUser($user);
        $tm->createTagConfig($user->getLogin(), TagConfig::tagConfigWithDescription('tag-one', 'tag-one'));
        // create the activity
        $activities = $am->storeActivities($user->getLogin(), __DIR__ . '/runnerup.tcx', 'runnerup.tcx');
        $this->assertEquals(1, sizeof($activities));
        $activity = $activities[0];
        // create the sport automatic tag config
        $sport = new SportActivityAutomaticTag();
        $tagConfig = $sport->generateTagConfigWithExtra($activity);
        $this->assertNotNull($tagConfig->getExtra());
        $this->assertEquals(1, count($tagConfig->getExtra()));
        $this->assertEquals(strtolower($activity->getSport()), $tagConfig->getTag());
        $this->assertEquals('runnerupweb\common\autotags\SportActivityAutomaticTag', $tagConfig->getProvider());
        $this->assertNotNull($tagConfig->getDescription());
        // fill the extra data
        $tagConfig->setExtra(array('runnerupweb.Sport' => $activity->getSport()));
        $this->assertNull($sport->convertExtraToConfig($tagConfig));
        $this->assertEquals($activity->getSport(), $tagConfig->getConfig());
        // store the tag config
        $this->assertNotNull($tm->createTagConfig($user->getLogin(), $tagConfig));
        // store the same activity again and the tag should be automatically created
        $activities = $am->storeActivities($user->getLogin(), __DIR__ . '/runnerup.tcx', 'runnerup.tcx');
        $this->assertEquals(1, sizeof($activities));
        $activity = $activities[0];
        // check the tags
        $tags = $tm->getActivityTags($user->getLogin(), $activity->getId());
        $this->assertEquals(1, count($tags));
        $this->assertTrue($this->isInArray($tagConfig->getTag(), true, $tags));
        // add the common tag
        $this->assertTrue($tm->assignTagToActivity($user->getLogin(), $activity->getId(), 'tag-one'));
        $tags = $tm->getActivityTags($user->getLogin(), $activity->getId());
        $this->assertEquals(2, count($tags));
        $this->assertTrue($this->isInArray($tagConfig->getTag(), true, $tags));
        $this->assertTrue($this->isInArray('tag-one', false, $tags));
        // change the type
        $tagConfig->setConfig('anothertype');
        $this->assertNotNull($tm->updateTagConfig($user->getLogin(), $tagConfig));
        $tm->calculateAutomaticTags($user->getLogin(), $activity, true);
        // now it should be deleted
        $tags = $tm->getActivityTags($user->getLogin(), $activity->getId());
        $this->assertEquals(1, count($tags));
        $this->assertTrue($this->isInArray('tag-one', false, $tags));
        // removethe user and the activity
        $this->assertTrue($am->deleteUserActivities($user->getLogin()));
        $this->assertTrue($um->deleteUser($user->getLogin()));
    }

    public function testRegexNotesActivityAutomaticTag() {
        $um = UserManager::getUserManager();
        $am = ActivityManager::getActivityManager();
        $tm = TagManager::getTagManager();
        // create the user
        $user = $this->createUser();
        $um->createUser($user);
        $tm->createTagConfig($user->getLogin(), TagConfig::tagConfigWithDescription('tag-one', 'tag-one'));
        // create the activity
        $activities = $am->storeActivities($user->getLogin(), __DIR__ . '/runnerup.tcx', 'runnerup.tcx');
        $this->assertEquals(1, sizeof($activities));
        $activity = $activities[0];
        // create the sport automatic tag config
        $regex = new RegexNotesAutomaticTag();
        $tagConfig = $regex->generateTagConfigWithExtra($activity);
        $this->assertNotNull($tagConfig->getExtra());
        $this->assertEquals(1, count($tagConfig->getExtra()));
        $this->assertNull($tagConfig->getTag());
        $this->assertEquals('runnerupweb\common\autotags\RegexNotesAutomaticTag', $tagConfig->getProvider());
        $this->assertNotNull($tagConfig->getDescription());
        // fill the extra data
        $tagConfig->setTag('walking');
        $tagConfig->setExtra(array('runnerupweb.regex' => "/walking/i"));
        $this->assertNull($regex->convertExtraToConfig($tagConfig));
        $this->assertEquals("/walking/i", $tagConfig->getConfig());
        // store the tag config
        $tagConfig->setConfig("/walking/i");
        $this->assertNotNull($tm->createTagConfig($user->getLogin(), $tagConfig));
        // read the tag config without error
        $tagConfig = $tm->getTagConfig($user->getLogin(), $tagConfig->getTag());
        $this->assertNull($regex->convertConfigToExtra($tagConfig));
        $this->assertNotNull($tagConfig->getExtra());
        $this->assertEquals(1, count($tagConfig->getExtra()));
        // store the same activity again and the tag should be automatically created
        $activities = $am->storeActivities($user->getLogin(), __DIR__ . '/runnerup.tcx', 'runnerup.tcx');
        $this->assertEquals(1, sizeof($activities));
        $activity = $activities[0];
        // check the tags
        $tags = $tm->getActivityTags($user->getLogin(), $activity->getId());
        $this->assertEquals(1, count($tags));
        $this->assertTrue($this->isInArray($tagConfig->getTag(), true, $tags));
        // removethe user and the activity
        $this->assertTrue($am->deleteUserActivities($user->getLogin()));
        $this->assertTrue($um->deleteUser($user->getLogin()));
    }

    public function testRouteActivityAutomaticTag() {
        $um = UserManager::getUserManager();
        $am = ActivityManager::getActivityManager();
        $tm = TagManager::getTagManager();
        // create the user
        $user = $this->createUser();
        $um->createUser($user);
        $tm->createTagConfig($user->getLogin(), TagConfig::tagConfigWithDescription('tag-one', 'tag-one'));
        // create the activity
        $activities = $am->storeActivities($user->getLogin(), __DIR__ . '/runnerup.tcx', 'runnerup.tcx');
        $this->assertEquals(1, sizeof($activities));
        $activity = $activities[0];
        // create the sport automatic tag config
        $route = new RouteAutomaticTag();
        $tagConfig = $route->generateTagConfigWithExtra($activity);
        $this->assertNotNull($tagConfig->getExtra());
        $this->assertEquals(1, count($tagConfig->getExtra()));
        $this->assertNull($tagConfig->getTag());
        $this->assertEquals('runnerupweb\common\autotags\RouteAutomaticTag', $tagConfig->getProvider());
        $this->assertNotNull($tagConfig->getDescription());
        // fill the extra data
        $tagConfig->setTag('route');
        $tagConfig->setExtra(array('runnerupweb.route' => $tagConfig->getExtra()[0]['value']));
        $this->assertNull($route->convertExtraToConfig($tagConfig));
        $this->assertNotNull($tagConfig->getConfig());
        // store the tag config
        $this->assertNotNull($tm->createTagConfig($user->getLogin(), $tagConfig));
        // read the tag config without error
        $tagConfig = $tm->getTagConfig($user->getLogin(), $tagConfig->getTag());
        $this->assertNull($route->convertConfigToExtra($tagConfig));
        $this->assertNotNull($tagConfig->getExtra());
        $this->assertEquals(1, count($tagConfig->getExtra()));
        // store the same activity again and the tag should be automatically created
        $activities = $am->storeActivities($user->getLogin(), __DIR__ . '/runnerup.tcx', 'runnerup.tcx');
        $this->assertEquals(1, sizeof($activities));
        $activity = $activities[0];
        // check the tags
        $tags = $tm->getActivityTags($user->getLogin(), $activity->getId());
        $this->assertEquals(1, count($tags));
        $this->assertTrue($this->isInArray($tagConfig->getTag(), true, $tags));
        // removethe user and the activity
        $this->assertTrue($am->deleteUserActivities($user->getLogin()));
        $this->assertTrue($um->deleteUser($user->getLogin()));
    }

    public function testLocationActivityAutomaticTag() {
        $um = UserManager::getUserManager();
        $am = ActivityManager::getActivityManager();
        $tm = TagManager::getTagManager();
        // create the user
        $user = $this->createUser();
        $um->createUser($user);
        $tm->createTagConfig($user->getLogin(), TagConfig::tagConfigWithDescription('tag-one', 'tag-one'));
        // create the activity
        $activities = $am->storeActivities($user->getLogin(), __DIR__ . '/runnerup.tcx', 'runnerup.tcx');
        $this->assertEquals(1, sizeof($activities));
        $activity = $activities[0];
        // create the sport automatic tag config
        $location = new LocationAutomaticTag();
        $tagConfig = $location->generateTagConfigWithExtra($activity);
        $this->assertNotNull($tagConfig->getExtra());
        $this->assertEquals(3, count($tagConfig->getExtra()));
        $this->assertNull($tagConfig->getTag());
        $this->assertEquals('runnerupweb\common\autotags\LocationAutomaticTag', $tagConfig->getProvider());
        $this->assertNotNull($tagConfig->getDescription());
        // fill the extra data
        $tagConfig->setTag('location');
        $tagConfig->setExtra(array(
            'runnerupweb.Latitude' => $tagConfig->getExtra()[0]['value'],
            'runnerupweb.Longitude' => $tagConfig->getExtra()[1]['value'],
            'runnerupweb.Distance' => $tagConfig->getExtra()[2]['value'])
        );
        $this->assertNull($location->convertExtraToConfig($tagConfig));
        $this->assertNotNull($tagConfig->getConfig());
        // store the tag config
        $this->assertNotNull($tm->createTagConfig($user->getLogin(), $tagConfig));
        // read the tag config without error
        $tagConfig = $tm->getTagConfig($user->getLogin(), $tagConfig->getTag());
        $this->assertNull($location->convertConfigToExtra($tagConfig));
        $this->assertNotNull($tagConfig->getExtra());
        $this->assertEquals(3, count($tagConfig->getExtra()));
        // store the same activity again and the tag should be automatically created
        $activities = $am->storeActivities($user->getLogin(), __DIR__ . '/runnerup.tcx', 'runnerup.tcx');
        $this->assertEquals(1, sizeof($activities));
        $activity = $activities[0];
        // check the tags
        $tags = $tm->getActivityTags($user->getLogin(), $activity->getId());
        $this->assertEquals(1, count($tags));
        $this->assertTrue($this->isInArray($tagConfig->getTag(), true, $tags));
        // removethe user and the activity
        $this->assertTrue($am->deleteUserActivities($user->getLogin()));
        $this->assertTrue($um->deleteUser($user->getLogin()));
    }

    public function testAverageSpeedActivityAutomaticTag() {
        $um = UserManager::getUserManager();
        $am = ActivityManager::getActivityManager();
        $tm = TagManager::getTagManager();
        // create the user
        $user = $this->createUser();
        $um->createUser($user);
        $tm->createTagConfig($user->getLogin(), TagConfig::tagConfigWithDescription('tag-one', 'tag-one'));
        // create the activity
        $activities = $am->storeActivities($user->getLogin(), __DIR__ . '/runnerup.tcx', 'runnerup.tcx');
        $this->assertEquals(1, sizeof($activities));
        $activity = $activities[0];
        // create the sport automatic tag config
        $avgSpeed = new AverageSpeedAutomaticTag();
        $tagConfig = $avgSpeed->generateTagConfigWithExtra($activity);
        $this->assertNotNull($tagConfig->getExtra());
        $this->assertEquals(2, count($tagConfig->getExtra()));
        $this->assertNull($tagConfig->getTag());
        $this->assertEquals('runnerupweb\common\autotags\AverageSpeedAutomaticTag', $tagConfig->getProvider());
        $this->assertNotNull($tagConfig->getDescription());
        // fill the extra data
        $tagConfig->setTag('location');
        $tagConfig->setExtra(array(
            'runnerupweb.GreaterThanOrEqueal' => $tagConfig->getExtra()[0]['value'],
            'runnerupweb.lessThan' => $tagConfig->getExtra()[1]['value'])
        );
        $this->assertNull($avgSpeed->convertExtraToConfig($tagConfig));
        $this->assertNotNull($tagConfig->getConfig());
        // store the tag config
        $this->assertNotNull($tm->createTagConfig($user->getLogin(), $tagConfig));
        // read the tag config without error
        $tagConfig = $tm->getTagConfig($user->getLogin(), $tagConfig->getTag());
        $this->assertNull($avgSpeed->convertConfigToExtra($tagConfig));
        $this->assertNotNull($tagConfig->getExtra());
        $this->assertEquals(2, count($tagConfig->getExtra()));
        // store the same activity again and the tag should be automatically created
        $activities = $am->storeActivities($user->getLogin(), __DIR__ . '/runnerup.tcx', 'runnerup.tcx');
        $this->assertEquals(1, sizeof($activities));
        $activity = $activities[0];
        // check the tags
        $tags = $tm->getActivityTags($user->getLogin(), $activity->getId());
        $this->assertEquals(1, count($tags));
        $this->assertTrue($this->isInArray($tagConfig->getTag(), true, $tags));
        // removethe user and the activity
        $this->assertTrue($am->deleteUserActivities($user->getLogin()));
        $this->assertTrue($um->deleteUser($user->getLogin()));
    }

    public function testDistanceActivityAutomaticTag() {
        $um = UserManager::getUserManager();
        $am = ActivityManager::getActivityManager();
        $tm = TagManager::getTagManager();
        // create the user
        $user = $this->createUser();
        $um->createUser($user);
        $tm->createTagConfig($user->getLogin(), TagConfig::tagConfigWithDescription('tag-one', 'tag-one'));
        // create the activity
        $activities = $am->storeActivities($user->getLogin(), __DIR__ . '/runnerup.tcx', 'runnerup.tcx');
        $this->assertEquals(1, sizeof($activities));
        $activity = $activities[0];
        // create the sport automatic tag config
        $distance = new DistanceAutomaticTag();
        $tagConfig = $distance->generateTagConfigWithExtra($activity);
        $this->assertNotNull($tagConfig->getExtra());
        $this->assertEquals(2, count($tagConfig->getExtra()));
        $this->assertNull($tagConfig->getTag());
        $this->assertEquals('runnerupweb\common\autotags\DistanceAutomaticTag', $tagConfig->getProvider());
        $this->assertNotNull($tagConfig->getDescription());
        // fill the extra data
        $tagConfig->setTag('location');
        $tagConfig->setExtra(array(
            'runnerupweb.GreaterThanOrEqueal' => $tagConfig->getExtra()[0]['value'],
            'runnerupweb.lessThan' => $tagConfig->getExtra()[1]['value'])
        );
        $this->assertNull($distance->convertExtraToConfig($tagConfig));
        $this->assertNotNull($tagConfig->getConfig());
        // store the tag config
        $this->assertNotNull($tm->createTagConfig($user->getLogin(), $tagConfig));
        // read the tag config without error
        $tagConfig = $tm->getTagConfig($user->getLogin(), $tagConfig->getTag());
        $this->assertNull($distance->convertConfigToExtra($tagConfig));
        $this->assertNotNull($tagConfig->getExtra());
        $this->assertEquals(2, count($tagConfig->getExtra()));
        // store the same activity again and the tag should be automatically created
        $activities = $am->storeActivities($user->getLogin(), __DIR__ . '/runnerup.tcx', 'runnerup.tcx');
        $this->assertEquals(1, sizeof($activities));
        $activity = $activities[0];
        // check the tags
        $tags = $tm->getActivityTags($user->getLogin(), $activity->getId());
        $this->assertEquals(1, count($tags));
        $this->assertTrue($this->isInArray($tagConfig->getTag(), true, $tags));
        // removethe user and the activity
        $this->assertTrue($am->deleteUserActivities($user->getLogin()));
        $this->assertTrue($um->deleteUser($user->getLogin()));
    }
}