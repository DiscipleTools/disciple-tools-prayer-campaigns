import {html, css, LitElement, range, map} from 'https://cdn.jsdelivr.net/gh/lit/dist@2/all/lit-all.min.js';
const strings = window.campaign_scripts.escapeObject(window.campaign_objects.translations)

/**
 * Timezone Picker Component
 */
export class CampaignSignUp extends LitElement {
  static styles = [
    window.campaignStyles,
    css`
      :host {
        position: relative;
        display: block;
        left: 50%;
        right: 50%;
        width: 100vw;
        margin: 0 -50vw;
        padding: 0 1rem;
        background-color: white;
      }
    `,
    css`
      .step-circle {
        border-radius: 100px;
        background-color: var(--cp-color);
        color: #fff;
        width: 2rem;
        height: 2rem;
        display: inline-flex;
        justify-content: center;
        align-items: center;
        margin-right: 0.5rem;
      }
      .section-title {
        font-size: 1.2rem;
      }
      .section-div {
        padding-bottom: 2rem;
      }
      label {
        display: block;
      }
      #campaign {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        column-gap: 3rem;
        font-size: 1rem;
        min-height: 500px;
      }


      .small {
        transform: scale(0.6);
      }
      .size-item {
        transition: transform .5s linear 0s;
        transform-style: preserve-3d;
        display:block;
      }
      .size-item.top-left {
        transform-origin: left top;
      }
      .size-item.top-right {
        transform-origin: right top;
      }
      
      .selected-times {
        background-color: rgba(70, 118, 250, 0.1);
        border-radius: 5px;
        margin-bottom: 1rem;
        padding: 1rem;
        display: flex;
        justify-content: space-between;
      }
      .selected-time-labels {
        display: flex;
      }
      .selected-time-labels ul{
        margin:0;
      }
      .selected-time-frequency {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
      }
      .mobile {
        display:none;
      }
      .desktop {
        display:block;
      }
      
      @media screen and (max-width: 600px) {
        .time {
          padding-inline-start: 0.3rem;
        }
        #campaign {
          grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        }
        .center-col {
          grid-column: span 1;
        }
        .time-label {
          padding-inline-start: 0.3rem;
        }
        .column {
          width: 100% !important;
          max-width: 100% !important;
        }
        .mobile {
          display:block;
        }
        .desktop {
          display:none;
        }
      }
      
      .section-div[disabled] {
        opacity: 0.5;
      }
      .place-indicator {
        color: orange;
        font-size: 1rem;
      }
      .remove-prayer-time-button {
        background: none;
        border: none;
        padding: .2rem;
        margin: 0;
        cursor: pointer;
        display: flex;
        justify-content: center;
      }
      .remove-prayer-time-button:hover {
        border: 1px solid red;    
      }
      .remove-prayer-time-button img {
        width: 1rem;
      }
      
      
    `
  ];

  static properties = {
    already_signed_up: {type: Boolean},
  }

  constructor() {
    super()
    this.campaign_data = {
      start_timestamp: 0,
      end_timestamp: 0,
      slot_length: 15,
      duration_options: {},
      coverage: {},
      enabled_frequencies: [],
    }
    this.already_signed_up = false;
    this._form_items = {
      email: '',
      name: '',
    }
    this.now = new Date().getTime()/1000
    this.selected_day = null;
    this.selected_times = [];
    this.recurring_signups = [];
    this.show_selected_times = false;
    this.timezone = window.campaign_user_data.timezone;
    this.days = [];

    this.get_campaign_data().then(()=>{
      this.frequency = {
        value: this.campaign_data.enabled_frequencies.length > 0 ? this.campaign_data.enabled_frequencies[0] : '',
      }
      this.duration = {
        value: 15,
        options: [
          {value: 15, label: `${strings['%s Minutes'].replace('%s', 15)}`},
          {value: 30, label: `${strings['%s Minutes'].replace('%s', 30)}`},
          {value: 60, label: `${strings['%s Hours'].replace('%s', 1)}`},
        ]
      }
      this.week_day = {
        value: '',
        options: [
          {value: '1', label: 'Mondays'},
          {value: '2', label: 'Tuesdays'},
          {value: '3', label: 'Wednesdays'},
          {value: '4', label: 'Thursdays'},
          {value: '5', label: 'Fridays'},
          {value: '6', label: 'Saturdays'},
          {value: '7', label: 'Sundays'},
        ]
      }
      this.requestUpdate()
    })
  }

