jQuery(document).ready(function ($) {
  const dtLandingDaySelector = document.getElementById("dt-landing-day-selector")
  const dtLandingDateDisplay = document.getElementById("dt-landing-date-display")
  const dtLandingDateSelector = document.getElementById("dt-landing-date-selector")
  const dtCampaignWizardToggle = document.getElementById("campaign-wizard-toggle")
  const dtCampaignWizard = document.getElementById("campaign-wizard")

  $(dtLandingDaySelector).on( 'change', handleDayChange)
  function handleDayChange(e) {
    const [ currentDay, currentDate ] = getCurrentDayDate()

    const newDay = parseInt(e.target.value)

    const dayDifference = newDay - currentDay

    const newDate = moment(currentDate).add(dayDifference, 'days')

    dtLandingDateDisplay.innerHTML = makeWpDate(newDate)
    dtLandingDateSelector.value = ''

    const wpDateDisplay = getWpDateDisplay()
    wpDateDisplay.innerHTML = makeWpDate(newDate)
  }

  $(dtLandingDateSelector).on( 'change', handleDateChange )
  function handleDateChange(e) {
    const wpDateDisplay = getWpDateDisplay()

    const newDate = e.target.value

    wpDateDisplay.innerHTML = makeWpDate(newDate)
    dtLandingDaySelector.value = calculateNewDay(newDate)
  }

  $(dtCampaignWizardToggle).on( 'click', handleToggleClick)
  function handleToggleClick(e) {
    $(dtCampaignWizard).toggle()
  }

  function getWpDateDisplay() {
    return document.querySelector(".edit-post-post-schedule__toggle")
  }

  function makeWpDate(date) {
    return moment(date).format('MMMM D, YYYY')
  }

  function getCurrentDayDate() {
    const currentDay = parseInt( dtLandingDaySelector.dataset["day"] )
    const currentDate = dtLandingDateDisplay.dataset["date"]

    return [ currentDay, currentDate ]
  }

  function calculateNewDay(newDate) {
    const [ currentDay, currentDate ] = getCurrentDayDate()

    /* calculate the difference of days between the dates */
    givenDate = moment(newDate).startOf('day')
    oldDate = moment(currentDate).startOf('day')
    diffInDays = moment.duration(givenDate.diff(oldDate)).asDays()

    /* return the currentDay adjusted by this difference */
    return parseInt( Math.round(currentDay + diffInDays) )
  }

  /**
   * CAMPAIGN CLONING
   */

  $('.clone-campaign-but').on('click', function (e) {
    const campaign_id = $(e.target).data('campaign_id');
    const clone_modal_new_name = $('#clone_modal_new_name');
    const clone_modal_campaign = $('#clone_modal_campaign');

    $(clone_modal_new_name).val('');
    $(clone_modal_campaign).val(campaign_id);

    const clone_dialog = jQuery('#clone_dialog');
    clone_dialog.dialog({
      modal: true,
      autoOpen: false,
      hide: 'fade',
      show: 'fade',
      height: 'auto',
      width: '450px',
      resizable: true,
      title: 'Clone Existing Campaign',
      buttons: {
        Close: function () {
          $(this).dialog('close');
        },
        Clone: function () {
          const new_name = $(clone_modal_new_name).val();
          if (!new_name) {
            $(clone_modal_new_name).focus();
          } else {
            const clone_modal_campaign_id = $(clone_modal_campaign).val();
            const clone_modal_buttons = $(this).parent().find('.ui-button');
            const clone_modal_spinner = $('#clone_modal_spinner');
            const clone_modal_msg = $('#clone_modal_msg');

            $(clone_modal_msg).fadeOut('fast');
            $(clone_modal_msg).text('');

            $(clone_modal_buttons).prop('disabled', true);
            $(clone_modal_spinner).fadeIn('fast', function () {

              const payload = {
                'new_name': new_name,
                'campaign_id': clone_modal_campaign_id
              };

              jQuery.ajax({
                type: 'POST',
                data: JSON.stringify(payload),
                contentType: 'application/json; charset=utf-8',
                dataType: 'json',
                url: window.dt_campaign_admin.root + 'admin/v1/clone_campaign',
                beforeSend: (xhr) => {
                  xhr.setRequestHeader("X-WP-Nonce", window.dt_campaign_admin.nonce);
                }
              }).then((response) => {
                console.log(response);
                $(clone_modal_spinner).fadeOut('fast', function () {
                  $(clone_modal_new_name).val('');
                  $(clone_modal_buttons).prop('disabled', false);

                  if ( response?.success ) {
                    clone_dialog.dialog('close');
                    window.location.reload();
                  } else {
                    $(clone_modal_msg).text( response?.msg ? response.msg : 'Failed to clone existing campaign!' );
                    $(clone_modal_msg).fadeIn('fast');
                  }
                });
              });
            });
          }
        }
      }
    });
    clone_dialog.dialog('open');
  });

  /**
   * Fields
   */
  $('.image-option').on('click', function (e) {
    const field_key = $(e.target).data('field');
    const image = $(e.target).data('src');
    //add src to value input
    $(`#${field_key}`).val(image);
    //show message
    $(`#${field_key}-row .images-selected`).show();
    //change image
    $(`#${field_key}-row .color-img`).last().attr('src', image);
    //add class to clicked image
    $(`#${field_key}-row .image-option`).removeClass('selected');
    $(e.target).addClass('selected');
  })
})
