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
use runnerupweb\common\Logging;
use runnerupweb\data\LoginResponse;
use runnerupweb\data\UserResponse;
use runnerupweb\data\ActivitySearchResponse;
use runnerupweb\data\Activity;

/**
 * Description of WSWorkoutTest
 *
 * @author ricky
 */
class WSWorkoutTest extends PHPUnit_Framework_TestCase {
    
    public static function setUpBeforeClass() {
        Logging::initLogger(__DIR__);
        // init the TCX manager for read the downloaded activities
        TCXManager::initTCXManager("/tmp", 100, false);
        // create the admin user
        $um = UserManager::initUserManager('mysql:host=localhost;dbname=runnerupweb;charset=utf8', 'runnerupweb', 'runnerupweb', 100);
        $admin = WSWorkoutTest::createAdminUser();
        $um->createUser($admin);
        // create the normal user using WS
        $test = new WSWorkoutTest();
        $test->doCookieLogin($admin);
        $test->doSetUser($test->createUser());
        $test->doCookieLogout();
    }

    public static function tearDownAfterClass() {
        // delete the common user and store
        $admin = WSWorkoutTest::createAdminUser();
        $test = new WSWorkoutTest();
        $test->doCookieLogin($admin);
        $test->doDelete($test->createUser()->getLogin());
        $test->doCookieLogout();
        // delete the uadmin user
        $um = UserManager::getUserManager();
        $um->deleteUser(WSWorkoutTest::createAdminUser()->getLogin());
        // delete the cookies file
        system("rm " . __DIR__ . '/cookies.txt');
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
    
    private function doUpload($file, $code = 200, $mime = 'application/xml') {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://localhost/rpc/json/workout/upload.php');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/cookies.txt');
        curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/cookies.txt');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $realpath = realpath($file);
        if (file_exists($realpath)) {
            $post = array('userFiles' => curl_file_create($realpath, $mime, 
                    pathinfo($realpath, PATHINFO_FILENAME) . '.' . pathinfo($realpath, PATHINFO_EXTENSION)));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
        curl_exec($ch);
        $this->assertEquals($code, curl_getinfo($ch, CURLINFO_HTTP_CODE));
        curl_close($ch);
    }
    
    private function doSearch($start, $end, $offset = null, $limit = null, $logged = true) {
        $url = 'http://localhost/rpc/json/workout/search.php?';
        if ($start) {
            $url = $url . 'start=' . $start . '&';
        }
        if ($end) {
            $url = $url . 'end=' . $end . '&';
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
            $response = ActivitySearchResponse::responseWithJson($result);
            return $response;
        } else {
            return null;
        }
    }
    
    private function http_parse_headers($header) {
        $retVal = array();
        $fields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $header));
        foreach ($fields as $field) {
            if (preg_match('/([^:]+): (.+)/m', $field, $match)) {
                $match[1] = preg_replace('/(?<=^|[\x09\x20\x2D])./e', 'strtoupper("\0")', strtolower(trim($match[1])));
                if (isset($retVal[$match[1]])) {
                    $retVal[$match[1]] = array($retVal[$match[1]], $match[2]);
                } else {
                    $retVal[$match[1]] = trim($match[2]);
                }
            }
        }
        return $retVal;
    }  
    
