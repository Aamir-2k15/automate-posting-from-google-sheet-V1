<?php
/*
Plugin Name: Automate Posting from Google Sheet.
Description: Creates posts from a Google Sheet also considers date and time. Needs Google Sheet URL & API Key.
Version: 2.1
Author:  Aamir
*/

include_once 'includes/class-custom-admin-page.php';

$saved_settings = get_option('automate_posting_settings', []);
$args = [
    'page_title' => 'Automate posting', // Title of the page
    'menu_title' => 'Automate posting', // Title of the menu item
    'capability' => 'manage_options', // Capability required to access the page (admin)
    'menu_slug'  => 'automate-posting', // The slug for the page (used in URL)
    'callback'   => 'automate_posting_content', // Callback function to render content
];

// Instantiating the CustomAdminPage class
new CustomAdminPage($args);

function automate_posting_content()
{
    global $saved_settings;
    ob_start();
?><div class="wrap" style="padding:35px 20px;">
        <h1 style="padding:15px 0px;margin:1px 0 30px 0;">Automate posting</h1>
        <?php include_once 'includes/class-wp-form-builder.php'; ?>
        <?php
       
       $automate_posting_settings = get_option('automate_posting_settings', []);

if (isset($_POST['save_settings'])) {
    // Sanitize input before saving
    $sanitized_settings = array_map('sanitize_text_field', $_POST);

    update_option('automate_posting_settings', $sanitized_settings);
}
       ?>
        <div id="response" class="notice notice-success is-dismissible" style="display:none;"></div>

        <form action="" method="post">
            <?php wp_nonce_field('automate_posting_action', 'automate_posting_nonce'); ?>
            <input type="hidden" name="action" value="automate_posting">
<?php
@$file_url_value = isset($saved_settings['file_url']) ? $saved_settings['file_url'] : $_POST['file_url'];
@$api_key_value = isset($saved_settings['api_key']) ? $saved_settings['api_key'] :  $_POST['api_key'];
@$sheet_name_value = isset($saved_settings['sheet_name']) ? $saved_settings['sheet_name'] : $_POST['sheet_name'];
@$sheet_range_value = isset($saved_settings['sheet_range']) ? $saved_settings['sheet_range'] :  $_POST['sheet_range'];
$form_builder = new WpFormBuilder();  
$form_builder->field([
    'name' => 'file_url',
    'id' => 'file_url',
    'label' => 'Google Sheets File URL',
    'type' => 'text',
    'value' => $file_url_value,
]);
$form_builder->field([
    'name' => 'api_key',
    'id' => 'api_key',
    'label' => 'Google Sheets API Key',
    'type' => 'text',
    'value' => $api_key_value,
]);
$form_builder->field([
    'name' => 'sheet_name',
    'id' => 'sheet_name',
    'label' => 'Google Sheet Name',
    'type' => 'text',
    'value' => $sheet_name_value,
]);
$form_builder->field([
    'name' => 'sheet_range',
    'id' => 'sheet_range',
    'label' => 'Google Sheet Range',
    'type' => 'text',
    'value' => $sheet_range_value,
]);
 
// Render the form
$form_builder->render();?>
            <input type="submit" value="Save Settings" name="save_settings" class="button-primary">
        </form>

        <h2>Google Sheet Sync</h2>
        <button id="sync_google_sheet" class="button button-primary"> Sync Posts with Google Sheet </button>


<script>
function showNotification(message, type = "success") {
    const responseDiv = document.getElementById("response");

    // Set the message text
    responseDiv.innerHTML = `<p>${message}</p>`;
    
    // Create and append the dismiss button if it doesn't exist
    let dismissButton = responseDiv.querySelector('.notice-dismiss');
    if (!dismissButton) {
        dismissButton = document.createElement('button');
        dismissButton.type = "button";
        dismissButton.classList.add('notice-dismiss');
        dismissButton.innerHTML = '<span class="screen-reader-text">Dismiss this notice.</span>';
        dismissButton.addEventListener('click', () => {
            responseDiv.style.display = 'none';
        });
        responseDiv.appendChild(dismissButton);
    }

    // Update the class for different message types
    responseDiv.className = `notice notice-${type} is-dismissible`;

    // Show the div
    responseDiv.style.display = "block";

    // Auto-hide after 20 seconds (optional)
    setTimeout(() => {
        responseDiv.style.display = "none";
    }, 20000);
}



document.getElementById("sync_google_sheet").addEventListener("click", function() {
    this.disabled = true; // Disable button to prevent multiple clicks
    this.innerText = "Syncing..."; // Change button text

    fetch("<?php echo admin_url('admin-ajax.php'); ?>?action=sync_with_google_sheet")
        .then(response => response.text()) // Get response as text first
        .then(text => {
            try {
                const data = JSON.parse(text); // Try to parse JSON
                if (data.success) {
                    showNotification(data.data, "success"); 
                } else {
                    showNotification("Error: " + data.data, "error");
                }
            } catch (error) {
                // Handle cases where response is not JSON (e.g., HTML error page)
                showNotification("Invalid response from server. Please check the Google Sheets URL.", "error");
            }
        })
        .catch(error => showNotification("Request Failed: " + error, "error"))
        .finally(() => {
            this.disabled = false;
            this.innerText = "Open & Sync Google Sheet";
        });
});


</script>
    </div>
<?php
    $html = ob_get_clean();
    echo $html;
}

