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
use runnerupweb\common\Logging;
use runnerupweb\data\LoginResponse;
use runnerupweb\data\UserResponse;
use runnerupweb\data\UserOption;
use runnerupweb\data\UserOptionResponse;
use runnerupweb\data\UserSearchResponse;

/**
 * Description of WSUserTest
 *
 * @author ricky
 */
class WSUserTest extends PHPUnit_Framework_TestCase {
    
    public static function setUpBeforeClass() {
        Logging::initLogger(__DIR__);
        Logging::initLogger(__DIR__);
        $um = UserManager::initUserManager('mysql:host=localhost;dbname=runnerupweb;charset=utf8', 'runnerupweb', 'runnerupweb', 100);
        $um->createUser(WSUserTest::createAdminUser());
    }

    public static function tearDownAfterClass() {
        // delete the cookies file and the admion user created
        system("rm " . __DIR__ . '/cookies.txt');
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
        $opts->set("activity.calculation.period", "25");
        $opts->set("preferred.unit.altitude", "km");
        $opts->set("activity.graphic.altitude.minimum", "100");
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

    private function doCookieLogin($user) {
        $ch = curl_init('http://localhost/site/authenticate.php?type=json');
        $data = [];
        $data['login'] = $user->getLogin();
        $data['password'] = $user->getPassword();
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
        $this->assertEquals(200, curl_getinfo($ch, CURLINFO_HTTP_CODE));
        $response = LoginResponse::responseWithJson($result);
        $this->assertTrue($response->isSuccess());
        if ($response->isSuccess()) {
            $userres = UserResponse::responseWithJson($result);
            $this->checkUsers($user, $userres->getUser());
        }
        curl_close($ch);
        return $response;
    }
    
    private function doCookieLogout() {
        $ch = curl_init('http://localhost/site/logout.php');
        curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/cookies.txt');
        curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/cookies.txt');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        $this->assertEquals(200, curl_getinfo($ch, CURLINFO_HTTP_CODE));
        $response = LoginResponse::responseWithJson($result);
        $this->assertTrue($response->isSuccess());
        curl_close($ch);
        return $response;
    }
    
    private function doSetUser($user, $logged = true) {
        $data_string = json_encode($user->jsonSerialize());
        $ch = curl_init('http://localhost/rpc/json/user/set_user.php');
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
        $this->assertEquals($logged? 200 : 403, curl_getinfo($ch, CURLINFO_HTTP_CODE));
        curl_close($ch);
        if ($logged) {
            $response = LoginResponse::responseWithJson($result);
            return $response;
        } else {
            return null;
        }
    }
    
    private function doGetUser(User $user, $logged = true) {
        $ch = curl_init('http://localhost/rpc/json/user/get_user.php?login=' . $user->getLogin());
        curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/cookies.txt');
        curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/cookies.txt');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        $this->assertEquals($logged? 200 : 403, curl_getinfo($ch, CURLINFO_HTTP_CODE));
        curl_close($ch);
        if ($logged) {
            $response = UserResponse::responseWithJson($result);
            return $response;
        } else {
            return null;
        }
    }
    
    private function doSetOptions($opts, $logged = true) {
        $data_string = json_encode($opts->jsonSerialize());
        $ch = curl_init('http://localhost/rpc/json/user/set_options.php');
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
        $this->assertEquals($logged? 200 : 403, curl_getinfo($ch, CURLINFO_HTTP_CODE));
        curl_close($ch);
        if ($logged) {
            $response = LoginResponse::responseWithJson($result);
            return $response;
        } else {
            return null;
        }
    }
    
    private function doGetOptions($logged = true) {
        $ch = curl_init('http://localhost/rpc/json/user/get_options.php');
        curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/cookies.txt');
        curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/cookies.txt');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        $this->assertEquals($logged? 200 : 403, curl_getinfo($ch, CURLINFO_HTTP_CODE));
        curl_close($ch);
        if ($logged) {
            $response = UserOptionResponse::responseWithJson($result);
            return $response;
        } else {
            return null;
        }
    }
    
    private function doGetOptionDefinitions($logged = true) {
        $ch = curl_init('http://localhost/rpc/json/user/get_option_definitions.php');
        curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/cookies.txt');
        curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/cookies.txt');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        $this->assertEquals($logged? 200 : 403, curl_getinfo($ch, CURLINFO_HTTP_CODE));
        curl_close($ch);
        if ($logged) {
            $response = UserOptionResponse::responseWithJson($result);
            return $response;
        } else {
            return null;
        }
    }
    
    private function doSearch($op, $login, $firstname, $lastname, $email, $offset = null, $limit = null, $logged = true) {
        $url = 'http://localhost/rpc/json/user/search.php?';
        if ($op) {
            $url = $url . 'op=' . $op . '&';
        }
        if ($login) {
            $url = $url . 'login=' . $login . '&';
        }
        if ($firstname) {
            $url = $url . 'firstname=' . $firstname . '&';
        }
        if ($lastname) {
            $url = $url . 'lastname=' . $lastname . '&';
        }
        if ($email) {
            $url = $url . 'email=' . $email . '&';
        }
        if ($offset) {
            $url = $url . 'offset=' . $offset . '&';
        }
        if ($limit) {
            $url = $url . 'limit=' . $limit . '&';
        }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/cookies.txt');
        curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/cookies.txt');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        $this->assertEquals($logged? 200 : 403, curl_getinfo($ch, CURLINFO_HTTP_CODE));
        curl_close($ch);
        if ($logged) {
            $response = UserSearchResponse::responseWithJson($result);
            return $response;
        } else {
            return null;
        }
    }
    
    private function doDelete($login, $logged = true) {
        $ch = curl_init('http://localhost/rpc/json/user/delete_user.php?login=' . $login);
        curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/cookies.txt');
        curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/cookies.txt');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        $this->assertEquals($logged? 200 : 403, curl_getinfo($ch, CURLINFO_HTTP_CODE));
        curl_close($ch);
        if ($logged) {
            $response = LoginResponse::responseWithJson($result);
            return $response;
        } else {
            return null;
        }
    }
    
    public function test403() {
        $admin = $this->createAdminUser();
        $opts = $this->createUserOptions();
        $this->doSetUser($admin, false);
        $this->doGetUser($admin, false);
        $this->doSetOptions($opts, false);
        $this->doGetOptions(false);
        $this->doGetOptionDefinitions(false);
        $this->doSearch(null, null, null, null, null, null, null, false);
        $this->doDelete($admin->getLogin(), false);
    }
    
    /**
     * @depends test403
     */
    public function testCreateOk() {
        $admin = $this->createAdminUser();
        $user = $this->createUser();
        // login
        $this->doCookieLogin($admin);
        // check error creating a bad filled user
        $user->setPassword(null);
        $r1 = $this->doSetUser($user);
        $this->assertFalse($r1->isSuccess());
        $this->assertEquals(4, $r1->getErrorCode());
        // create the user ok
        $user->setPassword($this->createUser()->getPassword());
        $r2 = $this->doSetUser($user);
        $this->assertTrue($r2->isSuccess());
        // logout
        $this->doCookieLogout();
    }
    
    
    /**
     * @depends testCreateOk
     */
    public function testAdmin403() {
        $admin = $this->createUser();
        // login
        $this->doCookieLogin($admin);
        // check non admin methods
        $this->doSearch(null, null, null, null, null, null, null, false);
        $this->doDelete($admin->getLogin(), false);
        // logout
        $this->doCookieLogout();
    }
    
    /**
     * @depends testAdmin403
     */
    public function testCreateKONonAdmin() {
        $admin = $this->createUser();
        // login
        $this->doCookieLogin($admin);
        // create user => error
        $user = $this->createUser("test2");
        $r1 = $this->doSetUser($user);
        $this->assertFalse($r1->isSuccess());
        $this->assertEquals(5, $r1->getErrorCode());
        // logout
        $this->doCookieLogout();
    }
    
    /**
     * @depends testCreateKONonAdmin
     */
    public function testUpdateUserAdmin() {
        $admin = $this->createAdminUser();
        // login
        $this->doCookieLogin($admin);
        // check error creating a bad filled user
        $user = $this->createUser();
        $user->setEmail("lalalalalaal");
        $r1 = $this->doSetUser($user);
        $this->assertFalse($r1->isSuccess());
        $this->assertEquals(1, $r1->getErrorCode());
        // modify the user email
        $user->setEmail("lala@lala.com");
        $r2 = $this->doSetUser($user);
        $this->assertTrue($r2->isSuccess());
        // modify the user password
        $user->setPassword($user->getPassword() . "123");
        $r3 = $this->doSetUser($user);
        $this->assertTrue($r3->isSuccess());
        // check the user
        $r4 = $this->doGetUser($user);
        $this->assertTrue($r4->isSuccess());
        $this->checkUsers($user, $r4->getUser());
        // logout
        $this->doCookieLogout();
    }
    
    /**
     * @depends testUpdateUserAdmin
     */
    public function testUpdateUserNonAdmin() {
        $admin = $this->createUser();
        $admin->setPassword($admin->getPassword() . "123");
        $admin->setEmail("lala@lala.com");
        $this->doCookieLogin($admin);
        // try to modify the admin user
        $u1 = $this->createAdminUser();
        $u1->setEmail("lala@lala.com");
        $r1 = $this->doSetUser($u1);
        $this->assertFalse($r1->isSuccess());
        $this->assertEquals(3, $r1->getErrorCode());
        // try to modify my role to admin
        $u2 = $this->createUser();
        $u2->setRole(User::ADMIN_ROLE);
        $r2 = $this->doSetUser($u2);
        $this->assertFalse($r2->isSuccess());
        $this->assertEquals(2, $r2->getErrorCode());
        // try to modify myself back to the begining
        $u2->setRole(User::USER_ROLE);
        $r3 = $this->doSetUser($u2);
        $this->assertTrue($r3->isSuccess());
        // read and check
        $r4 = $this->doGetUser($u2);
        $this->assertTrue($r4->isSuccess());
        $this->checkUsers($u2, $r4->getUser());
        $this->doCookieLogout();
    }
    
    /**
     * @depends testUpdateUserNonAdmin
     */
    public function testSetOptions() {
        $admin = $this->createUser();
        // login
        $this->doCookieLogin($admin);
        // set some bad properties
        $opts = $this->createUserOptions();
        $opts->set("prop.not.exists", "value");
        $r1 = $this->doSetOptions($opts);
        $this->assertFalse($r1->isSuccess());
        $this->assertEquals(1, $r1->getErrorCode());
        // set ok
        $opts->remove("prop.not.exists");
        $r2 = $this->doSetOptions($opts);
        $this->assertTrue($r2->isSuccess());
        // read and compare
        $r3 = $this->doGetOptions();
        $this->assertTrue($r3->isSuccess());
        $this->checkUserOptions($opts, $r3->getOptions());
        // logout
        $this->doCookieLogout();
    }
    
    /**
     * @depends testSetOptions
     */
    public function testGetOptionDefinitions() {
        $admin = $this->createUser();
        // login
        $this->doCookieLogin($admin);
        // get the definitions
        $r1 = $this->doGetOptionDefinitions();
        $this->assertTrue($r1->isSuccess());
        $this->assertNotNull($r1->getOptions());
        $this->assertTrue(count($r1->getOptions()->flat()) > 0);
        // logout
        $this->doCookieLogout();
    }
    
    /**
     * @depends testGetOptionDefinitions
     */
    public function testSearch() {
        $admin = $this->createAdminUser();
        $user = $this->createUser();
        // login
        $this->doCookieLogin($admin);
        // invalid op
        $r1 = $this->doSearch("OP_LALA", "lala", null, null, null);
        $this->assertFalse($r1->isSuccess());
        $this->assertEquals(1, $r1->getErrorCode());
        // invalid login sent
        $r2 = $this->doSearch("OP_EQUALS", "lala@lala", null, null, null);
        $this->assertFalse($r2->isSuccess());
        $this->assertEquals(2, $r2->getErrorCode());
        // invalid offset
        $r3 = $this->doSearch("OP_EQUALS", $user->getLogin(), null, null, null, "-1", null);
        $this->assertFalse($r3->isSuccess());
        $this->assertEquals(3, $r3->getErrorCode());
        // invalid limit
        $r4 = $this->doSearch("OP_EQUALS", $user->getLogin(), null, null, null, "0", "1000000");
        $this->assertFalse($r4->isSuccess());
        $this->assertEquals(4, $r4->getErrorCode());
        // search by login
        $r5 = $this->doSearch("OP_EQUALS", $user->getLogin(), null, null, null);
        $this->assertTrue($r5->isSuccess());
        $this->assertEquals(1, count($r5->getUsers()));
        $this->checkUsers($user, $r5->getUsers()[0]);
        // search by firstname
        $r6 = $this->doSearch("OP_ENDS_WITH", null, $user->getFirstname(), null, null);
        $this->assertTrue($r6->isSuccess());
        $this->assertEquals(1, count($r6->getUsers()));
        $this->checkUsers($user, $r6->getUsers()[0]);
        // search by lastname
        $r7 = $this->doSearch("OP_STARTS_WITH", null, null, $user->getLastname(), null);
        $this->assertTrue($r7->isSuccess());
        $this->assertEquals(1, count($r7->getUsers()));
        $this->checkUsers($user, $r7->getUsers()[0]);
        // search by email
        $r8 = $this->doSearch("OP_CONTAINS", null, null, null, $user->getEmail());
        $this->assertTrue($r8->isSuccess());
        $this->assertEquals(1, count($r8->getUsers()));
        $this->checkUsers($user, $r8->getUsers()[0]);
        // search by all
        $r9 = $this->doSearch("OP_EQUALS", $user->getLogin(), $user->getFirstname(), $user->getLastname(), $user->getEmail());
        $this->assertTrue($r9->isSuccess());
        $this->assertEquals(1, count($r9->getUsers()));
        $this->checkUsers($user, $r9->getUsers()[0]);
        // logout
        $this->doCookieLogout();
    }
    
    /**
     * @depends testSearch
     */
    public function testDelete() {
        $admin = $this->createAdminUser();
        $user = $this->createUser();
        // login
        $this->doCookieLogin($admin);
        // check invalid login
        $r1 = $this->doDelete(null);
        $this->assertFalse($r1->isSuccess());
        $this->assertEquals(1, $r1->getErrorCode());
        // check user not exists
        $r2 = $this->doDelete("thisuserdoesnotexists");
        $this->assertFalse($r2->isSuccess());
        $this->assertEquals(2, $r2->getErrorCode());
        // delete the user
        $r3 = $this->doDelete($user->getLogin());
        $this->assertTrue($r3->isSuccess());
        // get the user and not exists
        $r4 = $this->doGetUser($user);
        $this->assertFalse($r4->isSuccess());
        $this->assertEquals(2, $r4->getErrorCode());
        // logout
        $this->doCookieLogout();
    }
}
