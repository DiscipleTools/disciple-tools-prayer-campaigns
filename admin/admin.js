jQuery(document).ready(function ($) {
  const dtLandingDaySelector = document.getElementById("dt-landing-day-selector")
  const dtLandingDateDisplay = document.getElementById("dt-landing-date-display")
  const dtLandingDateSelector = document.getElementById("dt-landing-date-selector")

  const currentDay = parseInt( dtLandingDaySelector.dataset["day"] )
  const currentDate = dtLandingDateDisplay.dataset["date"]

  $(dtLandingDaySelector).on( 'change', handleDayChange)
  function handleDayChange(e) {
    const newDay = parseInt(e.target.value)

    const dayDifference = newDay - currentDay

    const newDate = moment(currentDate).add(dayDifference, 'days').format('Y/MM/DD')

    dtLandingDateDisplay.innerHTML = makeWpDate(newDate)

    const wpDateDisplay = getWpDateDisplay()
    wpDateDisplay.innerHTML = makeWpDate(newDate)
  }

  $(dtLandingDateSelector).on( 'change', handleDateChange )
  function handleDateChange(e) {
    const wpDateDisplay = getWpDateDisplay()

    wpDateDisplay.innerHTML = makeWpDate(e.target.value)
  }

  function getWpDateDisplay() {
    return document.querySelector(".edit-post-post-schedule__toggle")
  }

  function makeWpDate(date) {
    return moment(date).format('MMMM D, YYYY')
  }
})
