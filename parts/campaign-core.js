const day_in_seconds = 86400
let campaign_data_promise = null;
let default_timezone = Intl.DateTimeFormat().resolvedOptions().timeZone || 'America/Chicago'

const escapeHTML = function (str) {
  if (typeof str === "undefined") return '';
  if (typeof str !== "string") return str;
  return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&apos;");
}
const escapeObject = function (obj) {
  return Object.fromEntries(Object.entries(obj).map(([key, value]) => {
    return [ key, escapeHTML(value)]
  }))
}
const strings = escapeObject(window.campaign_objects.translations)


window.campaign_user_data = {
  timezone: default_timezone, //@todo make default
  recurring_signups: [],
}
window.set_user_data = function (data, campaign = false){
  let timezone_changes = false
  if ( data.timezone !== window.campaign_user_data.timezone ){
    timezone_changes = true
  }
  window.campaign_user_data = {...window.campaign_user_data, ...data}
  if ( timezone_changes ){
    if ( !campaign ){
      window.campaign_scripts.days = window.campaign_scripts.calculate_day_times( window.campaign_user_data.timezone, window.campaign_data.start_timestamp, window.campaign_data.end_timestamp, window.campaign_data.current_commitments, window.campaign_data.slot_length )
    }
    //event
    let event = new CustomEvent('campaign_timezone_change', {detail: {timezone:window.campaign_user_data.timezone, days_already_calculated: campaign}});
    window.dispatchEvent(event);
  }
}


window.campaign_data = {
  campaign_id: null,
  start_timestamp: 0,
  end_timestamp: null,
  slot_length: 15,
  duration_options: [
    {value: 15, label: `${strings['%s Minutes'].replace('%s', 15)}`},
    {value: 30, label: `${strings['%s Minutes'].replace('%s', 30)}`},
    {value: 60, label: `${strings['%s Hours'].replace('%s', 1)}`},
  ],
  coverage: {},
  enabled_frequencies: [],
  frequency_options: [
    {
      value: 'daily',
      label: strings['Daily'],
      // desc: `(${strings['up to %s months'].replace('%s', '3')})`,
      days_limit: 90,
      month_limit: 3,
      step: 'day',
    },
    {
      value: 'weekly',
      label: strings['Weekly'],
      // desc: `(${strings['up to %s months'].replace('%s', '6')})`,
      days_limit: 180,
      step: 'week',
      month_limit: 6
    },
    {
      value: 'monthly',
      label: strings['Monthly'],
      // desc: `(${strings['up to %s months'].replace('%s', '12')})`,
      days_limit: 365,
      step: 'month',
      month_limit: 12
    },
    {
      value: 'pick',
      label: strings['Pick Dates and Times']
    },
  ],

  current_commitments: {},
  minutes_committed: 0,
  time_committed: ''
}

