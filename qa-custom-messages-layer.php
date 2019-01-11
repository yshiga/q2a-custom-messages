<?php

require_once CML_DIR.'/cml-db-client.php';

class qa_html_theme_layer extends qa_html_theme_base {
  const MAX_USER_NUM = 10;

  public function head_css()
  {
    $allow_template = array(
      'message',
      'messages',
      'messages-select-user',
      'messages-select-group',
      'messages-select-add-user',
      'groupmsg'
    );
    qa_html_theme_base::head_css();
    if (in_array($this->template, $allow_template)) {
      $css_src = CML_RELATIVE_PATH . 'css/messages.css';
      $this->output('<link rel="stylesheet" href="'.$css_src.'"/>');
    }
  }
  public function main_parts($content) {
    $current_theme = qa_opt('site_theme');
    if ($current_theme === CML_TARGET_THEME_NAME && $this->template === 'messages') {
      // $template = file_get_contents(CML_DIR . '/messages-template.html');
      // $this->output($template);
      if (qa_is_logged_in()) {
        $messages = $content['message_list']['messages'];
        $path = CML_DIR . '/messages-template.html';
        include $path;
      }
    } elseif ($current_theme === CML_TARGET_THEME_NAME && ($this->template === 'message'
     || $this->template === 'groupmsg')) {
      if (qa_is_logged_in()) {
        if ($this->template === 'groupmsg') {
          $this->output_group_header();
        }
        $show_ok = cml_db_client::check_show_user_message(qa_get_logged_in_userid(), 30);
        $messages = isset($content['message_list']);
        if ($messages || $show_ok) {
          qa_html_theme_base::main_parts($content);
        } else {
          $this->output_not_posts();
        }
      }
    } elseif ($current_theme === CML_TARGET_THEME_NAME && $this->template === 'messages-select-user') {
      $this->output_user_list();
    } elseif ($current_theme === CML_TARGET_THEME_NAME && $this->template === 'messages-select-group') {
      $this->output_select_group();
    } elseif ($current_theme === CML_TARGET_THEME_NAME && $this->template === 'messages-select-add-user') {
      $this->output_add_user();
    } else {
      qa_html_theme_base::main_parts($content);
    }
  }

  public function widgets($region, $place)
  {
    if ($region === 'main' && $place === 'bottom') {
        if ($this->template === 'messages-select-group') {
          $this->output_select_group_button();
        } elseif ($this->template === 'messages-select-add-user') {
          $this->output_select_add_user_button();
        }
    } elseif ($region === 'main' && $place === 'high') {
        if ($this->template === 'message') {
            $show_ok = cml_db_client::check_show_user_message(qa_get_logged_in_userid(), 30);
            $messages = isset($this->content['message_list']);
            if ($messages || $show_ok) {
                $this->output(@$this->content['no_post_html']);
            }
        }
    }
    qa_html_theme_base::widgets($region, $place);
  }

  public function page_title_error() {
    $templates = array('messages', 'message', 'groupmsg');
    if (qa_opt('site_theme') === CML_TARGET_THEME_NAME && in_array($this->template, $templates) ) {
      if (isset($this->content['error'])) {
        $this->error($this->content['error']);
      }
      $this->output_buttons();
    } else {
      qa_html_theme_base::page_title_error();
    }

  }

  public function message_list_and_form($list)
  {
    if (qa_opt('site_theme') === CML_TARGET_THEME_NAME
        && ($this->template === 'message'
        || $this->template === 'groupmsg')) {
      if (strpos(qa_get_state(), 'message-sent') === false) {
          $input_error_msg = qa_lang('custom_messages/messege_input_error');
          $this->output('<div id="content-error" class="mdl-card__supporting-text">');
          $this->output('<span class="mdl-color-text--red">', $input_error_msg, '</span>');
          $this->output('</div>');
      }
      if ($this->template !== 'groupmsg') {
        $this->part_title($list);
      }

      $this->error(@$list['error']);

      if (!empty($list['form'])) {
        $this->output('<form '.$list['form']['tags'].'>');
        unset($list['form']['tags']); // we already output the tags before the messages
        $this->message_list_form($list);
      }

      if ($this->template === 'message') {
        $messages = $this->get_messages($list['messages']);
      } elseif ($this->template === 'groupmsg') {
        $messages = $this->get_group_messages($list['messages']);
      }
      $path = CML_DIR . '/message-template.html';
      include $path;

      if (!empty($list['form'])) {
        $this->output('</form>');
      }
    } else {
      qa_html_theme_base::message_list_and_form($list);
    }
  }

