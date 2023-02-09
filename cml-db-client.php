<?php

class cml_db_client
{
  public static function get_user_messages($userid = null)
  {
    if (empty($userid)) {
      return array();
    }
    $sql = '';
    $sql .= "SELECT tu.handle AS tohandle,";
    $sql .= " tu.avatarblobid AS toavatarblobid,";
    $sql .= " tu.flags AS toflags,";
    $sql .= " tu.level AS tolevel,";
    $sql .= " tl.location AS tolocation,";
    $sql .= " fu.handle AS fromhandle,";
    $sql .= " fu.avatarblobid AS fromavatarblobid,";
    $sql .= " fu.flags AS fromflags,";
    $sql .= " fu.level AS fromlevel,";
    $sql .= " fl.location AS fromlocation,";
    $sql .= " m.messageid,";
    $sql .= " m.type,";
    $sql .= " m.fromuserid,";
    $sql .= " m.touserid,";
    $sql .= " m.fromhidden,";
    $sql .= " m.tohidden,";
    $sql .= " m.content,";
    $sql .= " m.format,";
    $sql .= " m.created,";
    $sql .= " NULL AS groupid";
    $sql .= " FROM ^messages m";
    $sql .= " LEFT JOIN ^users tu ON tu.userid = m.touserid";
    $sql .= " LEFT JOIN (";
    $sql .= "   SELECT userid, CASE WHEN title = 'location' THEN content ELSE '' END AS location";
    $sql .= "   FROM ^userprofile";
    $sql .= "   WHERE title LIKE 'location'";
    $sql .= " ) tl ON tl.userid = tu.userid";
    $sql .= " LEFT JOIN ^users fu ON fu.userid = m.fromuserid";
    $sql .= " LEFT JOIN (";
    $sql .= "   SELECT userid, CASE WHEN title = 'location' THEN content ELSE '' END AS location";
    $sql .= "   FROM ^userprofile";
    $sql .= "   WHERE title LIKE 'location'";
    $sql .= " ) fl ON fl.userid = fu.userid";
    $sql .= " WHERE messageid IN (";
    $sql .= "   SELECT messageid";
    $sql .= "   FROM (";
    $sql .= "     SELECT CASE fromuserid WHEN $ THEN touserid ELSE fromuserid END AS userid,";
    $sql .= "     MAX(messageid) AS messageid";
    $sql .= "     FROM ^messages";
    $sql .= "     WHERE (fromuserid = $ OR touserid = $)";
    $sql .= "     AND type = 'PRIVATE'";
    $sql .= "     GROUP BY userid";
    $sql .= "   ) pm";
    $sql .= " )";
    $sql .= " AND tu.handle IS NOT NULL";
    $sql .= " AND fu.handle IS NOT NULL";

    $sql .= " UNION ALL ";
    $sql .= " SELECT ";
    $sql .= " NULL AS tohandle,";
    $sql .= " NULL AS toavatarblobid,";
    $sql .= " NULL AS tolevels,";
    $sql .= " NULL AS tofalgs,";
    $sql .= " NULL AS tolocation,";
    $sql .= " NULL AS fromhandle,";
    $sql .= " NULL AS fromavatarblobid,";
    $sql .= " NULL AS fromflags,";
    $sql .= " NULL AS fromlevel,";
    $sql .= " NULL AS fromlocation,";
    $sql .= " NULL AS messageid,";
    $sql .= " 'GROUP' AS type,";
    $sql .= " gm.userid AS fromuserid,";
    $sql .= " NULL AS touserid,";
    $sql .= " NULL AS fromhidden,";
    $sql .= " NULL AS tohidden,";
    $sql .= " gm.content,";
    $sql .= " gm.format,";
    $sql .= " CASE WHEN gm.created IS NULL THEN g.created ELSE gm.created END as created,";
    $sql .= " g.groupid";
    $sql .= " FROM ^msg_groups g";
    $sql .= " LEFT JOIN (";
    $sql .= "   SELECT *";
    $sql .= "   FROM ^msg_group_messages";
    $sql .= "   WHERE messageid IN";
    $sql .= "   (";
    $sql .= "     SELECT MAX(messageid)";
    $sql .= "     FROM ^msg_group_messages";
    $sql .= "     GROUP BY groupid";
    $sql .= "   )";
    $sql .= " ) gm";
    $sql .= " ON g.groupid = gm.groupid";
    $sql .= " WHERE g.groupid IN (";
    $sql .= "   SELECT groupid";
    $sql .= "   FROM ^msg_group_users";
    $sql .= "   WHERE userid = #";
    $sql .= " )";
    $sql .= " ORDER BY created DESC";

    return qa_db_read_all_assoc(qa_db_query_sub($sql, $userid, $userid, $userid, $userid));
  }

  public static function get_qa_count_days($userid=null, $days=30)
  {
    if (!isset($userid)) {
        $userid = qa_get_logged_in_userid();
    }

    if (empty($userid)) {
        return 0;
    }

    $sql = "SELECT count(*)";
    $sql .= " FROM ^posts";
    $sql .= " WHERE (type = 'A' OR type = 'Q')";
    $sql .= " AND userid = $";
    $sql .= " AND created > DATE_SUB(NOW(), INTERVAL # DAY)";

    return qa_db_read_one_value(qa_db_query_sub($sql, $userid, $days));
  }

