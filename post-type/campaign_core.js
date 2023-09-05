const day_in_seconds = 86400
window.campaign_scripts = {
  time_slot_coverage: {},
  processing_save: {},
  calculate_day_times: function (custom_timezone=null, start = null){
    //set up array of days and time slots according to timezone
    window.campaign_scripts.processing_save = {}
    window.campaign_scripts.time_slot_coverage = {}
    window.campaign_scripts.time_label_counts = {}
    window.campaign_scripts.missing_slots = {}
    let days = [];

    if ( !custom_timezone ){
      custom_timezone = Intl.DateTimeFormat().resolvedOptions().timeZone || 'America/Chicago'
    }

    let start_of_day = window.campaign_scripts.day_start( start || calendar_subscribe_object.start_timestamp, custom_timezone )
    let time_iterator = parseInt( start_of_day );

    let timezone_change_ref = this.timestamp_to_time( time_iterator, custom_timezone )
    let now = new Date().getTime() / 1000;

    while ( time_iterator < calendar_subscribe_object.end_timestamp ){

      if ( !days.length || time_iterator >= ( start_of_day + day_in_seconds ) ){

        let timezone_date = this.timestamp_to_time( time_iterator + day_in_seconds, custom_timezone )
        if ( timezone_change_ref !== null && timezone_date !== timezone_change_ref ){
          // Timezone change detected. Recalculating time slots.
          window.campaign_scripts.processing_save = {}
        }
        timezone_change_ref = this.timestamp_to_time( time_iterator, custom_timezone )

        start_of_day = ( time_iterator >= start_of_day + day_in_seconds ) ? time_iterator : start_of_day
        let day = window.campaign_scripts.timestamp_to_month_day( time_iterator, custom_timezone )

        days.push({
          "key": start_of_day,
          'day_start_zoned': window.campaign_scripts.day_start( time_iterator, custom_timezone ),
          "formatted": day,
          "month": window.campaign_scripts.timestamp_to_format( time_iterator, { month:"long" }, custom_timezone),
          "day": window.campaign_scripts.timestamp_to_format( time_iterator, { day:"numeric" }, custom_timezone),
          "percent": 0,
          "slots": [],
          "covered_slots": 0,
        })
      }

      //calculate time slot
      let mod_time = time_iterator % day_in_seconds
      let time_formatted = '';
      if ( window.campaign_scripts.processing_save[mod_time] ){
        time_formatted = window.campaign_scripts.processing_save[mod_time]
      } else {
        time_formatted = window.campaign_scripts.timestamp_to_time(time_iterator, custom_timezone)
        window.campaign_scripts.processing_save[mod_time] = time_formatted
      }
      if ( time_iterator >= calendar_subscribe_object.start_timestamp && time_iterator < calendar_subscribe_object.end_timestamp) {
        days[days.length - 1]["slots"].push({
          "key": time_iterator,
          "formatted": time_formatted,
          "subscribers": parseInt(calendar_subscribe_object.current_commitments[time_iterator] || 0)
        })


        if (!window.campaign_scripts.time_label_counts[time_formatted]) {
          window.campaign_scripts.time_label_counts[time_formatted] = 0
        }
        window.campaign_scripts.time_label_counts[time_formatted] += 1

        if (calendar_subscribe_object.current_commitments[time_iterator]) {
          days[days.length - 1].covered_slots += 1;

          if (!window.campaign_scripts.time_slot_coverage[time_formatted]) {
            window.campaign_scripts.time_slot_coverage[time_formatted] = [];
          }
          window.campaign_scripts.time_slot_coverage[time_formatted].push(calendar_subscribe_object.current_commitments[time_iterator]);
        } else {
          if (time_iterator >= now) {
            if (!window.campaign_scripts.missing_slots[time_formatted]) {
              window.campaign_scripts.missing_slots[time_formatted] = []
            }
            window.campaign_scripts.missing_slots[time_formatted].push(time_iterator)
          }
        }
      }
      time_iterator += calendar_subscribe_object.slot_length * 60;
    }
    days.forEach(d=>{
      d.percent = d.covered_slots / d.slots.length * 100
    })
    window.campaign_scripts.processing_save = {}

    return days;
  },
  //format date to month and day
  timestamp_to_month_day: function(timestamp, timezone = null){
    const options = { month: "long", day: "numeric" };
    if ( timezone ){
      options.timeZone = timezone
    }
    return new Intl.DateTimeFormat("en-US", options).format(
      timestamp * 1000
    );
  },
  //format date to hour:minutes
  timestamp_to_time: function (timestamp, timezone = null){
    const options = { hour: "numeric", minute: "numeric" };
    if ( timezone ){
      options.timeZone = timezone
    }
    return new Intl.DateTimeFormat("en-US", options).format(
      timestamp * 1000
    );
  },
  timestamp_to_format: ( timestamp, options, timezone = null )=>{
    if ( timezone ){
      options.timeZone = timezone
    }
    return new Intl.DateTimeFormat("en-US", options).format(
      timestamp * 1000
    );
  },

  //clean formatted summary for prayer commitment display
  timestamps_to_summary: function( timestamp_start, timestamp_end, timezone ) {
    const options = { hour: "numeric", minute: "numeric", timeZone: timezone };
    let summary = '';
    let date_start_clean = new Intl.DateTimeFormat("en-US", options).format( timestamp_start * 1000 );

    // Don't show the minutes if there are none
    summary = date_start_clean.toString().replace(':00', '');

    // Calculate time duration
    let date_start = new Date( timestamp_start * 1000 );
    let date_end = new Date( timestamp_end * 1000 );
    let time_duration = ( date_end - date_start ) / 60000;

    // Add minute, hour or hours suffix
    if ( time_duration < 60 ) {
        time_duration = time_duration + ' min';
    }
    if (time_duration == 60 ) {
        time_duration = time_duration / 60 + ' hr';
    }
    if (time_duration > 60 ) {
        time_duration = time_duration / 60 + ' hrs';
    }

    summary += ' (' + time_duration + ')';
    return summary;
  },

  /**
   * return the start of day timestamp of a particular timezone
   * @param timestamp
   * @param timezone
   * @returns {number}
   */
  day_start: (timestamp, timezone) =>{
    let date = new Date( timestamp * 1000)
    let invdate = new Date(date.toLocaleString('en-US', {
      timeZone: timezone
    }));
    let diff = date.getTime() - invdate.getTime();
    invdate.setHours(0,0,0,0)
    return (invdate.getTime()+diff)/1000

  },
  get_day_number: (timestamp, timezone) => {
    let date = new Date( timestamp * 1000)
    let invdate = new Date(date.toLocaleString('en-US', {
      timeZone: timezone
    }));
    return invdate.getDay();
  },
  day_start_timestamp_utc:( timestamp ) => {
    let start_of_day = new Date(timestamp*1000)
    start_of_day.setHours(0,0,0,0)
    return start_of_day.getTime()/1000
  },
  start_of_week: (timestamp, timezone) => {
    let day_number = window.campaign_scripts.get_day_number( timestamp, timezone )

    let date = new Date( ( timestamp - day_number * 86400 ) * 1000)
    let invdate = new Date(date.toLocaleString('en-US', {
      timeZone: timezone
    }));
    return invdate;

  },
  get_days_of_the_week_initials: (localeName = 'en-US', weekday = 'long')=>{
    let now = new Date()
    const day_in_milliseconds = 86400000
    const format = new Intl.DateTimeFormat(localeName, { weekday }).format;
    return [...Array(7).keys()]
    .map((day) => format(new Date().getTime() - ( now.getDay() - day  ) * day_in_milliseconds  ));
  },
  will_have_daylight_savings( timezone, start, end ){
    let ref = null
    while ( start < end ) {
      let tzDate = this.timestamp_to_time( start, timezone )
      if ( ref !== null && tzDate !== ref ){
        return true
      }
      ref = tzDate
      start += day_in_seconds
    }
    return false
  },
  escapeObject(obj) {
    return Object.fromEntries(Object.entries(obj).map(([key, value]) => {
      return [ key, window.campaign_scripts.escapeHTML(value)]
    }))
  },
  escapeHTML(str) {
    if (typeof str === "undefined") return '';
    if (typeof str !== "string") return str;
    return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&apos;");
  },
}

