console.log("here");

let current_time_zone = Intl.DateTimeFormat().resolvedOptions().timeZone || 'America/Chicago'

let calendar_subscribe_object = {
  number_of_time_slots: 0,
  coverage_levels: [],
  description: "",
  coverage_percentage: 0,
  second_level: 0,
  start_timestamp: 0,
  end_timestamp: 0,
  slot_length: 15,
  duration_options: {}
}
let escapeObject = (obj) => {
  return Object.fromEntries(Object.entries(obj).map(([key, value]) => {
    return [ key, window.lodash.escape(value)]
  }))
}

jQuery(document).ready(async function ($) {
  let jsObject = window.campaign_objects

  let data = await get_campaign_data()
  calendar_subscribe_object = { ...calendar_subscribe_object, ...data }
  calendar_subscribe_object.translations = escapeObject( jsObject.translations )
  console.log( calendar_subscribe_object );
  console.log(data)
  let days = window.campaign_scripts.calculate_day_times()
  console.log(days);
  draw_calendar()

  //draw progress circles
  window.customElements.define('progress-ring', ProgressRing);

  function draw_calendar( id = 'calendar-content' ){
    let week_day_names = days_for_locale(navigator.language, 'narrow')
    let headers = `
      <div class="day-cell week-day">${week_day_names[0]}</div>
      <div class="day-cell week-day">${week_day_names[1]}</div>
      <div class="day-cell week-day">${week_day_names[2]}</div>
      <div class="day-cell week-day">${week_day_names[3]}</div>
      <div class="day-cell week-day">${week_day_names[4]}</div>
      <div class="day-cell week-day">${week_day_names[5]}</div>
      <div class="day-cell week-day">${week_day_names[6]}</div>
    `
    let content = $(`#${window.lodash.escape(id)}`)
    content.empty()
    let list = ``
    let months = {};
    days.forEach(day=> {
      if (!months[day.month]) {
        months[day.month] = {with: 0, without: 0, key:day.key}
      }
      months[day.month].without += day.slots.length- day.covered_slots
    })

    Object.keys(months).forEach( (key, index) =>{
      //only show 2 months
      if ( index < 2 ){
        list += `<div class="calendar-month">
            <h3 class="month-title"><strong>${window.lodash.escape(key).substring(0,3)}</strong> ${new Date(months[key].key * 1000).getFullYear()}
            ${ months[key].with / months[key].without * 100 }%
            </h3>
            <div class="calendar">
        `
        list += headers
        let day_number = window.campaign_scripts.get_day_number(months[key].key, current_time_zone);
        //add extra days at the month start
        for (let i = 0; i < day_number; i++) {
          list += `<div class="day-cell disabled-calendar-day"></div>`
        }

        // fill in calendar
        days.filter(k=>k.month===key).forEach(day=>{
          if ( day.disabled ){
            list += `<div class="day-cell disabled-calendar-day">
          </div>`
          } else {
            list +=`
              <div class="display-day-cell" data-day=${window.lodash.escape(day.key)}>
                  <progress-ring stroke="3" radius="20" progress="${window.lodash.escape(day.percent)}" text="${window.lodash.escape(day.day)}"></progress-ring>
              </div>
            `
          }
        })

        //add extra days at the month end
        if (day_number!==0) {
          for (let i = 1; i <= 7 - day_number; i++) {
            list += `<div class="day-cell disabled-calendar-day"></div>`
          }
        }
        list += `</div></div>`
      }
    })

    content.html(`${list}`)
  }
  function days_for_locale(localeName = 'en-US', weekday = 'long') {
    let now = new Date()
    const format = new Intl.DateTimeFormat(localeName, { weekday }).format;
    return [...Array(7).keys()]
    .map((day) => format(new Date().getTime() - ( now.getDay() - day  ) * 86400000 ));
  }
  function get_campaign_data() {
    let link =  jsObject.root + jsObject.parts.root + '/v1/' + jsObject.parts.type + '/campaign_info';
    return jQuery.ajax({
      type: "GET",
      data: {action: 'get', parts: jsObject.parts, 'url': 'campaign_info', time: new Date().getTime()},
      contentType: "application/json; charset=utf-8",
      dataType: "json",
      url: link
    })
    .done(function (data) {
      return data
    })
  }
})
