<?php

add_action('wp_ajax_update_lead_status', 'update_lead_status_callback');
add_action('wp_ajax_update_followed_up_status', 'update_followed_up_status_callback');
add_action('wp_ajax_save_edited_lead', 'save_edited_lead_callback');
add_action('wp_ajax_update_lead_row', 'update_lead_row_callback'); // ✅ Focused single-field update

define('LEADS_DASHBOARD_NONCE_ACTION', 'leads_dashboard_nonce');

/**
 * Update lead status via AJAX.
 */
function update_lead_status_callback() {
    check_ajax_referer(LEADS_DASHBOARD_NONCE_ACTION, '_wpnonce');

    $task_id = sanitize_text_field($_POST['task_id'] ?? '');
    $new_status = sanitize_text_field($_POST['status'] ?? '');

    $valid_statuses = [
        'new_lead', 'contacted', 'follow_up', 'quote_sent',
        'sale', 'not_interested', 'archived'
    ];

    if (!$task_id || !in_array($new_status, $valid_statuses, true)) {
        wp_send_json_error(['message' => 'Invalid parameters']);
        wp_die();
    }

    $status_saved = update_option("wp_lead_status_$task_id", $new_status);
    $timestamp_saved = update_option("clickup_last_updated_$task_id", current_time('mysql'));

    if ($status_saved || $timestamp_saved) {
        wp_send_json_success([
            'message' => 'Status and timestamp updated',
            'new_status' => $new_status,
            'updated_at' => current_time('mysql'),
        ]);
    } else {
        wp_send_json_error(['message' => 'Nothing changed']);
    }
    wp_die();
}

/**
 * Update followed-up status via AJAX.
 */
function update_followed_up_status_callback() {
    check_ajax_referer(LEADS_DASHBOARD_NONCE_ACTION, '_wpnonce');

    $task_id = sanitize_text_field($_POST['task_id'] ?? '');
    $followed_up = sanitize_text_field($_POST['followed_up'] ?? '');

    $valid_follow_up = [
        '', 'not_contacted', 'called', 'emailed', 'meeting_booked'
    ];

    if (!$task_id || !in_array($followed_up, $valid_follow_up, true)) {
        wp_send_json_error(['message' => 'Invalid parameters']);
        wp_die();
    }

    $status_saved = update_option("wp_lead_followed_up_$task_id", $followed_up);
    $timestamp_saved = update_option("clickup_last_updated_$task_id", current_time('mysql'));

    if ($status_saved || $timestamp_saved) {
        wp_send_json_success([
            'message' => 'Follow-up status and timestamp updated',
            'followed_up' => $followed_up,
            'updated_at' => current_time('mysql'),
        ]);
    } else {
        wp_send_json_error(['message' => 'Nothing changed']);
    }
    wp_die();
}

/**
 * Save edited lead fields via AJAX (used by modal).
 */
function save_edited_lead_callback() {
    check_ajax_referer(LEADS_DASHBOARD_NONCE_ACTION, '_wpnonce');

    $task_id    = sanitize_text_field($_POST['task_id'] ?? '');
    $first_name = sanitize_text_field($_POST['first_name'] ?? '');
    $last_name  = sanitize_text_field($_POST['last_name'] ?? '');
    $email      = sanitize_email($_POST['email'] ?? '');
    $company    = sanitize_text_field($_POST['company'] ?? '');

    if (!$task_id) {
        wp_send_json_error(['message' => 'Task ID is required']);
        wp_die();
    }

    update_option("wp_lead_field_first_name_$task_id", $first_name);
    update_option("wp_lead_field_last_name_$task_id", $last_name);
    update_option("wp_lead_field_email_$task_id", $email);
    update_option("wp_lead_field_company_$task_id", $company);
    update_option("clickup_last_updated_$task_id", current_time('mysql'));

    wp_send_json_success(['message' => 'Lead updated successfully']);
    wp_die();
}

/**
 * ✅ Unified AJAX: Update a single lead row field (e.g. status)
 */
function update_lead_row_callback() {
    check_ajax_referer(LEADS_DASHBOARD_NONCE_ACTION, '_wpnonce');

    $task_id = sanitize_text_field($_POST['task_id'] ?? '');
    if (!$task_id) {
        wp_send_json_error(['message' => 'Missing task ID']);
        wp_die();
    }

    // Define allowed fields you expect to update
    $allowed_fields = ['first_name', 'last_name', 'email', 'company', 'status', 'followed_up'];

    $fields_updated = 0;

    foreach ($allowed_fields as $field) {
        if (isset($_POST[$field])) {
            $raw_value = $_POST[$field];
            // Sanitize according to field type
            if ($field === 'email') {
                $value = sanitize_email($raw_value);
            } else {
                $value = sanitize_text_field($raw_value);
            }

            // Validate status field
            if ($field === 'status') {
                $valid_statuses = [
                    'new_lead', 'contacted', 'follow_up', 'quote_sent',
                    'sale', 'not_interested', 'archived'
                ];
                if (!in_array($value, $valid_statuses, true)) {
                    wp_send_json_error(['message' => 'Invalid status value']);
                    wp_die();
                }
            }

            // Validate followed_up field if desired (optional)
            if ($field === 'followed_up') {
                $valid_follow_up = ['', 'not_contacted', 'called', 'emailed', 'meeting_booked'];
                if (!in_array($value, $valid_follow_up, true)) {
                    wp_send_json_error(['message' => 'Invalid followed_up value']);
                    wp_die();
                }
            }

            update_option("wp_lead_{$field}_{$task_id}", $value);
            $fields_updated++;
        }
    }

    if ($fields_updated > 0) {
        update_option("clickup_last_updated_$task_id", current_time('mysql'));
        wp_send_json_success(['message' => 'Lead row updated']);
    } else {
        wp_send_json_error(['message' => 'No valid fields to update']);
    }

    wp_die();
}
