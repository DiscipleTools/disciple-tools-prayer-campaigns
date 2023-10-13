import {html, css, LitElement, range, map, classMap, styleMap} from 'https://cdn.jsdelivr.net/gh/lit/dist@2/all/lit-all.min.js';
const strings = window.campaign_scripts.escapeObject(window.campaign_objects.translations)

export class cpTemplate extends LitElement {
  static styles = [
    css``
  ]

  static properties = {
    prop: {type: String},
  }

  constructor() {
    super();
  }

  render() {
    return html`
      <button>
        <slot></slot>
      </button>
    `

  }
}
customElements.define('cp-template', cpTemplate);

export class campaignButton extends LitElement {
  static styles = [
    css`
      button {
        color: #fefefe;
        font-size: 1rem;
        border-radius: 5px;
        border: 1px solid transparent;
        font-weight: normal;
        padding: .85rem 1rem;
        cursor:pointer;
        background-color: var( --cp-color, 'dodgerblue' );
      }
      button:hover {
        background-color: transparent;
        border-color: var( --cp-color, 'dodgerblue' );
        color: var( --cp-color, 'dodgerblue' );
      }
      button[disabled] {
        opacity: .25;
        cursor: not-allowed;
      }
      button.clear-button {
        background-color: transparent;
        padding:5px;
      }
    `
  ]
  constructor() {
    super();
  }

  render() {
    return html`
      <button>
        <slot></slot>
      </button>
    `

  }
}
customElements.define('cp-button', campaignButton);

/**
 * Timezone Picker Component
 * param timezone
 * @fires change, timezone
 */
export class TimeZonePicker extends LitElement {
  static styles = [
    css`
      button#change-timezone-button {
        background: none!important;
        border: none;
        padding: 0!important;
        color: #069;
        text-decoration: underline;
        cursor: pointer;
      }
    `,
    window.campaignStyles
  ];

  static properties = {
    timezone: {type: String},
  }

  constructor() {
    super()
  }

  timezone_changed(e){
    let value = e.target.value;
    //if valid
    if ( Intl.supportedValuesOf('timeZone').includes(value) ) {
      this.dispatchEvent(new CustomEvent('change', {detail: e.target.value}));
    } else {
      this.timezone = ''
      this.requestUpdate();
    }

  }
  show_picker(){
    this.timezone = '';
    this.requestUpdate()
  }
  render(){
    return html`
        <p>${strings['Detected time zone']}:
            <button id="change-timezone-button" ?hidden=${!this.timezone} @click=${this.show_picker}>${this.timezone}</button>
            <div ?hidden=${this.timezone.length} >
                <select @change=${this.timezone_changed}>
                    <option value="">${strings['Choose a timezone']}</option>
                    ${Intl.supportedValuesOf('timeZone').map(o=>{
                        return html`<option value="${o}">${o}</option>`
                    })}
                </select>
            </div>
        </p>
    `
  }
}
customElements.define('time-zone-picker', TimeZonePicker);

/**
 * Counter Row Component
 */
export class CounterRow extends LitElement {
  static styles = [
    window.campaignStyles,
    css`
      h2 {
        font-size: 3em;
        text-align: center;
        color: #fff;
        margin-bottom: 0.5rem;
      }
      h3 {
        font-size: 35px;
        color: #fff;
        margin-top: 0;
        margin-bottom: 15px;
      }
      h4 {
        font-size: 20px;
        color: #fff;
      }
    `
  ];

  static properties = {
    label: {type: String},
    title: {type: String},
    show: {type: Boolean},
    timezone: {type: String},
    end_time: {type: Number},
    start_time: {type: Number},
    starting_label: {type: String},
    ending_label: {type: String},
    is_finished: {type: String},
  }

  constructor() {
    super()
    this.starting_label = strings['Campaign Begins In'];
    this.ending_label = strings['Campaign Ends In'];
    this.is_finished = strings['Campaign Ended'];
  }

  connectedCallback() {
    super.connectedCallback();
    this.timezone = this.timezone || Intl.DateTimeFormat().resolvedOptions().timeZone;
    this.now = new Date().getTime() / 1000;

    if ( this.end_time && this.end_time < this.now ) {
      this.title = this.is_finished;
    } else if ( this.start_time && this.start_time > this.now ) {
      this.title = this.starting_label;
    } else if ( this.end_time && this.end_time > this.now ){
      this.title = this.ending_label;
    }

    if ( this.end_time && this.end_time < this.now || !this.end_time && this.start_time < this.now ){
      return
    }

    this.interval = window.setInterval(() => {
      let now = parseInt( new Date().getTime() / 1000)
      let timeLeft = ( now < this.start_time ? this.start_time : this.end_time ) - now;

      let days = Math.floor(timeLeft / (60 * 60 * 24));
      let hours = Math.floor((timeLeft % (60 * 60 * 24)) / (60 * 60));
      let minutes = Math.floor((timeLeft % (60 * 60)) / 60);
      let seconds = Math.floor(timeLeft % 60);

      this.label = `${days} ${strings.days}, ${hours} ${strings.hours}, ${minutes} ${strings.minutes}, ${seconds} ${strings.seconds}`;

      if ( timeLeft < 0 ) {
        this.title = this.is_finished;
        window.clearInterval(this.interval);
      }

    }, 1000);
  }

