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

use runnerupweb\common\ActivityManager;
use runnerupweb\common\UserManager;
use runnerupweb\common\TagManager;
use runnerupweb\data\User;
use runnerupweb\data\TagConfig;
use runnerupweb\common\Configuration;
use PHPUnit\Framework\TestCase;

/**
 * Description of ActivityManagerTest
 *
 * @author ricky
 */
class ActivityManagerTest extends TestCase {
    
    public static function setUpBeforeClass(): void {
        Configuration::getConfiguration();
    }

    public static function tearDownAfterClass(): void {
        // noop
    }
    
    private function createUser() {
        $user = User::userWithLogin('testam');
        $user->setPassword('testam');
        $user->setFirstname('testam');
        $user->setLastname('testam');
        $user->setEmail('testam@lala.com');
        $user->setRole(User::USER_ROLE);
        return $user;
    }
    
    public function testStore() {
        $am = ActivityManager::getActivityManager();
        $um = UserManager::getUserManager();
        $tm = TagManager::getTagManager();
        // create a user in the database
        $user = $this->createUser();
        $um->createUser($user);
        // store the activity
        $activities = $am->storeActivities($user->getLogin(), __DIR__ . '/runnerup.tcx', 'runnerup.tcx');
        $this->assertEquals(1, sizeof($activities));
        $activities[0]->clear();
        // check the activity exists in the database
        $activity = $am->getActivity($user->getLogin(), $activities[0]->getId());
        $this->assertNotNull($activity);
        $this->assertEquals($activity, $activities[0]);
        // check the file is created an associated correctly
        $this->assertNotNull($am->getActivityFile($user->getLogin(), $activity->getId()));
        // search the activity with only start date
        $res = $am->searchActivities($user->getLogin(), DateTime::createFromFormat('Y-m-d H:i:s', '2015-09-19 00:00:00'));
        $this->assertEquals(1, count($res));
        $this->assertEquals($res[0], $activities[0]);
        // search using start and end dates
        $res = $am->searchActivities($user->getLogin(), 
                DateTime::createFromFormat('Y-m-d H:i:s', '2015-09-19 00:00:00'),
                DateTime::createFromFormat('Y-m-d H:i:s', '2015-09-20 00:00:00'));
        $this->assertEquals(1, count($res));
        $this->assertEquals($res[0], $activities[0]);
        // assign the tag and search again
        $this->assertNotNull($tm->createTagConfig($user->getLogin(), TagConfig::tagConfigWithDescription('tag-test', 'tag-test')));
        $this->assertTrue($tm->assignTagToActivity($user->getLogin(), $activities[0]->getId(), 'tag-test'));
        $res = $am->searchActivities($user->getLogin(),
                DateTime::createFromFormat('Y-m-d H:i:s', '2015-09-19 00:00:00'),
                DateTime::createFromFormat('Y-m-d H:i:s', '2015-09-20 00:00:00'),
                'tag-test');
        $this->assertEquals(1, count($res));
        $this->assertEquals($res[0], $activities[0]);
        // re-read the activity from the file
        $activity = $am->getActivityFromFile($user->getLogin(), $activities[0]->getId());
        $this->assertNotNull($activity);
        $this->assertNotNull($activity->getLaps());
        $this->assertTrue(count($activity->getLaps()) > 0);
        // recalculate the tags
        $tagConfig = TagConfig::tagConfigWithDescription('running', 'running automatic tag');
        $tagConfig->setConfig('Running');
        $tagConfig->setProvider('runnerupweb\common\autotags\SportActivityAutomaticTag');
        $this->assertNotNull($tm->createTagConfig($user->getLogin(), $tagConfig));
        $this->assertTrue($am->recalculateTagsInActivity($user->getLogin(), $activity->getId(), true));
        $res = $am->searchActivities($user->getLogin(),
                DateTime::createFromFormat('Y-m-d H:i:s', '2015-09-19 00:00:00'),
                DateTime::createFromFormat('Y-m-d H:i:s', '2015-09-20 00:00:00'),
                'tag-test');
        $this->assertEquals(1, count($res));
        $this->assertEquals($activity->getId(), $res[0]->getId());
        // delete the activity
        $this->assertTrue($am->deleteActivity($user->getLogin(), $activities[0]->getId()));
        $this->assertNull($am->getActivityFile($user->getLogin(), $activity->getId()));
        $this->assertNull($am->getActivityFile($user->getLogin(), $activity->getId()));
        // delete folder for the user
        $am->deleteUserActivities($user->getLogin());
        $this->assertFalse(file_exists('/tmp/' . $user->getLogin()));
        // delete the user
        $this->assertTrue($um->deleteUser($user->getLogin()));
    }
    
}
