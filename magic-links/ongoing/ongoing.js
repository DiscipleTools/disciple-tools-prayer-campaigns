"use strict";

let current_time_zone = Intl.DateTimeFormat().resolvedOptions().timeZone || 'America/Chicago'

let calendar_subscribe_object = {
  start_timestamp: 0,
  end_timestamp: 0,
  slot_length: 15,
  duration_options: {}
}
let escapeObject = (obj) => {
  return Object.fromEntries(Object.entries(obj).map(([key, value]) => {
    return [ key, window.lodash.escape(value)]
  }))
}

jQuery(document).ready(async function ($) {
  let jsObject = window.campaign_objects

  let data = await get_campaign_data()
  $('.cp-wrapper').removeClass("loading-content")
  $('.cp-loading-page').hide()

  calendar_subscribe_object = { ...calendar_subscribe_object, ...data }
  calendar_subscribe_object.translations = escapeObject( jsObject.translations )
  let days = window.campaign_scripts.calculate_day_times()


  let week_day_names = days_for_locale(navigator.language, 'narrow')
  let headers = `
    <div class="day-cell week-day">${week_day_names[0]}</div>
    <div class="day-cell week-day">${week_day_names[1]}</div>
    <div class="day-cell week-day">${week_day_names[2]}</div>
    <div class="day-cell week-day">${week_day_names[3]}</div>
    <div class="day-cell week-day">${week_day_names[4]}</div>
    <div class="day-cell week-day">${week_day_names[5]}</div>
    <div class="day-cell week-day">${week_day_names[6]}</div>
  `;
  let daily_time_select = $('#cp-daily-time-select')
  let selected_times = [];
  let current_time_selected = $("#cp-individual-time-select").val();
  let modal_calendar = $('#day-select-calendar')

  draw_calendar()

  setup_signup();

  update_timezone()

  //draw progress circles
  window.customElements.define('progress-ring', ProgressRing);

  $('#confirm-timezone').on('click', function (){
    current_time_zone = $("#timezone-select").val()
    update_timezone()
    days = window.campaign_scripts.calculate_day_times(current_time_zone)
    // set_campaign_date_range_title()
    populate_daily_select()
    draw_calendar()
    draw_modal_calendar()
  })


  function setup_signup(){
    if ( calendar_subscribe_object.status === "inactive"){
      $('#cp-view-closed').show()
      $("#cp-wrapper").css("min-height", '500px')
    } else {
      $('#cp-main-page').show()
    }

    //navigation function
    $('.cp-nav').on( 'click', function (){
      $('.cp-view').hide()
      let view_to_open = $(this).data('open')
      $(`#${view_to_open}`).show()

      //force the screen to scroll to the top of the wrapper
      if ( $(this).data('force-scroll')){
        let elmnt = document.getElementById("cp-wrapper");
        elmnt.scrollIntoView();
      }

      //configure the view to go back to
      let back_to = $(this).data('back-to');
      if ( back_to )
      $(`#${view_to_open} .cp-close-button`).data('open', back_to)
    })


    populate_daily_select();
    duration_options_select()

    draw_modal_calendar();

    daily_time_select.on("change", function (){
      $('#cp-confirm-daily-times').attr('disabled', false)
    })

    $('#cp-confirm-daily-times').on("click", function (){
      let daily_time_selected = parseInt($("#cp-daily-time-select").val());
      let duration = parseInt($("#cp-prayer-time-duration-select").val())

      let start_time = days[0].key + daily_time_selected;
      let start_date = window.luxon.DateTime.fromSeconds(start_time).setZone(current_time_zone)
      let now = new Date().getTime()/1000
      for ( let i = 0; i < days.length; i++){
        let time_date = start_date.plus({day:i})
        let time = parseInt( time_date.toFormat('X') );
        let time_label = time_date.toFormat('HH:mm a');
        let already_added = selected_times.find(k=>k.time===time)
        if ( !already_added && time > now && time >= calendar_subscribe_object['start_timestamp'] ) {
          selected_times.push({time: time, duration: duration, label: time_label, key:now})
        }
      }
      let text = calendar_subscribe_object.translations.praying_everyday.replace( '%1$s', selected_times[selected_times.length-1].label).replace( '%2$s', selected_times[selected_times.length-1].duration )
      $('.cp-daily-selected-times').append(`<li id="cp-daily-key-${now}">${window.lodash.escape(text)}<button class="remove-daily-time-button" data-key="${now}">x</button></li>`)
    })
    $(document).on( 'click', '.remove-daily-time-button', function (){
      let key = $(this).data('key');
      let key_int = parseInt(key)
      selected_times = selected_times.filter(t=>parseInt(t.key) !== key_int)
      $('#cp-daily-key-' + key).remove()
      $(this).parent().remove()
    })



    //add a selected time to the array
    $('#cp-add-prayer-time').on("click", function(){
      current_time_selected = $("#cp-individual-time-select").val();
      let duration = parseInt($("#cp-individual-prayer-time-duration-select").val())
      let time_label = window.campaign_scripts.timestamp_to_format( current_time_selected, { month: "long", day: "numeric", hour:"numeric", minute: "numeric" }, current_time_zone)
      let now = new Date().getTime()/1000
      let already_added = selected_times.find(k=>k.time===current_time_selected)
      if ( !already_added && current_time_selected > now && current_time_selected >= calendar_subscribe_object['start_timestamp'] ){
        $('#cp-time-added').show().fadeOut(1000)
        selected_times.push({time: current_time_selected, duration: duration, label: time_label })
      }
      display_selected_times()
      $('#cp-confirm-individual-times').attr('disabled', false)
    })

    $(document).on( 'click', '.remove-prayer-time-button', function (){
      let time = parseInt($(this).data('time'))
      selected_times = selected_times.filter(t=>parseInt(t.time) !== time)
      display_selected_times()
    })


    //when a day is clicked on from the calendar
    $(document).on('click', '.day-in-select-calendar', function (){
      $('#day-select-calendar div').removeClass('selected-day')
      $(this).toggleClass('selected-day')
      //get day and build content
      let day_key = parseInt($(this).data("day"))
      let day=days.find(k=>k.key===day_key);
      //set time key on add button
      $('#cp-add-prayer-time').data("day", day_key).attr('disabled', false)

      //build time select
      let select_html = ``;
      day.slots.forEach(slot=> {
        let text = ``
        if ( slot.subscribers===1 ) {
          text = "(covered once)";
        }
        if ( slot.subscribers > 1 ) {
          text = `(covered ${slot.subscribers} times)`;
        }
        select_html += `<option value="${window.lodash.escape(slot.key)}" ${ (slot.key%(24*3600)) === (current_time_selected%(24*3600)) ? "selected" : '' }>
            ${window.lodash.escape(slot.formatted)} ${window.lodash.escape(text)}
        </option>`
      })
      $('#cp-individual-time-select').html(select_html).attr('disabled', false)
    })


    //submit form
    $('#cp-submit-form').on('click', function (){
      let submit_spinner = $('#cp-submit-form-spinner');
      let submit_button = jQuery('#cp-submit-form')
      submit_button.prop('disabled', true)
      submit_spinner.show()

      let honey = jQuery('#email').val()
      if ( honey ) {
        window.spinner.hide()
        return;
      }

      let name_input = jQuery('#name')
      let name = name_input.val()
      if ( ! name ) {
        jQuery('#name-error').show()
        submit_spinner.hide()
        name_input.focus(function(){
          jQuery('#name-error').hide()
        })
        submit_button.prop('disabled', false)
        return;
      }

      let email_input = jQuery('#e2')
      let email = email_input.val()
      if ( ! email ) {
        jQuery('#email-error').show()
        submit_spinner.hide()
        email_input.focus(function(){
          jQuery('#email-error').hide()
        })
        submit_button.prop('disabled', false)
        return;
      }

      let receive_prayer_time_notifications = $('#receive_prayer_time_notifications').is(':checked')

      let data = {
        name: name,
        email: email,
        selected_times: selected_times,
        campaign_id: calendar_subscribe_object.campaign_id,
        timezone: current_time_zone,
        receive_prayer_time_notifications,
        parts: jsObject.parts
      }
      send_submission(data, submit_spinner)
    })

    //when click ok after submit
    $('.cp-ok-done-button').on( 'click', function (){
      window.location.reload()
    })

    $(document).on('click', '.cp-goto-month', function (){
      let target = $(this).data('month-target');
      $('#day-select-calendar .calendar-month').hide()
      $(`#day-select-calendar .calendar-month[data-month-index='${target}']`).show()
    })
  }
  //Sign up functions
  function send_submission(data, submit_spinner){
    let link = jsObject.root + jsObject.parts.root + '/v1/' + jsObject.parts.type;
    if ( window.campaign_objects.remote ){
      link =  jsObject.root + jsObject.parts.root + '/v1/24hour-router';
    }
    data.url = '';
    jQuery.ajax({
      type: "POST",
      data: JSON.stringify(data),
      contentType: "application/json; charset=utf-8",
      dataType: "json",
      url: link
    })
    .done(function(){
      selected_times = [];
      submit_spinner.hide()
      $(`.success-confirmation-section`).show()
    })
    .fail(function(e) {
      $('#selection-error').empty().html(`<div class="cell center">
        So sorry. Something went wrong. Please, contact us to help you through it, or just try again.<br>
        <a href="${window.lodash.escape(window.location.href)}">Try Again</a>
        </div>`).show()
      $('#error').html(e)
      submit_spinner.hide()
    })
  }
  function populate_daily_select(){
    let select_html = `<option value="false">${calendar_subscribe_object.translations.select_a_time}</option>`

    let coverage = {}
    days.forEach(val=> {
      let day = val.key
      for ( const key in calendar_subscribe_object.current_commitments ){
        if (!calendar_subscribe_object.current_commitments.hasOwnProperty(key)) {
          continue;
        }
        if ( key >= day && key < day + 24 * 3600 ){
          let mod_time = key % (24 * 60 * 60)
          let time_formatted = '';
          if ( window.campaign_scripts.processing_save[mod_time] ){
            time_formatted = window.campaign_scripts.processing_save[mod_time]
          } else {
            time_formatted = window.campaign_scripts.timestamp_to_time( parseInt(key), current_time_zone )
            window.campaign_scripts.processing_save[mod_time] = time_formatted
          }
          if ( !coverage[time_formatted]){
            coverage[time_formatted] = [];
          }
          coverage[time_formatted].push(calendar_subscribe_object.current_commitments[key]);
        }
      }
    })
    let key = 0;
    let start_of_today = new Date()
    start_of_today.setHours(0,0,0,0)
    let start_time_stamp = start_of_today.getTime()/1000
    while ( key < 24 * 3600 ){
      let time_formatted = window.campaign_scripts.timestamp_to_time(start_time_stamp+key)
      let text = ''
      let fully_covered = window.campaign_scripts.time_slot_coverage[time_formatted] ? window.campaign_scripts.time_slot_coverage[time_formatted] === number_of_days : false;
      let level_covered = coverage[time_formatted] ? Math.min(...coverage[time_formatted]) : 0
      if ( fully_covered && level_covered > 1  ){
        text = `(${calendar_subscribe_object.translations.fully_covered_x_times.replace( '%1$s', level_covered)})`
      } else if ( fully_covered ) {
        text = `(${calendar_subscribe_object.translations.fully_covered_once})`
      }
      select_html += `<option value="${window.lodash.escape(key)}">
          ${window.lodash.escape(time_formatted)} ${ window.lodash.escape(text) }
      </option>`
      key += calendar_subscribe_object.slot_length * 60
    }
    daily_time_select.empty();
    daily_time_select.html(select_html)
  }
  function duration_options_select(){
    let duration_options_html = ``
    for (const prop in calendar_subscribe_object.duration_options) {
      if (calendar_subscribe_object.duration_options.hasOwnProperty(prop) && parseInt(prop) >= parseInt(calendar_subscribe_object.slot_length) ) {
        duration_options_html += `<option value="${window.lodash.escape(prop)}">${window.lodash.escape(calendar_subscribe_object.duration_options[prop].label)}</option>`
      }
    }
    $(".cp-time-duration-select").html(duration_options_html)
  }
  function display_selected_times(  ){
    let html = ""
    selected_times.sort((a,b)=>{
      return a.time - b.time
    });
    selected_times.forEach(time=>{
      html += `<li>
        ${calendar_subscribe_object.translations.time_slot_label.replace( '%1$s', time.label).replace( '%2$s', time.duration )}
        <button class="remove-prayer-time-button" data-time="${time.time}">x</button>
      </li>`
    })
    $('.cp-display-selected-times').html(html)
  }

  function update_timezone(){
    $('.timezone-current').html(current_time_zone)
    $('#selected-time-zone').val(current_time_zone).text(current_time_zone)
  }


  //Calendar Functions
  function draw_modal_calendar(){
    let now = new Date().getTime()/1000
    modal_calendar.empty()
    let list = ''
    let months = {};
    days.forEach(day=> {
      if (!months[day.month]) {
        months[day.month] = {key:day.key}
      }
    })
    Object.keys(months).forEach( (key, index) =>{

      let this_month_content = ``
      let day_number = window.campaign_scripts.get_day_number(months[key].key, current_time_zone);

      //add extra days at the month start
      for (let i = 0; i < day_number; i++) {
        this_month_content += `<div class="day-cell disabled-calendar-day"></div>`
      }
      // fill in calendar
      days.filter(k=>k.month===key).forEach(day=>{
        let disabled = (day.key + (24 * 3600)) < now;
        this_month_content += `
          <div class="day-cell ${disabled ? 'disabled-calendar-day':'day-in-select-calendar'}" data-day="${window.lodash.escape(day.key)}">
              ${window.lodash.escape(day.day)}
          </div>
        `
      })
      //add extra days at the month end
      if (day_number!==0) {
        for (let i = 1; i <= 7 - day_number; i++) {
          this_month_content += `<div class="day-cell disabled-calendar-day"></div>`
        }
      }

      let display_calendar = index === 0 ? 'display:block' : 'display:none';
      let next_month_button = index < Object.keys(months).length -1 ? '' : 'display:none'
      let prev_month_button = index > 0 ? '' : 'display:none'

      list += `<div class="calendar-month" data-month-index="${index}" style="${display_calendar}">
        <div style="display: flex">
          <div class="goto-month-container"><button class="cp-goto-month" data-month-target="${index-1}" style="${prev_month_button}"><</button></div>
          <div>
            <h3 class="month-title center">
                <strong>${window.lodash.escape(key).substring(0,3)}</strong>
                ${new Date(months[key].key * 1000).getFullYear()}
            </h3>
            <div class="calendar">
              ${headers}
              ${this_month_content}
            </div>
            </div>
          <div class="goto-month-container"><button class="cp-goto-month" data-month-target="${index+1}" style="${next_month_button}">></button></div>
        </div>
      `
      list += `</div></div>`
    })
    modal_calendar.html(list)
  }
  function draw_calendar( id = 'calendar-content' ){
    let content = $(`#${window.lodash.escape(id)}`)
    content.empty()
    let list = ``
    let months = {};
    days.forEach(day=> {
      if (!months[day.month]) {
        months[day.month] = {with: 0, without: 0, key:day.key}
      }
      months[day.month].without += day.slots.length- day.covered_slots
    })

    Object.keys(months).forEach( (key, index) =>{
      //only show 2 months
      if ( index < 2 ){
        list += `<div class="calendar-month">
          <h3 class="month-title"><strong>${window.lodash.escape(key).substring(0,3)}</strong> ${new Date(months[key].key * 1000).getFullYear()}
          ${ months[key].with / months[key].without * 100 }%
          </h3>
          <div class="calendar">
        `
        list += headers
        let day_number = window.campaign_scripts.get_day_number(months[key].key, current_time_zone);
        //add extra days at the month start
        for (let i = 0; i < day_number; i++) {
          list += `<div class="day-cell disabled-calendar-day"></div>`
        }

        // fill in calendar
        days.filter(k=>k.month===key).forEach(day=>{
          if ( day.disabled ){
            list += `<div class="day-cell disabled-calendar-day">
          </div>`
          } else {
            list +=`
              <div class="display-day-cell" data-day=${window.lodash.escape(day.key)}>
                  <progress-ring stroke="3" radius="20" progress="${window.lodash.escape(day.percent)}" text="${window.lodash.escape(day.day)}"></progress-ring>
              </div>
            `
          }
        })

        //add extra days at the month end
        if (day_number!==0) {
          for (let i = 1; i <= 7 - day_number; i++) {
            list += `<div class="day-cell disabled-calendar-day"></div>`
          }
        }
        list += `</div></div>`
      }
    })

    content.html(`${list}`)
  }
  function days_for_locale(localeName = 'en-US', weekday = 'long') {
    let now = new Date()
    const format = new Intl.DateTimeFormat(localeName, { weekday }).format;
    return [...Array(7).keys()]
    .map((day) => format(new Date().getTime() - ( now.getDay() - day  ) * 86400000 ));
  }
  function get_campaign_data() {
    let link =  jsObject.root + jsObject.parts.root + '/v1/' + jsObject.parts.type + '/campaign_info';
    return jQuery.ajax({
      type: "GET",
      data: {action: 'get', parts: jsObject.parts, 'url': 'campaign_info', time: new Date().getTime()},
      contentType: "application/json; charset=utf-8",
      dataType: "json",
      url: link
    })
    .done(function (data) {
      return data
    })
  }
})
