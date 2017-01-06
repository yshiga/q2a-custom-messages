<?php
class qa_html_theme_layer extends qa_html_theme_base {
  public function main() {
    if (qa_opt('site_theme') === CUL_TARGET_THEME_NAME && $this->template === 'messages') {
      $template = file_get_contents(CML_DIR . '/messages-template.html');
      $this->output($template);

    } else if (qa_opt('site_theme') === CUL_TARGET_THEME_NAME && $this->template === 'message') {
      $template = file_get_contents(CML_DIR . '/message-template.html');
      $this->output($template);
    } else {
      qa_html_theme_base::main();
    }
  }
}
