import {html, css, LitElement, range, map} from 'https://cdn.jsdelivr.net/gh/lit/dist@2/all/lit-all.min.js';
const strings = window.campaign_scripts.escapeObject(window.campaign_objects.translations)
function translate(str){
  if ( !strings[str] ){
    console.error("'" + str + "' => __( '" + str + "', 'disciple-tools-prayer-campaigns' ),");
  }
  return strings[str] || str
}

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
        padding-bottom: 1.5rem;
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
      
      .selected-times {
        border: 1px solid var(--cp-color);
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
      
      .column {
        max-width: 400px;
        flex-basis: 30%;
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
          flex-basis: 100%;
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
    _view: {type: String, state: true},
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
    this.account_link = '';

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
    .done((response)=>{
      this.selected_times = [];
      this._loading = false;
      if ( response.account_link ){
        this.account_link = response.account_link;
      }
      this._view = 'confirmation';
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
      message = strings["Prayer Time Selected"];
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
      position: "center",
      style: {
        background: background
      },
    }).showToast();
  }


  time_selected(selected_time){
    if (!this.frequency.value ){
      return this.show_toast( 'Please check step 1', 'warn')
    }
    if ( this.frequency.value==='weekly' && !this.week_day.value ){
      return this.show_toast( 'Please check step 3', 'warn')
    }
    if ( this.frequency.value === 'pick' ){
      return this.time_and_day_selected(selected_time)
    }
    let recurring_signup = window.campaign_scripts.build_selected_times_for_recurring(selected_time, this.frequency.value, this.duration.value, this.week_day.value)
    this.recurring_signups = [...this.recurring_signups, recurring_signup]
    window.campaign_user_data.recurring_signups = this.recurring_signups;
    this.requestUpdate()
    this.show_toast()
  }
  day_selected(selected_day){
    this.selected_day = selected_day
    setTimeout(()=>{
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
    // this.selected_day = null
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
              <div class="section-div">
                  <h2 class="section-title">
                      <span class="step-circle">!</span>
                      <span style="flex-grow: 1">${translate('Success')}</span>
                  </h2>
                  <p>
                      ${translate('Your registration was successful.')}
                  </p>
                  <p>
                      ${translate('Check your email for additional details and to manage your account.')}
                  </p>
                  <div class="nav-buttons">
                      <button @click=${()=>window.location.reload()}>${translate('Ok')}</button>
                      <a ?hidden="${this.account_link.length===0}" class="button" href="${this.account_link}">${translate('Access Account')}</a>
                  </div>
                      
          </div>
        </div>
      `
    } else {

    return html`
      <div id="campaign">
          
          <div class="column" ?hidden="${this._view === 'submit'}">
              
              <!--
                  Duration
              -->
              <div class="section-div" ?disabled="${!this.frequency.value}">
                  <h2 class="section-title">
                      <span class="step-circle">1</span>
                      <span>${translate('I will pray for')}</span></h2>
                  <div>
                      <cp-select 
                          .value="${this.duration.value}"
                          .options="${this.duration.options}"
                          @change="${e=>this.handle_click('duration', e.detail)}">
                      </cp-select>
                  </div>
              </div>
              
              <!--
                  FREQUENCY
              -->
              <div class="section-div">
                  <h2 class="section-title">
                      <span class="step-circle">2</span>
                      <span>${strings['How often?']}</span> <span ?hidden="${this.frequency?.value}" class="place-indicator">${strings['Start Here']}</span>
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
                  Week Day
              -->
              ${this.frequency.value === 'weekly' ? html`
                  <h2 class="section-title">
                      <span class="step-circle">3</span>
                      <span>${strings['On which week day?']}</span>
                      <span ?hidden="${this.week_day.value}" class="place-indicator">${strings['Continue here']}</span>
                  </h2>
                  <div>
                      <cp-select 
                          .value="${this.week_day.value}"
                          .options="${this.week_day.options}"
                          @change="${e=>this.handle_click('week_day', e.detail)}">
                      </cp-select>
                  </div>
  
              ` : '' }

              <!--
                  Calendar Picker
              -->
              ${this.frequency.value === 'pick' ? html`
                
                  <h2 class="section-title">
                      <span class="step-circle">3</span>
                      <span>${strings['Select a Date']}</span>
                      <span ?hidden="${!(this.recurring_signups.length === 0  && this.selected_times.length === 0) || this.selected_day }" class="place-indicator">${strings['Continue here']}</span>
                  </h2>
                  <cp-calendar-day-select
                      @day-selected="${e=>this.day_selected(e.detail)}"
                      start_timestamp="${this.campaign_data.start_timestamp}"
                      end_timestamp="${this.campaign_data.end_timestamp}"
                      .selected_times="${this.selected_times}"
                      .days="${this.days}"
                  ></cp-calendar-day-select>
                    
              `: ''}
          </div>
          <div class="column" ?hidden="${this._view === 'submit'}">
  
              <!--
                  Time Picker
              -->
              <div class="section-div" ?disabled="${!this.frequency.value || this.frequency.value==='weekly'&&!this.week_day.value}">
                  
                    <h2 class="section-title">
                        <span class="step-circle">4</span>
                        <span>
                            ${this.frequency.value === 'pick' ? ( 
                              this.selected_day ?
                                html`${translate('Select a Time for %s').replace('%s', window.campaign_scripts.ts_to_format(this.selected_day, 'DD', this.timezone))}`
                                : html`${translate('Select a Day')}`
                              ) :  html`${translate('At what time?')}`}
                        </span>
                        <span ?hidden="${!(this.recurring_signups.length === 0  && this.selected_times.length === 0) || !(this.frequency.value === 'daily' || this.week_day.value || this.selected_day)  }" class="place-indicator">${strings['Continue here']}</span>
                    </h2>
                    <cp-times 
                        slot_length="${this.campaign_data.slot_length}"
                        .frequency="${this.frequency.value}"
                        .weekday="${this.week_day.value}"
                        .selected_day="${this.selected_day}"
                        .selected_times="${this.selected_times}"
                        .recurring_signups="${['bob']}"
                        @time-selected="${e=>this.time_selected(e.detail)}" >
                    </cp-times>
              </div>
          </div>
          
          
          
  
          <!--
              Contact Info
          -->
          <div class="column" ?hidden="${this._view === 'submit'}">
              <div class="section-div" ?hidden="${this.already_signed_up}">
                  <h2 class="section-title">
                      <span class="step-circle">5</span>
                      <span>${strings['Contact Info']}</span>
                      <span ?hidden="${this.recurring_signups.length === 0  && this.selected_times.length === 0}" class="place-indicator">${strings['Continue here']}</span>
                  </h2>
  
                  <contact-info .selected_times_count="${this.selected_times_count()}"
                                @form-items=${this.handle_contact_info}
                                .form_error=${this._form_items.form_error}
                                @back=${()=>this._view = 'main'}
                  ></contact-info>
              </div>
              <!--
                already signed in
              -->
              <div class="section-div" ?hidden="${!this.already_signed_up}">
                  <h2 class="section-title">
                      <span class="step-circle">5</span>
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
                                      <button @click="${e=>this.remove_recurring_prayer_time(index)}" class="remove-prayer-time-button">
                                          <img src="${window.campaign_objects.plugin_url}assets/delete-red.svg">
                                      </button>
                                  </div>
                              </div>
                              <ul>
                                  <li>
                                      ${strings['Starting on %s'].replace('%s', value.first.toLocaleString({ month: 'long', day: 'numeric'}))}
                                  </li>
                                  <li>
                                      ${translate('Renews on %s').replace('%s', value.last.toLocaleString({ month: 'long', day: 'numeric'}))}
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
              <div class="desktop section-div">
                  <h2 class="section-title">
                      <span class="step-circle">*</span>
                      <span>${translate('My Prayer Commitments')} (${this.selected_times_count()})</span>
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
                              <li>
                                  ${strings['Starting on %s'].replace('%s', value.first.toLocaleString({ month: 'long', day: 'numeric'}))}
                              </li>
                              <li>
                                  ${translate('Renews on %s').replace('%s', value.last.toLocaleString({ month: 'long', day: 'numeric'}))}
                              </li>
                          </ul>
                      </div>
                  `)}
                  ${this.selected_times.map((value, index) => html`
                      <div class="selected-times">
                          <span class="aligned-row">
                              ${value.date_time.toLocaleString({ month: 'short', day: '2-digit' })},
                              <span class="dt-tag">${ value.date_time.toLocaleString({hour: '2-digit', minute: '2-digit'})}</span>
                              ${translate('for %s minutes').replace('%s', value.duration)}
                          </span>
                          <button @click="${e=>this.remove_prayer_time(value.time)}" class="remove-prayer-time-button">
                              <img src="${window.campaign_objects.plugin_url}assets/delete-red.svg">
                          </button>
                      </div>
                  `)}
              </div>
              
          </div>
          <!--
              Verify
          -->
          <div class="column" ?hidden="${this._view !== 'submit'}">
              <div class="section-div">
                  <h2 class="section-title" style="display: flex">
                      <span class="step-circle">5</span>
                      <span style="flex-grow: 1">${strings['Verify']}</span>
                      <button @click="${()=>this._view = 'main'}">Back</button>
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
        font-size: 15px;
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
      .progress-ring {
        width: 40px;
        height: 40px;
        padding-top: 2px;
      }
      .disabled-calendar-day {
        color: #c4c4c4;
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
    let now = now_date.toSeconds();
    let months_to_show = [];
    for( let i = 0; i < 2; i++ ){
      let month_days = window.campaign_scripts.build_calendar_days(now_date.plus({month:i}))
      let covered_slots = 0
      let total_slots = 0

      month_days.forEach(day=>{
        covered_slots += day.covered_slots || 0
        total_slots += day.slots.length || 0
      })
      months_to_show.push({
        date: now_date.plus({month:i}),
        days: month_days,
        percentage: (covered_slots / total_slots * 100).toFixed( 2 ),
        days_covered: ( this.campaign_data.slot_length * covered_slots / 60 / 24 ).toFixed( 1 )
      })
    }

    let week_day_names = window.campaign_scripts.get_days_of_the_week_initials(navigator.language, 'narrow')

    return html`
        <div class="calendar-wrapper ${this.loading ? 'loading' : ''}">
            ${months_to_show.map(month=>html`
                <div class="calendar-month">
                    <h3 class="month-title center">
                        ${month.date.toFormat( 'MMM y')}
                        <span class="month-percentage">${ month.percentage || 0 }% | ${month.days_covered} ${translate('days')}</span>

                    </h3>
                    <div class="calendar">
                        ${week_day_names.map(name=>html`<div class="week-day">${name}</div>`)}
                        ${map(range(month.date.startOf('month').weekday%7), i=>html`<div class="day-cell disabled-calendar-day"></div>`)}
                        ${month.days.map(day=>{
                            return html`
                                <div class="day-cell
                                     ${day.disabled ? 'disabled-calendar-day':'day-in-select-calendar'}"
                                data-day="${window.campaign_scripts.escapeHTML(day.key)}"
                                >
                                ${ ( day.disabled && day.key < window.campaign_data.start_timestamp ) ? window.campaign_scripts.escapeHTML(day.day) : html`
                                    <progress-ring class="progress-ring" stroke="3" radius="20" progress="${window.campaign_scripts.escapeHTML(day.percent)}" text="${window.campaign_scripts.escapeHTML(day.day)}"></progress-ring>
                                ` }
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
            <progress-ring stroke="10" radius="80" font="18"
                           progress="${this.campaign_data.coverage_percent || 0}"
                           progress2="0"
                           text="${this.campaign_data.coverage_percent || 0}%"
                           text2="">
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
      .selected-time-content h3 {
        margin: 0;
        font-size: 1.2rem;
      }
      .selected-time-content .title-row {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
      }
      .selected-time-content .title-row .dt-tag{
        
        margin-inline-start: 10px;
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
    _delete_time_modal_open: {type: Boolean, state: true},
    _extend_modal_open: {type: Boolean, state: true},
    _extend_modal_message: {type: String, state: true},
  }

  constructor() {
    super();
    this.selected_reccuring_signup_to_delete = null;
    this._selected_time_to_delete = null;
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
        <dt-modal
            .isOpen="${this._delete_time_modal_open}"
            title="Delete Prayer Time"
            hideButton="true"
            confirmButtonClass="danger"
            @close="${e=>this.delete_time_modal_closed(e)}"
        >
        <p slot="content">Really delete this prayer time?</p>
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
            const prayer_times = window.campaign_data.subscriber_info.my_commitments.filter(c=>value.report_id==c.recurring_id)
            return html`
            <div class="selected-times">
                <div class="selected-time-content">
                  <div class="title-row">
                    <h3>${window.luxon.DateTime.fromSeconds(value.first, {zone: this.timezone}).toFormat('DD')} - ${window.luxon.DateTime.fromSeconds(value.last, {zone:this.timezone}).toFormat('DD')}</h3>  
                    <button class="clear-button" @click="${()=>this.open_extend_times_modal(value.report_id)}">extend</button>  
                  </div>
                  <div>
                      <strong>${window.campaign_scripts.recurring_time_slot_label(value)}</strong>
                      <button disabled class="clear-button">change time</button>
                  </div>
                  <div class="selected-time-actions">
                      <button class="clear-button" @click="${e=>{value.display_times=!value.display_times;this.requestUpdate()}}">
                          See prayer times (${prayer_times.length})
                      </button>
                      <button class="clear-button danger loader" @click="${e=>this.open_delete_times_modal(e,value.report_id)}">
                          Remove all
                      </button>
                  </div>
                </div>
                <div style="margin-top:20px" ?hidden="${!value.display_times}">
                    ${prayer_times.map(c=>html`
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
        ${(window.campaign_data.subscriber_info.my_commitments).filter(c=>c.type==='selected_time').map((value, index) => {
          const date = window.luxon.DateTime.fromSeconds(value.time_begin, {zone: this.timezone})  
          return html`
            <div class="selected-times">
                <div class="selected-time-content">
                  <div style="display: flex; justify-content: space-between">
                    <div class="aligned-row">
                      <h3>${date.toFormat('DD')}</h3>
                      <span class="dt-tag">${date.toLocaleString({ hour: 'numeric', minute: 'numeric', hour12: true })}</span>
                      ${translate('for %s minutes').replace('%s', (value.time_end - value.time_begin)/60)}
                    </div>
                    <button class="clear-button danger loader remove-prayer-times-button" @click="${e=>this.open_delete_time_modal(e,value.report_id)}">
                        <img src="${window.campaign_objects.plugin_url}assets/delete-red.svg">
                    </button>
                  </div>
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
      // draw_calendar();
      // calculate_my_time_slot_coverage()
      // $(this).removeClass('loading')
      // $('#delete-times-modal').foundation('close')
    })
  }
  delete_time(){
    let data = {
      action: 'delete',
      report_id: this._selected_time_to_delete,
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

  open_delete_time_modal(e,report_id){
    const time = this.selected_times.find(k=>k.report_id===report_id)
    if ( !time ){
      return;
    }
    this._selected_time_to_delete = report_id
    this._delete_time_modal_open = true;
  }

  delete_times_modal_closed(e){
    this._delete_modal_open = false;
    if ( e.detail?.action === 'confirm' ){
      this.delete_recurring_time()
    }
  }
  delete_time_modal_closed(e){
    this._delete_modal_open = false;
    if ( e.detail?.action === 'confirm' ){
      this.delete_time()
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


