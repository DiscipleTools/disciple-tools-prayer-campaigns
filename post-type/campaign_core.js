window.campaign_scripts = {
  time_slot_coverage: {},
  processing_save: {},
  calculate_day_times: function (custom_timezone=null){
    //set up array of days and time slots according to timezone
    window.campaign_scripts.processing_save = {}
    let days = [];
    let time_iterator = calendar_subscribe_object.start_timestamp;

    let start_of_day = window.campaign_scripts.day_start( time_iterator, current_time_zone)

    while ( time_iterator < calendar_subscribe_object.end_timestamp ){

      if ( !days.length || time_iterator >= ( start_of_day+24*3600 ) ){
        start_of_day = ( time_iterator >= start_of_day+24*3600 ) ? time_iterator : start_of_day
        let day = window.campaign_scripts.timestamp_to_month_day( time_iterator, custom_timezone )
        let weekday = new Date(start_of_day * 1000).getDay()

        days.push({
          "key": start_of_day,
          "formatted": day,
          "month": window.campaign_scripts.timestamp_to_format( time_iterator, { month:"long" }, custom_timezone),
          "day": window.campaign_scripts.timestamp_to_format( time_iterator, { day:"numeric" }, custom_timezone),
          "weekday": weekday,
          "percent": 0,
          "slots": [],
          "covered_slots": 0,
        })
      }
      let mod_time = time_iterator % (24 * 60 * 60)
      let time_formatted = '';
      if ( window.campaign_scripts.processing_save[mod_time] ){
        time_formatted = window.campaign_scripts.processing_save[mod_time]
      } else {
        time_formatted = window.campaign_scripts.timestamp_to_time(time_iterator, custom_timezone)
        window.campaign_scripts.processing_save[mod_time] = time_formatted
      }
      days[days.length-1]["slots"].push({
        "key": time_iterator,
        "formatted": time_formatted,
        "subscribers": parseInt(calendar_subscribe_object.current_commitments[time_iterator] || 0)
      })

      if ( calendar_subscribe_object.current_commitments[time_iterator] ){
        days[days.length-1].covered_slots += 1;

        if ( !window.campaign_scripts.time_slot_coverage[time_formatted]){
          window.campaign_scripts.time_slot_coverage[time_formatted] = 0;
        }
        window.campaign_scripts.time_slot_coverage[time_formatted]++;
      }
      time_iterator += calendar_subscribe_object.slot_length * 60;
    }
    days.forEach(d=>{
      d.percent = d.covered_slots / d.slots.length * 100
    })

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
  timestamps_to_summary: function( timestamp_start, timestamp_end ) {
    const options = { hour: "numeric", minute: "numeric" };
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
  get_time_select_html: () => {
    let select_html = `<option value="false">Select a time</option>`

    let key = 0;
    let start_of_today = new Date()
    start_of_today.setHours(0,0,0,0)
    let start_time_stamp = start_of_today.getTime()/1000
    while ( key < 24 * 3600 ){
      let time_formatted = window.campaign_scripts.timestamp_to_time(start_time_stamp+key)

      let covered = window.campaign_scripts.time_slot_coverage[time_formatted] ? window.campaign_scripts.time_slot_coverage[time_formatted] === number_of_days : false;

      select_html += `<option value="${window.lodash.escape(key)}">
                        ${window.lodash.escape(time_formatted)} ${ covered ? "(fully covered)": '' }
                    </option>`
      key += calendar_subscribe_object.slot_length * 60
    }
    return select_html

  }

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

    let color = parseInt( progress ) >= 100 ? 'mediumseagreen' : 'dodgerblue'
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
             stroke="#eee"
             stroke-dasharray="${this._circumference} ${this._circumference}"
             style="stroke-dashoffset:${-this._circumference}"
             stroke-width="${stroke}"
             fill="transparent"
             r="${normalizedRadius}"
             cx="${radius}"
             cy="${radius}"
          />
          ${text_html}
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

  static get observedAttributes() {
    return ['progress'];
  }

  attributeChangedCallback(name, oldValue, newValue) {
    if (name === 'progress') {
      this.setProgress(newValue);
    }
  }
}
