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
        padding: 0 2rem;
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
        grid-gap: 3rem;
        font-size: 1rem;
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
    timezone: {type: String},
    slot_length: {type: Number},
    small: {type: Boolean},
    rest_root: {type: String},
    magic_link_parts: {type: Object},

  }

  constructor() {
    super()
    this.campaign_data = {
      start_timestamp: 0,
      end_timestamp: 0,
      slot_length: 60,
      duration_options: {},
      coverage: {}
    }
    this._form_items = {
      email: '',
      name: '',
    }
    this.now = new Date().getTime()/1000
    this.selected_day = null;
    this.selected_times = [];
    this.selected_times_labels = [];
    this.show_selected_times = false;
    this.timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
    this.days = [];

    this.get_campaign_data().then(()=>{
      this.frequency = {
        value: '',
        options: [
          {value: 'daily', label: 'Daily' + ( this.campaign_data.end_timestamp ? '' : ' (up to 3 months) '), days_limit:90, step:'day'},
          {value: 'weekly', label: 'Weekly' + ( this.campaign_data.end_timestamp ? '' : ' (up to 6 months) '), disabled: false, days_limit: 180, step:'week'},
          {value: 'monthly', label: 'Monthly' + ( this.campaign_data.end_timestamp ? '' : ' (up to 12 months) '), disabled: true, days_limit: 365, step:'month'},
          {value: 'pick', label: 'Pick Dates and Times'},
        ]
      }
      this.duration = {
        value: 15,
        options: [
          {value: 15, label: '15 Minutes'},
          {value: 30, label: '30 Minutes'},
          {value: 60, label: '1 Hour'},
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
    })
  }
  selected_times_count(){
    let count = 0;
    this.selected_times_labels.forEach(v=>{
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
    this.selected_times_labels.forEach(v=>{
      selected_times = [...selected_times, ...v.selected_times]
    })

    let data = {
      name: this._form_items.name,
      email: this._form_items.email,
      code: this._form_items.code,
      parts: window.campaign_objects.magic_link_parts,
      campaign_id: this.campaign_data.campaign_id,
      selected_times: selected_times,
      timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || 'America/Chicago', //@todo
    }

    let link = window.campaign_objects.rest_url + window.campaign_objects.magic_link_parts.root + '/v1/' + window.campaign_objects.magic_link_parts.type;
    if (window.campaign_objects.remote) {
      link = window.campaign_objects.rest_url + window.campaign_objects.magic_link_parts.root + '/v1/24hour-router';
    }
    jQuery.ajax({
      type: "POST",
      data: JSON.stringify(data),
      contentType: "application/json; charset=utf-8",
      dataType: "json",
      url: link
    })
    .done(()=>{
      this.selected_times = [];
      this._loading = false;
      if ( window.campaign_objects.remote === "1" ){
        this._view = 'confirmation'; //@todo
      } else {
        window.location.href = window.campaign_objects.home + '/prayer/email-confirmation';
      }
      this.requestUpdate()
    })
    .fail((e)=>{
      this._loading = false
      let message = html`So sorry. Something went wrong. Please, try again.<br>
          <a href="${window.lodash.escape(window.location.href)}">Try Again</a>`
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
        <a href="${window.lodash.escape(window.location.href)}">Try Again</a>`
      this._form_items.form_error = message
      this._loading = false
      this.requestUpdate()
    })
  }


  get_times(){
    let day_in_seconds = 86400;
    let key = 0;
    let start_of_today = new Date('2023-01-01')
    start_of_today.setHours(0, 0, 0, 0)
    let start_time_stamp = start_of_today.getTime() / 1000

    let options = [];
    while (key < day_in_seconds) {
      let time = window.luxon.DateTime.fromSeconds(start_time_stamp + key)
      let time_formatted = time.toFormat('hh:mm a')
      let progress = (
        window.campaign_scripts.time_slot_coverage?.[time_formatted]?.length ?
        window.campaign_scripts.time_slot_coverage?.[time_formatted]?.length / window.campaign_scripts.time_label_counts[time_formatted] * 100
        : 0
      ).toFixed(1)
      let min = time.toFormat(':mm')
      options.push({key: key, time_formatted: time_formatted, minute: min, hour: time.toFormat('hh a'), progress})
      key += this.campaign_data.slot_length * 60
    }
    return options;
  }

  build_list(selected_time){
    let selected_times = []
    let now = new Date().getTime()/1000
    let now_date = window.luxon.DateTime.fromSeconds(Math.max(now, this.days[0].key),{zone:this.timezone})
    let frequency_option = this.frequency.options.find(k=>k.value===this.frequency.value)
    if ( frequency_option.value === 'weekly' ){
      now_date = now_date.set({weekday: parseInt(this.week_day.value)})
    }
    let start_of_day = now_date.startOf('day').toSeconds()
    let start_time = start_of_day + selected_time;
    let start_date = window.luxon.DateTime.fromSeconds(start_time, {zone:this.timezone})

    let limit = this.campaign_data.end_timestamp
    if ( !this.campaign_data.end_timestamp ){
      limit = start_date.plus({days: frequency_option.days_limit}).toSeconds();
    }

    let date_ref = start_date
    while ( date_ref.toSeconds() <= limit ){
      let time = date_ref.toSeconds();
      let time_label = date_ref.toFormat('hh:mm a');
      let already_added = selected_times.find(k=>k.time===time)
      if ( !already_added && time > now && time >= this.campaign_data.start_timestamp ) {
        selected_times.push({time: time, duration:  this.duration.value, label: time_label, day_key:date_ref.startOf('day'), date_time:date_ref})
      }
      date_ref = date_ref.plus({[frequency_option.step]:1})
    }
    let label = '';
    if ( frequency_option.value === 'daily' ){
      label = "Every Day at " + selected_times[0].date_time.toLocaleString({ hour: 'numeric', minute: 'numeric', hour12: true });
    } else if ( frequency_option.value === 'weekly' ){
      label = "Every " + selected_times[0].date_time.toFormat('cccc') + " at " + selected_times[0].date_time.toLocaleString({ hour: 'numeric', minute: 'numeric', hour12: true });
    }
    this.selected_times_labels.push( {
      label: label,
      type: frequency_option.value,
      first: selected_times[0].date_time,
      last: selected_times[selected_times.length-1].date_time,
      time: selected_time,
      time_label: selected_times[0].label,
      count: selected_times.length,
      selected_times,
    })
    return selected_times;
  }
  time_selected(selected_time){
    this.build_list(selected_time)
    this.requestUpdate()
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
    window.campaign_scripts.timezone = e.detail
    this.days = window.campaign_scripts.calculate_day_times( this.timezone, this.campaign_data.start_timestamp, this.campaign_data.end_timestamp, this.campaign_data.current_commitments, this.campaign_data.slot_length )
    this.requestUpdate()
  }

  remove_recurring_prayer_time(index){
    this.selected_times_labels.splice(index,1)
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
    let times = this.get_times();
    return html`
    <div id="campaign">
        <div class="column" style="max-width: 300px" ?hidden="${this._view === 'submit'}">
            <div class="section-div">
                <h2 class="section-title">
                    <span class="step-circle">1</span>
                    <span>Frequency</span> <span ?hidden="${this.frequency.value}" class="place-indicator">Start Here</span>
                </h2>
                <div>
                    <cp-select 
                        .options="${this.frequency.options}"
                        .value="${this.frequency.value}"
                         @change="${this.handle_frequency}">
                    </cp-select>
                </div>
<!--                <p>-->
<!--                    Extend for more-->
<!--                </p>-->
            </div>
            <div class="section-div">
                <time-zone-picker timezone="${this.timezone}" @change="${this.timezone_change}">
                
            </div>
            <div class="section-div" ?disabled="${!this.frequency.value}">
                <h2 class="section-title"><span class="step-circle">2</span><span>Prayer Duration</span></h2>
                <div>
                    <cp-select 
                        .value="${this.duration.value}"
                        .options="${this.duration.options}"
                        @change="${e=>this.handle_click('duration', e.detail)}">
                    </cp-select>
                </div>
            </div>
            ${this.frequency.value === 'weekly' ? html`

                    <h2 class="section-title">
                        <span class="step-circle">3</span>
                        <span>Week Day</span> <span ?hidden="${this.week_day.value}" class="place-indicator">Now Here</span>
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
            <div class="section-div" ?disabled="${!this.frequency.value || this.frequency.value==='weekly'&&!this.week_day.value}">
                
                
                ${['daily', 'weekly'].includes(this.frequency.value) ? html`
                
                  <h2 class="section-title">
                      <span class="step-circle">3</span>
                      <span>Select Daily Prayer Time</span>
                  </h2>
                  <cp-times slot_length="${this.campaign_data.slot_length}" .times="${times}"
                      @time-selected="${e=>this.time_selected(e.detail)}" >
                  </cp-times>
                  
                ` : ''}
                
                
                ${this.frequency.value === 'pick' ? html`
                    
                    <div style="display: flex;flex-wrap: wrap">
                        <div style="flex-grow: 1">
                            <h2 class="section-title">
                                <span class="step-circle">3</span>
                                <span>Select a Date </span>
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
                        
                       
                        <div>
                            <h2 class="section-title">
                                <span class="step-circle">4</span>
                                <span>Select a Time ${this.selected_day ? html`for ${window.campaign_scripts.ts_to_format(this.selected_day, 'DD', this.timezone)}` : ''}</span>
                            </h2>
                            <div ?hidden="${!this.selected_day}">
                              <cp-times class="${!this.calendar_small ? 'small' : ''} size-item top-right"
                                  slot_length="${this.campaign_data.slot_length}"
                                  .times="${times}"
                                  type="once_day"
                                  .selected_day="${this.selected_day}"
                                  @time-selected="${e=>this.time_and_day_selected(e.detail)}" >
                            </div>
                        </div>
                        
                    </div>
                        
                    
                ` : ''}
                
            </div>
        </div>

        
        <div class="mobile selected-times" style="position: fixed; top:60px; right: 0; z-index: 10000;background-color: white; border:1px solid var(--cp-color); ${this.selected_times_count()?'': 'display:none'}">
            <div style="text-align: end;display: flex;justify-content: space-between" @click="${e=>{this.show_selected_times = !this.show_selected_times;this.requestUpdate()}}">
                <button ?hidden="${!this.show_selected_times}" class="button" style="padding:0.25rem 0.85rem">Close</button>
                <span>
                  &#128467;
                    (${this.selected_times_count()} <span ?hidden="${!this.show_selected_times}">prayer times</span>) 
                </span>
            </div>
            <div ?hidden="${!this.show_selected_times}" style="margin-top:1rem; max-height:50%; overflow-y: scroll">
                ${this.selected_times_labels.map((value, index) => html`
                    <div class="selected-times selected-time-labels">
                        <div class="selected-time-frequency">
                            <div>${value.label}</div>
                            <div>
                                <button @click="${e=>this.remove_recurring_prayer_time(index)}" class="remove-prayer-time-button"><img src="${window.campaign_objects.plugin_url}assets/delete-red.svg"></button>
                            </div>
                        </div>
                        <ul>
                            <li>
                                Starting on ${value.first.toLocaleString({ month: 'long', day: 'numeric'})}
                            </li>
                            <li>
                                Ending on ${value.last.toLocaleString({ month: 'long', day: 'numeric'})}
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
        
        <div class="column desktop" style="max-width: 300px;">
            <div class="section-div">
                <h2 class="section-title">
                    <span class="step-circle">*</span>
                    <span>Selected Times (${this.selected_times.length})</span>
                </h2>
                ${this.selected_times_labels.map((value, index) => html`
                    <div class="selected-times selected-time-labels">
                        <div class="selected-time-frequency">
                          <div>${value.label}</div>
                          <div>
                              <button @click="${e=>this.remove_recurring_prayer_time(index)}" class="remove-prayer-time-button"><img src="${window.campaign_objects.plugin_url}assets/delete-red.svg"></button>
                          </div>        
                        </div>
                        <ul>
                            <li>${value.count} prayer times</li>
                            <li>
                                Starting on ${value.first.toLocaleString({ month: 'long', day: 'numeric'})}
                            </li>
                            <li>
                                Ending on ${value.last.toLocaleString({ month: 'long', day: 'numeric'})}
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

        <div class="column" style="max-width: 300px" ?hidden="${this._view === 'submit'}">
            <div class="section-div">
                <h2 class="section-title">
                    <span class="step-circle">4</span>
                    <span>Contact Info</span>
                </h2>

                <contact-info 
                              @form-items=${this.handle_contact_info}
                              .form_error=${this._form_items.form_error}
                              @back=${()=>this._view = 'main'}
                ></contact-info>
            </div>
        </div>
        <div class="column" style="max-width: 300px" ?hidden="${this._view !== 'submit'}">
            <div class="section-div">
                <h2 class="section-title">
                    <span class="step-circle">5</span>
                    <span>Verify</span>
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
                        Submit
                            <img ?hidden=${!this._loading} class="button-spinner" src="${window.campaign_objects.plugin_url}spinner.svg" width="22px" alt="spinner"/>
                    </button>
                    
                </div>
            </div>
        </div>
    </div>
    `
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
    this.timezone = window.campaign_scripts.timezone
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
                                <div class="day-cell"
                                     ${disabled ? 'disabled-calendar-day':'day-in-select-calendar'}"
                                data-day="${window.lodash.escape(day.key)}"
                                @click="${e=>this.day_selected(e, day.key)}"
                                >
                                <progress-ring stroke="3" radius="20" progress="${window.lodash.escape(day.percent)}" text="${window.lodash.escape(day.day)}"></progress-ring>
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
  
  render() {
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
        <div style="color: rgba(0,0,0,0.57); text-align: center">Percentage covered in prayer</div>
        <div style="color: rgba(0,0,0,0.57); text-align: center" id="cp-time-committed-display">'% committed</div>
    </div>
    `
  }
}
customElements.define('cp-percentage', cpPercentage);