//based off of:
//https://css-tricks.com/building-progress-ring-quickly/
class ProgressRing extends HTMLElement {
  constructor() {
    super();
    const stroke = this.getAttribute('stroke');
    this._stroke = stroke;
    const radius = this.getAttribute('radius');
    const text = this.getAttribute('text');
    const text2 = this.getAttribute('text2');
    const progress = this.getAttribute('progress');
    this._progress2 = this.getAttribute('progress2');
    const font_size = this.getAttribute('font') || 15;
    const normalizedRadius = radius - stroke;
    this._circumference = normalizedRadius * 2 * Math.PI;

    let normalizedRadius2 = parseInt(radius) - stroke/2 + 1
    this._circumference2 = normalizedRadius2 * 2 * Math.PI;

    let text_html = ``;
    if ( text2 ){
      text_html = `<text x="50%" y="50%" text-anchor="middle" stroke-width="2px" font-size="${font_size}px">
          <tspan x="50%" dy="0">${window.lodash.escape(text || progress + '%')}</tspan>
          <tspan x="50%" dy="0.5cm">${window.lodash.escape(text2)}</tspan>
      </text>`
    } else {
      text_html =  `<text x="50%" y="50%" text-anchor="middle" stroke-width="2px" font-size="${font_size}px" dy=".3em">
        ${window.lodash.escape(text || progress + '%')}
      </text>
      `
    }
    this._root = this.attachShadow({mode: 'open'});

    let base_color = 'dodgerblue'
    if ( window.dt_campaign_core && window.dt_campaign_core.color ){
      base_color = window.dt_campaign_core.color
    }

    let color = parseInt( progress ) >= 100 ? 'mediumseagreen' : base_color
    this._root.innerHTML = `
      <svg height="${radius * 2}"
           width="${radius * 2}" >
           <circle
             class="first-circle"
             stroke="${color}"
             stroke-dasharray="${this._circumference} ${this._circumference}"
             style="stroke-dashoffset:${this._circumference}"
             stroke-width="${stroke}"
             fill="transparent"
             r="${normalizedRadius}"
             cx="${radius}"
             cy="${radius}"
          />
          <circle
             class="third-circle"
             stroke="red"
             stroke-dasharray="${this._circumference2} ${this._circumference2}"
             style="stroke-dashoffset:${this._circumference2}"
             stroke-width="4px"
             fill="transparent"
             r="${normalizedRadius2}"
             cx="${radius}"
             cy="${radius}"
          />
          <circle
             class="second-circle"
             stroke="#e2e2e2"
             stroke-dasharray="${this._circumference} ${this._circumference}"
             style="stroke-dashoffset:${-this._circumference}"
             stroke-width="${stroke}"
             fill="transparent"
             r="${normalizedRadius}"
             cx="${radius}"
             cy="${radius}"
          />
          <text class="inner-text" x="50%" y="50%" text-anchor="middle" stroke-width="2px" font-size="15px" dy=".3em">${text_html}</text>
      </svg>

      <style>
          circle {
            transition: stroke-dashoffset 0.35s;
            transform: rotate(-90deg);
            transform-origin: 50% 50%;
          }
      </style>
    `;
  }

