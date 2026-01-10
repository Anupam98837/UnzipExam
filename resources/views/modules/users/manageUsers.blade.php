{{-- resources/views/modules/users/manageUsers.blade.php (Unzip Examination, revamped) --}}

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
<link rel="stylesheet" href="{{ asset('assets/css/common/main.css') }}"/>

<style>
/* ===== Shell ===== */
.usr-wrap{
  max-width:1140px;
  margin:16px auto 40px;
  overflow:visible;
}
.panel{
  background:var(--surface);
  border:1px solid var(--line-strong);
  border-radius:16px;
  box-shadow:var(--shadow-2);
  padding:14px;
}

/* Table Card */
.table-wrap.card{
  position:relative;
  border-radius:16px;
  border:1px solid var(--line-strong);
  box-shadow:var(--shadow-2);
  background:var(--surface);
}
.table-wrap .card-body{overflow:visible}
.table-responsive{overflow:visible !important}
.table{--bs-table-bg:transparent}
.table thead th{
  font-weight:600;
  color:var(--muted-color);
  font-size:13px;
  border-bottom:1px solid var(--line-strong);
  background:var(--surface);
}
.table thead.sticky-top{z-index:3}
.table tbody tr{border-top:1px solid var(--line-soft)}
.table tbody tr:hover{background:var(--page-hover)}
.small{font-size:12.5px}

/* Dropdowns inside table */
.table-wrap .dropdown{position:relative;}
.dropdown [data-bs-toggle="dropdown"]{border-radius:10px}
.dropdown-menu{
  border-radius:12px;
  border:1px solid var(--line-strong);
  box-shadow:var(--shadow-2);
  min-width:220px;
  z-index:1040; /* BELOW Bootstrap modal (1055), ABOVE table */
}
.dropdown-item{
  display:flex;
  align-items:center;
  gap:.6rem;
}
.dropdown-item i{width:16px;text-align:center}
.dropdown-item.text-danger{color:var(--danger-color)!important}

/* Avatar cell */
.u-avatar{
  width:40px;
  height:40px;
  border-radius:10px;
  object-fit:cover;
  border:1px solid var(--line-strong);
}
.u-avatar-fallback{
  width:40px;
  height:40px;
  border-radius:10px;
  border:1px solid var(--line-strong);
  display:flex;
  align-items:center;
  justify-content:center;
  color:#9aa3b2;
  font-size:12px;
}

/* Badges */
.badge-role{
  background:color-mix(in oklab, var(--accent-color) 12%, transparent);
  color:var(--ink);
  border-radius:999px;
  font-weight:500;
}
.badge-soft-active{
  background:color-mix(in oklab, var(--success-color) 16%, transparent);
  color:var(--ink);
}
.badge-soft-inactive{
  background:color-mix(in oklab, var(--danger-color) 10%, transparent);
  color:var(--ink);
}

/* Password eye buttons */
.u-pw-wrap{position:relative}
.u-pw-wrap .u-eye{
  position:absolute;
  top:50%;
  right:10px;
  transform:translateY(-50%);
  width:32px;
  height:32px;
  border:none;
  background:transparent;
  display:grid;
  place-items:center;
  border-radius:8px;
  color:#9aa3b2;
  cursor:pointer;
}
.u-pw-wrap .u-eye:focus-visible{
  outline:none;
  box-shadow:var(--ring);
}

/* Modals */
.modal-content{
  border-radius:16px;
  border:1px solid var(--line-strong);
  background:var(--surface);
}
.modal-header{border-bottom:1px solid var(--line-strong)}
.modal-footer{border-top:1px solid var(--line-strong)}
.form-control,.form-select,textarea{
  border-radius:12px;
  border:1px solid var(--line-strong);
  background:#fff;
}
html.theme-dark .form-control,
html.theme-dark .form-select,
html.theme-dark textarea{
  background:#0f172a;
  color:#e5e7eb;
  border-color:var(--line-strong);
}

/* Input focus polish */
.form-control:focus,.form-select:focus{
  box-shadow:0 0 0 3px color-mix(in oklab, var(--accent-color) 20%, transparent);
  border-color:var(--accent-color);
}

/* Dark tweaks */
html.theme-dark .panel,
html.theme-dark .table-wrap.card,
html.theme-dark .modal-content{
  background:#0f172a;
  border-color:var(--line-strong);
}
html.theme-dark .table thead th{
  background:#0f172a;
  border-color:var(--line-strong);
  color:#94a3b8;
}
html.theme-dark .table tbody tr{border-color:var(--line-soft)}
html.theme-dark .dropdown-menu{
  background:#0f172a;
  border-color:var(--line-strong);
}

/* Empty state */
.empty-state{
  padding:38px;
  text-align:center;
  color:var(--muted-color);
}

/* Manage quizzes assignment code pill */
.badge-code{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  gap:6px;
  padding:3px 12px;
  min-width:130px;
  border-radius:999px;
  font-family:var(--font-mono, ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace);
  font-size:11px;
  letter-spacing:.08em;
  text-transform:uppercase;
  border:1px solid var(--accent-color);
  background:color-mix(in oklab, var(--accent-color) 14%, transparent);
  color:var(--accent-color);
  cursor:pointer;
  white-space:nowrap;
}
.badge-code span{
  max-width:120px;
  overflow:hidden;
  text-overflow:ellipsis;
}
html.theme-dark .badge-code{
  background:color-mix(in oklab, var(--accent-color) 32%, transparent);
  color:var(--surface);
  border-color:var(--accent-color);
}