window.campaign_scripts = {
  time_slot_coverage: {},
  processing_save: {},
  days: [],
  calculate_day_times: function (custom_timezone=null, start, end, current_commitments, slot_length){
    //set up array of days and time slots according to timezone
    window.campaign_scripts.processing_save = {}
    window.campaign_scripts.time_slot_coverage = {}
    window.campaign_scripts.time_label_counts = {}
    window.campaign_scripts.missing_slots = {}
    let days = [];
    let now = parseInt( new Date().getTime() / 1000 );
    if (!end){
      end = Math.max(now, start) + 365 * day_in_seconds;
    }

    if ( !custom_timezone ){
      custom_timezone = Intl.DateTimeFormat().resolvedOptions().timeZone || 'America/Chicago'
    }

    let start_of_day = window.luxon.DateTime.fromSeconds( start, {zone: custom_timezone}).startOf('day').toSeconds()
    let next_day = window.luxon.DateTime.fromSeconds( start, {zone: custom_timezone}).startOf('day')
    let time_iterator = parseInt( start_of_day );
    let timezone_change_ref = window.luxon.DateTime.fromSeconds( time_iterator, {zone: custom_timezone}).toFormat('h:mm a')

    while ( time_iterator < end ){

      if ( !days.length || time_iterator >= next_day.toSeconds() ){
        next_day = next_day.plus({days:1})
        let timezone_date = window.luxon.DateTime.fromSeconds( time_iterator + day_in_seconds, {zone: custom_timezone}).toFormat('h:mm a')
        if ( timezone_change_ref !== null && timezone_date !== timezone_change_ref ){
          // Timezone change detected. Recalculating time slots.
          window.campaign_scripts.processing_save = {}
        }
        timezone_change_ref = window.luxon.DateTime.fromSeconds( time_iterator, {zone: custom_timezone}).toFormat('h:mm a')

        start_of_day = ( time_iterator >= start_of_day + day_in_seconds ) ? time_iterator : start_of_day
        let date_time = window.luxon.DateTime.fromSeconds(start_of_day, {zone: custom_timezone});

        days.push({
          date_time: date_time,
          "key": start_of_day,
          'day_start_zoned': date_time.startOf('day').toSeconds(),
          "formatted": date_time.toFormat('MMMM d'),
          "month": date_time.toFormat('y_MM'),
          "day": date_time.toFormat('d'),
          "percent": 0,
          "slots": [],
          "covered_slots": 0,
          "weekday_number": date_time.toFormat('c'),
        })
      }

      //calculate time slot
      let mod_time = time_iterator % day_in_seconds
      let time_formatted = '';
      if ( window.campaign_scripts.processing_save[mod_time] ){
        time_formatted = window.campaign_scripts.processing_save[mod_time]
      } else {
        time_formatted = window.luxon.DateTime.fromSeconds(time_iterator, {zone: custom_timezone}).toFormat('hh:mm a')
        window.campaign_scripts.processing_save[mod_time] = time_formatted
      }
      if ( time_iterator >= start && time_iterator < end ) {
        days[days.length - 1]["slots"].push({
          "key": time_iterator,
          "formatted": time_formatted,
          "subscribers": parseInt(current_commitments?.[time_iterator] || 0)
        })


        if ( time_iterator > now ){
          if (!window.campaign_scripts.time_label_counts[time_formatted]) {
            window.campaign_scripts.time_label_counts[time_formatted] = 0
          }
          window.campaign_scripts.time_label_counts[time_formatted] += 1

          if (current_commitments[time_iterator]) {
            days[days.length - 1].covered_slots += 1;

            if (!window.campaign_scripts.time_slot_coverage[time_formatted]) {
              window.campaign_scripts.time_slot_coverage[time_formatted] = [];
            }
            window.campaign_scripts.time_slot_coverage[time_formatted].push(current_commitments[time_iterator]);
          } else {
            if (time_iterator >= now) {
              if (!window.campaign_scripts.missing_slots[time_formatted]) {
                window.campaign_scripts.missing_slots[time_formatted] = []
              }
              window.campaign_scripts.missing_slots[time_formatted].push(time_iterator)
            }
          }
        }
      }
      time_iterator += slot_length * 60;
    }
    days.forEach(d=>{
      d.percent = d.covered_slots / d.slots.length * 100
    })
    window.campaign_scripts.processing_save = {}


    //days ready event
    let event = new CustomEvent('campaign_days_ready', {detail: days});
    window.dispatchEvent(event);
    return days;
  },
  get_campaign_data: function( timezone ){

    let campaign_id = window.subscription_page_data?.campaign_id || window.campaign_objects.magic_link_parts.post_id;

    if ( campaign_data_promise === null ){
      let link = window.campaign_objects.rest_url + window.campaign_objects.magic_link_parts.root + '/v1/' + window.campaign_objects.magic_link_parts.type + '/campaign_info';
      campaign_data_promise = jQuery.ajax({
        type: 'GET',
        data: {action: 'get', parts: window.campaign_objects.magic_link_parts, 'url': 'campaign_info', time: new Date().getTime(), campaign_id},
        contentType: 'application/json; charset=utf-8',
        dataType: 'json',
        url: link
      })
      campaign_data_promise.then((data)=>{
        window.campaign_data = { ...window.campaign_data, ...data }
        window.campaign_data.frequency_options.forEach(k=>{
          if ( !window.campaign_data.enabled_frequencies.includes(k.value) ){
            k.disabled = true
          }
        })
        timezone = timezone || data.subscriber_info?.timezone || window.campaign_user_data.timezone
        this.days = window.campaign_scripts.calculate_day_times( timezone, data.start_timestamp, data.end_timestamp, data.current_commitments, data.slot_length )
        if ( data.subscriber_info ){
          window.set_user_data(data.subscriber_info, true)
        }
      })
    }
    return campaign_data_promise
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

  ts_to_format: ( timestamp, format = 'y', timezone )=>{
    let options = {}
    if ( timezone ){
      options.zone = timezone
    }
    return window.luxon.DateTime.fromSeconds( timestamp, options ).toFormat( format )
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
    let div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  },
  recurring_time_slot_label(value){
    let first = window.luxon.DateTime.fromSeconds(value.first, {zone:window.campaign_user_data.timezone})
    let time_label = first.toLocaleString({ hour: 'numeric', minute: 'numeric', hour12: true });
    const frequency_option = window.campaign_data.frequency_options.find(k=>k.value===value.type)
    let freq_label = frequency_option.label
    const duration_label = window.campaign_data.duration_options.find(k=>k.value===parseInt(value.duration)).label;
    if ( frequency_option.value === 'weekly' ){
      freq_label = strings['Every %s'].replace('%s', first.toFormat('cccc') );
    }
    let label = strings['%1$s at %2$s for %3$s'].replace('%1$s', freq_label).replace('%2$s', time_label).replace('%3$s', duration_label)
    return label;
  },
  build_calendar_days(month_date){
    const now = new Date().getTime()/1000
    const month_start = month_date.startOf('month').startOf('day');
    let month_days = []
    let this_month_days = window.campaign_scripts.days.filter(k=>k.month===month_date.toFormat('y_MM'));
    for ( let i = 0; i < month_date.daysInMonth; i++ ){
      let day_date = month_start.plus({days:i})
      let day = this_month_days.find(d=>d.key === day_date.toSeconds())
      let next_day = day_date.plus({days:1}).toSeconds()
      if ( !day ){
        day = {
          key:day_date.toSeconds(),
          percent: 0,
          day:i+1,
          formatted: day_date.toFormat('MMMM d'),
          slots: [],
        }
      }
      day.disabled = next_day < now || (window.campaign_data.end_timestamp && next_day > window.campaign_data.end_timestamp ) || next_day < window.campaign_data.start_timestamp;
      month_days.push(day)
    }
    return month_days
  },
  build_selected_times_for_recurring(selected_time, frequency, duration, weekday=null, from_date_ts=null){
    let selected_times = []
    let now = new Date().getTime()/1000
    let now_date = window.luxon.DateTime.fromSeconds(Math.max(now, window.campaign_scripts.days[0].key),{zone:window.campaign_user_data.timezone})
    let frequency_option = window.campaign_data.frequency_options.find(k=>k.value===frequency)
    if ( frequency_option.value === 'weekly' ){
      now_date = now_date.set({weekday: parseInt(weekday)})
    }
    let start_of_day = now_date.startOf('day').toSeconds()
    if ( from_date_ts ){
      start_of_day = window.luxon.DateTime.fromSeconds(from_date_ts,{zone:window.campaign_user_data.timezone}).startOf('day').toSeconds()
    }
    let start_time = start_of_day + selected_time;
    let start_date = window.luxon.DateTime.fromSeconds(start_time, {zone:window.campaign_user_data.timezone})

    if ( window.campaign_user_data.recurring_signups.find(k=>k.root===start_time) ){
      return null;
    }

    let limit = window.campaign_data.end_timestamp
    if ( !limit ){
      limit = start_date.plus({days: frequency_option.days_limit}).toSeconds();
    }

    let date_ref = start_date
    while ( date_ref.toSeconds() <= limit ){
      let time = date_ref.toSeconds();
      let time_label = date_ref.toFormat('hh:mm a');
      let already_added = selected_times.find(k=>k.time===time)
      if ( !already_added && time > now && time >= window.campaign_data.start_timestamp ) {
        selected_times.push({time: time, duration: duration, label: time_label, day_key:date_ref.startOf('day'), date_time:date_ref})
      }
      date_ref = date_ref.plus({[frequency_option.step]:1})
    }
    let label = window.campaign_scripts.recurring_time_slot_label({first:start_time, type: frequency_option.value, duration: duration})

    return {
      root: start_time,
      label: label,
      type: frequency_option.value,
      first: selected_times[0].date_time,
      last: selected_times[selected_times.length-1].date_time,
      time: selected_time,
      time_label: selected_times[0].label,
      count: selected_times.length,
      duration: duration,
      week_day: weekday,
      selected_times,
    }
  },

  submit_prayer_times: function (campaign_id, data, action = 'add' ){
    data.action = action
    data.parts = window.campaign_objects.magic_link_parts
    data.campaign_id = campaign_id
    data.timezone = window.campaign_user_data.timezone

    let link = window.campaign_objects.rest_url + window.campaign_objects.magic_link_parts.root + '/v1/' + window.campaign_objects.magic_link_parts.type;
    if ( window.campaign_objects.remote ) {
      link = window.campaign_objects.rest_url + window.campaign_objects.magic_link_parts.root + '/v1/24hour-router';
    }
    return jQuery.ajax({
      type: "POST",
      data: JSON.stringify(data),
      contentType: "application/json; charset=utf-8",
      dataType: "json",
      url: link
    })
  },

  get_empty_times(){
    let day_in_seconds = 86400;
    let key = 0;
    let start_of_today = new Date('2023-01-01')
    start_of_today.setHours(0, 0, 0, 0)
    let start_time_stamp = start_of_today.getTime() / 1000

    let options = [];
    while (key < day_in_seconds) {
      let time = window.luxon.DateTime.fromSeconds(start_time_stamp + key, {zone:window.campaign_user_data.timezone})
      let time_formatted = time.toFormat('hh:mm a')
      let progress = 0
      let min = time.toFormat(':mm')
      options.push({key: key, time_formatted: time_formatted, minute: min, hour: time.toFormat('hh a'), progress})
      key += window.campaign_data.slot_length * 60
    }
    return options;
  }
}

/**
 * EDIT FUNCTIONALITY
 */

jQuery(document).ready(function ($) {

    init_edit_bootstrap_modal();
    function init_edit_bootstrap_modal() {
        let current_lang = null;
        let lang_select = $('.dt-magic-link-language-selector');
        if ($(lang_select).length > 0) {
            current_lang = $(lang_select).find('option:selected').text().trim();
        }

        let content = `
        <input id="edit_modal_field_key" type="hidden"/>
        <input id="edit_modal_section_id" type="hidden"/>
        <input id="edit_modal_split_text" type="hidden"/>

        <div id="edit_modal" class="modal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">${escapeHTML(strings['modals']['edit']['modal_title'])}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <table>
                            <tbody>
                                <tr style="background-color: #ffffff;">
                                    <td style="vertical-align: top;  width: 30%;">${escapeHTML(strings['modals']['edit']['edit_original_string'])}</td>
                                    <td id="edit_modal_original_string" style="font-size: 12px; color: #3c3c3c;"></td>
                                </tr>
                                <tr style="background-color: #ffffff;">
                                    <td style="vertical-align: top;  width: 30%;">${escapeHTML(strings['modals']['edit']['edit_all_languages'])}</td>
                                    <td>
                                        <textarea id="edit_modal_all_languages" rows="5" style="min-width: 100%;"></textarea>
                                    </td>
                                </tr>
                                <tr style="background-color: #ffffff;">
                                    <td style="vertical-align: top; width: 30%;">${escapeHTML(strings['modals']['edit']['edit_selected_language'])} ${((current_lang !== null) ? ' - ['+ current_lang +']' : '' )}</td>
                                    <td>
                                        <textarea id="edit_modal_selected_language" rows="5" style="min-width: 100%;"></textarea>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-common edit-close-btn">${escapeHTML(strings['modals']['edit']['edit_btn_close'])}</button>
                        <button class="btn btn-common edit-update-btn">${escapeHTML(strings['modals']['edit']['edit_btn_update'])}</button>
                    </div>
                </div>
            </div>
        </div>`;
        $('#edit_modal_div').empty().html(content);
    }

    $(document).on('click', '.edit-btn', function (e) {

        // Set translation field values and display modal.
        let field_key = $(e.currentTarget).data('field_key');
        let section_id = $(e.currentTarget).data('section_id');
        let split_text = $(e.currentTarget).data('split_text');

        let lang_default = $('#' + section_id + '_lang_default').val().trim();
        let lang_all = $('#' + section_id + '_lang_all').val().trim();
        let lang_selected = $('#' + section_id + '_lang_selected').val().trim();

        // Capture hidden values to be applied further down stream.
        $('#edit_modal_field_key').val(field_key);
        $('#edit_modal_section_id').val(section_id);
        $('#edit_modal_split_text').val(split_text);

        // Obtain element handles and set modal display values.
        let edit_modal_original_string = $('#edit_modal_original_string');
        let edit_modal_all_languages = $('#edit_modal_all_languages');
        let edit_modal_selected_language = $('#edit_modal_selected_language');

        $(edit_modal_original_string).text( escapeHTML( lang_default ) );
        $(edit_modal_all_languages).val( escapeHTML( lang_all ) );
        $(edit_modal_selected_language).val( escapeHTML( lang_selected ) );

        // Display modal.
        $('#edit_modal').modal('show');
    });

    $(document).on('click', '.edit-close-btn', function (e) {
        $('#edit_modal').modal('hide');
    });

    $(document).on('click', '.edit-update-btn', function (e) {
        let field_key = $('#edit_modal_field_key').val();
        let section_id = $('#edit_modal_section_id').val();
        let split_text = $('#edit_modal_split_text').val();
        let lang_default = $('#edit_modal_original_string').text();
        let lang_all = $('#edit_modal_all_languages').val();
        let lang_selected = $('#edit_modal_selected_language').val();
        let lang_code = $('.dt-magic-link-language-selector').val();
        let campaign_id = window.subscription_page_data?.campaign_id || window.campaign_objects.magic_link_parts.post_id;

        // Capture nested edit button html, to be re-assigned following update.
        let edit_btn = $('#' + section_id).find('button.edit-btn');

        // Dispatch edit update request.
        let link = window.campaign_objects.rest_url + window.campaign_objects.magic_link_parts.root + '/v1/' + window.campaign_objects.magic_link_parts.type + '/campaign_edit';
        let payload = {
          'action': 'post',
          'parts': window.campaign_objects.magic_link_parts,
          'url': 'campaign_edit',
          'time': new Date().getTime(),
          campaign_id,
          'edit': {
            'field_key': field_key,
            'lang_all': lang_all,
            'lang_translate': lang_selected,
            'lang_code': lang_code
          }
        };

        jQuery.ajax({
            type: 'POST',
            data: JSON.stringify(payload),
            contentType: 'application/json; charset=utf-8',
            dataType: 'json',
            url: link,
          beforeSend: (xhr) => {
            xhr.setRequestHeader("X-WP-Nonce", window.campaign_objects.nonce);
          },
        })
        .promise()
        .then((response) => {
            if ( response && response['updated'] ) {
              if ( response['lang_all'] !== undefined ) {
                $('#' + section_id + '_lang_all').val( response['lang_all'] );
              }

              if ( response['lang_translate'] !== undefined ) {
                $('#' + section_id + '_lang_selected').val( response['lang_translate'] );
              }

              if ( response['section_lang'] !== undefined ) {
                let section_lang = response['section_lang'];

                // If split text, then ensure styling is maintained.
                if ( split_text === 'true' ) {
                  let parts = section_lang.split(/\s+/);
                  if ( parts.length > 0 ) {
                    let arr = [parts.shift(), parts.join(' ')];
                    $('#' + section_id).fadeOut('fast', function () {
                      $(this).html(`${arr[0]} <span>${arr[1]}</span>`).fadeIn('fast', function () {
                        if ( edit_btn ) {
                          $(this).append( edit_btn );
                        }
                      });
                    });
                  } else {
                    $('#' + section_id).fadeOut('fast', function () {
                      $(this).text(section_lang).fadeIn('fast', function () {
                          if ( edit_btn ) {
                              $(this).append( edit_btn );
                          }
                      });
                    });
                  }
                } else {
                  $('#' + section_id).fadeOut('fast', function () {
                    $(this).text(section_lang).fadeIn('fast', function () {
                        if ( edit_btn ) {
                            $(this).append( edit_btn );
                        }
                    });
                  });
                }
              }
            }

            $('#edit_modal').modal('hide');
        });
    });
});

/**
 * EDIT FUNCTIONALITY
 */