  connectedCallback() {
    super.connectedCallback()

    window.addEventListener('campaign_timezone_change', (e)=>{
      this.timezone = e.detail.timezone
      this.days = window.campaign_scripts.days
      this.requestUpdate()
    });
  }
  selected_times_count(){
    let count = 0;
    this.recurring_signups.forEach(v=>{
      count += v.selected_times.length
    })
    count += this.selected_times.length
    return count;
  }

  get_campaign_data() {
    return window.campaign_scripts.get_campaign_data().then((data) => {
      this._view = 'main';
      this.campaign_data = {...this.campaign_data, ...data};
      this.days = window.campaign_scripts.days
      this.requestUpdate()
      return data
    })
  }

  submit(){
    this._loading = true;
    this.requestUpdate()

    let selected_times = this.selected_times;

    let data = {
      name: this._form_items.name,
      email: this._form_items.email,
      code: this._form_items.code,
      selected_times: selected_times,
      recurring_signups: this.recurring_signups,
    }

    window.campaign_scripts.submit_prayer_times(this.campaign_data.campaign_id, data)
    .done(()=>{
      this.selected_times = [];
      this._loading = false;
      if ( window.campaign_objects.remote === "1" || this.already_signed_up ){
        this._view = 'confirmation';
      } else {
        window.location.href = window.campaign_objects.home + '/prayer/email-confirmation';
      }
      this.requestUpdate()
    })
    .fail((e)=>{
      this._loading = false
      let message = html`So sorry. Something went wrong. Please, try again.<br>
          <a href="${window.campaign_scripts.escapeHTML(window.location.href)}">Try Again</a>`
      if ( e.status === 401 ) {
        message = 'Confirmation code does not match or is expired. Please, try again.'
      }
      this._form_items.code_error = message
      this.requestUpdate()
    })
  }
  handle_contact_info(e){
    this._form_items = e.detail
    this._loading = true;

    let data = {
      email: this._form_items.email,
      parts: window.campaign_objects.magic_link_parts,
      campaign_id: this.campaign_data.campaign_id,
      url: 'verify',
    }
    let link = window.campaign_objects.rest_url + window.campaign_objects.magic_link_parts.root + '/v1/' + window.campaign_objects.magic_link_parts.type + '/verify';
    if (window.campaign_objects.remote) {
      link = window.campaign_objects.rest_url + window.campaign_objects.magic_link_parts.root + '/v1/24hour-router';
    }
    jQuery.ajax({
      type: 'POST',
      data: JSON.stringify(data),
      contentType: 'application/json; charset=utf-8',
      dataType: 'json',
      url: link
    })
    .done(()=>{
      this._loading = false
      this._view = 'submit'
      this.requestUpdate()
      //scroll to #campaign
      let element = document.querySelector('#features')
      if ( !element ){
        element = this.shadowRoot.querySelector('#campaign')
      }
      element.scrollIntoView({behavior: "smooth", block: "start", inline: "nearest"});
    })
    .fail((e)=>{
      console.log(e);
      let message = `So sorry. Something went wrong. Please, contact us to help you through it, or just try again.<br>
        <a href="${window.campaign_scripts.escapeHTML(window.location.href)}">Try Again</a>`
      this._form_items.form_error = message
      this._loading = false
      this.requestUpdate()
    })
  }


  show_toast(message='', type='success'){
    if ( !message ){
      message = strings["Prayer Time Added"];
    }
    let background = 'linear-gradient(to right, var(--cp-color-dark), var(--cp-color-light))';
    if ( type === 'warn' ){
      background = 'linear-gradient(to right, #f8b500, #f8b500)';
    }
    Toastify({
      text: message,
      duration: 3000,
      close: true,
      gravity: "bottom",
      style: {
        background: background
      },
    }).showToast();
  }