  disconnectedCallback() {
    super.disconnectedCallback();
    window.clearInterval(this.interval);
  }

  render(){
    return html`
        <h2>${this.title}</h2>
        <h3>${this.label}</h3>
    `
  }
}
customElements.define('counter-row', CounterRow);

/**
 * Contact Info Component
 */
export class ContactInfo extends LitElement {
  static styles = [
    css`
      #email {
        display:none;
      }
    
    `,
    campaignStyles, ];

  static properties = {
    _form_items: {state: true},
    _loading: {state:true},
    form_error: {state:true},
    last_date_label: {state:true},
  }

  constructor() {
    super();
    this._form_items = {
      email: '',
      name: '',
    }
  }

  _is_email(val){
    return String(val).match(/^\S+@\S+\.\S+$/)
  }

  handleInput(e){
    let val = e.target.value
    let name = e.target.name
    this._form_items[name] = val
    this.requestUpdate()
  }

  back(){
    this.dispatchEvent(new CustomEvent('back'));
  }

  verify_contact_info(){
    if ( this._form_items.EMAIL){
      return;
    }

    if ( !this._form_items.name || !this._is_email(this._form_items.email) ){
      this.form_error = strings['Please enter a valid name or email address']
      this.requestUpdate()
      return;
    }

    //bubble up form items
    this.dispatchEvent(new CustomEvent('form-items', { detail: this._form_items }));
  }

  render(){
    return html`
      <div>
          <label for="name">${strings['Name']}<br>
              <input class="cp-input" type="text" name="name" id="name" placeholder="${strings['Name']}" required @input=${this.handleInput} />
          </label>
      </div>
      <div>
          <label for="email">${strings['Email']}<br>
              <input class="cp-input" type="email" name="EMAIL" id="email" placeholder="${strings['Email']}" @input=${this.handleInput}/>
              <input class="cp-input" type="email" name="email" id="e2" placeholder="${strings['Email']}" @input=${this.handleInput} />
          </label>
      </div>
      <div>
          <div id='cp-no-selected-times' style='display: none' class="form-error" >
              ${strings['No prayer times selected']}
          </div>
      </div>

      <div id="cp-form-error" class="form-error" ?hidden=${!this.form_error}>
          ${this.form_error}
      </div>

      <div class="nav-buttons">
          <campaign-back-button @click=${this.back}></campaign-back-button>
          <button ?disabled=${!this._form_items.name || !this._is_email(this._form_items.email)}
                  @click=${()=>this.verify_contact_info()}>

              ${strings['Next']}
              <img ?hidden=${!this._loading} class="button-spinner" src="${window.campaign_objects.plugin_url}spinner.svg" width="22px" alt="spinner"/>
          </button>
      </div>
    `
  }
}
customElements.define('contact-info', ContactInfo);

/**
 * Group Select and Option Component
 */
export class select extends LitElement {
  static styles = [
    css`
      .select {
        background-color: transparent;
        border: 1px solid var(--cp-color);
        color: var(--cp-color);
        padding: 0.3rem;
        cursor: pointer;
        margin-bottom: 0.3rem;
        font-weight: bold;
        //font-size: 1rem;
        padding-inline-start: 1rem;
        border-radius: 5px;
        width: 100%;
        text-align: start;
        //line-height: ;
        
      }
      .select.selected {
        border: 1px solid #ccc;
        background-color: var(--cp-color);
        color: #fff;
        opacity: 0.9;
      }
      .select:hover {
        background-color: var(--cp-color);
        color: #fff;
        opacity: 0.5;
      }
      .select:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        
      }
    `
  ]

  static properties = {
    value: {type: String},
    options: {type: Array},
  }
  constructor() {
    super();
    this.options = [];
  }

  handleClick(value){
    if ( this.value != value ){
      this.value = value
      this.dispatchEvent(new CustomEvent('change', {detail: this.value}));
    }
  }

  render() {
    return html`
      ${this.options.filter(o=>!o.disabled).map(o=>html`
          <button class="select ${o.value.toString() === this.value?.toString() ? 'selected' : ''}"
                  ?disabled="${o.disabled}"
                  @click="${()=>this.handleClick(o.value)}"
            value="${o.value}">
                ${o.label}
              <span>${o.desc}</span>
          </button>`
      )}  
      
    `
  }
}
customElements.define('cp-select', select);

