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

use runnerupweb\common\Configuration;
use runnerupweb\common\Logging;
use runnerupweb\common\DataBase;
use runnerupweb\data\Activity;
use runnerupweb\data\Tag;
use runnerupweb\data\TagConfig;

/**
 * The tag manager manages tags associated to the activities. The tags
 * are simple strings that can be of two types: normal tags (just associated
 * by the user) and automatic tags (of a provider that are automatically assigned
 * to the activity at import time).
 *
 * The automatic tags uses the config table to configure the tag. The provider
 * column is the PHP class that manages the tag.
 *
 * CREATE TABLE `tag_config` (
 *   `tag` varchar(128) NOT NULL,
 *   `login` varchar(128) NOT NULL,
 *   `config` varchar(2048) DEFAULT NULL,
 *   `provider` varchar(128) DEFAULT NULL,
 *   `description` varchar(2048) DEFAULT NULL,
 *   PRIMARY KEY (`tag`, `login`),
 *   CONSTRAINT `tag_config_login_fgk` FOREIGN KEY (`login`) REFERENCES `user` (`login`) ON DELETE CASCADE
 * );
 *
 * CREATE TABLE `tag` (
 *   `tag` varchar(128) NOT NULL,
 *   `id` bigint(20) NOT NULL,
 *   `login` varchar(128) NOT NULL,
 *   PRIMARY KEY (`tag`, `id`, `login`),
 *   CONSTRAINT `tag_tag_config_fgk` FOREIGN KEY (`tag`) REFERENCES `tag_config` (`tag`) ON DELETE CASCADE,
 *   CONSTRAINT `tag_activity_fgk` FOREIGN KEY (`id`) REFERENCES `activity` (`id`) ON DELETE CASCADE,
 *   CONSTRAINT `tag_login_fgk` FOREIGN KEY (`login`) REFERENCES `user` (`login`) ON DELETE CASCADE
 * );

 *
 * @author ricky
 */
class TagManager extends DataBase {

    static private $tagManager;

    protected function __construct(string $url, string $username, string $password, int $maxrows) {
        parent::__construct($url, $username, $password, $maxrows);
    }

    /**
     * Initializer for the singleton.
     * @param type $url The URL to the database
     * @param type $username The username to connect to the ddbb
     * @param type $password The password of the user
     * @param type $maxrows Maximum rows to select
     * @return ActivityManager The singleton
     */
    static public function initTagManager(string $url, string $username, string $password, int $maxrows): TagManager {
        static::$tagManager = new TagManager($url, $username, $password, $maxrows);
        return static::getTagManager();
    }

    /**
     * Getter for the singleton.
     * @return ActivityManager
     */
    static public function getTagManager(): TagManager {
        return static::$tagManager;
    }

