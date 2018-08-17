<?php
/**
 * チャットグループのクラス
 */
class msg_groups
{
    public $groupid;
    public $title;
    public $created;
    public $join_users;
    public $all_users;
    const GROUP_MSG_INVITE = 0;
    const GROUP_MSG_JOIN = 1;
    const GROUP_NOTIFY_OFF = 0;
    const GROUP_NOTIFY_ON = 1;

    public function __construct($groupid=null)
    {
        $this->title = null;
        $this->groupid = null;
        $this->created = null;
        $this->join_users = array();
        $this->all_users = array();
        if (isset($groupid)) {
            $res = $this->get_group($groupid);
            if ($res) {
                $this->title = $res['title'];
                $this->groupid = $res['groupid'];
                $this->created = $res['created'];
                $this->join_users = self::get_group_join_users($groupid);
                $this->all_users = self::get_group_all_users($groupid);
            }
        }
    }

    public function create()
    {
        qa_db_query_sub(
            'INSERT INTO ^msg_groups (title, created) VALUES ($, NOW())',
            $this->title
        );
        $this->groupid = qa_db_last_insert_id();
        return $this->groupid;
    }

    public function add_user($userid, $join)
    {
        qa_db_query_sub(
            'INSERT INTO ^msg_group_users (`groupid`, `userid`, `join`, `notify`) VALUES (#, #, #, #)',
            $this->groupid, $userid, $join, self::GROUP_NOTIFY_OFF
        );
        return qa_db_last_insert_id();
    }

    public function remove_user($userid)
    {
        qa_db_query_sub(
            'DELETE FROM ^msg_group_users WHERE groupid = # AND userid = #',
            $this->groupid, $userid
        );
        return;
    }

    public function update_user($userid, $join)
    {
        qa_db_query_sub(
            'UPDATE ^msg_group_users SET `join` = # WHERE groupid = # AND userid = #',
            $join, $this->groupid, $userid
        );
        return;
    }


    public function get_group($groupid)
    {
        $sql = "SELECT *";
        $sql.= " FROM ^msg_groups";
        $sql.= " WHERE groupid = #";
        
        return qa_db_read_one_assoc(qa_db_query_sub($sql, $groupid), true);
    }

    public function allow_browse($userid)
    {
        return in_array($userid, $this->join_users);
    }

    public function is_invited($userid)
    {
        return in_array($userid, $this->all_users);
    }

    public function notify_off($userid)
    {
        qa_db_query_sub(
            'UPDATE ^msg_group_users SET `notify` = # WHERE groupid = # AND userid = #',
            self::GROUP_NOTIFY_OFF, $this->groupid, $userid
        );
        return;
    }

    public function notify_on($userid)
    {
        qa_db_query_sub(
            'UPDATE ^msg_group_users SET `notify` = # WHERE groupid = # AND userid = #',
            self::GROUP_NOTIFY_ON, $this->groupid, $userid
        );
        return;
    }

    public function get_group_notify($userid)
    {
        $sql = "SELECT `notify`";
        $sql.= " FROM ^msg_group_users";
        $sql.= " WHERE groupid = #";
        $sql.= " AND userid = #";
        
        return qa_db_read_one_value(qa_db_query_sub($sql, $this->groupid, $userid), true);
    }

    public static function get_group_join_users($groupid)
    {
        $sql = "SELECT userid";
        $sql.= " FROM ^msg_group_users";
        $sql.= " WHERE groupid = #";
        $sql.= " AND `join` = #";

        return qa_db_read_all_values(qa_db_query_sub($sql, $groupid, self::GROUP_MSG_JOIN));
    }

    public static function get_group_all_users($groupid)
    {
        $sql = "SELECT userid";
        $sql.= " FROM ^msg_group_users";
        $sql.= " WHERE groupid = #";

        return qa_db_read_all_values(qa_db_query_sub($sql, $groupid));
    }

    public static function already_existing($userids)
    {
        $count = count($userids);
        $groupids = self::get_groups_by_usercount($count);

        foreach ($groupids as $groupid) {
            $groupusers = self::get_group_all_users($groupid);
            $result = array_diff($userids, $groupusers);
            if (empty($result)) {
                return $groupid;
            }
        }
        return false;
    }

    public static function get_groups_count()
    {
        $res = qa_db_read_one_value(qa_db_query_sub('SELECT COUNT(*) FROM ^msg_groups'));
        return $res;
    }

    public static function get_groups_by_usercount($count)
    {
        $sql = "SELECT groupid";
        $sql.= " FROM ^msg_group_users";
        $sql.= " GROUP BY groupid";
        $sql.= " HAVING count(userid) = #";

        return qa_db_read_all_values(qa_db_query_sub($sql, $count));
    }

    public function get_group_handles($login_userid)
    {
        $handles = array();
        $len = 0;
        foreach ($this->all_users as $userid) {
            if ($userid === $login_userid) {
                continue;
            }
            $tmp = qa_userid_to_handle($userid);
            $len += mb_strlen($tmp, 'UTF-8');
            if ($len <= 20) {
                $handles[] = $tmp;
            }
        }
        return implode('、', $handles);
    }
}