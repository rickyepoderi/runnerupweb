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

namespace runnerupweb\common;

use runnerupweb\data\ActivitySearchResponse;
use runnerupweb\data\LoginResponse;
use runnerupweb\data\UserResponse;
use runnerupweb\data\UserOptionResponse;
use runnerupweb\data\UserSearchResponse;

/**
 * Simple curl client to attack the runnerupweb services endpoints.
 * 
 * @author ricky
 */
class Client {
    
    private $baseUrl;
    private $cookies = '';
    
    /**
     * Constructor of the client.
     * @param string $baseUrl The base url for runnerupweb
     */
    public function __construct($baseUrl) {
        $this->baseUrl = $baseUrl;
    }
    
    private function checkHttpError($ch, $validCodes = array(200)) {
        if (!in_array(curl_getinfo($ch, CURLINFO_HTTP_CODE), $validCodes)) {
            throw new \Exception('HTTP error code: ' . curl_getinfo($ch, CURLINFO_HTTP_CODE), curl_getinfo($ch, CURLINFO_HTTP_CODE));
        }
    }
    
    /**
     * Performs a login 
     * @param string $login The username
     * @param string $password The password
     * @return runnerupweb\data\LoginResponse The response
     * @throws Exception Some error
     */
    public function login($login, $password) {
        $ch = curl_init($this-> baseUrl . '/site/authenticate.php?type=json');
        $data = [];
        $data['login'] = $login;
        $data['password'] = $password;
        $dataString = json_encode($data);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($dataString))
        );
        $result = curl_exec($ch);
        $this->checkHttpError($ch);
        $matches = array();
        preg_match_all('|Set-Cookie: (.*);|U', $result, $matches);   
        $this->cookies = implode('; ', $matches[1]);
        $body = mb_substr($result, curl_getinfo($ch,CURLINFO_HEADER_SIZE));
        $response = LoginResponse::responseWithJson($body);
        curl_close($ch);
        return $response;
    }
    
    /**
     * Performs the logout.
     * @return runnerupweb\data\LoginResponse The response
     */
    public function logout() {
        $ch = curl_init($this-> baseUrl . '/site/logout.php');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIE, $this->cookies);
        $result = curl_exec($ch);
        $this->checkHttpError($ch);
        $response = LoginResponse::responseWithJson($result);
        curl_close($ch);
        return $response;
    }
    
    /**
     * Calls to set user.
     * @param runnerupweb\data\User $user
     * @return runnerupweb\data\LoginResponse
     */
    public function setUser($user) {
        $data_string = json_encode($user->jsonSerialize());
        $ch = curl_init($this-> baseUrl . '/rpc/json/user/set_user.php');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_COOKIE, $this->cookies);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string))
        );
        $result = curl_exec($ch);
        $this->checkHttpError($ch);
        curl_close($ch);
        $response = LoginResponse::responseWithJson($result);
        return $response;
    }
    
    /**
     * Gets a user using the login.
     * @param string $login
     * @return runnerupweb\data\UserResponse
     */
    public function getUser($login) {
        $ch = curl_init($this-> baseUrl . '/rpc/json/user/get_user.php?login=' . urlencode($login));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIE, $this->cookies);
        $result = curl_exec($ch);
        $this->checkHttpError($ch);
        curl_close($ch);
        $response = UserResponse::responseWithJson($result);
        return $response;
    }
    
    /**
     * Deletes a user.
     * @param string $login
     * @return runnerupweb\data\LoginResponse
     */
    public function deleteUser($login) {
        $ch = curl_init($this-> baseUrl . '/rpc/json/user/delete_user.php?login=' . urlencode($login));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIE, $this->cookies);
        $result = curl_exec($ch);
        $this->checkHttpError($ch);
        curl_close($ch);
        $response = LoginResponse::responseWithJson($result);
        return $response;
    }
    
    /**
     * Sets the user options.
     * @param runnerupweb\data\UserOption $opts
     * @return runnerupweb\data\LoginResponse
     */
    public function setUserOptions($opts) {
        $data_string = json_encode($opts->jsonSerialize());
        $ch = curl_init($this-> baseUrl . '/rpc/json/user/set_options.php');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIE, $this->cookies);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string))
        );
        $result = curl_exec($ch);
        $this->checkHttpError($ch);
        curl_close($ch);
        $response = LoginResponse::responseWithJson($result);
        return $response;
    }
    
    /**
     * Gets the user options.
     * @return runnerupweb\data\UserOptionResponse
     */
    public function getUserOptions() {
        $ch = curl_init($this-> baseUrl . '/rpc/json/user/get_options.php');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIE, $this->cookies);
        $result = curl_exec($ch);
        $this->checkHttpError($ch);
        curl_close($ch);
        $response = UserOptionResponse::responseWithJson($result);
        return $response;
    }
    
    /**
     * Gets the user definitions.
     * @return runnerupweb\data\UserOptionResponse
     */
    public function getOptionDefinitions() {
        $ch = curl_init($this-> baseUrl . '/rpc/json/user/get_option_definitions.php');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIE, $this->cookies);
        $result = curl_exec($ch);
        $this->checkHttpError($ch);
        curl_close($ch);
        $response = UserOptionResponse::responseWithJson($result);
        return $response;
    }
    
    /**
     * Searches for users.
     * @param string $op
     * @param string $login
     * @param string $firstname
     * @param string $lastname
     * @param string $email
     * @param string $offset
     * @param string $limit
     * @return runnerupweb\data\UserSearchResponse
     */
    public function searchUsers($op, $login, $firstname, $lastname, $email, $offset = null, $limit = null) {
        $url = $this-> baseUrl .  '/rpc/json/user/search.php?';
        if ($op) {
            $url = $url . 'op=' . urlencode($op) . '&';
        }
        if ($login) {
            $url = $url . 'login=' . urlencode($login) . '&';
        }
        if ($firstname) {
            $url = $url . 'firstname=' . urlencode($firstname) . '&';
        }
        if ($lastname) {
            $url = $url . 'lastname=' . urlencode($lastname) . '&';
        }
        if ($email) {
            $url = $url . 'email=' . urlencode($email) . '&';
        }
        if ($offset) {
            $url = $url . 'offset=' . urlencode($offset) . '&';
        }
        if ($limit) {
            $url = $url . 'limit=' . urlencode($limit) . '&';
        }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIE, $this->cookies);
        $result = curl_exec($ch);
        $this->checkHttpError($ch);
        curl_close($ch);
        $response = UserSearchResponse::responseWithJson($result);
        return $response;
    }
    
    /**
     * Uploads a workout.
     * @param string $file The file name to upload
     */
    public function uploadWorkout($file) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this-> baseUrl . '/rpc/json/workout/upload.php');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_COOKIE, $this->cookies);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $realpath = realpath($file);
        $mime = mime_content_type($file);
        if (file_exists($realpath)) {
            $post = array('userFiles' => curl_file_create($realpath, $mime, 
                    pathinfo($realpath, PATHINFO_FILENAME) . '.' . pathinfo($realpath, PATHINFO_EXTENSION)));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
        curl_exec($ch);
        $this->checkHttpError($ch);
        curl_close($ch);
    }
    
    /**
     * Searches for activities or workouts.
     * @param string $start
     * @param string $end
     * @param string $offset
     * @param string $limit
     * @return runnerupweb\data\ActivitySearchResponse
     */
    public function searchWorkouts($start, $end, $offset = null, $limit = null) {
        $url = $this-> baseUrl . '/rpc/json/workout/search.php?';
        if ($start) {
            $url = $url . 'start=' . urlencode($start) . '&';
        }
        if ($end) {
            $url = $url . 'end=' . urlencode($end) . '&';
        }
        if ($offset) {
            $url = $url . 'offset=' . urlencode($offset) . '&';
        }
        if ($limit) {
            $url = $url . 'limit=' . urlencode($limit) . '&';
        }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_COOKIE, $this->cookies);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        $this->checkHttpError($ch);
        curl_close($ch);
        $response = ActivitySearchResponse::responseWithJson($result);
        return $response;
    }
    
    private function http_parse_headers($raw_headers) {
        $headers = array();
        $key = '';
        foreach(explode("\n", $raw_headers) as $i => $h) {
            $h = explode(':', $h, 2);
            if (isset($h[1])) {
                if (!isset($headers[$h[0]])) {
                    $headers[$h[0]] = trim($h[1]);
                } elseif (is_array($headers[$h[0]])) {
                    $headers[$h[0]] = array_merge($headers[$h[0]], array(trim($h[1])));
                } else {
                    $headers[$h[0]] = array_merge(array($headers[$h[0]]), array(trim($h[1])));
                }
                $key = $h[0];
            } else { 
                if (substr($h[0], 0, 1) == "\t") {
                    $headers[$key] .= "\r\n\t".trim($h[0]);
                } elseif (!$key) {
                    $headers[0] = trim($h[0]); 
                }
            }
        }
        return $headers;
    }
    
    /**
     * Returns the activities in a file previously uploaded.
     * @param string $id
     * @param string $etag
     * @param string $last_modified
     * @return array An array with ('activity', 'Etag', 'Last-Modified')
     *               or null if 304 (etag or last_modified were sent and 304 returned)
     */
    public function downloadWorkout($id, $etag = null, $last_modified = null) {
        $return = null;
        $ch = curl_init($this-> baseUrl . '/rpc/json/workout/download.php?id=' . urlencode($id));
        curl_setopt($ch, CURLOPT_COOKIE, $this->cookies);
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
        $header = mb_substr($result, 0, $header_size);
        $headers = $this->http_parse_headers($header);
        $body = mb_substr($result, $header_size);
        $this->checkHttpError($ch, array(200, 304));
        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200) {
          $activities = TCXManager::getTCXManager()->parseString($body);
          $return = array('activity' => $activities[0], 
              'Etag' => $headers['Etag'], 
              'Last-Modified' => $headers['Last-Modified']);
        }
        curl_close($ch);
        return $return;
    }
    
    /**
     * Deletes a workout.
     * @param string $id
     * @return runnerupweb\data\LoginResponse
     */
    public function deleteWorkout($id) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this-> baseUrl . '/rpc/json/workout/delete.php?id=' . urlencode($id));
        curl_setopt($ch, CURLOPT_COOKIE, $this->cookies);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        $this->checkHttpError($ch);
        curl_close($ch);
        $response = LoginResponse::responseWithJson($result);
        return $response;
    }
}