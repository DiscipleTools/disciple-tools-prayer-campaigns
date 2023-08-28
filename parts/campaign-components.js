import {html, css, LitElement, range, map} from 'https://cdn.jsdelivr.net/gh/lit/dist@2/all/lit-all.min.js';
const strings = window.campaign_scripts.escapeObject(window.campaign_components.translations)

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