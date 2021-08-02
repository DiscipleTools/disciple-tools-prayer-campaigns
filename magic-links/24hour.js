"use strict";

let time_slot_coverage = {}
let current_time_zone = Intl.DateTimeFormat().resolvedOptions().timeZone || 'America/Chicago'

let calendar_subscribe_object = {
  number_of_time_slots: 0,
  coverage_levels: [],
  description: "",
  coverage_percentage: 0,
  second_level: 0,
  start_timestamp: 0,
  end_timestamp: 0,
  slot_length: 15,
}

jQuery(document).ready(function($) {
  let jsObject = window.campaign_objects

  jQuery.ajax({
    type: "GET",
    data: {action: 'get', parts: jsObject.parts},
    contentType: "application/json; charset=utf-8",
    dataType: "json",
    url: jsObject.root + jsObject.parts.root + '/v1/' + jsObject.parts.type + '/campaign_info'
  })
  .done(function (data) {

    calendar_subscribe_object = { ...calendar_subscribe_object, ...data}
    calendar_subscribe_object.end_timestamp -= 1;
    let days = window.campaign_scripts.calculate_day_times()
    const number_of_days = ( calendar_subscribe_object.end_timestamp - calendar_subscribe_object.start_timestamp ) / ( 24*3600)
    $('#cp-wrapper').removeClass("loading-content")
    //display description from remote
    $('#campaign-description').text(calendar_subscribe_object.description)
    //show coverage level
    if ( calendar_subscribe_object.coverage_levels[0].blocks_covered === calendar_subscribe_object.number_of_time_slots){
        $('#coverage-level').text(`All of the ${calendar_subscribe_object.number_of_time_slots} time slots are covered in once prayer once. Help us cover them twice!`)
    } else {
      let ppl_needed =  (60*24) / calendar_subscribe_object.slot_length;
      //&#128100
      $('#coverage-level').html(`${ppl_needed} people praying ${calendar_subscribe_object.slot_length} min everyday day will cover the region in 24h prayer.`)
    }

    //main progress circle
    $('#main-progress').html(`
      <progress-ring stroke="10" radius="80" font="18"
                     progress="${calendar_subscribe_object.coverage_percentage}"
                     progress2="${calendar_subscribe_object.second_level}"
                     text="${calendar_subscribe_object.coverage_percentage}%"
                     text2="${calendar_subscribe_object.text2 || ''}">
      </progress-ring>
    `)
    //draw progress circles
    window.customElements.define('progress-ring', ProgressRing);

    //navigation function
    $('.cp-nav').on( 'click', function (){
      $('.cp-view').hide()
      $(`#${$(this).data('open')}`).show()
      if ( $(this).data('force-scroll')){
        let elmnt = document.getElementById("cp-wrapper");
        elmnt.scrollIntoView();
      }
    })

    let set_campaign_date_range_title = function (){
      let start_time = window.campaign_scripts.timestamp_to_format( calendar_subscribe_object.start_timestamp, { month: "long", day: "numeric", hour:"numeric", minute:"numeric" }, current_time_zone)
      let end_time = window.campaign_scripts.timestamp_to_format( calendar_subscribe_object.end_timestamp, { month: "long", day: "numeric", hour:"numeric", minute:"numeric" }, current_time_zone)
      let start_end = `Everyday from <strong>${start_time}</strong> to <strong>${end_time}</strong>`
      $('#cp-start-end').html(start_end);
    }
    set_campaign_date_range_title()



    let headers = `
      <div class="day-cell week-day">Su</div>
      <div class="day-cell week-day">Mo</div>
      <div class="day-cell week-day">Tu</div>
      <div class="day-cell week-day">We</div>
      <div class="day-cell week-day">Th</div>
      <div class="day-cell week-day">Fr</div>
      <div class="day-cell week-day">Sa</div>
    `

    //display main calendar
    let draw_calendar = ( id = 'calendar-content') => {
      let content = $(`#${window.lodash.escape(id)}`)
      content.empty()
      let last_month = "";
      let list = ``
      days.forEach(day=>{
        if ( day.month !== last_month ){
          if ( last_month ){
            //add extra days at the month end
            let day_number = new Date(window.campaign_scripts.day_start(day.key, current_time_zone) * 1000).getDay()
            for ( let i = 1; i <= 7-day_number; i++ ){
              list +=  `<div class="day-cell disabled-calendar-day">${window.lodash.escape(i)}</div>`
            }
            list += `</div>`
          }

          list += `<h3 class="month-title">${window.lodash.escape(day.month)}</h3><div class="calendar">`
          if( !last_month ){
            list += headers
          }

          //add extra days at the month start
          let day_number = new Date(window.campaign_scripts.day_start(day.key, current_time_zone) * 1000).getDay()
          let start_of_week = new Date ( ( day.key - day_number * 86400 ) * 1000 )
          for ( let i = 0; i < day_number; i++ ){
            list +=  `<div class="day-cell disabled-calendar-day">${window.lodash.escape(start_of_week.getDate()+i)}</div>`
          }
          last_month = day.month
        }
        if ( day.disabled ){
          list += `<div class="day-cell disabled-calendar-day">
              ${day.day}
          </div>`
        } else {
          list +=`
            <div class="display-day-cell" data-day=${window.lodash.escape(day.key)}>
                <progress-ring stroke="3" radius="20" progress="${window.lodash.escape(day.percent)}" text="${window.lodash.escape(day.day)}"></progress-ring>
            </div>
          `
        }
      })
      list += `</div>`

      content.html(`<div class="" id="selection-grid-wrapper">${list}</div>`)
    }
    draw_calendar()

    /**
     * daily prayer time screen
     */
    let daily_time_select = $('#cp-daily-time-select')

    let select_html = `<option value="false">Select a time</option>`

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
        text = `(fully covered ${level_covered} times)`
      } else if ( fully_covered ) {
        text = "(fully covered once)"
      }
      select_html += `<option value="${window.lodash.escape(key)}"}>
          ${window.lodash.escape(time_formatted)} ${ window.lodash.escape(text) }
      </option>`
      key += calendar_subscribe_object.slot_length * 60
    }
    daily_time_select.empty();
    daily_time_select.html(select_html)


    daily_time_select.on("change", function (){
      $('#cp-confirm-daily-times').attr('disabled', false)
    })

    $('#cp-confirm-daily-times').on("click", function (){
      let daily_time_selected = parseInt($("#cp-daily-time-select").val());
      let duration = parseInt($("#cp-prayer-time-duration-select").val())
      days.forEach( day=>{
        let time = day.key + daily_time_selected;
        let now = new Date().getTime()/1000
        let time_label = window.campaign_scripts.timestamp_to_format( time, { month: "long", day: "numeric", hour:"numeric", minute: "numeric" }, current_time_zone)
        if ( time > now && time >= calendar_subscribe_object['start_timestamp'] ) {
          selected_times.push({time: time, duration: duration, label: time_label})
        }
      })
    })



    /**
     * Individual prayer times screen
     */
    let current_time_selected = $("cp-individual-time-select").val();

    //build the list of individually selected times
    let selected_times = [];
    let display_selected_times = function (){
      let html = ""
      selected_times.sort((a,b)=>{
        return a.time - b.time
      });
      selected_times.forEach(time=>{
        html += `<li>${time.label} for ${time.duration} minutes. </li>`
      })
      $('#cp-display-selected-times').html(html)
    }

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

    //dawn calendar in date select modal
    let modal_calendar = $('#day-select-calendar')
    let now = new Date().getTime()/1000
    let draw_modal_calendar = ()=> {
      let last_month = "";
      modal_calendar.empty()
      let list = ''
      days.forEach(day => {
        if (day.month!==last_month) {
          if (last_month) {
            //add extra days at the month end
            let day_number = new Date(window.campaign_scripts.day_start(day.key, current_time_zone) * 1000).getDay()
            for (let i = 1; i <= 7 - day_number; i++) {
              list += `<div class="day-cell disabled-calendar-day">${window.lodash.escape(i)}</div>`
            }
            list += `</div>`
          }

          list += `<h3 class="month-title">${window.lodash.escape(day.month)}</h3><div class="calendar">`
          if (!last_month) {
            list += headers
          }

          //add extra days at the month start
          let day_number = new Date(window.campaign_scripts.day_start(day.key, current_time_zone) * 1000).getDay()
          let start_of_week = new Date((day.key - day_number * 86400) * 1000)
          for (let i = 0; i < day_number; i++) {
            list += `<div class="day-cell disabled-calendar-day">${window.lodash.escape(start_of_week.getDate() + i)}</div>`
          }
          last_month = day.month
        }
        let disabled = (day.key + (24 * 3600)) < now;
        list += `
            <div class="day-cell ${disabled ? 'disabled-calendar-day':'day-in-select-calendar'}" data-day="${window.lodash.escape(day.key)}">
                ${window.lodash.escape(day.day)}
            </div>
        `
      })
      modal_calendar.html(list)
    }
    draw_modal_calendar()

    //when a day is click on from the calendar
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
        jQuery('#next_1').html('Shame, shame, shame. We know your name ... ROBOT!').prop('disabled', true )
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
      jQuery.ajax({
        type: "POST",
        data: JSON.stringify(data),
        contentType: "application/json; charset=utf-8",
        dataType: "json",
        url: jsObject.root + jsObject.parts.root + '/v1/' + jsObject.parts.type
      })
      .done(function(){
        selected_times = [];
        submit_spinner.hide()
        $(`.success-confirmation-section`).show()
      })
      .fail(function(e) {
        console.log(e)
        $('#selection-error').empty().html(`<div class="cell center">
                        So sorry. Something went wrong. Please, contact us to help you through it, or just try again.<br>
                        <a href="${window.lodash.escape(window.location.href)}">Try Again</a>
                        </div>`).show()
        $('#error').html(e)
        submit_spinner.hide()
      })
    })

  //@todo timezone
    let update_timezone = function (){
      $('.timezone-current').html(current_time_zone)
      $('#selected-time-zone').val(current_time_zone).text(current_time_zone)
    }
    update_timezone()

    $('#confirm-timezone').on('click', function (){
      current_time_zone = $("#timezone-select").val()
      update_timezone()
      days = window.campaign_scripts.calculate_day_times(current_time_zone)
      set_campaign_date_range_title()
      draw_calendar()
      draw_modal_calendar()
    })



  })
})
