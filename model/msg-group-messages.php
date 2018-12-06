<?php
/**
 * グループメッセージのクラス
 */
class msg_group_messages
{

    public static function recent_messages_selectspec($groupid)
    {
        $source = '^msg_group_messages gm LEFT JOIN ^users u ON gm.userid=u.userid WHERE groupid= # ORDER BY gm.created DESC';

        $arguments = array($groupid);

        return array(
            'columns' => array(
                'messageid',
                'groupid',
                'gm.userid',
                'content',
                'format',
                'created' => 'UNIX_TIMESTAMP(gm.created)',
                'u.handle',
                'u.avatarblobid',
                'u.avatarwidth',
                'u.avatarheight',
                'u.flags',
                'u.level',
                'u.email'
            ),
            'source' => $source,
            'arguments' => $arguments,
            'arraykey' => 'messageid',
            'sortdesc' => 'created',
        );
    }

    public static function create_message($groupid, $userid, $content, $format)
    {
        qa_db_query_sub(
            'INSERT INTO ^msg_group_messages (groupid, userid, content, `format`, created) VALUES (#, #, $, $, NOW())',
            $groupid, $userid, $content, $format
        );

        return qa_db_last_insert_id();
    }

    public static function update_message($messageid, $content)
    {
        return qa_db_query_sub(
            "UPDATE ^msg_group_messages SET content=$ WHERE messageid=#",
            $content, $messageid
        );
    }

    public static function message_html_fields($message, $options)
    {
        require_once QA_INCLUDE_DIR.'app/users.php';

        $fields = array('raw' => $message);
        $fields['tags'] = 'id="m'.qa_html($message['messageid']).'"';

    //	Message content

        $viewer = qa_load_viewer($message['content'], $message['format']);

        $fields['content'] = $viewer->get_html($message['content'], $message['format'], array(
            'blockwordspreg' => @$options['blockwordspreg'],
            'showurllinks' => @$options['showurllinks'],
            'linksnewwindow' => @$options['linksnewwindow'],
        ));

    //	Set ordering of meta elements which can be language-specific

        $fields['meta_order'] = qa_lang_html('main/meta_order');

        $fields['what'] = qa_lang_html('main/written');

    //	When it was written

        if (@$options['whenview']) {
            $fields['when'] = qa_when_to_html($message['created'], @$options['fulldatedays']);
        }

    //	Who wrote it, and their avatar

        if (@$options['whoview']) {
            $fields['who'] = qa_lang_html_sub_split('main/by_x', qa_get_one_user_html($message['handle'], false));
        }
        if (@$options['avatarsize'] > 0) {
            $fields['avatar'] = qa_get_user_avatar_html(@$message['flags'], @$message['email'], @$message['handle'],
                @$message['avatarblobid'], @$message['avatarwidth'], @$message['avatarheight'], $options['avatarsize']);
        }

    //	That's it!
        return $fields;
    }
    
    public static function get_recent_groups_by_user($userid)
    {
        $sql = "";
        $sql.= "SELECT gm.userid, gm.groupid, gm.content, gm.created";
        $sql.= " FROM ^msg_group_messages gm";
        $sql.= " INNER JOIN (";
        $sql.= " SELECT MAX(created) AS maxdate";
        $sql.= " FROM ^msg_group_messages";
        $sql.= " GROUP BY groupid";
        $sql.= " ) AS gm2";
        $sql.= " ON gm.created = gm2.maxdate";
        $sql.= " WHERE groupid IN (";
        $sql.= " SELECT groupid";
        $sql.= " FROM ^msg_group_users";
        $sql.= " WHERE userid = #";
        $sql.= " )";
        $sql.= " ORDER BY created DESC";
        
        return qa_db_read_all_assoc(qa_db_query_sub($sql, $userid));
    }
}