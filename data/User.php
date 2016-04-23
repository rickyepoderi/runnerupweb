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

namespace runnerupweb\data;

use runnerupweb\common\Logging;

/**
 * Class that represents a user in the application.
 *
 * @author ricky
 */
class User implements \JsonSerializable {
    
    const USER_ROLE='USER';
    const ADMIN_ROLE='ADMIN';
    
    private $login;
    private $password;
    private $role;
    private $email;
    private $firstname;
    private $lastname;
    
    public function __construct() {
        // noop
    }
    
    /**
     * Static method to create a User with the login name.
     * @param type $login The login name
     * @return User The user
     */
    public static function userWithLogin($login) {
        $user = new User();
        $user->login = $login;
        return $user;
    }
    
    /**
     * Static method to create a user using the json representation.
     * @param string $json The jhson code
     * @return User The user
     */
    public static function userWithJson($json) {
        $val = json_decode($json, true);
        return User::userWithAssoc($val);
    }
    
    /**
     * Static method to create a user from the assoc values.
     * @param mixed $assoc The assoc with the values
     * @return User The user
     */
    public static function userWithAssoc($assoc) {
        $user = new User();
        $user->login = isset($assoc['login'])? $assoc['login']:null;
        $user->password = isset($assoc['password'])? $assoc['password']:null;
        $user->role = isset($assoc['role'])? $assoc['role']:null;
        $user->email = isset($assoc['email'])? $assoc['email']:null;
        $user->firstname = isset($assoc['firstname'])? $assoc['firstname']:null;
        $user->lastname = isset($assoc['lastname'])? $assoc['lastname']:null;
        return $user;
    }
    
    /**
     * Getter for the login.
     * @return string
     */
    function getLogin() {
        return $this->login;
    }

    /**
     * Setter for the login.
     * @param string $login
     */
    function setLogin($login) {
        $this->login = $login;
    }

    /**
     * Getter for the password.
     * @return string
     */
    function getPassword() {
        return $this->password;
    }
    
    /**
     * Setter for the password.
     * @param string $password
     */
    function setPassword($password) {
        $this->password = $password;
    }
    
    /**
     * Getter for the role.
     * @return string
     */
    function getRole() {
        return $this->role;
    }

    /**
     * Getter for the firstname.
     * @return string
     */
    function getFirstname() {
        return $this->firstname;
    }

    /**
     * Setter for the firstname.
     * @param string $firstname
     */
    function setFirstname($firstname) {
        $this->firstname = $firstname;
    }
    
    /**
     * Getter for the lastname.
     * @return string
     */
    function getLastname() {
        return $this->lastname;
    }

    /**
     * Setter for the lastname.
     * @param string $lastname
     */
    function setLastname($lastname) {
        $this->lastname = $lastname;
    }

    /**
     * Setter for the role.
     * @param string $role
     */
    function setRole($role) {
        $this->role = $role;
    }

    /**
     * Setter for the role.
     * @return string
     */
    function getEmail() {
        return $this->email;
    }
    
    /**
     * Setter for the email.
     * @param string $email
     */
    function setEmail($email) {
        $this->email = $email;
    }

    /**
     * Method that checks if the user is well filled to update or create.
     * @param type $isnew whether the user is new (password is needed) or not
     * @return bool true if ok, false otherwise
     */
    function checkUser($isnew = false) {
        $res = !is_null($this->login)  && !preg_match("/[^A-Za-z0-9_\-\.]/", $this->login)
            && ($this->role === "USER" || $this->role === "ADMIN")
            && strlen($this->login) <= 64
            && (is_null($this->firstname) || strlen($this->firstname) <= 100)
            && (is_null($this->lastname) || strlen($this->lastname) <= 100)
            && (is_null($this->email) || (strlen($this->email) <= 100 && filter_var($this->email, FILTER_VALIDATE_EMAIL)))
            && (!$isnew || !is_null($this->password));
        Logging::debug("res: " . $res);
        return $res;
    }

    /**
     * 
     * @return array
     */
    public function jsonSerialize() {
        return [
                'login' => $this->login,
                'password' => $this->password,
                'role' => $this->role,
                'firstname' => $this->firstname,
                'lastname' => $this->lastname,
                'email' => $this->email,
            ];
    }

}
