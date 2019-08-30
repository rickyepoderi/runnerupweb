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

namespace runnerupweb\common;
use runnerupweb\common\DataBase;
use runnerupweb\data\User;

/**
 * Manager to manage Users. CRUD methods against the database.
 * 
 * CREATE DATABASE `runnerupweb` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
 * CREATE USER 'runnerupweb' IDENTIFIED BY 'runnerupweb';
 * GRANT ALL ON runnerupweb.* TO 'runnerupweb'@'localhost' IDENTIFIED BY 'runnerupweb';
 * 
 * CREATE TABLE `user` (
 *  `login` varchar(64) NOT NULL,
 *  `password` varchar(255) NOT NULL,
 *  `firstname` varchar(100) DEFAULT NULL,
 *  `lastname` varchar(100) DEFAULT NULL,
 *  `email` varchar(100) DEFAULT NULL,
 *  `role` enum('USER','ADMIN') NOT NULL,
 *  PRIMARY KEY (`login`)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
 * 
 * @author ricky
 */
class UserManager extends DataBase {
    
    static private $userManager;
    
    const OP_STARTS_WITH = 0;
    const OP_ENDS_WITH = 1;
    const OP_CONTAINS = 2;
    const OP_EQUALS = 3;
    
    protected function __construct($url, $username, $password, $maxrows) {
        parent::__construct($url, $username, $password, $maxrows);
    }
    
    /**
     * Initialize the UserManager against the database.
     * 
     * @param type $url The URL to connect to the database
     * @param type $username The username to connect 
     * @param type $password The password to connect
     * @param type $maxrows The max rows to return
     * @return UserManager The singleton UserManager initialized
     */
    static public function initUserManager($url, $username, $password, $maxrows): UserManager {
        static::$userManager = new UserManager($url, $username, $password, $maxrows);
        return static::getUserManager();
    }
    
    /**
     * Getter for the UserManager singleton.
     * 
     * @return UserManager The user manager singleton
     */
    static public function getUserManager(): UserManager {
        return static::$userManager;
    }
    
