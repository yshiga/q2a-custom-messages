
$(function(){
  // turn_off_submit_by_enter();
  update_confirm_status();

  var content_elem = document.getElementById('content');
  var content_editor = new MediumEditor('.editable');
  editor.subscribe('editableInput', function (event, editable) {
      update_confirm_status();
  });


  function update_confirm_status(){
    var content = get_content('content');
    content = content.replace(/<p><br><\/p>/g, '');
    content = content.replace(/<p class=""><br><\/p>/g, '');    
    content = content.replace(/<p class="">(&nbsp;\s?)*<\/p>/g, '');
    console.log(content);
    var length = content.length;
    var disabled = true;
    if(length > 0) {
      disabled = false;
    }
    $(".qa-part-form-message button").prop("disabled", disabled);
    if(disabled) {
      $("#content-error").show();
    } else {
      $("#content-error").hide();
    }
  }
  
  
});
