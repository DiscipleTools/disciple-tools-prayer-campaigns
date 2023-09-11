import {html, css, LitElement, range, map} from 'https://cdn.jsdelivr.net/gh/lit/dist@2/all/lit-all.min.js';
const strings = window.campaign_scripts.escapeObject(window.campaign_components.translations)

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
        <p>${strings['Detected Time Zone']}:
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
    
      input {
        font-size: 1rem;
        line-height: 1rem;
        color: black;
        border: 1px solid black;
      }
      input[type="text"], input[type="email"], input[type="tel"], input[type="password"] {
        min-width: 250px;
        max-width: 400px;
        //margin: auto;
        padding: 0 0.5rem;
        min-height: 40px;
        display: block;
      }`,
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
      this.form_error = 'Please enter a valid name or email address'
      this.requestUpdate()
      return;
    }

    //bubble up form items
    this.dispatchEvent(new CustomEvent('form-items', { detail: this._form_items }));
  }

  render(){
    return html`
      <div>
          <label for="name">Name<br>
              <input class="cp-input" type="text" name="name" id="name" placeholder="Name" required @input=${this.handleInput} />
          </label>
      </div>
      <div>
          <label for="email">Email<br>
              <input class="cp-input" type="email" name="EMAIL" id="email" placeholder="Email" @input=${this.handleInput}/>
              <input class="cp-input" type="email" name="email" id="e2" placeholder="Email" @input=${this.handleInput} />
          </label>
      </div>
      <div>
          <div id='cp-no-selected-times' style='display: none' class="form-error" >
              No prayer times selected
          </div>
      </div>

      <div id="cp-form-error" class="form-error" ?hidden=${!this.form_error}>
          ${this.form_error}
      </div>

      <div class="nav-buttons">
          <campaign-back-button @click=${this.back}></campaign-back-button>
          <button ?disabled=${!this._form_items.name || !this._is_email(this._form_items.email)}
                  @click=${()=>this.verify_contact_info()}>

              Verify
              <img ?hidden=${!this._loading} class="button-spinner" src="${window.campaign_components.plugin_url}spinner.svg" width="22px" alt="spinner"/>
          </button>
      </div>
    `
  }
}
customElements.define('contact-info', ContactInfo);

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
    label: {type: String},
    field: {type: Boolean},
    value: {type: String},
    options: {type: Array},
  }
  constructor() {
    super();
    this.options = [];
  }

  handleClick(e){
    if ( this.value != e.target.value ){
      this.value = e.target.value
      this.dispatchEvent(new CustomEvent('change', {detail: this.value}));
    }
  }

  render() {
    if ( typeof this.field === 'number' && !isNaN( this.field ) ){
      this.field = this.field.toString();
    }

    return html`
      ${this.options.map(o=>html`
          <button class="select ${o.value === this.value ? 'selected' : ''}"
                  ?disabled="${o.disabled}"
                  @click="${this.handleClick}"
            value="${o.value}">
                ${o.label}
          </button>`
      )}  
      
    `
  }
}
customElements.define('cp-select', select);

export class cpCalendar extends LitElement {
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
        height: 20px;
        width:40px;
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
      }
    `,
    // window.campaignStyles
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
  }

  connectedCallback(){
    super.connectedCallback();
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

    let now = parseInt(new Date().getTime() / 1000);
    let days = this.days.filter(d=>d.key >= now) || [];
    let current_time_zone = this.current_time_zone
    let current_month = this.month_to_show || days[0].key
    let current_month_date = window.luxon.DateTime.fromSeconds(current_month, {zone:current_time_zone})
    let this_month_days = days.filter(k=>k.month===current_month_date.toFormat('y_MM'));


    let previous_month = window.luxon.DateTime.fromSeconds(current_month, {zone:current_time_zone}).minus({months:1}).toSeconds()
    let next_month = window.luxon.DateTime.fromSeconds(current_month, {zone:current_time_zone}).plus({months:1}).toSeconds()

    let day_number = window.campaign_scripts.get_day_number(current_month, current_time_zone);


    return html`
      
      <div class="calendar-wrapper">
        <h3 class="month-title center">
            <button ?disabled="${previous_month < now}"
                    @click="${e=>this.next_view(previous_month)}"><</button>
            ${window.campaign_scripts.ts_to_format(current_month, current_time_zone, 'MMMM y')}
            <button ?disabled="${next_month > this.end_timestamp}" @click="${e=>this.next_view(next_month)}">
                >
            </button>
        </h3>
        <div class="calendar">
            ${week_day_names.map(name=>html`<div class="day-cell week-day">${name}</div>`)}
            ${map(range(day_number-1), i=>html`<div class="day-cell disabled-calendar-day"></div>`)}
            ${this_month_days.map(day=>{
                let disabled = (day.key + day_in_seconds) < now;
                return html`
                  <div class="day-cell ${selected_times.includes(day.day_start_zoned) ? 'selected-day':''}
                        ${disabled ? 'disabled-calendar-day':'day-in-select-calendar'}" 
                       data-day="${window.lodash.escape(day.key)}"
                       @click="${e=>this.day_selected(e, day.key)}"
                  >
                      ${window.lodash.escape(day.day)}
                  </div>`
            })}
            ${day_number ? map(range(7 - day_number), i=>html`<div class="day-cell disabled-calendar-day"></div>` ) : ''}
        </div>
      </div>
            
      `

  }
}
customElements.define('cp-calendar', cpCalendar);


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
    `
  ]

  static properties = {
    slot_length: {type: String},
    times: {type: Array},
  }

  constructor() {
    super();
  }

  time_selected(time_key){
    this.dispatchEvent(new CustomEvent('time-selected', {detail: time_key}));
  }

  render() {
    let time_slots = 60 / this.slot_length;
    return html`
        ${map(range(24),index => html`
            <div class="prayer-hour prayer-times">
                <div class="hour-cell">
                    ${this.times[index*time_slots].hour}
                </div>
                ${map(range(time_slots), (i) => {
                    let time = this.times[index*time_slots+i];
                    return html`
                    <div class="time ${time.progress >= 100 ? 'full-progress' : ''}" @click="${(e)=>this.time_selected(time.key)}" >
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
    let name = e.target.name
    this.code = val
    console.log(this.code);
    this.dispatchEvent(new CustomEvent('code-changed', {detail: this.code}));
    this.requestUpdate()
  }

  render() {
    return html`
      <p>
          A confirmation code hase been sent to ${this.email}. <br> Please enter the code below in
          the next 10 minutes to confirm your email address.
      </p>
      <label for="cp-confirmation-code" style="display: block">
          <strong>Confirmation Code:</strong><br>
      </label>
      <div class="otp-input-wrapper" style="padding: 20px 0">
          <input class="cp-confirmation-code" name="code" type='text' maxlength='6' pattern='[0-9]*'
                 autocomplete='off' required @input=${this.handleInput}>
          <svg viewBox='0 0 240 1' xmlns='http://www.w3.org/2000/svg'>
              <line x1='0' y1='0' x2='240' y2='0' stroke='#3e3e3e' stroke-width='2'
                    stroke-dasharray='20,22'/>
          </svg>
      </div>
      
    `

  }
}
customElements.define('cp-verify', cpVerify);