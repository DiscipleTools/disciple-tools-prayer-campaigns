jQuery(document).ready(function ($) {
  const dtLandingDaySelector = document.getElementById("dt-landing-day-selector")
  const dtLandingDateDisplay = document.getElementById("dt-landing-date-display")

  const currentDay = parseInt( dtLandingDaySelector.dataset["day"] )
  const currentDate = dtLandingDateDisplay.dataset["date"]

  console.log(currentDay, currentDate)

  dtLandingDaySelector.addEventListener( 'onchange', handleDayChange)

  $(dtLandingDaySelector).on( 'change', handleDayChange)

  function handleDayChange(e) {
    const newDay = parseInt(e.target.value)

    const dayDifference = newDay - currentDay

    const newDate = moment(currentDate).add(dayDifference, 'days').format('Y/MM/DD')

    dtLandingDateDisplay.innerHTML = newDate
  }
})
