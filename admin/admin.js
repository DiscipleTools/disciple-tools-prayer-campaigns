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

  new window.Foundation.Reveal( jQuery('#clone_modal') );
  $('.clone-campaign-but').on('click', function (e) {
    const campaign_id = $(e.target).data('campaign_id');

    $('#clone_modal_new_name').val('');
    $('#clone_modal_campaign').val(campaign_id);

    const clone_modal = jQuery('#clone_modal');
    clone_modal.foundation('open');
  });

  $('#clone_modal_close_but').on('click', function () {
    $('#clone_modal_new_name').val('');

    const clone_modal = jQuery('#clone_modal');
    clone_modal.foundation('close');
  });

  $('#clone_modal_clone_but').on('click', function () {
    const new_name_input = $('#clone_modal_new_name');
    const new_name = $(new_name_input).val();
    if (!new_name) {
      $(new_name_input).focus();
    } else {
      const clone_modal_campaign_id = $('#clone_modal_campaign').val();
      const clone_modal_close_but = $('#clone_modal_close_but');
      const clone_modal_clone_but = $('#clone_modal_clone_but');
      const clone_modal_spinner = $('#clone_modal_spinner');

      $(clone_modal_close_but).prop('disabled', true);
      $(clone_modal_clone_but).prop('disabled', true);
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
          $(clone_modal_spinner).fadeOut('fast', function () {
            $(new_name_input).val('');
            $(clone_modal_close_but).prop('disabled', false);
            $(clone_modal_clone_but).prop('disabled', false);

            window.location.reload();
          });
        });
      });
    }
  });

  /**
   * CAMPAIGN CLONING
   */
})
