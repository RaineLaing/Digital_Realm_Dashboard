<?php
/**
 * Lead Table Template
 * Displays parsed ClickUp tasks with filtering, sorting, and editing UI
 */

// === Fallback for missing function ===
if (!function_exists('get_clickup_tasks')) {
    function get_clickup_tasks() {
        return [];
    }
}

$tasks = get_clickup_tasks();
if (empty($tasks)) {
    echo "<p>No leads found.</p>";
    return;
}

// === Configuration ===
$hardcoded_keys = ['first name', 'last name', 'email', 'company', 'message'];
$status_options = [
    'new_lead' => 'New Lead',
    'contacted' => 'Contacted',
    'follow_up' => 'Follow Up',
    'quote_sent' => 'Quote Sent',
    'sale' => 'Sale',
    'not_interested' => 'Not Interested',
    'archived' => 'Archived',
];
$follow_options = [
    '' => 'Select',
    'not_contacted' => 'Not Contacted',
    'called' => 'Called',
    'emailed' => 'Emailed',
    'meeting_booked' => 'Meeting Booked'
];

// === Dynamic Field Detection ===
$extra_keys = [];
foreach ($tasks as $task) {
    foreach ($task['fields'] ?? [] as $key => $value) {
        $key_lower = strtolower($key);
        if (!in_array($key_lower, $hardcoded_keys) && !in_array($key_lower, $extra_keys)) {
            $extra_keys[] = $key_lower;
        }
    }
}
$all_keys = array_merge($hardcoded_keys, $extra_keys);

// === Status Counts ===
$total_leads = count($tasks);
$status_counts = array_fill_keys(array_keys($status_options), 0);
foreach ($tasks as $task) {
    $task_id = $task['id'];
    $saved_status = get_option("wp_lead_status_$task_id");
    $status = $saved_status ?: strtolower(str_replace(' ', '_', $task['status'] ?? 'new_lead'));
    if (isset($status_counts[$status])) {
        $status_counts[$status]++;
    }
}

// === Display Summary + Filter ===
?>


<form id="lead-filter-form" class="mb-3" style="display:flex; gap: 10px; flex-wrap: wrap;">
    <label for="status-filter" class="form-label mb-0 align-self-center">Filter by Status:</label>
    <select id="status-filter" class="form-select form-select-sm" style="max-width: 200px;">
        <option value="">All</option>
        <?php foreach ($status_options as $value => $label): ?>
            <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
        <?php endforeach; ?>
    </select>
</form>
<p id="no-results" style="display:none;"><em>No leads match your filter.</em></p>
<input type="text" id="lead-search" class="form-control form-control-sm mb-3" placeholder="Search by name..." style="max-width: 300px;">