/**
 * Select Dates Calendar Component
 *
 * @property {String} start_timestamp - Start timestamp
 * @property {String} end_timestamp - End timestamp
 * @property {Array} days - Array of days to display
 * @property {Array} selected_times - Array of selected times
 * @property {Boolean} calendar_disabled - Disable calendar
 * @fires day-selected, timestamp of selected day
 *
 */
export class cpCalendarDaySelect extends LitElement {
  static styles = [
    css`
      :host {
        display: block;
      }
      .calendar {
        display: grid;
        grid-template-columns: repeat(7, 40px);
      }
      .day-cell {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 40px;
        width: 40px;
      }
      .day-cell:hover {
        background-color: #4676fa1a;
        cursor: pointer;
        border-radius: 50%;
      }
      .week-day {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 40px;
        width: 40px;
      }
      .selected-time {
        color: black;
        border-radius: 50%;
        border: 2px solid;
        background-color: #4676fa1a;
      }
      .selected-day {
        color: white;
        border-radius: 50%;
        border: 2px solid;
        background-color: var(--cp-color);
      }
      .month-title {
        display: flex;
        justify-content: space-between;
        max-width: 280px;
        font-size: 1.2rem;
      }
      .month-next {
        padding: 0.25rem 0.5rem;
      }
    `,
    window.campaignStyles
  ]

  static properties = {
    start_timestamp: {type: String},
    end_timestamp: {type: String},
    days: {type: Array},
    selected_times: {type: Array},
    calendar_disabled: {type: Boolean},
  }

  constructor() {
    super();
    this.month_to_show = null;
    this.calendar_disabled = false;
    this.start_timestamp = window.campaign_data.start_timestamp
    this.end_timestamp = window.campaign_data.end_timestamp
    this.days = window.campaign_scripts.days
    this.selected_times = []
  }

  connectedCallback(){
    super.connectedCallback();
    //get days from days ready event
    window.addEventListener('campaign_days_ready', e=>{
      this.days = e.detail
      this.requestUpdate()
    })
  }

  next_view(e){
    if ( this.calendar_disabled ){
      return;
    }
    this.month_to_show = e
    this.requestUpdate()
    //remove all selected-time css
    this.shadowRoot.querySelectorAll('.selected-time').forEach(e=>e.classList.remove('selected-time'))
  }

  day_selected(e, day){
    if ( this.calendar_disabled ){
      return;
    }
    //dispatch event
    this.dispatchEvent(new CustomEvent('day-selected', {detail: day}));
    //highlight selected day
    this.shadowRoot.querySelectorAll('.selected-time').forEach(e=>e.classList.remove('selected-time'))

    e.target.classList.add('selected-time');
  }


  render() {
    if ( this.days.length === 0 ){
      return html`<div></div>`
    }

    let selected_times = this.selected_times.map(t=>t.day_key);

    let week_day_names = window.campaign_scripts.get_days_of_the_week_initials(navigator.language, 'narrow')

    let now_date = window.luxon.DateTime.now()
    let now = now_date.toSeconds();
    let start_of_day = window.campaign_scripts.day_start_timestamp_utc(now)
    let days = this.days.filter(d=>d.key >= start_of_day) || [];
    let current_time_zone = this.current_time_zone
    let current_month = this.month_to_show || days[0].key
    let current_month_date = window.luxon.DateTime.fromSeconds(current_month, {zone:current_time_zone})
    let this_month_days = days.filter(k=>k.month===current_month_date.toFormat('y_MM'));

    let previous_month = window.luxon.DateTime.fromSeconds(current_month, {zone:current_time_zone}).minus({months:1}).toSeconds()
    let next_month = window.luxon.DateTime.fromSeconds(current_month, {zone:current_time_zone}).plus({months:1}).toSeconds()

    let day_number = window.campaign_scripts.get_day_number(current_month, current_time_zone);

    if ( !this.end_timestamp ){
      this.end_timestamp = this.days[this.days.length - 1].key
    }


    return html`
      
      <div class="calendar-wrapper">
        <h3 class="month-title center">
            <button class="month-next" ?disabled="${previous_month < now_date.minus({months:1}).toSeconds() }"
                    @click="${e=>this.next_view(previous_month)}">
                <
            </button>
            ${window.campaign_scripts.ts_to_format(current_month, 'MMMM y', current_time_zone)}
            <button class="month-next" ?disabled="${next_month > this.end_timestamp}" @click="${e=>this.next_view(next_month)}">
                >
            </button>
        </h3>
        <div class="calendar">
            ${week_day_names.map(name=>html`<div class="day-cell week-day">${name}</div>`)}
            ${map(range(day_number), i=>html`<div class="day-cell disabled-calendar-day"></div>`)}
            ${this_month_days.map(day=>{
                let disabled = (day.key + day_in_seconds) < now;
                return html`
                  <div class="day-cell ${selected_times.includes(day.day_start_zoned) ? 'selected-day':''}
                        ${disabled ? 'disabled-calendar-day':'day-in-select-calendar'}" 
                       data-day="${window.campaign_scripts.escapeHTML(day.key)}"
                       @click="${e=>this.day_selected(e, day.key)}"
                  >
                      ${window.campaign_scripts.escapeHTML(day.day)}
                  </div>`
            })}
            ${day_number ? map(range(7 - day_number), i=>html`<div class="day-cell disabled-calendar-day"></div>` ) : ''}
        </div>
      </div>
            
      `

  }
}
customElements.define('cp-calendar-day-select', cpCalendarDaySelect);