  public static function get_blog_count_days($userid=null, $days=30)
  {
    if (!isset($userid)) {
        $userid = qa_get_logged_in_userid();
    }

    if (empty($userid)) {
        return 0;
    }

    $sql = "SELECT count(*)";
    $sql .= " FROM ^blogs";
    $sql .= " WHERE type = 'B'";
    $sql .= " AND userid = $";
    $sql .= " AND created > DATE_SUB(NOW(), INTERVAL # DAY)";

    return qa_db_read_one_value(qa_db_query_sub($sql, $userid, $days));
  }

  public static function check_show_user_message($userid, $days) {
    // 投稿数によるメッセージ利用制限を削除
    return true;

    $post_count = self::get_qa_count_days($userid, $days);
    $blog_count = self::get_blog_count_days($userid, $days);
    if ($post_count > 0 || $blog_count > 0) {
      return true;
    } else {
      return false;
    }
  }

  /*
   * 相互フォローユーザーを取得
   */
  public static function select_follow_each_other($userid)
  {
      $sql = '';
      $sql.= 'SELECT u.userid, handle, avatarblobid,';
      $sql.= ' content as location';
      $sql.= ' FROM ^users u';
      $sql.= ' LEFT JOIN (';
      $sql.= '     SELECT userid, content';
      $sql.= '     FROM ^userprofile';
      $sql.= "     WHERE title like 'location'";
      $sql.= ' ) p ON u.userid = p.userid';
      $sql.= ' WHERE u.userid IN (';
      $sql.= '     SELECT entityid';
      $sql.= '     FROM ^userfavorites';
      $sql.= '     WHERE userid = $';
      $sql.= "     AND entitytype = 'U'";
      $sql.= '     AND entityid IN (';
      $sql.= '         SELECT userid';
      $sql.= '         FROM ^userfavorites';
      $sql.= '         WHERE entityid = $';
      $sql.= "         AND entitytype = 'U'";
      $sql.= '))';

      return qa_db_read_all_assoc(qa_db_query_sub($sql, $userid, $userid));

  }

  /*
   * 1ヶ月以内にやり取りのあったユーザーを取得するSQL
   */
  public static function get_interaction_uesrs_sql($userid)
  {
      $answered_and_comment_users = self::get_answered_and_comment_users($userid);
      $blog_comment_users = self::get_blog_comment_users($userid);
      $answered_and_comment_posted_users = self::get_answered_and_comment_posted_users($userid);
      $blog_posted_users = self::get_blog_posted_users($userid);

      // ユーザーIDの配列を結合
      $merged_users = array_merge(
        $answered_and_comment_users,
        $blog_comment_users,
        $answered_and_comment_posted_users,
        $blog_posted_users
      );
      // 重複値をなくして、NULL削除
      $uniq_filter_users = array_filter(array_unique($merged_users));
      // 添字を振り直す
      $userids = array_merge($uniq_filter_users);

      $sql = '';
      if (count($userids) > 0) {
        $sql.= "SELECT userid";
        $sql.= " FROM ^users";
        $sql.= " WHERE userid != $";
        $sql.= qa_db_apply_sub(" AND userid IN ($)", array($userids));
        $sql.= " ORDER BY created DESC";
      }
      return $sql;
  }

  /*
   * 最近メッセージを送ったユーザーを取得するSQL
   */
  public static function get_recent_send_message_users_sql()
  {
      $sql = '';
      $sql.= "SELECT DISTINCT touserid as userid";
      $sql.= " FROM ^messages";
      $sql.= " WHERE type = 'PRIVATE'";
      $sql.= " AND fromuserid = $";
      $sql.= " ORDER BY created DESC";
      $sql.= " LIMIT 5";
      return $sql;
  }

  /*
   * やり取りのあったユーザーを取得する
   */
  public static function select_interaction_users($userid)
  {
      // 回答やコメント、飼育日誌でやり取りしたユーザー
      $sql = self::get_interaction_uesrs_sql($userid);
      if (!empty($sql)) {
        $interaction_users = qa_db_read_all_values(qa_db_query_sub($sql, $userid));
      } else {
        $interaction_users = array();
      }
      // 最近メッセージを送ったユーザー
      $sql2 = self::get_recent_send_message_users_sql();
      $send_message_users = qa_db_read_all_values(qa_db_query_sub($sql2, $userid));

      $userids = array_unique(array_merge($interaction_users, $send_message_users));

      if (count($userids) > 0) {
        // 該当ユーザーのうち「すべてのユーザーとやり取りする」にチェックが入っていないユーザーを除外
        $sql = '';
        $sql.= 'SELECT u.userid, handle, avatarblobid,';
        $sql.= ' content as location';
        $sql.= ' FROM ^users u';
        $sql.= ' LEFT JOIN (';
        $sql.= '     SELECT userid, content';
        $sql.= '     FROM ^userprofile';
        $sql.= "     WHERE title like 'location'";
        $sql.= ' ) p ON u.userid = p.userid';
        $sql.= ' WHERE u.userid IN ($)';
        $sql.= ' AND NOT (u.flags & #)';

        $result = qa_db_read_all_assoc(qa_db_query_sub($sql, $userids, QA_USER_FLAGS_NO_MESSAGES));
      } else {
        $result = array();
      }
      return $result;
  }

