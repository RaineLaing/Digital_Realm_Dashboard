<div class="d-flex align-items-center justify-content-center mb-4 mt-4">
  <img src="<?php echo LEADS_DASHBOARD_URL . 'assets/js/img/DR_logo.png'; ?>" alt="Digital Realm Logo" style="max-height: 60px;" class="me-3">
  <h1 class="my-0">Lead Launch</h1>
</div>

<div class="container-fluid">

<!-- Row 2: Full-width card -->
<div class="card mb-1">
  <div class="card-body">
    <?php include __DIR__ . '/cards/lead-capture.php'; ?>
  </div>
</div>

<!-- Leads Table -->
<div class="row mt-4">
  <div class="col-12">

    <!-- Button Row -->
    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
      <button id="export-csv" class="btn btn-sm btn-primary">
        Export Leads 
      </button>
    </div>

    <div class="card">
      <div class="card-body">
        <?php include __DIR__ . '/leads-table.php'; ?>
      </div>
    </div>
  </div>
</div>

  <!--Email Marketing Section -->
  <div class="row mb-4">
    <div class="col-md-12 mb-3">
      <div class="card h-100">
        <div class="card-body">
          <?php include __DIR__ . '/cards/email-marketing-overview.php'; ?>
        </div>
      </div>
    </div>
  </div>

  

  