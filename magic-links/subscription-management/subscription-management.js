jQuery(document).ready(function($){
  $('.nav-bar button').on('click', function (){
    $('.nav-bar button').removeClass('active')
    $(this).addClass('active')
    $('.display-panel').hide()
    $(`#${$(this).data('show')}`).show()
  })
})


import {html, css, LitElement} from 'https://cdn.jsdelivr.net/gh/lit/dist@2/all/lit-all.min.js';
const strings = window.campaign_scripts.escapeObject(window.campaign_objects.translations)

export class cpProfile extends LitElement {
  static styles = [
    window.campaignStyles,
    css`
      .profile-container {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
      }
      .contact-info {
        max-width: 500px;
      }
    `,
  ]

  static properties = {
    subscriber_data: {type: Object},
    hide_advanced_settings: {type: Boolean, state: true},
    _delete_modal_open: {type: Boolean, state: true},
    _show_error_message: {type: Boolean, state: true},
    show_spinner: {type: Boolean, state: true},
  }

  constructor() {
    super();
    this.loaded = false
    this.updates = {}
    this.hide_advanced_settings = true
    this._delete_modal_open = false;
    this._show_error_message = false;
    this.show_spinner = false
  }

  connectedCallback() {
    super.connectedCallback()
    window.campaign_scripts.get_campaign_data().then((data)=>{
      this.subscriber_data = window.campaign_user_data;
      this.loaded = true;
    })
  }



  render() {
    if ( !this.loaded ){
      return html``;
    }
    return html`
      <div class="profile-container">
      <div class="contact-info">
        <h2>
            Account Settings
        </h2>
        <div>
            <label>
                Name
                <input type="text"
                       @change="${e=>this.updates.name = e.target.value}"
                       value="${this.subscriber_data.name}">
            </label>
        </div>
        <div>
            <label>
                Email
                <input type="email"
                       @change="${e=>this.updates.email = e.target.value}"
                       value="${this.subscriber_data.email}">
            </label>
        </div>
        <div>
            <label>
                Timezone
                <select
                    @change="${e=>this.updates.timezone = e.target.value}"
                    >
                    <option value="">Select a timezone</option>
                    ${Intl.supportedValuesOf('timeZone').map(o=>{
                        return html`<option value="${o}" ?selected="${o===this.subscriber_data.timezone}">${o}</option>`
                    })}
                </select>
            </label>
        </div>
        <div>
            <label style="display: flex">
                Receive Prayer time notifications
                <input type="checkbox"
                       @change="${e=>this.updates.receive_prayer_time_notifications = e.target.checked}"
                       ?checked="${this.subscriber_data.receive_prayer_time_notifications}"
                >
            </label>
        </div>
        <div>
            <button class="loader ${this.show_spinner ? 'loading' : ''}" @click="${this.save_profile}">Save</button>
        </div>
        <div class="advanced-profile">
            <div style="display: flex">
              <h3>Advanced Settings</h3>
              <button class="clear-button" @click="${()=>this.hide_advanced_settings=!this.hide_advanced_settings}">show</button>
            </div>
            <div ?hidden="${this.hide_advanced_settings}">
                <dt-modal
                    .isOpen="${this._delete_modal_open}"
                    title="Delete Account"
                    hideButton="true"
                    confirmButtonClass="danger"
                    @close="${e=>this.delete_profile_modal_closed(e)}"
                >
                    <p slot="content" style="max-width:500px">
                        Need to cancel? We get it! But wait!
                        If your prayer commitment is scheduled to start in less than 48-hours, please ask a friend to cover it for you.
                        That will keep the prayer chain from being broken AND will give someone the joy of fighting for the lost! Thanks!</p>
                </dt-modal>
                
                <label style="margin-right: 10px">
                  Delete this account and all the scheduled prayer times
                </label>
                <button class="button danger" @click="${()=>this._delete_modal_open=true}" >Delete</button>
                <p class="form-error" ?hidden="${!this._show_error_message}">
                    So sorry. Something went wrong. Please, contact us to help you through it, or just try again.
                </p>
            </div>
        </div>
      </div>
      </div>
    `
  }

  save_profile(e){
    this.show_spinner = true;
    jQuery.ajax({
      type: "POST",
      data: JSON.stringify({parts: window.subscription_page_data.parts, updates:this.updates}),
      contentType: "application/json; charset=utf-8",
      dataType: "json",
      url: window.subscription_page_data.root + window.subscription_page_data.parts.root + '/v1/' + window.subscription_page_data.parts.type + '/update-profile',
      beforeSend: function (xhr) {
        xhr.setRequestHeader('X-WP-Nonce', window.subscription_page_data.nonce )
      }
    }).then((data)=>{
      this.show_spinner = false;
      this.updates = {}
    })
    if ( this.updates.timezone ){
      window.set_user_data({timezone: this.updates.timezone})
    }
  }

  delete_profile_modal_closed(e){
    this._delete_modal_open = false;
    if( e.detail.action === 'confirm' ){
      jQuery.ajax({
        type: "DELETE",
        data: JSON.stringify({parts: window.subscription_page_data.parts}),
        contentType: "application/json; charset=utf-8",
        dataType: "json",
        url: window.subscription_page_data.root + window.subscription_page_data.parts.root + '/v1/' + window.subscription_page_data.parts.type + '/delete_profile',
        beforeSend: function (xhr) {
          xhr.setRequestHeader('X-WP-Nonce', window.subscription_page_data.nonce )
        }
      }).done(function(){
        let wrapper = jQuery('#wrapper')
        wrapper.empty().html(`
          <div class="center">
          <h1>Your account has been deleted!</h1>
          <p>Thank you for praying with us.<p>
          </div>
      `)
      })
      .fail((e)=> {
        console.log(e)
        if ( e.status === 200 ){
          let wrapper = jQuery('#wrapper')
          wrapper.empty().html(`
              <div class="center">
              <h1>Your account has been deleted!</h1>
              <p>Thank you for praying with us.<p>
              </div>
          `)
          return
        }
        this._show_error_message = true;
      })
    }
  }
}
customElements.define('cp-profile', cpProfile);