    private function doDownload($id, $code = 200, $etag = null, $last_modified = null) {
        $ch = curl_init('http://localhost/rpc/json/workout/download.php?id=' . $id);
        curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/cookies.txt');
        curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/cookies.txt');
        curl_setopt($ch, CURLOPT_ACCEPT_ENCODING, 'gzip');
        if ($etag || $last_modified) {
            $req_headers = array();
            if ($etag) {
                $req_headers[] = "If-None-Match: $etag";
            }
            if ($last_modified) {
                $req_headers[] = "If-Modified-Since: $last_modified";
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $req_headers);
        }
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        $result = curl_exec($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($result, 0, $header_size);
        $headers = $this->http_parse_headers($header);
        $body = substr($result, $header_size);
        $this->assertEquals($code, curl_getinfo($ch, CURLINFO_HTTP_CODE));
        curl_close($ch);
        if ($code === 200) {
            $activities = TCXManager::getTCXManager()->parseString($body);
            $this->assertEquals(1, count($activities));
            return array('activity' => $activities[0], 'Etag' => $headers['Etag'], 'Last-Modified' => $headers['Last-Modified']);
        } else {
            return null;
        }
    }
    
    public function test403() {
        $this->doUpload(__DIR__ . '/FitnessHistoryDetail.tcx', 403);
        $this->doSearch(null, null, null, null, false);
        $this->doDownload(1, 403);
    }
    
    /**
     * @depends test403
     */
    public function testUpload() {
        $user = $this->createUser();
        // login
        $this->doCookieLogin($user);
        // check error because no file
        $this->doUpload(__DIR__ . '/filedoesnotexists.tcx', 500);
        // check invalid mime type
        $this->doUpload(__DIR__ . '/../public/resources/images/runnerupweb-white.png', 500, 'image/png');
        // upload three files to the user (the sample.tcx contains two activities) successfully
        $this->doUpload(__DIR__ . '/FitnessHistoryDetail.tcx');
        $this->doUpload(__DIR__ . '/sample.tcx');
        $this->doUpload(__DIR__ . '/runnerup.tcx');
        // logout
        $this->doCookieLogout();
    }
    
    public function testSearch() {
        $user = $this->createUser();
        // login
        $this->doCookieLogin($user);
        // invalid start time
        $r1 = $this->doSearch('not a valid date', date('Y-m-d\TH:i:s\Z'));
        $this->assertFalse($r1->isSuccess());
        $this->assertEquals(1, $r1->getErrorCode());
        // empty start time
        $r2 = $this->doSearch(null, date('Y-m-d\TH:i:s\Z'));
        $this->assertFalse($r2->isSuccess());
        $this->assertEquals(1, $r2->getErrorCode());
        // invalid end time
        $r3 = $this->doSearch(date('Y-m-d\TH:i:s\Z', mktime(0, 0, 0, 1, 1, 2000)), 'invalid end time');
        $this->assertFalse($r3->isSuccess());
        $this->assertEquals(2, $r3->getErrorCode());
        // invalid offset
        $r4 = $this->doSearch(date('Y-m-d\TH:i:s\Z', mktime(0, 0, 0, 1, 1, 2000)), date('Y-m-d\TH:i:s\Z'), -1, null);
        $this->assertFalse($r4->isSuccess());
        $this->assertEquals(3, $r4->getErrorCode());
        // invalid limit
        $r5 = $this->doSearch(date('Y-m-d\TH:i:s\Z', mktime(0, 0, 0, 1, 1, 2000)), date('Y-m-d\TH:i:s\Z'), 0, 1000000);
        $this->assertFalse($r5->isSuccess());
        $this->assertEquals(4, $r5->getErrorCode());
        // search the three four activities
        $r6 = $this->doSearch(date('Y-m-d\TH:i:s\Z', mktime(0, 0, 0, 1, 1, 2000)), date('Y-m-d\TH:i:s\Z'));
        $this->assertTrue($r6->isSuccess());
        $this->assertEquals(4, count($r6->getActivities()));
        $this->assertEquals(2015, $r6->getActivities()[0]->getStartTime()->format('Y'));
        $this->assertEquals(2008, $r6->getActivities()[1]->getStartTime()->format('Y'));
        $this->assertEquals(2008, $r6->getActivities()[2]->getStartTime()->format('Y'));
        $this->assertEquals(2007, $r6->getActivities()[3]->getStartTime()->format('Y'));
        // search the two activities in 2008
        $r7 = $this->doSearch(date('Y-m-d\TH:i:s\Z', mktime(0, 0, 0, 1, 1, 2008)), date('Y-m-d\TH:i:s\Z', mktime(0, 0, 0, 31, 12, 2008)));
        $this->assertTrue($r7->isSuccess());
        $this->assertEquals(2, count($r7->getActivities()));
        // perform the same search but with limit 2
        $r8 = $this->doSearch(date('Y-m-d\TH:i:s\Z', mktime(0, 0, 0, 1, 1, 2000)), date('Y-m-d\TH:i:s\Z'), 0, 2);
        $this->assertTrue($r8->isSuccess());
        $this->assertEquals(2, count($r8->getActivities()));
        $this->assertEquals(2015, $r8->getActivities()[0]->getStartTime()->format('Y'));
        $this->assertEquals(2008, $r8->getActivities()[1]->getStartTime()->format('Y'));
        // continue with the second
        $r9 = $this->doSearch(date('Y-m-d\TH:i:s\Z', mktime(0, 0, 0, 1, 1, 2000)), date('Y-m-d\TH:i:s\Z'), 2, 2);
        $this->assertTrue($r9->isSuccess());
        $this->assertEquals(2, count($r9->getActivities()));
        $this->assertEquals(2008, $r9->getActivities()[0]->getStartTime()->format('Y'));
        $this->assertEquals(2007, $r9->getActivities()[1]->getStartTime()->format('Y'));
        // logout
        $this->doCookieLogout();
    }
    
    public function testDownload() {
        $user = $this->createUser();
        // login
        $this->doCookieLogin($user);
        // download a non existant activity
        $this->doDownload(-1, 404);
        // search the for activities
        $r1 = $this->doSearch(date('Y-m-d\TH:i:s\Z', mktime(0, 0, 0, 1, 1, 2000)), date('Y-m-d\TH:i:s\Z'));
        $this->assertTrue($r1->isSuccess());
        $this->assertEquals(4, count($r1->getActivities()));
        // download the last activity and compare with the real file
        $r2 = $this->doDownload($r1->getActivities()[3]->getId());
        $real = TCXManager::getTCXManager()->parse(__DIR__ . '/FitnessHistoryDetail.tcx')[0];
        $this->assertEquals($real, $r2['activity']);
        // check 304 if Etag
        $this->doDownload($r1->getActivities()[3]->getId(), 304, $r2['Etag']);
        // check 304 if modified since
        $this->doDownload($r1->getActivities()[3]->getId(), 304, null, $r2['Last-Modified']);
        // check 304 if both
        $this->doDownload($r1->getActivities()[3]->getId(), 304, $r2['Etag'], $r2['Last-Modified']);
        // logout
        $this->doCookieLogout();
    }
    
}
