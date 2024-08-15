

let submit_form = function (){

  $(this).addClass("loading")

  let email = $('#email-2').val();

  window.makeRequest( "POST", '/', { parts: jsObject.parts, email }, jsObject.rest_namespace ).done(function(data){
    $('#form-content').hide()
    $('#form-confirm').show()
  })
  .fail(function(e) {
    console.log(e)
    jQuery('#error').html(e)
  })
}
