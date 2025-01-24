<?php

/**
 * Plugin Name: Sucuri Log Exporter
 * Description: Export Sucuri security logs to external formats
 * Version: 1.0.0
 * Author: Marcio Fao
 * Text Domain: sucuri-log-exporter
 * Domain Path: /lang
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit;
}

define('SUCURI_EXPORTER_VERSION', '1.0.0');
define('SUCURI_EXPORTER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SUCURI_EXPORTER_PLUGIN_URL', plugin_dir_url(__FILE__));

// create the admin menu for the plugin
add_action('admin_menu', 'sucuri_log_exporter_menu');
function sucuri_log_exporter_menu()
{
    add_menu_page(
        'Sucuri Log Exporter',
        'Sucuri Log Exporter',
        'manage_options',
        'sucuri-log-exporter',
        'sucuri_log_exporter_page',
        'dashicons-admin-generic',
        100
    );
}
// create the admin page for the plugin
function sucuri_log_exporter_page()
{
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
?>
    <div class="wrap">
        <h1><?php _e('Sucuri Log Exporter', 'sucuri-log-exporter'); ?></h1>
        <p><?php _e('Export Sucuri security logs', 'sucuri-log-exporter'); ?></p>
        <?php $current_url = $_SERVER['REQUEST_URI']; ?>
        <a href="<?php echo strpos($current_url, '&invert') === false ? $current_url . '&invert' : str_replace('&invert', '', $current_url) ?>">Invert list</a>

        <!--link that will copy all contents of .sle-logs to clipboard-->
        <button onclick="copyToClipboard('.sle-logs')">Copy to clipboard</button>
        <script>
            function copyToClipboard(element) {
                var copyText = document.querySelector(element);
                var range = document.createRange();
                range.selectNode(copyText);
                window.getSelection().removeAllRanges();
                window.getSelection().addRange(range);
                document.execCommand("copy");
                window.getSelection().removeAllRanges();
            }
        </script>
        <!-- export as csv .sle-logs content button -->
         <button onclick="exportToCSV('.sle-logs')">Export to CSV</button>
         <script>
            function exportToCSV(element) {
                var copyText = document.querySelector(element);
                var csv = copyText.innerText;
                var hiddenElement = document.createElement('a');
                hiddenElement.href = 'data:text/csv;charset=utf-8,' + encodeURI(csv);
                hiddenElement.target = '_blank';
                hiddenElement.download = 'sucuri-logs.csv';
                hiddenElement.click();
            }
         </script>
        <form method="post" action="options.php">
            <?php
            // settings_fields('sucuri_log_exporter_settings');
            // do_settings_sections('sucuri-log-exporter');
            // submit_button();
            ?>
        </form>
        <style>
            .sle-logs {
                /*break lines*/
                white-space: pre-wrap;
            }
        </style>
        <div class="sle-logs"><?php sle_read_sucuri_logs(); ?></div>

    </div>
<?php
}

function sle_read_sucuri_logs()
{
    //reads and output the content of file wp-content/uploads/sucuri/sucuri-auditqueue.php
    $sucuri_logs = file_get_contents(ABSPATH . 'wp-content/uploads/sucuri/sucuri-auditqueue.php');
    //remove first 6 lines
    $sucuri_logs = explode("?>\n", $sucuri_logs)[1];
    //explode string into array by each line
    $sucuri_logs = explode("\n", $sucuri_logs);
    //invert array if set
    if(isset($_GET['invert'])) {
        $sucuri_logs = array_reverse($sucuri_logs);
    }
    foreach ($sucuri_logs as $value) {
        if (empty($value)) {
            continue;
        }
        $split_value = explode(':"', $value);
        $sucuri_time = $split_value[0];
        list($timestamp, $microseconds) = explode('_', $sucuri_time);
        $datetime_gmt = gmdate('Y-m-d H:i:s', $timestamp) . '.' . $microseconds;
        echo $datetime_gmt . ' - ' . substr($split_value[1], 0, -1) . '<br>';
    }
}
