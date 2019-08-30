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
use runnerupweb\data\TagConfig;
use runnerupweb\common\autotags\SportActivityAutomaticTag;
use runnerupweb\common\UserManager;
use runnerupweb\common\TCXManager;
use runnerupweb\common\Configuration;
use runnerupweb\common\Client;
use PHPUnit\Framework\TestCase;

/**
 * Description of WSWorkoutTest
 *
 * @author ricky
 */
class WSWorkoutTest extends TestCase {
    
    public static function setUpBeforeClass(): void {
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

    public static function tearDownAfterClass(): void {
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

    static private function createCommonTagConfig(): TagConfig {
        $tagConfig = TagConfig::tagConfigWithDescription('tag-test', 'tag-test-description');
        return $tagConfig;
    }
    
    private function checkUsers(User $u1, User $u2): void {
        $this->assertEquals($u1->getLogin(), $u2->getLogin());
        $this->assertEquals($u1->getFirstname(), $u2->getFirstname());
        $this->assertEquals($u1->getLastname(), $u2->getLastname());
        $this->assertEquals($u1->getEmail(), $u2->getEmail());
        $this->assertEquals($u1->getRole(), $u2->getRole());
        $this->assertNull($u2->getPassword());
    }

    private function checkTagConfigs(TagConfig $tc1, TagConfig $tc2): void {
        $this->assertEquals($tc1->getTag(), $tc2->getTag());
        $this->assertEquals($tc1->getDescription(), $tc2->getDescription());
        $this->assertEquals($tc1->getProvider(), $tc2->getProvider());
        $this->assertEquals($tc1->getConfig(), $tc2->getConfig());
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

    public function testSetTagConfig(): void {
        $c = new Client('http://localhost/runnerupweb');
        $tagConfig = WSWorkoutTest::createCommonTagConfig();
        // check login is necessary
        try {$c->setTagConfig('create', $tagConfig);} catch(Exception $e) {$this->assertEquals(403, $e->getCode());}
        // login
        $user = $this->createUser();
        $c->login($user->getLogin(), $user->getPassword());
        // incorrect mode
        $r1 = $c->setTagConfig('invalid', $tagConfig);
        $this->assertFalse($r1->isSuccess());
        $this->assertEquals(1, $r1->getErrorCode());
        // incorrect tag config (for example long tag)
        $name = $tagConfig->getTag();
        $tagConfig->setTag('A');
        for ($i = 0; $i < 130; $i++) {
            $tagConfig->setTag($tagConfig->getTag() . 'A');
        }
        $r2 = $c->setTagConfig('create', $tagConfig);
        $this->assertFalse($r2->isSuccess());
        $this->assertEquals(3, $r2->getErrorCode());
        // create the tag
        $tagConfig->setTag($name);
        $r3 = $c->setTagConfig('create', $tagConfig);
        $this->assertTrue($r3->isSuccess());
        $getr3 = $c->getTagConfig($tagConfig->getTag());
        $this->checkTagConfigs($tagConfig, $getr3->getTagConfig());
        // check duplicate tag
        $r4 = $c->setTagConfig('create', $tagConfig);
        $this->assertFalse($r4->isSuccess());
        $this->assertEquals(4, $r4->getErrorCode());
        // update the tag config
        $desc = $tagConfig->getDescription();
        $tagConfig->setDescription('changed');
        $r5 = $c->setTagConfig('edit', $tagConfig);
        $this->assertTrue($r5->isSuccess());
        $getr5 = $c->getTagConfig($tagConfig->getTag());
        $this->checkTagConfigs($tagConfig, $getr5->getTagConfig());
        // update back
        $tagConfig->setDescription($desc);
        $r6 = $c->setTagConfig('edit', $tagConfig);
        $this->assertTrue($r6->isSuccess());
        // update a non existent tag
        $tagConfig->setTag('non-exists');
        $r6 = $c->setTagConfig('edit', $tagConfig);
        $this->assertFalse($r6->isSuccess());
        $this->assertEquals(5, $r6->getErrorCode());
        // logout
        $c->logout();
    }

    public function testGetTagConfig(): void {
        $c = new Client('http://localhost/runnerupweb');
        $tagConfig = WSWorkoutTest::createCommonTagConfig();
        // check login is necessary
        try {$c->getTagConfig($tagConfig->getTag());} catch(Exception $e) {$this->assertEquals(403, $e->getCode());}
        // login
        $user = $this->createUser();
        $c->login($user->getLogin(), $user->getPassword());
        // no tag
        $r1 = $c->getTagConfig('');
        $this->assertFalse($r1->isSuccess());
        $this->assertEquals(1, $r1->getErrorCode());
        // tag does not exists
        $r2 = $c->getTagConfig('invalid');
        $this->assertFalse($r2->isSuccess());
        $this->assertEquals(3, $r2->getErrorCode());
        // ok
        $r3 = $c->getTagConfig($tagConfig->getTag());
        $this->assertTrue($r3->isSuccess());
        $this->checkTagConfigs($tagConfig, $r3->getTagConfig());
        // logout
        $c->logout();
    }

    public function testAutomaticTag(): void {
        $c = new Client('http://localhost/runnerupweb');
        $tagConfig = WSWorkoutTest::createCommonTagConfig();
        // check login is necessary
        try {$c->automaticTag(null, null);} catch(Exception $e) {$this->assertEquals(403, $e->getCode());}
        // login
        $user = $this->createUser();
        $c->login($user->getLogin(), $user->getPassword());
        // list of providers OK
        $r1 = $c->automaticTag(null, null);
        $this->assertNotNull($r1->getExtra());
        $this->assertTrue(count($r1->getExtra()) > 0);
        $this->assertTrue(in_array(SportActivityAutomaticTag::class, $r1->getExtra()));
        // invalid activity id
        $r2 = $c->automaticTag(SportActivityAutomaticTag::class, 0);
        $this->assertFalse($r2->isSuccess());
        $this->assertEquals(2, $r2->getErrorCode());
        // activity does not exists
        $r3 = $c->automaticTag(SportActivityAutomaticTag::class, 1);
        $this->assertFalse($r3->isSuccess());
        $this->assertEquals(3, $r3->getErrorCode());
        // search the first activity
        $r4 = $c->searchWorkouts(date('Y-m-d\TH:i:s\Z', mktime(0, 0, 0, 1, 1, 2000)), date('Y-m-d\TH:i:s\Z'), 0, 1);
        $this->assertTrue($r4->isSuccess());
        $this->assertEquals(1, count($r4->getActivities()));
        // provider does not exists
        $r5 = $c->automaticTag('invalid', $r4->getActivities()[0]->getId());
        $this->assertFalse($r5->isSuccess());
        $this->assertEquals(4, $r5->getErrorCode());
        // get the tag config OK
        $r6 = $c->automaticTag(SportActivityAutomaticTag::class, $r4->getActivities()[0]->getId());
        $this->assertTrue($r6->isSuccess());
        $this->assertEquals(SportActivityAutomaticTag::class, $r6->getTagConfig()->getProvider());
        $this->assertNotNull($r6->getTagConfig()->getExtra());
        // create an automatic tag config without value
        $autoTagConfig = $r6->getTagConfig();
        $r7 = $c->setTagConfig('create', $autoTagConfig);
        $this->assertFalse($r7->isSuccess());
        $this->assertEquals(2, $r7->getErrorCode());
        // create ok
        $name = $autoTagConfig->getExtra()[0]['name'];
        $value = $autoTagConfig->getExtra()[0]['value'];
        $autoTagConfig->setExtra(array($name => $value));
        $r8 = $c->setTagConfig('create', $autoTagConfig);
        $this->assertTrue($r8->isSuccess());
        // get the automatic task
        $r10 = $c->getTagConfig($autoTagConfig->getTag());
        $this->assertTrue($r10->isSuccess());
        $this->checkTagConfigs($autoTagConfig, $r10->getTagConfig());
        // create automatic tag config with invalid provider
        $autoTagConfig->setProvider('invalid');
        $r9 = $c->setTagConfig('create', $autoTagConfig);
        $this->assertFalse($r9->isSuccess());
        $this->assertEquals(6, $r9->getErrorCode());
        // logout
        $c->logout();
    }

    public function testListTagConfigs(): void {
        $c = new Client('http://localhost/runnerupweb');
        // check login is necessary
        try {$c->listTagConfigs();} catch(Exception $e) {$this->assertEquals(403, $e->getCode());}
        // login
        $user = $this->createUser();
        $c->login($user->getLogin(), $user->getPassword());
        // list ok
        $r1 = $c->listTagConfigs();
        $this->assertTrue($r1->isSuccess());
        $this->assertEquals(2, count($r1->getTags()));
        $this->assertEquals(1, count(array_filter($r1->getTags(), function($t) {return $t->isAuto();})));
        // logout
        $c->logout();
    }

    public function testManageWorkoutTagAssign(): void {
        $c = new Client('http://localhost/runnerupweb');
        // check login is necessary
        try {$c->manageWorkoutTag('ASSIGN', 1, 'tag-test');} catch(Exception $e) {$this->assertEquals(403, $e->getCode());}
        // login
        $user = $this->createUser();
        $c->login($user->getLogin(), $user->getPassword());
        // get the first activity
        $sr = $c->searchWorkouts(date('Y-m-d\TH:i:s\Z', mktime(0, 0, 0, 1, 1, 2000)), date('Y-m-d\TH:i:s\Z'), 0, 1);
        $this->assertTrue($sr->isSuccess());
        $this->assertEquals(1, count($sr->getActivities()));
        $activity = $sr->getActivities()[0];
        // invalid activity id
        $r1 = $c->manageWorkoutTag('ASSIGN', 0, 'tag-test');
        $this->assertFalse($r1->isSuccess());
        $this->assertEquals(1, $r1->getErrorCode());
        // not tag sent
        $r2 = $c->manageWorkoutTag('ASSIGN', 1, '');
        $this->assertFalse($r2->isSuccess());
        $this->assertEquals(2, $r2->getErrorCode());
        // invalid operation
        $r3 = $c->manageWorkoutTag('invalid', 1, 'tag-test');
        $this->assertFalse($r3->isSuccess());
        $this->assertEquals(3, $r3->getErrorCode());
        // invalid tag
        $r4 = $c->manageWorkoutTag('ASSIGN', $activity->getId(), 'invalid');
        $this->assertFalse($r4->isSuccess());
        $this->assertEquals(5, $r4->getErrorCode());
        // invalid actity
        $r5 = $c->manageWorkoutTag('ASSIGN', 1, 'tag-test');
        $this->assertFalse($r5->isSuccess());
        $this->assertEquals(6, $r5->getErrorCode());
        // assign ok
        $r5 = $c->manageWorkoutTag('ASSIGN', $activity->getId(), 'tag-test');
        $this->assertTrue($r5->isSuccess());
        // check is assigned
        $r6 = $c->listWorkoutTags($activity->getId());
        $this->assertTrue($r6->isSuccess());
        $this->assertEquals(1, count($r6->getTags()));
        $this->assertEquals('tag-test', $r6->getTags()[0]->getTag());
        // logout
        $c->logout();
    }

    public function testListWorkoutTags(): void {
        $c = new Client('http://localhost/runnerupweb');
        // check login is necessary
        try {$c->listWorkoutTags(1);} catch(Exception $e) {$this->assertEquals(403, $e->getCode());}
        // login
        $user = $this->createUser();
        $c->login($user->getLogin(), $user->getPassword());
        // get the first activity
        $sr = $c->searchWorkouts(date('Y-m-d\TH:i:s\Z', mktime(0, 0, 0, 1, 1, 2000)), date('Y-m-d\TH:i:s\Z'), 0, 1);
        $this->assertTrue($sr->isSuccess());
        $this->assertEquals(1, count($sr->getActivities()));
        $activity = $sr->getActivities()[0];
        // invalid id
        $r1 = $c->listWorkoutTags(0);
        $this->assertFalse($r1->isSuccess());
        $this->assertEquals(1, $r1->getErrorCode());
        // activity does not exists
        $r2 = $c->listWorkoutTags(1);
        $this->assertFalse($r2->isSuccess());
        $this->assertEquals(2, $r2->getErrorCode());
        // search ok
        $r3 = $c->listWorkoutTags($activity->getId());
        $this->assertTrue($r3->isSuccess());
        $this->assertEquals(1, count($r3->getTags()));
        $this->assertEquals('tag-test', $r3->getTags()[0]->getTag());
        // logout
        $c->logout();
    }

    public function testCalculateAutomaticTags(): void {
        $c = new Client('http://localhost/runnerupweb');
        // check login is necessary
        try {$c->calculateAutomaticTags(1, false);} catch(Exception $e) {$this->assertEquals(403, $e->getCode());}
        // login
        $user = $this->createUser();
        $c->login($user->getLogin(), $user->getPassword());
        // get the first activity
        $sr = $c->searchWorkouts(date('Y-m-d\TH:i:s\Z', mktime(0, 0, 0, 1, 1, 2000)), date('Y-m-d\TH:i:s\Z'), 0, 1);
        $this->assertTrue($sr->isSuccess());
        $this->assertEquals(1, count($sr->getActivities()));
        $activity = $sr->getActivities()[0];
        // invalid id
        $r1 = $c->calculateAutomaticTags(0, false);
        $this->assertFalse($r1->isSuccess());
        $this->assertEquals(1, $r1->getErrorCode());
        // activity does not exists
        $r2 = $c->calculateAutomaticTags(1, false);
        $this->assertFalse($r2->isSuccess());
        $this->assertEquals(3, $r2->getErrorCode());
        // calculate the activities ok
        $r3 = $c->calculateAutomaticTags($activity->getId(), false);
        $this->assertTrue($r3->isSuccess());
        // check is assigned
        $r4 = $c->listWorkoutTags($activity->getId());
        $this->assertTrue($r4->isSuccess());
        $this->assertEquals(2, count($r4->getTags()));
        $this->assertEquals(1, count(array_filter($r4->getTags(), function($t) {return $t->getTag() === 'running';})));
        // modify the running activity to not match the sport
        $r5 = $c->getTagConfig('running');
        $this->assertTrue($r5->isSuccess());
        $autoTagConfig = $r5->getTagConfig();
        $name = $autoTagConfig->getExtra()[0]['name'];
        $autoTagConfig->setExtra(array($name => 'invalid'));
        $r6 = $c->setTagConfig('edit', $autoTagConfig);
        $this->assertTrue($r6->isSuccess());
        // check with delete
        $r7 = $c->calculateAutomaticTags($activity->getId(), true);
        $this->assertTrue($r7->isSuccess());
        // check is deleted from the activity
        $r8 = $c->listWorkoutTags($activity->getId());
        $this->assertTrue($r4->isSuccess());
        $this->assertEquals(1, count($r8->getTags()));
        $this->assertEquals(0, count(array_filter($r8->getTags(), function($t) {return $t->getTag() === 'running';})));
        // logout
        $c->logout();
    }
    
    public function testSearch(): void {
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
        // search the activities with filter
        $r10 = $c->searchWorkouts(date('Y-m-d\TH:i:s\Z', mktime(0, 0, 0, 1, 1, 2000)), date('Y-m-d\TH:i:s\Z'), 0, 2, 'tag-test');
        $this->assertTrue($r10->isSuccess());
        $this->assertEquals(1, count($r10->getActivities()));
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

    public function testManageWorkoutTagUnassign(): void {
        $c = new Client('http://localhost/runnerupweb');
        // login
        $user = $this->createUser();
        $c->login($user->getLogin(), $user->getPassword());
        // get the first activity
        $sr = $c->searchWorkouts(date('Y-m-d\TH:i:s\Z', mktime(0, 0, 0, 1, 1, 2000)), date('Y-m-d\TH:i:s\Z'), 0, 1);
        $this->assertTrue($sr->isSuccess());
        $this->assertEquals(1, count($sr->getActivities()));
        $activity = $sr->getActivities()[0];
        // unassign ok
        $r1 = $c->manageWorkoutTag('UNASSIGN', $activity->getId(), 'tag-test');
        $this->assertTrue($r1->isSuccess());
        // check is unassigned
        $r2 = $c->listWorkoutTags($activity->getId());
        $this->assertTrue($r2->isSuccess());
        $this->assertEquals(0, count(array_filter($r2->getTags(), function($t) {return $t->getTag() === 'tag-test';})));
        // unassign a not assigned tag
        $r3 = $c->manageWorkoutTag('UNASSIGN', $activity->getId(), 'tag-test');
        $this->assertFalse($r3->isSuccess());
        $this->assertEquals(4, $r3->getErrorCode());
        // logout
        $c->logout();
    }

    public function testDeleteTagConfig(): void {
        $c = new Client('http://localhost/runnerupweb');
        // check login is necessary
        try {$c->deleteTagConfig('tag-test');} catch(Exception $e) {$this->assertEquals(403, $e->getCode());}
        // login
        $user = $this->createUser();
        $c->login($user->getLogin(), $user->getPassword());
        // invalid tag
        $r1 = $c->deleteTagConfig('');
        $this->assertFalse($r1->isSuccess());
        $this->assertEquals(1, $r1->getErrorCode());
        // tag does not exists
        $r2 = $c->deleteTagConfig('invalid');
        $this->assertFalse($r2->isSuccess());
        $this->assertEquals(2, $r2->getErrorCode());
        // delete tag-test
        $r3 = $c->deleteTagConfig('tag-test');
        $this->assertTrue($r3->isSuccess());
        // delete running
        $r4 = $c->deleteTagConfig('running');
        $this->assertTrue($r4->isSuccess());
        // check there are no tag configs
        $r5 = $c->listTagConfigs();
        $this->assertTrue($r5->isSuccess());
        $this->assertEquals(0, count($r5->getTags()));
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