export class cpMyCalendar extends LitElement {
  static styles = [
    css`
      :host {
        display: block;
        --size: min(60px, calc((100vw - 2rem) / 7))
      }
      .calendar {
        display: grid;
        grid-template-columns: repeat(7, var(--size));
      }
      .day-cell {
        display: flex;
        align-items: center;
        justify-content: center;
        height: var(--size);
        width: var(--size);
        position: relative;
      }
      .day-cell.enabled-day:hover {
        background-color: #4676fa1a;
        cursor: pointer;
        border-radius: 50%;
      }
      .week-day {
        display: flex;
        align-items: center;
        justify-content: center;
        height: var(--size);
        width: var(--size);
        font-weight: bold;
      }
      .selected-time {
        //color: black;
        //border-radius: 50%;
        //border: 2px solid;
        //background-color: #4676fa1a;
      }
      .selected-day {
        color: white;
        border-radius: 50%;
        border: 2px solid;
        background-color: var(--cp-color);
      }
      .month-title {
        display: flex;
        justify-content: space-between;
        max-width: calc(var(--size) * 7);
        font-size: 1.2rem;
        align-items: center;
      }
      .month-next {
        //padding: 0.25rem 0.5rem;
      }
      .indicator-section {
        position: absolute;
        bottom: 17%;
        display: flex;
        gap:1px;
      }
      .prayer-time-indicator {
        width: 5px;
        height: 5px;
        background-color: #57d449;
        border-radius: 100px;
      }
      progress-ring {
        height: var(--size);
      }
    `,
    window.campaignStyles
  ]

  static properties = {
    start_timestamp: {type: String},
    end_timestamp: {type: String},
    days: {type: Array},
    selected_times: {type: Array},
    calendar_disabled: {type: Boolean},
  }

  constructor() {
    super();
    this.month_to_show = null;
    this.calendar_disabled = false;
    this.start_timestamp = window.campaign_data.start_timestamp
    this.end_timestamp = window.campaign_data.end_timestamp
    this.days = window.campaign_scripts.days
    this.selected_times = []
  }

  connectedCallback(){
    super.connectedCallback();
    //get days from days ready event
    window.addEventListener('campaign_days_ready', e=>{
      this.days = e.detail
      this.requestUpdate()
    })
  }

  next_view(e){
    if ( this.calendar_disabled ){
      return;
    }
    this.month_to_show = e
    this.requestUpdate()
    //remove all selected-time css
    this.shadowRoot.querySelectorAll('.selected-time').forEach(e=>e.classList.remove('selected-time'))
  }

  day_selected(e, day){
    if ( this.calendar_disabled ){
      return;
    }
    //dispatch event
    this.dispatchEvent(new CustomEvent('day-selected', {detail: day}));
    //highlight selected day
    this.shadowRoot.querySelectorAll('.selected-time').forEach(e=>e.classList.remove('selected-time'))

    e.target.classList.add('selected-time');
  }


