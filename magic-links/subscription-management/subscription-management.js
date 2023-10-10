let current_time_zone = Intl.DateTimeFormat().resolvedOptions().timeZone || 'America/Chicago'
if ( window.subscription_page_data.timezone ){
  current_time_zone = window.subscription_page_data.timezone
}

function toggle_danger() {
  $('.danger-zone-content').toggleClass('collapsed');
  $('.chevron').toggleClass('toggle_up');
}

jQuery(document).ready(function($){
  $('.nav-bar button').on('click', function (){
    $('.nav-bar button').removeClass('active')
    $(this).addClass('active')
    $('.display-panel').hide()
    $(`#${$(this).data('show')}`).show()
  })

  $('#allow_notifications').on('change', function (){
    let selected_option = $(this).val();
    $('.notifications_allowed_spinner').addClass('active')
    jQuery.ajax({
      type: "POST",
      data: JSON.stringify({parts: window.subscription_page_data.parts, allowed:selected_option==="allowed"}),
      contentType: "application/json; charset=utf-8",
      dataType: "json",
      url: window.subscription_page_data.root + window.subscription_page_data.parts.root + '/v1/' + window.subscription_page_data.parts.type + '/allow-notifications',
      beforeSend: function (xhr) {
        xhr.setRequestHeader('X-WP-Nonce', window.subscription_page_data.nonce )
      }
    }).done(function(){
      $('.notifications_allowed_spinner').removeClass('active')
    })
    .fail(function(e) {

    })
  })

  /**
   * Delete profile
   */
  $('#confirm-delete-profile').on('click', function (){
    let spinner = $(this)

    jQuery.ajax({
      type: "DELETE",
      data: JSON.stringify({parts: window.subscription_page_data.parts}),
      contentType: "application/json; charset=utf-8",
      dataType: "json",
      url: window.subscription_page_data.root + window.subscription_page_data.parts.root + '/v1/' + window.subscription_page_data.parts.type + '/delete_profile',
      beforeSend: function (xhr) {
        xhr.setRequestHeader('X-WP-Nonce', window.subscription_page_data.nonce )
      }
    }).done(function(){
      show_delete_profile_success(spinner)
    })
    .fail(function(e) {
      console.log(e)
      if ( e.status === 200 ){
        show_delete_profile_success(spinner)
        return
      }
      $('#confirm-delete-profile').toggleClass('loading')
      $('#delete-account-errors').empty().html(`<div class="grid-x"><div class="cell center">
        So sorry. Something went wrong. Please, contact us to help you through it, or just try again.<br>

        </div></div>`)
      $('#error').html(e)
      spinner.removeClass('active')
    })
  })

  function show_delete_profile_success(spinner){
    let wrapper = jQuery('#wrapper')
    wrapper.empty().html(`
          <div class="center">
          <h1>Your profile has been deleted!</h1>
          <p>Thank you for praying with us.<p>
          </div>
      `)
    spinner.removeClass('active')
    $(`#delete-profile-modal`).foundation('close')
  }
})
