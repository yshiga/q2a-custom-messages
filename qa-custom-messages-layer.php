<?php
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
        $content = $this->get_html($message['raw']['content']); 
        // $content = $message['raw']['content'];
        $tmp['content'] = $this->medium_editor_embed_replace($content);
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
        $created_date = qa_when_to_html($message['raw']['created'], 30);
        if ($created_date['suffix']) {
          $tmp['created'] = $created_date['data'] . $created_date['suffix'];
        } else {
          $tmp_date = new DateTime('@'.$message['raw']['created']);
          $tmp_date->setTimeZone( new DateTimeZone('Asia/Tokyo'));
          $tmp['created'] = $tmp_date->format('Y年m月d日');
        }
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
}