  time_selected(selected_time){
    if (!this.frequency.value){
      this.show_toast( 'Please check step 1', 'warn')
    }
    let recurring_signup = window.campaign_scripts.build_selected_times_for_recurring(selected_time, this.frequency.value, this.duration.value, this.week_day.value)
    this.recurring_signups.push(recurring_signup)
    this.requestUpdate()
    this.show_toast()
  }
  day_selected(selected_day){
    this.selected_day = selected_day
    setTimeout(()=>{
      this.calendar_small = true
      this.requestUpdate()

    })
  }
  time_and_day_selected(selected_time){
    let time = this.selected_day + selected_time;
    if ( selected_time > 86400 ){
      time = selected_time;
    }
    let date_time = window.luxon.DateTime.fromSeconds(time, {zone:this.timezone});
    let label = date_time.toFormat('hh:mm a');
    let already_added = this.selected_times.find(k=>k.time===time)
    if ( !already_added && time > this.now && time >= this.campaign_data.start_timestamp ) {
      const selected_time = {time: time, duration: this.duration.value, label, day_key:date_time.startOf('day').toSeconds(), date_time:date_time}
      this.selected_times = [...this.selected_times, selected_time]
    }
    this.selected_times.sort((a,b)=>a.time-b.time)
    this.calendar_small = false
    this.selected_day = null
    this.requestUpdate()
    this.show_toast()
  }


  handle_frequency(e){
    this.frequency.value = e.detail;
    this.requestUpdate()
  }

  handle_click(field,e){
    this[field].value = e
    this.requestUpdate()
  }

  timezone_change(e){
    this.timezone = e.detail
    window.set_user_data({timezone: this.timezone})
  }

  remove_recurring_prayer_time(index){
    this.recurring_signups.splice(index,1)
    this.requestUpdate()
  }

  remove_prayer_time(time){
    this.selected_times = this.selected_times.filter(k=>k.time!==time)
    this.requestUpdate()
  }