  private function get_messages($messages)
  {
    $ret = array();
    $loginuserhandle = qa_get_logged_in_handle();
    foreach ($messages as $message) {
      $tmp = array();
      $tmp['messageid'] = $message['raw']['messageid'];
      $content = $this->get_html($message['content']); 
      // $content = $message['raw']['content'];
      $tmp['content'] = $this->filter_content($content);
      if ($message['raw']['fromhandle'] === $loginuserhandle) {
        $tmp['status'] = 'sent';
        $tmp['color'] = 'mdl-color--orange-100';
        $tmp['textalign'] = 'text-align: right;';
        if ($tmp['content'] != qa_lang_html('custom_messages/message_deleted')) {
          $tmp['deleteable'] = true;
        } else {
          $tmp['deleteable'] = false;
        }
      } else {
        $tmp['status'] = 'received';
        $tmp['color'] = 'mdl-color--grey-50';
        $tmp['textalign'] = '';
        $tmp['deleteable'] = false;
      }
      $tmp['avatarblobid'] = $message['raw']['fromavatarblobid'];
      $created_date = qa_when_to_html($message['raw']['created'], 30);
      if (isset($created_date['suffix']) && !empty($created_date['suffix'])) {
        $tmp['created'] = $created_date['data'] . $created_date['suffix'];
      } else {
        $tmp_date = new DateTime('@'.$message['raw']['created']);
        $tmp_date->setTimeZone( new DateTimeZone('Asia/Tokyo'));
        $tmp['created'] = $tmp_date->format('Y年m月d日');
      }
      $ret[] = $tmp;
    }
    return $ret;
  }

  public function get_group_messages($messages)
  {
    $ret = array();
    $loginuserhandle = qa_get_logged_in_handle();
    foreach ($messages as $message) {
      $tmp = array();
      $raw = $message['raw'];
      $tmp['messageid'] = $raw['messageid'];
      if (empty($raw['handle'])) {
        $tmp['handle'] = qa_lang('main/anonymous');
      } else {
        $tmp['handle'] = $raw['handle'];
      }
      $content = $this->get_html($message['content']); 
      $tmp['content'] = $this->filter_content($content);
      if ($raw['handle'] === $loginuserhandle) {
        $tmp['status'] = 'sent';
        $tmp['color'] = 'mdl-color--orange-100';
        $tmp['textalign'] = 'style="text-align: right;display: block;"';
        $tmp['handle_align'] = 'style="text-align: right;display: block;"';
        if ($tmp['content'] != qa_lang_html('custom_messages/message_deleted')) {
          $tmp['deleteable'] = true;
        } else {
          $tmp['deleteable'] = false;
        }
      } else {
        $tmp['status'] = 'received';
        $tmp['color'] = 'mdl-color--grey-50';
        $tmp['textalign'] = 'style="text-align: right;display: block;"';
        $tmp['handle_align'] = '';
        $tmp['deleteable'] = false;
      }
      if (empty($raw['avatarblobid'])) {
        $tmp['avatarblobid'] = qa_opt('avatar_default_blobid');
      } else {
        $tmp['avatarblobid'] = $raw['avatarblobid'];
      }
      if (isset($message['when']['suffix']) && !empty($message['when']['suffix'])) {
        $tmp['created'] = $message['when']['data'] . $message['when']['suffix'];
      } else {
        $tmp_date = new DateTime('@'.$raw['created']);
        $tmp_date->setTimeZone( new DateTimeZone('Asia/Tokyo'));
        $tmp['created'] = $tmp_date->format('Y年m月d日');
      }
      $ret[] = $tmp;
    }
    return $ret;
  }

  public function nav($navtype, $level=null)
  {
    $templates = array('messages', 'message', 'groupmsg');
    if (qa_opt('site_theme') === CML_TARGET_THEME_NAME && in_array($this->template, $templates) ) {
      if ($navtype === 'sub') {
        unset($this->content['navigation']['sub']);
      }
    }
    qa_html_theme_base::nav($navtype, $level);
  }

  public function body_footer()
  {
      if (qa_opt('site_theme') === CML_TARGET_THEME_NAME 
         && ($this->template === 'message'
         || $this->template === 'groupmsg')) {
        if (strpos(qa_get_state(), 'message-sent') === false) {
          $this->output('<script src="'. CML_RELATIVE_PATH . 'js/message.js' . '"></script>');
        }
        if (strpos(qa_request(), 'message') !== false) {
          $delete_url = 'delete-message/private';
        } else {
          $delete_url = 'delete-message/group';
        }
        $path = CML_DIR . '/html/delete_confirm_dialog.html';
        include $path;
      }
      if (qa_opt('site_theme') === CML_TARGET_THEME_NAME &&
          $this->template === 'groupmsg') {
        $this->output_dialog_leave();
      }
      qa_html_theme_base::body_footer();
  }

  public function form_buttons($form, $columns)
  {
      if (qa_opt('site_theme') === CML_TARGET_THEME_NAME 
      && ($this->template === 'message'
      ||  $this->template === 'groupmsg')) {
          if(isset($form['buttons']['send'])) {
              $tags = $form['buttons']['send']['tags'];
              $tags .= " id='send-message'";
              $form['buttons']['send']['tags'] = $tags;
          }
      }
      qa_html_theme_base::form_buttons($form, $columns);
  }
  
