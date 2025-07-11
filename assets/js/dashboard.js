document.addEventListener('DOMContentLoaded', function () {
  const statusFilter = document.getElementById('status-filter');
  const leadSearchInput = document.getElementById('lead-search');
  const table = document.getElementById('leads-table');
  const tbody = table?.querySelector('tbody');
  const noResults = document.getElementById('no-results');
  const exportBtn = document.getElementById('export-csv');

  let sortColumnIndex = null;
  let sortAscending = true;

  const tooltipList = Array.from(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  tooltipList.forEach(el => new bootstrap.Tooltip(el));

  function sortTableByColumn(colIndex) {
    if (!tbody) return;
    const rowsArray = Array.from(tbody.rows);
    const isSameColumn = sortColumnIndex === colIndex;
    sortAscending = isSameColumn ? !sortAscending : true;
    sortColumnIndex = colIndex;

    document.querySelectorAll('th.sortable').forEach(th => th.classList.remove('active-asc', 'active-desc'));
    const selectedHeader = document.querySelector(`th[data-column-index="${colIndex}"]`);
    if (selectedHeader) selectedHeader.classList.add(sortAscending ? 'active-asc' : 'active-desc');

    rowsArray.sort((a, b) => {
      const aText = a.cells[colIndex]?.innerText.trim().toLowerCase() || '';
      const bText = b.cells[colIndex]?.innerText.trim().toLowerCase() || '';
      const aDate = Date.parse(aText);
      const bDate = Date.parse(bText);
      const isDate = !isNaN(aDate) && !isNaN(bDate);

      return isDate
        ? (sortAscending ? aDate - bDate : bDate - aDate)
        : (sortAscending ? aText.localeCompare(bText) : bText.localeCompare(aText));
    });

    rowsArray.forEach(row => tbody.appendChild(row));
  }

  function attachSortHandlers() {
    if (!table) return;
    const headers = table.querySelectorAll('th.sortable');
    headers.forEach((header) => {
      const colIndex = parseInt(header.getAttribute('data-column-index'));
      if (!isNaN(colIndex)) {
        header.style.cursor = 'pointer';
        header.addEventListener('click', () => sortTableByColumn(colIndex));
      }
    });
  }

  setTimeout(() => attachSortHandlers(), 100);

  function handleRowVisibility() {
    if (!tbody) return;
    const selectedStatus = statusFilter?.value || '';
    const searchTerm = leadSearchInput?.value.trim().toLowerCase() || '';
    let visibleCount = 0;
    const activeCard = document.querySelector('.pipeline-card.active[data-status]');
    const selectedCardStatus = activeCard ? activeCard.getAttribute('data-status') : null;

    Array.from(tbody.rows).forEach(row => {
      const statusSelect = row.querySelector('select.lead-status-select');
      if (!statusSelect) return;

      const rowStatus = statusSelect.value;
      const rowText = row.textContent.toLowerCase();
      const createdStr = row.getAttribute('data-created') || '';
      const createdDate = createdStr ? new Date(createdStr) : null;

      let matchesPipeline = true;
      let matchesTime = true;
      const now = new Date();

      if (typeof pipelineFilters !== 'undefined' && pipelineFilters instanceof Set) {
        if (pipelineFilters.has('today')) {
          matchesTime = createdDate?.toDateString() === now.toDateString();
        }
        if (pipelineFilters.has('this_month')) {
          matchesTime = createdDate?.getMonth() === now.getMonth() &&
                        createdDate?.getFullYear() === now.getFullYear();
        }
      }

      const matchesDropdown = !selectedStatus || rowStatus === selectedStatus;
      const matchesSearch = !searchTerm || rowText.includes(searchTerm);
      const matchesCardStatus = !selectedCardStatus || rowStatus === selectedCardStatus;

      const show = matchesPipeline && matchesTime && matchesDropdown && matchesSearch && matchesCardStatus;
      row.style.display = show ? '' : 'none';
      if (show) visibleCount++;
    });

    if (noResults) noResults.style.display = visibleCount === 0 ? '' : 'none';
  }

  function updatePipelineCardCounts() {
    if (!tbody) return;
    const visibleRows = Array.from(tbody.rows).filter(row => row.style.display !== 'none');
    const counts = {};
    visibleRows.forEach(row => {
      const select = row.querySelector('select.lead-status-select');
      if (select) {
        counts[select.value] = (counts[select.value] || 0) + 1;
      }
    });
    document.querySelectorAll('.pipeline-card[data-status]').forEach(card => {
      const status = card.getAttribute('data-status');
      const countSpan = card.querySelector('.fs-4');
      if (countSpan) countSpan.textContent = counts[status] || 0;
    });
  }

  // === FOCUSED SAVE HANDLER: only save status changes ===
  tbody?.addEventListener('change', function (event) {
    const input = event.target;
    if (!input.classList.contains('lead-status-select')) return; // only status selects

    const row = input.closest('tr');
    const taskId = row?.getAttribute('data-task-id');
    if (!taskId) return;

    const newStatus = input.value;

    const data = new FormData();
    data.append('action', 'update_lead_row');
    data.append('_wpnonce', ajax_object.nonce);
    data.append('task_id', taskId);
    data.append('status', newStatus);

    fetch(ajax_object.ajax_url, {
      method: 'POST',
      credentials: 'same-origin',
      body: data,
    })
    .then(response => response.json())
    .then(json => {
      if (json.success) {
        console.log('Status updated:', newStatus);
        updatePipelineCardCounts();
      } else {
        alert('Save failed: ' + (json.data?.message || 'Unknown error'));
      }
    })
    .catch(err => {
      console.error('Save error:', err);
      alert('Error saving status');
    });
  });

  // === EDIT LEAD BUTTON & MODAL HANDLER ===
  const editLeadModal = new bootstrap.Modal(document.getElementById('editLeadModal'));
  const editLeadForm = document.getElementById('edit-lead-form');

  tbody?.addEventListener('click', function(event) {
    const btn = event.target.closest('.edit-lead-btn');
    if (!btn) return;

    const taskId = btn.getAttribute('data-task-id');
    if (!taskId) return;

    // Find the row for this task
    const row = document.querySelector(`tr[data-task-id="${taskId}"]`);
    if (!row) return;

    const cells = row.querySelectorAll('td');

    // Fill modal inputs with current values (adjust indexes if your columns change)
    document.getElementById('edit-task-id').value = taskId;
    document.getElementById('edit-first-name').value = cells[0]?.textContent.trim() || '';
    document.getElementById('edit-last-name').value = cells[1]?.textContent.trim() || '';
    document.getElementById('edit-email').value = cells[2]?.textContent.trim() || '';
    document.getElementById('edit-company').value = cells[3]?.textContent.trim() || '';
    // If you add phone in your table or stored elsewhere, set it here; else blank:
    document.getElementById('edit-phone').value = '';

    editLeadModal.show();
  });

  editLeadForm.addEventListener('submit', function(event) {
    event.preventDefault();
  
    const formData = new FormData(editLeadForm);
    formData.append('action', 'save_edited_lead');  // <-- MATCH PHP
    formData.append('_wpnonce', ajax_object.nonce);
  
    fetch(ajax_object.ajax_url, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData,
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Update the table row with new values immediately
        const taskId = formData.get('task_id');
        const row = document.querySelector(`tr[data-task-id="${taskId}"]`);
        if (row) {
          const cells = row.querySelectorAll('td');
          cells[0].textContent = formData.get('first_name');
          cells[1].textContent = formData.get('last_name');
          cells[2].textContent = formData.get('email');
          cells[3].textContent = formData.get('company');
          // Update phone if applicable
        }
        editLeadModal.hide();
      } else {
        alert('Save failed: ' + (data.data?.message || 'Unknown error'));
      }
    })
    .catch(error => {
      console.error('Save error:', error);
      alert('Error saving lead data.');
    });
  });

  if (statusFilter) statusFilter.addEventListener('change', () => {
    document.querySelectorAll('.pipeline-card.active').forEach(card => card.classList.remove('active'));
    handleRowVisibility();
  });

  if (leadSearchInput) leadSearchInput.addEventListener('input', () => {
    document.querySelectorAll('.pipeline-card.active').forEach(card => card.classList.remove('active'));
    handleRowVisibility();
  });

  const pipelineCards = document.querySelectorAll('.pipeline-card[data-status]');
  pipelineCards.forEach(card => {
    card.addEventListener('click', () => {
      const selected = card.classList.contains('active');
      pipelineCards.forEach(c => c.classList.remove('active'));
      if (!selected) card.classList.add('active');
      if (statusFilter) statusFilter.value = '';
      if (leadSearchInput) leadSearchInput.value = '';
      handleRowVisibility();
    });
  });

  handleRowVisibility();
  updatePipelineCardCounts();

  // Export functionality
  if (exportBtn) {
    exportBtn.addEventListener('click', () => {
      if (!tbody) return;

      const visibleRows = Array.from(tbody.rows).filter(row => row.style.display !== 'none');
      const headers = ['First Name', 'Last Name', 'Email', 'Company', 'Status', 'Created', 'Last Updated'];

      const csvData = [headers];

      visibleRows.forEach(row => {
        const rowData = [];

        const cells = row.querySelectorAll('td');

        // First 4 columns: First Name, Last Name, Email, Company
        for (let i = 0; i < 4; i++) {
          rowData.push(cells[i]?.textContent.trim() || '');
        }

        // Status column is the 5th <td> (index 4), which contains a select element
        const statusSelect = cells[4]?.querySelector('select.lead-status-select');
        rowData.push(statusSelect?.selectedOptions[0]?.textContent.trim() || '');

        // Created and Last Updated — adjust indexes if your table columns differ
        const created = cells[7]?.textContent.trim() || '';
        const lastUpdated = cells[8]?.textContent.trim() || '';

        rowData.push(created, lastUpdated);

        csvData.push(rowData);
      });

      // Convert to CSV format
      const csvContent = csvData.map(row =>
        row.map(cell => `"${cell.replace(/"/g, '""')}"`).join(',')
      ).join('\n');

      // Trigger download
      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = 'leads-export.csv';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    });
  }

});