  render(){
    if ( this.days.length === 0 ){
      return html`<div class="loading"></div>`
    }
    if ( !this.frequency ){
      return;
    }
    if ( this._view === 'confirmation' ){
      return html`
        <div id="campaign">
          <div class="column" style="text-align: center">
            <h2>Your selected prayer times have been added</h2>
              <button @click="${e=>window.location.reload()}">Ok</button>
          </div>
        </div>
      `
    } else {

    return html`
      <div id="campaign">
          
          <div class="column" style="max-width: 300px" ?hidden="${this._view === 'submit'}">
              
              <!--
                  FREQUENCY
              -->
              <div class="section-div">
                  <h2 class="section-title">
                      <span class="step-circle">1</span>
                      <span>${strings['I will pray']}</span> <span ?hidden="${this.frequency?.value}" class="place-indicator">${strings['Start Here']}</span>
                  </h2>
                  <cp-select 
                      show_desc="${!!this.campaign_data.end_timestamp}"
                      .options="${window.campaign_data.frequency_options}"
                      .value="${this.frequency.value}"
                       @change="${this.handle_frequency}">
                  </cp-select>
                  <time-zone-picker timezone="${this.timezone}" @change="${this.timezone_change}">
              </div>
  
              <!--
                  Duration
              -->
              <div class="section-div" ?disabled="${!this.frequency.value}">
                  <h2 class="section-title">
                      <span class="step-circle">2</span>
                      <span>${strings['For how long?']}</span></h2>
                  <div>
                      <cp-select 
                          .value="${this.duration.value}"
                          .options="${this.duration.options}"
                          @change="${e=>this.handle_click('duration', e.detail)}">
                      </cp-select>
                  </div>
              </div>
  
              <!--
                  Week Day
              -->
              ${this.frequency.value === 'weekly' ? html`
                  <h2 class="section-title">
                      <span class="step-circle">3</span>
                      <span>${strings['On which week day?']}</span> <span ?hidden="${this.week_day.value}" class="place-indicator">${strings['Continue Here']}</span>
                  </h2>
                  <div>
                      <cp-select 
                          .value="${this.week_day.value}"
                          .options="${this.week_day.options}"
                          @change="${e=>this.handle_click('week_day', e.detail)}">
                      </cp-select>
                  </div>
  
              ` : '' }
          </div>
          <div class="column center-col" ?hidden="${this._view === 'submit'}">
  
              <!--
                  Time Picker
              -->
              <div class="section-div" ?disabled="${!this.frequency.value || this.frequency.value==='weekly'&&!this.week_day.value}" ?hidden="${this.frequency.value === 'pick'}">
                  
                    <h2 class="section-title">
                        <span class="step-circle">3</span>
                        <span>${strings['At what time?']}</span>
                    </h2>
                    <cp-times 
                        slot_length="${this.campaign_data.slot_length}"
                        .frequency="${this.frequency.value}"
                        .weekday="${this.week_day.value}"
                        @time-selected="${e=>this.time_selected(e.detail)}" >
                    </cp-times>
              </div>
  
              <div class="section-div" ?disabled="${!this.frequency.value}" ?hidden="${this.frequency.value !== 'pick'}">
  
  
                  <!--
                      Calendar Picker
                  -->
                  ${this.frequency.value === 'pick' ? html`
                      <div style="display: flex;flex-wrap: wrap">
                          <div style="flex-grow: 1">
                              <h2 class="section-title">
                                  <span class="step-circle">3</span>
                                  <span>${strings['Select a Date']}</span>
                              </h2>
                              <cp-calendar-day-select style="display: flex;justify-content: center" 
                                  class="size-item top-left ${this.calendar_small ? 'small' : ''}" @click="${()=>{this.calendar_small = false;this.requestUpdate();}}"
                                  @day-selected="${e=>this.day_selected(e.detail)}"
                                  .selected_times="${this.selected_times}"
                                  start_timestamp="${this.campaign_data.start_timestamp}"
                                  end_timestamp="${this.campaign_data.end_timestamp}"
                                  .days="${this.days}"
                                  .calendar_disabled="${this.calendar_small}"
                              ></cp-calendar-day-select>
                          </div>
  
                          <!--
                              Time Picker
                          -->
                          <div>
                              <h2 class="section-title">
                                  <span class="step-circle">4</span>
                                  <span>
                                      ${this.selected_day ? 
                                          html`${strings['Select a Time for %s'].replace('%s', window.campaign_scripts.ts_to_format(this.selected_day, 'DD', this.timezone))}`
                                          : html`${strings['Select a Time']}`}</span>
                              </h2>
                              <div ?hidden="${!this.selected_day}">
                                <cp-times class="${!this.calendar_small ? 'small' : ''} size-item top-right"
                                    slot_length="${this.campaign_data.slot_length}"
                                    type="once_day"
                                    .selected_day="${this.selected_day}"
                                    @time-selected="${e=>this.time_and_day_selected(e.detail)}" >
                              </div>
                          </div>
                      </div>
                  ` : ''}
                  
              </div>
          </div>
          
          <!--
              Mobile Times Floater
          -->
          <div class="mobile selected-times" style="padding: 0.5rem; position: fixed; top:60px; right: 0; z-index: 10000;background-color: white; border:1px solid var(--cp-color); ${this.selected_times_count()?'': 'display:none'}">
              <div style="text-align: end;display: flex;justify-content: space-between" @click="${e=>{this.show_selected_times = !this.show_selected_times;this.requestUpdate()}}">
                  <button ?hidden="${!this.show_selected_times}" class="button" style="padding:0.25rem 0.85rem">${strings['Close']}</button>
                  <span style="display: flex; align-items: center">
                      <img src="${window.campaign_objects.plugin_url}assets/calendar.png" style="width: 2rem;">
                      <span>
                        (${this.selected_times_count()} <span ?hidden="${!this.show_selected_times}">${strings['prayer times']}</span>)
                      </span>
                  </span>
              </div>
              <div ?hidden="${!this.show_selected_times}" style="margin-top:1rem; max-height:50%; overflow-y: scroll">
                  ${this.recurring_signups.map((value, index) => html`
                      <div class="selected-times selected-time-labels">
                          <div class="selected-time-frequency">
                              <div>${value.label}</div>
                              <div>
                                  <button @click="${e=>this.remove_recurring_prayer_time(index)}" class="remove-prayer-time-button"><img src="${window.campaign_objects.plugin_url}assets/delete-red.svg"></button>
                              </div>
                          </div>
                          <ul>
                              <li>
                                  ${strings['Starting on %s'].replace('%s', value.first.toLocaleString({ month: 'long', day: 'numeric'}))}
                              </li>
                              <li>
                                  ${strings['Ending on %s'].replace('%s', value.last.toLocaleString({ month: 'long', day: 'numeric'}))}
                              </li>
                          </ul>
                      </div>
                  `)}
                  ${this.selected_times.map((value, index) => html`
                      <div class="selected-times">
                          <span>${value.date_time.toLocaleString({ month: 'short', day: '2-digit', hour: '2-digit', minute: '2-digit' })}</span>
                          <button @click="${e=>this.remove_prayer_time(value.time)}" class="remove-prayer-time-button">
                              <img src="${window.campaign_objects.plugin_url}assets/delete-red.svg">
                          </button>
                      </div>
                  `)}
              </div>
          </div>
          
          <!--
              Desktop Selected Times Section
          -->
          <div class="column desktop" style="max-width: 300px;">
              <div class="section-div">
                  <h2 class="section-title">
                      <span class="step-circle">*</span>
                      <span>${strings['Selected Times']} (${this.selected_times_count()})</span>
                  </h2>
                  ${this.recurring_signups.map((value, index) => html`
                      <div class="selected-times selected-time-labels">
                          <div class="selected-time-frequency">
                            <div>${value.label}</div>
                            <div>
                                <button @click="${e=>this.remove_recurring_prayer_time(index)}" class="remove-prayer-time-button"><img src="${window.campaign_objects.plugin_url}assets/delete-red.svg"></button>
                            </div>        
                          </div>
                          <ul>
                              <li>${value.count} ${strings['prayer times']}</li>
                              <li>
                                  ${strings['Starting on %s'].replace('%s', value.first.toLocaleString({ month: 'long', day: 'numeric'}))}
                              </li>
                              <li>
                                  ${strings['Ending on %s'].replace('%s', value.last.toLocaleString({ month: 'long', day: 'numeric'}))}
                              </li>
                          </ul>
                      </div>
                  `)}
                  ${this.selected_times.map((value, index) => html`
                      <div class="selected-times">
                          <span>${value.date_time.toLocaleString({ month: 'short', day: '2-digit', hour: '2-digit', minute: '2-digit' })}</span>
                          <button @click="${e=>this.remove_prayer_time(value.time)}" class="remove-prayer-time-button">
                              <img src="${window.campaign_objects.plugin_url}assets/delete-red.svg">
                          </button>
                      </div>
                  `)}
              </div>
              
          </div>
          
          
          
          <div class="column" ?hidden="${!this.already_signed_up}">
              <div class="section-div">
                  <h2 class="section-title">
                      <span class="step-circle">4</span>
                      <span>${strings['Review']}</span>
                  </h2>
  
                  <div style="text-align: center;margin-top:20px">
                      <button ?disabled=${!this.selected_times_count()}
                              @click=${()=>this.submit()}>
                          ${strings['Submit']}
                          <img ?hidden=${!this._loading} class="button-spinner" src="${window.campaign_objects.plugin_url}spinner.svg" width="22px" alt="spinner"/>
                      </button>
  
                  </div>
              </div>
          </div>
  
          <!--
              Contact Info
          -->
          <div class="column" style="max-width: 300px" ?hidden="${this._view === 'submit' || this.already_signed_up}">
              <div class="section-div">
                  <h2 class="section-title">
                      <span class="step-circle">4</span>
                      <span>${strings['Contact Info']}</span>
                  </h2>
  
                  <contact-info 
                                @form-items=${this.handle_contact_info}
                                .form_error=${this._form_items.form_error}
                                @back=${()=>this._view = 'main'}
                  ></contact-info>
              </div>
          </div>
          <!--
              Verify
          -->
          <div class="column" style="max-width: 300px" ?hidden="${this._view !== 'submit'}">
              <div class="section-div">
                  <h2 class="section-title">
                      <span class="step-circle">5</span>
                      <span>${strings['Verify']}</span>
                  </h2>
                  <cp-verify
                      email="${this._form_items.email}"
                      @code-changed=${e=>{this._form_items.code = e.detail;this.requestUpdate()}}
                  ></cp-verify>
                  <div class='form-error' 
                       ?hidden=${!this._form_items?.code_error}>
                      ${this._form_items?.code_error}
                  </div>
  
                  <div style="text-align: center;margin-top:20px">
                      <campaign-back-button @click=${() => this._view = 'contact-info'}></campaign-back-button>
                      <button ?disabled=${this._form_items?.code?.length !== 6}
                              @click=${()=>this.submit()}>
                          ${strings['Submit']}
                              <img ?hidden=${!this._loading} class="button-spinner" src="${window.campaign_objects.plugin_url}spinner.svg" width="22px" alt="spinner"/>
                      </button>
                      
                  </div>
              </div>
          </div>
      </div>
      `
    }
  }
}
customElements.define('campaign-sign-up', CampaignSignUp);



