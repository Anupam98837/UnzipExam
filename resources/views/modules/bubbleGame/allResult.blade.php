{{-- resources/views/modules/bubble_game/manageBubbleGameResults.blade.php --}}
@extends('pages.users.layout.structure')

@section('title','Bubble Game Results')

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
<link rel="stylesheet" href="{{ asset('assets/css/common/main.css') }}"/>

<style>
/* ===== Shell ===== */
.qr-wrap{max-width:1140px;margin:16px auto 40px;overflow:visible}
.panel{background:var(--surface);border:1px solid var(--line-strong);border-radius:16px;box-shadow:var(--shadow-2);padding:14px}

/* Toolbar */
.mfa-toolbar .form-control{height:40px;border-radius:12px;border:1px solid var(--line-strong);background:var(--surface)}
.mfa-toolbar .form-select{height:40px;border-radius:12px;border:1px solid var(--line-strong);background:var(--surface)}
.mfa-toolbar .btn{height:40px;border-radius:12px}
.mfa-toolbar .btn-light{background:var(--surface);border:1px solid var(--line-strong)}
.mfa-toolbar .btn-primary{background:var(--primary-color);border:none}

/* Tabs */
.nav.nav-tabs{border-color:var(--line-strong)}
.nav-tabs .nav-link{color:var(--ink)}
.nav-tabs .nav-link.active{background:var(--surface);border-color:var(--line-strong) var(--line-strong) var(--surface)}
.tab-content,.tab-pane{overflow:visible}

/* Table Card */
.table-wrap.card{position:relative;border:1px solid var(--line-strong);border-radius:16px;background:var(--surface);box-shadow:var(--shadow-2);overflow:visible}
.table-wrap .card-body{overflow:visible}
.table-responsive{overflow:visible !important}
.table{--bs-table-bg:transparent}
.table thead th{font-weight:600;color:var(--muted-color);font-size:13px;border-bottom:1px solid var(--line-strong);background:var(--surface)}
.table thead.sticky-top{z-index:3}
.table tbody tr{border-top:1px solid var(--line-soft)}
.table tbody tr:hover{background:var(--page-hover)}
td .fw-semibold{color:var(--ink)}
.small{font-size:12.5px}

