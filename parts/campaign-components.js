import {html, css, LitElement, range, map, classMap, styleMap} from 'https://cdn.jsdelivr.net/gh/lit/dist@2/all/lit-all.min.js';
const strings = window.campaign_scripts.escapeObject(window.campaign_objects.translations)
function translate(str){
  if ( !strings[str] ){
    console.error("'" + str + "' => __( '" + str + "', 'disciple-tools-prayer-campaigns' ),");
  }
  return strings[str] || str
}
const day_in_seconds = 86400

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
        background-color: var( --cp-color, dodgerblue );
      }
      button:hover {
        background-color: transparent;
        border-color: var( --cp-color, dodgerblue );
        color: var( --cp-color, dodgerblue );
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
                    <option value="">${strings['Select a timezone']}</option>
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
    selected_times_count: {state:true},
  }

  constructor() {
    super();
    this._form_items = {
      email: '',
      name: '',
      receive_prayer_tools_news: window.campaign_objects.dt_campaigns_is_prayer_tools_news_enabled ? true : false,
    }
    window.campaign_data.signup_form_fields?.map(f=>{
      this._form_items[f.key] = f.default || null;
    })
    this.selected_times_count = 0;
    this._loading = false;
  }

  _is_email(val){
    return String(val).match(/^\S+@\S+\.\S+$/)
  }

  handleInput(e){
    let val = e.target.type === 'checkbox' ? e.target.checked : e.target.value;
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
      ${ window.campaign_objects.dt_campaigns_is_prayer_tools_news_enabled ?
          html`<label for="receive_prayer_tools_news" style="font-weight: normal; display: block">
                <input type="checkbox" checked id="receive_prayer_tools_news" name="receive_prayer_tools_news" @input=${this.handleInput}/>
                ${translate("Receive news from Prayer.Tools about upcoming prayer campaigns and occasional communication from GospelAmbition.org")}
          </label>`
      : ``}

      <!-- Additional Fields -->
      ${ window.campaign_data.signup_form_fields?.map(f=>{
        let key = window.campaign_scripts.escapeHTML(f.key)
        let name = window.campaign_scripts.escapeHTML(f.name)
        let description = window.campaign_scripts.escapeHTML(f.description)
        if ( f.type ==='text' && f.subtype ==='phone'){
          return html`<cp-phone-number>
              
          </cp-phone-number>`
        } else if ( f.type === 'text' ){
          return html`
            <div>
                <label for="${key}">${name}<br>
                    <input
                        class="cp-input"
                        type="text" name="${key}" id="${key}" placeholder="${description}" @input=${this.handleInput}/>
                </label>
            </div>
          `
        } else if ( f.type === 'boolean' ){
          return html`
            <div>
                <label for="${key}" style="font-weight: normal; display: block">
                    <input
                        type="checkbox"
                        name="${key}"
                        id="${key}"
                        ?checked=${f.default}
                        @input=${this.handleInput}/>
                    ${description || name}
                </label>
            </div>
          `
        } else if ( f.type === 'textarea' ) {
          return html`
            <div>
              <label for="${key}">${name}<br>
                <textarea class="cp-input" rows="5" name="${key}" id="${key}" placeholder="${description}" @input=${this.handleInput}></textarea>
              </label>
            </div>
          `;

        } else if ( f.type === 'number' ) {
          return html`
            <div>
              <label for="${key}">${name}<br>
                <input
                  class="cp-input"
                  type="number" name="${key}" id="${key}" placeholder="${description}" @input=${this.handleInput}/>
              </label>
            </div>
          `;

        } else if ( f.type === 'phone' ) {
          return html`
            <div>
              <label for="${key}">${name}<br>
                <input
                  class="cp-input"
                  type="number" name="${key}" id="${key}" placeholder="${description}" @input=${this.handleInput}/>
              </label>
            </div>
          `;

        } else if ( f.type === 'key_select' ) {

          // Package default options.
          let options = [];
          for (const [key, option] of Object.entries(f.default)) {
            options.push({
              'key': key,
              'label': option['label']
            });
          }

          return html`
            <div>
              <label for="${key}">${name}<br>
                <select class="cp-input" name="${key}" id="${key}" @input=${this.handleInput}>

                  ${options.map((option, idx) =>
                      html`
                        <option value=${option['key']}>
                          ${option['label']}
                        </option>`)}

                </select>
              </label>
            </div>
          `;
        }
      } ) }

      <div>
          <div id='cp-no-selected-times' style='display: none' class="form-error" >
              ${strings['No prayer times selected']}
          </div>
      </div>

      <div id="cp-form-error" class="form-error" ?hidden=${!this.form_error}>
          ${this.form_error}
      </div>

      <div class="nav-buttons">
          <button
              class="button-content"
              ?disabled=${!this._form_items.name || !this._is_email(this._form_items.email) || this.selected_times_count === 0 || this._loading}
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
 * @fires day-selected, timestamp of selected day
 *
 */
export class cpCalendarDaySelect extends LitElement {
  static styles = [
    css`
      :host {
        display: block;
        container-type: inline-size;
        container-name: calendar;
      }
      .calendar {
        display: grid;
        grid-template-columns: repeat(7, 14cqw);
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
      .day-cell.disabled-calendar-day {
        color:lightgrey;
        cursor: not-allowed;
      }
      .week-day {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 40px;
        width: 40px;
        font-weight: bold;
        font-size:clamp(0.75em, 0.65rem + 2cqi, 1em);
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
  }

  constructor() {
    super();
    this.month_to_show = null;
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
    this.month_to_show = e
    this.requestUpdate()
    //remove all selected-time css
    this.shadowRoot.querySelectorAll('.selected-time').forEach(e=>e.classList.remove('selected-time'))
  }

  day_selected(e, day){
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
    if ( !this.end_timestamp ){
      this.end_timestamp = this.days[this.days.length - 1].key
    }

    let selected_times = this.selected_times.map(t=>t.day_key);

    let week_day_names = window.campaign_scripts.get_days_of_the_week_initials(navigator.language, 'narrow')

    let now_date = window.luxon.DateTime.now({zone:window.campaign_user_data.timezone})
    let now = now_date.toSeconds();
    let month_date = window.luxon.DateTime.fromSeconds(this.month_to_show || Math.max(this.days[0].key, now, window.campaign_data.start_timestamp), {zone:window.campaign_user_data.timezone})
    let month_start = month_date.startOf('month')

    let month_days =  window.campaign_scripts.build_calendar_days(month_date)

    let first_day_is_weekday = month_start.weekday
    let previous_month = month_date.minus({months:1}).toSeconds()
    let next_month = month_start.plus({months:1}).toSeconds()

    return html`

      <div class="calendar-wrapper">
        <h3 class="month-title center">
            <button class="month-next" ?disabled="${month_start.toSeconds() < now}"
                    @click="${e=>this.next_view(previous_month)}">
                <
            </button>
            ${month_date.toLocaleString({ month: 'long', year: 'numeric' })}
            <button class="month-next" ?disabled="${next_month > this.end_timestamp}" @click="${e=>this.next_view(next_month)}">
                >
            </button>
        </h3>
        <div class="calendar">
            ${week_day_names.map(name=>html`<div class="day-cell week-day">${name}</div>`)}
            ${map(range(first_day_is_weekday%7), i=>html`<div class="day-cell disabled-calendar-day"></div>`)}
            ${month_days.map(day=>{
                return html`
                  <div class="day-cell ${day.disabled ? 'disabled':''} ${selected_times.includes(day.key) ? 'selected-day':''}"
                       data-day="${window.campaign_scripts.escapeHTML(day.key)}"
                       @click="${e=>!day.disabled&&this.day_selected(e, day.key)}"
                  >
                      ${window.campaign_scripts.escapeHTML(day.day)}
                  </div>`
            })}
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
        --size: min(60px, calc((100vw - 2rem) / 7));
      }
      .calendar-wrapper {
        //container-type: inline-size;
        container-name: cp-calendar;
        border-radius: 10px;
        padding: 1em;
        display: block;
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
        font-size:clamp(1em, 2cqw, 0.5em + 1cqi);
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
    `,
    window.campaignStyles
  ]

  static properties = {
    start_timestamp: {type: String},
    end_timestamp: {type: String},
    days: {type: Array},
    selected_times: {type: Array},
  }

  constructor() {
    super();
    this.month_to_show = null;
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
    this.month_to_show = e
    this.requestUpdate()
    //remove all selected-time css
    this.shadowRoot.querySelectorAll('.selected-time').forEach(e=>e.classList.remove('selected-time'))
  }

  day_selected(e, day){
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
    if ( !this.end_timestamp ){
      this.end_timestamp = 9999999999
    }

    let week_day_names = window.campaign_scripts.get_days_of_the_week_initials(navigator.language, 'narrow')

    let now_date = window.luxon.DateTime.now({zone:window.campaign_user_data.timezone})
    let now = now_date.toSeconds();
    let month_date = window.luxon.DateTime.fromSeconds(this.month_to_show || Math.max(this.days[0].key, now, window.campaign_data.start_timestamp), {zone:window.campaign_user_data.timezone})
    let month_start = month_date.startOf('month')
    let month_end = month_date.endOf('month')

    let my_commitments = {};
    (window.campaign_data.subscriber_info?.my_commitments || []).filter(c=>c.time_begin >= month_start.toSeconds() && c.time_begin <= month_end.toSeconds()).forEach(c=>{
      let formatted = window.luxon.DateTime.fromSeconds(parseInt(c.time_begin), {zone:window.campaign_user_data.timezone}).toFormat('MMMM d');
      if ( !my_commitments[formatted]){
        my_commitments[formatted] = 0;
      }
      my_commitments[formatted]++
    })

    let month_days = window.campaign_scripts.build_calendar_days(month_date)

    let first_day_is_weekday = month_start.weekday
    let previous_month = month_date.minus({months:1}).toSeconds()
    let next_month = month_date.plus({months:1}).toSeconds()

    //get width of #prayer-times
    let max_cell_size = document.querySelector('#prayer-times').offsetWidth / 7;
    let size = Math.min(max_cell_size, 40)


    return html`

      <div class="calendar-wrapper">
        <h3 class="month-title center">
            <button class="month-next" ?disabled="${ month_start.toSeconds() < now }"
                    @click="${e=>this.next_view(previous_month)}">
                <
            </button>
            ${month_date.toLocaleString({ month: 'long', year: 'numeric' })}
            <button class="month-next" ?disabled="${next_month > this.end_timestamp}" @click="${e=>this.next_view(next_month)}">
                >
            </button>
        </h3>
        <div class="calendar">
            ${week_day_names.map(name=>html`<div class="day-cell week-day">${name}</div>`)}
            ${map(range(first_day_is_weekday%7), i=>html`<div class="day-cell disabled-calendar-day"></div>`)}
            ${month_days.map(day=>{
                return html`
                  <div class="day-cell ${day.disabled ? 'disabled':''}"
                       data-day="${window.campaign_scripts.escapeHTML(day.key)}"
                       @click="${e=>this.day_selected(e, day.key)}"
                  >
                    <progress-ring class="${day.disabled?'disabled':0}" progress="${window.campaign_scripts.escapeHTML(day.percent)}" text="${window.campaign_scripts.escapeHTML(day.day)}"></progress-ring>
                    <div class="indicator-section">
                      ${map(range(my_commitments[day.formatted]||0),i=> {
                        return html`<span class="prayer-time-indicator"></span>`
                      })}
                    </div>
                  </div>`
              })}
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
      .times-container {
          display: grid;
          text-align: center;
          margin-bottom: 0.1rem;
          //max-height: 600px;
          //overflow-y: auto;
      }
      .times-section {
          display: grid;
          align-items: center;
          margin-bottom: 0.5rem;
          //grid-template-columns: auto 1fr 1fr 1fr 1fr 1fr 1fr;
          grid-gap: 0.3rem 1rem;
      }

      .section-column {
          display: grid;
          grid-gap: 0.3rem 1rem;
          grid-template-columns: 1fr 1fr 1fr 1fr 1fr;
      }
      .prayer-hour {
        font-size: 0.8rem;
        font-weight: bold;
        white-space: nowrap;
      }
      .grid-cell {
        display: flex;
        justify-content: center;
        text-align: center;
        font-size: 0.8rem;
        padding: 0.1rem;
      }
      .time {
        background-color: #4676fa1a;
        border-radius: 5px;
        cursor: pointer;
      }
      .empty-time {
          opacity: .8;
          font-size:.8rem;
      }
      .time[disabled] {
        opacity: 0.3;
        cursor: not-allowed;
      }
      .time.selected-time {
          color: white;
          opacity: 1;
          background-color: var(--cp-color);
      }
      .time.selected-time img {
          filter: invert(1);
      }
      .time.selected-time .empty-time {
        opacity: 1;
      }
      .time img {
        display: flex;
        align-self: center;
        margin-inline-start: 0.1rem;
        width: 0.7rem;
        height: 0.7rem;
      }

      .legend-row {
        display: flex;
        font-size: 0.8rem;
        grid-column-gap: 0.3rem;
        justify-content: right;
        margin-bottom: 0.5rem;
      }
      .legend-row span {
        display: flex;
        align-items: center;
      }
      .legend-row span.time {
        padding: 0.3rem 0.5rem;
      }
      .center-content {
        display: flex;
        align-items: center;
        justify-content: center;
      }
      @media (min-width: 640px) {
          .time:hover {
              background-color: var(--cp-color);
              opacity: 0.8;
              color: #fff;
          }
      }
    `
  ]

  static properties = {
    slot_length: {type: String},
    times: {type: Array},
    selected_day: {type: String},
    frequency: {type: String},
    weekday: {type: String},
    selected_times: {type: Array},
    recurring_signups: {type: Array},
  }

  constructor() {
    super();
    this.days = window.campaign_scripts.days
    this.selected_times = []
  }

  connectedCallback(){
    super.connectedCallback();
    //set scroll position
    setTimeout(()=>{
      this.shadowRoot.querySelector('.times-container').scrollTop = 250;
    })
    window.addEventListener('campaign_timezone_change', (e)=>{
      this.days = window.campaign_scripts.days
      this.requestUpdate()
    });
  }


  render() {
    if ( this.frequency === 'pick' && this.selected_day ){
      this.times = this.get_times()
    }
    if ( this.frequency === 'daily' ){
      this.times = this.get_daily_times()
    }
    if ( this.frequency === 'weekly' && this.weekday ){
      this.times = this.get_weekly_times()
    }
    if ( !this.times ){
      this.times = window.campaign_scripts.get_empty_times()
    }
    let now = window.luxon.DateTime.now().toSeconds();
    let time_slots = 60 / this.slot_length;

    let index = 0;
    return html`

      <div class="times-container">
          <div class="legend-row">
              <span class="time">
                  06:15
              </span>
              <span>${translate('Time not covered')}</span>
              <span class="time">
                  2 <img src="${window.campaign_objects.plugin_url}assets/noun-person.png">
              </span>
              <span>
                  ${translate('# of people covering this time' )}
              </span>
          </div>

        ${map(range(4),row => html`
          <div class="times-section">
            <div class="section-column time-column">
              <div>&nbsp;</div>
               ${map(range(time_slots),i=>html`<div class="grid-cell">${this.times[i].minute}</div>`)}
            </div>

            ${map(range(6),i => {
              index = i + row * 6
              return html`

              ${ this.times[index*time_slots] ? html`
              <div class="section-column">
                  <div class="prayer-hour">
                      ${this.times[index*time_slots].hour}
                  </div>
                  ${map(range(time_slots), (i) => {
                      let time = this.times[index*time_slots+i];
                      let html2 = ``
                      if ( time.coverage_count ) {
                          html2 = html`${time.coverage_count} <img src="${window.campaign_objects.plugin_url}assets/noun-person.png">`
                      } else {
                          html2 = html`<span class="empty-time">
                              ${time.hour_minute}
                          </span>`
                      }
                      // } else if ( false && time.progress < 100 ) {
                      //     html2 = html`<progress-ring stroke="2" radius="10" progress="${time.progress}"></progress-ring>`
                      return html`
                      <div class="grid-cell time ${time.selected ? 'selected-time' : ''}" title=":${time.minute}"
                           @click="${(e)=>this.time_selected(e,time.key)}"
                           ?disabled="${this.frequency === 'pick' && time.key < now}">
                          ${html2}
                      </div>
                  `})}
              </div>` : html``}
            `
            })}
          </div>
        `)}
      </div>
    `
  }

  time_selected(e,time_key){
    if ( time_key < parseInt(new Date().getTime() / 1000) && this.frequency === 'pick'){
      return;
    }
    this.dispatchEvent(new CustomEvent('time-selected', {detail: time_key}));
    this.times = window.campaign_scripts.get_empty_times()
  }

  get_times(){
    let day = this.days.find(d=>d.key === this.selected_day);
    let times = []
    day.slots.forEach(s=>{
      let time =  window.luxon.DateTime.fromSeconds( s.key, {zone:window.campaign_user_data.timezone} )

      let progress = s.subscribers ? 100 : 0;
      const selected = this.selected_times.find(t=>s.key>=t.time && s.key < (t.time + t.duration * 60));
      times.push({
        key: s.key,
        hour: time.toLocaleString({ hour: '2-digit' }),
        minute: time.toFormat('mm'),
        hour_minute: time.toFormat('hh:mm'),
        progress: progress,
        selected: selected,
        coverage_count: s.subscribers + (selected ? 1 : 0),
      })
    })
    return times;
  }
  get_daily_times(){
    let start_of_time_frame = window.luxon.DateTime.now({zone:window.campaign_user_data.timezone})
    if ( start_of_time_frame.toSeconds() < window.campaign_data.start_timestamp ){
      start_of_time_frame = window.luxon.DateTime.fromSeconds(window.campaign_data.start_timestamp, {zone:window.campaign_user_data.timezone})
    }

    let time_frame_day_start = start_of_time_frame.startOf('day').toSeconds()
    let in_one_month = start_of_time_frame.plus({months:1}).toSeconds()
    let next_month = this.days.filter(d=>{
      return d.key >= time_frame_day_start &&
        d.key <= (( window.campaign_data.end_timestamp || in_one_month ))
    })
    let coverage = {}
    next_month.forEach(d=>{
      d.slots.forEach(s=>{
        if ( s.key >= start_of_time_frame.toSeconds() && s.subscribers ){
          if ( !coverage[s.formatted] ){
            coverage[s.formatted] = []
          }
          coverage[s.formatted].push(s.subscribers)
        }
      })
    })

    let options = [];
    let key = 0;
    while (key < day_in_seconds) {
      let time = window.luxon.DateTime.fromSeconds(time_frame_day_start + key)
      let time_formatted = time.toFormat('hh:mm a')
      let progress = 0;
      //quantity of prayer counts
      if ( !coverage[time_formatted] ){
        progress = 0
      } else {
        if ( window.campaign_data.end_timestamp ){
            progress = window.campaign_scripts.time_slot_coverage?.[time_formatted]?.length / window.campaign_scripts.time_label_counts[time_formatted] * 100
        } else {
            progress = coverage[time_formatted].length / ( next_month.length - 1 ) * 100
        }
      }
      progress = progress.toFixed(1)
      let min = time.toFormat(':mm')
      let selected = (window.campaign_user_data.recurring_signups||[]).find(r=>r.type==='daily' && key >= r.time && key < (r.time + r.duration * 60) )
      let coverage_count = progress >= 100 ? Math.min(...(coverage[time_formatted] || [0])) : 0
      coverage_count += selected ? 1 : 0

      options.push({
        key: key,
        time_formatted: time_formatted,
        hour_minute: time.toFormat('hh:mm'),
        minute: min,
        hour: time.toLocaleString({ hour: '2-digit' }),
        progress,
        selected,
        coverage_count: coverage_count
      })
      key += window.campaign_data.slot_length * 60
    }
    return options;
  }
  get_weekly_times(){
    let start_of_time_frame = window.luxon.DateTime.now({zone:window.campaign_user_data.timezone})
    if ( start_of_time_frame.toSeconds() < window.campaign_data.start_timestamp ){
      start_of_time_frame = window.luxon.DateTime.fromSeconds(window.campaign_data.start_timestamp, {zone:window.campaign_user_data.timezone})
    }

    let time_frame_day_start = start_of_time_frame.startOf('day').toSeconds()
    let next_month = this.days.filter(d=>{
      return d.key > time_frame_day_start &&
        d.key <= start_of_time_frame.plus({months:1}).toSeconds() &&
        d.weekday_number === this.weekday
    })
    let coverage = {}
    next_month.forEach(d=>{
      d.slots.forEach(s=>{
        if ( s.key >= start_of_time_frame.toSeconds() && s.subscribers ){
          if ( !coverage[s.formatted] ){
            coverage[s.formatted] = []
          }
          coverage[s.formatted].push(s.subscribers)
        }
      })
    })

    let options = [];
    let key = 0;
    while (key < day_in_seconds) {
      let time = window.luxon.DateTime.fromSeconds(time_frame_day_start + key)
      let time_formatted = time.toFormat('hh:mm a')
      let progress = (
        coverage[time_formatted] ? coverage[time_formatted].length / next_month.length * 100 : 0
      ).toFixed(1)
      let min = time.toFormat(':mm')
      const selected = (window.campaign_user_data.recurring_signups||[]).find(r=>r.type==='weekly' && r.week_day===this.weekday && key >= r.time && key < (r.time + r.duration * 60));
      options.push({
        key: key,
        time_formatted: time_formatted,
        minute: min,
        hour: time.toLocaleString({ hour: '2-digit' }),
        hour_minute: time.toFormat('hh:mm'),
        progress,
        coverage_count: ( progress >= 100 ? Math.min(...(coverage[time_formatted] || [0])) : 0 ) + (selected ? 1 : 0),
        selected: selected
      })
      key += this.slot_length * 60
    }
    return options;
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
            ${translate('Almost there! Finish signing up by activating your account.')}
        </p>

        <p style="text-align: start">
            ${translate('Click the "Activate Account" button in the email sent to: %s').replace('%s', '')}
            <strong>${this.email}</strong>
        </p>

        <p style="text-align: start">
            ${translate('It will look like this:')}
        </p>
        <p style="margin-top: 1rem; margin-bottom: 1rem; border:1px solid; border-radius: 5px; padding: 4px">
            <img style="width: 100%" src="${window.campaign_objects.plugin_url}assets/activate_account.gif"/>
        </p>
      </div>

    `

  }
}
customElements.define('cp-verify', cpVerify);

export class cpProgressRing extends LitElement {
  static styles = [
    css`
    :host {
      display: block;
      --pi: 3.14159265358979;
      --radius: 50cqi;
      --stroke-width: max(3px, 5cqi);
      --normalized-radius: calc(var(--radius) - var(--stroke-width));
      --normalized-radius2: calc(var(--radius) - var(--stroke-width) / 2 + 1);
      --circumference: calc(var(--normalized-radius) * 2 * var(--pi));
      --circumference2: calc(var(--normalized-radius2) * 2 * var(--pi));

      --offset2: calc((var(--progress) / 100 * var(--circumference))*-1);
      --offset: calc( var(--circumference) + var(--offset2));
      --offset3: calc( var(--circumference2) - var(--progress2) / 100 * var(--circumference2));


      height: 95%;
      width: 95%;
      container-type: inline-size;
    }
    .inner-text {
      font-size: clamp(1em, 0.5em + 3cqi, 1.25rem);
    }

    svg {
      width: 100cqi;
      height: 100cqi;
    }

    circle {
      transition: stroke-dashoffset 0.35s;
      transform: rotate(-90deg);
      transform-origin: 50% 50%;
      stroke-width: var(--stroke-width);
      stroke-dasharray: var(--circumference) var(--circumference);
      r: var(--normalized-radius);
      cx: var(--radius);
      cy: var(--radius);
    }

    circle.first-circle {
      stroke-dashoffset: var(--offset);
    }
    circle.second-circle {
      stroke-dashoffset: var(--offset2);
    }
    `
  ]

  static properties = {
    text: {type: String},
    progress: {type: Number},
    progress2: {type: Number},
    font_size: {type: Number},
    color: {type: String},
  }

  constructor() {
    super();
    this.font_size = 15
    this.color = 'dodgerblue'
    this.progress = 0;
    this.text = ''
  }

  render() {
    this.progress = parseInt(this.progress).toFixed()
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
      <svg>
           <circle
             class="first-circle"
             stroke="${this.color}"
             fill="transparent"
          />
          <circle
             class="second-circle"
             stroke="${this.color}"
             stroke-opacity="0.1"
             fill="transparent"
          />
          <text class="inner-text" x="50%" y="50%" text-anchor="middle" stroke-width="2px" font-size="1em" dy=".3em">
              ${window.campaign_scripts.escapeHTML(this.text)}
          </text>
      </svg>
      <style>
        :host{
          --progress: ${Math.min(this.progress, 100)};
          --progress2: ${this.progress2};
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


export class cpPhoneNumber extends LitElement {
  static styles = [
    css`
        option::before {
            content: attr(data-before);
            margin-right: 8px;
       }
    `
  ]

  static properties = {
    prop: {type: String},
  }

  constructor() {
    super();
  }

  countries_list() {
    return [
      {
      "name": "Afghanistan",
      "dial_code": "+93",
      "emoji": "🇦🇫",
      "code": "AF"
      },
      {
        "name": "Aland Islands",
        "dial_code": "+358",
        "emoji": "🇦🇽",
        "code": "AX"
      },
      {
        "name": "Albania",
        "dial_code": "+355",
        "emoji": "🇦🇱",
        "code": "AL"
      },
      {
        "name": "Algeria",
        "dial_code": "+213",
        "emoji": "🇩🇿",
        "code": "DZ"
      },
      {
        "name": "AmericanSamoa",
        "dial_code": "+1684",
        "emoji": "🇦🇸",
        "code": "AS"
      },
      {
        "name": "Andorra",
        "dial_code": "+376",
        "emoji": "🇦🇩",
        "code": "AD"
      },
      {
        "name": "Angola",
        "dial_code": "+244",
        "emoji": "🇦🇴",
        "code": "AO"
      },
      {
        "name": "Anguilla",
        "dial_code": "+1264",
        "emoji": "🇦🇮",
        "code": "AI"
      },
      {
        "name": "Antarctica",
        "dial_code": "+672",
        "emoji": "🇦🇶",
        "code": "AQ"
      },
      {
        "name": "Antigua and Barbuda",
        "dial_code": "+1268",
        "emoji": "🇦🇬",
        "code": "AG"
      },
      {
        "name": "Argentina",
        "dial_code": "+54",
        "emoji": "🇦🇷",
        "code": "AR"
      },
      {
        "name": "Armenia",
        "dial_code": "+374",
        "emoji": "🇦🇲",
        "code": "AM"
      },
      {
        "name": "Aruba",
        "dial_code": "+297",
        "emoji": "🇦🇼",
        "code": "AW"
      },
      {
        "name": "Australia",
        "dial_code": "+61",
        "emoji": "🇦🇺",
        "code": "AU"
      },
      {
        "name": "Austria",
        "dial_code": "+43",
        "emoji": "🇦🇹",
        "code": "AT"
      },
      {
        "name": "Azerbaijan",
        "dial_code": "+994",
        "emoji": "🇦🇿",
        "code": "AZ"
      },
      {
        "name": "Bahamas",
        "dial_code": "+1242",
        "emoji": "🇧🇸",
        "code": "BS"
      },
      {
        "name": "Bahrain",
        "dial_code": "+973",
        "emoji": "🇧🇭",
        "code": "BH"
      },
      {
        "name": "Bangladesh",
        "dial_code": "+880",
        "emoji": "🇧🇩",
        "code": "BD"
      },
      {
        "name": "Barbados",
        "dial_code": "+1246",
        "emoji": "🇧🇧",
        "code": "BB"
      },
      {
        "name": "Belarus",
        "dial_code": "+375",
        "emoji": "🇧🇾",
        "code": "BY"
      },
      {
        "name": "Belgium",
        "dial_code": "+32",
        "emoji": "🇧🇪",
        "code": "BE"
      },
      {
        "name": "Belize",
        "dial_code": "+501",
        "emoji": "🇧🇿",
        "code": "BZ"
      },
      {
        "name": "Benin",
        "dial_code": "+229",
        "emoji": "🇧🇯",
        "code": "BJ"
      },
      {
        "name": "Bermuda",
        "dial_code": "+1441",
        "emoji": "🇧🇲",
        "code": "BM"
      },
      {
        "name": "Bhutan",
        "dial_code": "+975",
        "emoji": "🇧🇹",
        "code": "BT"
      },
      {
        "name": "Bolivia, Plurinational State of",
        "dial_code": "+591",
        "emoji": "🇧🇴",
        "code": "BO"
      },
      {
        "name": "Bosnia and Herzegovina",
        "dial_code": "+387",
        "emoji": "🇧🇦",
        "code": "BA"
      },
      {
        "name": "Botswana",
        "dial_code": "+267",
        "emoji": "🇧🇼",
        "code": "BW"
      },
      {
        "name": "Brazil",
        "dial_code": "+55",
        "emoji": "🇧🇷",
        "code": "BR"
      },
      {
        "name": "British Indian Ocean Territory",
        "dial_code": "+246",
        "emoji": "🇮🇴",
        "code": "IO"
      },
      {
        "name": "Brunei Darussalam",
        "dial_code": "+673",
        "emoji": "🇧🇳",
        "code": "BN"
      },
      {
        "name": "Bulgaria",
        "dial_code": "+359",
        "emoji": "🇧🇬",
        "code": "BG"
      },
      {
        "name": "Burkina Faso",
        "dial_code": "+226",
        "emoji": "🇧🇫",
        "code": "BF"
      },
      {
        "name": "Burundi",
        "dial_code": "+257",
        "emoji": "🇧🇮",
        "code": "BI"
      },
      {
        "name": "Cambodia",
        "dial_code": "+855",
        "emoji": "🇰🇭",
        "code": "KH"
      },
      {
        "name": "Cameroon",
        "dial_code": "+237",
        "emoji": "🇨🇲",
        "code": "CM"
      },
      {
        "name": "Canada",
        "dial_code": "+1",
        "emoji": "🇨🇦",
        "code": "CA"
      },
      {
        "name": "Cape Verde",
        "dial_code": "+238",
        "emoji": "🇨🇻",
        "code": "CV"
      },
      {
        "name": "Cayman Islands",
        "dial_code": "+345",
        "emoji": "🇰🇾",
        "code": "KY"
      },
      {
        "name": "Central African Republic",
        "dial_code": "+236",
        "emoji": "🇨🇫",
        "code": "CF"
      },
      {
        "name": "Chad",
        "dial_code": "+235",
        "emoji": "🇹🇩",
        "code": "TD"
      },
      {
        "name": "Chile",
        "dial_code": "+56",
        "emoji": "🇨🇱",
        "code": "CL"
      },
      {
        "name": "China",
        "dial_code": "+86",
        "emoji": "🇨🇳",
        "code": "CN"
      },
      {
        "name": "Christmas Island",
        "dial_code": "+61",
        "emoji": "🇨🇽",
        "code": "CX"
      },
      {
        "name": "Cocos (Keeling) Islands",
        "dial_code": "+61",
        "emoji": "🇨🇨",
        "code": "CC"
      },
      {
        "name": "Colombia",
        "dial_code": "+57",
        "emoji": "🇨🇴",
        "code": "CO"
      },
      {
        "name": "Comoros",
        "dial_code": "+269",
        "emoji": "🇰🇲",
        "code": "KM"
      },
      {
        "name": "Congo",
        "dial_code": "+242",
        "emoji": "🇨🇬",
        "code": "CG"
      },
      {
        "name": "Congo, The Democratic Republic of the Congo",
        "dial_code": "+243",
        "emoji": "🇨🇩",
        "code": "CD"
      },
      {
        "name": "Cook Islands",
        "dial_code": "+682",
        "emoji": "🇨🇰",
        "code": "CK"
      },
      {
        "name": "Costa Rica",
        "dial_code": "+506",
        "emoji": "🇨🇷",
        "code": "CR"
      },
      {
        "name": "Cote d'Ivoire",
        "dial_code": "+225",
        "emoji": "🇨🇮",
        "code": "CI"
      },
      {
        "name": "Croatia",
        "dial_code": "+385",
        "emoji": "🇭🇷",
        "code": "HR"
      },
      {
        "name": "Cuba",
        "dial_code": "+53",
        "emoji": "🇨🇺",
        "code": "CU"
      },
      {
        "name": "Cyprus",
        "dial_code": "+357",
        "emoji": "🇨🇾",
        "code": "CY"
      },
      {
        "name": "Czech Republic",
        "dial_code": "+420",
        "emoji": "🇨🇿",
        "code": "CZ"
      },
      {
        "name": "Denmark",
        "dial_code": "+45",
        "emoji": "🇩🇰",
        "code": "DK"
      },
      {
        "name": "Djibouti",
        "dial_code": "+253",
        "emoji": "🇩🇯",
        "code": "DJ"
      },
      {
        "name": "Dominica",
        "dial_code": "+1767",
        "emoji": "🇩🇲",
        "code": "DM"
      },
      {
        "name": "Dominican Republic",
        "dial_code": "+1849",
        "emoji": "🇩🇴",
        "code": "DO"
      },
      {
        "name": "Ecuador",
        "dial_code": "+593",
        "emoji": "🇪🇨",
        "code": "EC"
      },
      {
        "name": "Egypt",
        "dial_code": "+20",
        "emoji": "🇪🇬",
        "code": "EG"
      },
      {
        "name": "El Salvador",
        "dial_code": "+503",
        "emoji": "🇸🇻",
        "code": "SV"
      },
      {
        "name": "Equatorial Guinea",
        "dial_code": "+240",
        "emoji": "🇬🇶",
        "code": "GQ"
      },
      {
        "name": "Eritrea",
        "dial_code": "+291",
        "emoji": "🇪🇷",
        "code": "ER"
      },
      {
        "name": "Estonia",
        "dial_code": "+372",
        "emoji": "🇪🇪",
        "code": "EE"
      },
      {
        "name": "Ethiopia",
        "dial_code": "+251",
        "emoji": "🇪🇹",
        "code": "ET"
      },
      {
        "name": "Falkland Islands (Malvinas)",
        "dial_code": "+500",
        "emoji": "🇫🇰",
        "code": "FK"
      },
      {
        "name": "Faroe Islands",
        "dial_code": "+298",
        "emoji": "🇫🇴",
        "code": "FO"
      },
      {
        "name": "Fiji",
        "dial_code": "+679",
        "emoji": "🇫🇯",
        "code": "FJ"
      },
      {
        "name": "Finland",
        "dial_code": "+358",
        "emoji": "🇫🇮",
        "code": "FI"
      },
      {
        "name": "France",
        "dial_code": "+33",
        "emoji": "🇫🇷",
        "code": "FR"
      },
      {
        "name": "French Guiana",
        "dial_code": "+594",
        "emoji": "🇬🇫",
        "code": "GF"
      },
      {
        "name": "French Polynesia",
        "dial_code": "+689",
        "emoji": "🇵🇫",
        "code": "PF"
      },
      {
        "name": "Gabon",
        "dial_code": "+241",
        "emoji": "🇬🇦",
        "code": "GA"
      },
      {
        "name": "Gambia",
        "dial_code": "+220",
        "emoji": "🇬🇲",
        "code": "GM"
      },
      {
        "name": "Georgia",
        "dial_code": "+995",
        "emoji": "🇬🇪",
        "code": "GE"
      },
      {
        "name": "Germany",
        "dial_code": "+49",
        "emoji": "🇩🇪",
        "code": "DE"
      },
      {
        "name": "Ghana",
        "dial_code": "+233",
        "emoji": "🇬🇭",
        "code": "GH"
      },
      {
        "name": "Gibraltar",
        "dial_code": "+350",
        "emoji": "🇬🇮",
        "code": "GI"
      },
      {
        "name": "Greece",
        "dial_code": "+30",
        "emoji": "🇬🇷",
        "code": "GR"
      },
      {
        "name": "Greenland",
        "dial_code": "+299",
        "emoji": "🇬🇱",
        "code": "GL"
      },
      {
        "name": "Grenada",
        "dial_code": "+1473",
        "emoji": "🇬🇩",
        "code": "GD"
      },
      {
        "name": "Guadeloupe",
        "dial_code": "+590",
        "emoji": "🇬🇵",
        "code": "GP"
      },
      {
        "name": "Guam",
        "dial_code": "+1671",
        "emoji": "🇬🇺",
        "code": "GU"
      },
      {
        "name": "Guatemala",
        "dial_code": "+502",
        "emoji": "🇬🇹",
        "code": "GT"
      },
      {
        "name": "Guernsey",
        "dial_code": "+44",
        "emoji": "🇬🇬",
        "code": "GG"
      },
      {
        "name": "Guinea",
        "dial_code": "+224",
        "emoji": "🇬🇳",
        "code": "GN"
      },
      {
        "name": "Guinea-Bissau",
        "dial_code": "+245",
        "emoji": "🇬🇼",
        "code": "GW"
      },
      {
        "name": "Guyana",
        "dial_code": "+595",
        "emoji": "🇬🇾",
        "code": "GY"
      },
      {
        "name": "Haiti",
        "dial_code": "+509",
        "emoji": "🇭🇹",
        "code": "HT"
      },
      {
        "name": "Holy See (Vatican City State)",
        "dial_code": "+379",
        "emoji": "🇻🇦",
        "code": "VA"
      },
      {
        "name": "Honduras",
        "dial_code": "+504",
        "emoji": "🇭🇳",
        "code": "HN"
      },
      {
        "name": "Hong Kong",
        "dial_code": "+852",
        "emoji": "🇭🇰",
        "code": "HK"
      },
      {
        "name": "Hungary",
        "dial_code": "+36",
        "emoji": "🇭🇺",
        "code": "HU"
      },
      {
        "name": "Iceland",
        "dial_code": "+354",
        "emoji": "🇮🇸",
        "code": "IS"
      },
      {
        "name": "India",
        "dial_code": "+91",
        "emoji": "🇮🇳",
        "code": "IN"
      },
      {
        "name": "Indonesia",
        "dial_code": "+62",
        "emoji": "🇮🇩",
        "code": "ID"
      },
      {
        "name": "Iran, Islamic Republic of Persian Gulf",
        "dial_code": "+98",
        "emoji": "🇮🇷",
        "code": "IR"
      },
      {
        "name": "Iraq",
        "dial_code": "+964",
        "emoji": "🇮🇷",
        "code": "IQ"
      },
      {
        "name": "Ireland",
        "dial_code": "+353",
        "emoji": "🇮🇪",
        "code": "IE"
      },
      {
        "name": "Isle of Man",
        "dial_code": "+44",
        "emoji": "🇮🇲",
        "code": "IM"
      },
      {
        "name": "Israel",
        "dial_code": "+972",
        "emoji": "🇮🇱",
        "code": "IL"
      },
      {
        "name": "Italy",
        "dial_code": "+39",
        "emoji": "🇮🇹",
        "code": "IT"
      },
      {
        "name": "Jamaica",
        "dial_code": "+1876",
        "emoji": "🇯🇲",
        "code": "JM"
      },
      {
        "name": "Japan",
        "dial_code": "+81",
        "emoji": "🇯🇵",
        "code": "JP"
      },
      {
        "name": "Jersey",
        "dial_code": "+44",
        "emoji": "🇯🇪",
        "code": "JE"
      },
      {
        "name": "Jordan",
        "dial_code": "+962",
        "emoji": "🇯🇴",
        "code": "JO"
      },
      {
        "name": "Kazakhstan",
        "dial_code": "+77",
        "emoji": "🇰🇿",
        "code": "KZ"
      },
      {
        "name": "Kenya",
        "dial_code": "+254",
        "emoji": "🇰🇪",
        "code": "KE"
      },
      {
        "name": "Kiribati",
        "dial_code": "+686",
        "emoji": "🇰🇮",
        "code": "KI"
      },
      {
        "name": "Korea, Democratic People's Republic of Korea",
        "dial_code": "+850",
        "emoji": "🇰🇵",
        "code": "KP"
      },
      {
        "name": "Korea, Republic of South Korea",
        "dial_code": "+82",
        "emoji": "🇰🇷",
        "code": "KR"
      },
      {
        "name": "Kuwait",
        "dial_code": "+965",
        "emoji": "🇰🇼",
        "code": "KW"
      },
      {
        "name": "Kyrgyzstan",
        "dial_code": "+996",
        "emoji": "🇰🇬",
        "code": "KG"
      },
      {
        "name": "Laos",
        "dial_code": "+856",
        "emoji": "🇱🇦",
        "code": "LA"
      },
      {
        "name": "Latvia",
        "dial_code": "+371",
        "emoji": "🇱🇻",
        "code": "LV"
      },
      {
        "name": "Lebanon",
        "dial_code": "+961",
        "emoji": "🇱🇧",
        "code": "LB"
      },
      {
        "name": "Lesotho",
        "dial_code": "+266",
        "emoji": "🇱🇸",
        "code": "LS"
      },
      {
        "name": "Liberia",
        "dial_code": "+231",
        "emoji": "🇱🇷",
        "code": "LR"
      },
      {
        "name": "Libyan Arab Jamahiriya",
        "dial_code": "+218",
        "emoji": "🇱🇾",
        "code": "LY"
      },
      {
        "name": "Liechtenstein",
        "dial_code": "+423",
        "emoji": "🇱🇮",
        "code": "LI"
      },
      {
        "name": "Lithuania",
        "dial_code": "+370",
        "emoji": "🇱🇹",
        "code": "LT"
      },
      {
        "name": "Luxembourg",
        "dial_code": "+352",
        "emoji": "🇱🇺",
        "code": "LU"
      },
      {
        "name": "Macao",
        "dial_code": "+853",
        "emoji": "🇲🇴",
        "code": "MO"
      },
      {
        "name": "Macedonia",
        "dial_code": "+389",
        "emoji": "🇲🇰",
        "code": "MK"
      },
      {
        "name": "Madagascar",
        "dial_code": "+261",
        "emoji": "🇲🇬",
        "code": "MG"
      },
      {
        "name": "Malawi",
        "dial_code": "+265",
        "emoji": "🇲🇼",
        "code": "MW"
      },
      {
        "name": "Malaysia",
        "dial_code": "+60",
        "emoji": "🇲🇾",
        "code": "MY"
      },
      {
        "name": "Maldives",
        "dial_code": "+960",
        "emoji": "🇲🇻",
        "code": "MV"
      },
      {
        "name": "Mali",
        "dial_code": "+223",
        "emoji": "🇲🇱",
        "code": "ML"
      },
      {
        "name": "Malta",
        "dial_code": "+356",
        "emoji": "🇲🇹",
        "code": "MT"
      },
      {
        "name": "Marshall Islands",
        "dial_code": "+692",
        "emoji": "🇲🇭",
        "code": "MH"
      },
      {
        "name": "Martinique",
        "dial_code": "+596",
        "emoji": "🇲🇶",
        "code": "MQ"
      },
      {
        "name": "Mauritania",
        "dial_code": "+222",
        "emoji": "🇲🇷",
        "code": "MR"
      },
      {
        "name": "Mauritius",
        "dial_code": "+230",
        "emoji": "🇲🇺",
        "code": "MU"
      },
      {
        "name": "Mayotte",
        "dial_code": "+262",
        "emoji": "🇾🇹",
        "code": "YT"
      },
      {
        "name": "Mexico",
        "dial_code": "+52",
        "emoji": "🇲🇽",
        "code": "MX"
      },
      {
        "name": "Micronesia, Federated States of Micronesia",
        "dial_code": "+691",
        "emoji": "🇫🇲",
        "code": "FM"
      },
      {
        "name": "Moldova",
        "dial_code": "+373",
        "emoji": "🇲🇩",
        "code": "MD"
      },
      {
        "name": "Monaco",
        "dial_code": "+377",
        "emoji": "🇲🇨",
        "code": "MC"
      },
      {
        "name": "Mongolia",
        "dial_code": "+976",
        "emoji": "🇲🇳",
        "code": "MN"
      },
      {
        "name": "Montenegro",
        "dial_code": "+382",
        "emoji": "🇲🇪",
        "code": "ME"
      },
      {
        "name": "Montserrat",
        "dial_code": "+1664",
        "emoji": "🇲🇸",
        "code": "MS"
      },
      {
        "name": "Morocco",
        "dial_code": "+212",
        "emoji": "🇲🇦",
        "code": "MA"
      },
      {
        "name": "Mozambique",
        "dial_code": "+258",
        "emoji": "🇲🇿",
        "code": "MZ"
      },
      {
        "name": "Myanmar",
        "dial_code": "+95",
        "emoji": "🇲🇲",
        "code": "MM"
      },
      {
        "name": "Namibia",
        "emoji": "🇳🇦",
        "dial_code": "+264",
        "code": "NA"
      },
      {
        "name": "Nauru",
        "dial_code": "+674",
        "emoji": "🇳🇷",
        "code": "NR"
      },
      {
        "name": "Nepal",
        "dial_code": "+977",
        "emoji": "🇳🇵",
        "code": "NP"
      },
      {
        "name": "Netherlands",
        "dial_code": "+31",
        "emoji": "🇳🇱",
        "code": "NL"
      },
      {
        "name": "Netherlands Antilles",
        "dial_code": "+599",
        "emoji": "🇧🇶",
        "code": "AN"
      },
      {
        "name": "New Caledonia",
        "dial_code": "+687",
        "emoji": "🇳🇨",
        "code": "NC"
      },
      {
        "name": "New Zealand",
        "dial_code": "+64",
        "emoji": "🇳🇿",
        "code": "NZ"
      },
      {
        "name": "Nicaragua",
        "dial_code": "+505",
        "emoji": "🇳🇮",
        "code": "NI"
      },
      {
        "name": "Niger",
        "dial_code": "+227",
        "emoji": "🇳🇪",
        "code": "NE"
      },
      {
        "name": "Nigeria",
        "dial_code": "+234",
        "emoji": "🇳🇬",
        "code": "NG"
      },
      {
        "name": "Niue",
        "dial_code": "+683",
        "emoji": "🇳🇺",
        "code": "NU"
      },
      {
        "name": "Norfolk Island",
        "dial_code": "+672",
        "emoji": "🇳🇫",
        "code": "NF"
      },
      {
        "name": "Northern Mariana Islands",
        "dial_code": "+1670",
        "emoji": "🇲🇵",
        "code": "MP"
      },
      {
        "name": "Norway",
        "dial_code": "+47",
        "emoji": "🇳🇴",
        "code": "NO"
      },
      {
        "name": "Oman",
        "dial_code": "+968",
        "emoji": "🇴🇲",
        "code": "OM"
      },
      {
        "name": "Pakistan",
        "dial_code": "+92",
        "emoji": "🇵🇰",
        "code": "PK"
      },
      {
        "name": "Palau",
        "dial_code": "+680",
        "emoji": "🇵🇼",
        "code": "PW"
      },
      {
        "name": "Palestinian Territory, Occupied",
        "dial_code": "+970",
        "emoji": "🇵🇸",
        "code": "PS"
      },
      {
        "name": "Panama",
        "dial_code": "+507",
        "emoji": "🇵🇦",
        "code": "PA"
      },
      {
        "name": "Papua New Guinea",
        "dial_code": "+675",
        "emoji": "🇵🇬",
        "code": "PG"
      },
      {
        "name": "Paraguay",
        "dial_code": "+595",
        "emoji": "🇵🇾",
        "code": "PY"
      },
      {
        "name": "Peru",
        "dial_code": "+51",
        "emoji": "🇵🇪",
        "code": "PE"
      },
      {
        "name": "Philippines",
        "dial_code": "+63",
        "emoji": "🇵🇭",
        "code": "PH"
      },
      {
        "name": "Pitcairn",
        "dial_code": "+872",
        "emoji": "🇵🇳",
        "code": "PN"
      },
      {
        "name": "Poland",
        "dial_code": "+48",
        "emoji": "🇵🇱",
        "code": "PL"
      },
      {
        "name": "Portugal",
        "dial_code": "+351",
        "emoji": "🇵🇹",
        "code": "PT"
      },
      {
        "name": "Puerto Rico",
        "dial_code": "+1939",
        "emoji": "🇵🇷",
        "code": "PR"
      },
      {
        "name": "Qatar",
        "dial_code": "+974",
        "emoji": "🇶🇦",
        "code": "QA"
      },
      {
        "name": "Romania",
        "dial_code": "+40",
        "emoji": "🇷🇴",
        "code": "RO"
      },
      {
        "name": "Russia",
        "dial_code": "+7",
        "emoji": "🇷🇺",
        "code": "RU"
      },
      {
        "name": "Rwanda",
        "dial_code": "+250",
        "emoji": "🇷🇼",
        "code": "RW"
      },
      {
        "name": "Reunion",
        "dial_code": "+262",
        "emoji": "🇷🇪",
        "code": "RE"
      },
      {
        "name": "Saint Barthelemy",
        "dial_code": "+590",
        "emoji": "🇧🇱",
        "code": "BL"
      },
      {
        "name": "Saint Helena, Ascension and Tristan Da Cunha",
        "dial_code": "+290",
        "emoji": "🇸🇭",
        "code": "SH"
      },
      {
        "name": "Saint Kitts and Nevis",
        "dial_code": "+1869",
        "emoji": "🇰🇳",
        "code": "KN"
      },
      {
        "name": "Saint Lucia",
        "dial_code": "+1758",
        "emoji": "🇱🇨",
        "code": "LC"
      },
      {
        "name": "Saint Martin",
        "dial_code": "+590",
        "emoji": "🇲🇫",
        "code": "MF"
      },
      {
        "name": "Saint Pierre and Miquelon",
        "dial_code": "+508",
        "emoji": "🇵🇲",
        "code": "PM"
      },
      {
        "name": "Saint Vincent and the Grenadines",
        "dial_code": "+1784",
        "emoji": "🇻🇨",
        "code": "VC"
      },
      {
        "name": "Samoa",
        "dial_code": "+685",
        "emoji": "🇼🇸",
        "code": "WS"
      },
      {
        "name": "San Marino",
        "dial_code": "+378",
        "emoji": "🇸🇲",
        "code": "SM"
      },
      {
        "name": "Sao Tome and Principe",
        "dial_code": "+239",
        "emoji": "🇸🇹",
        "code": "ST"
      },
      {
        "name": "Saudi Arabia",
        "dial_code": "+966",
        "emoji": "🇸🇦",
        "code": "SA"
      },
      {
        "name": "Senegal",
        "dial_code": "+221",
        "emoji": "🇸🇳",
        "code": "SN"
      },
      {
        "name": "Serbia",
        "dial_code": "+381",
        "emoji": "🇷🇸",
        "code": "RS"
      },
      {
        "name": "Seychelles",
        "dial_code": "+248",
        "emoji": "🇸🇨",
        "code": "SC"
      },
      {
        "name": "Sierra Leone",
        "dial_code": "+232",
        "emoji": "🇸🇱",
        "code": "SL"
      },
      {
        "name": "Singapore",
        "dial_code": "+65",
        "emoji": "🇸🇬",
        "code": "SG"
      },
      {
        "name": "Slovakia",
        "dial_code": "+421",
        "emoji": "🇸🇰",
        "code": "SK"
      },
      {
        "name": "Slovenia",
        "dial_code": "+386",
        "emoji": "🇸🇮",
        "code": "SI"
      },
      {
        "name": "Solomon Islands",
        "dial_code": "+677",
        "emoji": "🇸🇧",
        "code": "SB"
      },
      {
        "name": "Somalia",
        "dial_code": "+252",
        "emoji": "🇸🇴",
        "code": "SO"
      },
      {
        "name": "South Africa",
        "dial_code": "+27",
        "emoji": "🇿🇦",
        "code": "ZA"
      },
      {
        "name": "South Sudan",
        "dial_code": "+211",
        "emoji": "🇸🇸",
        "code": "SS"
      },
      {
        "name": "South Georgia and the South Sandwich Islands",
        "dial_code": "+500",
        "emoji": "🇬🇸",
        "code": "GS"
      },
      {
        "name": "Spain",
        "dial_code": "+34",
        "emoji": "🇪🇸",
        "code": "ES"
      },
      {
        "name": "Sri Lanka",
        "dial_code": "+94",
        "emoji": "🇱🇰",
        "code": "LK"
      },
      {
        "name": "Sudan",
        "dial_code": "+249",
        "emoji": "🇸🇩",
        "code": "SD"
      },
      {
        "name": "Suriname",
        "dial_code": "+597",
        "emoji": "🇸🇷",
        "code": "SR"
      },
      {
        "name": "Svalbard and Jan Mayen",
        "dial_code": "+47",
        "emoji": "🇸🇯",
        "code": "SJ"
      },
      {
        "name": "Swaziland",
        "dial_code": "+268",
        "emoji": "🇸🇿",
        "code": "SZ"
      },
      {
        "name": "Sweden",
        "dial_code": "+46",
        "emoji": "🇸🇪",
        "code": "SE"
      },
      {
        "name": "Switzerland",
        "dial_code": "+41",
        "emoji": "🇨🇭",
        "code": "CH"
      },
      {
        "name": "Syrian Arab Republic",
        "dial_code": "+963",
        "emoji": "🇸🇾",
        "code": "SY"
      },
      {
        "name": "Taiwan",
        "dial_code": "+886",
        "emoji": "🇹🇼",
        "code": "TW"
      },
      {
        "name": "Tajikistan",
        "dial_code": "+992",
        "emoji": "🇹🇯",
        "code": "TJ"
      },
      {
        "name": "Tanzania, United Republic of Tanzania",
        "dial_code": "+255",
        "emoji": "🇹🇿",
        "code": "TZ"
      },
      {
        "name": "Thailand",
        "dial_code": "+66",
        "emoji": "🇹🇭",
        "code": "TH"
      },
      {
        "name": "Timor-Leste",
        "dial_code": "+670",
        "emoji": "🇹🇱",
        "code": "TL"
      },
      {
        "name": "Togo",
        "dial_code": "+228",
        "emoji": "🇹🇬",
        "code": "TG"
      },
      {
        "name": "Tokelau",
        "dial_code": "+690",
        "emoji": "🇹🇰",
        "code": "TK"
      },
      {
        "name": "Tonga",
        "dial_code": "+676",
        "emoji": "🇹🇴",
        "code": "TO"
      },
      {
        "name": "Trinidad and Tobago",
        "dial_code": "+1868",
        "emoji": "🇹🇹",
        "code": "TT"
      },
      {
        "name": "Tunisia",
        "dial_code": "+216",
        "emoji": "🇹🇳",
        "code": "TN"
      },
      {
        "name": "Turkey",
        "dial_code": "+90",
        "emoji": "🇹🇷",
        "code": "TR"
      },
      {
        "name": "Turkmenistan",
        "dial_code": "+993",
        "emoji": "🇹🇲",
        "code": "TM"
      },
      {
        "name": "Turks and Caicos Islands",
        "dial_code": "+1649",
        "emoji": "🇹🇨",
        "code": "TC"
      },
      {
        "name": "Tuvalu",
        "dial_code": "+688",
        "emoji": "🇹🇻",
        "code": "TV"
      },
      {
        "name": "Uganda",
        "dial_code": "+256",
        "emoji": "🇺🇬",
        "code": "UG"
      },
      {
        "name": "Ukraine",
        "dial_code": "+380",
        "emoji": "🇺🇦",
        "code": "UA"
      },
      {
        "name": "United Arab Emirates",
        "dial_code": "+971",
        "emoji": "🇦🇪",
        "code": "AE"
      },
      {
        "name": "United Kingdom",
        "dial_code": "+44",
        "emoji": "🇬🇧",
        "code": "GB"
      },
      {
        "name": "United States",
        "dial_code": "+1",
        "emoji": "🇺🇸",
        "code": "US"
      },
      {
        "name": "Uruguay",
        "dial_code": "+598",
        "emoji": "🇺🇾",
        "code": "UY"
      },
      {
        "name": "Uzbekistan",
        "dial_code": "+998",
        "emoji": "🇺🇿",
        "code": "UZ"
      },
      {
        "name": "Vanuatu",
        "dial_code": "+678",
        "emoji": "🇻🇺",
        "code": "VU"
      },
      {
        "name": "Venezuela, Bolivarian Republic of Venezuela",
        "dial_code": "+58",
        "emoji": "🇻🇪",
        "code": "VE"
      },
      {
        "name": "Vietnam",
        "dial_code": "+84",
        "emoji": "🇻🇳",
        "code": "VN"
      },
      {
        "name": "Virgin Islands, British",
        "dial_code": "+1284",
        "emoji": "🇻🇬",
        "code": "VG"
      },
      {
        "name": "Virgin Islands, U.S.",
        "dial_code": "+1340",
        "emoji": "🇻🇮",
        "code": "VI"
      },
      {
        "name": "Wallis and Futuna",
        "dial_code": "+681",
        "emoji": "🇼🇫",
        "code": "WF"
      },
      {
        "name": "Yemen",
        "dial_code": "+967",
        "emoji": "🇾🇪",
        "code": "YE"
      },
      {
        "name": "Zambia",
        "dial_code": "+260",
        "emoji": "🇿🇲",
        "code": "ZM"
      },
      {
        "name": "Zimbabwe",
        "dial_code": "+263",
        "emoji": "🇿🇼",
        "code": "ZW"
      }
    ]
  }

  render() {
    return html`
      <div>
          <select id="countryCode" @change="${this._changeCountry}" style="width: 100px">
              
            ${this.countries_list().map(country => html`
                <option data-before="${country.emoji}" value="${country.dial_code}">${country.name} ${country.dial_code}</option>`)}
          </select>
          <input type="text" id="phoneNumber" placeholder="Phone Number">
      </div>
    `
  }
}
customElements.define('cp-phone-number', cpPhoneNumber);