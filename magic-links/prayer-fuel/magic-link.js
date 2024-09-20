jQuery(document).ready(function($) {
  const translations = escapeObject(window.prayer_fuel_scripts.translations)

  /**
   * Extend prayer times if needed
   */
  if ( window.prayer_fuel_scripts.auto_extend_prayer_times ) {
    window.campaign_scripts.get_campaign_data().then((data) => {
      const recurring_signups = data?.subscriber_info?.my_recurring_signups
      const timezone = window.campaign_user_data.timezone
      if (recurring_signups.length) {
        recurring_signups.forEach((recurring_sign) => {
          //extend the prayer times if the last time is within the month months
          const one_months_from_now = (new Date().getTime() / 1000) + 30 * day_in_seconds
          const now = new Date().getTime() / 1000
          if (recurring_sign.last > now && recurring_sign.last < one_months_from_now && ( !window.campaign_data.end_timestamp || ( recurring_sign.last + 30 * day_in_seconds < window.campaign_data.end_timestamp ) ) ) {
            let recurring_extend = window.campaign_scripts.build_selected_times_for_recurring(
              recurring_sign.time, recurring_sign.type,
              recurring_sign.duration,
              recurring_sign.week_day || null,
              recurring_sign.last
            );
            recurring_extend.report_id = recurring_sign.report_id

            //filter out existing times
            let existing_times = window.campaign_data.subscriber_info.my_commitments.filter(c => recurring_sign.report_id===c.recurring_id).map(c => parseInt(c.time_begin))
            recurring_extend.selected_times = recurring_extend.selected_times.filter(c => !existing_times.includes(c.time))
            recurring_extend.auto_extend = true
            window.campaign_scripts.submit_prayer_times(recurring_sign.campaign_id, recurring_extend, 'update_recurring_signup').then(resp => {
              let last_day = recurring_extend.last.toLocaleString({ day: 'numeric', month: 'short', year: 'numeric' })
              last_day = `<strong>${last_day}</strong>`
                let html = `<div class="alert" id="${recurring_sign.report_id}">
                   <h4>${translations['Your prayer time has been extended']}</h4>
                    <p>${translations['signup_up_to_pray'].replace( '%1$s', recurring_extend.label).replace( '%2$s', last_day)}</p>
                    <button class="btn btn-secondary btn-common dissmiss" data-recurring-id="${recurring_sign.report_id}">${translations['OK']}</button>
                    <button class="btn btn-secondary btn-common email-calendar" data-recurring-id="${recurring_sign.report_id}">${translations['Email me the updated calendar']}</button>
                </div>`
                $('#alert-section').append(html)
            })
            // else {
            //   let html = `<div class="prayer-times-extend alert" id="${recurring_sign.report_id}">
            //     <h4>Your prayer time will be ending soon.</h4>
            //     <p>Extend it to keep on praying. You are signed up to pray:</p>
            //     <p>${recurring_extend.label} ending on ${last_day}</p>
            //     <button class="btn btn-primary btn-common extend-confirm" data-recurring-id="${recurring_sign.report_id}">Extend until ${recurring_extend.last.toLocaleString({ day: 'numeric', month: 'short', year: 'numeric' })}</button>
            //     <button class="btn btn-secondary btn-common extend-cancel" style="background-color: grey" data-recurring-id="${recurring_sign.report_id}">Dismiss this reminder</button>
            //   </div>`
            //   $('#alert-section').append(html)
            // }
          }
        })
      }
    })
  }

  $(document).on('click', '.dissmiss', function(e) {
    let recurring_id = $(this).data('recurring-id')
    $('#'+recurring_id).fadeOut(1000)
  })

  $(document).on('click', '.email-calendar', function(e) {
      $(this).append('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>')

      $.ajax({
        type: 'POST',
        contentType: 'application/json; charset=utf-8',
        dataType: 'json',
        url: window.prayer_fuel_scripts.site_url + "/wp-json/prayer/v1/fuel/email-calendar",
        data: JSON.stringify({parts: window.prayer_fuel_scripts.parts}),
        beforeSend: (xhr) => {
          xhr.setRequestHeader("X-WP-Nonce", window.prayer_fuel_scripts.nonce);
        },
      }).then( ()=>{
        $(this).html('Email sent')
        $('#confirmation_email_sent').show()
      })
  })

  $(document).on('click', '.extend-confirm', function(e) {
    let recurring_id = $(this).data('recurring-id')
    let recurring_sign = window.campaign_data.subscriber_info.my_recurring_signups.find(c => c.report_id === recurring_id)
    let recurring_extend = window.campaign_scripts.build_selected_times_for_recurring(
      recurring_sign.time, recurring_sign.type,
      recurring_sign.duration,
      recurring_sign.week_day || null,
      recurring_sign.last
    );
    recurring_extend.report_id = recurring_sign.report_id

    //filter out existing times
    let existing_times = window.campaign_data.subscriber_info.my_commitments.filter(c => recurring_sign.report_id===c.recurring_id).map(c => parseInt(c.time_begin))
    recurring_extend.selected_times = recurring_extend.selected_times.filter(c => !existing_times.includes(c.time))
    recurring_extend.auto_extend = true

    $(this).html('Extending...');
    window.campaign_scripts.submit_prayer_times(recurring_sign.campaign_id, recurring_extend, 'update_recurring_signup').then(resp => {
      $(this).html('Extended!');
      setTimeout(()=>{
        $('#'+recurring_id).fadeOut(1000)
      },1000)
    })
  })


  $('#share-button').click(function() {
    let url = window.prayer_fuel_scripts.campaign_url  + '/list/';

    let data = {
      title: translations['Prayer Fuel'],
      text: translations['Join me in praying!'],
      url: url,
    }

    //if mobile
    if (window.navigator.share && window.navigator.canShare(data)) {
      window.navigator.share(data)
    } else {
      let $temp = $("<input>");
      $("body").append($temp);
      $temp.val(url).select();
      document.execCommand("copy");
      $temp.remove();
      $('#share-button').html('Link copied!')
    }
  })
})