    /**
     * Returns the list of tags the user has managed
     * @param type $username The name of the user to get the tags
     * @return array The array of tags used by the users (tag, description)
     * @throws \runnerupweb\common\Exception
     */
    public function getAllTags(string $username): array {
        $db = $this->getConnection();
        $res = [];
        try {
            $stmt = $db->prepare("SELECT tag, provider IS NOT NULL AS auto FROM tag_config WHERE login = ?");
            $stmt->execute([$username]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            while ($row != null) {
                $tag = Tag::tagFromAssoc($row);
                array_push($res, $tag);
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            }
            $db->commit();
            return $res;
        } catch (Exception $ex) {
            $db->rollback();
            throw $ex;
        }
    }

    /**
     * Return the list of tags associated to an activity.
     * @param string $username The username
     * @param int $id The activity
     * @return array The array of tags
     * @throws \runnerupweb\common\Exception
     */
    public function getActivityTags(string $username, int $id): array {
        $db = $this->getConnection();
        try {
            $res = $this->getActivityTagsInTransaction($db, $username, $id);
            $db->commit();
            return $res;
        } catch (Exception $ex) {
            $db->rollback();
            throw $ex;
        }
    }

    /**
     * Return the activity tags in a existing transaction
     * @param \PDO $db
     * @param string $username
     * @param int $id
     * @return array
     */
    public function getActivityTagsInTransaction(\PDO $db, string $username, int $id): array {
        $res = [];
        $stmt = $db->prepare("SELECT tag.tag as tag, tag_config.provider IS NOT NULL AS auto"
                . " FROM tag INNER JOIN tag_config ON tag.tag = tag_config.tag AND tag.login = tag_config.login"
                . " WHERE tag.login = ? AND tag.id=?");
        $stmt->execute([$username, $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        while ($row != null) {
            $tag = Tag::tagFromAssoc($row);
            array_push($res, $tag);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        }
        return $res;
    }

    /**
     * Assigns a tag to an activity.
     * @param string $username The username
     * @param int $id The activity id
     * @param string $tag The tag string
     * @return bool true if added
     * @throws \runnerupweb\common\Exception
     */
    public function assignTagToActivity(string $username, int $id, string $tag): bool {
        $db = $this->getConnection();
        try {
            $res = $this->assignTagToActivityInTransaction($db, $username, $id, $tag);
            $db->commit();
            return $res;
        } catch (Exception $ex) {
            $db->rollback();
            throw $ex;
        }
    }

    /**
     * Assigns a tag but inside a transaction.
     * @param \PDO $db
     * @param string $username
     * @param int $id
     * @param string $tag
     * @return bool
     */
    public function assignTagToActivityInTransaction(\PDO $db, string $username, int $id, string $tag): bool {
        $stmt = $db->prepare("INSERT INTO tag(tag, id, login) VALUES(?, ?, ?)");
        $stmt->execute([$tag, $id, $username]);
        return $stmt->rowCount() === 1;
    }

    /**
     * Removes the tag from an activity
     * @param string $username The username
     * @param int $id The activity id
     * @param string $tag The tag name
     * @return bool true if removed
     * @throws \runnerupweb\common\Exception
     */
    public function removeTagFromActivity(string $username, int $id, string $tag): bool {
        $db = $this->getConnection();
        try {
            $res = $this->removeTagFromActivityInTransaction($db, $username, $id, $tag);
            $db->commit();
            return $res;
        } catch (Exception $ex) {
            $db->rollback();
            throw $ex;
        }
    }

    /**
     * Removes the tag in an existing transaction
     * @param \PDO $db
     * @param string $username
     * @param int $id
     * @param string $tag
     * @return bool
     */
    public function removeTagFromActivityInTransaction(\PDO $db, string $username, int $id, string $tag): bool {
        $stmt = $db->prepare("DELETE FROM tag WHERE tag=? AND id=? AND login=?");
        $stmt->execute([$tag, $id, $username]);
        return $stmt->rowCount() === 1;
    }

    /**
     * Calculates the automatic tags.
     * @param string $username
     * @param Activity $activity
     * @param bool $delete
     * @return void
     * @throws \runnerupweb\common\Exception
     */
    public function calculateAutomaticTags(string $username, Activity $activity, bool $delete): void {
        $db = $this->getConnection();
        try {
            $this->calculateAutomaticTagsInTransaction($db, $username, $activity, $delete);
            $db->commit();
        } catch (Exception $ex) {
            $db->rollback();
            throw $ex;
        }
    }

    /**
     * Calculate the tags used by automatic tags
     * @param \PDO $db
     * @param string $username
     * @param Activity $activity
     * @param bool $delete if true previous assigned values are deleted
     * @return void
     */
    public function calculateAutomaticTagsInTransaction(\PDO $db, string $username, Activity $activity, bool $delete): void {
        // get all the automatic tags defined for the user and calculate the tags
        $tagConfigs = $this->getTagConfigsForUserInTransaction($db, $username, true);
        $tags = [];
        foreach ($tagConfigs as $tagConfig) {
            $provider = $tagConfig->getProvider();
            Logging::debug("Calculating provider $provider");
            $autoTag = new $provider();
            if ($autoTag->isAssignable($activity, $tagConfig)) {
                array_push($tags, $tagConfig->getTag());
            }
        }
        $tags = array_unique($tags);
        $currentTags = $this->getActivityTagsInTransaction($db, $username, $activity->getId());
        // map Tag to tag name if is auto
        $current = [];
        foreach ($currentTags as $tag) {
            if ($tag->isAuto()) {
                array_push($current, $tag->getTag());
            }
        }
        // add the tags to be added
        $toAdd = array_diff($tags, $current);
        foreach ($toAdd as $tag) {
            $this->assignTagToActivityInTransaction($db, $username, $activity->getId(), $tag);
        }
        if ($delete) {
            // delete the tags to delete
            $toDelete = array_diff($current, $tags);
            foreach ($toDelete as $tag) {
                $this->removeTagFromActivityInTransaction($db, $username, $activity->getId(), $tag);
            }
        }
    }

    /**
     * Creates a tag config.
     * @param string $username The username associated to the tag config
     * @param TagConfig $tagConfig The tag config
     * @return TagConfig The same tag config
     * @throws \runnerupweb\common\Exception
     */
    public function createTagConfig(string $username, TagConfig $tagConfig): TagConfig {
        $db = $this->getConnection();
        try {
            $stmt = $db->prepare("INSERT INTO tag_config(login, tag, config, provider, description) VALUES(?, ?, ?, ?, ?)");
            $stmt->execute([$username, $tagConfig->getTag(), $tagConfig->getConfig(),
                $tagConfig->getProvider(), $tagConfig->getDescription()]);
            $db->commit();
            return $tagConfig;
        } catch (Exception $ex) {
            $db->rollback();
            throw $ex;
        }
    }

    /**
     * Updates a tag config.
     * @param string $username The username the tag is asscoiated
     * @param TagConfig $tagConfig The tag config to update
     * @return TagConfig The same tag config
     * @throws \runnerupweb\common\Exception
     * @throws \PDOException
     */
    public function updateTagConfig(string $username, TagConfig $tagConfig): TagConfig {
        $db = $this->getConnection();
        try {
            $sql = "UPDATE tag_config SET config=?, provider=?, description=? WHERE login=? AND tag=?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$tagConfig->getConfig(), $tagConfig->getProvider(), $tagConfig->getDescription(),
                $username, $tagConfig->getTag()]);
            if ($stmt->rowCount() === 0) {
                Logging::debug("Row count: ", array($stmt->rowCount()));
                throw new \PDOException("The TagConfig does not exists!");
            }
            $db->commit();
            return $tagConfig;
        } catch (Exception $ex) {
            $db->rollback();
            throw $ex;
        }
    }

    /**
     * Retrieves a tag config from the table.
     * @param string $username
     * @param string $tag
     * @return TagConfig|null
     * @throws \runnerupweb\common\Exception
     */
    public function getTagConfig(string $username, string $tag): ?TagConfig {
        $db = $this->getConnection();
        $tagConfig = null;
        try {
            $stmt = $db->prepare("SELECT tag, config, provider, description FROM tag_config WHERE login=? AND tag=?");
            $stmt->execute([$username, $tag]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($row != null) {
                $tagConfig = TagConfig::tagConfigFromAssoc($row);
            }
            $db->commit();
            return $tagConfig;
        } catch (Exception $ex) {
            $db->rollback();
            throw $ex;
        }
    }

    /**
     * Get all the tag configs for a user
     * @param string $username
     * @param bool $automatic
     * @return array
     * @throws \runnerupweb\common\Exception
     */
    public function getTagConfigsForUser(string $username, bool $automatic = false): array {
        $db = $this->getConnection();
        try {
            $tagConfigs = $this->getTagConfigsForUserInTransaction($db, $username, $automatic);
            $db->commit();
            return $tagConfigs;
        } catch (Exception $ex) {
            $db->rollback();
            throw $ex;
        }
    }

    /**
     * Get tag confis for a user inside a transaction.
     * @param \PDO $db
     * @param string $username
     * @param bool $automatic
     * @return array
     */
    public function getTagConfigsForUserInTransaction(\PDO $db, string $username, bool $automatic): array {
        $tagConfigs = [];
        $sql = "SELECT tag, config, provider, description FROM tag_config WHERE login=?";
        if ($automatic) {
            $sql = $sql . " AND provider IS NOT NULL";
        }
        $stmt = $db->prepare($sql);
        $stmt->execute([$username]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        while ($row != null) {
            array_push($tagConfigs, TagConfig::tagConfigFromAssoc($row));
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        }
        return $tagConfigs;
        
    }

    /**
     * Deletes a tag config
     * @param string $username The username
     * @param string $tag The tag name
     * @return bool true if deleted
     * @throws \runnerupweb\common\Exception
     */
    public function deleteTagConfig(string $username, string $tag): bool {
        $db = $this->getConnection();
        try {
            $stmt = $db->prepare("DELETE FROM tag_config WHERE login=? AND tag=?");
            $stmt->execute([$username, $tag]);
            $db->commit();
            return $stmt->rowCount() === 1;
        } catch (Exception $ex) {
            $db->rollback();
            throw $ex;
        }
    }

    /**
     * Return the list of classes that implements the automatic interface.
     * It just uses the configuration
     * @return array Array of string classes
     */
    public function listAtomaticTagProviders(): array {
        return Configuration::getConfiguration()->getProperty('tags', 'automatic.providers');
    }

    /**
     * Generates the tag config from a full activity.
     * @param string $provider
     * @param Activity
     * @return TagConfig|null
     */
    public function generateTagConfigFromActivity(string $provider, Activity $activity): ?TagConfig {
        if (!in_array($provider, Configuration::getConfiguration()->getProperty('tags', 'automatic.providers'))) {
            return null;
        }
        $autoTag = new $provider();
        return $autoTag->generateTagConfigWithExtra($activity);
    }

}