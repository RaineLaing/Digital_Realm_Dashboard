<?php
require_once dirname(dirname(__DIR__)) . '/includes/mailchimp-api.php';

$stats = get_mailchimp_stats();

if (!$stats || !is_array($stats)) {
    echo '<p class="text-danger">Unable to retrieve Mailchimp stats at this time.</p>';
    return;
}

// Helper for trend arrow icons (placeholder: always up arrow)
function trend_arrow($up = true) {
    return $up 
        ? '<span style="color:green; font-weight:bold; margin-left:5px;">&#9650;</span>'  // ▲ up arrow
        : '<span style="color:red; font-weight:bold; margin-left:5px;">&#9660;</span>';   // ▼ down arrow
}
?>

<style>
  .email-overview-card {
    background-color: #f9fafb;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 1px 5px rgb(0 0 0 / 0.1);
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen,
      Ubuntu, Cantarell, "Open Sans", "Helvetica Neue", sans-serif;
  }
  .email-overview-card .stat-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.25rem;
  }
  .email-overview-card .progress {
    height: 8px;
    border-radius: 4px;
    overflow: hidden;
    box-shadow: inset 0 1px 2px rgb(0 0 0 / 0.1);
  }
  .email-overview-card .progress-bar {
    box-shadow: none;
  }
  .email-overview-badge {
    background-color: #4a90e2;
    color: white;
    padding: 0.3rem 0.8rem;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.9rem;
  }
</style>

<div class="card p-3 mb-4">
  <h3 class="mb-3">📧 Email Marketing Overview</h3>

  <div class="d-flex justify-content-between">
    <strong>Total Subscribers:</strong>
    <span><?php echo number_format($stats['total_subscribers']); ?></span>
  </div>

  <div class="d-flex justify-content-between mt-3">
    <strong>Open Rate:</strong>
    <span>
      <?php echo $stats['open_rate']; ?>%
      <?php echo trend_arrow(true); ?>
    </span>
  </div>
  <div class="progress mb-3" style="height: 8px;">
    <div
      class="progress-bar bg-success"
      style="width: <?php echo $stats['open_rate']; ?>%;"
    ></div>
  </div>

  <div class="d-flex justify-content-between">
    <strong>Click Rate:</strong>
    <span>
      <?php echo $stats['click_rate']; ?>%
      <?php echo trend_arrow(true); ?>
    </span>
  </div>
  <div class="progress mb-3" style="height: 8px;">
    <div
      class="progress-bar bg-info"
      style="width: <?php echo $stats['click_rate']; ?>%;"
    ></div>
  </div>

  <div class="d-flex justify-content-between mt-3">
    <strong>Campaigns Sent:</strong>
    <span class="badge bg-primary"><?php echo $stats['campaigns_sent']; ?></span>
  </div>
</div>
