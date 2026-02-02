<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

class DT_Porch_Admin_Tab_Group_Reports extends DT_Porch_Admin_Tab_Base {

    public $title = 'Group Reports';
    public $key = 'group-reports';

    public function __construct() {
        parent::__construct( $this->key );
        add_action( 'dt_prayer_campaigns_tab_content', [ $this, 'dt_prayer_campaigns_tab_content' ], 10, 2 );
        add_filter( 'prayer_campaign_tabs', [ $this, 'prayer_campaign_tabs' ], 25, 1 );
    }

    public function prayer_campaign_tabs( $tabs ) {
        $tabs[ $this->key ] = $this->title;
        return $tabs;
    }

    public function dt_prayer_campaigns_tab_content( $tab, $campaign_id ) {
        if ( $tab !== $this->key || empty( $campaign_id ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_dt' ) && ! current_user_can( 'wp_api_allowed_user' ) ) {
            return;
        }

        $this->handle_actions( $campaign_id );
        $this->render_content( $campaign_id );
    }

    private function handle_actions( $campaign_id ) {
        // Handle delete action
        if ( isset( $_GET['action'], $_GET['report_id'], $_GET['_wpnonce'] ) && $_GET['action'] === 'delete' ) {
            if ( wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'delete_group_report' ) ) {
                $report_id = absint( $_GET['report_id'] );
                $this->delete_report( $report_id, $campaign_id );
                wp_safe_redirect( admin_url( 'admin.php?page=dt_prayer_campaigns&tab=' . $this->key . '&campaign=' . $campaign_id . '&deleted=1' ) );
                exit;
            }
        }

        // Handle edit form submission
        if ( isset( $_POST['group_report_edit_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['group_report_edit_nonce'] ) ), 'edit_group_report' ) ) {
            if ( isset( $_POST['report_id'], $_POST['group_size'] ) ) {
                $report_id = absint( $_POST['report_id'] );
                $group_size = absint( $_POST['group_size'] );
                $this->update_report( $report_id, $group_size, $campaign_id );
                wp_safe_redirect( admin_url( 'admin.php?page=dt_prayer_campaigns&tab=' . $this->key . '&campaign=' . $campaign_id . '&updated=1' ) );
                exit;
            }
        }
    }

    private function render_content( $campaign_id ) {
        // Check if editing
        if ( isset( $_GET['action'], $_GET['report_id'] ) && $_GET['action'] === 'edit' ) {
            $report_id = absint( $_GET['report_id'] );
            $report = $this->get_report( $report_id, $campaign_id );
            if ( $report ) {
                $this->render_edit_form( $report, $campaign_id );
                return;
            }
        }

        $this->render_list( $campaign_id );
    }

    private function get_daily_summary( $campaign_id, $page = 1, $per_page = 30 ) {
        global $wpdb;
        $offset = ( $page - 1 ) * $per_page;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT DATE(FROM_UNIXTIME(timestamp)) as day, COUNT(*) as report_count, SUM(value) as total_people
             FROM $wpdb->dt_reports
             WHERE post_type = 'campaigns' AND type = 'fuel' AND parent_id = %d
             GROUP BY DATE(FROM_UNIXTIME(timestamp))
             ORDER BY day DESC
             LIMIT %d OFFSET %d",
            $campaign_id, $per_page, $offset
        ) );
    }

