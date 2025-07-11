<?php
// mailchimp-api.php

function get_mailchimp_stats() {
    // Hardcoded API key and List ID for testing
    $api_key = '284e2d0beb5f17ca8f7832c7a40089e0-us5';    // Replace with your actual Mailchimp API key
    $list_id = '76e2a966b6';        // Replace with your actual Mailchimp List ID (Audience ID)

    if (empty($api_key) || empty($list_id)) {
        return 'Mailchimp API key or List ID missing.';
    }

    $data_center = substr($api_key, strpos($api_key, '-') + 1);
    if (!$data_center) {
        return 'Invalid Mailchimp API key format.';
    }

    $url = "https://$data_center.api.mailchimp.com/3.0/lists/$list_id";

    $response = wp_remote_get($url, [
        'headers' => [
            'Authorization' => 'Basic ' . base64_encode('user:' . $api_key),
            'Content-Type' => 'application/json',
        ],
        'timeout' => 10,
    ]);

    if (is_wp_error($response)) {
        return 'Mailchimp API request error: ' . $response->get_error_message();
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);

    // Remove or comment out debugging echo here
    // echo '<pre style="color: green;">';
    // echo "HTTP Status Code: $code\n";
    // echo "Response Body: $body\n";
    // echo '</pre>';

    if ($code !== 200) {
        return "Mailchimp API returned HTTP $code: $body";
    }

    $data = json_decode($body, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return 'Mailchimp API JSON decode error: ' . json_last_error_msg();
    }

    return [
        'total_subscribers' => $data['stats']['member_count'] ?? 0,
        'open_rate' => isset($data['stats']['avg_open_rate']) ? round($data['stats']['avg_open_rate'] * 100) : 0,
        'click_rate' => isset($data['stats']['avg_click_rate']) ? round($data['stats']['avg_click_rate'] * 100) : 0,
        'campaigns_sent' => $data['stats']['campaign_count'] ?? 0,
    ];
}