/* CSV Import specific styles */
.csv-upload-area{
  border:3px dashed var(--line-strong);
  border-radius:16px;
  padding:48px 24px;
  text-align:center;
  transition:border-color 0.2s ease;
  background:var(--page);
  cursor:pointer;
}
.csv-upload-area:hover{
  border-color:var(--accent-color);
}
.csv-upload-area.dragover{
  border-color:var(--accent-color);
  background:color-mix(in oklab, var(--accent-color) 8%, transparent);
}
.csv-icon{
  font-size:48px;
  margin-bottom:16px;
  color:var(--accent-color);
}
.csv-help{
  font-size:12px;
  color:var(--muted-color);
  margin-top:12px;
}
.csv-template-link{
  display:inline-flex;
  align-items:center;
  gap:6px;
  color:var(--accent-color);
  text-decoration:none;
  font-weight:500;
}
.csv-template-link:hover{
  text-decoration:underline;
}
.import-progress{
  height:6px;
  border-radius:3px;
  background:var(--line-soft);
  overflow:hidden;
  margin:16px 0;
}
.import-progress-bar{
  height:100%;
  background:var(--accent-color);
  transition:width 0.3s ease;
  border-radius:3px;
}
.import-results{
  max-height:200px;
  overflow-y:auto;
  border:1px solid var(--line-strong);
  border-radius:12px;
  padding:12px;
  background:var(--page);
}
.import-result-item{
  padding:4px 8px;
  border-radius:6px;
  margin-bottom:4px;
  font-size:13px;
}
.import-result-item.success{
  background:color-mix(in oklab, var(--success-color) 10%, transparent);
  border-left:3px solid var(--success-color);
}
.import-result-item.error{
  background:color-mix(in oklab, var(--danger-color) 10%, transparent);
  border-left:3px solid var(--danger-color);
}
.import-result-item.warning{
  background:color-mix(in oklab, #f59e0b 10%, transparent);
  border-left:3px solid #f59e0b;
}
</style>

<div class="usr-wrap">

  {{-- ================= Toolbar ================= --}}
  <div class="row align-items-center g-2 mb-3 mfa-toolbar panel">
    <div class="col-12 col-lg d-flex align-items-center flex-wrap gap-2">

      <div class="d-flex align-items-center gap-2">
        <label class="text-muted small mb-0">Per Page</label>
        <select id="perPage" class="form-select" style="width:96px;">
          <option>10</option>
          <option selected>20</option>
          <option>50</option>
          <option>100</option>
        </select>
      </div>

      <div class="position-relative" style="min-width:300px;">
        <input id="searchInput" type="search" class="form-control ps-5" placeholder="Search by name or email…">
        <i class="fa fa-search position-absolute" style="left:12px; top:50%; transform:translateY(-50%); opacity:.6;"></i>
      </div>

      <button id="btnFilter" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#filterModal">
        <i class="fa fa-filter me-1"></i>Filter
      </button>

      <button id="btnReset" class="btn btn-primary">
        <i class="fa fa-rotate-left me-1"></i>Reset
      </button>
    </div>

    <div class="col-12 col-lg-auto ms-lg-auto d-flex justify-content-lg-end">
      <div id="writeControls" style="display:none;">
        {{-- CSV Import Button --}}
        <button type="button" class="btn btn-outline-primary me-2" id="btnImportCsv" data-bs-toggle="modal" data-bs-target="#importCsvModal">
          <i class="fa fa-file-import me-1"></i> Import CSV
        </button>
        {{-- Add User Button --}}
        <button type="button" class="btn btn-primary" id="btnAddUser">
          <i class="fa fa-plus me-1"></i> Add User
        </button>
      </div>
    </div>
  </div>

  {{-- ================= Users Table ================= --}}
  <div class="card table-wrap">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover table-borderless align-middle mb-0">
          <thead class="sticky-top">
            <tr>
              <th style="width:90px;">Status</th>
              <th style="width:80px;">Avatar</th>
              <th>Name</th>
              <th>Email</th>
              <th style="width:160px;">Role</th>
              <th style="width:140px;" class="text-center">Quizzes</th>
              <th style="width:110px;" class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody id="usersTbody">
            <tr>
              <td colspan="7" class="empty-state">
                <i class="fa fa-circle-notch fa-spin mb-2" style="font-size:20px;"></i>
                <div>Loading users…</div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="d-flex flex-wrap align-items-center justify-content-between p-3 gap-2">
        <div class="text-muted small" id="resultsInfo">—</div>
        <nav><ul id="pager" class="pagination mb-0"></ul></nav>
      </div>
    </div>
  </div>
</div>

{{-- ================= Filter Users Modal ================= --}}
<div class="modal fade" id="filterModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa fa-filter me-2"></i>Filter Users</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          {{-- Status --}}
          <div class="col-12">
            <label class="form-label">Status</label>
            <select id="modal_status" class="form-select">
              <option value="all">All Status</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>

          {{-- Role (client-side filtered) --}}
          <div class="col-12">
            <label class="form-label">Role</label>
            <select id="modal_role" class="form-select">
              <option value="">All Roles</option>
              <option value="super_admin">Super Admin</option>
              <option value="admin">Admin</option>
              <option value="examiner">Examiner</option>
              <option value="student">Student</option>
            </select>
          </div>

          {{-- Sort (frontend + future backend) --}}
          <div class="col-12">
            <label class="form-label">Sort By</label>
            <select id="modal_sort" class="form-select">
              <option value="-created_at">Newest First</option>
              <option value="created_at">Oldest First</option>
              <option value="name">Name A-Z</option>
              <option value="-name">Name Z-A</option>
              <option value="email">Email A-Z</option>
              <option value="-email">Email Z-A</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="button" id="btnApplyFilters" class="btn btn-primary">
          <i class="fa fa-check me-1"></i>Apply Filters
        </button>
      </div>
    </div>
  </div>
</div>

{{-- ================= Import CSV Modal ================= --}}
<div class="modal fade" id="importCsvModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa fa-file-import me-2"></i>Import Users from CSV</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="importCsvForm" enctype="multipart/form-data">
        <div class="modal-body">
          {{-- Step 1: Upload --}}
          <div id="importStep1">
            {{-- CSV Upload Area --}}
            <div class="csv-upload-area" id="csvDropZone">
              <div class="csv-icon">
                <i class="fa fa-file-csv"></i>
              </div>
              <h5 class="mb-2">Drag & drop your CSV file here</h5>
              <p class="text-muted mb-3">or click to browse</p>
              <input type="file" id="csvFile" name="file" accept=".csv,text/csv" class="d-none" required>
              <button type="button" class="btn btn-primary" id="csvBrowseBtn">
                <i class="fa fa-folder-open me-1"></i> Browse Files
              </button>
              <div class="csv-help mt-3">
                <div><strong>CSV Format:</strong> name, email, password, role</div>
                <div class="mt-1">First row must contain header. Max file size: 10MB</div>
              </div>
            </div>

            {{-- File Info --}}
            <div id="fileInfo" class="mt-3 d-none">
              <div class="alert alert-light d-flex align-items-center justify-content-between">
                <div>
                  <i class="fa fa-file-csv text-primary me-2"></i>
                  <span id="fileName" class="fw-semibold"></span>
                  <span id="fileSize" class="text-muted ms-2"></span>
                </div>
                <button type="button" class="btn btn-sm btn-light" id="clearFileBtn">
                  <i class="fa fa-times"></i>
                </button>
              </div>
            </div>

            {{-- CSV Template --}}
            <div class="mt-4">
              <div class="alert alert-info">
                <div class="d-flex align-items-center">
                  <i class="fa fa-circle-info me-2"></i>
                  <div>
                    <strong>Download CSV template</strong> to ensure proper format
                    <div class="mt-1">
                      <a href="javascript:void(0)" id="downloadTemplateBtn" class="csv-template-link">
                        <i class="fa fa-download"></i> Download Template.csv
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {{-- Step 2: Import Progress --}}
          <div id="importStep2" class="d-none">
            <div class="text-center py-4">

              {{-- Spinner (shown while processing) --}}
              <div id="importSpinnerWrap">
                <div class="spinner-border text-primary mb-3" role="status">
                  <span class="visually-hidden">Loading...</span>
                </div>
              </div>

              {{-- Big Tick (shown on success) --}}
              <div id="importSuccessWrap" class="d-none">
                <div style="font-size:72px; line-height:1; margin-bottom:10px;">
                  <i class="fa fa-circle-check text-success"></i>
                </div>
              </div>

              <h5 id="importStatusText">Processing CSV file...</h5>

              <div class="import-progress mt-4">
                <div class="import-progress-bar" id="importProgressBar" style="width: 0%"></div>
              </div>
              <div id="importStats" class="mt-3 text-muted small">
                Processing...
              </div>
            </div>

            {{-- Import Results --}}
            <div id="importResults" class="mt-4 d-none">
              <h6 class="mb-3">Import Results</h6>
              <div class="import-results" id="importResultsList"></div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal" id="importCancelBtn">Cancel</button>
          <button type="submit" class="btn btn-primary" id="importSubmitBtn" disabled>
            <i class="fa fa-upload me-1"></i> Start Import
          </button>
          <button type="button" class="btn btn-primary d-none" id="importDoneBtn" data-bs-dismiss="modal">
            <i class="fa fa-check me-1"></i> Done
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- ================= Add/Edit User Modal ================= --}}
<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <form class="modal-content" id="userForm" enctype="multipart/form-data">
      <div class="modal-header">
        <h5 class="modal-title" id="userModalTitle">Add User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <input type="hidden" id="userId"/>

        <div class="row g-3">
          <div class="col-md-12">
            <label class="form-label">Full Name <span class="text-danger">*</span></label>
            <input class="form-control" id="userName" required maxlength="150" placeholder="John Doe">
          </div>

          <div class="col-md-6">
            <label class="form-label">Email <span class="text-danger">*</span></label>
            <input type="email" class="form-control" id="userEmail" required maxlength="255" placeholder="john.doe@example.com">
          </div>
          <div class="col-md-6">
            <label class="form-label">Phone</label>
            <input class="form-control" id="userPhone" maxlength="32" placeholder="+91 99999 99999">
          </div>

          <div class="col-md-6">
            <label class="form-label">Role <span class="text-danger">*</span></label>
            <select class="form-select" id="userRole" required>
              <option value="">Select Role</option>
              <option value="super_admin">Super Admin</option>
              <option value="examiner">Examiner</option>
              <option value="student">Student</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Status</label>
            <select class="form-select" id="userStatus">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>

          {{-- Password (create only) --}}
          <div class="col-md-6 js-pw-section">
            <label class="form-label">Password <span class="text-danger">*</span></label>
            <div class="u-pw-wrap">
              <input type="password" class="form-control pe-5" id="userPassword" placeholder="••••••••">
              <button type="button" class="u-eye js-eye-toggle" data-target="userPassword" aria-label="Toggle password visibility">
                <i class="fa-regular fa-eye-slash"></i>
              </button>
            </div>
            <div class="form-text">Password for new user (min 8 characters).</div>
          </div>
          <div class="col-md-6 js-pw-section">
            <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
            <div class="u-pw-wrap">
              <input type="password" class="form-control pe-5" id="userPasswordConfirmation" placeholder="••••••••">
              <button type="button" class="u-eye js-eye-toggle" data-target="userPasswordConfirmation" aria-label="Toggle confirm password visibility">
                <i class="fa-regular fa-eye-slash"></i>
              </button>
            </div>
          </div>

          {{-- Optional profile/contact fields --}}
          <div class="col-md-6">
            <label class="form-label">Alt. Email</label>
            <input type="email" class="form-control" id="userAltEmail" maxlength="255" placeholder="alt@example.com">
          </div>
          <div class="col-md-6">
            <label class="form-label">Alt. Phone</label>
            <input class="form-control" id="userAltPhone" maxlength="32" placeholder="+91 88888 88888">
          </div>
          <div class="col-md-6">
            <label class="form-label">WhatsApp</label>
            <input class="form-control" id="userWhatsApp" maxlength="32" placeholder="+91 77777 77777">
          </div>
          <div class="col-md-12">
            <label class="form-label">Address</label>
            <textarea class="form-control" id="userAddress" rows="2" placeholder="Street, City, State, ZIP"></textarea>
          </div>

          <div class="col-md-12">
            <label class="form-label">Avatar (optional)</label>
            <div class="d-flex align-items-center gap-2">
              <img id="imagePreview" alt="Preview" style="width:48px;height:48px;border-radius:10px;object-fit:cover;border:1px solid var(--line-strong);display:none;">
              <input type="file" id="userImage" accept="image/*" class="form-control">
            </div>
            <div class="form-text">PNG, JPG, WEBP, GIF, SVG up to 5MB.</div>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary" id="saveUserBtn">
          <i class="fa fa-floppy-disk me-1"></i> Save
        </button>
      </div>
    </form>
  </div>