export class cpCalendar extends LitElement {
  static styles = [
    css`
      .calendar-wrapper {
        background-color: #f8f9fad1;
        border-radius: 10px;
        padding: 1em
      }
      .calendar-month {
        display: block;
        vertical-align: top;
      }
      .month-title {
        display: flex;
        justify-content: space-between;
        max-width: 280px;
        color: var(--cp-color);
        margin:0;
      }
      .month-title .month-percentage {
        color: black; font-size:1.2rem;
      }
      .calendar {
        display: grid;
        grid-template-columns: repeat(7, 40px);
        margin-bottom: 1rem;
      }
      .day-cell {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 40px;
        width: 40px;
      }
      .week-day {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 40px;
        width: 40px;
        color:black;
        font-size:12px;
        font-weight:550;
      }
      .loading {
        min-height: 600px;
      }
    `
  ]

  static properties = {
    prop: {type: String},
  }

  constructor() {
    super();
    this.campaign_data = {}
    this.days = [];
    this.loading = true
  }


  async connectedCallback() {
    super.connectedCallback();
    this.campaign_data = await window.campaign_scripts.get_campaign_data()
    this.loading = false
    this.days = window.campaign_scripts.days
    this.timezone = window.campaign_user_data.timezone
    this.requestUpdate()
  }



