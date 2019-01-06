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

use runnerupweb\data\UserOption;
use runnerupweb\data\User;
use runnerupweb\common\UserManager;
use runnerupweb\common\UserOptionManager;
use runnerupweb\common\Configuration;
use PHPUnit\Framework\TestCase;

/**
 * Description of UserOptionManagerTest
 *
 * @author ricky
 */
class UserOptionManagerTest extends TestCase {
    
    public static function setUpBeforeClass() {
        Configuration::getConfiguration();
    }

    public static function tearDownAfterClass() {
        // noop
    }
    
    private function createUser() {
        $user = User::userWithLogin('testuom');
        $user->setPassword('testuom');
        $user->setFirstname('testuom');
        $user->setLastname('testuom');
        $user->setEmail('testuom@lala.com');
        $user->setRole(User::USER_ROLE);
        return $user;
    }
    
    public function testUserOption() {
        $opts = new UserOption();
        $opts->set("option.uno.lala", "lalaval1");
        $opts->set("option.uno.koko", "kokoval1");
        $opts->set("option.dos.lala", "lalaval2");
        $opts->set("option.dos.lala", "lalaval2new");
        $this->assertEquals("lalaval1", $opts->get("option.uno.lala"));
        $this->assertEquals("kokoval1", $opts->get("option.uno.koko"));
        $this->assertEquals("lalaval2new", $opts->get("option.dos.lala"));
        $this->assertNull($opts->get("option.uno"));
        $this->assertNull($opts->get("kaka.kaka.kaka"));
        $this->assertNull($opts->get("option.uno.patata"));
        $this->assertEquals(3, count($opts->flat()));
        $opts->remove("option.dos.lala");
        $this->assertEquals(2, count($opts->flat()));
    }
    
    public function testUserOptionsJson() {
        $opts = UserOption::userOptionWithJson('{
          "activity": {
              "calculation": {
                  "period": "20"
              },
              "graphic": {
                  "speed": {
                      "minimum": "0.83"
                  }
              },
              "map": {
                  "tilelayer": "openstreetmap"
              }
          },
          "background": {
              "image": "runner-761262_1280.jpg"
          },
          "preferred": {
              "activity-list": {
                  "page-size": "50",
                  "period": "month"
              },
              "unit": {
                  "distance": "km",
                  "speed": "m\/km"
              }
          }
        }');
        $this->assertEquals("20", $opts->get("activity.calculation.period"));
        $this->assertEquals("0.83", $opts->get("activity.graphic.speed.minimum"));
        $this->assertEquals("openstreetmap", $opts->get("activity.map.tilelayer"));
        $this->assertEquals("runner-761262_1280.jpg", $opts->get("background.image"));
        $this->assertEquals("50", $opts->get("preferred.activity-list.page-size"));
        $this->assertEquals("month", $opts->get("preferred.activity-list.period"));
        $this->assertEquals("km", $opts->get("preferred.unit.distance"));
        $this->assertEquals("m/km", $opts->get("preferred.unit.speed"));
    }

    public function testDatabase() {
        $um = UserManager::getUserManager();
        // create the user for the test
        $user = $this->createUser();
        $um->createUser($user);
        // set the initial options
        $opts = new UserOption();
        $opts->set("option.uno.lala", "lalaval1");
        $opts->set("option.uno.koko", "kokoval1");
        $opts->set("option.dos.lala", "lalaval2");
        $uom = UserOptionManager::getUserOptionManager();
        $uom->set($user->getLogin(), $opts);
        // read and compare
        $read_opts = $uom->get($user->getLogin());
        $this->assertEquals($read_opts, $opts);
        // update the options with one insert, one delete and one update
        $opts->remove("option.uno.lala");
        $opts->set("option.uno.koko", "kokoval1new");
        $opts->set("option.tres.lala", "lalaval3");
        $uom->set($user->getLogin(), $opts);
        // check again
        $read_opts = $uom->get($user->getLogin());
        $this->assertEquals($read_opts, $opts);
        // delete all options
        $opts->remove("option.uno.koko");
        $opts->remove("option.dos.lala");
        $opts->remove("option.tres.lala");
        $uom->set($user->getLogin(), $opts);
        // check is empty
        $read_opts = $uom->get($user->getLogin());
        $this->assertEquals(0, count($read_opts->getMap()));
        // delete the user
        $this->assertTrue($um->deleteUser($user->getLogin()));
    }
    
    public function testDefinitions() {
        $uom = UserOptionManager::getUserOptionManager();
        $opts = $uom->getDefinitions();
        $this->assertTrue(count($opts->flat()) > 0);
        $this->assertEquals("<select><option>m</option><option>km</option><option>mile</option></select>", $opts->get("preferred.unit.distance"));
    }
    
    
    public function testCheck() {
        $opts = new UserOption();
        $opts->set("preferred.unit.distance", "m");
        $opts->set("preferred.unit.altitude", "m");
        $opts->set("preferred.activity-list.page-size", "20");
        $uom = UserOptionManager::getUserOptionManager();
        $this->assertTrue($uom->check($opts));
        $opts->set("option.not.exist", "val");
        $this->assertFalse($uom->check($opts));
    }
    
}
