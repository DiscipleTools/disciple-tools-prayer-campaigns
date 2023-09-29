const day_in_seconds = 86400
let campaign_data_promise = null;
let default_timezone = Intl.DateTimeFormat().resolvedOptions().timeZone || 'America/Chicago'

window.campaign_scripts = {
  timezone: default_timezone,
  time_slot_coverage: {},
  processing_save: {},
  days: {},
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
    let time_iterator = parseInt( start_of_day );
    let timezone_change_ref = window.luxon.DateTime.fromSeconds( time_iterator, {zone: custom_timezone}).toFormat('h:mm a')

    while ( time_iterator < end ){

      if ( !days.length || time_iterator >= ( start_of_day + day_in_seconds ) ){
        let timezone_date = window.luxon.DateTime.fromSeconds( time_iterator + day_in_seconds, {zone: custom_timezone}).toFormat('h:mm a')
        if ( timezone_change_ref !== null && timezone_date !== timezone_change_ref ){
          // Timezone change detected. Recalculating time slots.
          window.campaign_scripts.processing_save = {}
        }
        timezone_change_ref = window.luxon.DateTime.fromSeconds( time_iterator, {zone: custom_timezone}).toFormat('h:mm a')
        let date_time = window.luxon.DateTime.fromSeconds(time_iterator, {zone: custom_timezone});

        start_of_day = ( time_iterator >= start_of_day + day_in_seconds ) ? time_iterator : start_of_day

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

    return days;
  },
  get_campaign_data: function( timezone ){
    if ( !timezone ){
      timezone = this.timezone
    }
    if ( window.jsObject?.campaign_data ){
      return new Promise((resolve, reject)=>{
        let data = window.jsObject.campaign_data
        this.days = window.campaign_scripts.calculate_day_times( timezone, data.start_timestamp, data.end_timestamp, data.current_commitments, data.slot_length )
        resolve(window.jsObject.campaign_data)
      })
    }

    if ( campaign_data_promise === null ){
      let link = window.campaign_objects.rest_url + window.campaign_objects.magic_link_parts.root + '/v1/' + window.campaign_objects.magic_link_parts.type + '/campaign_info';
      campaign_data_promise = jQuery.ajax({
        type: 'GET',
        data: {action: 'get', parts: window.campaign_objects.magic_link_parts, 'url': 'campaign_info', time: new Date().getTime()},
        contentType: 'application/json; charset=utf-8',
        dataType: 'json',
        url: link
      })
      campaign_data_promise.then((data)=>{
        this.days = window.campaign_scripts.calculate_day_times( timezone, data.start_timestamp, data.end_timestamp, data.current_commitments, data.slot_length )
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
    return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&apos;");
  },
}
