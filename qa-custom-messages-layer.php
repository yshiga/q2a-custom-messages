<?php

require_once CML_DIR.'/cml-db-client.php';

class qa_html_theme_layer extends qa_html_theme_base {
  public function main_parts($content) {
    if (qa_opt('site_theme') === CML_TARGET_THEME_NAME && $this->template === 'messages') {
      // $template = file_get_contents(CML_DIR . '/messages-template.html');
      // $this->output($template);
      if (qa_is_logged_in()) {
        $messages = $content['message_list']['messages'];
        $path = CML_DIR . '/messages-template.html';
        include $path;
      }
    } elseif (qa_opt('site_theme') === CML_TARGET_THEME_NAME && $this->template === 'message') {
      $show_ok = cml_db_client::check_show_user_message(qa_get_logged_in_userid(), 30);
      $messages = isset($content['message_list']);
      if ($messages || $show_ok) {
        qa_html_theme_base::main_parts($content);
      } else {
        $this->output_not_posts();
      }
    } else {
      qa_html_theme_base::main_parts($content);
    }
  }

  public function page_title_error() {
    $templates = array('messages', 'message');
    if (qa_opt('site_theme') === CML_TARGET_THEME_NAME && in_array($this->template, $templates) ) {
  		if (isset($this->content['error']))
  			$this->error($this->content['error']);
    } else {
      qa_html_theme_base::page_title_error();
    }

  }

  public function message_list_and_form($list)
  {
    if (qa_opt('site_theme') === CML_TARGET_THEME_NAME && $this->template === 'message') {
      if (strpos(qa_get_state(), 'message-sent') === false) {
          $input_error_msg = qa_lang('custom_messages/messege_input_error');
          $this->output('<div id="content-error" class="mdl-card__supporting-text">');
          $this->output('<span class="mdl-color-text--red">', $input_error_msg, '</span>');
          $this->output('</div>');
      }
      $this->part_title($list);

      $this->error(@$list['error']);

      if (!empty($list['form'])) {
        $this->output('<form '.$list['form']['tags'].'>');
        unset($list['form']['tags']); // we already output the tags before the messages
        $this->message_list_form($list);
      }

      $messages = array();
      $loginuserhandle = qa_get_logged_in_handle();
      foreach ($list['messages'] as $message) {
        $tmp = array();
        $tmp['content'] = $this->medium_editor_embed_replace($message['raw']['content']);
        if ($message['raw']['fromhandle'] === $loginuserhandle) {
          $tmp['status'] = 'sent';
          $tmp['color'] = 'mdl-color--orange-100';
          $tmp['textalign'] = 'style="text-align: right;"';
        } else {
          $tmp['status'] = 'received';
          $tmp['color'] = 'mdl-color--grey-50';
          $tmp['textalign'] = '';
        }
        $tmp['avatarblobid'] = $message['raw']['fromavatarblobid'];
        $create_date = new DateTime('@'.$message['raw']['created']);
        $create_date->setTimeZone( new DateTimeZone('Asia/Tokyo'));
        $tmp['created'] = $create_date->format('Y年m月d日');
        $messages[] = $tmp;
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

  public function nav($navtype, $level=null)
  {
    $templates = array('messages', 'message');
    if (qa_opt('site_theme') === CML_TARGET_THEME_NAME && in_array($this->template, $templates) ) {
      if ($navtype === 'sub') {
        unset($this->content['navigation']['sub']);
      }
    }
    qa_html_theme_base::nav($navtype, $level);
  }

  public function body_footer()
  {
      if (qa_opt('site_theme') === CML_TARGET_THEME_NAME && $this->template === 'message') {
        if (strpos(qa_get_state(), 'message-sent') === false) {
          $this->output('<script src="'. CML_RELATIVE_PATH . 'js/message.js' . '"></script>');
        }
      }
  }

  public function form_buttons($form, $columns)
  {
      if (qa_opt('site_theme') === CML_TARGET_THEME_NAME && $this->template === 'message') {
          if(isset($form['buttons']['send'])) {
              $tags = $form['buttons']['send']['tags'];
              $tags .= " id='send-message'";
              $form['buttons']['send']['tags'] = $tags;
          }
      }
      qa_html_theme_base::form_buttons($form, $columns);
  }
  
  private function output_not_posts() {
    $path = CML_DIR . '/html/not_post_qa_blog.html';
    $html = file_get_contents($path);
    $this->output($html);
  }
}