  public function get_html($html) {
    require_once QA_INCLUDE_DIR.'util/string.php';

    $htmlunlinkeds=array_reverse(preg_split('|<[Aa]\s+[^>]+>.*</[Aa]\s*>|', $html, -1, PREG_SPLIT_OFFSET_CAPTURE)); // start from end so we substitute correctly

    foreach ($htmlunlinkeds as $htmlunlinked) { // and that we don't detect links inside HTML, e.g. <img src="http://...">
      $thishtmluntaggeds=array_reverse(preg_split('/<[^>]*>/', $htmlunlinked[0], -1, PREG_SPLIT_OFFSET_CAPTURE)); // again, start from end

      foreach ($thishtmluntaggeds as $thishtmluntagged) {
        $innerhtml=$thishtmluntagged[0];

        if (is_numeric(strpos($innerhtml, '://'))) { // quick test first
          $newhtml=qa_html_convert_urls($innerhtml, true);

          $html=substr_replace($html, $newhtml, $htmlunlinked[1]+$thishtmluntagged[1], strlen($innerhtml));
        }
      }
    }
    return $html;
  }

  private function output_not_posts() {
    $msg_temp = qa_lang('custom_messages/not_post_message');
    $subs = array(
      '^ask_url' => '/ask',
      '^root_url' => '/',
    );
    $message = strtr($msg_temp, $subs);
    $path = CML_DIR . '/html/not_post_qa_blog.html';
    $html_temp = file_get_contents($path);
    $html = strtr($html_temp, array('^message' => $message));
    $this->output($html);
  }

  private function output_buttons()
  {
    if ($this->template === 'messages') {
      $path = CML_DIR .'/html/messages_buttons.html';
      include $path;
    }
  }

  private function output_user_list()
  {
    $header_note = $this->content['list']['note'];
    $users = $this->content['list']['users'];
    $users2 = $this->content['list']['users2'];
    $path = CML_DIR .'/html/user_list.html';
    include $path;
  }

  private function output_group_header()
  {
    $path = CML_DIR . '/html/groupmsg_header.html';
    $groupid = @$this->content['groupid'];
    $group_chip = $this->content['message_list']['chip'];
    $button_leave = qa_lang('custom_messages/leave_button_label');
    if (@$this->content['group_notify'] == 1) {
      $button_notice = qa_lang('custom_messages/off_button_label');
      $button_name = 'do_group_notice_off';
    } else {
      $button_notice = qa_lang('custom_messages/on_button_label');
      $button_name = 'do_group_notice_on';
    }
    $title = $this->content['message_list']['title'];
    $action = qa_path('groupmsg/'.$groupid, null, qa_opt('site_url'));
    $code = $this->content['group']['code'];
    $user_count =  @$this->content['group_user_count'];
    if ($user_count < self::MAX_USER_NUM) {
      $invite_text = qa_lang('custom_messages/invite_groupmsg');
      $add_url = qa_path('messages',array('state' => 'add-user', 'groupid' => $groupid), qa_opt('site_url'));
      $invite_html = <<<EOF
<a href="{$add_url}">
        <i class="material-icons">keyboard_arrow_right</i>
        {$invite_text}
    </a>
EOF;
    } else {
      $invite_html = qa_lang('custom_messages/user_max_num_msg');
    }
    include $path;
  }

  private function output_select_group()
  {
    $header_note = $this->content['list']['note'];
    $header_sub = qa_lang('custom_messages/select_user');
    $action_path = qa_path('groupmsg', null, qa_opt('site_url'));
    $users = $this->content['list']['users'];
    $path = CML_DIR .'/html/select_group.html';
    include $path;
  }

  private function output_add_user()
  {
    $header_note = $this->content['list']['note'];
    $header_sub = qa_lang('custom_messages/add_user');
    $groupid = @$this->content['groupid'];
    $action_path = qa_path('groupmsg/'.$groupid, array('state'=>'add-user'), qa_opt('site_url'));
    $users = $this->content['list']['users'];
    $path = CML_DIR .'/html/select_group.html';
    include $path;
  }

  private function output_select_group_button()
  {
    if (count($this->content['list']['users']) > 0) {
      $max_user_num = self::MAX_USER_NUM;
      $min_select_num = 2;
      $path = CML_DIR . '/html/select_group_button.html';
      include $path;
    }
  }

  private function output_select_add_user_button()
  {
    if (count($this->content['list']['users']) > 0) {
      $max_user_num = self::MAX_USER_NUM;
      $current_user_num = @$this->content['group_user_count'];
      $path = CML_DIR . '/html/select_add_user_button.html';
      include $path;
    }
  }

  private function output_dialog_leave()
  {
    $path = CML_DIR . '/html/dialog_leave.html';
    include $path;
  }

  private function filter_content($content)
  {
    $tmp = $this->medium_editor_embed_replace($content);
    $new_content = qa_theme_utils::add_image_anchor($tmp);
    return $new_content;
  }
}
