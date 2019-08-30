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
use runnerupweb\data\UserOption;
use runnerupweb\common\Configuration;
use runnerupweb\common\Client;
use PHPUnit\Framework\TestCase;

/**
 * Description of WSUserTest
 *
 * @author ricky
 */
class WSUserTest extends TestCase {
    
    public static function setUpBeforeClass(): void {
        Configuration::getConfiguration();
        $um = UserManager::getUserManager();
        $um->createUser(WSUserTest::createAdminUser());
    }

    public static function tearDownAfterClass(): void {
        $um = UserManager::getUserManager();
        $um->deleteUser(WSUserTest::createAdminUser()->getLogin());
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
    
    static private function createUserOptions() {
        $opts = new UserOption();
        $opts->set("preferred.activity-list.page-size", "50");
        $opts->set("preferred.unit.altitude", "km");
        $opts->set("activity.map.tilelayer", "opencyclemap");
        return $opts;
    }
    
    private function checkUsers(User $u1, User $u2) {
        $this->assertEquals($u1->getLogin(), $u2->getLogin());
        $this->assertEquals($u1->getFirstname(), $u2->getFirstname());
        $this->assertEquals($u1->getLastname(), $u2->getLastname());
        $this->assertEquals($u1->getEmail(), $u2->getEmail());
        $this->assertEquals($u1->getRole(), $u2->getRole());
        $this->assertNull($u2->getPassword());
    }
    
    private function checkUserOptions(UserOption $uo1, UserOption $uo2) {
        $this->assertEquals($uo1->flat(), $uo2->flat());
    }

    public function test403() {
        $admin = $this->createAdminUser();
        $opts = $this->createUserOptions();
        $c = new Client('http://localhost/runnerupweb');
        try {$c->setUser($admin);} catch(Exception $e) {$this->assertEquals(403, $e->getCode());}
        try {$c->getUser($admin->getLogin());} catch(Exception $e) {$this->assertEquals(403, $e->getCode());}
        try {$c->setUserOptions($opts);} catch(Exception $e) {$this->assertEquals(403, $e->getCode());}
        try {$c->getUserOptions();} catch(Exception $e) {$this->assertEquals(403, $e->getCode());}
        try {$c->getOptionDefinitions();} catch(Exception $e) {$this->assertEquals(403, $e->getCode());}
        try {$c->searchUsers(null, null, null, null, null, null, null);} catch(Exception $e) {$this->assertEquals(403, $e->getCode());}
        try {$c->deleteUser($admin->getLogin());} catch(Exception $e) {$this->assertEquals(403, $e->getCode());}
    }
    
    public function testCreateOK() {
        $admin = $this->createAdminUser();
        $user = $this->createUser();
        $c = new Client('http://localhost/runnerupweb');
        // login
        $c->login($admin->getLogin(), $admin->getPassword());
        // check error creating a bad filled user
        $user->setPassword(null);
        $r1 = $c->setUser($user);
        $this->assertFalse($r1->isSuccess());
        $this->assertEquals(4, $r1->getErrorCode());
        // create the user ok
        $user->setPassword($this->createUser()->getPassword());
        $r2 = $c->setUser($user);
        $this->assertTrue($r2->isSuccess());
        // check the user
        $r3 = $c->getUser($user->getLogin());
        $this->assertTrue($r3->isSuccess());
        $this->checkUsers($user, $r3->getUser());
        // delete the user => do not delete for the rest of tests
        //$r4 = $c->deleteUser($user->getLogin());
        //$this->assertTrue($r3->isSuccess());
        // logout
        $c->logout();
    }
    
    public function testAdmin403() {
        $admin = $this->createUser();
        $c = new Client('http://localhost/runnerupweb');
        // login
        $c->login($admin->getLogin(), $admin->getPassword());
        // check non admin methods
        try {$c->searchUsers(null, null, null, null, null, null, null);} catch(Exception $e) {$this->assertEquals(403, $e->getCode());}
        try {$c->deleteUser($admin->getLogin());} catch(Exception $e) {$this->assertEquals(403, $e->getCode());}
        // logout
        $c->logout();
    }
    
    public function testCreateKONonAdmin() {
        $admin = $this->createUser();
        $c = new Client('http://localhost/runnerupweb');
        // login
        $c->login($admin->getLogin(), $admin->getPassword());
        // create user => error
        $user = $this->createUser("test2");
        $r1 = $c->setUser($user);
        $this->assertFalse($r1->isSuccess());
        $this->assertEquals(5, $r1->getErrorCode());
        // logout
        $c->logout();
    }
    
    public function testUpdateUserAdmin() {
        $admin = $this->createAdminUser();
        $c = new Client('http://localhost/runnerupweb');
        // login
        $c->login($admin->getLogin(), $admin->getPassword());
        // check error creating a bad filled user
        $user = $this->createUser();
        $user->setEmail("lalalalalaal");
        $r1 = $c->setUser($user);
        $this->assertFalse($r1->isSuccess());
        $this->assertEquals(1, $r1->getErrorCode());
        // modify the user email
        $user->setEmail("lala@lala.com");
        $r2 = $c->setUser($user);
        $this->assertTrue($r2->isSuccess());
        // modify the user password
        $user->setPassword($user->getPassword() . "123");
        $r3 = $c->setUser($user);
        $this->assertTrue($r3->isSuccess());
        // check the user
        $r4 = $c->getUser($user->getLogin());
        $this->assertTrue($r4->isSuccess());
        $this->checkUsers($user, $r4->getUser());
        // logout
        $c->logout();
    }
    
    public function testUpdateUserNonAdmin() {
        $admin = $this->createUser();
        $admin->setPassword($admin->getPassword() . "123");
        $admin->setEmail("lala@lala.com");
        $c = new Client('http://localhost/runnerupweb');
        $c->login($admin->getLogin(), $admin->getPassword());
        // try to modify the admin user
        $u1 = $this->createAdminUser();
        $u1->setEmail("lala@lala.com");
        $r1 = $c->setUser($u1);
        $this->assertFalse($r1->isSuccess());
        $this->assertEquals(3, $r1->getErrorCode());
        // try to modify my role to admin
        $u2 = $this->createUser();
        $u2->setRole(User::ADMIN_ROLE);
        $r2 = $c->setUser($u2);
        $this->assertFalse($r2->isSuccess());
        $this->assertEquals(2, $r2->getErrorCode());
        // try to modify myself back to the begining
        $u2->setRole(User::USER_ROLE);
        $r3 = $c->setUser($u2);
        $this->assertTrue($r3->isSuccess());
        // read and check
        $r4 = $c->getUser($u2->getLogin());
        $this->assertTrue($r4->isSuccess());
        $this->checkUsers($u2, $r4->getUser());
        $c->logout();
    }
    
    public function testSetOptions() {
        $admin = $this->createUser();
        $c = new Client('http://localhost/runnerupweb');
        // login
        $c->login($admin->getLogin(), $admin->getPassword());
        // set some bad properties
        $opts = $this->createUserOptions();
        $opts->set("prop.not.exists", "value");
        $r1 = $c->setUserOptions($opts);
        $this->assertFalse($r1->isSuccess());
        $this->assertEquals(1, $r1->getErrorCode());
        // set ok
        $opts->remove("prop.not.exists");
        $r2 = $c->setUserOptions($opts);
        $this->assertTrue($r2->isSuccess());
        // read and compare
        $r3 = $c->getUserOptions();
        $this->assertTrue($r3->isSuccess());
        $this->checkUserOptions($opts, $r3->getOptions());
        // logout
        $c->logout();
    }
    
    public function testGetOptionDefinitions() {
        $admin = $this->createUser();
        $c = new Client('http://localhost/runnerupweb');
        // login
        $c->login($admin->getLogin(), $admin->getPassword());
        // get the definitions
        $r1 = $c->getOptionDefinitions();
        $this->assertTrue($r1->isSuccess());
        $this->assertNotNull($r1->getOptions());
        $this->assertTrue(count($r1->getOptions()->flat()) > 0);
        // logout
        $c->logout();
    }
    
    public function testSearch() {
        $admin = $this->createAdminUser();
        $user = $this->createUser();
        $c = new Client('http://localhost/runnerupweb');
        // login
        $c->login($admin->getLogin(), $admin->getPassword());
        // invalid op
        $r1 = $c->searchUsers("OP_LALA", "lala", null, null, null);
        $this->assertFalse($r1->isSuccess());
        $this->assertEquals(1, $r1->getErrorCode());
        // invalid login sent
        $r2 = $c->searchUsers("OP_EQUALS", "lala@lala", null, null, null);
        $this->assertFalse($r2->isSuccess());
        $this->assertEquals(2, $r2->getErrorCode());
        // invalid offset
        $r3 = $c->searchUsers("OP_EQUALS", $user->getLogin(), null, null, null, "-1", null);
        $this->assertFalse($r3->isSuccess());
        $this->assertEquals(3, $r3->getErrorCode());
        // invalid limit
        $r4 = $c->searchUsers("OP_EQUALS", $user->getLogin(), null, null, null, "0", "1000000");
        $this->assertFalse($r4->isSuccess());
        $this->assertEquals(4, $r4->getErrorCode());
        // search by login
        $r5 = $c->searchUsers("OP_EQUALS", $user->getLogin(), null, null, null);
        $this->assertTrue($r5->isSuccess());
        $this->assertEquals(1, count($r5->getUsers()));
        $this->checkUsers($user, $r5->getUsers()[0]);
        // search by firstname
        $r6 = $c->searchUsers("OP_ENDS_WITH", null, $user->getFirstname(), null, null);
        $this->assertTrue($r6->isSuccess());
        $this->assertEquals(1, count($r6->getUsers()));
        $this->checkUsers($user, $r6->getUsers()[0]);
        // search by lastname
        $r7 = $c->searchUsers("OP_STARTS_WITH", null, null, $user->getLastname(), null);
        $this->assertTrue($r7->isSuccess());
        $this->assertEquals(1, count($r7->getUsers()));
        $this->checkUsers($user, $r7->getUsers()[0]);
        // search by email
        $r8 = $c->searchUsers("OP_CONTAINS", null, null, null, $user->getEmail());
        $this->assertTrue($r8->isSuccess());
        $this->assertEquals(1, count($r8->getUsers()));
        $this->checkUsers($user, $r8->getUsers()[0]);
        // search by all
        $r9 = $c->searchUsers("OP_EQUALS", $user->getLogin(), $user->getFirstname(), $user->getLastname(), $user->getEmail());
        $this->assertTrue($r9->isSuccess());
        $this->assertEquals(1, count($r9->getUsers()));
        $this->checkUsers($user, $r9->getUsers()[0]);
        // logout
        $c->logout();
    }
    
    public function testDelete() {
        $admin = $this->createAdminUser();
        $user = $this->createUser();
        $c = new Client('http://localhost/runnerupweb');
        // login
        $c->login($admin->getLogin(), $admin->getPassword());
        // check invalid login
        $r1 = $c->deleteUser(null);
        $this->assertFalse($r1->isSuccess());
        $this->assertEquals(1, $r1->getErrorCode());
        // check user not exists
        $r2 = $c->deleteUser("thisuserdoesnotexists");
        $this->assertFalse($r2->isSuccess());
        $this->assertEquals(2, $r2->getErrorCode());
        // delete the user
        $r3 = $c->deleteUser($user->getLogin());
        $this->assertTrue($r3->isSuccess());
        // get the user and not exists
        $r4 = $c->deleteUser($user->getLogin());
        $this->assertFalse($r4->isSuccess());
        $this->assertEquals(2, $r4->getErrorCode());
        // logout
        $c->logout();
    }
}
