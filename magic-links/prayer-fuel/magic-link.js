jQuery(document).ready(function($) {
  /**
   * Extend prayer times if needed
   */
  window.campaign_scripts.get_campaign_data().then((data) => {
    const recurring_signups = data?.subscriber_info?.my_recurring_signups
    if (recurring_signups.length) {
      recurring_signups.forEach((recurring_sign) => {

        //extend the prayer times if the last time is within the next two months
        const two_months_from_now = (new Date().getTime() / 1000) + 60 * day_in_seconds
        const now = new Date().getTime() / 1000
        if (recurring_sign.last > now && recurring_sign.last < two_months_from_now) {
          let recurring_extend = window.campaign_scripts.build_selected_times_for_recurring(
            recurring_sign.time, recurring_sign.type,
            recurring_sign.duration,
            recurring_sign.week_day || null,
            recurring_sign.last
          );
          recurring_extend.report_id = recurring_sign.report_id

          //filter out existing times
          let existing_times = window.campaign_data.subscriber_info.my_commitments.filter(c => recurring_sign.report_id===c.recurring_id).map(c => parseInt(c.time_begin))
          recurring_extend.selected_times = recurring_extend.selected_times.filter(c => !existing_times.includes(c.time))
          recurring_extend.auto_extend = true
          window.campaign_scripts.submit_prayer_times(recurring_sign.campaign_id, recurring_extend, 'update_recurring_signup').then(resp => {
            // console.log(resp);
            //@todo
          })
        }
      })
    }
  })
})