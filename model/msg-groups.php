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
        $this->title = 'グループチャット';
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

    public function delete()
    {

    }

    public function add_user($userid, $join)
    {
        qa_db_query_sub(
            'INSERT INTO ^msg_group_users (`groupid`, `userid`, `join`, `notify`) VALUES (#, #, #, #)',
            $this->groupid, $userid, $join, self::GROUP_NOTIFY_OFF
        );
        return qa_db_last_insert_id();
    }

    public function remove_user()
    {

    }

    public function update_user()
    {

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
}