  render() {
    let now_date = window.luxon.DateTime.now()
    let current_year_month = now_date.toFormat('y_MM')
    let now = now_date.toSeconds();
    let start_of_today = now_date.startOf('day').toSeconds()
    let months = {};
    this.days.forEach(day=> {
      if ( day.month === current_year_month || day.key >= start_of_today ){
        if (!months[day.month]) {
          let day_number = window.campaign_scripts.get_day_number(day.key, this.timezone);
          months[day.month] = {with: 0, without: 0, key:day.key, minutes_covered:0, days:0, month_starts_on_day:day_number}
        }
        months[day.month].with += day.covered_slots
        months[day.month].without += day.slots.length - day.covered_slots
        months[day.month].minutes_covered += day.covered_slots * this.campaign_data.slot_length
        months[day.month].days += 1
      }
    })

    //first 2 months
    let months_to_show = Object.keys(months).slice(0,2)
    let week_day_names = window.campaign_scripts.get_days_of_the_week_initials(navigator.language, 'narrow')

    months_to_show.forEach(m=>{
      months[m].percentage = (months[m].with / ( months[m].without + months[m].with )* 100).toFixed( 2 )
      months[m].days_covered = ( months[m].minutes_covered / 60/24 ).toFixed( 1 )

    })

    return html`
        <div class="calendar-wrapper ${this.loading ? 'loading' : ''}">
            ${months_to_show.map(month=>html`
                <div class="calendar-month">
                    <h3 class="month-title center">
                        ${window.campaign_scripts.ts_to_format(months[month].key, 'MMM y')}
                        <span class="month-percentage">${ months[month].percentage}% | ${months[month].days_covered} ${window.campaign_objects.translations.days}</span>

                    </h3>
                    <div class="calendar">
                        ${week_day_names.map(name=>html`<div class="week-day">${name}</div>`)}
                        ${map(range(months[month].month_starts_on_day), i=>html`<div class="day-cell disabled-calendar-day"></div>`)}
                        ${this.days.filter(d=>d.month===month).map(day=>{
                            let disabled = (day.key + day_in_seconds) < now;
                            return html`
                                <div class="day-cell
                                     ${disabled ? 'disabled-calendar-day':'day-in-select-calendar'}"
                                data-day="${window.campaign_scripts.escapeHTML(day.key)}"
                                @click="${e=>this.day_selected(e, day.key)}"
                                >
                                <progress-ring stroke="3" radius="20" progress="${window.campaign_scripts.escapeHTML(day.percent)}" text="${window.campaign_scripts.escapeHTML(day.day)}"></progress-ring>
                                </div>`
                        })}
                    </div>
                </div>
            </div>
        `)}
    `
  }
}
customElements.define('cp-calendar', cpCalendar);


export class cpPercentage extends LitElement {
  static styles = [
    css`
      
    `
  ]

  static properties = {
    prop: {type: String},
  }

  constructor() {
    super();
  }

  async connectedCallback() {
    super.connectedCallback();
    this.campaign_data = await window.campaign_scripts.get_campaign_data()
    this.loading = false
    this.days = window.campaign_scripts.days
    this.timezone = window.campaign_user_data.timezone
    this.requestUpdate()
  }
  
