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
use runnerupweb\data\LoginResponse;
use runnerupweb\data\UserResponse;

/**
 * Description of WSLoginTest
 *
 * @author ricky
 */
class WSLoginTest extends PHPUnit_Framework_TestCase {

    static private function createAdminUser() {
        $user = User::userWithLogin('testadmin');
        $user->setPassword('testadmin123');
        $user->setFirstname('testadmin');
        $user->setLastname('testadmin');
        $user->setRole(User::ADMIN_ROLE);
        return $user;
    }
    
    public static function setUpBeforeClass() {
        Logging::initLogger(__DIR__);
        Logging::initLogger(__DIR__);
        $um = UserManager::initUserManager('mysql:host=localhost;dbname=runnerupweb;charset=utf8', 'runnerupweb', 'runnerupweb', 100);
        $um->createUser(WSLoginTest::createAdminUser());
    }

    public static function tearDownAfterClass() {
        // delete the cookies file and the admion user created
        system("rm " . __DIR__ . '/cookies.txt');
        $um = UserManager::getUserManager();
        $um->deleteUser(WSLoginTest::createAdminUser()->getLogin());
    }

    private function doCookieLogin($username, $password, $expectedOk) {
        $ch = curl_init('http://localhost/site/authenticate.php?type=json');
        $data = [];
        if ($username) {
            $data['login'] = $username;
        }
        if ($password) {
            $data['password'] = $password;
        }
        $data_string = json_encode($data);
        curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/cookies.txt');
        curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/cookies.txt');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string))
        );
        $result = curl_exec($ch);
        if ($expectedOk) {
            $this->assertEquals(200, curl_getinfo($ch, CURLINFO_HTTP_CODE));
        }
        $response = LoginResponse::responseWithJson($result);
        $this->assertTrue($expectedOk? $response->isSuccess() : !$response->isSuccess());
        if ($response->isSuccess()) {
            $userres = UserResponse::responseWithJson($result);
            $user = $this->createAdminUser();
            $this->assertEquals($user->getLogin(), $userres->getUser()->getLogin());
            $this->assertEquals($user->getFirstname(), $userres->getUser()->getFirstname());
            $this->assertEquals($user->getLastname(), $userres->getUser()->getLastname());
            $this->assertEquals($user->getEmail(), $userres->getUser()->getEmail());
            $this->assertEquals($user->getRole(), $userres->getUser()->getRole());
        }
        curl_close($ch);
        return $response;
    }
    
    function doCookieLogout($expectedHttp, $expectedOk) {
        $ch = curl_init('http://localhost/site/logout.php');
        curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/cookies.txt');
        curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/cookies.txt');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        if ($expectedOk) {
            $this->assertEquals($expectedHttp, curl_getinfo($ch, CURLINFO_HTTP_CODE));
        }
        $response = LoginResponse::responseWithJson($result);
        $this->assertTrue($expectedOk? $response->isSuccess() : !$response->isSuccess());
        curl_close($ch);
        return $response;
    }

    public function testLoginOk() {
        $user = $this->createAdminUser();
        $this->doCookieLogin($user->getLogin(), $user->getPassword(), true);
        $this->doCookieLogout(200, true);
    }

    public function testLoginWrongPassword() {
        $user = $this->createAdminUser();
        $result = $this->doCookieLogin($user->getLogin(), $user->getPassword() . 'KO', false);
        $this->assertEquals(1102, $result->getErrorCode());
    }
    
    public function testLoginWrongLogin() {
        $user = $this->createAdminUser();
        $result = $this->doCookieLogin($user->getLogin() . 'KO', $user->getPassword(), false);
        $this->assertEquals(1102, $result->getErrorCode());
    }
    
    public function testLoginWrongData() {
        $user = $this->createAdminUser();
        $result = $this->doCookieLogin($user->getLogin(), null, false);
        $this->assertEquals(1101, $result->getErrorCode());
    }
    
    public function testLogout403() {
        $this->doCookieLogout(403, false);
    }

}