    /**
     * Method to create a user in the ddbb.
     * @param User $user The user to create
     * @return User The same user created
     * @throws \runnerupweb\common\Exception
     */
    public function createUser(User $user): User {
        if ($user->getPassword() != null) {
            $prevpwd = $user->getPassword();
            $password = hash('sha512', $prevpwd);
            $user->setPassword($password);
        } else {
            $prevpwd = null;
        }
        $db = $this->getConnection();
        try {
            $stmt = $db->prepare("INSERT INTO user(login, password, firstname, lastname, email, role) VALUES(?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user->getLogin(), $user->getPassword(), 
                $user->getFirstname(), $user->getLastname(), $user->getEmail(), $user->getRole()]);
            $db->commit();
            $user->setPassword($prevpwd);
            return $user;
        } catch (Exception $ex) {
            $db->rollback();
            throw $ex;
        }
    }
    
    /**
     * Return a user or null.
     * @param string $login The login to search
     * @return User User or null
     * @throws \runnerupweb\common\Exception
     */
    public function getUser(string $login): ?User {
        $db = $this->getConnection();
        try {
            $stmt = $db->prepare("SELECT firstname, lastname, email, role FROM user WHERE login = ?");
            $stmt->execute([$login]);
            $db->commit();
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($row != null) {
                $user = User::userWithLogin($login);
                $user->setFirstname($row['firstname']);
                $user->setLastname($row['lastname']);
                $user->setEmail($row['email']);
                $user->setRole($row['role']);
                return $user;
            } else {
                return null;
            }
        } catch (Exception $ex) {
            $db->rollback();
            throw $ex;
        }
    }
    
    /**
     * Method to check a user password. It returns the user if the password
     * is ok.
     * 
     * @param string $login The login of the user
     * @param string $password The password to check
     * @return User The user if password ok, null otherwise
     * @throws \runnerupweb\common\Exception
     */
    public function checkUserPassword(string $login, string $password): ?User {
        $db = $this->getConnection();
        try {
            $stmt = $db->prepare("SELECT password, firstname, lastname, email, role FROM user WHERE login = ?");
            $stmt->execute([$login]);
            $db->commit();
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($row != null) {
                $enc = hash('sha512', $password);
                if ($enc === $row['password']) {
                    $user = User::userWithLogin($login);
                    $user->setFirstname($row['firstname']);
                    $user->setLastname($row['lastname']);
                    $user->setEmail($row['email']);
                    $user->setRole($row['role']);
                    return $user;
                } else {
                    return null;
                }
            } else {
                return null;
            }
        } catch (Exception $ex) {
            $db->rollback();
            throw $ex;
        }
    }
    
    /**
     * Method that updates the user passed.
     * @param User $user The user to update
     * @return User The same user updated
     * @throws \runnerupweb\common\Exception
     * @throws \PDOException
     */
    public function updateUser(User $user): User {
        $db = $this->getConnection();
        try {
            $sql = "UPDATE user SET firstname=?, lastname=?, email=?, role=?";
            if ($user->getPassword() != null) {
                $sql = $sql . ", password = ?";
                $user->setPassword(hash('sha512', $user->getPassword()));
            }
            $sql = $sql . " WHERE login = ?";
            Logging::debug("Executing: " . $sql);
            $stmt = $db->prepare($sql);
            if ($user->getPassword() != null) {
                $stmt->execute([$user->getFirstname(), $user->getLastname(), $user->getEmail(), $user->getRole(), $user->getPassword(), $user->getLogin()]);
            } else {
                $stmt->execute([$user->getFirstname(), $user->getLastname(), $user->getEmail(), $user->getRole(), $user->getLogin()]);
            }
            if ($stmt->rowCount() === 0) {
                Logging::debug("Row count: ", array($stmt->rowCount()));
                throw new \PDOException("The user does not exists!");
            }
            $db->commit();
            $user->setPassword(null);
            return $user;
        } catch (Exception $ex) {
            $db->rollback();
            throw $ex;
        }
    }
    
    /**
     * Method that deletes a user in the ddbb.
     * @param string $login The login to delete
     * @return bool user was deleted
     * @throws Exception 
     */
    public function deleteUser(string $login): bool {
        $db = $this->getConnection();
        try {
            $stmt = $db->prepare("DELETE FROM user WHERE login = ?");
            $stmt->execute([$login]);
            $db->commit();
            return $stmt->rowCount() === 1;
        } catch (Exception $ex) {
            $db->rollback();
            throw $ex;
        }
    }
    
    /**
     * 
     * @param string $name
     * @param int $op
     * @param string $value
     * @param bool $first
     * @param string[] $parameters
     * @return string
     * @throws Exception
     */
    private function assignOperation(string $name, int $op, string $value, bool $first, array &$parameters): string {
        $res = "";
        if ($value) {
            switch ($op) {
                case self::OP_STARTS_WITH:
                    $res = ($first? ' WHERE ':' AND ') . $name . ' LIKE ?';
                    array_push($parameters, $value . '%');
                    break;
                case self::OP_ENDS_WITH:
                    $res = ($first? ' WHERE ':' AND ') . $name . ' LIKE ?';
                    array_push($parameters, '%' . $value);
                    break;
                case self::OP_CONTAINS:
                    $res = ($first? ' WHERE ':' AND ') . $name . ' LIKE ?';
                    array_push($parameters, '%' . $value . '%');
                    break;
                case self::OP_EQUALS:
                    $res = ($first? ' WHERE ':' AND ') . $name . '=?';
                    array_push($parameters, $value);
                    break;
                default:
                    throw new \Exception('Invalid Operation');
            }
        }
        return $res;
    }
    
    /**
     * Method that perform a search over the user table based on the login,
     * firstname and lastname. The search uses AND using the same operation
     * over the three fields.
     * 
     * @param string $login Login value to search
     * @param string $firstname Firstname value to search
     * @param string $lastname Lastname value to search
     * @param string $email email to search
     * @param int $op The operation to use
     * @param int $offset for paged searches (default to 0)
     * @param int $limit for pages searches (default to 0 => transformed to maxrows)
     * @return User[] Array of Users
     */
    public function search(?string $login, ?string $firstname, ?string $lastname, ?string $email, int $op, ?int $offset = null, ?int $limit = null): array {
        Logging::debug("search $login $firstname $lastname $op $offset $limit");
        $limit = ($limit == null)? $this->maxrows : $limit;
        $offset = ($offset == null)? 0 : $offset;
        $res = [];
        $db = $this->getConnection();
        $parameters = [];
        try {
            $sql = "SELECT login, firstname, lastname, email, role FROM user";
            $first = true;
            if ($login) {
                Logging::debug("adding login filter");
                $sql = $sql . $this->assignOperation("login", $op, $login, $first, $parameters);
                $first = false;
            }
            if ($firstname) {
                $sql = $sql . $this->assignOperation("firstname", $op, $firstname, $first, $parameters);
                $first = false;
            }
            if ($lastname) {
                $sql = $sql . $this->assignOperation("lastname", $op, $lastname, $first, $parameters);
                $first = false;
            }
            if ($email) {
                $sql = $sql . $this->assignOperation("email", $op, $email, $first, $parameters);
                $first = false;
            }
            $sql = $sql . " ORDER BY login LIMIT ? OFFSET ?";
            array_push($parameters, $limit);
            array_push($parameters, $offset);
            Logging::debug("SQL: $sql");
            Logging::debug("parameters: ", $parameters);
            $stmt = $db->prepare($sql);
            $stmt->execute($parameters);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            while ($row != null) {
                $user = User::userWithLogin($login);
                $user->setLogin($row['login']);
                $user->setFirstname($row['firstname']);
                $user->setLastname($row['lastname']);
                $user->setEmail($row['email']);
                $user->setRole($row['role']);
                array_push($res, $user);
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            }
            $db->commit();
            return $res;
        } catch (Exception $ex) {
            $db->rollback();
            throw $ex;
        }
    }
}