  render() {
    if ( !this.campaign_data ){
      return html`<div class="loading"></div>`
    }

    return html`
    <div class="cp-progress-wrapper cp-wrapper">
        <div id="main-progress" class="cp-center">
<!--            <div class="cp-center" style="margin: 0 auto 10px auto; background-color: #ededed; border-radius: 20px; height: 150px; width: 150px;"></div>-->
            <progress-ring stroke="10" radius="80" font="18"
                           progress="50"
                           progress2="85"
                           text="50%"
                           text2="Bob">
            </progress-ring>
        </div>
        <div style="color: rgba(0,0,0,0.57); text-align: center">${strings['Percentage covered in prayer']}</div>
        <div style="color: rgba(0,0,0,0.57); text-align: center" id="cp-time-committed-display">${strings['%s committed'].replace('%s', this.campaign_data.time_committed)}</div>
    </div>
    `
  }
}
customElements.define('cp-percentage', cpPercentage);


export class campaignSubscriptions extends LitElement {
  static styles = [
    window.campaignStyles,
    css`
      .remove-prayer-times-button {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 5px;
      }
      .remove-prayer-times-button:hover {
        border: 1px solid red;
      }
      .remove-prayer-times-button img {
        width: 1rem;
        
      }
      .selected-times {
        //background-color: rgba(70, 118, 250, 0.1);
        border-radius: 5px;
        border: 1px solid var(--cp-color);
        margin-bottom: 2rem;
        padding: 1rem;
        justify-content: space-between;
        box-shadow: 10px 10px 5px var(--cp-color-light);
      }
      .selected-time-content {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap:5px;
      }
      .selected-time-actions {
        display: flex;
        justify-content: space-between;
      }
      h3 {
        margin: 0 0 10px 0;
        font-size: 1.2rem;
      }
      button.hollow-button {
        padding: 0.5rem;
      }
      .remove-row {
        display: flex;
        align-items: center;
      }
    `,

  ]

  static properties = {
    prop: {type: String},
    _delete_modal_open: {type: Boolean, state: true},
    _extend_modal_open: {type: Boolean, state: true},
    _extend_modal_message: {type: String, state: true},
  }

  constructor() {
    super();
    this.selected_reccuring_signup_to_delete = null;
    this._delete_modal_open = false;
    this._extend_modal_open = false;
    this._extend_modal_message = 'Def';
  }

  async connectedCallback() {
    super.connectedCallback();
    this.campaign_data = await window.campaign_scripts.get_campaign_data()
    this.timezone = window.campaign_user_data.timezone
    this.requestUpdate()
    window.addEventListener('campaign_timezone_change', (e)=>{
      this.timezone = e.detail.timezone
      this.days = window.campaign_scripts.days
      this.requestUpdate()
    });
  }

  render() {
    if ( !window.campaign_data.subscriber_info ){
      return;
    }
    this.selected_times = window.campaign_data.subscriber_info.my_commitments;
    this.my_recurring = window.campaign_data.subscriber_info.my_recurring;
    this.recurring_signups = window.campaign_data.subscriber_info.my_recurring_signups;
    return html`
        <!--delete modal-->
        <dt-modal
            .isOpen="${this._delete_modal_open}"
            title="Delete Prayer Times"
            hideButton="true"
            confirmButtonClass="danger"
            @close="${e=>this.delete_times_modal_closed(e)}"
        >
        <p slot="content">Really delete these prayer times?</p>
        </dt-modal>

        <!--extend modal-->
        <dt-modal
            .isOpen="${this._extend_modal_open}"
            .content="${this._extend_modal_message}"
            title="Extend Prayer Times"
            hideButton="true"
            confirmButtonClass="danger"
            @close="${e=>this.extend_times_modal_closed(e)}" >
        </dt-modal>
        
        
        ${(this.recurring_signups||[]).map((value, index) => {
            return html`
            <div class="selected-times">
                <div class="selected-time-content">
                  <div>
                    <h3 style="display: inline">${window.luxon.DateTime.fromSeconds(value.first, {zone: this.timezone}).toFormat('DD')} - ${window.luxon.DateTime.fromSeconds(value.last, {zone:this.timezone}).toFormat('DD')}</h3>  
                    <button class="clear-button" @click="${()=>this.open_extend_times_modal(value.report_id)}">extend</button>  
                  </div>
                  <div>
                      <strong>${window.campaign_scripts.recurring_time_slot_label(value)}</strong>
                      <button disabled class="clear-button">change time</button>
                  </div>
                  <div class="selected-time-actions">
                      <button class="clear-button" @click="${e=>{value.display_times=!value.display_times;this.requestUpdate()}}">
                          See prayer times (${(value.commitments_report_ids||[]).length})
                      </button>
                      <button class="clear-button danger loader" @click="${e=>this.open_delete_times_modal(e,value.report_id)}">
                          Remove all
                      </button>
                  </div>
                </div>
                <div style="margin-top:20px" ?hidden="${!value.display_times}">
                    ${window.campaign_data.subscriber_info.my_commitments.filter(c=>value.commitments_report_ids.includes(c.report_id)).map(c=>html`
                        <div class="remove-row">
                            <span>${window.luxon.DateTime.fromSeconds(parseInt(c.time_begin), {zone: this.timezone}).toLocaleString({ month: 'short', day: '2-digit', hour: '2-digit', minute: '2-digit' })}</span>
                            <button ?disabled="${true}" 
                                    class="remove-prayer-times-button clear-button">
                                <img src="${window.campaign_objects.plugin_url}assets/delete-red.svg">
                            </button>
                        </div>
                    `)}
                </div>
                
            </div>
        `})}
    `
  }

