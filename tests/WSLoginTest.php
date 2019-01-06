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
use runnerupweb\common\Client;
use runnerupweb\common\Configuration;
use runnerupweb\common\UserManager;
use runnerupweb\data\LoginResponse;
use runnerupweb\data\UserResponse;
use PHPUnit\Framework\TestCase;

/**
 * Description of WSLoginTest
 *
 * @author ricky
 */
class WSLoginTest extends TestCase {

    static private function createAdminUser() {
        $user = User::userWithLogin('testadmin');
        $user->setPassword('testadmin123');
        $user->setFirstname('testadmin');
        $user->setLastname('testadmin');
        $user->setRole(User::ADMIN_ROLE);
        return $user;
    }
    
    public static function setUpBeforeClass() {
        Configuration::getConfiguration();
        $um = UserManager::getUserManager();
        $um->createUser(WSLoginTest::createAdminUser());
    }

    public static function tearDownAfterClass() {
        $um = UserManager::getUserManager();
        $um->deleteUser(WSLoginTest::createAdminUser()->getLogin());
    }

    public function testLoginOk() {
        $user = $this->createAdminUser();
        $c = new Client('http://localhost/runnerupweb');
        $response = $c->login($user->getLogin(), $user->getPassword());
        $this->assertTrue($response->isSuccess());
        $response = $c->logout();
        $this->assertTrue($response->isSuccess());
    }

    public function testLoginWrongPassword() {
        $user = $this->createAdminUser();
        $c = new Client('http://localhost/runnerupweb');
        $result = $c->login($user->getLogin(), $user->getPassword() . "KO");
        $this->assertEquals(1102, $result->getErrorCode());
    }
    
    public function testLoginWrongLogin() {
        $user = $this->createAdminUser();
        $c = new Client('http://localhost/runnerupweb');
        $result = $c->login($user->getLogin() . "KO", $user->getPassword());
        $this->assertEquals(1102, $result->getErrorCode());
    }
    
    public function testLoginWrongData() {
        $user = $this->createAdminUser();
        $c = new Client('http://localhost/runnerupweb');
        $result = $c->login($user->getLogin(), null);
        $this->assertEquals(1101, $result->getErrorCode());
    }
    
    public function testLogout403() {
        $c = new Client('http://localhost/runnerupweb');
        try {
            $c->logout();
        } catch (Exception $e) {
            $this->assertEquals(403, $e->getCode());
        }
    }

}
