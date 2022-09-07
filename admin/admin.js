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
})