  render() {
    if ( this.days.length === 0 ){
      return html`<div></div>`
    }

    let selected_times = this.selected_times.map(t=>t.day_key);

    let week_day_names = window.campaign_scripts.get_days_of_the_week_initials(navigator.language, 'narrow')

    let now_date = window.luxon.DateTime.now()
    let now = now_date.toSeconds();
    let start_of_day = window.campaign_scripts.day_start_timestamp_utc(now)
    let days = this.days.filter(d=>d.key >= start_of_day) || [];
    let current_time_zone = this.current_time_zone
    let current_month = this.month_to_show || days[0].key
    let current_month_date = window.luxon.DateTime.fromSeconds(current_month, {zone:current_time_zone})
    let this_month_days = days.filter(k=>k.month===current_month_date.toFormat('y_MM'));

    let previous_month = window.luxon.DateTime.fromSeconds(current_month, {zone:current_time_zone}).minus({months:1}).toSeconds()
    let next_month = window.luxon.DateTime.fromSeconds(current_month, {zone:current_time_zone}).plus({months:1}).toSeconds()

    let day_number = window.campaign_scripts.get_day_number(current_month, current_time_zone);

    if ( !this.end_timestamp ){
      this.end_timestamp = this.days[this.days.length - 1].key
    }

    let my_commitments = {};

    (window.campaign_data.subscriber_info?.my_commitments || []).filter(c=>c.time_begin >= start_of_day && c.time_begin <= next_month).forEach(c=>{
      let formatted = window.luxon.DateTime.fromSeconds(parseInt(c.time_begin), {zone:current_time_zone}).toFormat('MMMM d');
      if ( !my_commitments[formatted]){
        my_commitments[formatted] = 0;
      }
      my_commitments[formatted]++
    })

    //get width of #prayer-times
    let max_cell_size = document.querySelector('#prayer-times').offsetWidth / 7;
    let size = Math.min(max_cell_size, 60)


    return html`
      
      <div class="calendar-wrapper">
        <h3 class="month-title center">
            <button class="month-next" ?disabled="${previous_month < now_date.minus({months:1}).toSeconds() }"
                    @click="${e=>this.next_view(previous_month)}">
                <
            </button>
            ${window.campaign_scripts.ts_to_format(current_month, 'MMMM y', current_time_zone)}
            <button class="month-next" ?disabled="${next_month > this.end_timestamp}" @click="${e=>this.next_view(next_month)}">
                >
            </button>
        </h3>
        <div class="calendar">
            ${week_day_names.map(name=>html`<div class="day-cell week-day">${name}</div>`)}
            ${map(range(day_number), i=>html`<div class="day-cell disabled-calendar-day"></div>`)}
            ${this_month_days.map(day=>{
              let disabled = (day.key + day_in_seconds) < now;
                return html`
                  <div class="day-cell enabled-day ${selected_times.includes(day.day_start_zoned) ? 'selected-day':''}
                        ${disabled ? 'disabled-calendar-day':'day-in-select-calendar'}" 
                       data-day="${window.campaign_scripts.escapeHTML(day.key)}"
                       @click="${e=>this.day_selected(e, day.key)}"
                  >
                    <progress-ring stroke="3" radius="${(size/2).toFixed()}" progress="${window.campaign_scripts.escapeHTML(day.percent)}" text="${window.campaign_scripts.escapeHTML(day.day)}"></progress-ring>
                    <div class="indicator-section">
                      ${range(my_commitments[day.formatted]||0).map(i=> {
                        return html`<span class="prayer-time-indicator"></span>`
                      })}
                    </div>
                  </div>`
              })}
            ${day_number ? map(range(7 - day_number), i=>html`<div class="day-cell disabled-calendar-day"></div>` ) : ''}
        </div>
      </div>
            
      `

  }
}
customElements.define('my-calendar', cpMyCalendar);

/**
 * Select Time of day Component
 *
 * @property {String} slot_length - Length of time slots
 * @property {Array} times - Array of times to display
 * @fires time-selected, key of selected time
 *
 */
export class cpTimes extends LitElement {
  static styles = [
    css`
      .prayer-hour {
        margin-bottom: 1rem;
        display: grid;
        grid-template-columns: 1fr 1fr 1fr 1fr 1fr;
        grid-gap: 1rem 0.3rem;
        max-width: 400px;
      }
      .prayer-hour:hover {
        background-color: #4676fa1a;
      }
      .hour-cell {
        font-size: 0.8rem;
        display: flex;
        align-content: center;
        align-items: center;
        justify-content: center;
        white-space: nowrap;
      }
      .time.selected-time {
        color: white;
        background-color: var(--cp-color);
      }
      progress-ring {
        height: 20px;
      }
      .time {
        flex-basis: 20%;
        background-color: #4676fa1a;
        font-size: 0.8rem;
        border-radius: 5px;
        display: flex;
        justify-content: space-between;
        cursor: pointer;
      }
      .time.full-progress {
        background-color: #00800052;
      }
      .time:hover .time-label {
        background-color: var(--cp-color);
        opacity: 0.5;
        color: #fff;
      }
      .time:hover .control{
        background-color: var(--cp-color);
        opacity: 0.8;
        color: #fff;
      }
      .time[disabled] {
        opacity: 0.5;
        cursor: not-allowed;
      }
      .time-label {
        padding: 0.3rem;
        padding-inline-start: 1rem;
        width: 100%;
      }
      .control {
        background-color: #4676fa36;
        display: flex;
        align-items: center;
        padding: 0 0.1rem;
        border-top-right-radius: 5px;
        border-bottom-right-radius: 5px;
      }
      .times-container {
        overflow-y: scroll;
        max-height: 500px;
        padding-inline-end: 10px;
      }
    `
  ]

