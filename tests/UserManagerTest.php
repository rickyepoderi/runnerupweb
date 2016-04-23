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

use runnerupweb\data\User;
use runnerupweb\common\UserManager;
use runnerupweb\common\Logging;

/**
 * Description of DataBaseTest
 *
 * @author ricky
 */
class DataBaseTest extends PHPUnit_Framework_TestCase {
    
    public static function setUpBeforeClass() {
        Logging::initLogger(__DIR__);
        UserManager::initUserManager('mysql:host=localhost;dbname=runnerupweb;charset=utf8', 'runnerupweb', 'runnerupweb', 100);
    }

    public static function tearDownAfterClass() {
        // noop
    }

    private function createUser1() {
        $user = User::userWithLogin('testum');
        $user->setPassword('testum123');
        $user->setFirstname('name');
        $user->setLastname('surname');
        $user->setEmail('testum@lala.com');
        $user->setRole(User::USER_ROLE);
        return $user;
    }
    
    private function createUser2() {
        $user = User::userWithLogin('testum');
        $user->setPassword('testum321');
        $user->setFirstname('name2');
        $user->setLastname('surname2');
        $user->setEmail('testum@koko.com');
        $user->setRole(User::ADMIN_ROLE);
        return $user;
    }
    
    private function createUser3() {
        $user = User::userWithLogin('testum2');
        $user->setPassword('testum123');
        $user->setFirstname('name2');
        $user->setLastname('surname2');
        $user->setEmail('testum@lala.com');
        $user->setRole(User::USER_ROLE);
        return $user;
    }
    
    public function testDatabase() {
        $this->assertNotNull(UserManager::getUserManager());
    }
    
    /**
     * @depends testDatabase
     */
    public function testCreateUser() {
        $user = $this->createUser1();
        $um = UserManager::getUserManager();
        $this->assertTrue($user->checkUser(true));
        $um->createUser($user);
        $user->setPassword(null);
        $this->assertEquals($user, $um->getUser($user->getLogin()));
        $this->assertEquals($user, $um->checkUserPassword($user->getLogin(), $this->createUser1()->getPassword()));
    }
    
    /**
     * @depends testCreateUser
     */
    public function testUpdateUser() {
        $user = $this->createUser2();
        $um = UserManager::getUserManager();
        $this->assertTrue($user->checkUser(false));
        $um->updateUser($user);
        $this->assertEquals($user, $um->getUser($user->getLogin()));
        $this->assertEquals($user, $um->checkUserPassword($user->getLogin(), $this->createUser2()->getPassword()));
        $this->assertNull($um->checkUserPassword($user->getLogin(), $this->createUser1()->getPassword()));
    }
    
    /**
     * @depends testUpdateUser
     */
    public function testUpdateUserWithoutPassword() {
        $user = $this->createUser1();
        $um = UserManager::getUserManager();
        $this->assertTrue($user->checkUser(false));
        $um->updateUser($user);
        $user2 = $um->getUser($user->getLogin());
        $this->assertEquals($user, $user2);
        $this->assertEquals($user, $um->checkUserPassword($user->getLogin(), $this->createUser1()->getPassword()));
        $this->assertNull($um->checkUserPassword($user->getLogin(), $this->createUser2()->getPassword()));
    }
    
    /**
     * @depends testUpdateUserWithoutPassword
     */
    public function testSearchLogin() {
        $user = $this->createUser1();
        $user->setPassword(null);
        $um = UserManager::getUserManager();
        $res = $um->search($user->getLogin(), null, null, null, UserManager::OP_EQUALS);
        $this->assertEquals(1, count($res));
        $this->assertEquals($user, $res[0]);
    }
    
    /**
     * @depends testSearchLogin
     */
    public function testSearchFirstname() {
        $user = $this->createUser1();
        $user->setPassword(null);
        $um = UserManager::getUserManager();
        $res = $um->search(null, $user->getFirstname(), null, null, UserManager::OP_STARTS_WITH);
        $this->assertEquals(1, count($res));
        $this->assertEquals($user, $res[0]);
    }
    
    /**
     * @depends testSearchFirstname
     */
    public function testSearchLastname() {
        $user = $this->createUser1();
        $user->setPassword(null);
        $um = UserManager::getUserManager();
        $res = $um->search(null, null, $user->getLastname(), null, UserManager::OP_ENDS_WITH);
        $this->assertEquals(1, count($res));
        $this->assertEquals($user, $res[0]);
    }
    
    /**
     * @depends testSearchLastname
     */
    public function testSearchEmail() {
        $user = $this->createUser1();
        $user->setPassword(null);
        $um = UserManager::getUserManager();
        $res = $um->search(null, null, null, $user->getEmail(), UserManager::OP_EQUALS);
        $this->assertEquals(1, count($res));
        $this->assertEquals($user, $res[0]);
    }
    
    /**
     * @depends testSearchEmail
     */
    public function testSearchAll() {
        $user = $this->createUser1();
        $user->setPassword(null);
        $um = UserManager::getUserManager();
        $res = $um->search($user->getLogin(), $user->getFirstname(), $user->getLastname(), $user->getEmail(), UserManager::OP_CONTAINS);
        $this->assertEquals(1, count($res));
        $this->assertEquals($user, $res[0]);
    }
    
    /**
     * @depends testSearchAll
     */
    public function testDeleteUser() {
        $user = $this->createUser1();
        $um = UserManager::getUserManager();
        $this->assertTrue($um->deleteUser($user->getLogin()));
        $this->assertNull($um->getUser($user->getLogin()));
    }
    
}
