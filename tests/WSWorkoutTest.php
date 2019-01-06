<?php

/*
 * Copyright (C) 2016 <https://github.com/rickyepoderi/runnerupweb>
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

use runnerupweb\data\User;
use runnerupweb\common\UserManager;
use runnerupweb\common\TCXManager;
use runnerupweb\common\Configuration;
use runnerupweb\common\Client;
use runnerupweb\data\LoginResponse;
use runnerupweb\data\UserResponse;
use runnerupweb\data\ActivitySearchResponse;
use PHPUnit\Framework\TestCase;

/**
 * Description of WSWorkoutTest
 *
 * @author ricky
 */
class WSWorkoutTest extends TestCase {
    
    public static function setUpBeforeClass() {
        Configuration::getConfiguration();
        $um = UserManager::getUserManager();
        $admin = WSWorkoutTest::createAdminUser();
        $um->createUser($admin);
        // create the normal user using WS
        $test = new WSWorkoutTest();
        $c = new Client('http://localhost/runnerupweb');
        $c->login($admin->getLogin(), $admin->getPassword());
        $c->setUser($test->createUser());
        $c->logout();
    }

    public static function tearDownAfterClass() {
        // delete the common user and store
        $admin = WSWorkoutTest::createAdminUser();
        $test = new WSWorkoutTest();
        $c = new Client('http://localhost/runnerupweb');
        $c->login($admin->getLogin(), $admin->getPassword());
        $c->deleteUser($test->createUser()->getLogin());
        $c->logout();
        // delete the uadmin user
        $um = UserManager::getUserManager();
        $um->deleteUser(WSWorkoutTest::createAdminUser()->getLogin());
    }
    
    static private function createAdminUser() {
        $user = User::userWithLogin('testadmin');
        $user->setPassword('testadmin123');
        $user->setFirstname('testadmin');
        $user->setLastname('testadmin');
        $user->setRole(User::ADMIN_ROLE);
        return $user;
    }
    
    static private function createUser($prefix = "test") {
        $user = User::userWithLogin($prefix . 'user');
        $user->setPassword($prefix . 'user123');
        $user->setFirstname($prefix . 'user');
        $user->setLastname($prefix . 'user');
        $user->setEmail($prefix . "user@lala.com");
        $user->setRole(User::USER_ROLE);
        return $user;
    }
    
    private function checkUsers(User $u1, User $u2) {
        $this->assertEquals($u1->getLogin(), $u2->getLogin());
        $this->assertEquals($u1->getFirstname(), $u2->getFirstname());
        $this->assertEquals($u1->getLastname(), $u2->getLastname());
        $this->assertEquals($u1->getEmail(), $u2->getEmail());
        $this->assertEquals($u1->getRole(), $u2->getRole());
        $this->assertNull($u2->getPassword());
    }
    
    public function testUpload() {
        $c = new Client('http://localhost/runnerupweb');
        // check login is necessary
        try {$c->uploadWorkout(__DIR__ . '/FitnessHistoryDetail.tcx');} catch(Exception $e) {$this->assertEquals(403, $e->getCode());}
        // login
        $user = $this->createUser();
        $c->login($user->getLogin(), $user->getPassword());
        // check invalid mime type
        try {$c->uploadWorkout(__DIR__ . '/../public/resources/images/runnerupweb-white.png');} catch(Exception $e) {$this->assertEquals(500, $e->getCode());}
        // upload three files to the user (the sample.tcx contains two activities) successfully
        $c->uploadWorkout(__DIR__ . '/FitnessHistoryDetail.tcx');
        $c->uploadWorkout(__DIR__ . '/sample.tcx');
        $c->uploadWorkout(__DIR__ . '/runnerup.tcx');
        // logout
        $c->logout();
    }
    