  static properties = {
    slot_length: {type: String},
    times: {type: Array},
    selected_day: {type: String},
    type: {type: String},
  }

  constructor() {
    super();
    this.days = window.campaign_scripts.days
    this.type = 'all_days'
  }

  time_selected(e,time_key){
    if ( time_key < parseInt(new Date().getTime() / 1000) && this.type === 'once_day'){
      return;
    }
    e.currentTarget.classList.add('selected-time');
    this.dispatchEvent(new CustomEvent('time-selected', {detail: time_key}));
  }

  get_times(){
      let day = this.days.find(d=>d.key === this.selected_day);
      let now = parseInt(new Date().getTime() / 1000);
      let times = []
      day.slots.forEach(s=>{
        let time =  window.luxon.DateTime.fromSeconds( s.key, {zone:window.campaign_scripts.timezone} )

        let progress = s.subscribers ? 100 : 0;
        times.push({
            key: s.key,
            hour: time.toFormat('hh a'),
            minute: time.toFormat('mm'),
            progress: progress,
        })
      })
    return times;
  }

  connectedCallback(){
    super.connectedCallback();
    //set scroll position
    setTimeout(()=>{
      this.shadowRoot.querySelector('.times-container').scrollTop = 250;
    })
  }

  render() {
    if ( this.type === 'once_day' && this.selected_day ){
      this.times = this.get_times()
    }
    let now = window.luxon.DateTime.now().toSeconds();
    let time_slots = 60 / this.slot_length;
    return html`
      <div class="times-container">
        ${map(range(24),index => html`
            <div class="prayer-hour prayer-times">
                <div class="hour-cell">
                    ${this.times[index*time_slots].hour}
                </div>
                ${map(range(time_slots), (i) => {
                    let time = this.times[index*time_slots+i];
                    return html`
                    <div class="time ${time.progress >= 100 ? 'full-progress' : ''}" @click="${(e)=>this.time_selected(e,time.key)}" ?disabled="${this.type === 'once_day' && time.key < now}">
                        <span class="time-label">${time.minute}</span>
                        <span class="control">
                          ${time.progress < 100 ? 
                              html`<progress-ring stroke="2" radius="10" progress="${time.progress}"></progress-ring>` :
                              html`<div style="height:20px;width:20px;display:flex;justify-content: center">&#10003;</div>`}
                        </span>
                    </div>
                `})}
            </div>
        `)}
      </div>
    `
  }
}
customElements.define('cp-times', cpTimes);


export class cpVerify extends LitElement {
  static styles = [
    css`
      .otp-input-wrapper {
        width: 240px;
        text-align: left;
        display: inline-block;
      }
      .otp-input-wrapper input {
        padding: 0;
        width: 264px;
        font-size: 22px;
        font-weight: 600;
        color: #3e3e3e;
        background-color: transparent;
        border: 0;
        margin-left: 2px;
        letter-spacing: 30px;
        font-family: sans-serif !important;
      }
      .otp-input-wrapper input:focus {
        box-shadow: none;
        outline: none;
      }
      .otp-input-wrapper svg {
        position: relative;
        display: block;
        width: 240px;
        height: 2px;
      }
      .verify-section {
        text-align: center;
      }
    `
  ]

  static properties = {
    email: {type: String},
  }

  constructor() {
    super();
    this.email = '';
    this.code = '';
  }

  handleInput(e){
    let val = e.target.value
    this.code = val
    this.dispatchEvent(new CustomEvent('code-changed', {detail: this.code}));
    this.requestUpdate()
  }

  render() {
    return html`
      <div class="verify-section">
        <p style="text-align: start">
            ${strings['A confirmation code hase been sent to %s.'].replace('%s', this.email)}
            <br>
            ${strings['Please enter the code below in the next 10 minutes to confirm your email address.']}
        </p>
        <label for="cp-confirmation-code" style="display: block">
            <strong>${strings['Confirmation Code']}:</strong><br>
        </label>
        <div class="otp-input-wrapper" style="padding: 20px 0">
            <input class="cp-confirmation-code" name="code" type='text' maxlength='6' pattern='[0-9]*'
                   autocomplete='off' required @input=${this.handleInput}>
            <svg viewBox='0 0 240 1' xmlns='http://www.w3.org/2000/svg'>
                <line x1='0' y1='0' x2='240' y2='0' stroke='#3e3e3e' stroke-width='2'
                      stroke-dasharray='20,22'/>
            </svg>
        </div>
      </div>
      
    `

  }
}
customElements.define('cp-verify', cpVerify);

export class cpProgressRing extends LitElement {
  static styles = [
    css``
  ]

  static properties = {
    radius: {type: Number},
    text: {type: String},
    progress: {type: Number},
    progress2: {type: Number},
    font_size: {type: Number},
    stroke: {type: Number},
    color: {type: String},
  }

  constructor() {
    super();
    this.radius = 30
    this.stroke = 3
    this.font_size = 15
    this.color = 'dodgerblue'
    this.progress = 0;
    this.text = ''
  }

  render() {
    this.progress = parseInt(this.progress).toFixed()
    this.radius = parseInt(this.radius).toFixed()
    this.stroke = parseInt(this.stroke).toFixed()
    const normalizedRadius = this.radius - this.stroke;
    this._circumference = normalizedRadius * 2 * Math.PI;

    let normalizedRadius2 = parseInt(this.radius) - this.stroke/2 + 1
    this._circumference2 = normalizedRadius2 * 2 * Math.PI;

    let offset = this._circumference - (this.progress / 100 * this._circumference);
    const offset2 = -(this.progress / 100 * this._circumference);
    if ( this._progress2 ){
      const offset3 = this._circumference2 - (this._progress2 / 100 * ( this._circumference2 ) );
    }

    this.color = parseInt( this.progress ) >= 100 ? 'mediumseagreen' : this.color

    // if ( text2 ){
    //   text_html = `<text x="50%" y="50%" text-anchor="middle" stroke-width="2px" font-size="${font_size}px">
    //       <tspan x="50%" dy="0">${window.campaign_scripts.escapeHTML(text || progress + '%')}</tspan>
    //       <tspan x="50%" dy="0.5cm">${window.campaign_scripts.escapeHTML(text2)}</tspan>
    //   </text>`
    // } else {
    //   text_html =  `<text x="50%" y="50%" text-anchor="middle" stroke-width="2px" font-size="${font_size}px" dy=".3em">
    //     ${window.campaign_scripts.escapeHTML(text || progress + '%')}
    //   </text>
    //   `
    // }
    // circrle3 @todo


    return html`
      <svg height="${this.radius * 2}"
           width="${this.radius * 2}" >
           <circle
             class="first-circle"
             stroke="${this.color}"
             stroke-dasharray="${this._circumference} ${this._circumference}"
             style="stroke-dashoffset:${offset}"
             stroke-width="${this.stroke}"
             fill="transparent"
             r="${normalizedRadius}"
             cx="${this.radius}"
             cy="${this.radius}"
          />
          <circle
             class="second-circle"
             stroke="${this.color}"
             stroke-opacity="0.1"
             stroke-dasharray="${this._circumference} ${this._circumference}"
             style="stroke-dashoffset:${offset2}"
             stroke-width="${this.stroke}"
             fill="transparent"
             r="${normalizedRadius}"
             cx="${this.radius}"
             cy="${this.radius}"
          />
          <text class="inner-text" x="50%" y="50%" text-anchor="middle" stroke-width="2px" font-size="15px" dy=".3em">
              ${window.campaign_scripts.escapeHTML(this.text)}
          </text>
      </svg>

      <style>
          circle {
            transition: stroke-dashoffset 0.35s;
            transform: rotate(-90deg);
            transform-origin: 50% 50%;
          }
      </style>
    `

  }
}
customElements.define('progress-ring', cpProgressRing);

class DtModal extends LitElement {
  static styles = [
    window.campaignStyles,
    css`
      :host {
        display: block;
        font-family: var(--font-family);
      }
      :host:has(dialog[open]) {
        overflow: hidden;
      }

      .dt-modal {
        display: block;
        background: var(--dt-modal-background-color, #fff);
        color: var(--dt-modal-color, #000);
        max-inline-size: min(90vw, 100%);
        max-block-size: min(80vh, 100%);
        max-block-size: min(80dvb, 100%);
        margin: auto;
        height: fit-content;
        padding: var(--dt-modal-padding, 1em);
        position: fixed;
        inset: 0;
        border-radius: 1em;
        border: none;
        box-shadow: var(--shadow-6);
        z-index: 1000;
        transition: opacity 0.1s ease-in-out;
      }

      dialog:not([open]) {
        pointer-events: none;
        opacity: 0;
      }

      dialog::backdrop {
        background: var(--dt-modal-backdrop-color, rgba(0, 0, 0, 0.55));
        animation: var(--dt-modal-animation, fade-in 0.75s);
      }

      @keyframes fade-in {
        from {
          opacity: 0;
        }
        to {
          opacity: 1;
        }
      }

      h1,
      h2,
      h3,
      h4,
      h5,
      h6 {
        line-height: 1.4;
        text-rendering: optimizeLegibility;
        color: inherit;
        font-style: normal;
        font-weight: 300;
        margin: 0;
      }

      form {
        display: grid;
        height: fit-content;
        grid-template-columns: 1fr;
        grid-template-rows: 50px auto 100px;
        grid-template-areas:
          'header'
          'main'
          'footer';
        position: relative;
      }

      form.no-header {
        grid-template-rows: auto auto;
        grid-template-areas:
          'main'
          'footer';
      }

      header {
        grid-area: header;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
      }
      
      .button.opener {
        color: var(--dt-modal-button-opener-color,var(--dt-modal-button-color, #fff) );
        background: var(--dt-modal-button-opener-background, var(--dt-modal-button-background, #000) );
        border: 0.1em solid var(--dt-modal-button-opener-background, #000);
      }
      button.toggle {
        margin-inline-end: 0;
        margin-inline-start: auto;
        background: none;
        border: none;
        color: inherit;
        cursor: pointer;
        display: flex;
        align-items: flex-start;
      }

      article {
        grid-area: main;
        overflow: auto;
      }

      footer {
        grid-area: footer;
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
      }

  `];


