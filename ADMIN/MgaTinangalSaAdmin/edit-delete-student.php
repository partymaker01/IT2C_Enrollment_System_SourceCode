<?php

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Edit/Delete Student Info - Enrollment System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <style>
    body {
      background-color: #e6f2e6;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    h2 {
      color: #2e7d32;
      font-weight: 700;
    }

    .table-responsive {
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }

    .action-btns > button {
      min-width: 75px;
    }

    #toastContainer {
      position: fixed;
      top: 1rem;
      right: 1rem;
      z-index: 1080;
    }
  </style>
</head>
<body>

  <div class="container my-5">
    <h2 class="mb-4 text-center">
      Edit/Delete Student Info
    </h2>
      <div class="mb-3">
        <a href="/IT2C_Enrollment_System_SourceCode/ADMIN/admin-dashboard.php" class="btn btn-outline-success">&larr; Back to Dashboard</a>
      </div>

    <div class="table-responsive">
      <table class="table table-striped table-hover align-middle text-center">
        <thead class="table-success">
          <tr>
            <th scope="col">
              Student ID
            </th>
            <th scope="col">
              Full Name
            </th>
            <th scope="col">
              Program
            </th>
            <th scope="col">
              Year Level
            </th>
            <th scope="col">
              Contact
            </th>
            <th scope="col">
              Email
            </th>
            <th scope="col" style="min-width:130px;">
              Actions
            </th>
          </tr>
        </thead>
        <tbody id="studentTableBody">
          <tr data-id="20250001">
            <td>
              20250001
            </td>
            <td>
              Jerick Dela Cruz reyes
            </td>
            <td>
              ECT
            </td>
            <td>
              1st Year
            </td>
            <td>
              09171234567
            </td>
            <td>j@email.com</td>
            <td class="action-btns">
              <button type="button" class="btn btn-sm btn-primary me-2 edit-btn" aria-label="Edit Jerick Dela Cruz Reyes">
                Edit
              </button>
              <button type="button" class="btn btn-sm btn-danger delete-btn" aria-label="Delete Jerick Dela Cruz Reyes">
                Delete
              </button>
            </td>
          </tr>
          <tr data-id="20250002">
            <td>
              20250002
            </td>
            <td>
              Maria Clara
            </td>
            <td>
              IT
            </td>
            <td>
              2nd Year
            </td>
            <td>
              09181234567
            </td>
            <td>jerick@email.com</td>
            <td class="action-btns">
              <button type="button" class="btn btn-sm btn-primary me-2 edit-btn" aria-label="Edit jerick reyes">
                Edit
              </button>
              <button type="button" class="btn btn-sm btn-danger delete-btn" aria-label="Delete jerick reyes">
                Delete
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Edit Modal -->
  <div class="modal fade" id="editStudentModal" tabindex="-1" aria-labelledby="editStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <form id="editStudentForm" class="modal-content" onsubmit="return saveChanges()">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title" id="editStudentModalLabel">
            Edit Student Info
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="editStudentId" />
          <div class="row g-3">
            <div class="col-md-6">
              <label for="editFullName" class="form-label">
                Full Name
              </label>
              <input type="text" class="form-control" id="editFullName" required autocomplete="off" />
            </div>
            <div class="col-md-6">
              <label for="editProgram" class="form-label">
                Program
              </label>
              <select class="form-select" id="editProgram" required>
                <option value="">
                  Select program
                </option>
                <option>
                  HRMT
                </option>
                <option>
                  IT
                </option>
                <option>
                  ECT
                </option>
                <option>
                  HST
                </option>
              </select>
            </div>
            <div class="col-md-6">
              <label for="editYearLevel" class="form-label">
                Year Level
              </label>
              <select class="form-select" id="editYearLevel" required>
                <option value="">
                  Select year level
                </option>
                <option>
                  1st Year
                </option>
                <option>
                  2nd Year
                </option>
                <option>
                  3rd Year
                </option>
              </select>
            </div>
            <div class="col-md-6">
              <label for="editContactNumber" class="form-label">
                Contact Number
              </label>
              <input type="tel" class="form-control" id="editContactNumber" pattern="[0-9]{11}" placeholder="e.g. 09171234567" required />
              <div class="form-text">
                Enter exactly 11-digit mobile number.
              </div>
            </div>
            <div class="col-12">
              <label for="editEmailAddress" class="form-label">
                Email Address
              </label>
              <input type="email" class="form-control" id="editEmailAddress" placeholder="example@mail.com" required />
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            Cancel
          </button>
          <button type="submit" class="btn btn-success">
            Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>

  <div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3"></div>

  <script>
    let selectedRow = null;

    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    document.querySelectorAll('.edit-btn').forEach(button => {
      button.addEventListener('click', function() {
        selectedRow = this.closest('tr');
        document.getElementById('editStudentId').value = selectedRow.dataset.id;
        document.getElementById('editFullName').value = selectedRow.children[1].textContent.trim();
        document.getElementById('editProgram').value = selectedRow.children[2].textContent.trim();
        document.getElementById('editYearLevel').value = selectedRow.children[3].textContent.trim();
        document.getElementById('editContactNumber').value = selectedRow.children[4].textContent.trim();
        document.getElementById('editEmailAddress').value = selectedRow.children[5].textContent.trim();

        const editModal = new bootstrap.Modal(document.getElementById('editStudentModal'));
        editModal.show();
      });
    });

    function saveChanges() {
      if (!selectedRow) return false;

      const contactInput = document.getElementById('editContactNumber');
      if (!contactInput.checkValidity()) {
        showToast('Contact Number must be exactly 11 digits.', 'danger');
        contactInput.focus();
        return false;
      }

      const emailInput = document.getElementById('editEmailAddress');
      if (!emailInput.checkValidity()) {
        showToast('Please enter a valid email address.', 'danger');
        emailInput.focus();
        return false;
      }

      selectedRow.children[1].textContent = document.getElementById('editFullName').value.trim();
      selectedRow.children[2].textContent = document.getElementById('editProgram').value;
      selectedRow.children[3].textContent = document.getElementById('editYearLevel').value;
      selectedRow.children[4].textContent = contactInput.value.trim();
      selectedRow.children[5].textContent = emailInput.value.trim();

      const editModal = bootstrap.Modal.getInstance(document.getElementById('editStudentModal'));
      editModal.hide();

      showToast('Student info updated successfully.', 'success');

      return false;
    }

    document.querySelectorAll('.delete-btn').forEach(button => {
      button.addEventListener('click', function() {
        const row = this.closest('tr');
        const studentName = row.children[1].textContent.trim();
        if (confirm(`Are you sure you want to delete the record of ${studentName}?`)) {
          row.remove();
          showToast('Student record deleted.', 'warning');
        }
      });
    });

    function showToast(message, type = 'info') {
      const toastContainer = document.getElementById('toastContainer');
      const toastId = `toast${Date.now()}`;
      const toastEl = document.createElement('div');
      toastEl.className = `toast align-items-center text-bg-${type} border-0`;
      toastEl.role = 'alert';
      toastEl.ariaLive = 'assertive';
      toastEl.ariaAtomic = 'true';
      toastEl.id = toastId;
      toastEl.innerHTML = `
        <div class="d-flex">
          <div class="toast-body">${message}</div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
      `;
      toastContainer.appendChild(toastEl);
      const bsToast = new bootstrap.Toast(toastEl, { delay: 4000 });
      bsToast.show();
      toastEl.addEventListener('hidden.bs.toast', () => {
        toastEl.remove();
      });
    }
  </script>
</body>
</html>
