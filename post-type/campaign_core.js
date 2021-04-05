window.campaign_scripts = {
  time_slot_coverage: {},
  calculate_day_times: function (custom_timezone=null){
    //set up array of days and time slots according to timezone
    let days = [];
    let processing_save = {}
    let time_iterator = calendar_subscribe_object.start_timestamp;

    let start_of_day = window.campaign_scripts.day_start( time_iterator, current_time_zone)

    while ( time_iterator < calendar_subscribe_object.end_timestamp ){

      if ( !days.length || time_iterator >= ( start_of_day+24*3600 ) ){
        start_of_day = ( time_iterator >= start_of_day+24*3600 ) ? time_iterator : start_of_day
        let day = window.campaign_scripts.timestamp_to_month_day( time_iterator, custom_timezone )
        days.push({
          "key": time_iterator,
          "formatted": day,
          "month": window.campaign_scripts.timestamp_to_format( time_iterator, { month:"long" }, custom_timezone),
          "day": window.campaign_scripts.timestamp_to_format( time_iterator, { day:"numeric" }, custom_timezone),
          "percent": 0,
          "slots": [],
          "covered_slots": 0,
        })
      }
      let mod_time = time_iterator % (24 * 60 * 60)
      let time_formatted = '';
      if ( processing_save[mod_time] ){
        time_formatted = processing_save[mod_time]
      } else {
        time_formatted = window.campaign_scripts.timestamp_to_time(time_iterator, custom_timezone)
        processing_save[mod_time] = time_formatted
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
  /**
   * return the st of day timestamp of a particular timezone
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
  day_start_timestamp_utc:( timestamp ) => {
    let start_of_day = new Date(timestamp*1000)
    start_of_day.setHours(0,0,0,0)
    return start_of_day.getTime()/1000
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
                        ${window.lodash.escape(time_formatted)} ${ covered ? "(Already covered)": '' }
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
    const radius = this.getAttribute('radius');
    const text = this.getAttribute('text');
    const progress = this.getAttribute('progress');
    const font_size = this.getAttribute('font') || 15;
    const normalizedRadius = radius - stroke;
    this._circumference = normalizedRadius * 2 * Math.PI;

    this._root = this.attachShadow({mode: 'open'});
    this._root.innerHTML = `
      <svg height="${radius * 2}"
           width="${radius * 2}" >
           <circle
             class="first-circle"
             stroke="dodgerblue"
             stroke-dasharray="${this._circumference} ${this._circumference}"
             style="stroke-dashoffset:${this._circumference}"
             stroke-width="${stroke}"
             fill="transparent"
             r="${normalizedRadius}"
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
          <text x="50%" y="50%" text-anchor="middle" stroke-width="2px" font-size="${font_size}px" dy=".3em">
              ${text || progress + '%'}
          </text>
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
