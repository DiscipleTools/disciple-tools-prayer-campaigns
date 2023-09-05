"use strict";

let current_time_zone = Intl.DateTimeFormat().resolvedOptions().timeZone || 'America/Chicago'
let selected_times = [];

const now = parseInt(new Date().getTime()/1000);
let calendar_subscribe_object = {
  start_timestamp: 0,
  end_timestamp: 0,
  slot_length: 15,
  duration_options: {},
  coverage_by_month: {}
}
let escapeObject = (obj) => {
  return Object.fromEntries(Object.entries(obj).map(([key, value]) => {
    return [ key, window.lodash.escape(value)]
  }))
}

jQuery(document).ready(function($) {
  let jsObject = window.campaign_objects


  calendar_subscribe_object.translations = escapeObject( jsObject.translations )
  let days

  let week_day_names = window.campaign_scripts.get_days_of_the_week_initials(navigator.language, 'narrow')
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
  let current_time_selected = $("#cp-individual-time-select").val();
  let modal_calendar = $('#day-select-calendar')

  // coverage_by_month();

  get_campaign_data().then((data) => {
    $('.cp-wrapper').removeClass("loading-content")
    $('.cp-loading-page').hide()
    calendar_subscribe_object = { ...calendar_subscribe_object, ...data }
    days = window.campaign_scripts.calculate_day_times( current_time_zone, now - 30 * day_in_seconds )
    draw_calendar()
    setup_signup();
  })

  update_timezone(current_time_zone)


  $('#confirm-timezone').on('click', function (){
    current_time_zone = $("#timezone-select").val()
    update_timezone(current_time_zone)
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
      if ( back_to ){
        $(`#${view_to_open} .cp-close-button`).data('open', back_to)
      }
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
      let date_label = window.campaign_scripts.timestamp_to_format( selected_times[selected_times.length - 1].time, { month: "long", day: "numeric" }, current_time_zone)

      let time_text = calendar_subscribe_object.duration_options[selected_times[selected_times.length-1].duration]?.label || selected_times[selected_times.length-1].duration;
      let text = calendar_subscribe_object.translations.praying_everyday.replace( '%1$s', selected_times[selected_times.length-1].label).replace( '%2$s', time_text ).replace( '%3$s', date_label )
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
          text = `(${calendar_subscribe_object.translations.covered_once})`;
        }
        if ( slot.subscribers > 1 ) {
          text = `(${calendar_subscribe_object.translations.covered_x_times.replace( '%1$s', slot.subscribers)})`
        }
        let disabled = slot.key < calendar_subscribe_object.start_timestamp ? 'disabled' : '';
        let selected = ( slot.key % day_in_seconds) === ( current_time_selected % day_in_seconds ) ? "selected" : '';
        select_html += `<option value="${window.lodash.escape(slot.key)}" ${selected} ${disabled}>
            ${window.lodash.escape(slot.formatted)} ${window.lodash.escape(text)}
        </option>`
      })
      $('#cp-individual-time-select').html(select_html).attr('disabled', false)
    })


    $(document).on('click', '.cp-goto-month', function (){
      let target = $(this).data('month-target');
      $('#day-select-calendar .calendar-month').hide()
      $(`#day-select-calendar .calendar-month[data-month-index='${target}']`).show()
    })
  }

  function populate_daily_select(){
    let select_html = `<option value="false">${calendar_subscribe_object.translations.select_a_time}</option>`

    let months = coverage_by_month(true)
    let key = 0;
    let start_of_today = new Date('2023-01-01')
    start_of_today.setHours(0,0,0,0)
    let start_time_stamp = start_of_today.getTime()/1000
    while ( key < day_in_seconds ){
      let time_formatted = window.campaign_scripts.timestamp_to_time(start_time_stamp+key)
      let months_covered = [];
      Object.keys(months).forEach(m=>{
        if (months[m].coverage[time_formatted] && months[m].coverage[time_formatted].length === months[m].days_in_month){
          months_covered.push(m);
        }
      })
      let text = ''
      if ( months_covered.length ){
        text = months_covered.join(', ') + ' ' + calendar_subscribe_object.translations.covered
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

  /**
   * Calculate which times slots are filled for each month
   * @param after_now
   * @returns {{}}
   */
  function coverage_by_month( after_now = false ){
    let months = {};
    days.forEach(day=> {
      if ( !after_now || day.key > now ){
        if (!months[day.month]) {
          let date = new Date( day.key * 1000 );
          let days_in_month = new Date( date.getFullYear(), date.getMonth()+1, 0).getDate()
          if ( date.getMonth() === new Date().getMonth() ){
            days_in_month = days_in_month - new Date().getDate()
          }
          months[day.month] = {key: day.key, coverage:{}, days_in_month, covered_slots:0}
        }
        months[day.month].covered_slots += day.covered_slots
        day.slots.filter(s => s.subscribers > 0).forEach(slot=>{
          if ( !months[day.month].coverage[slot.formatted] ){
            months[day.month].coverage[slot.formatted] = []
          }
           months[day.month].coverage[slot.formatted].push(slot.subscribers)
        })
      }
    })
    calendar_subscribe_object.coverage_by_month = months
    return months
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

  function update_timezone(tz){
    $('.timezone-current').html(tz)
    $('#selected-time-zone').val(tz).text(tz)
  }


  //Calendar Functions
  function draw_modal_calendar(){
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
        let disabled = (day.key + day_in_seconds) < now;
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
        </div></div>
      `
    })
    modal_calendar.html(list)
  }

  function draw_calendar( id = 'calendar-content' ){
    let content = $(`#${window.lodash.escape(id)}`)
    content.empty()
    let list = ``
    let months = {};
    let current_month = window.campaign_scripts.timestamp_to_format( now, { month:"long" }, current_time_zone);

    days.forEach(day=> {
      if ( day.month === current_month || day.key > now ){
        if (!months[day.month]) {
          months[day.month] = {with: 0, without: 0, key:day.key, minutes_covered:0 }
        }

        months[day.month].with += day.covered_slots
        months[day.month].without += day.slots.length - day.covered_slots
        months[day.month].minutes_covered += day.covered_slots * calendar_subscribe_object.slot_length
      }
    })

    Object.keys(months).forEach( (key, index) =>{
      //only show 2 months
      if ( index < 2 ){

        let calendar_days = ``
        let day_number = window.campaign_scripts.get_day_number(months[key].key, current_time_zone);
        //add extra days at the month start
        for (let i = 0; i < day_number; i++) {
          calendar_days += `<div class="day-cell disabled-calendar-day"></div>`
        }

        // fill in calendar
        days.filter(k=>k.month===key).forEach(day=>{
          if ( day.disabled ){
            calendar_days += `<div class="day-cell disabled-calendar-day">
          </div>`
          } else {
            calendar_days +=`
              <div class="display-day-cell" data-day=${window.lodash.escape(day.key)}>
                  <progress-ring stroke="3" radius="20" progress="${window.lodash.escape(day.percent)}" text="${window.lodash.escape(day.day)}"></progress-ring>
              </div>
            `
          }
        })

        //add extra days at the month end
        if (day_number!==0) {
          for (let i = 1; i <= 7 - day_number; i++) {
            calendar_days += `<div class="day-cell disabled-calendar-day"></div>`
          }
        }
        list += `<div class="calendar-month">
          <h3 class="month-title"><strong>${window.lodash.escape(key).substring(0,3)}</strong> ${new Date(months[key].key * 1000).getFullYear()}
            <span class="month-percentage">${ (months[key].with / ( months[key].without + months[key].with )* 100).toFixed( 2 ) }% | ${(months[key].minutes_covered / 60/24).toFixed( 1 )} ${calendar_subscribe_object.translations.days}</span>
          </h3>
          <div class="calendar">
            ${headers}
            ${calendar_days}
          </div>
        </div>`

      }
    })

    content.html(`${list}`)
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
