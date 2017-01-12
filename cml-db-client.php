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
    $sql .= " tl.location AS tolocation,";
    $sql .= " fu.handle AS fromhandle,";
    $sql .= " fu.avatarblobid AS fromavatarblobid,";
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
    $sql .= "     WHERE fromuserid = $ OR touserid = $";
    $sql .= "     GROUP BY userid";
    $sql .= "   ) pm";
    $sql .= " )";
    $sql .= " ORDER BY created DESC";
    return qa_db_read_all_assoc(qa_db_query_sub($sql, $userid, $userid, $userid));
  }
}