  public static function select_recent_message_users($userid, $exclude=null)
  {
      $sql = '';
      $sql.= 'SELECT u.userid, handle, avatarblobid,';
      $sql.= ' content as location';
      $sql.= ' FROM ^users u';
      $sql.= ' LEFT JOIN (';
      $sql.= '     SELECT userid, content';
      $sql.= '     FROM ^userprofile';
      $sql.= "     WHERE title like 'location'";
      $sql.= ' ) p ON u.userid = p.userid';
      $sql.= ' WHERE u.userid IN (';
      $sql.= '   SELECT DISTINCT touserid';
      $sql.= '   FROM ^messages';
      $sql.= "   WHERE `type` = 'PRIVATE'";
      $sql.= '   AND fromuserid = $';
      $sql.= '   AND created >= DATE_SUB(NOW(), INTERVAL 1 YEAR)';
      $sql.= ' UNION';
      $sql.= ' SELECT DISTINCT fromuserid';
      $sql.= ' FROM ^messages';
      $sql.= " WHERE `type` = 'PRIVATE'";
      $sql.= ' AND touserid = $';
      $sql.= ' AND created >= DATE_SUB(NOW(), INTERVAL 1 YEAR)';
      $sql.= ' )';
      if ($exclude) {
          $sql .= qa_db_apply_sub(' AND u.userid NOT IN ($)', array($exclude));
      }

      return qa_db_read_all_assoc(qa_db_query_sub($sql, $userid, $userid));
  }

  public static function test_users()
  {
      $sql = '';
      $sql.= 'SELECT u.userid, handle, avatarblobid,';
      $sql.= ' content as location';
      $sql.= ' FROM ^users u';
      $sql.= ' LEFT JOIN (';
      $sql.= '     SELECT userid, content';
      $sql.= '     FROM ^userprofile';
      $sql.= "     WHERE title like 'location'";
      $sql.= ' ) p ON u.userid = p.userid';
      $sql.= ' WHERE u.userid > 2710';

      return qa_db_read_all_assoc(qa_db_query_sub($sql));
  }

  public static function update_message($messageid, $content)
  {
    return qa_db_query_sub(
        "UPDATE ^messages SET content=$ WHERE messageid=#",
        $content, $messageid
    );
  }

  private static function get_answered_and_comment_users($userid, $day=30)
  {
    $sql = "";
    $sql.= "SELECT DISTINCT userid";
    $sql.= " FROM ^posts";
    $sql.= " WHERE parentid IN (";
    $sql.= "    SELECT postid";
    $sql.= "    FROM ^posts";
    $sql.= "    WHERE userid = $";
    $sql.= "    AND type IN ('Q', 'A')";
    $sql.= " )";
    $sql.= " AND created > DATE_SUB(NOW(), INTERVAL # DAY)";
    return qa_db_read_all_values(qa_db_query_sub($sql, $userid, $day));
  }

  private static function get_blog_comment_users($userid, $day=30)
  {
    $sql = "";
    $sql.= " SELECT DISTINCT userid";
    $sql.= " FROM ^blogs";
    $sql.= " WHERE parentid IN (";
    $sql.= "    SELECT postid";
    $sql.= "    FROM ^blogs";
    $sql.= "    WHERE userid = $";
    $sql.= "    AND type = 'B'";
    $sql.= " )";
    $sql.= " AND created > DATE_SUB(NOW(), INTERVAL # DAY)";
    return qa_db_read_all_values(qa_db_query_sub($sql, $userid, $day));
  }

  private static function get_answered_and_comment_posted_users($userid, $day=30)
  {
    $sql = "";
    $sql.= " SELECT DISTINCT userid";
    $sql.= " FROM ^posts";
    $sql.= " WHERE postid IN (";
    $sql.= "    SELECT parentid";
    $sql.= "    FROM ^posts";
    $sql.= "    WHERE userid = $";
    $sql.= "    AND type IN ('C', 'A')";
    $sql.= "    AND created > DATE_SUB(NOW(), INTERVAL # DAY)";
    $sql.= " )";
    return qa_db_read_all_values(qa_db_query_sub($sql, $userid, $day));
  }

  private static function get_blog_posted_users($userid, $day=30)
  {
    $sql = "";
    $sql.= " SELECT DISTINCT userid";
    $sql.= " FROM ^blogs";
    $sql.= " WHERE postid IN (";
    $sql.= "    SELECT parentid";
    $sql.= "    FROM ^blogs";
    $sql.= "    WHERE userid = $";
    $sql.= "    AND type = 'C'";
    $sql.= "    AND created > DATE_SUB(NOW(), INTERVAL # DAY)";
    $sql.= " )";
    return qa_db_read_all_values(qa_db_query_sub($sql, $userid, $day));
  }
}
