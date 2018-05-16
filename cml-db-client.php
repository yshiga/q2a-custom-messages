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
    $sql .= " m.*";
    $sql .= " FROM qa_messages m";
    $sql .= " LEFT JOIN qa_users tu ON tu.userid = m.touserid";
    $sql .= " LEFT JOIN (";
    $sql .= "   SELECT userid, CASE WHEN title = 'location' THEN content ELSE '' END AS location";
    $sql .= "   FROM qa_userprofile";
    $sql .= "   WHERE title LIKE 'location'";
    $sql .= " ) tl ON tl.userid = tu.userid";
    $sql .= " LEFT JOIN qa_users fu ON fu.userid = m.fromuserid";
    $sql .= " LEFT JOIN (";
    $sql .= "   SELECT userid, CASE WHEN title = 'location' THEN content ELSE '' END AS location";
    $sql .= "   FROM qa_userprofile";
    $sql .= "   WHERE title LIKE 'location'";
    $sql .= " ) fl ON fl.userid = fu.userid";
    $sql .= " WHERE messageid IN (";
    $sql .= "   SELECT messageid";
    $sql .= "   FROM (";
    $sql .= "     SELECT CASE fromuserid WHEN $ THEN touserid ELSE fromuserid END AS userid,";
    $sql .= "     MAX(messageid) AS messageid";
    $sql .= "     FROM qa_messages";
    $sql .= "     WHERE (fromuserid = $ OR touserid = $)";
    $sql .= "     AND type = 'PRIVATE'";
    $sql .= "     GROUP BY userid";
    $sql .= "   ) pm";
    $sql .= " )";
    $sql .= " AND tu.handle IS NOT NULL";
    $sql .= " AND fu.handle IS NOT NULL";
    $sql .= " ORDER BY created DESC";
    return qa_db_read_all_assoc(qa_db_query_sub($sql, $userid, $userid, $userid));
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
    $post_count = self::get_qa_count_days($userid, $days);
    $blog_count = self::get_blog_count_days($userid, $days);
    if ($post_count > 0 || $blog_count > 0) {
      return true;
    } else {
      return false;
    }
  }
}