<!-- === Table === -->
<div class="table-responsive" style="max-height: 500px;">
  <table id="leads-table" class="table table-bordered table-lg table-striped table-hover">
    <thead class="table-light sticky-top">
      <tr>
        <?php 
        $colIndex = 0;
        $sortableKeys = ['first name', 'last name', 'email', 'company'];

        foreach ($all_keys as $key):
            $keySlug = strtolower($key);
            $isSortable = in_array($keySlug, $sortableKeys);
        ?>
          <th<?php echo $isSortable ? ' class="sortable" data-column-index="' . $colIndex . '"' : ''; ?>>
              <?php echo ucwords($key); ?>
              <?php if ($isSortable): ?><span class="sort-indicator"></span><?php endif; ?>
          </th>
        <?php
            $colIndex++;
        endforeach;
        ?>

        <th>Status</th>
        <th class="sortable" data-column-index="<?php echo $colIndex++; ?>">Created <span class="sort-indicator"></span></th>
        <th class="sortable" data-column-index="<?php echo $colIndex++; ?>">Last Updated <span class="sort-indicator"></span></th>
        <th>Edit</th>
        <th>Mailchimp Sync</th>
      </tr>
    </thead>

    <tbody>
      <?php foreach ($tasks as $task): 
          $task_id = $task['id'];
          $status = get_option("wp_lead_status_$task_id") ?: strtolower(str_replace(' ', '_', $task['status'] ?? 'new_lead'));
          $created_timestamp = $task['created'] ? date('c', strtotime($task['created'])) : '';
      ?>
      <tr id="row-<?php echo esc_attr($task_id); ?>" 
          class="lead-row" 
          data-task-id="<?php echo esc_attr($task_id); ?>" 
          data-status="<?php echo esc_attr($status); ?>" 
          data-created="<?php echo esc_attr($created_timestamp); ?>">

          <?php foreach ($all_keys as $key): 
              $slug = strtolower($key);
              $value = get_option("wp_lead_field_{$slug}_{$task_id}", $task['fields'][$slug] ?? '');
          ?>
            <td><?php echo esc_html($value ?: '—'); ?></td>
          <?php endforeach; ?>

          <td style="min-width: 160px;">
            <select class="lead-status-select form-select form-select-sm w-100" data-task-id="<?php echo esc_attr($task_id); ?>">
              <?php foreach ($status_options as $val => $label): ?>
                  <option value="<?php echo esc_attr($val); ?>" <?php selected($status, $val); ?>><?php echo esc_html($label); ?></option>
              <?php endforeach; ?>
            </select>
          </td>

          <td><?php echo esc_html($task['created'] ? date('Y-m-d h:i A', strtotime($task['created'])) : '—'); ?></td>

          <td class="last-updated" data-task-id="<?php echo esc_attr($task_id); ?>">
              <?php echo esc_html($task['last_updated'] ? date('Y-m-d h:i A', strtotime($task['last_updated'])) : '—'); ?>
          </td>

          <td>
            <button class="btn btn-sm btn-outline-primary edit-lead-btn" data-task-id="<?php echo esc_attr($task_id); ?>">Edit</button>
          </td>

          <td class="mailchimp-sync-cell" data-task-id="<?php echo esc_attr($task_id); ?>">
            <?php if (get_option("mailchimp_synced_$task_id") === 'yes'): ?>
              <span class="text-success">&#10003;</span>
            <?php else: ?>
              <button class="btn btn-sm btn-outline-primary mailchimp-sync-btn" data-task-id="<?php echo esc_attr($task_id); ?>">Not Synced</button>
            <?php endif; ?>
          </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>


<!-- === Edit Lead Modal === -->
<div class="modal fade" id="editLeadModal" tabindex="-1" aria-labelledby="editLeadModalLabel" aria-hidden="true">
<div class="modal-dialog modal-lg">
  <div class="modal-content">
    <form id="edit-lead-form">
      <div class="modal-header">
        <h5 class="modal-title">Edit Lead</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="task_id" id="edit-task-id" />
        <div class="mb-3"><label class="form-label">First Name</label><input type="text" class="form-control" id="edit-first-name" name="first_name" /></div>
        <div class="mb-3"><label class="form-label">Last Name</label><input type="text" class="form-control" id="edit-last-name" name="last_name" /></div>
        <div class="mb-3"><label class="form-label">Email</label><input type="email" class="form-control" id="edit-email" name="email" /></div>
        <div class="mb-3"><label class="form-label">Company</label><input type="text" class="form-control" id="edit-company" name="company" /></div>
        <div class="mb-3"><label class="form-label">Phone Number</label><input type="text" class="form-control" id="edit-phone" name="phone" /></div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Save changes</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </form>
  </div>
</div>
</div>

<!-- === Sorting Styles === -->
<style>
th.sortable { position: relative; cursor: pointer; }
th.sortable .sort-indicator::after {
  content: '⇅'; font-size: 0.75rem; color: #999;
  position: absolute; right: 6px; top: 50%; transform: translateY(-50%);
}
th.sortable.active-asc .sort-indicator::after { content: '↑'; color: #000; }
th.sortable.active-desc .sort-indicator::after { content: '↓'; color: #000; }
</style>