  setProgress(percent) {
    const offset = this._circumference - (percent / 100 * this._circumference);
    const circle = this._root.querySelector('circle.first-circle');
    circle.style.strokeDashoffset = offset;
    const circle2 = this._root.querySelector('circle.second-circle');
    circle2.style.strokeDashoffset = -(percent / 100 * this._circumference);
    if ( this._progress2 ){
      const offset3 = this._circumference2 - (this._progress2 / 100 * ( this._circumference2 ) );
      const circle3 = this._root.querySelector('circle.third-circle');
      circle3.style.strokeDashoffset = offset3
    }
  }
  setText(text) {
    const textElement = this._root.querySelector('.inner-text');
    textElement.innerHTML = text;
  }

  static get observedAttributes() {
    return ['progress', 'text'];
  }

  attributeChangedCallback(name, oldValue, newValue) {
    if (name === 'progress') {
      this.setProgress(newValue);
    }
    if (name === 'text') {
      this.setText(newValue);
    }
  }
}
window.customElements.define('progress-ring', ProgressRing);








jQuery(document).ready(function($) {
  let jsObject = window.campaign_objects



  $('#cp-confirm-email').on('click', function (e) {
    e.preventDefault()

    if (selected_times.length===0) {
      $('#cp-no-selected-times').show().fadeOut(5000)
      return;
    }

    let submit_spinner = $('.cp-submit-form-spinner');
    let submit_button = jQuery('#cp-confirm-email')
    submit_button.prop('disabled', true)
    submit_spinner.show()


    let honey = jQuery('#email').val()
    if (honey) {
      window.spinner.hide()
      return;
    }

    let name_input = jQuery('#name')
    let name = name_input.val()
    if (!name) {
      jQuery('#name-error').show()
      submit_spinner.hide()
      name_input.focus(function () {
        jQuery('#name-error').hide()
      })
      submit_button.prop('disabled', false)
      return;
    }

    let email_input = jQuery('#e2')
    let email = email_input.val()
    if (!email || !String(email).match(/^\S+@\S+\.\S+$/)) {
      jQuery('#email-error').show()
      submit_spinner.hide()
      email_input.focus(function () {
        jQuery('#email-error').hide()
      })
      submit_button.prop('disabled', false)
      return;
    }

    let data = {
      email: email,
      parts: jsObject.parts,
      campaign_id: calendar_subscribe_object.campaign_id,
      url: 'verify',
    }

    let link = jsObject.root + jsObject.parts.root + '/v1/' + jsObject.parts.type + '/verify';
    if (window.campaign_objects.remote) {
      link = jsObject.root + jsObject.parts.root + '/v1/24hour-router';
    }
    jQuery.ajax({
      type: "POST",
      data: JSON.stringify(data),
      contentType: "application/json; charset=utf-8",
      dataType: "json",
      url: link
    })
    .done(function () {
      submit_spinner.hide()
      $('.cp-view').hide()
      let view_to_open = 'cp-view-validate'
      $(`#${view_to_open}`).show()
      $('#cp-sent-email').html(email);
      submit_button.prop('disabled', false)
    })
    .fail(function (e) {
      console.log(e);
      submit_button.prop('disabled', false)
      let message = `So sorry. Something went wrong. Please, contact us to help you through it, or just try again.<br>
        <a href="${window.lodash.escape(window.location.href)}">Try Again</a>`
      $('#cp-validate-error').empty().html(`<div class="cell center">
        ${message}
    </div>`).show()
      submit_spinner.hide()
    })

  })

  //submit form
  $('#cp-submit-form').on('click', async function (e) {
    e.preventDefault()

    if ( selected_times.length === 0 ) {
      $('#cp-no-selected-times').show().fadeOut(5000)
      return;
    }

    let submit_spinner = $('.cp-submit-form-spinner');
    let submit_button = jQuery('#cp-submit-form')
    submit_button.prop('disabled', true)
    submit_spinner.show()


    let name_input = jQuery('#name')
    let name = name_input.val()
    let email_input = jQuery('#e2')
    let email = email_input.val()

    let confirmation_input = jQuery('#cp-confirmation-code')
    let confirmation_code = confirmation_input.val()
    if ( !confirmation_code ) {
      jQuery('#confirmation-error').show()
      submit_spinner.hide()
      confirmation_input.focus(function(){
        jQuery('#confirmation-error').hide()
      })
      submit_button.prop('disabled', false)
    }

    let data = {
      name: name,
      email: email,
      selected_times: selected_times,
      campaign_id: calendar_subscribe_object.campaign_id,
      timezone: current_time_zone,
      p4m_news: document.querySelector('#receive_pray4movement_news').checked,
      parts: jsObject.parts,
      code: confirmation_code
    }
    send_submission(data, submit_spinner)
  })

  let send_submission = (data, submit_spinner)=>{
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
      if ( jsObject.remote === "1" ){
        $('.cp-view').hide()
        $(`#cp-success-confirmation-section`).show()
      } else {
        window.location.href = jsObject.home + '/prayer/email-confirmation';
      }
    })
    .fail(function(e) {
      $('#cp-submit-form').prop('disabled', false)
      let message = `So sorry. Something went wrong. Please, contact us to help you through it, or just try again.<br>
          <a href="${window.lodash.escape(window.location.href)}">Try Again</a>`
      if ( e.status === 401 ) {
        message = 'Confirmation code does not match or is expired. Please, try again.'
      }
      $('#cp-form-error').empty().html(`<div class="cell center">
          ${message}
        </div>`).show()
      submit_spinner.hide()
    })
  }

  //when click ok after submit
  $('.cp-ok-done-button').on('click', function () {
    window.location.reload()
  })
})