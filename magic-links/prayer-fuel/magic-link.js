jQuery(document).ready(function($) {
  /**
   * Extend prayer times if needed
   */
  window.campaign_scripts.get_campaign_data().then((data) => {
    const recurring_signups = data?.subscriber_info?.my_recurring_signups
    const timezone = window.campaign_user_data.timezone

    let query_params = new URLSearchParams(window.location.search)

    if (recurring_signups.length) {
      recurring_signups.forEach((recurring_sign) => {
        //extend the prayer times if the last time is within the next two months
        const two_months_from_now = (new Date().getTime() / 1000) + 60 * day_in_seconds
        const now = new Date().getTime() / 1000
        if (recurring_sign.last > now && recurring_sign.last < two_months_from_now) {
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
          //@todo translate
          //@todo dismiss
          let last_day = window.luxon.DateTime.fromSeconds(recurring_sign.last, {zone:timezone}).toLocaleString({ day: 'numeric', month: 'short', year: 'numeric' })
          last_day = `<strong>${last_day}</strong>`
          if ( query_params.get('auto') === 'true' && window.campaign_user_data.auto_extend_prayer_times ) {
            //@todo auto extend
            let html = `<div class="alert" id="${recurring_sign.report_id}">
               <h4>Your prayer time has automatically been extended</h4>
                <p>You are signed up to pray:</p>
                <p>${recurring_extend.label} until ${last_day}</p>
                <button class="btn btn-secondary btn-common dissmiss" data-recurring-id="${recurring_sign.report_id}">Ok</button>
            </div>`
            $('#alert-section').append(html)
          } else {
            let html = `<div class="prayer-times-extend alert" id="${recurring_sign.report_id}">
              <h4>Your prayer time will be ending soon.</h4>
              <p>Extend it to keep on praying. You are signed up to pray:</p>
              <p>${recurring_extend.label} ending on ${last_day}</p>
              <button class="btn btn-primary btn-common extend-confirm" data-recurring-id="${recurring_sign.report_id}">Extend until ${recurring_extend.last.toLocaleString({ day: 'numeric', month: 'short', year: 'numeric' })}</button>
              <button class="btn btn-secondary btn-common extend-cancel" style="background-color: grey" data-recurring-id="${recurring_sign.report_id}">Dismiss this reminder</button>
            </div>`
            $('#alert-section').append(html)
          }
        }
      })
    }
  })

  $(document).on('click', '.dissmiss', function(e) {
    let recurring_id = $(this).data('recurring-id')
    $('#'+recurring_id).fadeOut(1000)
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

    //@todo translate
    let data = {
      title: 'Prayer Fuel',
      text: 'Join me in praying!',
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