/* Badges */
.badge-soft{background:color-mix(in oklab, var(--muted-color) 12%, transparent);color:var(--ink)}
.badge-pill{display:inline-flex;align-items:center;gap:.35rem;padding:.25rem .55rem;border-radius:999px;border:1px solid var(--line-strong);background:color-mix(in oklab, var(--muted-color) 10%, transparent)}
.badge-success{background:var(--success-color)!important;color:#fff!important;border:none!important}
.badge-warning{background:var(--warning-color)!important;color:#0b1324!important;border:none!important}
.badge-danger{background:var(--danger-color)!important;color:#fff!important;border:none!important}
.badge-secondary{background:#64748b!important;color:#fff!important;border:none!important}

/* Sorting */
.sortable{cursor:pointer;white-space:nowrap}
.sortable .caret{display:inline-block;margin-left:.35rem;opacity:.65}
.sortable.asc .caret::after{content:"▲";font-size:.7rem}
.sortable.desc .caret::after{content:"▼";font-size:.7rem}

/* Checkbox cell */
.chkcell{width:44px}
.chkcell .form-check-input{cursor:pointer}

/* ✅ Bulk mode: hide checkboxes by default, show only after bulk-filter apply */
/* ✅ FIX: show bulk checkbox column ONLY in Results tab (nothing else changes) */
.qr-wrap .bulk-col{display:none !important;}
.qr-wrap.bulk-mode #tab-results .bulk-col{display:table-cell !important;}
.qr-wrap.bulk-mode #tab-results .bulk-col .form-check-input{display:inline-block !important;}

/* Dropdowns inside table */
.table-wrap .dropdown{position:relative;z-index:6}
.table-wrap .dd-toggle{position:relative;z-index:7}
.dropdown [data-bs-toggle="dropdown"]{border-radius:10px}
.table-wrap .dropdown-menu{border-radius:12px;border:1px solid var(--line-strong);box-shadow:var(--shadow-2);min-width:220px;z-index:5000}
.dropdown-menu.dd-portal{position:fixed!important;left:0;top:0;transform:none!important;z-index:5000;border-radius:12px;border:1px solid var(--line-strong);box-shadow:var(--shadow-2);min-width:220px;background:var(--surface)}
.dropdown-item{display:flex;align-items:center;gap:.6rem}
.dropdown-item i{width:16px;text-align:center}
.dropdown-item.text-danger{color:var(--danger-color)!important}

/* Action icon style */
.icon-btn{display:inline-flex;align-items:center;justify-content:center;height:34px;min-width:34px;padding:0 10px;border:1px solid var(--line-strong);background:var(--surface);border-radius:10px}
.icon-btn:hover{box-shadow:var(--shadow-1)}

/* Empty & loader */
.empty{color:var(--muted-color)}
.placeholder{background:linear-gradient(90deg,#00000010,#00000005,#00000010);border-radius:8px}

/* Modals */
.modal-content{border-radius:16px;border:1px solid var(--line-strong);background:var(--surface)}
.modal-header{border-bottom:1px solid var(--line-strong)}
.modal-footer{border-top:1px solid var(--line-strong)}
.form-control,.form-select{border-radius:12px;border:1px solid var(--line-strong);background:#fff}
html.theme-dark .form-control,html.theme-dark .form-select{background:#0f172a;color:#e5e7eb;border-color:var(--line-strong)}

/* Switch tweak */
.form-switch .form-check-input{width:46px;height:24px}
.form-switch .form-check-input:focus{box-shadow:0 0 0 4px color-mix(in oklab, var(--primary-color) 22%, transparent);border-color:var(--primary-color)}

/* Dark tweaks */
html.theme-dark .panel,
html.theme-dark .table-wrap.card,
html.theme-dark .modal-content{background:#0f172a;border-color:var(--line-strong)}
html.theme-dark .table thead th{background:#0f172a;border-color:var(--line-strong);color:#94a3b8}
html.theme-dark .table tbody tr{border-color:var(--line-soft)}
html.theme-dark .dropdown-menu{background:#0f172a;border-color:var(--line-strong)}
</style>
@endpush

@section('content')
<div class="qr-wrap" id="qrWrap">

  {{-- ================= Tabs ================= --}}
  <ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item">
      <a class="nav-link active" data-bs-toggle="tab" href="#tab-results" role="tab" aria-selected="true">
        <i class="fa-solid fa-square-poll-vertical me-2"></i>Results
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" data-bs-toggle="tab" href="#tab-published" role="tab" aria-selected="false">
        <i class="fa-solid fa-eye me-2"></i>Published to Students
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" data-bs-toggle="tab" href="#tab-unpublished" role="tab" aria-selected="false">
        <i class="fa-solid fa-eye-slash me-2"></i>Not Published
      </a>
    </li>
  </ul>

  <div class="tab-content mb-3">

    {{-- ========== TAB: Results ========== --}}
    <div class="tab-pane fade show active" id="tab-results" role="tabpanel">
      {{-- Toolbar --}}
      <div class="row align-items-center g-2 mb-3 mfa-toolbar panel">
        <div class="col-12 col-xl d-flex align-items-center flex-wrap gap-2">
          <div class="d-flex align-items-center gap-2">
            <label class="text-muted small mb-0">Per page</label>
            <select id="per_page" class="form-select" style="width:96px;">
              <option>10</option>
              <option selected>20</option>
              <option>30</option>
              <option>50</option>
              <option>100</option>
            </select>
          </div>

          <div class="position-relative" style="min-width:320px;">
            <input id="q" type="text" class="form-control ps-5" placeholder="Search student / email / game…">
            <i class="fa fa-search position-absolute" style="left:12px;top:50%;transform:translateY(-50%);opacity:.6;"></i>
          </div>

          <button id="btnFilter" class="btn btn-primary ms-1" data-bs-toggle="modal" data-bs-target="#filterModal">
            <i class="fa fa-filter me-1"></i>Filter
          </button>

          {{-- ✅ Bulk publish button (same behavior as quiz results page) --}}
          <button id="btnBulkPublish" class="btn btn-primary">
            <i class="fa fa-bullhorn me-1"></i>Bulk Publish
          </button>
          <button id="btnExport" class="btn btn-light">
  <i class="fa fa-file-export me-1"></i>Export
</button>


          <button id="btnReset" class="btn btn-primary">
            <i class="fa fa-rotate-left me-1"></i>Reset
          </button>
        </div>
      </div>

      {{-- Table --}}
      <div class="card table-wrap">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover table-borderless align-middle mb-0">
              <thead class="sticky-top">
                <tr>
                  <th class="chkcell bulk-col">
                    <input id="chkAll-results" class="form-check-input" type="checkbox" title="Select all on this page">
                  </th>

                  <th class="sortable" data-col="student_name">STUDENT <span class="caret"></span></th>

                  {{-- ✅ NEW COLUMN --}}
                  <th style="width:170px;">FOLDER</th>

                  <th class="sortable" data-col="game_title">GAME <span class="caret"></span></th>
                  <th style="width:110px;">ATTEMPT</th>
                  <th class="sortable" data-col="score" style="width:140px;">SCORE <span class="caret"></span></th>
                  <th class="sortable" data-col="accuracy" style="width:120px;">% <span class="caret"></span></th>

                  {{-- ✅ NEW COLUMN --}}
                  <th style="width:140px;">PUBLISH STATUS</th>

                  <th style="width:150px;">STATUS</th>
                  <th class="sortable" data-col="result_created_at" style="width:170px;">SUBMITTED <span class="caret"></span></th>
                  <th class="text-end" style="width:112px;">ACTIONS</th>
                </tr>
              </thead>
              <tbody id="rows-results">
                <tr id="loaderRow-results" style="display:none;">
                  <td colspan="11" class="p-0">
                    <div class="p-4">
                      <div class="placeholder-wave">
                        <div class="placeholder col-12 mb-2" style="height:18px;"></div>
                        <div class="placeholder col-12 mb-2" style="height:18px;"></div>
                        <div class="placeholder col-12 mb-2" style="height:18px;"></div>
                        <div class="placeholder col-12 mb-2" style="height:18px;"></div>
                      </div>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div id="empty-results" class="empty p-4 text-center" style="display:none;">
            <i class="fa fa-circle-info mb-2" style="font-size:32px; opacity:.6;"></i>
            <div>No results found.</div>
          </div>

          <div class="d-flex flex-wrap align-items-center justify-content-between p-3 gap-2">
            <div class="text-muted small" id="metaTxt-results">—</div>
            <nav style="position:relative; z-index:1;"><ul id="pager-results" class="pagination mb-0"></ul></nav>
          </div>
        </div>
      </div>
    </div>

    {{-- ========== TAB: Published ========== --}}
    <div class="tab-pane fade" id="tab-published" role="tabpanel">
      <div class="card table-wrap">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover table-borderless align-middle mb-0">
              <thead class="sticky-top">
                <tr>
                  <th class="chkcell bulk-col">
                    <input id="chkAll-published" class="form-check-input" type="checkbox" title="Select all on this page">
                  </th>

                  <th>STUDENT</th>

                  {{-- ✅ NEW COLUMN --}}
                  <th style="width:170px;">FOLDER</th>

                  <th>GAME</th>
                  <th style="width:110px;">ATTEMPT</th>
                  <th style="width:140px;">SCORE</th>
                  <th style="width:120px;">%</th>

                  {{-- ✅ NEW COLUMN --}}
                  <th style="width:140px;">PUBLISH STATUS</th>

                  <th style="width:170px;">SUBMITTED</th>
                  <th class="text-end" style="width:112px;">ACTIONS</th>
                </tr>
              </thead>
              <tbody id="rows-published">
                <tr id="loaderRow-published" style="display:none;">
                  <td colspan="10" class="p-0">
                    <div class="p-4">
                      <div class="placeholder-wave">
                        <div class="placeholder col-12 mb-2" style="height:18px;"></div>
                        <div class="placeholder col-12 mb-2" style="height:18px;"></div>
                      </div>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div id="empty-published" class="empty p-4 text-center" style="display:none;">
            <i class="fa fa-eye mb-2" style="font-size:32px; opacity:.6;"></i>
            <div>No published results.</div>
          </div>

          <div class="d-flex flex-wrap align-items-center justify-content-between p-3 gap-2">
            <div class="text-muted small" id="metaTxt-published">—</div>
            <nav style="position:relative; z-index:1;"><ul id="pager-published" class="pagination mb-0"></ul></nav>
          </div>
        </div>
      </div>
    </div>

    {{-- ========== TAB: Unpublished ========== --}}
    <div class="tab-pane fade" id="tab-unpublished" role="tabpanel">
      <div class="card table-wrap">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover table-borderless align-middle mb-0">
              <thead class="sticky-top">
                <tr>
                  <th class="chkcell bulk-col">
                    <input id="chkAll-unpublished" class="form-check-input" type="checkbox" title="Select all on this page">
                  </th>

                  <th>STUDENT</th>

                  {{-- ✅ NEW COLUMN --}}
                  <th style="width:170px;">FOLDER</th>

                  <th>GAME</th>
                  <th style="width:110px;">ATTEMPT</th>
                  <th style="width:140px;">SCORE</th>
                  <th style="width:120px;">%</th>

                  {{-- ✅ NEW COLUMN --}}
                  <th style="width:140px;">PUBLISH STATUS</th>

                  <th style="width:170px;">SUBMITTED</th>
                  <th class="text-end" style="width:112px;">ACTIONS</th>
                </tr>
              </thead>
              <tbody id="rows-unpublished">
                <tr id="loaderRow-unpublished" style="display:none;">
                  <td colspan="10" class="p-0">
                    <div class="p-4">
                      <div class="placeholder-wave">
                        <div class="placeholder col-12 mb-2" style="height:18px;"></div>
                        <div class="placeholder col-12 mb-2" style="height:18px;"></div>
                      </div>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div id="empty-unpublished" class="empty p-4 text-center" style="display:none;">
            <i class="fa fa-eye-slash mb-2" style="font-size:32px; opacity:.6;"></i>
            <div>No unpublished results.</div>
          </div>

          <div class="d-flex flex-wrap align-items-center justify-content-between p-3 gap-2">
            <div class="text-muted small" id="metaTxt-unpublished">—</div>
            <nav style="position:relative; z-index:1;"><ul id="pager-unpublished" class="pagination mb-0"></ul></nav>
          </div>
        </div>
      </div>
    </div>

  </div><!-- /.tab-content -->

</div>

{{-- Filter Modal --}}
<div class="modal fade" id="filterModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa fa-filter me-2"></i>Filter Results</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <div class="row g-2">
          <div class="col-12">
            <label class="form-label">Bubble Game (Select)</label>
            <select id="fGameId" class="form-select">
              <option value="">All games</option>
              {{-- Games will be loaded dynamically --}}
            </select>
          </div>
        <div class="col-12">
  <label class="form-label">Folder name</label>
  <select id="fFolderName" class="form-select">
    <option value="">All folders</option>
    {{-- folders loaded dynamically --}}
  </select>
</div>


          <div class="col-12">
            <label class="form-label">Attempt status</label>
            <select id="fAttemptStatus" class="form-select">
              <option value="">All</option>
              <option value="submitted">Submitted</option>
              <option value="auto_submitted">Auto submitted</option>
              <option value="in_progress">In progress</option>
            </select>
          </div>

          <div class="col-12">
            <label class="form-label">Published to student</label>
            <select id="fPublish" class="form-select">
              <option value="">All</option>
              <option value="1">Published</option>
              <option value="0">Not published</option>
            </select>
          </div>

          <div class="col-6">
            <label class="form-label">Min %</label>
            <input id="fMinPct" type="number" class="form-control" placeholder="0" min="0" max="100">
          </div>
          <div class="col-6">
            <label class="form-label">Max %</label>
            <input id="fMaxPct" type="number" class="form-control" placeholder="100" min="0" max="100">
          </div>

          <div class="col-6">
            <label class="form-label">From (YYYY-MM-DD)</label>
            <input id="fFrom" type="date" class="form-control">
          </div>
          <div class="col-6">
            <label class="form-label">To (YYYY-MM-DD)</label>
            <input id="fTo" type="date" class="form-control">
          </div>

          <div class="col-12">
            <label class="form-label">Game UUID (optional)</label>
            <input id="fGameUuid" type="text" class="form-control" placeholder="e.g. 6d2f...">
          </div>

          <div class="col-12">
            <label class="form-label">Student email (optional)</label>
            <input id="fStudentEmail" type="text" class="form-control" placeholder="student@example.com">
          </div>

        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button id="btnApplyFilters" type="button" class="btn btn-primary">
          <i class="fa fa-check me-1"></i>Apply Filters
        </button>
      </div>
    </div>
  </div>
</div>

{{-- ✅ Individual Publish Modal (same as quiz results page) --}}
<div class="modal fade" id="publishModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="fa fa-eye me-2"></i>Publish Result to Student
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="small text-muted">Result UUID</div>
        <div class="fw-semibold mb-2" id="pm_uuid">—</div>

        <div class="row g-2">
          <div class="col-12">
            <div class="small text-muted">Student</div>
            <div class="fw-semibold" id="pm_student">—</div>
            <div class="text-muted small" id="pm_email">—</div>
          </div>

          <div class="col-12">
            <div class="small text-muted">Game</div>
            <div class="fw-semibold" id="pm_game">—</div>
          </div>

          <div class="col-6">
            <div class="small text-muted">Attempt</div>
            <div class="fw-semibold" id="pm_attempt">—</div>
          </div>

          <div class="col-6">
            <div class="small text-muted">Score</div>
            <div class="fw-semibold" id="pm_score">—</div>
          </div>

          <div class="col-12 mt-2">
            <div class="d-flex align-items-center justify-content-between gap-2 p-2 rounded-3"
                 style="border:1px solid var(--line-strong);background:color-mix(in oklab,var(--muted-color) 8%,transparent);">
              <div>
                <div class="fw-semibold">Publish to student</div>
                <div class="text-muted small">If ON, student can view this result.</div>
              </div>
              <div class="form-check form-switch m-0">
                <input id="pm_toggle" class="form-check-input" type="checkbox">
              </div>
            </div>
          </div>

        </div>

        <input type="hidden" id="pm_result_id" value="">
      </div>

      <div class="modal-footer">
        <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button id="pm_save" class="btn btn-primary">
          <i class="fa fa-check me-1"></i>Save
        </button>
      </div>
    </div>
  </div>
</div>

{{-- ✅ Bulk Publish Modal (filter-only like quiz results page) --}}
<div class="modal fade" id="bulkPublishModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header align-items-start">
        <div>
          <h5 class="modal-title">
            <i class="fa fa-bullhorn me-2"></i>Bulk Publish (Select Students)
          </h5>
          <div class="text-muted small">
            Apply filters → table will show checkboxes → select students → click Publish/Unpublish.
          </div>
        </div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="row g-2">

          <div class="col-12">
            <label class="form-label">Bubble Game</label>
            <select id="bGameId" class="form-select">
              <option value="">Select game</option>
            </select>
          </div>
        <div class="col-12">
  <label class="form-label">Folder name</label>
  <select id="bFolderName" class="form-select">
    <option value="">All folders</option>
  </select>
</div>

          <div class="col-12 d-none">
            <label class="form-label">Attempt status</label>
            <select id="bAttemptStatus" class="form-select">
              <option value="">All</option>
              <option value="submitted">Submitted</option>
              <option value="auto_submitted">Auto submitted</option>
              <option value="in_progress">In progress</option>
            </select>
          </div>

          {{-- ✅ Publish Status filter (Yes/No) --}}
          <div class="col-12">
            <label class="form-label">Publish status</label>
            <select id="bPublish" class="form-select">
              <option value="">All</option>
              <option value="1">Yes (Published)</option>
              <option value="0">No (Not published)</option>
            </select>
          </div>

          <div class="col-6">
            <label class="form-label">From</label>
            <input id="bFrom" type="date" class="form-control">
          </div>
          <div class="col-6">
            <label class="form-label">To</label>
            <input id="bTo" type="date" class="form-control">
          </div>

          <div class="col-12 mt-2">
            <div class="p-3 rounded-3"
                 style="border:1px solid var(--line-strong);background:color-mix(in oklab,var(--muted-color) 8%,transparent);">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <div class="fw-semibold">Matching Results Count</div>
                  <div class="text-muted small">These results will be listed for selection.</div>
                </div>
                <div class="fw-semibold" style="font-size:20px;">
                  <span id="bm_count">—</span>
                </div>
              </div>
              <div class="text-muted small mt-2">
                <i class="fa fa-circle-info me-1"></i>
                For safety, please select at least one: <b>Game</b> or <b>Date Range</b> or <b>Attempt status</b> or <b>Publish status</b>.
              </div>
            </div>
          </div>

        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button id="bm_run" class="btn btn-primary">
          <i class="fa fa-check me-1"></i>Apply Bulk Filters
        </button>
      </div>
    </div>
  </div>
</div>

{{-- Toasts --}}
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:2100">
  <div id="okToast" class="toast text-bg-success border-0">
    <div class="d-flex">
      <div id="okMsg" class="toast-body">Done</div>
      <button class="btn-close btn-close-white m-auto me-2" data-bs-dismiss="toast"></button>
    </div>
  </div>
  <div id="errToast" class="toast text-bg-danger border-0 mt-2">
    <div class="d-flex">
      <div id="errMsg" class="toast-body">Something went wrong</div>
      <button class="btn-close btn-close-white m-auto me-2" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
/* ===== Force dropdown overflows to body (portal) ===== */
(function(){
  let activePortal = null;
  const placeMenu = (menu, btnRect) => {
    const vw = Math.max(document.documentElement.clientWidth, window.innerWidth || 0);
    const spaceRight = vw - btnRect.right;

    menu.classList.add('dd-portal');
    menu.style.display = 'block';
    menu.style.visibility = 'hidden';
    document.body.appendChild(menu);

    const mw = menu.offsetWidth, mh = menu.offsetHeight;
    let left = btnRect.left;
    if (spaceRight < mw && btnRect.right - mw > 8) left = btnRect.right - mw;

    let top = btnRect.bottom + 4;
    const vh = Math.max(document.documentElement.clientHeight, window.innerHeight || 0);
    if (top + mh > vh - 8) top = Math.max(8, vh - mh - 8);

    menu.style.left = left + 'px';
    menu.style.top  = top + 'px';
    menu.style.visibility = 'visible';
  };

  document.addEventListener('show.bs.dropdown', function(ev){
    const toggle = ev.target;
    const btn  = toggle.querySelector('.dd-toggle, [data-bs-toggle="dropdown"]');
    const menu = toggle.querySelector('.dropdown-menu');
    if (!btn || !menu) return;

    if (activePortal && activePortal.menu && activePortal.menu.isConnected) {
      activePortal.menu.classList.remove('dd-portal');
      activePortal.parent.appendChild(activePortal.menu);
      activePortal = null;
    }

    const rect = btn.getBoundingClientRect();
    menu.__ddParent = menu.parentElement;
    placeMenu(menu, rect);
    activePortal = { menu: menu, parent: menu.__ddParent };

    const closeOnEnv = () => { try { bootstrap.Dropdown.getOrCreateInstance(btn).hide(); } catch {} };
    menu.__ddListeners = [
      ['scroll', closeOnEnv, true],
      ['resize', closeOnEnv, false]
    ];
    window.addEventListener('resize', closeOnEnv);
    document.addEventListener('scroll', closeOnEnv, true);
  });

  document.addEventListener('hidden.bs.dropdown', function(ev){
    const toggle = ev.target;
    const menu = toggle.querySelector('.dropdown-menu.dd-portal') || activePortal?.menu;
    if (!menu) return;

    if (menu.__ddListeners) {
      document.removeEventListener('scroll', menu.__ddListeners[0][1], true);
      window.removeEventListener('resize', menu.__ddListeners[1][1]);
      menu.__ddListeners = null;
    }

    if (menu.__ddParent) {
      menu.classList.remove('dd-portal');
      menu.style.cssText = '';
      menu.__ddParent.appendChild(menu);
      activePortal = null;
    }
  });
})();

/* ================= Dropdown toggle handler ================= */
document.addEventListener('click', (e) => {
  const btn = e.target.closest('.dd-toggle');
  if (!btn) return;
  e.preventDefault(); e.stopPropagation();
  const inst = bootstrap.Dropdown.getOrCreateInstance(btn, { autoClose:'outside', boundary:'viewport' });
  inst.toggle();
});

(function(){
  const TOKEN = localStorage.getItem('token') || sessionStorage.getItem('token') || '';
  if (!TOKEN){
    Swal.fire('Login needed','Your session expired. Please login again.','warning')
      .then(()=> location.href='/');
    return;
  }

  /* ========= Toasts ========= */
  const okToast  = new bootstrap.Toast(document.getElementById('okToast'));
  const errToast = new bootstrap.Toast(document.getElementById('errToast'));
  const ok  = (m)=>{ document.getElementById('okMsg').textContent  = m||'Done'; okToast.show(); };
  const err = (m)=>{ document.getElementById('errMsg').textContent = m||'Something went wrong'; errToast.show(); };

  /* ========= DOM ========= */
  const wrapEl = document.getElementById('qrWrap');

  const q = document.getElementById('q');
  const perPageSel = document.getElementById('per_page');
  const btnReset = document.getElementById('btnReset');
  const btnApplyFilters = document.getElementById('btnApplyFilters');
  const btnBulkPublish = document.getElementById('btnBulkPublish');
  const btnExport = document.getElementById('btnExport');

  const fGameId = document.getElementById('fGameId');
  const fAttemptStatus = document.getElementById('fAttemptStatus');
  const fPublish = document.getElementById('fPublish');
  const fMinPct = document.getElementById('fMinPct');
  const fMaxPct = document.getElementById('fMaxPct');
  const fFrom = document.getElementById('fFrom');
  const fTo = document.getElementById('fTo');
  const fGameUuid = document.getElementById('fGameUuid');
  const fStudentEmail = document.getElementById('fStudentEmail');
  const fFolderName = document.getElementById('fFolderName');

  // Publish modal
  const pm = {
    el: document.getElementById('publishModal'),
    uuid: document.getElementById('pm_uuid'),
    student: document.getElementById('pm_student'),
    email: document.getElementById('pm_email'),
    game: document.getElementById('pm_game'),
    attempt: document.getElementById('pm_attempt'),
    score: document.getElementById('pm_score'),
    toggle: document.getElementById('pm_toggle'),
    id: document.getElementById('pm_result_id'),
    save: document.getElementById('pm_save'),
  };

  // Bulk modal (filter-only)
  const bm = {
    el: document.getElementById('bulkPublishModal'),
    game: document.getElementById('bGameId'),
    status: document.getElementById('bAttemptStatus'),
    publish: document.getElementById('bPublish'),
    from: document.getElementById('bFrom'),
    to: document.getElementById('bTo'),
    count: document.getElementById('bm_count'),
    run: document.getElementById('bm_run'),
    folder: document.getElementById('bFolderName'),

  };

  const tabs = {
    results:     { rows:'#rows-results',     loader:'#loaderRow-results',     empty:'#empty-results',     meta:'#metaTxt-results',     pager:'#pager-results',     extra:{} },
    published:   { rows:'#rows-published',   loader:'#loaderRow-published',   empty:'#empty-published',   meta:'#metaTxt-published',   pager:'#pager-published',   extra:{ publish_to_student:'1' } },
    unpublished: { rows:'#rows-unpublished', loader:'#loaderRow-unpublished', empty:'#empty-unpublished', meta:'#metaTxt-unpublished', pager:'#pager-unpublished', extra:{ publish_to_student:'0' } },
  };

  const qs=(sel)=>document.querySelector(sel);
  const qsa=(sel)=>document.querySelectorAll(sel);
  const esc=(s)=>{const m={'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;','`':'&#96;'}; return (s==null?'':String(s)).replace(/[&<>"'`]/g,ch=>m[ch]); };
  const fmtDate=(iso)=>{ if(!iso) return '-'; const d=new Date(iso); if(isNaN(d)) return esc(iso); return d.toLocaleString(undefined,{year:'numeric',month:'short',day:'2-digit',hour:'2-digit',minute:'2-digit'}); };
  const showLoader=(scope, v)=>{ qs(tabs[scope].loader).style.display = v ? '' : 'none'; };

  const fmtPct = (n)=> (n==null || n==='') ? '—' : (Number(n).toFixed(2) + '%');

  // ✅ FIX: robust "publish_to_student" normalizer
  const pubVal = (v) => {
    if (v === 1 || v === true) return 1;
    const s = String(v ?? '').trim().toLowerCase();
    return (s === '1' || s === 'true' || s === 'yes') ? 1 : 0;
  };

  function statusBadge(s){
    const v = String(s||'').toLowerCase();
    if (v==='submitted') return `<span class="badge badge-success text-uppercase">submitted</span>`;
    if (v==='auto_submitted') return `<span class="badge badge-warning text-uppercase">auto</span>`;
    if (v==='in_progress') return `<span class="badge badge-secondary text-uppercase">in progress</span>`;
    return `<span class="badge badge-secondary text-uppercase">${esc(s||'-')}</span>`;
  }

  function publishStatusBadge(isPub){
    const yes = pubVal(isPub) === 1;
    return yes
      ? `<span class="badge badge-success text-uppercase">Yes</span>`
      : `<span class="badge badge-danger text-uppercase">No</span>`;
  }

  function folderBadge(item){
    const student = item?.student || {};
    const name =
      student.user_folder_name ||
      student.folder_title ||
      student.folder_name ||
      student.folder_group_name ||
      student.folder_group ||
      student.folder ||
      student.user_folder?.title ||
      student.user_folder?.name ||
      '';
    if (!name) return `<span class="text-muted small">—</span>`;
    return `<span class="small">${esc(name)}</span>`;
  }

  function actionMenu(item){
    const result = item?.result || {};
    const student = item?.student || {};
    const game = item?.game || item?.bubble_game || {};

    const rid = result?.uuid ?? '';
    const ridId = result?.id ?? result?.result_id ?? '';

    // ✅ FIX: publish flag can exist in different places
    const isPub = pubVal(
      result?.publish_to_student ??
      item?.publish_to_student ??
      item?.published_to_student ??
      0
    ) === 1;

    // ✅ bubble result view route
    const viewUrl = rid ? `/test/results/${encodeURIComponent(rid)}/view` : '#';

    const pubTxt = isPub ? 'Unpublish from Student' : 'Publish to Student';
    const pubIcon = isPub ? 'fa-eye-slash' : 'fa-eye';

    return `
      <div class="dropdown text-end" data-bs-display="static">
        <button type="button" class="btn btn-light btn-sm dd-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="Actions">
          <i class="fa fa-ellipsis-vertical"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li>
            <a class="dropdown-item" href="${viewUrl}">
              <i class="fa fa-eye"></i> View Result
            </a>
          </li>

          <li>
            <button class="dropdown-item"
              data-act="publish"
              data-result-id="${esc(ridId)}"
              data-result-uuid="${esc(rid)}"
              data-publish="${isPub ? '1' : '0'}"
              data-student="${esc(student?.name||'-')}"
              data-email="${esc(student?.email||'-')}"
              data-game="${esc(game?.title || game?.game_title || game?.name || '-')}"
              data-attempt="${esc(result?.attempt_no ?? result?.attempt_number ?? 0)}"
              data-score="${esc(result?.score ?? 0)}"
            >
              <i class="fa ${pubIcon}"></i> ${pubTxt}
            </button>
          </li>

          <li>
            <button class="dropdown-item" data-act="copy" data-id="${esc(rid)}">
              <i class="fa fa-copy"></i> Copy Result UUID
            </button>
          </li>
        </ul>
      </div>
    `;
  }

  function rowHTML(scope, item){
    const r = item || {};
    const student = r.student || {};
    const game = r.game || r.bubble_game || {};
    const attempt = r.attempt || {};
    const result = r.result || {};

    const ridId = result?.id ?? result?.result_id ?? '';
    const ridUuid = result?.uuid ?? '';

    // ✅ FIX: robust publish flag
    const isPub = pubVal(
      result?.publish_to_student ??
      r?.publish_to_student ??
      r?.published_to_student ??
      0
    ) === 1;

    const tr = document.createElement('tr');

    tr.innerHTML = `
      <td class="chkcell bulk-col">
        <input class="form-check-input chk-row" type="checkbox"
          data-scope="${esc(scope)}"
          data-id="${esc(ridId||'')}"
          data-uuid="${esc(ridUuid||'')}"
          data-pub="${isPub ? '1' : '0'}"
        >
      </td>

      <td>
        <div class="fw-semibold">${esc(student.name||'-')}</div>
        <div class="text-muted small">${esc(student.email||'-')}</div>
      </td>

      <td>${folderBadge(r)}</td>

      <td>
        <div class="fw-semibold">${esc(game.title || game.game_title || game.name || '-')}</div>
      </td>

      <td>
        <span class="badge-pill"><i class="fa fa-repeat"></i> #${Number(result.attempt_no||result.attempt_number||0)}</span>
      </td>

      <td>
        <div class="fw-semibold">${Number(result.score||0)}</div>
      </td>

      <td>
        <div class="fw-semibold">${fmtPct(result.accuracy ?? result.percentage)}</div>
      </td>

      <td>${publishStatusBadge(result.publish_to_student ?? r.publish_to_student)}</td>

      ${scope==='results' ? `<td>${statusBadge(attempt.status)}</td>` : ``}

      <td>${fmtDate(result.created_at || result.result_created_at)}</td>

      <td class="text-end">${actionMenu(r)}</td>
    `;

    return tr;
  }

  /* ========= State ========= */
  let sort = '-result_created_at';
  const state = { results:{page:1}, published:{page:1}, unpublished:{page:1} };
  const loadedOnce = { results:false, published:false, unpublished:false };

  // ✅ results endpoint + fallback
  let RESULT_LIST_ENDPOINT = '/api/bubble-game-results/all';
  const fallbackResultEndpoint = '/api/bubble-game-results';
const BUBBLE_EXPORT_API = '/api/bubble-game/result/export';

  function getActiveScope(){
    const active = document.querySelector('.tab-pane.active');
    if (!active) return 'results';
    if (active.id === 'tab-published') return 'published';
    if (active.id === 'tab-unpublished') return 'unpublished';
    return 'results';
  }

  async function fetchJson(url, opts = {}){
    const res = await fetch(url, {
      ...opts,
      headers: {
        'Authorization': 'Bearer ' + TOKEN,
        'Accept': 'application/json',
        ...(opts.headers || {})
      }
    });
    const json = await res.json().catch(()=> ({}));
    return { res, json };
  }

  /* ============================================================
   * ✅ BULK SELECTION MODE (same as quiz results page)
   * ============================================================ */
  const bulk = {
    mode: false,
    filtersActive: false,
filters: { game_id:'', attempt_status:'', publish_to_student:'', from:'', to:'', folder_id:'' },
    selected: new Map(), // id -> {pub, uuid}
  };

  function setBulkMode(on){
    bulk.mode = !!on;
    wrapEl.classList.toggle('bulk-mode', bulk.mode);

    ['results','published','unpublished'].forEach(sc=>{
      const h = document.getElementById(`chkAll-${sc}`);
      if (h){
        h.checked = false;
        h.indeterminate = false;
      }
    });

    updateBulkButton();
  }

  function computeBulkButtonState(){
    if (!bulk.mode) return { label:'Bulk Publish', icon:'fa-bullhorn' };
    if (bulk.selected.size === 0) return { label:'Publish', icon:'fa-eye' };

    const vals = Array.from(bulk.selected.values()).map(v => Number(v?.pub||0));
    const allPublished = vals.length>0 && vals.every(v => v===1);
    if (allPublished) return { label:'Unpublish', icon:'fa-eye-slash' };
    return { label:'Publish', icon:'fa-eye' };
  }

  function updateBulkButton(){
    const st = computeBulkButtonState();
    btnBulkPublish.innerHTML = `<i class="fa ${st.icon} me-1"></i>${st.label}`;
  }

  function clearBulkSelection(){
    bulk.selected.clear();
    updateBulkButton();
    document.querySelectorAll('input.chk-row').forEach(c => c.checked = false);

    ['results','published','unpublished'].forEach(sc=>{
      const h = document.getElementById(`chkAll-${sc}`);
      if (h){
        h.checked = false;
        h.indeterminate = false;
      }
    });
  }

  function syncHeaderCheckbox(scope){
    const header = document.getElementById(`chkAll-${scope}`);
    if (!header) return;

    const rows = Array.from(document.querySelectorAll(`input.chk-row[data-scope="${scope}"]`));
    if (!rows.length){
      header.checked = false;
      header.indeterminate = false;
      return;
    }
    const checkedCount = rows.filter(r => r.checked).length;
    header.checked = checkedCount === rows.length;
    header.indeterminate = checkedCount > 0 && checkedCount < rows.length;
  }

  function applyBulkCheckedOnRender(scope){
    if (!bulk.mode) return;

    const rows = document.querySelectorAll(`input.chk-row[data-scope="${scope}"]`);
    rows.forEach(chk=>{
      const id = chk.dataset.id || '';
      chk.checked = bulk.selected.has(id);
    });
    syncHeaderCheckbox(scope);
    updateBulkButton();
  }

  // ✅ publish/unpublish patch with multi-endpoint + multi-method fallbacks
async function patchPublishAny(resultId, resultUuid, publishVal){
  const payload = { publish_to_student: Number(publishVal) };
  const tries = [];

  // ✅ Prefer UUID first (more reliable)
  if (resultUuid) {
    tries.push(`/api/bubble-game-results/${encodeURIComponent(resultUuid)}/publish`);
    tries.push(`/api/bubble-game-result/${encodeURIComponent(resultUuid)}/publish`);
    tries.push(`/api/exam/result/${encodeURIComponent(resultUuid)}/publish`);
  }
  if (resultId) {
    tries.push(`/api/bubble-game-results/${encodeURIComponent(resultId)}/publish`);
    tries.push(`/api/bubble-game-result/${encodeURIComponent(resultId)}/publish`);
    tries.push(`/api/exam/result/${encodeURIComponent(resultId)}/publish`);
  }

  let lastErr = null;

  // ✅ Try PATCH → if 405 then try POST → then PUT
  const methods = ['PATCH','POST','PUT'];

  for (const url of tries){
    for (const method of methods){
      try{
        const { res, json } = await fetchJson(url, {
          method,
          headers:{ 'Content-Type':'application/json' },
          body: JSON.stringify(payload),
        });

        if (res.ok) return json;

        // ✅ continue fallback if endpoint missing OR method not allowed
        if (res.status === 404 || res.status === 405) {
          lastErr = new Error(json?.message || 'Not found / Method not allowed');
          continue;
        }

        throw new Error(json?.message || 'Publish update failed');
      }catch(e){
        lastErr = e;
      }
    }
  }

  throw (lastErr || new Error('Publish update failed'));
}

/* ============================================================
 * ✅ EXPORT (CSV Download with Bearer Token)
 * ============================================================ */
/* ============================================================
 * ✅ EXPORT (CSV Download with Bearer Token) — FIXED
 *   ✅ Uses folder_id (not folder_name) so filtered export works
 * ============================================================ */
function buildExportParams(scope){
  const usp = new URLSearchParams();

  // ✅ Export always from page 1 with very high limit
  usp.set('page', '1');
  usp.set('per_page', '100000');
  usp.set('sort', sort);

  if (q && q.value.trim()) usp.set('q', q.value.trim());

  // Normal filters
  if (fGameId && fGameId.value) usp.set('bubble_game_id', fGameId.value);
  if (fAttemptStatus && fAttemptStatus.value) usp.set('attempt_status', fAttemptStatus.value);
  if (fPublish && fPublish.value !== '') usp.set('publish_to_student', fPublish.value);

  if (fMinPct && fMinPct.value !== '') usp.set('min_percentage', fMinPct.value);
  if (fMaxPct && fMaxPct.value !== '') usp.set('max_percentage', fMaxPct.value);

  if (fFrom && fFrom.value) usp.set('from', fFrom.value);
  if (fTo && fTo.value) usp.set('to', fTo.value);

  if (fGameUuid && fGameUuid.value.trim()) usp.set('game_uuid', fGameUuid.value.trim());
  if (fStudentEmail && fStudentEmail.value.trim()) usp.set('student_email', fStudentEmail.value.trim());

  // ✅ ✅ FIX: Folder dropdown value is folder_id (numeric)
  if (fFolderName && fFolderName.value) usp.set('folder_id', fFolderName.value);

  // ✅ If bulk mode active, export that filtered selection list
  if (bulk.mode && bulk.filtersActive){
    if (bulk.filters.game_id) usp.set('bubble_game_id', bulk.filters.game_id);
    if (bulk.filters.attempt_status) usp.set('attempt_status', bulk.filters.attempt_status);
    if (bulk.filters.publish_to_student !== '') usp.set('publish_to_student', bulk.filters.publish_to_student);
    if (bulk.filters.from) usp.set('from', bulk.filters.from);
    if (bulk.filters.to) usp.set('to', bulk.filters.to);

    // ✅ ✅ FIX: bulk folder should be folder_id also
    if (bulk.filters.folder_id) usp.set('folder_id', bulk.filters.folder_id);
  }

  // tab specific (published/unpublished)
  const extra = tabs[scope]?.extra || {};
  Object.keys(extra).forEach(k => usp.set(k, extra[k]));

  return usp.toString();
}

async function downloadExportCSV(){
  const scope = getActiveScope();
  const url = `${BUBBLE_EXPORT_API}?${buildExportParams(scope)}`;

  const oldHtml = btnExport?.innerHTML || '';
  if (btnExport){
    btnExport.disabled = true;
    btnExport.innerHTML = `<i class="fa fa-spinner fa-spin me-1"></i>Exporting...`;
  }

  try{
    const res = await fetch(url, {
      method: 'GET',
      headers: {
        'Authorization': 'Bearer ' + TOKEN,
        'Accept': 'text/csv'
      }
    });

    // If API returned json error
    if (!res.ok){
      const j = await res.json().catch(()=> ({}));
      throw new Error(j?.message || `Export failed (${res.status})`);
    }

    const blob = await res.blob();

    // ✅ filename from header if provided
    const cd = res.headers.get('Content-Disposition') || '';
    let filename = `bubble_game_results_${new Date().toISOString().slice(0,19).replace(/[:T]/g,'-')}.csv`;
    const m1 = /filename\*=UTF-8''([^;]+)/i.exec(cd);
    const m2 = /filename="?([^"]+)"?/i.exec(cd);
    if (m1?.[1]) filename = decodeURIComponent(m1[1]);
    else if (m2?.[1]) filename = m2[1];

    const a = document.createElement('a');
    const href = URL.createObjectURL(blob);
    a.href = href;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();

    setTimeout(()=> URL.revokeObjectURL(href), 1200);

    ok('Export downloaded');
  }catch(e){
    console.error(e);
    err(e.message || 'Export failed');
  }finally{
    if (btnExport){
      btnExport.disabled = false;
      btnExport.innerHTML = oldHtml || `<i class="fa fa-file-export me-1"></i>Export`;
    }
  }
}

btnExport?.addEventListener('click', (e)=>{
  e.preventDefault();
  downloadExportCSV();
});

  async function runBulkAction(){
    if (!bulk.mode) return;

    if (bulk.selected.size === 0){
      err('Select at least 1 student result');
      return;
    }

    const vals = Array.from(bulk.selected.values()).map(v => Number(v?.pub||0));
    const allPublished = vals.length>0 && vals.every(v => v===1);
    const publishVal = allPublished ? 0 : 1;

    const ids = Array.from(bulk.selected.keys());
    btnBulkPublish.disabled = true;

    let success = 0, failed = 0;

    const limit = 6;
    let idx = 0;
    const workers = Array.from({length: limit}).map(async ()=>{
      while (idx < ids.length){
        const my = ids[idx++];
        const meta = bulk.selected.get(my) || {};
        try{
          await patchPublishAny(my, meta.uuid || '', publishVal);
          success++;
        }catch(e){
          failed++;
          console.error(e);
        }
      }
    });

    try{
      await Promise.all(workers);

      ok(publishVal ? `Published ${success}/${ids.length}` : `Unpublished ${success}/${ids.length}`);
      if (failed>0) err(`${failed} failed (check console)`);

      await load(getActiveScope());
      if (loadedOnce.published) await load('published');
      if (loadedOnce.unpublished) await load('unpublished');

      // ✅ exit bulk mode after action
      bulk.filtersActive = false;
bulk.filters = { game_id:'', attempt_status:'', publish_to_student:'', from:'', to:'', folder_id:'' };

      clearBulkSelection();
      setBulkMode(false);

    }finally{
      btnBulkPublish.disabled = false;
      updateBulkButton();
    }
  }
  async function loadFoldersForFilter() {
  try {
    // ✅ try multiple endpoints (fallback-safe)
    const endpoints = [
      '/api/user-folders?per_page=500',
      '/api/user-folders/all',
      '/api/folders?per_page=500',
      '/api/folders/all',
      '/api/student-folders?per_page=500',
    ];

    let folders = [];
    for (const ep of endpoints) {
      const { res, json } = await fetchJson(ep, {});
      if (res.ok) {
        folders = (json?.data || json?.items || json?.folders || []);
        if (Array.isArray(folders) && folders.length) break;
      }
    }

    if (!Array.isArray(folders) || folders.length === 0) return;

    // ✅ normalize names (title/name/folder_name)
    const normalized = folders
  .map(f => ({
    id: f?.id ?? '',
    title: (f?.title || f?.name || f?.folder_name || '').trim()
  }))
  .filter(x => x.id && x.title);

// ✅ unique by id
const map = new Map();
normalized.forEach(x => map.set(String(x.id), x.title));

const finalList = Array.from(map.entries())
  .map(([id, title]) => ({ id, title }))
  .sort((a, b) => a.title.localeCompare(b.title));

const fill = (sel) => {
  if (!sel) return;
  while (sel.options.length > 1) sel.remove(1);

  finalList.forEach(f => {
    const opt = document.createElement('option');
    opt.value = f.id;          // ✅ NOW value = folder_id
    opt.textContent = f.title; // ✅ show title to user
    sel.appendChild(opt);
  });
};

fill(fFolderName);
fill(bm.folder);


  } catch (e) {
    console.error('Failed to load folders:', e);
  }
}


  /* ========= Load games for dropdown ========= */
  async function loadGamesForFilter() {
    try {
      // ✅ Change API if your endpoint differs
      const { res, json } = await fetchJson('/api/bubble-games?per_page=200&status=active', {});
      if (!res.ok) throw new Error(json?.message || 'Failed to load games');

      const games = json?.data || [];

      const fill = (sel) => {
        if (!sel) return;
        while (sel.options.length > 1) sel.remove(1);
        games.forEach(g => {
          const opt = document.createElement('option');
          opt.value = g.id ?? (g.uuid ?? '');
          opt.dataset.uuid = g.uuid ?? '';
          opt.textContent = (g.title || g.game_title || g.name || 'Unnamed Game');
          sel.appendChild(opt);
        });
      };

      fill(fGameId);
      fill(bm.game);

    } catch(e) {
      console.error('Failed to load games:', e);
    }
  }

  function buildParams(scope){
    const usp = new URLSearchParams();
    usp.set('page', state[scope].page || 1);
    usp.set('per_page', Number(perPageSel?.value || 20));
    usp.set('sort', sort);

    if (q && q.value.trim()) usp.set('q', q.value.trim());

    // Normal filters
    if (fGameId && fGameId.value) usp.set('bubble_game_id', fGameId.value);
    if (fAttemptStatus && fAttemptStatus.value) usp.set('attempt_status', fAttemptStatus.value);
    if (fPublish && fPublish.value !== '') usp.set('publish_to_student', fPublish.value);

    if (fMinPct && fMinPct.value !== '') usp.set('min_percentage', fMinPct.value);
    if (fMaxPct && fMaxPct.value !== '') usp.set('max_percentage', fMaxPct.value);

    if (fFrom && fFrom.value) usp.set('from', fFrom.value);
    if (fTo && fTo.value) usp.set('to', fTo.value);

    if (fGameUuid && fGameUuid.value.trim()) usp.set('game_uuid', fGameUuid.value.trim());
    if (fStudentEmail && fStudentEmail.value.trim()) usp.set('student_email', fStudentEmail.value.trim());
if (fFolderName && fFolderName.value) usp.set('folder_id', fFolderName.value);

    // ✅ Bulk filters override
    if (bulk.mode && bulk.filtersActive){
      if (bulk.filters.game_id) usp.set('bubble_game_id', bulk.filters.game_id);
      if (bulk.filters.attempt_status) usp.set('attempt_status', bulk.filters.attempt_status);
      if (bulk.filters.publish_to_student !== '') usp.set('publish_to_student', bulk.filters.publish_to_student);
      if (bulk.filters.from) usp.set('from', bulk.filters.from);
      if (bulk.filters.to) usp.set('to', bulk.filters.to);
if (bulk.filters.folder_id) usp.set('folder_id', bulk.filters.folder_id);

    }

    // tab-specific
    const extra = tabs[scope].extra || {};
    Object.keys(extra).forEach(k => usp.set(k, extra[k]));

    return usp.toString();
  }

  function urlFor(scope){
    return `${RESULT_LIST_ENDPOINT}?${buildParams(scope)}`;
  }

  async function load(scope){
    const refs = tabs[scope];
    const rowsEl = qs(refs.rows);
    const empty  = qs(refs.empty);
    const pager  = qs(refs.pager);
    const meta   = qs(refs.meta);

    rowsEl.querySelectorAll('tr:not([id^="loaderRow"])').forEach(n=>n.remove());
    empty.style.display='none';
    pager.innerHTML = '';
    meta.textContent = '—';
    showLoader(scope, true);

    try{
      let { res, json } = await fetchJson(urlFor(scope));
      if (res.status === 404 && RESULT_LIST_ENDPOINT.endsWith('/all')) {
        RESULT_LIST_ENDPOINT = fallbackResultEndpoint;
        ({ res, json } = await fetchJson(urlFor(scope)));
      }
      if (!res.ok) throw new Error(json?.message || 'Load failed');

      const items = json?.data || [];
      const pagination = json?.pagination || {page:1, per_page:20, total:items.length, total_pages:1};

      loadedOnce[scope] = true;

      if (items.length===0) empty.style.display='';

      const frag = document.createDocumentFragment();
      items.forEach(it => frag.appendChild(rowHTML(scope, it)));
      rowsEl.appendChild(frag);

      const total   = Number(pagination.total||0);
      const perPage = Number(pagination.per_page||20);
      const current = Number(pagination.page||1);
      const totalPages = Math.max(1, Number(pagination.total_pages || Math.ceil(total/perPage) || 1));

      function li(disabled, active, label, target){
        const cls=['page-item',disabled?'disabled':'',active?'active':''].filter(Boolean).join(' ');
        const href=disabled?'#':'javascript:void(0)';
        return `<li class="${cls}"><a class="page-link" href="${href}" data-page="${target||''}">${label}</a></li>`;
      }

      let html='';
      html += li(current<=1,false,'Previous',current-1);
      const w=3, start=Math.max(1,current-w), end=Math.min(totalPages,current+w);
      if (start>1){
        html += li(false,false,1,1);
        if(start>2) html+='<li class="page-item disabled"><span class="page-link">…</span></li>';
      }
      for(let p=start;p<=end;p++) html += li(false,p===current,p,p);
      if (end<totalPages){
        if(end<totalPages-1) html+='<li class="page-item disabled"><span class="page-link">…</span></li>';
        html+=li(false,false,totalPages,totalPages);
      }
      html += li(current>=totalPages,false,'Next',current+1);
      pager.innerHTML = html;

      pager.querySelectorAll('a.page-link[data-page]').forEach(a=>{
        a.addEventListener('click',()=>{
          const target=Number(a.dataset.page);
          if(!target || target===state[scope].page) return;
          state[scope].page = Math.max(1,target);
          load(scope);
          window.scrollTo({top:0,behavior:'smooth'});
        });
      });

      meta.textContent = `Showing page ${current} of ${totalPages} — ${total} result(s)`;

      // ✅ keep selections after reload
      applyBulkCheckedOnRender(scope);

    }catch(e){
      console.error(e);
      empty.style.display='';
      meta.textContent='Failed to load';
      err(e.message || 'Load error');
    }finally{
      showLoader(scope, false);
    }
  }

  /* ========= Sorting (main tab) ========= */
  qsa('#tab-results thead th.sortable').forEach(th=>{
    th.addEventListener('click', ()=>{
      const col = th.dataset.col;
      if (sort === col){ sort = '-'+col; }
      else if (sort === '-'+col){ sort = col; }
      else { sort = (col === 'result_created_at') ? '-result_created_at' : col; }

      state.results.page = 1;
      load('results');

      qsa('#tab-results thead th.sortable').forEach(t=>t.classList.remove('asc','desc'));
      if (sort === col) th.classList.add('asc');
      else if (sort === '-'+col) th.classList.add('desc');
    });
  });

  /* ========= Search debounce ========= */
  let srchT;
  q?.addEventListener('input', ()=>{
    clearTimeout(srchT);
    srchT=setTimeout(()=>{
      state.results.page=1;
      load('results');
    }, 350);
  });
/* ✅ MODAL BACKDROP FIX (Filter Modal) */
const filterModalEl = document.getElementById('filterModal');
const filterModalInst = filterModalEl ? bootstrap.Modal.getOrCreateInstance(filterModalEl) : null;

let pendingFilterReload = false;

function cleanupModalBackdrops(){
  setTimeout(()=>{
    if (!document.querySelector('.modal.show')) {
      document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
      document.body.classList.remove('modal-open');
      document.body.style.removeProperty('overflow');
      document.body.style.removeProperty('padding-right');
    }
  }, 50);
}

btnApplyFilters?.addEventListener('click', (e)=>{
  e.preventDefault();
  e.stopPropagation();

  pendingFilterReload = true;
  filterModalInst?.hide(); // ✅ hide first (bootstrap removes backdrop properly)
});

filterModalEl?.addEventListener('hidden.bs.modal', ()=>{
  if (pendingFilterReload){
    pendingFilterReload = false;
    Object.keys(state).forEach(k => state[k].page = 1);
    load('results'); // ✅ load AFTER modal fully closes
  }
  cleanupModalBackdrops(); // ✅ failsafe cleanup
});


  // ✅ Reset exits bulk mode
  btnReset?.addEventListener('click', ()=>{
    if (q) q.value='';
    if (perPageSel) perPageSel.value='20';
    if (fGameId) fGameId.value='';
    if (fAttemptStatus) fAttemptStatus.value='';
    if (fPublish) fPublish.value='';
    if (fMinPct) fMinPct.value='';
    if (fMaxPct) fMaxPct.value='';
    if (fFrom) fFrom.value='';
    if (fTo) fTo.value='';
    if (fGameUuid) fGameUuid.value='';
    if (fStudentEmail) fStudentEmail.value='';
    if (fFolderName) fFolderName.value = '';

    sort='-result_created_at';

    // exit bulk mode
    bulk.filtersActive = false;
    bulk.filters = { game_id:'', attempt_status:'', publish_to_student:'', from:'', to:'' };
    clearBulkSelection();
    setBulkMode(false);

    Object.keys(state).forEach(k => state[k].page = 1);
    load('results');
  });

  perPageSel?.addEventListener('change', ()=>{
    Object.keys(state).forEach(k => state[k].page = 1);
    load('results');
  });

  /* ========= Tabs load on demand ========= */
  // ✅ FIX: safe optional chaining so no crash
  document.querySelector('a[href="#tab-results"]')?.addEventListener('shown.bs.tab', ()=> load('results'));
  document.querySelector('a[href="#tab-published"]')?.addEventListener('shown.bs.tab', ()=> load('published'));
  document.querySelector('a[href="#tab-unpublished"]')?.addEventListener('shown.bs.tab', ()=> load('unpublished'));

  /* ========= Copy UUID ========= */
  document.addEventListener('click', async (e)=>{
    const btn = e.target.closest('button.dropdown-item[data-act="copy"]');
    if(!btn) return;
    const id = btn.dataset.id || '';
    if(!id) return;
    try{
      await navigator.clipboard.writeText(id);
      ok('Copied result uuid');
    }catch{
      ok('Copy: '+id);
    }
  });

  /* ========= Page Select All (bulk-mode only) ========= */
  function wireSelectAll(scope){
    const header = document.getElementById(`chkAll-${scope}`);
    if (!header) return;

    header.addEventListener('change', ()=>{
      if (!bulk.mode) {
        header.checked = false;
        header.indeterminate = false;
        return;
      }

      const checked = header.checked;
      document.querySelectorAll(`input.chk-row[data-scope="${scope}"]`).forEach(chk=>{
        chk.checked = checked;

        const id = chk.dataset.id || '';
        const uuid = chk.dataset.uuid || '';
        const pub = Number(chk.dataset.pub||0);

        if (!id) return;
        if (checked) bulk.selected.set(id, {pub, uuid});
        else bulk.selected.delete(id);
      });

      syncHeaderCheckbox(scope);
      updateBulkButton();
    });
  }
  wireSelectAll('results');
  wireSelectAll('published');
  wireSelectAll('unpublished');

  /* ========= Row checkbox selection (bulk-mode only) ========= */
  document.addEventListener('change', (e)=>{
    const chk = e.target.closest('input.chk-row');
    if (!chk) return;

    if (!bulk.mode){
      chk.checked = false;
      return;
    }

    const id = chk.dataset.id || '';
    const uuid = chk.dataset.uuid || '';
    const pub = Number(chk.dataset.pub||0);

    if (!id) return;

    if (chk.checked) bulk.selected.set(id, {pub, uuid});
    else bulk.selected.delete(id);

    syncHeaderCheckbox(chk.dataset.scope || 'results');
    updateBulkButton();
  });

  /* ========= Individual Publish Modal ========= */
  const publishModal = new bootstrap.Modal(pm.el);

  function openPublishModal(ds){
    pm.uuid.textContent = ds.resultUuid || '—';
    pm.student.textContent = ds.student || '—';
    pm.email.textContent = ds.email || '—';
    pm.game.textContent = ds.game || '—';
    pm.attempt.textContent = '#' + (ds.attempt || '0');
    pm.score.textContent = String(ds.score ?? '0');
    pm.toggle.checked = String(ds.publish||'0') === '1';
    pm.id.value = ds.resultId || '';
    publishModal.show();
  }

  document.addEventListener('click', (e)=>{
    const btn = e.target.closest('button.dropdown-item[data-act="publish"]');
    if(!btn) return;

    openPublishModal({
      resultId: btn.dataset.resultId || '',
      resultUuid: btn.dataset.resultUuid || '',
      publish: btn.dataset.publish || '0',
      student: btn.dataset.student || '',
      email: btn.dataset.email || '',
      game: btn.dataset.game || '',
      attempt: btn.dataset.attempt || '0',
      score: btn.dataset.score || '0',
    });
  });

  pm.save?.addEventListener('click', async ()=>{
    const rid = pm.id.value || '';
    if (!rid){
      err('Result id missing');
      return;
    }
    const publishVal = pm.toggle.checked ? 1 : 0;

    try{
      pm.save.disabled = true;
      await patchPublishAny(rid, pm.uuid.textContent?.trim() || '', publishVal);

      ok(publishVal ? 'Published to student' : 'Unpublished from student');
      publishModal.hide();

      await load(getActiveScope());
      if (loadedOnce.published) await load('published');
      if (loadedOnce.unpublished) await load('unpublished');

    }catch(e){
      console.error(e);
      err(e.message || 'Failed');
    }finally{
      pm.save.disabled = false;
    }
  });

  /* ========= Bulk Publish Modal (filter-only now) ========= */
  const bulkModal = new bootstrap.Modal(bm.el);

  function buildBulkCountParams(){
    const usp = new URLSearchParams();
    usp.set('page','1');
    usp.set('per_page','1');
    usp.set('sort','-result_created_at');

    if (bm.game?.value) usp.set('bubble_game_id', bm.game.value);
    if (bm.status?.value) usp.set('attempt_status', bm.status.value);
    if (bm.publish?.value !== '') usp.set('publish_to_student', bm.publish.value);
    if (bm.from?.value) usp.set('from', bm.from.value);
    if (bm.to?.value) usp.set('to', bm.to.value);
if (bm.folder?.value) usp.set('folder_id', bm.folder.value);

    if (q && q.value.trim()) usp.set('q', q.value.trim());
    return usp.toString();
  }

  async function refreshBulkCount(){
    bm.count.textContent = '…';

const hasSafe = !!(bm.game?.value || bm.status?.value || (bm.publish?.value !== '') || bm.from?.value || bm.to?.value || bm.folder?.value);
    if (!hasSafe){
      bm.count.textContent = '—';
      return;
    }

    try{
      let url = `${RESULT_LIST_ENDPOINT}?${buildBulkCountParams()}`;
      let { res, json } = await fetchJson(url);

      if (res.status === 404 && RESULT_LIST_ENDPOINT.endsWith('/all')) {
        RESULT_LIST_ENDPOINT = fallbackResultEndpoint;
        url = `${RESULT_LIST_ENDPOINT}?${buildBulkCountParams()}`;
        ({ res, json } = await fetchJson(url));
      }

      if (!res.ok) throw new Error(json?.message || 'Count failed');

      const total = Number(json?.pagination?.total ?? 0);
      bm.count.textContent = String(total);
    }catch(e){
      console.error(e);
      bm.count.textContent = '—';
    }
  }

  let bulkCountTimer = null;
  function scheduleBulkCount(){
    clearTimeout(bulkCountTimer);
    bulkCountTimer = setTimeout(refreshBulkCount, 250);
  }

  // ✅ Toolbar bulk button behavior:
  // - default: open bulk filter modal
  // - after bulk filter applied: becomes Publish/Unpublish action button
  btnBulkPublish?.addEventListener('click', ()=>{
    if (!bulk.mode){
      // prefill bulk filter from current filter
      if (bm.game && fGameId) bm.game.value = fGameId.value || '';
      if (bm.status && fAttemptStatus) bm.status.value = fAttemptStatus.value || '';
      if (bm.publish && fPublish) bm.publish.value = fPublish.value || '';
      if (bm.from && fFrom) bm.from.value = fFrom.value || '';
      if (bm.to && fTo) bm.to.value = fTo.value || '';
      if (bm.folder && fFolderName) bm.folder.value = fFolderName.value || '';

      bm.count.textContent = '—';
      bulkModal.show();
      scheduleBulkCount();
      return;
    }

    runBulkAction();
  });

  [bm.game, bm.status, bm.publish, bm.from, bm.to].forEach(el=>{
    if (!el) return;
    el.addEventListener('change', scheduleBulkCount);
    el.addEventListener('input', scheduleBulkCount);
  });

  bm.run?.addEventListener('click', async ()=>{
    const hasSafe = !!(bm.game?.value || bm.status?.value || (bm.publish?.value !== '') || bm.from?.value || bm.to?.value);
    if (!hasSafe){
      err('Please select at least Game OR Date Range OR Attempt status OR Publish status.');
      return;
    }

    bulk.filtersActive = true;
    bulk.filters = {
      game_id: bm.game?.value || '',
      attempt_status: bm.status?.value || '',
      publish_to_student: (bm.publish?.value !== '') ? bm.publish.value : '',
      from: bm.from?.value || '',
      to: bm.to?.value || '',
folder_id: bm.folder?.value || '',
    };

    clearBulkSelection();
    setBulkMode(true);

    bulkModal.hide();

    // move to Results tab for selection
    const tabResults = document.querySelector('a[href="#tab-results"]');
    if (tabResults){
      bootstrap.Tab.getOrCreateInstance(tabResults).show();
    }

    Object.keys(state).forEach(k => state[k].page = 1);

    await load('results');
    if (loadedOnce.published) await load('published');
    if (loadedOnce.unpublished) await load('unpublished');

    ok('Bulk selection enabled — select students & click Publish/Unpublish');
  });

  /* ========= Initial load ========= */
  setBulkMode(false);
Promise.all([ loadGamesForFilter(), loadFoldersForFilter() ])
  .finally(() => load('results'));

})();
</script>
@endpush