</div>

{{-- ================= View User Modal ================= --}}
<div class="modal fade" id="userViewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa fa-user me-2"></i>User Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="userViewBody">
        Loading…
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

{{-- ================= Manage User Quizzes Modal ================= --}}
<div class="modal fade" id="userQuizzesModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="fa fa-question-circle me-2"></i>
          Manage Quizzes — <span id="uq_user_name">User</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
          <div class="position-relative" style="max-width:280px;">
            <input id="uq_search" class="form-control ps-5" placeholder="Search quizzes…">
            <i class="fa fa-search position-absolute" style="left:12px;top:50%;transform:translateY(-50%);opacity:.65;"></i>
          </div>
          <div class="d-flex align-items-center gap-2">
            <label class="small text-muted mb-0">Filter</label>
            <select id="uq_filter" class="form-select" style="width:180px;">
              <option value="all">All quizzes</option>
              <option value="assigned">Assigned only</option>
              <option value="unassigned">Unassigned only</option>
            </select>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Quiz</th>
                <th style="width:120px;">Time (min)</th>
                <th style="width:120px;">Questions</th>
                <th style="width:120px;">Status</th>
                <th style="width:120px;">Public</th>
                <th style="width:170px;">Assignment Code</th>
                <th class="text-center" style="width:120px;">Assigned</th>
              </tr>
            </thead>
            <tbody id="uq_rows">
              <tr id="uq_loader">
                <td colspan="7" class="p-3 text-center text-muted">
                  <i class="fa fa-circle-notch fa-spin me-1"></i> Loading quizzes…
                </td>
              </tr>
            </tbody>
          </table>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

{{-- ================= Toasts ================= --}}
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:2100;">
  <div id="toastSuccess" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body" id="toastSuccessText">Done</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
  <div id="toastError" class="toast align-items-center text-bg-danger border-0 mt-2" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body" id="toastErrorText">Something went wrong</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

