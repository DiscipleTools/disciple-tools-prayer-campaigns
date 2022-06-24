let calendar_subscribe_object = window.jsObject
let current_time_zone = Intl.DateTimeFormat().resolvedOptions().timeZone || 'America/Chicago'
if ( calendar_subscribe_object.timezone ){
  current_time_zone = calendar_subscribe_object.timezone
}
const number_of_days = ( calendar_subscribe_object.end_timestamp - calendar_subscribe_object.start_timestamp ) / ( 24*3600)

let verified = false


function toggle_danger() {
  $('.danger-zone-content').toggleClass('collapsed');
  $('.chevron').toggleClass('toggle_up');
}

jQuery(document).ready(function($){

  //set up array of days and time slots according to timezone
  let days = window.campaign_scripts.calculate_day_times(current_time_zone);


  let week_day_names = window.campaign_scripts.get_days_of_the_week_initials(navigator.language, 'narrow')
  let headers = `
    <div class="new_weekday">${week_day_names[0]}</div>
    <div class="new_weekday">${week_day_names[1]}</div>
    <div class="new_weekday">${week_day_names[2]}</div>
    <div class="new_weekday">${week_day_names[3]}</div>
    <div class="new_weekday">${week_day_names[4]}</div>
    <div class="new_weekday">${week_day_names[5]}</div>
    <div class="new_weekday">${week_day_names[6]}</div>
  `
  let daily_time_select = $('#cp-daily-time-select')
  let modal_calendar = $('#day-select-calendar')
  let now = new Date().getTime()/1000
  let selected_times = [];
  calendar_subscribe_object.my_recurring = {}

  /**
   * Add notice showing that my times have been verified
   */
  if ( verified ){
    $("#times-verified-notice").show()
  }


  update_timezone()
  draw_calendar()

  calculate_my_time_slot_coverage()

  setup_duration_options()

  setup_daily_prayer_times()
  setup_individual_prayer_times()


  //change timezone
  $('#confirm-timezone').on('click', function (){
    current_time_zone = $("#timezone-select").val()
    update_timezone()
    days = window.campaign_scripts.calculate_day_times(current_time_zone)
    draw_calendar()
    display_my_commitments()
    draw_modal_calendar()
  })
  /**
   * Remove a prayer time
   */
  $(document).on("click", '.remove-my-prayer-time', function (){
    let x = $(this)
    let id = x.data("report")
    let time = x.data('time')
    x.removeClass("fi-x").addClass("loading-spinner active");
    jQuery.ajax({
      type: "POST",
      data: JSON.stringify({ action: 'delete', parts: calendar_subscribe_object.parts, report_id: id }),
      contentType: "application/json; charset=utf-8",
      dataType: "json",
      url: calendar_subscribe_object.root + calendar_subscribe_object.parts.root + '/v1/' + calendar_subscribe_object.parts.type,
      beforeSend: function (xhr) {
        xhr.setRequestHeader('X-WP-Nonce', calendar_subscribe_object.nonce )
      }
    })
    .done(function(data){
      $($(`*[data-report=${id}]`)[0].parentElement.parentElement).css({'background-color':'lightgray','text-decoration':'line-through'});
      $($(`*[data-report=${id}]`)[1].parentElement.parentElement).css({'background-color':'lightgray','text-decoration':'line-through'});
      x.removeClass("loading-spinner");
      console.log("adding deleted time" + time);
      $(`#selected-${time}`).addClass('deleted-time')
    })
    .fail(function(e) {
      console.log(e)
      jQuery('#error').html(e)
    })
  })

  /**
   * Modal for displaying on individual day
   */
  $('.new-day-number').on( 'click', function (){
    let day_timestamp = $(this).data('day')
    draw_day_coverage_content_modal( day_timestamp );
  })


  function calculate_my_time_slot_coverage(){
    let html = ``
    let in_three_months_in_seconds = now + 3600 * 24 * 90;
    let label = window.campaign_scripts.timestamp_to_format( in_three_months_in_seconds, { year:"numeric", month: "long", day: "numeric" }, current_time_zone )
    for ( const time in calendar_subscribe_object.my_recurring ){
      if ( calendar_subscribe_object.my_recurring[time].count > 1 ){
        html += `<tr>
          <td>${time}</td>
          <td>${calendar_subscribe_object.my_recurring[time].count}</td>
          <td><button class="button change-time-bulk" data-key="${time}">Change start time</button></td>
          <td><button class="button outline delete-time-bulk" data-key="${time}">x</button></td>
          <td><button class="button outline" data-key="${time}">Extend till ${label}</button></td>
          </tr>
        `
      }
    }
    $('#recurring_time_slots').empty().html(html)
  }

  let opened_daily_time_changed_modal = null
  $(document).on('click', '.change-time-bulk', function (){
    opened_daily_time_changed_modal = $(this).data('key');
    $('#change-times-modal').foundation('open')
  })
  $('#update-daily-time').on('click', function (){
    $(this).addClass('loading')
    const time = parseInt($('#change-time-select').val())
    const new_time = window.campaign_scripts.day_start(calendar_subscribe_object.my_recurring[opened_daily_time_changed_modal].time, current_time_zone) + time
    let data = {
      action: 'change_times',
      offset: new_time - calendar_subscribe_object.my_recurring[opened_daily_time_changed_modal].time,
      report_ids: calendar_subscribe_object.my_recurring[opened_daily_time_changed_modal].report_ids,
      parts: calendar_subscribe_object.parts
    }
    jQuery.ajax({
      type: "POST",
      data: JSON.stringify(data),
      contentType: "application/json; charset=utf-8",
      dataType: "json",
      url: calendar_subscribe_object.root + calendar_subscribe_object.parts.root + '/v1/' + calendar_subscribe_object.parts.type,
      beforeSend: function (xhr) {
        xhr.setRequestHeader('X-WP-Nonce', calendar_subscribe_object.nonce )
      }
    }).then(data=>{
      calendar_subscribe_object.my_commitments = data;
      draw_calendar();
      calculate_my_time_slot_coverage()
      $(this).removeClass('loading')
      $('#change-times-modal').foundation('close')
    })
  })

  let opened_delete_time_modal = null
  $(document).on('click', '.delete-time-bulk', function (){
    opened_delete_time_modal = $(this).data('key');
    $('#delete-time-slot-text').text(opened_delete_time_modal)
    $('#delete-times-modal').foundation('open')
  })
  $('#confirm-delete-daily-time').on('click', function (){
    $(this).addClass('loading')
    let data = {
      action: 'delete_times',
      report_ids: calendar_subscribe_object.my_recurring[opened_delete_time_modal].report_ids,
      parts: calendar_subscribe_object.parts
    }
    jQuery.ajax({
      type: "POST",
      data: JSON.stringify(data),
      contentType: "application/json; charset=utf-8",
      dataType: "json",
      url: calendar_subscribe_object.root + calendar_subscribe_object.parts.root + '/v1/' + calendar_subscribe_object.parts.type,
      beforeSend: function (xhr) {
        xhr.setRequestHeader('X-WP-Nonce', calendar_subscribe_object.nonce )
      }
    }).then(data=>{
      calendar_subscribe_object.my_commitments = data;
      draw_calendar();
      calculate_my_time_slot_coverage()
      $(this).removeClass('loading')
      $('#delete-times-modal').foundation('close')
    })
  })

  function update_timezone(){
    $('.timezone-current').html(current_time_zone)
    $('#selected-time-zone').val(current_time_zone).text(current_time_zone)
  }
  /**
   * Draw or refresh the main calendar
   */
  function draw_calendar( id = 'calendar-content'){
    let now = new Date().getTime()/1000
    let content = $(`#${id}`);
    content.empty();

    let current_month = window.campaign_scripts.timestamp_to_format( now, { month:"long" }, current_time_zone);
    let months = {};
    days.forEach(day=> {
      if (day.month === current_month || day.key > now) {
        if (!months[day.month]) {
          months[day.month] = {key: day.key}
        }
      }
    })
    let calendar = ``
    Object.keys(months).forEach( (key, index) =>{
      let this_month_content = ``;
      let day_number = window.campaign_scripts.get_day_number(months[key].key, current_time_zone);
      //add extra days at the month start
      for (let i = 0; i < day_number; i++) {
        //this_month_content += `<!--<div class="day-cell disabled-calendar-day"></div>-->`
        this_month_content += `<div class="new_day_cell"></div>`

      }
      // fill in calendar
      days.filter(k=>k.month===key).forEach(day=>{
        this_month_content +=`
          <div class="new_day_cell">
            <div class="new-day-number" data-time="${window.lodash.escape(day.key)}" data-day="${window.lodash.escape(day.key)}">${window.lodash.escape(day.day)}
              <div><small>${window.lodash.escape(parseInt(day.percent))}%</small></div>
              <div class="progress-bar-container">
                  <div class="progress-bar" data-percent="${window.lodash.escape(day.percent)}" style="width:${window.lodash.escape(parseInt(day.percent))}%"></div>
              </div>
            </div>
            <div class="day-extra" id=calendar-extra-${window.lodash.escape(day.key)}></div>
        </div>
        `
      })
      //add extra days at the month end
      if (day_number!==0) {
        for (let i = 1; i <= 7 - day_number; i++) {
          // calendar_days += `<div class="day-cell disabled-calendar-day"></div>`
          this_month_content += `<div class="new_day_cell"></div>`
        }
      }
      let display_calendar = index === 0 ? 'display:block' : 'display:none';
      let next_month_button = index < Object.keys(months).length -1 ? '' : 'display:none'
      let prev_month_button = index > 0 ? '' : 'display:none'
      calendar += `
        <div class="calendar-month" data-month-index="${index}" style="${display_calendar}">
          <div style="display: flex">
            <div class="goto-month-container"><button class="cp-goto-month" data-month-target="${index-1}" style="${prev_month_button}"><</button></div>
            <div style="flex-grow:1;">
              <div class="calendar-title">
                <h2>${window.lodash.escape(key)} ${new Date(months[key].key * 1000).getFullYear()}</h2>
              </div>
              <div class="new_calendar">
                ${headers}
                ${this_month_content}
              </div>
            </div>
            <div class="goto-month-container"><button class="cp-goto-month" data-month-target="${index+1}" style="${next_month_button}">></button></div>
          </div>
        </div>
      `
    })


    content.html(`<div class="grid-x" id="selection-grid-wrapper">${calendar}</div>`)
    display_my_commitments()
  }
  $(document).on('click', '#calendar-content .cp-goto-month', function (){
    let target = $(this).data('month-target');
    $('#calendar-content .calendar-month').hide()
    $(`#calendar-content .calendar-month[data-month-index='${target}']`).show()
  })

  /**
   * Show my commitment under each day
   */
  function display_my_commitments(){
    $('.day-extra').empty()
    calendar_subscribe_object.my_recurring = {}
    calendar_subscribe_object.my_commitments.forEach(c=>{
      let time = c.time_begin;
      let now = new Date().getTime()/1000
      if ( time >= now ){
        let day_timestamp = 0
        days.forEach(d=>{
          if ( d.key < c.time_begin ){
            day_timestamp = d.key
          }
        })

        let date = new Date( time * 1000 );
        let weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        let day_number = date.getDate();
        let day_weekday = weekdays[ date.getDay() ];

        let summary_text = window.campaign_scripts.timestamps_to_summary(c.time_begin, c.time_end, current_time_zone)
        if ( !calendar_subscribe_object.my_recurring[summary_text] ){
          calendar_subscribe_object.my_recurring[summary_text] = { count: 0, report_ids: [], time:parseInt(c.time_begin) }
        }
        calendar_subscribe_object.my_recurring[summary_text].count++;
        calendar_subscribe_object.my_recurring[summary_text].report_ids.push(c.report_id)

        $(`#calendar-extra-${window.lodash.escape(day_timestamp)}`).append(`
            <div class="prayer-commitment" id="selected-${window.lodash.escape(time)}"
                data-time="${window.lodash.escape(time)}">
                <div class="prayer-commitment-text">
                    ${window.lodash.escape(summary_text)}
                    <i class="fi-x remove-selection remove-my-prayer-time" data-report="${window.lodash.escape(c.report_id)}" data-time="${window.lodash.escape(time)}" data-day="${window.lodash.escape(day_timestamp)}"></i>
                </div>
            </div>
        `)
        $('#mobile-commitments-container').append(`
          <div class="mobile-commitments" id="mobile-commitment-${window.lodash.escape(time)}">
              <div class="mobile-commitments-date">
                  <div class="mc-day"><b>${window.lodash.escape(day_weekday)}</b></div>
                  <div class="mc-day">${window.lodash.escape(day_number)}</div>
              </div>
              <div class="mc-prayer-commitment-description">
                  <div class="mc-prayer-commitment-text">
                      <div class="mc-description-duration">${window.lodash.escape(summary_text)}</div>
                      <div class="mc-description-time"> <i class="fi-x remove-selection remove-my-prayer-time" style="margin-left:6px;" data-report="${window.lodash.escape(c.report_id)}" data-time="${window.lodash.escape(time)}" data-day="${window.lodash.escape(day_timestamp)}"></i></div>
                  </div>
              </div>
          </div>`)
      }
    })
  }


  /**
   * Modal for displaying on individual day
   */
  function draw_day_coverage_content_modal( day_timestamp ){
    $('#view-times-modal').foundation('open')
    let list_title = jQuery('#list-modal-title')
    let day=days.find(k=>k.key===day_timestamp)
    list_title.empty().html(`<h2 class="section_title">${window.lodash.escape(day.formatted)}</h2>`)
    let day_times_content = $('#day-times-table-body')
    let times_html = ``
    let row_index = 0
    day.slots.forEach(slot=>{
      let background_color = 'white'
      if ( slot.subscribers > 0) {
        background_color = '#1e90ff75'
      }
      if ( row_index === 0 ){
        times_html += `<tr><td>${window.lodash.escape(slot.formatted)}</td>`
      }
      times_html +=`<td style="background-color:${background_color}">
          ${window.lodash.escape(slot.subscribers)} <i class="fi-torsos"></i>
      </td>`
      if ( times_html === 3 ){
        times_html += `</tr>`
      }
      row_index = row_index === 3 ? 0 : row_index + 1;
    })
    day_times_content.empty().html(`<div class="grid-x"> ${times_html} </div>`)
  }


  /**
   * daily prayer time screen
   */
  function setup_daily_select(){

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
    $('.cp-daily-time-select').html(select_html)

  }

  function setup_duration_options(){
    let duration_options_html = ``
    for (const prop in calendar_subscribe_object.duration_options) {
      if (calendar_subscribe_object.duration_options.hasOwnProperty(prop) && parseInt(prop) >= parseInt(calendar_subscribe_object.slot_length) ) {
        duration_options_html += `<option value="${window.lodash.escape(prop)}">${window.lodash.escape(calendar_subscribe_object.duration_options[prop].label)}</option>`
      }
    }
    $(".cp-time-duration-select").html(duration_options_html)

  }


  function setup_daily_prayer_times(){
    setup_daily_select()

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
        let time_label = time_date.toFormat('MMMM dd HH:mm a');
        let already_added = selected_times.find(k=>k.time===time)
        if ( !already_added && time > now && time >= calendar_subscribe_object['start_timestamp'] ) {
          selected_times.push({time: time, duration: duration, label: time_label})
        }
      }
      submit_times();
    })
  }

  /**
   * Individual prayer times screen
   */
  function setup_individual_prayer_times(){
    draw_modal_calendar()

    let current_time_selected = $("cp-individual-time-select").val();
    $(document).on( 'click', '.remove-prayer-time-button', function (){
      let time = parseInt($(this).data('time'))
      selected_times = selected_times.filter(t=>parseInt(t.time) !== time)
      display_selected_times()
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

    $(document).on('click', '#day-select-calendar .cp-goto-month', function (){
      let target = $(this).data('month-target');
      $('#day-select-calendar .calendar-month').hide()
      $(`#day-select-calendar .calendar-month[data-month-index='${target}']`).show()
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


    $('#cp-confirm-individual-times').on( 'click', function (){
      submit_times();
    })
  }




  //build the list of individually selected times
  function display_selected_times(){
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

  //dawn calendar in date select view

  function draw_modal_calendar() {
    let current_month = window.campaign_scripts.timestamp_to_format( now, { month:"long" }, current_time_zone);
    modal_calendar.empty()
    let list = ''
    let months = {};
    days.forEach(day=> {
      if (day.month === current_month || day.key > now) {
        if (!months[day.month]) {
          months[day.month] = {key: day.key}
        }
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
        <div style="display: flex; justify-content: center">
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
        </div></div>
      `
    })
    modal_calendar.html(list)
  }




  let submit_times = function(){
    let submit_button = $('.submit-form-button')
    submit_button.addClass( 'loading' )
    let data = {
      action: 'add',
      selected_times,
      parts: calendar_subscribe_object.parts
    }
    jQuery.ajax({
      type: "POST",
      data: JSON.stringify(data),
      contentType: "application/json; charset=utf-8",
      dataType: "json",
      url: calendar_subscribe_object.root + calendar_subscribe_object.parts.root + '/v1/' + calendar_subscribe_object.parts.type,
      beforeSend: function (xhr) {
        xhr.setRequestHeader('X-WP-Nonce', calendar_subscribe_object.nonce )
      }
    })
    .done(function(response){
      $('.hide-on-success').hide();
      submit_button.removeClass('loading')
      $('#modal-calendar').hide()

      $(`.success-confirmation-section`).show()
      calendar_subscribe_object.my_commitments = response
      display_my_commitments()
      submit_button.prop('disabled', false)
    })
    .fail(function(e) {
      console.log(e)
      $('#selection-error').empty().html(`<div class="cell center">
                        So sorry. Something went wrong. Please, contact us to help you through it, or just try again.<br>
                        <a href="${window.lodash.escape(window.location.href)}">Try Again</a>
                        </div>`).show()
      $('#error').html(e)
      submit_button.removeClass('loading')
    })
  }

  $('.close-ok-success').on("click", function (){
    window.location.reload()
  })


  $('#allow_notifications').on('change', function (){
    let selected_option = $(this).val();
    $('.notifications_allowed_spinner').addClass('active')
    jQuery.ajax({
      type: "POST",
      data: JSON.stringify({parts: calendar_subscribe_object.parts, allowed:selected_option==="allowed"}),
      contentType: "application/json; charset=utf-8",
      dataType: "json",
      url: calendar_subscribe_object.root + calendar_subscribe_object.parts.root + '/v1/' + calendar_subscribe_object.parts.type + '/allow-notifications',
      beforeSend: function (xhr) {
        xhr.setRequestHeader('X-WP-Nonce', calendar_subscribe_object.nonce )
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
    let wrapper = jQuery('#wrapper')
    jQuery.ajax({
      type: "DELETE",
      data: JSON.stringify({parts: calendar_subscribe_object.parts}),
      contentType: "application/json; charset=utf-8",
      dataType: "json",
      url: calendar_subscribe_object.root + calendar_subscribe_object.parts.root + '/v1/' + calendar_subscribe_object.parts.type + '/delete_profile',
      beforeSend: function (xhr) {
        xhr.setRequestHeader('X-WP-Nonce', calendar_subscribe_object.nonce )
      }
    }).done(function(){
      wrapper.empty().html(`
          <div class="center">
          <h1>Your profile has been deleted!</h1>
          <p>Thank you for praying with us.<p>
          </div>
      `)
      spinner.removeClass('active')
      $(`#delete-profile-modal`).foundation('close')
    })
    .fail(function(e) {
      console.log(e)
      $('#confirm-delete-profile').toggleClass('loading')
      $('#delete-account-errors').empty().html(`<div class="grid-x"><div class="cell center">
        So sorry. Something went wrong. Please, contact us to help you through it, or just try again.<br>

        </div></div>`)
      $('#error').html(e)
      spinner.removeClass('active')
    })
  })

  /**
   * Display mobile commitments if screen dimension is narrow
   */
  if ( innerWidth < 475 ) {
    $( '.prayer-commitment' ).attr( 'class', 'prayer-commitment-tiny' );
    $( '.mc-title' ).show();
    $( '#mobile-commitments-container' ).show();
  }
})