    private function get_total_days( $campaign_id ) {
        global $wpdb;
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(DISTINCT DATE(FROM_UNIXTIME(timestamp)))
             FROM $wpdb->dt_reports
             WHERE post_type = 'campaigns' AND type = 'fuel' AND parent_id = %d",
            $campaign_id
        ) );
    }

    private function get_reports_for_day( $campaign_id, $day ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $wpdb->dt_reports
             WHERE post_type = 'campaigns' AND type = 'fuel' AND parent_id = %d
             AND DATE(FROM_UNIXTIME(timestamp)) = %s
             ORDER BY timestamp DESC",
            $campaign_id, $day
        ) );
    }

    private function get_report( $report_id, $campaign_id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $wpdb->dt_reports
             WHERE id = %d AND post_type = 'campaigns' AND type = 'fuel' AND parent_id = %d",
            $report_id, $campaign_id
        ) );
    }

    private function update_report( $report_id, $group_size, $campaign_id ) {
        global $wpdb;
        return $wpdb->update(
            $wpdb->dt_reports,
            [ 'value' => $group_size ],
            [
                'id' => $report_id,
                'parent_id' => $campaign_id,
                'post_type' => 'campaigns',
                'type' => 'fuel'
            ],
            [ '%d' ],
            [ '%d', '%d', '%s', '%s' ]
        );
    }

    private function delete_report( $report_id, $campaign_id ) {
        global $wpdb;
        return $wpdb->delete(
            $wpdb->dt_reports,
            [
                'id' => $report_id,
                'parent_id' => $campaign_id,
                'post_type' => 'campaigns',
                'type' => 'fuel'
            ],
            [ '%d', '%d', '%s', '%s' ]
        );
    }

    private function render_list( $campaign_id ) {
        $page = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
        $per_page = 30;
        $daily_summary = $this->get_daily_summary( $campaign_id, $page, $per_page );
        $total_days = $this->get_total_days( $campaign_id );
        $total_pages = ceil( $total_days / $per_page );

        $base_url = admin_url( 'admin.php?page=dt_prayer_campaigns&tab=' . $this->key . '&campaign=' . $campaign_id );

        // Show success messages
        if ( isset( $_GET['deleted'] ) ) {
            echo '<div class="notice notice-success is-dismissible"><p>Report deleted successfully.</p></div>';
        }
        if ( isset( $_GET['updated'] ) ) {
            echo '<div class="notice notice-success is-dismissible"><p>Report updated successfully.</p></div>';
        }

        ?>
        <style>
            .group-reports-day-row {
                cursor: pointer;
            }
            .group-reports-day-row:hover {
                background-color: #f0f0f1;
            }
            .group-reports-toggle {
                display: inline-block;
                width: 20px;
                transition: transform 0.2s;
                color: #2271b1;
            }
            .group-reports-toggle.expanded {
                transform: rotate(90deg);
            }
            .group-reports-details {
                display: none;
            }
            .group-reports-details.expanded {
                display: table-row;
            }
            .group-reports-details > td {
                padding: 0 !important;
                background: #f6f7f7;
            }
            .group-reports-details-wrapper {
                margin: 12px 12px 12px 40px;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
                overflow: hidden;
            }
            .group-reports-details-table {
                margin: 0;
                border: none;
                border-radius: 0;
                box-shadow: none;
            }
            .group-reports-details-table thead th {
                background: #f0f0f1;
                font-size: 12px;
                padding: 8px 12px;
                border-bottom: 1px solid #c3c4c7;
            }
            .group-reports-details-table tbody tr:nth-child(odd) {
                background: #fff;
            }
            .group-reports-details-table tbody tr:nth-child(even) {
                background: #f9f9f9;
            }
            .group-reports-details-table tbody td {
                padding: 10px 12px;
                vertical-align: middle;
            }
            .group-reports-details-table .actions a {
                text-decoration: none;
            }
            .group-reports-details-table .actions .delete {
                color: #b32d2e;
            }
            .group-reports-details-table .actions .delete:hover {
                color: #a00;
            }
        </style>
        <div class="wrap">
            <h2>Group Prayer Reports</h2>
            <p>These are reports from users who indicated they prayed with additional people on prayer fuel pages. Click a day to expand.</p>

            <?php if ( empty( $daily_summary ) ) : ?>
                <p>No group reports found for this campaign.</p>
            <?php else : ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th style="width: 5%;"></th>
                            <th style="width: 45%;">Date</th>
                            <th style="width: 25%;">Reports</th>
                            <th style="width: 25%;">Total People</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $daily_summary as $day_data ) :
                            $day = $day_data->day;
                            $formatted_day = gmdate( 'l, F j, Y', strtotime( $day ) );
                            $day_id = 'day-' . esc_attr( $day );
                        ?>
                            <tr class="group-reports-day-row" data-day="<?php echo esc_attr( $day ); ?>" data-campaign="<?php echo esc_attr( $campaign_id ); ?>">
                                <td><span class="group-reports-toggle dashicons dashicons-arrow-right-alt2"></span></td>
                                <td><strong><?php echo esc_html( $formatted_day ); ?></strong></td>
                                <td><?php echo esc_html( $day_data->report_count ); ?></td>
                                <td><?php echo esc_html( $day_data->total_people ); ?></td>
                            </tr>
                            <tr class="group-reports-details" id="<?php echo esc_attr( $day_id ); ?>">
                                <td colspan="4">
                                    <div class="group-reports-details-wrapper">
                                        <?php
                                        $day_reports = $this->get_reports_for_day( $campaign_id, $day );
                                        ?>
                                        <table class="group-reports-details-table">
                                            <thead>
                                                <tr>
                                                    <th style="width: 30%;">Time</th>
                                                    <th style="width: 30%;">Group Size</th>
                                                    <th style="width: 40%;">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ( $day_reports as $report ) : ?>
                                                    <tr>
                                                        <td><?php echo esc_html( gmdate( 'g:i a', $report->timestamp ) ); ?></td>
                                                        <td><?php echo esc_html( $report->value ); ?> people</td>
                                                        <td class="actions">
                                                            <a href="<?php echo esc_url( add_query_arg( [ 'action' => 'edit', 'report_id' => $report->id ], $base_url ) ); ?>">Edit</a>
                                                            &nbsp;|&nbsp;
                                                            <a href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'delete', 'report_id' => $report->id ], $base_url ), 'delete_group_report' ) ); ?>"
                                                               onclick="return confirm('Are you sure you want to delete this report?');"
                                                               class="delete">Delete</a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ( $total_pages > 1 ) : ?>
                    <div class="tablenav bottom">
                        <div class="tablenav-pages">
                            <span class="displaying-num"><?php echo esc_html( $total_days ); ?> days</span>
                            <span class="pagination-links">
                                <?php if ( $page > 1 ) : ?>
                                    <a class="prev-page button" href="<?php echo esc_url( add_query_arg( 'paged', $page - 1, $base_url ) ); ?>">‹</a>
                                <?php else : ?>
                                    <span class="tablenav-pages-navspan button disabled">‹</span>
                                <?php endif; ?>

                                <span class="paging-input">
                                    <?php echo esc_html( $page ); ?> of <span class="total-pages"><?php echo esc_html( $total_pages ); ?></span>
                                </span>

                                <?php if ( $page < $total_pages ) : ?>
                                    <a class="next-page button" href="<?php echo esc_url( add_query_arg( 'paged', $page + 1, $base_url ) ); ?>">›</a>
                                <?php else : ?>
                                    <span class="tablenav-pages-navspan button disabled">›</span>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>
        <script>
            jQuery(document).ready(function($) {
                $('.group-reports-day-row').on('click', function() {
                    var day = $(this).data('day');
                    var detailsRow = $('#day-' + day);
                    var toggle = $(this).find('.group-reports-toggle');

                    detailsRow.toggleClass('expanded');
                    toggle.toggleClass('expanded');
                });
            });
        </script>
        <?php
    }

    private function render_edit_form( $report, $campaign_id ) {
        $base_url = admin_url( 'admin.php?page=dt_prayer_campaigns&tab=' . $this->key . '&campaign=' . $campaign_id );
        ?>
        <div class="wrap">
            <h2>Edit Group Report</h2>
            <p><a href="<?php echo esc_url( $base_url ); ?>">← Back to list</a></p>

            <form method="POST">
                <?php wp_nonce_field( 'edit_group_report', 'group_report_edit_nonce' ); ?>
                <input type="hidden" name="report_id" value="<?php echo esc_attr( $report->id ); ?>">

                <table class="form-table">
                    <tr>
                        <th><label>Date</label></th>
                        <td><?php echo esc_html( gmdate( 'Y-m-d H:i:s', $report->timestamp ) ); ?></td>
                    </tr>
                    <tr>
                        <th><label for="group_size">Group Size</label></th>
                        <td>
                            <input type="number" name="group_size" id="group_size" min="1" value="<?php echo esc_attr( $report->value ); ?>" class="regular-text">
                            <p class="description">Number of people who prayed together</p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <input type="submit" class="button button-primary" value="Save Changes">
                    <a href="<?php echo esc_url( $base_url ); ?>" class="button">Cancel</a>
                </p>
            </form>
        </div>
        <?php
    }
}
new DT_Porch_Admin_Tab_Group_Reports();