  static get properties() {
    return {
      title: { type: String },
      content: { type: String, state: true },
      context: { type: String },
      isHelp: { type: Boolean },
      isOpen: { type: Boolean, state: true },
      hideHeader: { type: Boolean },
      hideButton: { type: Boolean },
      buttonClass: { type: Object },
      buttonStyle: { type: Object },
      confirmButtonClass: { type: String },

    };
  }

  constructor() {
    super();
    this.context = 'default';
    this.addEventListener('open', (e) => this._openModal());
    this.addEventListener('close', (e) => this._closeModal());
  }

  _openModal() {
    this.isOpen = true;
    this.shadowRoot.querySelector('dialog').showModal();

    document.querySelector('body').style.overflow = "hidden"
  }

  _dialogHeader(svg) {
    if (!this.hideHeader) {
      return html`
      <header>
            <h1 id="modal-field-title">${this.title}</h1>
            <button @click="${this._cancelModal}" class="toggle">${svg}</button>
          </header>
      `;
    }
    return html``;
  }

  _closeModal() {
    this.isOpen = false;
    this.shadowRoot.querySelector('dialog').close();
    document.querySelector('body').style.overflow = "initial"
  }
  _cancelModal() {
    this._triggerClose('cancel');
  }
  _triggerClose(action) {
    this.dispatchEvent(new CustomEvent('close', {
      detail: {
        action,
      },
    }));
  }