    public function testSearch() {
        $c = new Client('http://localhost/runnerupweb');
        // check login is necessary
        try {$c->searchWorkouts('', '');} catch(Exception $e) {$this->assertEquals(403, $e->getCode());}
        // login
        $user = $this->createUser();
        $c->login($user->getLogin(), $user->getPassword());
        // invalid start time
        $r1 = $c->searchWorkouts('not-a-valid-date', date('Y-m-d\TH:i:s\Z'));
        $this->assertFalse($r1->isSuccess());
        $this->assertEquals(1, $r1->getErrorCode());
        // empty start time
        $r2 = $c->searchWorkouts(null, date('Y-m-d\TH:i:s\Z'));
        $this->assertFalse($r2->isSuccess());
        $this->assertEquals(1, $r2->getErrorCode());
        // invalid end time
        $r3 = $c->searchWorkouts(date('Y-m-d\TH:i:s\Z', mktime(0, 0, 0, 1, 1, 2000)), 'invalid-end-time');
        $this->assertFalse($r3->isSuccess());
        $this->assertEquals(2, $r3->getErrorCode());
        // invalid offset
        $r4 = $c->searchWorkouts(date('Y-m-d\TH:i:s\Z', mktime(0, 0, 0, 1, 1, 2000)), date('Y-m-d\TH:i:s\Z'), -1, null);
        $this->assertFalse($r4->isSuccess());
        $this->assertEquals(3, $r4->getErrorCode());
        // invalid limit
        $r5 = $c->searchWorkouts(date('Y-m-d\TH:i:s\Z', mktime(0, 0, 0, 1, 1, 2000)), date('Y-m-d\TH:i:s\Z'), 0, 1000000);
        $this->assertFalse($r5->isSuccess());
        $this->assertEquals(4, $r5->getErrorCode());
        // search the four activities
        $r6 = $c->searchWorkouts(date('Y-m-d\TH:i:s\Z', mktime(0, 0, 0, 1, 1, 2000)), date('Y-m-d\TH:i:s\Z'));
        $this->assertTrue($r6->isSuccess());
        $this->assertEquals(4, count($r6->getActivities()));
        $this->assertEquals(2015, $r6->getActivities()[0]->getStartTime()->format('Y'));
        $this->assertEquals(2008, $r6->getActivities()[1]->getStartTime()->format('Y'));
        $this->assertEquals(2008, $r6->getActivities()[2]->getStartTime()->format('Y'));
        $this->assertEquals(2007, $r6->getActivities()[3]->getStartTime()->format('Y'));
        // search the two activities in 2008
        $r7 = $c->searchWorkouts(date('Y-m-d\TH:i:s\Z', mktime(0, 0, 0, 1, 1, 2008)), date('Y-m-d\TH:i:s\Z', mktime(0, 0, 0, 31, 12, 2008)));
        $this->assertTrue($r7->isSuccess());
        $this->assertEquals(2, count($r7->getActivities()));
        // perform the same search but with limit 2
        $r8 = $c->searchWorkouts(date('Y-m-d\TH:i:s\Z', mktime(0, 0, 0, 1, 1, 2000)), date('Y-m-d\TH:i:s\Z'), 0, 2);
        $this->assertTrue($r8->isSuccess());
        $this->assertEquals(2, count($r8->getActivities()));
        $this->assertEquals(2015, $r8->getActivities()[0]->getStartTime()->format('Y'));
        $this->assertEquals(2008, $r8->getActivities()[1]->getStartTime()->format('Y'));
        // continue with the second
        $r9 = $c->searchWorkouts(date('Y-m-d\TH:i:s\Z', mktime(0, 0, 0, 1, 1, 2000)), date('Y-m-d\TH:i:s\Z'), 2, 2);
        $this->assertTrue($r9->isSuccess());
        $this->assertEquals(2, count($r9->getActivities()));
        $this->assertEquals(2008, $r9->getActivities()[0]->getStartTime()->format('Y'));
        $this->assertEquals(2007, $r9->getActivities()[1]->getStartTime()->format('Y'));
        // logout
        $c->logout();
    }
    
    public function testDownload() {
        $c = new Client('http://localhost/runnerupweb');
        // test login is necessary
        try {$c->downloadWorkout(1);} catch(Exception $e) {$this->assertEquals(403, $e->getCode());}
        // login
        $user = $this->createUser();
        $c->login($user->getLogin(), $user->getPassword());
        // download a non existant activity
        try {$c->downloadWorkout(-1);} catch(Exception $e) {$this->assertEquals(404, $e->getCode());}
        // search the for activities
        $r1 = $c->searchWorkouts(date('Y-m-d\TH:i:s\Z', mktime(0, 0, 0, 1, 1, 2000)), date('Y-m-d\TH:i:s\Z'));
        $this->assertTrue($r1->isSuccess());
        $this->assertEquals(4, count($r1->getActivities()));
        // download the last activity and compare with the real file
        $r2 = $c->downloadWorkout($r1->getActivities()[3]->getId());
        $real = TCXManager::getTCXManager()->parse(__DIR__ . '/FitnessHistoryDetail.tcx')[0];
        $this->assertEquals($real, $r2['activity']);
        // check 304 if Etag
        $c->downloadWorkout($r1->getActivities()[3]->getId(), 304, $r2['Etag']);
        // check 304 if modified since
        $c->downloadWorkout($r1->getActivities()[3]->getId(), 304, null, $r2['Last-Modified']);
        // check 304 if both
        $c->downloadWorkout($r1->getActivities()[3]->getId(), 304, $r2['Etag'], $r2['Last-Modified']);
        // logout
        $c->logout();
    }
    
    public function testDelete() {
        $c = new Client('http://localhost/runnerupweb');
        // test login should be there
        try {$dr = $c->deleteWorkout(1);} catch(Exception $e) {$this->assertEquals(403, $e->getCode());}
        // login
        $user = $this->createUser();
        $c->login($user->getLogin(), $user->getPassword());
        // search all activities
        $r = $c->searchWorkouts(date('Y-m-d\TH:i:s\Z', mktime(0, 0, 0, 1, 1, 2000)), date('Y-m-d\TH:i:s\Z'));
        $this->assertTrue($r->isSuccess());
        $this->assertEquals(4, count($r->getActivities()));
        // delete the four activities
        foreach ($r->getActivities() as $act) {
            $dr = $c->deleteWorkout($act->getId());
            $this->assertTrue($dr->isSuccess());
        }
        // check now the delete return a fail for the activities
        foreach ($r->getActivities() as $act) {
            $dr = $c->deleteWorkout($act->getId());
            $this->assertFalse($dr->isSuccess());
            $this->assertEquals(2, $dr->getErrorCode());
        }
        // logout
        $c->logout();
    }
}
