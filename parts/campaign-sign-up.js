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
    _loading: {type: Boolean, state: true},
    selected_times: {type: Array},
  }

  constructor() {
    super()
    this._loading = false;
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
      receive_pray4movement_news: window.campaign_objects.dt_campaigns_is_p4m_news_enabled ? true : false,
    }
    this.now = new Date().getTime()/1000
    this.selected_day = null;
    this.undefined_prayer_time = false;
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
      let options = []
      if (this.campaign_data.slot_length <= 5) {
        options.push({value: 5, label: `${strings['%s Minutes'].replace('%s', 5)}`},)
      }
      if (this.campaign_data.slot_length <= 10) {
        options.push({value: 10, label: `${strings['%s Minutes'].replace('%s', 10)}`},)
      }
      if (this.campaign_data.slot_length <= 15) {
        options.push({value: 15, label: `${strings['%s Minutes'].replace('%s', 15)}`},)
      }
      if (this.campaign_data.slot_length <= 30) {
        options.push({value: 30, label: `${strings['%s Minutes'].replace('%s', 30)}`},)
      }
      if (this.campaign_data.slot_length <= 60) {
        options.push({value: 60, label: `${strings['%s Hour'].replace('%s', 1)}`},)
      }

      this.duration = {
        value: 15,
        options: options
      }
      this.week_day = {
        value: '',
        options: [
          {value: '1', label: translate('Mondays')},
          {value: '2', label: translate('Tuesdays')},
          {value: '3', label: translate('Wednesdays')},
          {value: '4', label: translate('Thursdays')},
          {value: '5', label: translate('Fridays')},
          {value: '6', label: translate('Saturdays')},
          {value: '7', label: translate('Sundays')},
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
    window.campaign_user_data.recurring_signups_combined.forEach(v=>{
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
      receive_pray4movement_news: this._form_items.receive_pray4movement_news,
      selected_times: selected_times,
      recurring_signups: window.campaign_user_data.recurring_signups_combined,
    }
    //add this._form_items
    Object.keys(this._form_items).forEach(key=>{
      data[key] = this._form_items[key]
    })

    window.campaign_scripts.submit_prayer_times(this.campaign_data.campaign_id, data)
    .done((response)=>{
      this.selected_times = [];
      this._loading = false;
      this._view = 'confirmation';
      this.requestUpdate()
    })
    .fail((e)=>{
      this._loading = false
      let message = html`So sorry. Something went wrong. Please, try again.<br>
          <a href="${window.campaign_scripts.escapeHTML(window.location.href)}">Try Again</a>`
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
      url: '',
      name: this._form_items.name,
      receive_pray4movement_news: this._form_items.receive_pray4movement_news,
      selected_times: this.selected_times,
      recurring_signups: window.campaign_user_data.recurring_signups_combined,
    }

    // Capture additional custom fields.
    Object.keys(this._form_items).forEach(key => {
      if ( !data.hasOwnProperty(key) ) {
        data[key] = this._form_items[key];
      }
    });

    window.campaign_scripts.submit_prayer_times(this.campaign_data.campaign_id, data)
    .done((response)=>{
      this.selected_times = [];
      this._loading = false;
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
      let message = html`${translate('So sorry. Something went wrong. You can:')} <br>
        <a href="${window.campaign_scripts.escapeHTML(window.location.href)}">${translate('Try Again')}</a> <a href="${window.campaign_scripts.escapeHTML(window.location.href)}">${translate('Contact Us')}</a>`
      if ( e?.responseJSON?.code === 'activate_account' ){
        message = translate('Please check your email to activate your account before adding more prayer times.')
      }


      this._form_items.form_error = message
      this._loading = false
      this.requestUpdate()
    })
  }


  show_toast(message='', type='success'){
    if ( !message ){
      message = strings["Prayer Time Selected"];
    }
    let background = '#4caf50';
    if ( type === 'warn' ){
      background = 'linear-gradient(to right, #f8b500, #f8b500)';
    }
    Toastify({
      text: message,
      duration: 1500,
      close: true,
      gravity: "bottom",
      position: "center",
      style: {
        background: background
      },
    }).showToast();
  }


  time_selected(selected_time, undefined_prayer_time){
    this.undefined_prayer_time = undefined_prayer_time
    if (!this.frequency.value ){
      return this.show_toast( 'Please check step 1', 'warn')
    }
    if ( this.frequency.value==='weekly' && !this.week_day.value ){
      return this.show_toast( 'Please check step 3', 'warn')
    }
    if ( this.frequency.value === 'pick' ){
      return this.time_and_day_selected(selected_time)
    }
    console.log(this.undefined_prayer_time);
    let recurring_signup = window.campaign_scripts.build_selected_times_for_recurring(selected_time||0, this.frequency.value, this.duration.value, this.week_day.value, null, this.undefined_prayer_time)
    console.log(recurring_signup);
    if ( recurring_signup ){
      //keep this new recurring signup from overlapping with an existing one
      let has_overlay = this.recurring_signups.find(
        k => k.type===recurring_signup.type && (
          (k.first.toSeconds() < recurring_signup.first.toSeconds()
            && k.first.toSeconds() + k.duration * 60 > recurring_signup.first.toSeconds())
          ||
          (k.first.toSeconds() > recurring_signup.first.toSeconds()
            && k.first.toSeconds() < recurring_signup.first.toSeconds() + k.duration * 60)
        ))
      if ( has_overlay ){
        return;
      }
      this.recurring_signups = [...this.recurring_signups, recurring_signup]
      window.campaign_user_data.recurring_signups = this.recurring_signups;
      window.campaign_scripts.combine_recurring_signups()
      this.show_toast()
      this.requestUpdate()
    } else {
      //remove time from selected
      let index = this.recurring_signups.findIndex(k=>k.time===selected_time)
      if ( index > -1 ){
        this.recurring_signups.splice(index,1)
        window.campaign_user_data.recurring_signups = this.recurring_signups;
        window.campaign_scripts.combine_recurring_signups()
        this.show_toast(translate('Prayer Time Removed'), 'warn')
        this.requestUpdate()
      }
    }
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

  remove_recurring_prayer_time(root, duration, type){
    this.recurring_signups = this.recurring_signups.filter(k=> !(k.root >= root && k.root <= root + duration * 60 && k.type===type))
    window.campaign_user_data.recurring_signups = this.recurring_signups;
    window.campaign_scripts.combine_recurring_signups()
    this.requestUpdate()
  }

  remove_prayer_time(time){
    this.selected_times = this.selected_times.filter(k=>k.time!==time)
    this.requestUpdate()
  }

  duration_section(position){
    if ( !this.duration?.value ){
      return html``;
    }
    return html`
      <!--
          Duration
      -->
      <div class="section-div">
          <h2 class="section-title">
              <span class="step-circle">${position}</span>
              <span>${translate('I will pray for')}</span></h2>
          <div>
              <cp-select
                  .value="${this.duration.value}"
                  .options="${this.duration.options}"
                  @change="${e=>this.handle_click('duration', e.detail)}">
              </cp-select>
          </div>
      </div>
    `
  }

  frequency_section(position){
    if ( !this.frequency?.value ){
      return html``;
    }
    return html`
      <!--
          FREQUENCY
      -->
      <div class="section-div">
          <h2 class="section-title">
              <span class="step-circle">${position}</span>
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
    `
  }

  week_day_section( position ){
    if ( !this.frequency?.value ){
      return html``;
    }
    return html`
      <!--
        Week Day
      -->

      <h2 class="section-title">
          <span class="step-circle">${position}</span>
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

    `
  }

  calendar_picker_section(position){
    return html`
    <!--
        Calendar Picker
    -->
    <h2 class="section-title">
        <span class="step-circle">${position}</span>
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
    `
  }

  time_picker_section(position){

    let time_picker = html``
    if ( window.campaign_data.campaign_goal === '247coverage' ){
      time_picker = html`
        <cp-times
            slot_length="${this.campaign_data.slot_length}"
            .frequency="${this.frequency.value}"
            .weekday="${this.week_day.value}"
            .selected_day="${this.selected_day}"
            .selected_times="${this.selected_times}"
            .recurring_signups="${[]}"
            @time-selected="${e => this.time_selected(e.detail, false)}">
        </cp-times>
      `
    } else {
      time_picker = html`
        <cp-simple-times
            slot_length="${this.campaign_data.slot_length}"
            .frequency="${this.frequency.value}"
            .weekday="${this.week_day.value}"
            .selected_day="${this.selected_day}"
            .selected_times="${this.selected_times}"
            .selected_duration="${this.duration.value}"
            .undefined_prayer_time="${this.undefined_prayer_time}"
            .recurring_signups="${[]}"
            @time-selected="${e => this.time_selected(e.detail.time, e.detail.undefined_prayer_time)}">
        </cp-simple-times>
      `
    }


    return html`
        <div class="section-div" ?disabled="${!this.frequency.value || this.frequency.value==='weekly' && !this.week_day.value}">

            <h2 class="section-title">
                <span class="step-circle">${position}</span>
                <span>
                    ${this.frequency.value==='pick' ? (
                        this.selected_day ?
                            html`${translate('Select a Time for %s').replace('%s', window.campaign_scripts.ts_to_format(this.selected_day, 'DD', this.timezone))}`
                            :html`${translate('Select a Day')}`
                    ):html`${translate('At what time?')}`}
                </span>
                <span
                    ?hidden="${!(this.recurring_signups.length===0 && this.selected_times.length===0) || !(this.frequency.value==='daily' || this.week_day.value || this.selected_day)}"
                    class="place-indicator">${strings['Continue here']}</span>
            </h2>
            ${time_picker}
        </div>
    `
  }

  contact_info_section(position){
    return html`
        <div class="section-div" ?hidden="${this.already_signed_up}">
            <h2 class="section-title">
                <span class="step-circle">${position}</span>
                <span>${strings['Contact Info']}</span>
                <span ?hidden="${this.recurring_signups.length === 0  && this.selected_times.length === 0}" class="place-indicator">${strings['Continue here']}</span>
            </h2>

            <contact-info .selected_times_count="${this.selected_times_count()}"
                          ._loading="${this._loading}"
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
                <span class="step-circle">${position}</span>
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


    `
  }

  selected_times_section() {
    return html`
        <!--
              Mobile Times Floater
          -->
        <div class="mobile selected-times"
             style="padding: 0.5rem; position: fixed; top:60px; right: 0; z-index: 10000;background-color: white; border:1px solid var(--cp-color); ${this.selected_times_count() ? '':'display:none'}">
            <div style="text-align: end;display: flex;justify-content: space-between" @click="${e => {
                this.show_selected_times = !this.show_selected_times;
                this.requestUpdate()
            }}">
                <button ?hidden="${!this.show_selected_times}" class="button" style="padding:0.25rem 0.85rem">
                    ${strings['Close']}
                </button>
                <span style="display: flex; align-items: center">
                    <img src="${window.campaign_objects.plugin_url}assets/calendar.png" style="width: 2rem;">
                    <span>
                      (${this.selected_times_count()} <span
                        ?hidden="${!this.show_selected_times}">${strings['prayer commitments']}</span>)
                    </span>
                </span>
            </div>
            <div ?hidden="${!this.show_selected_times}" style="margin-top:1rem; max-height:50%; overflow-y: scroll">
                ${window.campaign_user_data.recurring_signups_combined.map((value, index) => {
                    let last_prayer_time_near_campaign_end = this.campaign_data.end_timestamp && (value.last > this.campaign_data.end_timestamp - 86400 * 30)
                    return html`
                        <div class="selected-times selected-time-labels">
                            <div class="selected-time-frequency">
                                <div>${value.label}</div>
                                <div>
                                    <button @click="${e => this.remove_recurring_prayer_time(value.root, value.duration, value.type)}"
                                            class="remove-prayer-time-button">
                                        <img src="${window.campaign_objects.plugin_url}assets/delete-red.svg">
                                    </button>
                                </div>
                            </div>
                            <ul>
                                <li>
                                    ${strings['Starting on %s'].replace('%s', value.first.toLocaleString({
                                        month: 'long',
                                        day: 'numeric'
                                    }))}
                                </li>
                                <li>
                                    ${translate(last_prayer_time_near_campaign_end ? 'Ends on %s':'Renews on %s').replace('%s', value.last.toLocaleString({
                                        month: 'long',
                                        day: 'numeric'
                                    }))}
                                </li>
                            </ul>
                        </div>
                    `
                })}
                ${this.selected_times.map((value, index) => html`
                    <div class="selected-times">
                        <span>${value.date_time.toLocaleString({
                            month: 'short',
                            day: '2-digit',
                            hour: '2-digit',
                            minute: '2-digit'
                        })}</span>
                        <button @click="${e => this.remove_prayer_time(value.time)}" class="remove-prayer-time-button">
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
            ${window.campaign_user_data.recurring_signups_combined.map((value, index) => {
                let last_prayer_time_near_campaign_end = this.campaign_data.end_timestamp && (value.last > this.campaign_data.end_timestamp - 86400 * 30)
                return html`
                    <div class="selected-times selected-time-labels">
                        <div class="selected-time-frequency">
                            <div>${value.label}</div>
                            <div>
                                <button @click="${e => this.remove_recurring_prayer_time(value.root, value.duration, value.type)}"
                                        class="remove-prayer-time-button"><img
                                    src="${window.campaign_objects.plugin_url}assets/delete-red.svg"></button>
                            </div>
                        </div>
                        <ul>
                            <li>
                                ${strings['Starting on %s'].replace('%s', value.first.toLocaleString({
                                    month: 'long',
                                    day: 'numeric'
                                }))}
                            </li>
                            <li>
                                ${translate(last_prayer_time_near_campaign_end ? 'Ends on %s':'Renews on %s').replace('%s', value.last.toLocaleString({
                                    month: 'long',
                                    day: 'numeric'
                                }))}
                            </li>
                        </ul>
                    </div>
                `
            })}
            ${this.selected_times.map((value, index) => html`
                <div class="selected-times">
                          <span class="aligned-row">
                              ${value.date_time.toLocaleString({month: 'short', day: '2-digit'})},
                              <span class="dt-tag">${value.date_time.toLocaleString({
                                  hour: '2-digit',
                                  minute: '2-digit'
                              })}</span>
                              ${translate('for %s minutes').replace('%s', value.duration)}
                          </span>
                    <button @click="${e => this.remove_prayer_time(value.time)}" class="remove-prayer-time-button">
                        <img src="${window.campaign_objects.plugin_url}assets/delete-red.svg">
                    </button>
                </div>
            `)}
        </div>
    `
  }

  verify_section(position) {
    return html`
        <!--
            Verify
        -->
        <div class="column">
            <div class="section-div">
                <h2 class="section-title" style="display: flex">
                    <span class="step-circle" style="background-color: red"></span>
                    <span style="flex-grow: 1">${translate('Pending - Verification Needed')}</span>
                </h2>
                <cp-verify
                    email="${this._form_items.email}"
                    @code-changed=${e => {
                        this._form_items.code = e.detail;
                        this.requestUpdate()
                    }}
                ></cp-verify>
                <button @click="${() => this._view = 'main'}">${translate('Back to sign-up')}</button>
                <div class='form-error'
                     ?hidden=${!this._form_items?.code_error}>
                    ${this._form_items?.code_error}
                </div>
            </div>
        </div>
        </div>
    `
  }

  confirmation_section() {
    return html`
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
                <button @click=${() => window.location.reload()}>${translate('Ok')}</button>
                ${window.campaign_objects.remote ? ``:
                    html`<a class="button" href="${window.campaign_objects.campaign_root + '/list'}">${translate('See Prayer Fuel')}`}</a>
            </div>

        </div>
    `
  }

  campaign_ended_section(){
    return html`
        <div class="section-div">
            <h2 class="section-title">
                <span class="step-circle">!</span>
                <span style="flex-grow: 1">${translate('Campaign Ended')}</span>
            </h2>
            <br>
            <br>
            <div>
                <a class="button" href="${window.campaign_objects.campaign_root + '/list'}">${translate('See Prayer Fuel')}</a>
            </div>
        </div>
    `
  }

  render(){
    let display = [
      {
        key: 'col1',
        show: this._view === 'main',
        sections: [
          { key: 'duration', show: true },
          { key: 'frequency', show: true },
          { key: 'week_day', show: this.frequency?.value === 'weekly' },
          { key: 'calendar_picker', show: this.frequency?.value === 'pick' }
        ]
      },
      {
        key: 'col2',
        show: this._view === 'main',
        sections: [
          { key: 'time_picker', show: true}
        ]
      },
      {
        key: 'col3',
        show: this._view === 'main',
        sections: [
          { key: 'contact_info', show: true },
          { key: 'selected_times', show: true}

        ]
      },
      {
        key: 'verify',
        show: this._view === 'submit',
        sections: [
          { key: 'verify', show: true }
        ]

      },
      {
        key: 'confirmation',
        show: this._view === 'confirmation',
        sections: [
          { key: 'confirmation', show: true }
        ]
      },

    ]
    if ( this.campaign_data.end_timestamp && this.campaign_data.end_timestamp < this.now ){
      display = [
        {
          key: 'campaign_ended',
          show: this.campaign_data.end_timestamp && this.campaign_data.end_timestamp < this.now,
          sections: [
            { key: 'campaign_ended', show: true }
          ]
        }
      ]
    }

    if ( this.days.length === 0 ){
      return html`<div class="loading"></div>`
    }
    if ( !this.frequency ){
      return;
    }

    let index = 0
    return html`
        <div id="campaign">

            ${display.map(column => {
                if (column.show) {
                    return html`
                        <div class="column">
                            ${column.sections.map(section => {
                                if (section.show) {
                                    index += 1
                                    return this[section.key + '_section'](index)
                                }
                            })}
                        </div>
                    `
                }
            })}
        </div>
      `
  }
}
customElements.define('campaign-sign-up', CampaignSignUp);



export class cpCalendar extends LitElement {
  static styles = [
    css`
    :host {
    }
      .calendar-wrapper {
        container-type: inline-size;
        container-name: cp-calendar;
        background-color: #f8f9fad1;
        border-radius: 10px;
        padding: 1em;
        display: block;
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
        grid-template-columns: repeat(7, 12.5cqw);
        gap: 0.3rem;
        margin-bottom: 1rem;
        justify-items: center;
      }
      .day-cell {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 14cqw;
        width: 14cqw;
        font-size: 15px;
      }

      @container cp-calendar (min-width: 250px) {
        .day-cell {
          height: 15cqw;
          width: 15cqw;
        }
        .week-day {
          height: 15cqw;
          width: 15cqw;
        }
      }
      .week-day {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 14cqw;
        width: 14cqw;
        color:black;
        font-size: clamp(1em, 2cqw, 0.5em + 1cqi);
        font-weight:550;
      }

      @container cp-calendar (min-width: 350px) {
        .week-day {
          height: 7.5cqw;
          width: 15cqw;
        }
      }

      .loading {
        min-height: 600px;
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
    let now = new Date().getTime()/1000;
    let now_date = window.luxon.DateTime.fromSeconds(Math.max(now, this.campaign_data.start_timestamp))
    //when campaign has ended, show the last month
    if ( this.campaign_data.end_timestamp && this.campaign_data.end_timestamp < now ){
      if ( this.campaign_data.end_timestamp - this.campaign_data.start_timestamp < 86400 * 60 ){
        now_date = window.luxon.DateTime.fromSeconds(this.campaign_data.start_timestamp)
      } else {
        now_date = window.luxon.DateTime.fromSeconds(this.campaign_data.end_timestamp).minus({month:1})
      }
    }
    let months_to_show = [];
    for( let i = 0; i < 2; i++ ){
      let next_month = now_date.startOf('month').plus({month:i})
      if ( this.campaign_data.end_timestamp && next_month.toSeconds() > this.campaign_data.end_timestamp ){
        continue;
      }

      let month_days = window.campaign_scripts.build_calendar_days(now_date.plus({month:i}))
      let covered_slots = 0; // time slots with at least 1 person
      let total_slots = 0; // all the time slots in the day (different on daylight savings days)
      let total_times = 0 // have many prayer times are happening that day
      let goal_hours = window.campaign_data.campaign_goal === 'quantity' ? ( window.campaign_data.goal_quantity || 24 ) : 24;

      month_days.forEach(day=>{
        covered_slots += day.covered_slots || 0
        total_slots += day.slots.length || 0
        total_times += Math.min(day.total_times, goal_hours * 60 / window.campaign_data.slot_length ) || 0
      })

      let percentage_of = window.campaign_data.campaign_goal !== '247coverage' ? total_times : covered_slots;
      if ( window.campaign_data.campaign_goal === 'quantity' ){
        total_slots = total_slots * goal_hours / 24
      }
      months_to_show.push({
        date: now_date.plus({month:i}),
        days: month_days,
        percentage: ((total_slots ? ( percentage_of / total_slots ) : 0 ) * 100).toFixed( 2 ),
        days_of_prayer: ( this.campaign_data.slot_length * covered_slots / 60 / 24 ).toFixed( 1 ), //quantity of days for prayer
      })
    }

    let week_day_names = window.campaign_scripts.get_days_of_the_week_initials(navigator.language, 'narrow')

    return html`
        <div class="calendar-wrapper ${this.loading ? 'loading' : ''}">
            ${months_to_show.map(month=>html`
                <div class="calendar-month">
                    <h3 class="month-title center">
                        ${month.date.toLocaleString({ month: 'short', year: 'numeric' })}
                        <span class="month-percentage">${ month.percentage || 0 }% | ${month.days_of_prayer || 0} ${translate('days')}</span>

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
                                ${ ( day.disabled && ( day.key < window.campaign_data.start_timestamp || day.key > window.campaign_data.end_timestamp ) ) ? window.campaign_scripts.escapeHTML(day.day) : html`
                                    <progress-ring class="progress-ring" progress="${window.campaign_scripts.escapeHTML(day.percent)}" text="${window.campaign_scripts.escapeHTML(day.day)}"></progress-ring>
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
    const message = this.campaign_data.campaign_goal==='quantity' ?
      translate('Goal: %s hours of prayer every day').replace('%s', this.campaign_data.goal_quantity || 24):
      translate('Goal: 24/7 coverage')

    return html`
    <div class="cp-progress-wrapper cp-wrapper">
        <div id="main-progress" class="cp-center" style="display: flex;justify-content: center">
            <progress-ring
               style="max-width: 150px"
               progress="${this.campaign_data.coverage_percent || 0}"
               progress2="0"
               text="${this.campaign_data.coverage_percent || 0}%"
               text2="">
            </progress-ring>
        </div>
        <div style="color: rgba(0,0,0,0.57); text-align: center">${message}</div>
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
    _renew_modal_open: {type: Boolean, state: true},
    _renew_modal_message: {type: String, state: true},
    _change_times_modal_open: {type: String, state: true},
  }

  constructor() {
    super();
    this.selected_recurring_signup_to_delete = null;
    this._selected_time_to_delete = null;
    this._delete_modal_open = false;
    this._extend_modal_open = false;
    this._renew_modal_open = false;
    this._change_times_modal_open = false;
    this._extend_modal_message = 'Def';
    this.change_time_details = null
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
    let now = new Date().getTime() / 1000;
    this.selected_times = window.campaign_data.subscriber_info.my_commitments;
    this.my_recurring = window.campaign_data.subscriber_info.my_recurring;
    this.recurring_signups = window.campaign_data.subscriber_info.my_recurring_signups;
    this.recurring_signups.sort((a,b)=>b.last-a.last);
    return html`
        <!--delete modal-->
        <dt-modal
            .isOpen="${this._delete_modal_open}"
            title="${translate('Stop Praying')}"
            hideButton="true"
            confirmButtonClass="danger"
            @close="${e=>this.delete_times_modal_closed(e)}"
        >
        <p slot="content">${translate('Your future prayer times will be canceled.')}</p>
        </dt-modal>
        <dt-modal
            .isOpen="${this._delete_time_modal_open}"
            title="${translate('Delete Prayer Time')}"
            hideButton="true"
            confirmButtonClass="danger"
            @close="${e=>this.delete_time_modal_closed(e)}"
        >
        <p slot="content">${translate('Really delete this prayer time?')}</p>
        </dt-modal>

        <!--extend modal-->
        <dt-modal
            .isOpen="${this._extend_modal_open}"
            .content="${this._extend_modal_message}"
            title="${translate('Extend Prayer Times')}"
            hideButton="true"
            confirmButtonClass="danger"
            @close="${e=>this.extend_times_modal_closed(e)}" >
        </dt-modal>
        <!--extend modal-->
        <dt-modal
            .isOpen="${this._renew_modal_open}"
            .content="${this._renew_modal_message}"
            title="${translate('Renew Prayer Times')}"
            hideButton="true"
            confirmButtonClass="danger"
            @close="${e=>this.extend_times_modal_closed(e, true)}" >
        </dt-modal>

        <!--change times modal-->
        <dt-modal
            .isOpen="${this._change_times_modal_open}"
            title="${translate('Change Prayer Time')}"
            hideButton="true"
            confirmButtonClass="danger"
            @close="${e=>this.change_times_modal_closed(e)}" >
            <p slot="content">${ this.change_time_details ? html`
                ${translate('Your current prayer time is %s').replace('%s', window.luxon.DateTime.fromSeconds(this.change_time_details.first, {zone: this.timezone}).toLocaleString({ hour: '2-digit', minute: '2-digit' }))}
                <br>
                <br>
                <strong>${translate('Select a new time:')}</strong>
                ${this.build_select_for_day_times()}
            ` : ''}</p>
        </dt-modal>

        ${(this.recurring_signups||[]).map((value, index) => {
            let last_prayer_time_near_campaign_end = this.campaign_data.end_timestamp && ( value.last > this.campaign_data.end_timestamp - 86400 * 30 )
            let day_in_seconds = 86400
            //in the next 60 days and not more than 2 weeks old
            let extend_enabled = !last_prayer_time_near_campaign_end && value.last < now + day_in_seconds * 60 && value.last > now - day_in_seconds * 14
            //more than 2 weeks old
            let renew_extended = !last_prayer_time_near_campaign_end && value.last < now - day_in_seconds * 14

            const prayer_times = window.campaign_data.subscriber_info.my_commitments.filter(c=>value.report_id==c.recurring_id)
            return html`
            <div class="selected-times">
                <div class="selected-time-content">
                  <div class="title-row">
                      <h3>${window.luxon.DateTime.fromSeconds(value.first, {zone: this.timezone}).toFormat('DD')} - ${window.luxon.DateTime.fromSeconds(value.last, {zone:this.timezone}).toFormat('DD')}</h3>
                      <button ?hidden="${!extend_enabled}" class="clear-button" @click="${()=>this.open_extend_times_modal(value.report_id)}">${translate('extend')}</button>
                      <button ?hidden="${!renew_extended}" class="clear-button" @click="${()=>this.open_extend_times_modal(value.report_id, true)}">${translate('renew')}</button>
                  </div>
                  <div>
                      <strong>${window.campaign_scripts.recurring_time_slot_label(value)}</strong>
                      <button @click="${e=>this.open_change_time_modal(e,value.report_id)}"
                          class="clear-button">${translate('change time')}</button>
                  </div>
                  <div class="selected-time-actions">
                      <button class="clear-button" @click="${e=>{value.display_times=!value.display_times;this.requestUpdate()}}">
                          ${translate('See prayer times')} (${prayer_times.length})
                      </button>
                      <button class="clear-button danger loader" @click="${e=>this.open_delete_times_modal(e,value.report_id)}">
                          ${translate('Stop Praying').toLowerCase()}
                      </button>
                  </div>
                </div>
                <div style="margin-top:20px" ?hidden="${!value.display_times}">
                    ${prayer_times.map(c=>html`
                        <div class="remove-row">
                            <span>${window.luxon.DateTime.fromSeconds(parseInt(c.time_begin), {zone: this.timezone}).toLocaleString({ month: 'short', day: '2-digit', hour: '2-digit', minute: '2-digit' })}</span>
                            <button ?disabled="${c.time_begin < now}" @click="${e=>this.open_delete_time_modal(e,c.report_id)}"
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
                    <button ?disabled="${value.time_begin < now}" class="clear-button danger loader remove-prayer-times-button" @click="${e=>this.open_delete_time_modal(e,value.report_id)}">
                        <img src="${window.campaign_objects.plugin_url}assets/delete-red.svg">
                    </button>
                  </div>
                </div>
            </div>
        `})}

    `
  }

  build_select_for_day_times(){
    let times = window.campaign_scripts.get_empty_times()
    return html`<select @change="${e=>this.change_time_details.new_time = e.target.value}">
        <option value="">${translate('Select a time')}</option>
        ${times.map(time=>html`<option value="${time.key}">${time.time_formatted}</option>`)}
    </select>
    `;
  }

  delete_recurring_time(){
    let data = {
      action: 'delete_recurring_signup',
      report_id: this.selected_recurring_signup_to_delete,
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
    let report_to_delete = this._selected_time_to_delete
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
      window.campaign_data.subscriber_info.my_commitments = window.campaign_data.subscriber_info.my_commitments.filter(k=>k.report_id!==report_to_delete)
      this._selected_time_to_delete = null;
      this._delete_time_modal_open = false;
      this.requestUpdate()
    })
  }

  open_delete_times_modal(e,report_id){
    const recurring_sign = this.recurring_signups.find(k=>k.report_id===report_id)
    if ( !recurring_sign ){
      return;
    }
    this.selected_recurring_signup_to_delete = report_id
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
    if ( e.detail?.action === 'confirm' ){
      this.delete_time()
    }
    this._selected_time_to_delete = null;
    this._delete_time_modal_open = false;
  }

  open_extend_times_modal(report_id, renew){
    const recurring_sign = this.recurring_signups.find(k=>k.report_id===report_id)
    if ( !recurring_sign ){
      return;
    }
    this.selected_recurring_signup_to_extend = report_id
    let frequency_option = window.campaign_data.frequency_options.find(k=>k.value===recurring_sign.type)

    if ( renew ){
      this._renew_modal_message = ("Renew for %s months?").replace('%s', frequency_option.month_limit );
      this._renew_modal_open = true;
    } else {
      this._extend_modal_message = ("Extend for %s months?").replace('%s', frequency_option.month_limit );
      this._extend_modal_open = true;
    }
  }

  extend_times_modal_closed(e, renew = false){
    this._extend_modal_open = false;
    if ( e.detail?.action === 'confirm' ){
      let recurring_sign = this.recurring_signups.find(k=>k.report_id===this.selected_recurring_signup_to_extend)
      if ( !recurring_sign ){
        return;
      }

      if ( !recurring_sign.time || recurring_sign.time == 0 ){
        recurring_sign.time = recurring_sign.first % ( 86400 * 24 )
      }

      let recurring_extend = window.campaign_scripts.build_selected_times_for_recurring(
        recurring_sign.time, recurring_sign.type,
        recurring_sign.duration,
        recurring_sign.week_day || null,
        renew ? null : recurring_sign.last, );
      recurring_extend.report_id = recurring_sign.report_id

      //filter out existing times
      let existing_times = window.campaign_data.subscriber_info.my_commitments.filter(c=>recurring_sign.report_id === c.recurring_id).map(c=>parseInt(c.time_begin))


      recurring_extend.selected_times = recurring_extend.selected_times.filter(c=>!existing_times.includes(c.time))

      if ( renew ){
        let data = {
          recurring_signups: [recurring_extend],
        }
        window.campaign_scripts.submit_prayer_times( recurring_sign.campaign_id, data, 'add').then(resp=>{
          window.location.reload() //@todo replace with event
        })

      } else {
        window.campaign_scripts.submit_prayer_times( recurring_sign.campaign_id, recurring_extend, 'update_recurring_signup').then(resp=>{
          window.location.reload() //@todo replace with event
        })
      }
    }
  }


  open_change_time_modal(e,report_id){
    const recurring_sign = this.recurring_signups.find(k=>k.report_id===report_id)
    if ( !recurring_sign ){
      return;
    }
    this.change_time_details = recurring_sign
    this._change_times_modal_open = true;
  }

  change_times_modal_closed(e){
    if ( e.detail?.action === 'confirm' && this.change_time_details?.time ){
      let data = {
        report_id: this.change_time_details.report_id,
        offset: this.change_time_details.new_time - this.change_time_details.time,
        time: this.change_time_details.new_time
      }

      window.campaign_scripts.submit_prayer_times( this.change_time_details.campaign_id, data, 'change_times').then(response=>{
        window.location.reload()
      })
    }
    this.change_time_details = null
    this._change_times_modal_open = false;
  }
}
customElements.define('campaign-subscriptions', campaignSubscriptions);