  _dialogClick(e) {
    if (e.target.tagName !== 'DIALOG') {
      // This prevents issues with forms
      return;
    }

    // Close the modal if the user clicks outside of the modal
    const rect = e.target.getBoundingClientRect();

    const clickedInDialog =
      rect.top <= e.clientY &&
      e.clientY <= rect.top + rect.height &&
      rect.left <= e.clientX &&
      e.clientX <= rect.left + rect.width;

    if (clickedInDialog === false) {
      this._cancelModal();
    }
  }

  _dialogKeypress(e) {
    if (e.key === 'Escape') {
      this._cancelModal();
    }
  }
  firstUpdated() {
    if (this.isOpen) {
      this._openModal();
    }
  }
  updated(changedProperties) {
    if (changedProperties.has('isOpen')) {
      if (this.isOpen) {
        this._openModal();
      }
    }
  }

  _onButtonClick() {
    this._triggerClose('close');
  }

  _onModalConfirm() {
    this._triggerClose('confirm');
  }

  render() {
    // prettier-ignore
    const svg = html`
      <svg viewPort="0 0 12 12" version="1.1" width='12' height='12'>
          xmlns="http://www.w3.org/2000/svg">
        <line x1="1" y1="11"
              x2="11" y2="1"
              stroke="currentColor"
              stroke-width="2"/>
        <line x1="1" y1="1"
              x2="11" y2="11"
              stroke="currentColor"
              stroke-width="2"/>
      </svg>
    `;
    return html`
      <dialog
        id=""
        class="dt-modal"
        @click=${this._dialogClick}
        @keypress=${this._dialogKeypress}
      >
        <form method="dialog" class=${this.hideHeader ? "no-header" : ""}>
          ${this._dialogHeader(svg)}
          ${this.content ? html`
            <article><p>${this.content}</p></article>
          ` : ''}
          <article>
            <slot name="content"></slot>
          </article>
          <footer>
            <button
              class="clear-button small"
              data-close=""
              aria-label="Close reveal"
              type="button"
              @click=${this._onButtonClick}
            >
              <slot name="close-button">Close</slot>
            </button>
              
            <button
              class="button small ${this.confirmButtonClass}"
              data-close=""
              aria-label="Confirm reveal"
              type="button"
              @click=${this._onModalConfirm}
            >
              <slot name="confirm-button">Confirm</slot>
            </button>
          </footer>
        </form>
      </dialog>
    `;
  }
}

window.customElements.define('dt-modal', DtModal);