{{-- Dependencies --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Global dropdown handler like W3T 3-dots menus
document.addEventListener('click', function(e){
  const btn = e.target.closest('.dd-toggle');
  if (!btn) return;
  e.preventDefault();
  e.stopPropagation();
  try{
    const inst = bootstrap.Dropdown.getOrCreateInstance(btn, {
      autoClose: btn.getAttribute('data-bs-auto-close') || 'outside',
      boundary: btn.getAttribute('data-bs-boundary') || 'viewport'
    });
    inst.toggle();
  }catch(ex){
    console.error('Dropdown toggle error', ex);
  }
});

document.addEventListener('DOMContentLoaded', function(){

  /* =================== AUTH & GLOBALS =================== */
  const TOKEN = sessionStorage.getItem('token') || localStorage.getItem('token') || '';
  if (!TOKEN){
    Swal.fire('Login needed','Your session expired. Please login again.','warning')
      .then(()=> location.href = '/');
    return;
  }
  const ROLE = (sessionStorage.getItem('role') || localStorage.getItem('role') || '').toLowerCase();
  const CAN_WRITE  = (ROLE === 'admin' || ROLE === 'super_admin');
  const CAN_DELETE = (ROLE === 'super_admin');

  // Seeded system admin – never shown/managed from UI
  const SYSTEM_ADMIN_EMAIL = 'admin@hallienz.com';

  const writeControls = document.getElementById('writeControls');
  if (CAN_WRITE && writeControls) writeControls.style.display = 'flex';

  /* =================== ELEMENTS =================== */
  const usersTbody   = document.getElementById('usersTbody');
  const pager        = document.getElementById('pager');
  const resultsInfo  = document.getElementById('resultsInfo');
  const perPageSel   = document.getElementById('perPage');
  const searchInput  = document.getElementById('searchInput');
  const btnReset     = document.getElementById('btnReset');
  const btnAddUser   = document.getElementById('btnAddUser');
  const btnImportCsv = document.getElementById('btnImportCsv');

  // Import CSV Modal elements
  const importCsvModalEl = document.getElementById('importCsvModal');
  const importCsvModal   = new bootstrap.Modal(importCsvModalEl);
  const importCsvForm    = document.getElementById('importCsvForm');
  const csvDropZone      = document.getElementById('csvDropZone');
  const csvBrowseBtn     = document.getElementById('csvBrowseBtn');
  const csvFileInput     = document.getElementById('csvFile');
  const fileInfo         = document.getElementById('fileInfo');
  const fileName         = document.getElementById('fileName');
  const fileSize         = document.getElementById('fileSize');
  const clearFileBtn     = document.getElementById('clearFileBtn');
  const downloadTemplateBtn = document.getElementById('downloadTemplateBtn');
  const importStep1      = document.getElementById('importStep1');
  const importStep2      = document.getElementById('importStep2');
  const importStatusText = document.getElementById('importStatusText');
  const importProgressBar= document.getElementById('importProgressBar');
  const importStats      = document.getElementById('importStats');
  const importResults    = document.getElementById('importResults');
  const importResultsList= document.getElementById('importResultsList');
  const importCancelBtn  = document.getElementById('importCancelBtn');
  const importSubmitBtn  = document.getElementById('importSubmitBtn');
  const importDoneBtn    = document.getElementById('importDoneBtn');
  const importSpinnerWrap = document.getElementById('importSpinnerWrap');
  const importSuccessWrap = document.getElementById('importSuccessWrap');

  // Filter modal
  const filterModalEl   = document.getElementById('filterModal');
  const filterModal     = new bootstrap.Modal(filterModalEl);
  const modalStatus     = document.getElementById('modal_status');
  const modalRole       = document.getElementById('modal_role');
  const modalSort       = document.getElementById('modal_sort');
  const btnApplyFilters = document.getElementById('btnApplyFilters');

  // User modal
  const userModalEl   = document.getElementById('userModal');
  const userModal     = new bootstrap.Modal(userModalEl);
  const userForm      = document.getElementById('userForm');
  const userModalTitle= document.getElementById('userModalTitle');
  const saveUserBtn   = document.getElementById('saveUserBtn');

  const userIdInput   = document.getElementById('userId');
  const userNameInput = document.getElementById('userName');
  const userEmailInput= document.getElementById('userEmail');
  const userPhoneInput= document.getElementById('userPhone');
  const userRoleInput = document.getElementById('userRole');
  const userStatusInput= document.getElementById('userStatus');
  const userPasswordInput  = document.getElementById('userPassword');
  const userPassword2Input = document.getElementById('userPasswordConfirmation');
  const userAltEmailInput  = document.getElementById('userAltEmail');
  const userAltPhoneInput  = document.getElementById('userAltPhone');
  const userWhatsAppInput  = document.getElementById('userWhatsApp');
  const userAddressInput   = document.getElementById('userAddress');
  const userImageInput     = document.getElementById('userImage');
  const imagePreview       = document.getElementById('imagePreview');
  const pwSections         = document.querySelectorAll('.js-pw-section');

  // View modal
  const userViewModalEl = document.getElementById('userViewModal');
  const userViewModal   = new bootstrap.Modal(userViewModalEl);
  const userViewBody    = document.getElementById('userViewBody');

  // Manage quizzes modal
  const userQuizzesModalEl = document.getElementById('userQuizzesModal');
  const userQuizzesModal   = new bootstrap.Modal(userQuizzesModalEl);
  const uq_user_name       = document.getElementById('uq_user_name');
  const uq_rows            = document.getElementById('uq_rows');
  const uq_loader          = document.getElementById('uq_loader');
  const uq_search          = document.getElementById('uq_search');
  const uq_filter          = document.getElementById('uq_filter');

  // Toasts
  const toastOk  = new bootstrap.Toast(document.getElementById('toastSuccess'));
  const toastErr = new bootstrap.Toast(document.getElementById('toastError'));
  const okTxt    = document.getElementById('toastSuccessText');
  const errTxt   = document.getElementById('toastErrorText');
  const ok  = (m)=>{ okTxt.textContent = m || 'Done'; toastOk.show(); };
  const err = (m)=>{ errTxt.textContent= m || 'Something went wrong'; toastErr.show(); };

  /* =================== UTILS =================== */
  function esc(s){
    const m = {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'};
    return (s==null?'':String(s)).replace(/[&<>"']/g,ch=>m[ch]);
  }
  function firstError(j){
    if (j && j.errors){
      const k = Object.keys(j.errors)[0];
      if (k){
        const v = j.errors[k];
        return Array.isArray(v) ? v[0] : String(v);
      }
    }
    return j && j.message ? j.message : '';
  }
  function debounce(fn,ms){
    let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a),ms); };
  }
  function authHeaders(extra){
    return Object.assign({'Authorization':'Bearer '+TOKEN}, extra || {});
  }
  const ROLE_LABEL = {
    super_admin: 'Super Admin',
    admin:       'Admin',
    examiner:    'Examiner',
    student:     'Student'
  };
  function roleLabel(v){
    const k = (v || '').toLowerCase();
    return ROLE_LABEL[k] || (v || '');
  }
  function fixImageUrl(url){
    if (!url) return null;
    if (url.startsWith('http://') || url.startsWith('https://') || url.startsWith('//')) return url;
    if (url.startsWith('/')) return url;
    return '/' + url;
  }
  function hideAllDropdowns(){
    document.querySelectorAll('.dd-toggle').forEach(btn=>{
      const inst = bootstrap.Dropdown.getInstance(btn);
      if (inst) inst.hide();
    });
  }
  function formatFileSize(bytes){
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  }

  /* =================== STATE =================== */
  let page        = 1;
  let perPage     = parseInt(perPageSel.value,10) || 20;
  let q           = '';
  let statusFilter= 'all';
  let roleFilter  = '';
  let sort        = '-created_at';
  let totalPages  = 1;
  let totalCount  = 0;
  let usersCache  = [];

  let uq_userId   = null;
  let uq_data     = [];

  /* =================== CSV IMPORT FEATURE =================== */
  // Download CSV template
  downloadTemplateBtn.addEventListener('click', function() {
    const csvContent = "name,email,password,role\n" +
                      "John Doe,john.doe@example.com,Pass@123,student\n" +
                      "Jane Smith,jane.smith@example.com,Pass@456,examiner\n" +
                      "Bob Wilson,bob@example.com,Pass@999,admin\n" +
                      "Alice Johnson,alice@example.com,Pass@789,student";

    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'users_template.csv';
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);

    ok('Template downloaded');
  });

  // File selection
  csvBrowseBtn.addEventListener('click', () => csvFileInput.click());

  csvFileInput.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
      handleFileSelection(file);
    }
  });

  // Drag and drop
  ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    csvDropZone.addEventListener(eventName, preventDefaults, false);
  });

  function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
  }

  ['dragenter', 'dragover'].forEach(eventName => {
    csvDropZone.addEventListener(eventName, highlight, false);
  });

  ['dragleave', 'drop'].forEach(eventName => {
    csvDropZone.addEventListener(eventName, unhighlight, false);
  });

  function highlight() {
    csvDropZone.classList.add('dragover');
  }

  function unhighlight() {
    csvDropZone.classList.remove('dragover');
  }

  csvDropZone.addEventListener('drop', function(e) {
    const dt = e.dataTransfer;
    const file = dt.files[0];
    if (file && file.type === 'text/csv' || file.name.endsWith('.csv')) {
      handleFileSelection(file);
    } else {
      err('Please drop a CSV file');
    }
  });

  function handleFileSelection(file) {
    // Check file size (10MB max)
    if (file.size > 10 * 1024 * 1024) {
      err('File size must be less than 10MB');
      return;
    }

    // Check file type
    if (!file.type.includes('csv') && !file.name.endsWith('.csv')) {
      err('Please select a CSV file');
      return;
    }

    // Update UI
    csvFileInput.files = new DataTransfer().files; // Clear first
    const dataTransfer = new DataTransfer();
    dataTransfer.items.add(file);
    csvFileInput.files = dataTransfer.files;

    fileName.textContent = file.name;
    fileSize.textContent = `(${formatFileSize(file.size)})`;
    fileInfo.classList.remove('d-none');
    importSubmitBtn.disabled = false;

    ok('File selected: ' + file.name);
  }

  clearFileBtn.addEventListener('click', function() {
    csvFileInput.value = '';
    fileInfo.classList.add('d-none');
    importSubmitBtn.disabled = true;
  });

  // Import form submission
  importCsvForm.addEventListener('submit', async function(e) {
    e.preventDefault();

    if (!csvFileInput.files || csvFileInput.files.length === 0) {
      err('Please select a CSV file');
      return;
    }

    // Switch to progress view
    importStep1.classList.add('d-none');
    importStep2.classList.remove('d-none');
    importSubmitBtn.classList.add('d-none');
    importCancelBtn.disabled = true;

    // reset visuals
    importSpinnerWrap.style.display = '';
    importSuccessWrap.classList.add('d-none');

    importStatusText.textContent = 'Processing CSV file...';
    importProgressBar.style.width = '10%';

    const formData = new FormData();
    formData.append('file', csvFileInput.files[0]);

    try {
      const response = await fetch('/api/users/import-csv', {
        method: 'POST',
        headers: authHeaders(),
        body: formData
      });

      importProgressBar.style.width = '70%';
      importStatusText.textContent = 'Creating users...';

      const result = await response.json().catch(() => ({}));

      if (!response.ok) {
        throw new Error(result.message || 'Import failed');
      }

      // Success
      importProgressBar.style.width = '100%';
      importStatusText.textContent = 'Import completed!';

      // Hide spinner, show BIG tick ✅
      importSpinnerWrap.style.display = 'none';
      importSuccessWrap.classList.remove('d-none');

      importCancelBtn.classList.add('d-none');
      importDoneBtn.classList.remove('d-none');

      // Show results
      if (result.meta) {
        const { imported, skipped, errors } = result.meta;
        importStats.innerHTML = `
          <div><strong>Imported:</strong> ${imported} users</div>
          <div><strong>Skipped:</strong> ${skipped} rows</div>
          ${errors.length > 0 ? `<div><strong>Errors:</strong> ${errors.length}</div>` : ''}
        `;

        if (errors.length > 0 || imported > 0) {
          importResults.classList.remove('d-none');
          let resultsHTML = '';

          if (imported > 0) {
            resultsHTML += `<div class="import-result-item success">
              <i class="fa fa-check-circle me-1"></i>
              Successfully imported ${imported} users
            </div>`;
          }

          if (skipped > 0) {
            resultsHTML += `<div class="import-result-item warning">
              <i class="fa fa-exclamation-triangle me-1"></i>
              Skipped ${skipped} rows (duplicate emails, invalid data)
            </div>`;
          }

          errors.forEach((error, index) => {
            if (index < 20) { // Limit display to 20 errors
              resultsHTML += `<div class="import-result-item error">
                <i class="fa fa-times-circle me-1"></i>
                ${esc(error)}
              </div>`;
            }
          });

          if (errors.length > 20) {
            resultsHTML += `<div class="import-result-item warning">
              <i class="fa fa-info-circle me-1"></i>
              ...and ${errors.length - 20} more errors
            </div>`;
          }

          importResultsList.innerHTML = resultsHTML;
        }

        if (imported > 0) {
          ok(`Successfully imported ${imported} users`);
          // Refresh the users list
          setTimeout(() => {
            loadUsers().catch(ex => err(ex.message || 'Reload failed'));
          }, 1000);
        }
      }

    } catch (error) {
      console.error('Import error:', error);
      importStatusText.textContent = 'Import failed';
      importProgressBar.style.width = '0%';
      importCancelBtn.disabled = false;
      importSubmitBtn.classList.remove('d-none');

      // keep tick hidden; spinner can be hidden to avoid misleading state
      importSpinnerWrap.style.display = 'none';
      importSuccessWrap.classList.add('d-none');

      importResults.classList.remove('d-none');
      importResultsList.innerHTML = `
        <div class="import-result-item error">
          <i class="fa fa-times-circle me-1"></i>
          ${esc(error.message || 'Import failed')}
        </div>
      `;

      err(error.message || 'Import failed');
    }
  });

  // Reset import modal when closed
  importCsvModalEl.addEventListener('hidden.bs.modal', function() {
    // Reset form
    importCsvForm.reset();
    csvFileInput.value = '';
    fileInfo.classList.add('d-none');
    importStep1.classList.remove('d-none');
    importStep2.classList.add('d-none');
    importResults.classList.add('d-none');
    importResultsList.innerHTML = '';
    importProgressBar.style.width = '0%';
    importSubmitBtn.disabled = true;
    importSubmitBtn.classList.remove('d-none');
    importCancelBtn.disabled = false;
    importCancelBtn.classList.remove('d-none');
    importDoneBtn.classList.add('d-none');

    // reset visuals
    importSpinnerWrap.style.display = '';
    importSuccessWrap.classList.add('d-none');
  });

  /* =================== FETCH USERS =================== */
  async function loadUsers(){
    usersTbody.innerHTML =
      `<tr><td colspan="7" class="empty-state">
         <i class="fa fa-circle-notch fa-spin mb-2" style="font-size:20px;"></i>
         <div>Loading users…</div>
       </td></tr>`;

    const params = new URLSearchParams({
      page: String(page),
      per_page: String(perPage),
      q: q,
      sort: sort
    });
    if (statusFilter && statusFilter !== 'all'){
      params.set('status', statusFilter);
    }
    if (roleFilter){
      // backend currently doesn't filter by role; still sent for future use
      params.set('role', roleFilter);
    }

    let res, json;
    try{
      res = await fetch('/api/users?' + params.toString(), {
        headers: authHeaders({'Accept':'application/json'})
      });
      if (res.status === 401 || res.status === 403){
        Swal.fire('Unauthorized','Please login again.','warning')
          .then(()=> location.href='/');
        return;
      }
      json = await res.json().catch(()=> ({}));
      if (!res.ok) throw new Error(json.message || 'Failed to load users');
    }catch(e){
      console.error('Users load error', e);
      usersTbody.innerHTML =
        `<tr><td colspan="7" class="empty-state text-danger">`+esc(e.message||'Failed to load users')+`</td></tr>`;
      resultsInfo.textContent = 'Failed to load users';
      err(e.message || 'Failed to load users');
      return;
    }

    const raw = Array.isArray(json.data) ? json.data : [];
    const hiddenEmail = SYSTEM_ADMIN_EMAIL.toLowerCase();
    const systemAdminInPage = raw.some(row => (row.email || '').toLowerCase() === hiddenEmail);

    let rows = raw.filter(row => (row.email || '').toLowerCase() !== hiddenEmail);

    totalCount = json.meta?.total ?? raw.length;
    if (systemAdminInPage && totalCount > rows.length){
      totalCount = totalCount - 1;
    }
    totalPages = json.meta?.total_pages ?? Math.max(1, Math.ceil(totalCount / perPage));

    // client-side role filter on visible set
    if (roleFilter){
      const rf = roleFilter.toLowerCase();
      rows = rows.filter(r => (r.role || '').toLowerCase() === rf);
    }

    // client-side sort for name/email (created_at can be implemented in backend later)
    function cmp(a,b){
      return a < b ? -1 : (a > b ? 1 : 0);
    }
    if (sort === 'name' || sort === '-name'){
      rows.sort((a,b)=> cmp((a.name||'').toLowerCase(), (b.name||'').toLowerCase()));
      if (sort === '-name') rows.reverse();
    }else if (sort === 'email' || sort === '-email'){
      rows.sort((a,b)=> cmp((a.email||'').toLowerCase(), (b.email||'').toLowerCase()));
      if (sort === '-email') rows.reverse();
    }
    // other sort keys (created_at) will be handled when backend supports

    usersCache = rows;
    renderUsers(rows);
    renderPager();

    const shown = rows.length;
    if (!shown){
      resultsInfo.textContent = `0 of ${totalCount}`;
    }else{
      const start = (page-1)*perPage + 1;
      const end   = start + shown - 1;
      resultsInfo.textContent = `Showing ${start} to ${end} of ${totalCount} entries`;
    }
  }

  function renderUsers(rows){
    if (!rows.length){
      usersTbody.innerHTML =
        `<tr><td colspan="7" class="empty-state">
           <i class="fa fa-users mb-2" style="font-size:22px;opacity:.7;"></i>
           <div>No users found.</div>
         </td></tr>`;
      return;
    }

    usersTbody.innerHTML = rows.map(row => {
      const active = (row.status || '').toLowerCase() === 'active';
      const statusBadge = CAN_WRITE
        ? `<div class="form-check form-switch m-0">
             <input class="form-check-input js-toggle-status" type="checkbox" ${active?'checked':''} title="Toggle Active">
           </div>`
        : `<span class="badge ${active?'badge-soft-active':'badge-soft-inactive'}">${active?'Active':'Inactive'}</span>`;

      const imgUrl = fixImageUrl(row.image);
      const avatarHtml = `
        <div style="position:relative;">
          ${imgUrl ? `
            <img src="${esc(imgUrl)}" alt="avatar" class="u-avatar"
                 loading="lazy"
                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
          ` : ''}
          <div class="u-avatar-fallback" style="display:${imgUrl?'none':'flex'};">
            <span>${esc((row.name||'').charAt(0) || '?')}</span>
          </div>
        </div>`;

      const emailHtml = row.email
        ? `<a href="mailto:${esc(row.email)}">${esc(row.email)}</a>`
        : `<span class="text-muted">—</span>`;

      const quizzesBtn = CAN_WRITE
        ? `<button type="button" class="btn btn-light btn-sm js-manage-quizzes">
             <i class="fa fa-question-circle me-1"></i>Manage
           </button>`
        : `<span class="text-muted small">—</span>`;

      let actions = `
        <div class="dropdown text-end" data-bs-display="static">
          <button type="button" class="btn btn-light btn-sm dd-toggle"
                  data-bs-toggle="dropdown" data-bs-auto-close="outside"
                  data-bs-boundary="viewport" aria-expanded="false"
                  title="Actions">
            <i class="fa fa-ellipsis-vertical"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li>
              <button type="button" class="dropdown-item" data-action="view">
                <i class="fa fa-eye"></i> View
              </button>
            </li>`;
      if (CAN_WRITE){
        actions += `
            <li>
              <button type="button" class="dropdown-item" data-action="edit">
                <i class="fa fa-pen-to-square"></i> Edit
              </button>
            </li>
            <li>
              <button type="button" class="dropdown-item" data-action="quizzes">
                <i class="fa fa-question-circle"></i> Manage Quizzes
              </button>
            </li>`;
      }
      if (CAN_DELETE){
        actions += `
            <li><hr class="dropdown-divider"></li>
            <li>
              <button type="button" class="dropdown-item text-danger" data-action="delete">
                <i class="fa fa-trash"></i> Delete
              </button>
            </li>`;
      }
      actions += `
          </ul>
        </div>`;

      return `
        <tr data-id="${row.id}">
          <td>${statusBadge}</td>
          <td>${avatarHtml}</td>
          <td class="fw-semibold">${esc(row.name || '')}</td>
          <td>${emailHtml}</td>
          <td>
            <span class="badge badge-role">
              <i class="fa fa-user-shield me-1"></i>${esc(roleLabel(row.role))}
            </span>
          </td>
          <td class="text-center">${quizzesBtn}</td>
          <td class="text-end">${actions}</td>
        </tr>`;
    }).join('');
  }

  function renderPager(){
    if (!totalPages || totalPages <= 1){
      pager.innerHTML = '';
      return;
    }
    let html = '';
    function item(p,label,disabled,active){
      if (disabled){
        return `<li class="page-item disabled"><span class="page-link">${label}</span></li>`;
      }
      if (active){
        return `<li class="page-item active"><span class="page-link">${label}</span></li>`;
      }
      return `<li class="page-item"><a class="page-link" href="#" data-page="${p}">${label}</a></li>`;
    }
    html += item(Math.max(1,page-1),'Previous',page<=1,false);
    const start = Math.max(1, page-2);
    const end   = Math.min(totalPages, page+2);
    for (let i=start;i<=end;i++){
      html += item(i, String(i), false, i===page);
    }
    html += item(Math.min(totalPages,page+1),'Next',page>=totalPages,false);
    pager.innerHTML = html;
  }

  /* =================== EVENTS: PAGER, SEARCH, FILTER =================== */
  pager.addEventListener('click', function(e){
    const a = e.target.closest('a.page-link');
    if (!a) return;
    e.preventDefault();
    const p = parseInt(a.dataset.page,10);
    if (!p || p === page) return;
    page = p;
    loadUsers().catch(ex => err(ex.message || 'Load failed'));
    window.scrollTo({top:0,behavior:'smooth'});
  });

  const triggerSearch = debounce(function(){
    q = searchInput.value.trim();
    page = 1;
    loadUsers().catch(ex => err(ex.message || 'Load failed'));
  }, 320);
  searchInput.addEventListener('input', triggerSearch);

  perPageSel.addEventListener('change', function(){
    perPage = parseInt(perPageSel.value,10) || 20;
    page = 1;
    loadUsers().catch(ex => err(ex.message || 'Load failed'));
  });

  filterModalEl.addEventListener('show.bs.modal', function(){
    modalStatus.value = statusFilter;
    modalRole.value   = roleFilter;
    modalSort.value   = sort;
  });

  btnApplyFilters.addEventListener('click', function(){
    statusFilter = modalStatus.value;
    roleFilter   = modalRole.value;
    sort         = modalSort.value;
    page         = 1;
    filterModal.hide();
    loadUsers().catch(ex => err(ex.message || 'Load failed'));
  });

  btnReset.addEventListener('click', function(){
    statusFilter = 'all';
    roleFilter   = '';
    sort         = '-created_at';
    q            = '';
    perPage      = 20;
    page         = 1;

    searchInput.value  = '';
    perPageSel.value   = '20';
    modalStatus.value  = 'all';
    modalRole.value    = '';
    modalSort.value    = '-created_at';

    loadUsers().catch(ex => err(ex.message || 'Load failed'));
  });

  /* =================== ADD / EDIT USER =================== */
  function resetUserForm(){
    userIdInput.value   = '';
    userNameInput.value = '';
    userEmailInput.value= '';
    userPhoneInput.value= '';
    userRoleInput.value = '';
    userStatusInput.value='active';
    userPasswordInput.value = '';
    userPassword2Input.value= '';
    userAltEmailInput.value = '';
    userAltPhoneInput.value = '';
    userWhatsAppInput.value = '';
    userAddressInput.value  = '';
    userImageInput.value    = '';
    imagePreview.style.display = 'none';
    imagePreview.src = '';
    // show password fields in create mode
    pwSections.forEach(el => el.classList.remove('d-none'));
  }

  function openCreateUser(){
    resetUserForm();
    userModalTitle.textContent = 'Add User';
    userForm.dataset.mode = 'create';
    userModal.show();
  }

  async function openEditUser(id){
    resetUserForm();
    userModalTitle.textContent = 'Edit User';
    userForm.dataset.mode = 'edit';
    // hide password fields for edit
    pwSections.forEach(el => el.classList.add('d-none'));

    try{
      const res = await fetch(`/api/users/${id}`, {
        headers: authHeaders({'Accept':'application/json'})
      });
      const j = await res.json().catch(()=> ({}));
      if (!res.ok) throw new Error(j.message || 'Failed to load user');

      const u = j.user || {};
      userIdInput.value    = u.id || '';
      userNameInput.value  = u.name || '';
      userEmailInput.value = u.email || '';
      userPhoneInput.value = u.phone_number || '';
      userRoleInput.value  = u.role || '';
      userStatusInput.value= u.status || 'active';
      userAltEmailInput.value = u.alternative_email || '';
      userAltPhoneInput.value = u.alternative_phone_number || '';
      userWhatsAppInput.value = u.whatsapp_number || '';
      userAddressInput.value  = u.address || '';

      const imgUrl = fixImageUrl(u.image);
      if (imgUrl){
        imagePreview.src = imgUrl;
        imagePreview.style.display = 'block';
      }
      userModal.show();
    }catch(e){
      err(e.message || 'Failed to open editor');
    }
  }

  userImageInput.addEventListener('change', function(){
    const f = userImageInput.files && userImageInput.files[0];
    if (!f){
      imagePreview.style.display = 'none';
      imagePreview.src = '';
      return;
    }
    const url = URL.createObjectURL(f);
    imagePreview.src = url;
    imagePreview.style.display = 'block';
  });

  // password eye toggles
  document.querySelectorAll('.js-eye-toggle').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const id = btn.dataset.target;
      const inp = document.getElementById(id);
      if (!inp) return;
      const isPwd = inp.type === 'password';
      inp.type = isPwd ? 'text' : 'password';
      const icon = btn.querySelector('i');
      if (icon){
        icon.classList.toggle('fa-eye-slash', !isPwd);
        icon.classList.toggle('fa-eye', isPwd);
      }
    });
  });

  userForm.addEventListener('submit', async function(e){
    e.preventDefault();
    if (!CAN_WRITE){
      err('You do not have permission to modify users');
      return;
    }

    const mode = userForm.dataset.mode || 'create';

    const name  = userNameInput.value.trim();
    const email = userEmailInput.value.trim();
    const role  = userRoleInput.value;
    const status= userStatusInput.value || 'active';

    if (!name){
      Swal.fire('Name required','Please enter full name.','info');
      return;
    }
    if (!email){
      Swal.fire('Email required','Please enter email.','info');
      return;
    }
    if (!role){
      Swal.fire('Role required','Please select role.','info');
      return;
    }

    if (mode === 'create'){
      const pw  = userPasswordInput.value;
      const pw2 = userPassword2Input.value;
      if (!pw || pw.length < 8){
        Swal.fire('Password too short','Password must be at least 8 characters.','info');
        return;
      }
      if (pw !== pw2){
        Swal.fire('Password mismatch','Password and confirm password must match.','info');
        return;
      }
    }

    const fd = new FormData();
    fd.append('name', name);
    fd.append('email', email);
    fd.append('role', role);
    fd.append('status', status);

    if (userPhoneInput.value.trim()){
      fd.append('phone_number', userPhoneInput.value.trim());
    }
    if (userAltEmailInput.value.trim()){
      fd.append('alternative_email', userAltEmailInput.value.trim());
    }
    if (userAltPhoneInput.value.trim()){
      fd.append('alternative_phone_number', userAltPhoneInput.value.trim());
    }
    if (userWhatsAppInput.value.trim()){
      fd.append('whatsapp_number', userWhatsAppInput.value.trim());
    }
    if (userAddressInput.value.trim()){
      fd.append('address', userAddressInput.value.trim());
    }
    if (mode === 'create'){
      fd.append('password', userPasswordInput.value);
    }
    if (userImageInput.files && userImageInput.files[0]){
      fd.append('image', userImageInput.files[0]);
    }

    let url  = '/api/users';
    let method = 'POST';
    if (mode === 'edit'){
      const id = userIdInput.value;
      if (!id){
        err('Missing user id');
        return;
      }
      url = `/api/users/${id}`;
      // use _method PATCH so file upload works nicely
      fd.append('_method','PATCH');
      method = 'POST';
    }

    saveUserBtn.disabled = true;
    const oldHtml = saveUserBtn.innerHTML;
    saveUserBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status"></span>Saving…`;

    try{
      const res = await fetch(url, {
        method,
        headers: authHeaders({'Accept':'application/json'}),
        body: fd
      });
      const j = await res.json().catch(()=> ({}));
      if (!res.ok) throw new Error(firstError(j) || 'Save failed');

      ok(mode === 'create' ? 'User created' : 'User updated');
      userModal.hide();
      loadUsers().catch(ex => err(ex.message || 'Reload failed'));
    }catch(e){
      err(e.message || 'Save failed');
    }finally{
      saveUserBtn.disabled = false;
      saveUserBtn.innerHTML = oldHtml;
    }
  });

  if (CAN_WRITE && btnAddUser){
    btnAddUser.addEventListener('click', openCreateUser);
  }

  /* =================== ROW ACTIONS =================== */
  usersTbody.addEventListener('change', async function(e){
    const sw = e.target.closest('.js-toggle-status');
    if (!sw) return;

    if (!CAN_WRITE){
      sw.checked = !sw.checked;
      return;
    }

    const tr = sw.closest('tr');
    const id = tr?.dataset?.id;
    if (!id) return;

    const willActive = sw.checked;
    try{
      const res = await fetch(`/api/users/${id}`, {
        method:'PATCH',
        headers: authHeaders({'Content-Type':'application/json','Accept':'application/json'}),
        body: JSON.stringify({ status: willActive ? 'active' : 'inactive' })
      });
      const j = await res.json().catch(()=> ({}));
      if (!res.ok) throw new Error(firstError(j) || 'Status update failed');
      ok(willActive ? 'User activated' : 'User deactivated');
    }catch(e){
      sw.checked = !willActive;
      err(e.message || 'Status update failed');
    }
  });

  usersTbody.addEventListener('click', async function(e){
    const tr = e.target.closest('tr[data-id]');
    if (!tr) return;
    const id = tr.dataset.id;

    // close any open dropdowns before opening modals
    hideAllDropdowns();

    if (e.target.closest('.js-manage-quizzes')){
      if (!CAN_WRITE){
        err('You do not have permission to manage quizzes');
        return;
      }
      openUserQuizzes(id);
      return;
    }

    const actionBtn = e.target.closest('[data-action]');
    if (!actionBtn) return;

    const act = actionBtn.dataset.action;
    if (act === 'view'){
      openViewUser(id);
    }else if (act === 'edit'){
      if (!CAN_WRITE){
        err('You do not have permission to edit users');
        return;
      }
      openEditUser(id);
    }else if (act === 'quizzes'){
      if (!CAN_WRITE){
        err('You do not have permission to manage quizzes');
        return;
      }
      openUserQuizzes(id);
    }else if (act === 'delete'){
      if (!CAN_DELETE){
        err('Only Super Admin can delete users');
        return;
      }
      confirmDeleteUser(id);
    }
  });

  async function openViewUser(id){
    userViewBody.innerHTML = 'Loading…';
    userViewModal.show();

    try{
      const res = await fetch(`/api/users/${id}`, {
        headers: authHeaders({'Accept':'application/json'})
      });
      const j = await res.json().catch(()=> ({}));
      if (!res.ok) throw new Error(j.message || 'Failed to load user');

      const u = j.user || {};
      const img = fixImageUrl(u.image);
      const avatar = img
        ? `<img src="${esc(img)}" alt="avatar" style="width:64px;height:64px;border-radius:14px;object-fit:cover;border:1px solid var(--line-strong);" onerror="this.style.display='none';">`
        : `<div class="u-avatar-fallback" style="width:64px;height:64px;font-size:18px;">
             <span>${esc((u.name||'').charAt(0) || '?')}</span>
           </div>`;

      userViewBody.innerHTML = `
        <div class="d-flex gap-3 align-items-start mb-3">
          ${avatar}
          <div>
            <div class="h5 mb-1">${esc(u.name || '')}</div>
            <div class="small text-muted mb-1">${esc(u.email || '')}</div>
            <div class="small">
              <span class="badge badge-role me-1"><i class="fa fa-user-shield me-1"></i>${esc(roleLabel(u.role))}</span>
              <span class="badge ${String(u.status).toLowerCase()==='active'?'badge-soft-active':'badge-soft-inactive'}">
                ${esc(u.status || '')}
              </span>
            </div>
          </div>
        </div>
        <div class="row g-3 small">
          <div class="col-md-6">
            <div><span class="text-muted">Phone:</span> ${esc(u.phone_number || '—')}</div>
            <div><span class="text-muted">Alt. Phone:</span> ${esc(u.alternative_phone_number || '—')}</div>
            <div><span class="text-muted">WhatsApp:</span> ${esc(u.whatsapp_number || '—')}</div>
          </div>
          <div class="col-md-6">
            <div><span class="text-muted">Alt. Email:</span> ${esc(u.alternative_email || '—')}</div>
            <div><span class="text-muted">Last login:</span> ${esc(u.last_login_at || '—')}</div>
            <div><span class="text-muted">Last IP:</span> ${esc(u.last_login_ip || '—')}</div>
          </div>
          <div class="col-12">
            <div class="text-muted mb-1">Address</div>
            <div>${u.address ? esc(u.address) : '<span class="text-muted">—</span>'}</div>
          </div>
        </div>`;
    }catch(e){
      userViewBody.innerHTML = `<div class="text-danger">${esc(e.message || 'Failed to load user')}</div>`;
    }
  }

  async function confirmDeleteUser(id){
    const resUser = usersCache.find(u => String(u.id) === String(id));
    const name = resUser?.name || 'this user';

    const {isConfirmed} = await Swal.fire({
      title: 'Delete user?',
      text: `This will soft-delete ${name}.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Delete',
      confirmButtonColor: '#ef4444'
    });
    if (!isConfirmed) return;

    try{
      const res = await fetch(`/api/users/${id}`, {
        method:'DELETE',
        headers: authHeaders({'Accept':'application/json'})
      });
      const j = await res.json().catch(()=> ({}));
      if (!res.ok) throw new Error(firstError(j) || 'Delete failed');
      ok('User deleted');
      loadUsers().catch(ex => err(ex.message || 'Reload failed'));
    }catch(e){
      err(e.message || 'Delete failed');
    }
  }

  /* =================== USER QUIZZES =================== */
  async function openUserQuizzes(id){
    uq_userId = parseInt(id,10);
    const targetUser = usersCache.find(u => String(u.id) === String(id));
    uq_user_name.textContent = targetUser?.name || ('User #'+id);
    uq_search.value = '';
    uq_filter.value = 'all';
    uq_rows.innerHTML = '';
    uq_loader.style.display = '';

    userQuizzesModal.show();

    try{
      const res = await fetch(`/api/users/${id}/quizzes`, {
        headers: authHeaders({'Accept':'application/json'})
      });
      const j = await res.json().catch(()=> ({}));
      if (!res.ok) throw new Error(j.message || 'Failed to load quizzes');

      uq_data = Array.isArray(j.data) ? j.data : [];
      renderUserQuizzes();
    }catch(e){
      console.error('User quizzes load error', e);
      uq_rows.innerHTML =
        `<tr><td colspan="7" class="p-3 text-danger text-center">${esc(e.message || 'Failed to load quizzes')}</td></tr>`;
    }finally{
      uq_loader.style.display = 'none';
    }
  }

  function renderUserQuizzes(){
    uq_rows.querySelectorAll('tr:not(#uq_loader)').forEach(tr => tr.remove());

    let list = uq_data.slice();
    const qText = uq_search.value.trim().toLowerCase();
    const filter = uq_filter.value;

    if (qText){
      list = list.filter(x => {
        const nm = (x.quiz_name || '').toLowerCase();
        return nm.includes(qText);
      });
    }
    if (filter === 'assigned'){
      list = list.filter(x => !!x.assigned);
    }else if (filter === 'unassigned'){
      list = list.filter(x => !x.assigned);
    }

    if (!list.length){
      uq_rows.innerHTML =
        `<tr><td colspan="7" class="p-3 text-center text-muted">No quizzes.</td></tr>`;
      return;
    }

    const frag = document.createDocumentFragment();
    list.forEach(qz => {
      const assigned = !!qz.assigned;
      const status  = (qz.status || '').toLowerCase();
      const isPublic = (qz.is_public || '').toLowerCase();
      const code = qz.assignment_code || '';

      const statusBadge = status === 'active'
        ? `<span class="badge badge-soft-active text-uppercase">${esc(status)}</span>`
        : `<span class="badge badge-soft-inactive text-uppercase">${esc(status||'-')}</span>`;

      const publicBadge = (isPublic === 'yes' || isPublic === 'public')
        ? `<span class="badge bg-success-subtle text-success border border-success-subtle">Yes</span>`
        : `<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">No</span>`;

      const codeHtml = code
        ? `<button type="button" class="badge-code js-copy-assignment" data-code="${esc(code)}" title="Click to copy assignment code">
             <span>${esc(code)}</span>
             <i class="fa-regular fa-copy"></i>
           </button>`
        : '<span class="text-muted small">—</span>';

      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td class="fw-semibold">${esc(qz.quiz_name || '')}</td>
        <td>${qz.total_time != null ? esc(String(qz.total_time)) : '—'}</td>
        <td>${qz.total_questions != null ? esc(String(qz.total_questions)) : '—'}</td>
        <td>${statusBadge}</td>
        <td>${publicBadge}</td>
        <td>${codeHtml}</td>
        <td class="text-center">
          <div class="form-check form-switch d-inline-block m-0">
            <input class="form-check-input uq-toggle" type="checkbox" data-qid="${qz.quiz_id}" ${assigned?'checked':''}>
          </div>
        </td>`;
      frag.appendChild(tr);
    });
    uq_rows.appendChild(frag);

    uq_rows.querySelectorAll('.uq-toggle').forEach(ch => {
      ch.addEventListener('change', async ()=>{
        const quizId   = parseInt(ch.dataset.qid,10);
        const assigned = !!ch.checked;
        await toggleUserQuiz(quizId, assigned, ch);
      });
    });
  }

  async function toggleUserQuiz(quizId, assigned, checkboxEl){
    if (!uq_userId || !quizId) return;
    try{
      const url = assigned
        ? `/api/users/${uq_userId}/quizzes/assign`
        : `/api/users/${uq_userId}/quizzes/unassign`;

      const res = await fetch(url, {
        method:'POST',
        headers: authHeaders({'Content-Type':'application/json','Accept':'application/json'}),
        body: JSON.stringify({ quiz_id: quizId })
      });
      const j = await res.json().catch(()=> ({}));
      if (!res.ok) throw new Error(firstError(j) || 'Operation failed');

      const item = uq_data.find(x => Number(x.quiz_id) === Number(quizId));
      if (assigned){
        const code = j.data?.assignment_code || item?.assignment_code || '';
        if (item){
          item.assigned = true;
          item.assignment_code = code;
          item.status = 'active';
        }
        ok('Quiz assigned to user');
      }else{
        if (item){
          item.assigned = false;
          // keep code for audit if needed; remove from UI
          item.assignment_code = null;
          item.status = 'revoked';
        }
        ok('Quiz unassigned from user');
      }
      renderUserQuizzes();
    }catch(e){
      if (checkboxEl) checkboxEl.checked = !assigned;
      err(e.message || 'Failed to update assignment');
    }
  }

  uq_search.addEventListener('input', debounce(renderUserQuizzes, 250));
  uq_filter.addEventListener('change', renderUserQuizzes);

  /* =================== COPY ASSIGNMENT CODE =================== */
  document.addEventListener('click', function(e){
    const pill = e.target.closest('.js-copy-assignment');
    if (!pill) return;
    const code = pill.dataset.code || '';
    if (!code) return;

    if (navigator.clipboard && navigator.clipboard.writeText){
      navigator.clipboard.writeText(code)
        .then(()=> ok('Assignment code copied'))
        .catch(()=> err('Unable to copy code'));
    }else{
      const tmp = document.createElement('input');
      tmp.value = code;
      document.body.appendChild(tmp);
      tmp.select();
      try{
        document.execCommand('copy');
        ok('Assignment code copied');
      }catch(_){
        err('Unable to copy code');
      }
      document.body.removeChild(tmp);
    }
  });

  /* =================== INITIAL LOAD =================== */
  loadUsers().catch(ex => err(ex.message || 'Failed to load users'));

});
</script>