  delete_recurring_time(){
    let data = {
      action: 'delete_recurring_signup',
      report_id: this.selected_reccuring_signup_to_delete,
      parts: window.subscription_page_data.parts
    }
    jQuery.ajax({
      type: "POST",
      data: JSON.stringify(data),
      contentType: "application/json; charset=utf-8",
      dataType: "json",
      url: window.subscription_page_data.root + window.subscription_page_data.parts.root + '/v1/' + window.subscription_page_data.parts.type,
      beforeSend: function (xhr) {
        xhr.setRequestHeader('X-WP-Nonce', window.subscription_page_data.nonce )
      }
    }).then(data=>{
      window.location.reload()
      // calendar_subscribe_object.my_commitments = data;
      // draw_calendar();
      // calculate_my_time_slot_coverage()
      // $(this).removeClass('loading')
      // $('#delete-times-modal').foundation('close')
    })
  }

  open_delete_times_modal(e,report_id){
    const recurring_sign = this.recurring_signups.find(k=>k.report_id===report_id)
    if ( !recurring_sign ){
      return;
    }
    this.selected_reccuring_signup_to_delete = report_id
    this._delete_modal_open = true;
  }

  delete_times_modal_closed(e){
    this._delete_modal_open = false;
    if ( e.detail?.action === 'confirm' ){
      this.delete_recurring_time()
    }
  }

  open_extend_times_modal(report_id){
    const recurring_sign = this.recurring_signups.find(k=>k.report_id===report_id)
    if ( !recurring_sign ){
      return;
    }
    this.selected_reccuring_signup_to_extend = report_id
    let frequency_option = window.campaign_data.frequency_options.find(k=>k.value===recurring_sign.type)

    this._extend_modal_message = ("Extend for %s months?").replace('%s', frequency_option.month_limit );
    this._extend_modal_open = true;
  }

  extend_times_modal_closed(e){
    this._extend_modal_open = false;
    if ( e.detail?.action === 'confirm' ){
      let recurring_sign = this.recurring_signups.find(k=>k.report_id===this.selected_reccuring_signup_to_extend)
      if ( !recurring_sign ){
        return;
      }

      let recurring_extend = window.campaign_scripts.build_selected_times_for_recurring( recurring_sign.time, recurring_sign.type, recurring_sign.duration, recurring_sign.week_day || null, recurring_sign.last );
      recurring_extend.report_id = recurring_sign.report_id

      //filter out existing times
      let existing_times = window.campaign_data.subscriber_info.my_commitments.filter(c=>recurring_sign.commitments_report_ids.includes(c.report_id)).map(c=>parseInt(c.time_begin))
      recurring_extend.selected_times = recurring_extend.selected_times.filter(c=>!existing_times.includes(c.time))

      window.campaign_scripts.submit_prayer_times( recurring_sign.campaign_id, recurring_extend, 'update_recurring_signup').then(data=>{
        window.location.reload() //@todo replace with event
      })
    }
  }
}
customElements.define('campaign-subscriptions', campaignSubscriptions);


