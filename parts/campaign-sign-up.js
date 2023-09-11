import {html, css, LitElement, range, map} from 'https://cdn.jsdelivr.net/gh/lit/dist@2/all/lit-all.min.js';
const strings = window.campaign_scripts.escapeObject(window.campaign_components.translations)

console.log("test");

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
        padding-bottom: 3rem;
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
      }
      .selected-time-labels {
        display: flex;
      }
      .selected-time-labels ul{
        margin:0;
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
          max-width: 100% !important;
        }
      }
      
    `,
  ];

  static properties = {
    timezone: {type: String},
    slot_length: {type: Number},
    small: {type: Boolean},
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
    this.slot_length = 15;
    this.show_below_fold = false
    this.selected_times = [];
    this.selected_times_labels = [];
    this.duration = 15;
    this.timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
    this.days = [];
    this.get_campaign_data()

    this.frequency = {
      value: 'daily',
      options: [
        {value: 'daily', label: 'Daily (for 3 months)'},
        {value: 'weekly', label: 'Weekly (for 6 months)'},
        {value: 'monthly', label: 'Monthly (for 1 year)'},
        {value: 'pick', label: 'Pick Dates and Times'},
      ]
    }
    this.slot_length = {
      value: 15,
      options: [
        {value: 15, label: '15 Minutes'},
        {value: 30, label: '30 Minutes'},
        {value: 60, label: '1 Hour'},
      ]
    }
    this.week_day = {
      value: 'monday',
      options: [
        {value: 'monday', label: 'Mondays'},
        {value: 'tuesday', label: 'Tuesdays'},
        {value: 'wednesday', label: 'Wednesdays'},
        {value: 'thursday', label: 'Thursdays'},
        {value: 'friday', label: 'Fridays'},
        {value: 'saturday', label: 'Saturdays'},
        {value: 'sunday', label: 'Sundays'},
      ]
    }
  }
  get_campaign_data() {
    let link = window.campaign_objects.root + window.campaign_objects.parts.root + '/v1/' + window.campaign_objects.parts.type + '/campaign_info';
    return jQuery.ajax({
      type: 'GET',
      data: {action: 'get', parts: window.campaign_objects.parts, 'url': 'campaign_info', time: new Date().getTime()},
      contentType: 'application/json; charset=utf-8',
      dataType: 'json',
      url: link
    })
    .done((data) => {
      this._view = 'main';
      this.campaign_data = {...this.campaign_data, ...data};
      window.calendar_subscribe_object = this.campaign_data
      console.log(window.calendar_subscribe_object);
      this.days = window.campaign_scripts.calculate_day_times(
        this.timezone,
        this.now - 30 * day_in_seconds,
        this.now + 365 * day_in_seconds,
        this.campaign_data.current_commitments,
        this.campaign_data.slot_length,
      )
      this.requestUpdate()
      return data
    })
  }

  get_times(){
    let day_in_seconds = 86400;
    let key = 0;
    let start_of_today = new Date('2023-01-01')
    start_of_today.setHours(0, 0, 0, 0)
    let start_time_stamp = start_of_today.getTime() / 1000

    let options = [];
    // if ( this.selected_day !== 0 ){
      while (key < day_in_seconds) {
        let time = window.luxon.DateTime.fromSeconds(start_time_stamp + key)
        let time_formatted = time.toFormat('hh:mm a')
        let progress = (window.campaign_scripts.time_slot_coverage?.[time_formatted]?.length ? window.campaign_scripts.time_slot_coverage?.[time_formatted]?.length / window.campaign_scripts.time_label_counts[time_formatted] * 100 : 0).toFixed(1)
        let min = time.toFormat(':mm')
        options.push({key: key, time_formatted: time_formatted, minute: min, hour: time.toFormat('hh a'), progress})
        key += this.slot_length.value * 60
      }
    // }
    return options;
  }

  build_list(selected_time){

    this.days.length;
    let duration = 15;
    let selected_times = []
    let days = this.days;
    let start_time = days[0].key + selected_time;
    let start_date = window.luxon.DateTime.fromSeconds(start_time, {zone:this.timezone})
    let now = new Date().getTime()/1000
    for ( let i = 0; i < days.length; i++){
      let time_date = start_date.plus({day:i})
      let time = parseInt( time_date.toFormat('X') );
      let time_label = time_date.toFormat('hh:mm a');
      let already_added = selected_times.find(k=>k.time===time)
      if ( !already_added && time > now && time >= calendar_subscribe_object['start_timestamp'] ) {
        selected_times.push({time: time, duration: duration, label: time_label, day_key:time_date.startOf('day'), date_time:time_date})
      }
    }
    let label = "Every Day at " + selected_times[0].date_time.toLocaleString({ hour: 'numeric', minute: 'numeric', hour12: true });
    this.selected_times_labels.push( {
      label: label,
      type: 'daily',
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
    let date_time = window.luxon.DateTime.fromSeconds(time, {zone:this.timezone});
    let label = date_time.toFormat('hh:mm a');
    let already_added = this.selected_times.find(k=>k.time===time)
    if ( !already_added && time > this.now && time >= this.campaign_data.start_timestamp ) {
      const selected_time = {time: time, duration: this.duration, label, day_key:date_time.startOf('day').toSeconds(), date_time:date_time}
      this.selected_times = [...this.selected_times, selected_time]
    }
    this.selected_times.sort((a,b)=>a.time-b.time)
    this.calendar_small = false
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

  render(){
    if ( this.days.length === 0 ){
      return html`<div class="loading"></div>`
    }
    let times = this.get_times();
    return html`
    <div id="campaign">
        <div class="column" style="max-width: 300px">
            <div class="section-div">
                <h2 class="section-title">
                    <span class="step-circle">1</span>
                    <span>Frequency</span>
                </h2>
                <div>
                    <cp-select 
                        .options="${this.frequency.options}"
                        .value="${this.frequency.value}"
                         @change="${this.handle_frequency}">
                    </cp-select>
                </div>
                <p>
                    Extend for more
                </p>
            </div>
            <div class="section-div">
                <h2 class="section-title"><span class="step-circle">2</span><span>Prayer Duration</span></h2>
                <div>
                    <cp-select 
                        .value="${this.slot_length.value}"
                        .options="${this.slot_length.options}"
                        @change="${e=>this.handle_click('slot_length', e.detail)}">
                    </cp-select>
                </div>
            </div>
        </div>
        <div class="column center-col">
            <div class="section-div">
                ${this.frequency.value === 'weekly' ? html`
                    <h2 class="section-title">
                        <span class="step-circle">3</span>
                        <span>Week Day</span>
                    </h2>
                    <div>
                        <cp-select 
                            .value="${this.week_day.value}"
                            .options="${this.week_day.options}"
                            @change="${e=>this.handle_click('week_day', e.detail)}">
                        </cp-select>
                    </div>

                ` : '' }
                
                ${['daily', 'weekly'].includes(this.frequency.value) ? html`
                
                  <h2 class="section-title">
                      <span class="step-circle">3</span>
                      <span>Prayer Time</span>
                  </h2>
                  <cp-times slot_length="${this.slot_length.value}" .times="${times}"
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
                            <cp-calendar style="display: flex;justify-content: center" 
                                class="size-item top-left ${this.calendar_small ? 'small' : ''}" @click="${()=>{this.calendar_small = false;this.requestUpdate();}}"
                                @day-selected="${e=>this.day_selected(e.detail)}"
                                .selected_times="${this.selected_times}"
                                start_timestamp="${this.campaign_data.start_timestamp}"
                                end_timestamp="${this.campaign_data.end_timestamp}"
                                .days="${this.days}"
                                .calendar_disabled="${this.calendar_small}"
                            ></cp-calendar>
                        </div>
                        <div>
                            <h2 class="section-title">
                                <span class="step-circle">4</span>
                                <span>Select a Time ${this.selected_day ? html`for ${window.campaign_scripts.ts_to_format(this.selected_day, this.timezone, 'DD')}` : ''}</span>
                            </h2>
                            <cp-times class="${!this.calendar_small ? 'small' : ''} size-item top-right"
                                slot_length="${this.slot_length.value}"
                                .times="${times}"
                                @time-selected="${e=>this.time_and_day_selected(e.detail)}" >
                        </div>
                        
                    </div>
                        
                    
                ` : ''}
                
            </div>
        </div>

        
        <div class="column" style="max-width: 300px">
            <div class="section-div">
                <h2 class="section-title">
                    <span class="step-circle">*</span>
                    <span>Selected Times (${this.selected_times.length})</span>
                </h2>
                ${this.selected_times_labels.map((value, index) => html`
                    <div class="selected-times selected-time-labels">
                        <span>${value.label}</span>
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
                        ${value.date_time.toLocaleString({ month: 'short', day: '2-digit', hour: '2-digit', minute: '2-digit' })}
                    </div>
                `)}
            </div>
            
        </div>

        <div class="column" style="max-width: 300px">
            <div class="section-div">
                <h2 class="section-title">
                    <span class="step-circle">4</span>
                    <span>Contact Info</span>
                </h2>

                <contact-info ?disabled="${true}"
                              @form-items=${this.handle_contact_info}
                              .form_error=${this._form_items.form_error}
                              @back=${()=>this._view = 'main'}
                ></contact-info>
            </div>

        </div>
    </div>
    `
  }
}
customElements.define('campaign-sign-up', CampaignSignUp);

