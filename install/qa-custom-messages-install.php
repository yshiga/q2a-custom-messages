<?php
// don't allow this page to be requested directly from browser
if (!defined('QA_VERSION')) {
    header('Location: ../../');
    exit;
}

class q2a_custom_messages_install
{

    // 生成するテーブル
    private $table_names = array(
        'groups',
        'group_users',
        'group_messages'
    );

    /**
     * create table function of framework
     * @param  [type] $tableslc []
     * @return array  array of table creattion sqls
     */
    function init_queries($tableslc)
    {
        $queries = array();

        foreach($this->table_names as $table_name) {
            if (!in_array(qa_db_add_table_prefix('msg_'.$table_name), $tableslc)) {
                    $queries[] = file_get_contents(CML_DIR . '/sql/qa_create_msg_' . $table_name . '.sql');
                }
        }

        if (count($queries) > 0) {
            return $queries;
        }

        return null;
    }

    function process_event($event, $post_userid, $post_handle, $cookieid, $params)
    {
        // do nothing
    }
}