////////////
function sync_with_google_sheet()
{
    global $saved_settings;
    $sheetUrl = $_POST['file_url'] ?? $saved_settings['file_url'];

    // Extract the spreadsheet ID from the URL
    preg_match('/\/d\/([a-zA-Z0-9-_]+)/', $sheetUrl, $matches);
    if (!isset($matches[1])) {
        echo '<div class="updated"><p>Invalid Google Sheets URL.</p></div>';
        return;
    }

    $sheet_id = $matches[1];
    $api_key = $saved_settings['api_key'];
    // $range = "Sheet1!A:F";
    $sheet_name =  $saved_settings['sheet_name'];
    $sheet_range = $saved_settings['sheet_range'];
    $range = "$sheet_name!$sheet_range";

    // Fetch Google Sheets data  
    $fetch_url = "https://sheets.googleapis.com/v4/spreadsheets/$sheet_id/values/$range?key=$api_key";


    $response = wp_remote_get($fetch_url);
    // $response = file_get_contents($fetch_url);

    if (is_wp_error($response)) {
        wp_send_json_error("Request Failed: " . $response->get_error_message());
    }

    $response_body = wp_remote_retrieve_body($response);
    $data = json_decode($response_body, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        wp_send_json_error("Invalid response from Google Sheets API. Please check your spreadsheet URL. $fetch_url");
    }

    if (!isset($data['values'])) {
        wp_send_json_error("Failed to fetch data from Google Sheets. Make sure the spreadsheet is public.");
    }

    $values = $data['values'];
    $header = $values[0];
    $title_col = array_search('Title', $header);
    $body_col = array_search('Body', $header);
    $category_col = array_search('Category', $header);
    $time_col = array_search('Time', $header);
    $date_col = array_search('Date', $header);

    if ($title_col === false || $body_col === false || $category_col === false || $time_col === false || $date_col === false) {
        wp_send_json_error("Required columns (Title, Body, Category, Time, Date) not found.");
    }

    $posts_created = 0;
    $created_titles = [];
    $skipped_titles = [];

    for ($i = 1; $i < count($values); $i++) {
        $title = sanitize_text_field($values[$i][$title_col] ?? '');
        $body = wp_kses_post($values[$i][$body_col] ?? '');
        $category_name = sanitize_text_field($values[$i][$category_col] ?? '');
        $post_time = sanitize_text_field($values[$i][$time_col] ?? '');
        $post_date = sanitize_text_field($values[$i][$date_col] ?? '');

        if (!empty($body)) {
            // Check if post with the same title already exists
            $existing_post_query = new WP_Query([
                'post_type'   => $post_type,
                'title'       => trim(strtolower($title)),
                'posts_per_page' => 1,
            ]);
            
            if ($existing_post_query->have_posts()) {
                $skipped_titles[] = $title;
                continue;
            }

            // Determine the taxonomy dynamically
            $post_type = 'post';//$saved_settings['create_post_types'];
            $taxonomies = get_object_taxonomies($post_type);
            $taxonomy = !empty($taxonomies) ? $taxonomies[0] : 'category';

            // Check if category exists, create if necessary
            $category_id = 0;
            if (!empty($category_name)) {
                $category_obj = get_term_by('name', $category_name, $taxonomy);
                if ($category_obj) {
                    $category_id = $category_obj->term_id;
                } else {
                    $new_category = wp_insert_term($category_name, $taxonomy);
                    if (!is_wp_error($new_category)) {
                        $category_id = $new_category['term_id'];
                    }
                }
            }

            // Handle post scheduling with improved date parsing
            $scheduled_datetime = false;
            if (!empty($post_date)) {
                $datetime_str = trim($post_date . ' ' . $post_time);
                $timestamp = strtotime($datetime_str);
                if ($timestamp === false) {
                    $timestamp = strtotime(str_replace('/', '-', $datetime_str));
                }
                if ($timestamp !== false) {
                    $scheduled_datetime = $timestamp;
                }
            }

            $current_datetime = current_time('timestamp');
            $post_status = ($scheduled_datetime && $scheduled_datetime > $current_datetime) ? 'future' : 'publish';
            $post_date_formatted = ($scheduled_datetime) ? date('Y-m-d H:i:s', $scheduled_datetime) : current_time('mysql');

            // Create WordPress post
            $new_post = [
                'post_title'    => $title,
                'post_content'  => $body,
                'post_status'   => $post_status,
                'post_author'   => 1,
                'post_type'     => $post_type,
                'post_date'     => $post_date_formatted,
            ];
            $post_id = wp_insert_post($new_post);

            if ($post_id && $category_id) {
                wp_set_object_terms($post_id, [$category_id], $taxonomy);
            }

            if ($post_id) {
                $posts_created++;
                $created_titles[] = $title;
            }
        }
    }

    // Prepare response message
    $message = "";
    if ($posts_created > 0) {
        $message .= " Created $posts_created new post(s):<br><ol><li>" . implode("</li><li>", $created_titles) . "</li></ol>";
    }
    if (!empty($skipped_titles)) {
        $message .= "<div style='color:red'>Skipped duplicate titles:</div><br><ol><li>" . implode("</li><li>", $skipped_titles) . "</li></ol>";
    }

    wp_send_json_success($message);
}
add_action('wp_ajax_sync_with_google_sheet', 'sync_with_google_sheet');
