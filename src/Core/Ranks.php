<?php

namespace Rcms\Core;

class Ranks {

    const LOGGED_OUT = -1;
    const ADMIN = 1;
    const MODERATOR = 0;
    const USER = 2;
    
    /**
     * Please use Website->getRanks() instead of directly creating instances.
     * This constructor will change in the future when configurable ranks are
     * added.
     */
    public function __construct() {
        
    }

    /**
     * Returns all ranks as id=>name pairs.
     * @return array The highest id in use for a rank.
     */
    public function getAllRanks() {
        $rankIds = [self::USER, self::MODERATOR, self::ADMIN];
        $ranks = [];
        foreach ($rankIds as $rankId) {
            $ranks[$rankId] = $this->getRankName($rankId);
        }
        return $ranks;
    }

    /**
     * Gets the rank id assigned to new accounts.
     * @return int The rank id.
     */
    public function getDefaultRankForAccounts() {
        return self::USER;
    }

    /**
     * Returns true if the given number is a valid rank id for accounts.
     * The LOGGED_OUT rank isn't a valid rank for accounts.
     * @return boolean Whether the rank is valid.
     */
    public function isValidRankForAccounts($id) {
        if ($id == self::USER || $id == self::ADMIN || $id == self::MODERATOR) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Gets the translation string for the rank with the given id. When the rank
     * is not found, the translation of users.rank.unknown is returned.
     * @param int $id The rank id.
     * @return string The translation id for the rank name.
     */
    public function getRankName($id) {
        switch ($id) {
            case -1: return "users.rank.visitor";
            case 0: return "users.rank.moderator";
            case 1: return "users.rank.admin";
            case 2: return "users.rank.user";
            default: return "users.rank.unknown";
        }
    }

    public function isValidStatus($id) {
        if ($id == User::STATUS_NORMAL || $id == User::STATUS_DELETED || $id == User::STATUS_BANNED) {
            return true;
        } else {
            return false;
        }
    }

    public function getStatusName(Text $text, $id) {
        switch ($id) {
            case User::STATUS_BANNED: return $text->t("users.status.banned");
            case User::STATUS_DELETED: return $text->t("users.status.deleted");
            case User::STATUS_NORMAL: return $text->t("users.status.allowed");
            default: return $text->t("users.status.unknown");
        }
    }

}
