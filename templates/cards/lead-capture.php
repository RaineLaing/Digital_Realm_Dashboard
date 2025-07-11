<?php
// Lead Capture Overview Data

if (!function_exists('get_clickup_tasks')) {
    function get_clickup_tasks() {
        return [];
    }
}
$tasks = get_clickup_tasks();

$status_options = [
    'new_lead' => 'New Lead',
    'contacted' => 'Contacted',
    'follow_up' => 'Follow Up',
    'quote_sent' => 'Quote Sent',
    'sale' => 'Sale',
    'not_interested' => 'Not Interested',
    'archived' => 'Archived',
];

$total_leads = count($tasks);
$status_counts = array_fill_keys(array_keys($status_options), 0);

$now = time();
$seven_days_ago = $now - 7 * 86400;
$fourteen_days_ago = $now - 14 * 86400;

$today_start = strtotime('today');
$month_start = strtotime(date('Y-m-01 00:00:00'));

$leads_today = 0;
$leads_this_month = 0;

$recent_counts = array_fill_keys(array_keys($status_options), 0);
$previous_counts = array_fill_keys(array_keys($status_options), 0);

foreach ($tasks as $task) {
    $task_id = $task['id'];
    $created_timestamp = isset($task['created']) ? strtotime($task['created']) : 0;
    $saved_status = get_option("wp_lead_status_$task_id");
    $status = $saved_status ?: strtolower(str_replace(' ', '_', $task['status'] ?? 'new_lead'));

    if (isset($status_counts[$status])) {
        $status_counts[$status]++;
    }

    if ($created_timestamp >= $seven_days_ago) {
        if (isset($recent_counts[$status])) {
            $recent_counts[$status]++;
        }
    } elseif ($created_timestamp >= $fourteen_days_ago && $created_timestamp < $seven_days_ago) {
        if (isset($previous_counts[$status])) {
            $previous_counts[$status]++;
        }
    }

    if ($created_timestamp >= $today_start) $leads_today++;
    if ($created_timestamp >= $month_start) $leads_this_month++;
}

$growth_percentages = [];
foreach ($status_options as $key => $label) {
    $recent = $recent_counts[$key];
    $previous = $previous_counts[$key];
    $growth_percentages[$key] = ($previous === 0) ? ($recent > 0 ? 100 : 0) : round((($recent - $previous) / $previous) * 100);
}
?>
<!-- Main Section -->
<div class="lead-capture-flex-container" style="max-width: 1200px; margin: 0 auto; padding: 1rem;">

<div class="lead-capture-flex-container" style="max-width: 1200px; margin: 0 auto; padding: .25rem;">

<!-- Summary Cards -->
<div class="card  mb-1 border-0">
  <h3 class="mb-1"> Lead Summary</h3>
  <div class= "d-flex gap-1 justify-content-start flex-wrap">
    <?php
      $summary_cards = [
        ['label' => 'Total Leads', 'count' => $total_leads],
        ['label' => 'Today', 'count' => $leads_today],
        ['label' => 'This Month', 'count' => $leads_this_month],
      ];

      foreach ($summary_cards as $card): ?>
        <div
          class="pipeline-card text-center"
          style="min-width: 120px; max-width: 160px; flex: 0 0 auto;"
        >
          <strong><?php echo $card['label']; ?></strong><br>
          <span class="fs-4"><?php echo intval($card['count']); ?></span>
        </div>
    <?php endforeach; ?>
  </div>
</div>

</div>

  <!-- Pipeline Status Cards -->
  <div class="card p-1 border-0">
    <h3 class="mb-1">Lead Pipeline Overview</h3>
    <div class= "d-flex gap-1 justify-content-start flex-wrap">

      <?php
        $shown = 0;
        foreach ($status_counts as $status_key => $count):

          $growth = $growth_percentages[$status_key] ?? 0;
          $arrow = $growth > 0 ? '▲' : ($growth < 0 ? '▼' : '');
          $color = $growth > 0 ? 'green' : ($growth < 0 ? 'red' : 'gray');
      ?>
          <div
            class="pipeline-card text-center"
            data-status="<?php echo esc_attr($status_key); ?>"
            title="Click to filter by <?php echo htmlspecialchars($status_options[$status_key]); ?>"
            data-bs-toggle="tooltip"
            data-bs-placement="top"
          >
            <strong><?php echo htmlspecialchars($status_options[$status_key]); ?></strong><br>
            <span class="fs-4"><?php echo intval($count); ?></span>
            <?php if ($arrow): ?>
              <span style="color: <?php echo $color; ?>; font-weight: bold; font-size: 0.9rem;">
                <?php echo $arrow . ' ' . abs($growth) . '%'; ?>
              </span>
            <?php endif; ?>
          </div>
      <?php endforeach; ?>
      
    </div>
  </div>

</div>


<style>
  .pipeline-card {
    background-color: linear-gradient(145deg, #fdf6fb, #f9f0ff);
    border: 1px solid #d1c4e9;
    min-height: 120px;
    width: 150px;
    border-radius: 10px;
    padding: 1rem;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    transition: all 0.2s ease-in-out;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
  }

  
  .pipeline-card:hover {
    box-shadow: 0 4px 12px rgba(253, 46, 46, 0.08);
    background-color: #f9f9fb;
  }
  .pipeline-card.active {
    border-color: #0d6efd;
    background-color: #e7f1ff;
    box-shadow: 0 0 0 2px #0d6efd33;
  }


</style>
