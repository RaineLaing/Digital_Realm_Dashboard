<?php
// Fetch tasks (leads) from ClickUp and parse them into usable data
function get_clickup_tasks() {
    $clickup_token = 'pk_126007464_NM204P42GQ6IBXIUTOHWPI1CGTADPMMY'; // Replace with your token or use a secure option
    $list_id = '901314630015'; // Replace with your actual list ID

    $response = wp_remote_get("https://api.clickup.com/api/v2/list/$list_id/task", [
        'headers' => [
            'Authorization' => $clickup_token,
            'Content-Type' => 'application/json',
        ],
    ]);

    $parsed_tasks = [];

    if (!is_wp_error($response)) {
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $tasks = $body['tasks'] ?? [];

        foreach ($tasks as $task) {
            // Normalize status key
            $status_raw = $task['status']['status'] ?? 'new_lead';
            $status = strtolower(str_replace(' ', '_', $status_raw));

            // Parse the task description to extract lead fields
            $fields = parse_task_description($task['description'] ?? '');

            $parsed_tasks[] = [
                'id' => $task['id'],
                'status' => $status,
                'created' => date('Y-m-d H:i', floor($task['date_created'] / 1000)),
                'last_updated' => get_option("clickup_last_updated_" . $task['id'], '—'),
                'fields' => $fields,
            ];
        }
    }

    // ✅ Merge manually-added leads
    $manual_leads = get_option('wp_manual_leads', []);
    foreach ($manual_leads as $task_id => $meta) {
        $parsed_tasks[] = [
            'id' => $task_id,
            'status' => get_option("wp_lead_status_$task_id", 'new_lead'),
            'created' => $meta['created'],
            'last_updated' => get_option("clickup_last_updated_$task_id", '—'),
            'fields' => [
                'first name' => get_option("wp_lead_field_first_name_$task_id", ''),
                'last name'  => get_option("wp_lead_field_last_name_$task_id", ''),
                'email'      => get_option("wp_lead_field_email_$task_id", ''),
                'company'    => get_option("wp_lead_field_company_$task_id", ''),
                'message'    => get_option("wp_lead_field_message_$task_id", ''),
            ],
        ];
    }

    return $parsed_tasks;
}

// Helper function to parse lead info from task description
function parse_task_description($description) {
    $fields = [];
    foreach (explode("\n", $description) as $line) {
        if (strpos($line, ':') !== false) {
            list($key, $value) = array_map('trim', explode(':', $line, 2));
            $fields[strtolower($key)] = $value;
        }
    }
    return $fields;
}

// Create a new task in ClickUp (used by the AJAX handler for adding new leads)
function create_clickup_task($task_name, $description) {
    $clickup_token = 'pk_126007464_NM204P42GQ6IBXIUTOHWPI1CGTADPMMY'; // Use your real token or better yet, store securely
    $list_id = '901314630015'; // Your ClickUp List ID where tasks should be created

    $url = "https://api.clickup.com/api/v2/list/$list_id/task";

    $body = [
        'name' => $task_name,
        'description' => $description,
        'status' => 'new_lead', // default status for new leads
    ];

    $args = [
        'headers' => [
            'Authorization' => $clickup_token,
            'Content-Type' => 'application/json',
        ],
        'body' => json_encode($body),
        'timeout' => 15,
    ];

    $response = wp_remote_post($url, $args);

    if (is_wp_error($response)) {
        return false;
    }

    $code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    $data = json_decode($response_body, true);

    if ($code !== 200 || empty($data['id'])) {
        return false;
    }

    return $data; // The created task data (including 'id')
}
