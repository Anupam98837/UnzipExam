@extends('pages.users.layout.structure')

@push('styles')
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
  <link rel="stylesheet" href="{{ asset('assets/css/common/main.css') }}"/>

  <style>
    :root{
      --ink: #111827;
      --muted: #6b7280;
      --surface: #ffffff;
      --border: #e5e7eb;
      --primary: #4f46e5;
      --secondary: #0ea5e9;
      --danger: #ef4444;
      --success: #10b981;
      --warning: #f59e0b;
      --bg-gray: #f9fafb;
    }
    html.theme-dark :root{
      --surface: #1e293b;
      --border: #334155;
      --bg-gray: #0f172a;
      --ink: #f1f5f9;
    }

    body{
      background: var(--bg-gray);
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      margin: 0;
      padding: 0;
    }

    .container{
      max-width: 1400px;
      margin: 0 auto;
      padding: 20px;
    }

    /* Game Header */
    .game-header{
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 20px;
      margin-bottom: 24px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .game-header-top{
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      margin-bottom: 16px;
    }
    .game-chip{
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 4px 12px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      background: linear-gradient(135deg, #a5b4fc, #818cf8);
      color: white;
      margin-bottom: 8px;
    }
    .game-title{
      margin: 0 0 8px;
      font-size: 20px;
      font-weight: 700;
      color: var(--ink);
    }
    .game-desc{
      margin: 0;
      font-size: 14px;
      color: var(--muted);
      max-width: 600px;
    }
    .game-meta{
      display: flex;
      gap: 24px;
      flex-wrap: wrap;
    }
    .meta-item{
      text-align: center;
      min-width: 100px;
    }
    .meta-label{
      font-size: 12px;
      color: var(--muted);
      margin-bottom: 4px;
    }
    .meta-value{
      font-size: 16px;
      font-weight: 600;
      color: var(--ink);
    }

    /* Layout */
    .layout-grid{
      display: grid;
      grid-template-columns: 300px 1fr;
      gap: 20px;
      align-items: start;
    }
    @media (max-width: 1024px){
      .layout-grid{ grid-template-columns: 1fr; }
    }

    /* Sidebar */
    .sidebar{
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 12px;
      overflow: hidden;
      position: sticky;
      top: 20px;
      height: calc(100vh - 200px);
      display: flex;
      flex-direction: column;
    }
    .sidebar-header{
      padding: 16px;
      border-bottom: 1px solid var(--border);
      background: var(--bg-gray);
    }
    .sidebar-header h6{
      margin: 0;
      font-size: 14px;
      font-weight: 600;
      color: var(--ink);
    }
    .sidebar-actions{
      padding: 12px 16px;
      border-bottom: 1px solid var(--border);
      display: flex;
      gap: 8px;
    }
    .sidebar-search{
      flex: 1;
      position: relative;
    }
    .sidebar-search input{
      width: 100%;
      padding: 8px 12px 8px 36px;
      border: 1px solid var(--border);
      border-radius: 8px;
      font-size: 13px;
      background: var(--surface);
    }
    .sidebar-search .search-icon{
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--muted);
      font-size: 12px;
    }
    .sidebar-body{
      flex: 1;
      overflow-y: auto;
      padding: 8px 0;
    }

    /* Question List */
    .question-list{ padding: 0; margin: 0; list-style: none; }
    .question-item{
      padding: 12px 16px;
      border-bottom: 1px solid var(--border);
      cursor: pointer;
      transition: all 0.2s;
      display: flex;
      align-items: center;
      gap: 12px;
      position: relative;
    }
    .question-item:hover{ background: var(--bg-gray); }
    .question-item.active{
      background: #eef2ff;
      border-left: 3px solid var(--primary);
    }
    html.theme-dark .question-item.active{ background: #312e81; }
    .q-number{
      width: 28px;
      height: 28px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--bg-gray);
      border-radius: 6px;
      font-size: 12px;
      font-weight: 600;
      color: var(--muted);
      flex-shrink: 0;
    }
    .question-item.active .q-number{
      background: var(--primary);
      color: white;
    }
    .q-content{ flex: 1; min-width: 0; }
    .q-title{
      font-size: 13px;
      font-weight: 500;
      color: var(--ink);
      margin: 0 0 4px;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    .q-meta{
      display: flex;
      gap: 6px;
      align-items: center;
    }
    .q-badge{
      padding: 2px 6px;
      border-radius: 4px;
      font-size: 10px;
      font-weight: 600;
      white-space: nowrap;
    }
    .q-badge.bubbles{ background: #dbeafe; color: #1e40af; }
    .q-badge.points{ background: #dcfce7; color: #166534; }
    .q-badge.type-asc{ background: #f0f9ff; color: #0369a1; }
    .q-badge.type-desc{ background: #fef7cd; color: #92400e; }

    /* Main Content */
    .main-content{
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 12px;
      overflow: hidden;
      position: relative;
    }
    .content-header{
      padding: 16px 20px;
      border-bottom: 1px solid var(--border);
      background: var(--bg-gray);
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .content-header h5{
      margin: 0;
      font-size: 16px;
      font-weight: 600;
      color: var(--ink);
    }
    .content-header-actions{ display: flex; gap: 8px; align-items: center; }
    .content-body{ padding: 24px; min-height: 500px; position: relative; }

    /* Form Elements */
    .section-title{
      font-size: 15px;
      font-weight: 600;
      color: var(--ink);
      margin: 0 0 16px;
      padding-bottom: 8px;
      border-bottom: 2px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
    }
    .section-title .st-left{ display:flex; align-items:center; gap:8px; }
    .row{ display: flex; gap: 16px; margin-bottom: 20px; }
    .col{ flex: 1; }
    .form-group{ margin-bottom: 20px; }
    .form-label{
      display: block;
      margin-bottom: 6px;
      font-size: 13px;
      font-weight: 600;
      color: var(--ink);
    }
    .form-control, .form-select{
      width: 100%;
      padding: 10px 12px;
      border: 1px solid var(--border);
      border-radius: 8px;
      font-size: 14px;
      background: var(--surface);
      color: var(--ink);
      transition: all 0.2s;
    }
    .form-control:focus, .form-select:focus{
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    }

    /* Bubbles Editor */
    .bubbles-editor{
      background: var(--bg-gray);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 16px;
      margin-bottom: 20px;
    }
    .bubbles-list{
      display: flex;
      flex-direction: column;
      gap: 12px;
      margin-bottom: 16px;
    }
    .bubble-item{
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 8px;
      transition: all 0.2s;
    }
    .bubble-item:hover{
      border-color: var(--primary);
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .bubble-handle{ color: var(--muted); cursor: move; font-size: 14px; }
    .bubble-inputs{ flex: 1; display: flex; gap: 12px; }
    .bubble-label{ flex: 2; }
    .bubble-value{ flex: 1; }
    .bubble-actions{ display: flex; gap: 8px; }
    .bubble-btn{
      width: 32px;
      height: 32px;
      border: none;
      background: transparent;
      border-radius: 6px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      color: var(--muted);
      transition: all 0.2s;
    }
    .bubble-btn:hover{ background: var(--bg-gray); }
    .bubble-btn.delete:hover{ background: #fee; color: var(--danger); }
    .add-bubble-btn{
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 10px 16px;
      border: 2px dashed var(--border);
      background: transparent;
      border-radius: 8px;
      font-size: 13px;
      font-weight: 500;
      color: var(--primary);
      cursor: pointer;
      transition: all 0.2s;
      width: 100%;
      justify-content: center;
    }
    .add-bubble-btn:hover{ border-color: var(--primary); background: #f5f3ff; }

    /* Answer Order Builder */
    .answer-wrap{
      background: var(--bg-gray);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 14px;
      margin-bottom: 16px;
    }
    .answer-toolbar{
      display:flex;
      gap:8px;
      align-items:flex-start;
      justify-content: space-between;
      margin-bottom: 12px;
    }
    .answer-hint{
      font-size: 12px;
      color: var(--muted);
      margin: 0;
      line-height: 1.35;
    }
    .answer-list{
      display:flex;
      flex-direction:column;
      gap: 10px;
    }
    .answer-item{
      display:flex;
      align-items:center;
      gap: 12px;
      padding: 12px;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 10px;
      transition: all .2s;
    }
    .answer-item:hover{
      border-color: var(--primary);
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    .answer-handle{
      color: var(--muted);
      cursor: move;
      font-size: 14px;
      width: 24px;
      display:flex;
      align-items:center;
      justify-content:center;
      flex-shrink:0;
    }
    .answer-orderno{
      width: 28px;
      height: 28px;
      border-radius: 8px;
      background: #eef2ff;
      color: #3730a3;
      font-weight: 700;
      display:flex;
      align-items:center;
      justify-content:center;
      flex-shrink:0;
      font-size: 13px;
    }
    html.theme-dark .answer-orderno{
      background:#312e81;
      color:#fff;
    }
    .answer-main{ flex:1; min-width:0; }
    .answer-eq{
      margin: 0 0 6px;
      font-size: 13px;
      font-weight: 600;
      color: var(--ink);
      word-break: break-word;
    }
    .answer-meta{
      display:flex;
      gap: 8px;
      flex-wrap: wrap;
      align-items:center;
    }
    .answer-pill{
      font-size: 11px;
      font-weight: 700;
      padding: 4px 8px;
      border-radius: 999px;
      border: 1px solid var(--border);
      background: var(--bg-gray);
      color: var(--ink);
      display:inline-flex;
      align-items:center;
      gap: 6px;
    }
    .answer-pill.ok{
      background: #dcfce7;
      border-color: #bbf7d0;
      color: #166534;
    }
    .answer-pill.err{
      background: #fee2e2;
      border-color: #fecaca;
      color: #991b1b;
    }

    /* Buttons */
    .btn{
      padding: 10px 20px;
      border: none;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.2s;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }
    .btn-primary{ background: var(--primary); color: white; }
    .btn-primary:hover{ background: #4338ca; transform: translateY(-1px); }
    .btn-secondary{ background: var(--secondary); color: white; }
    .btn-secondary:hover{ background: #0284c7; transform: translateY(-1px); }
    .btn-light{ background: var(--surface); color: var(--ink); border: 1px solid var(--border); }
    .btn-light:hover{ background: var(--bg-gray); border-color: var(--primary); }
    .btn-danger{ background: var(--danger); color: white; }
    .btn-danger:hover{ background: #dc2626; }
    .btn-sm{ padding: 6px 12px; font-size: 13px; }

    /* Footer */
    .content-footer{
      padding: 16px 24px;
      border-top: 1px solid var(--border);
      display: flex;
      justify-content: flex-end;
      gap: 12px;
      background: var(--bg-gray);
    }

    /* Empty State */
    .empty-state{
      padding: 60px 20px;
      text-align: center;
      color: var(--muted);
    }
    .empty-state i{
      font-size: 48px;
      opacity: 0.3;
      margin-bottom: 16px;
    }

    /* Loader */
    .loader-overlay{
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(255,255,255,0.8);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 100;
      border-radius: 12px;
      display: none;
    }
    html.theme-dark .loader-overlay{ background: rgba(0,0,0,0.6); }
    .loader{
      width: 40px;
      height: 40px;
      border: 4px solid var(--border);
      border-top: 4px solid var(--primary);
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }
    @keyframes spin{ 0%{transform:rotate(0)} 100%{transform:rotate(360deg)} }

    /* Toast */
    .toast-container{
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 9999;
    }
    .toast{
      min-width: 300px;
      padding: 16px;
      border-radius: 8px;
      margin-bottom: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      display: none;
      align-items: center;
      gap: 12px;
      animation: slideIn 0.3s ease;
    }
    .toast.show{ display:flex; }
    .toast.success{ background: var(--success); color:#fff; }
    .toast.error{ background: var(--danger); color:#fff; }
    @keyframes slideIn{ from{transform:translateX(100%);opacity:0} to{transform:translateX(0);opacity:1} }

    /* Preview Modal */
    .preview-overlay{
      position: fixed;
      inset: 0;
      background: rgba(15,23,42,0.35);
      backdrop-filter: blur(3px);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 9998;
      padding: 20px;
    }
    .preview-modal{
      width: min(800px, 100%);
      max-height: 90vh;
      background: var(--surface);
      border-radius: 18px;
      border: 1px solid var(--border);
      box-shadow: 0 22px 55px rgba(15,23,42,0.45);
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }
    .preview-header{
      padding: 20px;
      border-bottom: 1px solid var(--border);
      background: linear-gradient(135deg, var(--bg-gray), rgba(79,70,229,0.04));
    }
    .preview-title{
      margin: 0 0 8px;
      font-size: 18px;
      font-weight: 600;
      color: var(--ink);
    }
    .preview-chips{ display:flex; gap:8px; flex-wrap:wrap; }
    .preview-body{ padding: 20px; overflow-y:auto; flex:1; }
    .preview-footer{
      padding: 16px 20px;
      border-top: 1px solid var(--border);
      display: flex;
      justify-content: flex-end;
      gap: 8px;
    }

    /* Drag & Drop */
    .sortable-ghost{ opacity: 0.4; background: #f0f0f0; }
    .sortable-drag{ opacity: 0.8; transform: rotate(2deg); }

    /* Scrollbar */
    .sidebar-body::-webkit-scrollbar,
    .preview-body::-webkit-scrollbar{ width: 6px; }
    .sidebar-body::-webkit-scrollbar-track,
    .preview-body::-webkit-scrollbar-track{ background: transparent; }
    .sidebar-body::-webkit-scrollbar-thumb,
    .preview-body::-webkit-scrollbar-thumb{ background: var(--border); border-radius: 3px; }

    /* Responsive */
    @media (max-width: 768px){
      .row{ flex-direction: column; gap: 12px; }
      .bubble-inputs{ flex-direction: column; }
      .game-header-top{ flex-direction: column; gap: 16px; }
      .content-header{ flex-direction: column; gap: 12px; align-items: flex-start; }
      .content-footer{ flex-direction: column; }
      .content-footer .btn{ width: 100%; }
      .answer-toolbar{ flex-direction: column; }
      .answer-toolbar .btn{ width: 100%; }
    }
  </style>
@endpush

@section('content')
  <div class="container">
    <!-- Game Header -->
    <div class="game-header">
      <div class="game-header-top">
        <div>
          <div class="game-chip">
            <i class="fa fa-gamepad"></i>
            <span>Bubble Game</span>
          </div>
          <h1 class="game-title" id="gameTitle">Loading...</h1>
          <p class="game-desc" id="gameDesc"></p>
        </div>
        <div class="game-meta">
          <div class="meta-item">
            <div class="meta-label">Questions</div>
            <div class="meta-value" id="questionsCount">0</div>
          </div>
          <div class="meta-item">
            <div class="meta-label">Bubbles</div>
            <div class="meta-value" id="totalBubbles">0</div>
          </div>
          <div class="meta-item">
            <div class="meta-label">Points</div>
            <div class="meta-value" id="totalPoints">0</div>
          </div>
        </div>
      </div>
      <div class="game-meta">
        <div class="meta-item">
          <div class="meta-label">Time per Question</div>
          <div class="meta-value" id="perQuestionTime">30s</div>
        </div>
        <div class="meta-item">
          <div class="meta-label">Max Attempts</div>
          <div class="meta-value" id="maxAttempts">1</div>
        </div>
        <div class="meta-item">
          <div class="meta-label">Points Correct</div>
          <div class="meta-value" id="pointsCorrect">1</div>
        </div>
        <div class="meta-item">
          <div class="meta-label">Points Wrong</div>
          <div class="meta-value" id="pointsWrong">0</div>
        </div>
      </div>
    </div>

    <div class="layout-grid">
      <!-- Sidebar -->
      <div class="sidebar">
        <div class="sidebar-header">
          <h6><i class="fa fa-list-ol me-2"></i>Questions (<span id="sidebarQuestionsCount">0</span>)</h6>
        </div>
        <div class="sidebar-actions">
          <div class="sidebar-search">
            <i class="fa fa-search search-icon"></i>
            <input type="text" id="qSearch" placeholder="Search questions...">
          </div>
          <button id="btnNewQuestion" class="btn btn-primary btn-sm">
            <i class="fa fa-plus"></i>
          </button>
        </div>
        <div class="sidebar-body">
          <div id="qList" class="question-list">
            <div class="empty-state">
              <i class="fa fa-spinner fa-spin"></i>
              <div>Loading questions...</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content -->
      <div class="main-content">
        <div class="loader-overlay" id="contentLoader">
          <div class="loader"></div>
        </div>

        <div class="content-header">
          <div>
            <button id="btnBack" class="btn btn-light btn-sm" onclick="window.history.back()">
              <i class="fa fa-arrow-left"></i> Back
            </button>
            <h5 class="mt-2 mb-0" id="formTitle">New Question</h5>
          </div>
          <div class="content-header-actions">
            <button id="btnHelp" class="btn btn-light btn-sm" title="Help">
              <i class="fa fa-circle-question"></i>
            </button>
            <button id="btnPreview" class="btn btn-secondary btn-sm" style="display:none">
              <i class="fa fa-eye"></i> Preview
            </button>
          </div>
        </div>

        <div class="content-body">
          <form id="qForm" novalidate>
            <input type="hidden" id="qId">
            <input type="hidden" id="gameUuid" value="{{ request()->query('game') ?? request()->query('game_uuid') ?? request()->query('uuid') ?? request()->query('id') }}">

            <!-- Basic Information -->
            <div class="section-title">
              <div class="st-left"><i class="fa fa-info-circle"></i> Basic Information</div>
            </div>

            <div class="row">
              <div class="col">
                <div class="form-group">
                  <label class="form-label">Question Title (Optional)</label>
                  <input id="qTitle" type="text" class="form-control" placeholder="Enter question title">
                </div>
              </div>
              <div class="col">
                <div class="form-group">
                  <label class="form-label">Select Type</label>
                  <select id="qSelectType" class="form-select">
                    <option value="ascending">Ascending</option>
                    <option value="descending">Descending</option>
                  </select>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col">
                <div class="form-group">
                  <label class="form-label">Points</label>
                  <input id="qPoints" type="number" min="1" class="form-control" value="1">
                </div>
              </div>
              <div class="col">
                <div class="form-group">
                  <label class="form-label">Display Order</label>
                  <input id="qOrder" type="number" min="0" class="form-control" value="1">
                </div>
              </div>
              <div class="col">
                <div class="form-group">
                  <label class="form-label">Status</label>
                  <select id="qStatus" class="form-select">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Bubbles Editor -->
            <div class="section-title">
              <div class="st-left">
                <i class="fa fa-circle-nodes"></i> Bubbles
                <span class="ml-2 text-sm text-muted" id="bubblesCount">0 bubbles</span>
              </div>
            </div>

            <div class="bubbles-editor">
              <div class="form-group">
                <label class="form-label">Bubble List (Equations)</label>
                <p class="text-sm text-muted mb-3">Drag to reorder bubbles (display order). Each bubble label should be a math equation like <code>(1+2)*3</code>.</p>

                <div id="bubblesList" class="bubbles-list"></div>

                <button type="button" id="btnAddBubble" class="add-bubble-btn">
                  <i class="fa fa-plus"></i> Add Bubble
                </button>
              </div>
            </div>

            <!-- Answer Configuration -->
            <div class="section-title">
              <div class="st-left"><i class="fa fa-list-ol"></i> Correct Answer Order</div>
            </div>

            <div class="answer-wrap">
              <div class="answer-toolbar">
                <p class="answer-hint">
                  Correct order is <b>auto-arranged</b> based on <b>Asc/Desc</b> and computed results.
                  Saved automatically as <code>answer_sequence_json</code> + <code>answer_value_json</code>.
                </p>

                <!-- ✅ keep manual button too (just in case) -->
                <button type="button" id="btnAutoOrder" class="btn btn-light btn-sm" title="Auto arrange now">
                  <i class="fa fa-wand-magic-sparkles"></i> Auto Arrange
                </button>
              </div>

              <div id="answerOrderList" class="answer-list">
                <div class="empty-state" style="padding:26px 10px;">
                  <i class="fa fa-circle-info"></i>
                  <div>Add bubbles to see computed answers here.</div>
                </div>
              </div>
            </div>

            <!-- Info Box -->
            <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
              <div class="flex items-start gap-3">
                <i class="fa fa-info-circle text-blue-500 mt-1"></i>
                <div>
                  <h6 class="font-semibold text-blue-800 mb-1">How Bubble Games Work</h6>
                  <p class="text-sm text-blue-700 mb-2">
                    <strong>Ascending</strong>: User should arrange bubbles from smallest result to largest result.<br>
                    <strong>Descending</strong>: User should arrange bubbles from largest result to smallest result.<br>
                    <strong>Correct Order</strong>: Auto-generated (no manual JSON typing).
                  </p>
                  <p class="text-xs text-blue-600">
                    Tip: Bubble list order is the initial display order. Correct order is always computed from results.
                  </p>
                </div>
              </div>
            </div>
          </form>
        </div>

        <div class="content-footer">
          <button id="btnCancel" class="btn btn-light">Cancel</button>
          <button id="btnDelete" class="btn btn-danger" style="display: none;">
            <i class="fa fa-trash"></i> Delete Question
          </button>
          <button id="btnSave" class="btn btn-primary">
            <i class="fa fa-save"></i> Save Question
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Toast Container -->
  <div class="toast-container">
    <div id="successToast" class="toast success">
      <i class="fa fa-check-circle"></i>
      <span id="successMsg">Success!</span>
    </div>
    <div id="errorToast" class="toast error">
      <i class="fa fa-exclamation-circle"></i>
      <span id="errorMsg">Error!</span>
    </div>
  </div>

  <!-- Preview Modal -->
  <div id="previewOverlay" class="preview-overlay">
    <div class="preview-modal">
      <div class="preview-header">
        <div>
          <div id="previewChips" class="preview-chips"></div>
          <h5 id="previewTitle" class="preview-title">Question Preview</h5>
        </div>
        <button type="button" class="btn btn-light btn-sm" id="previewCloseBtn">
          <i class="fa fa-times"></i>
        </button>
      </div>
      <div class="preview-body">
        <div id="previewContent"></div>
      </div>
      <div class="preview-footer">
        <button type="button" class="btn btn-light" id="previewCloseBtn2">Close</button>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
  <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <script>
document.addEventListener('DOMContentLoaded', function() {
  const TOKEN = localStorage.getItem('token') || sessionStorage.getItem('token') || '';

  const elements = {
    gameTitle: document.getElementById('gameTitle'),
    gameDesc: document.getElementById('gameDesc'),
    questionsCount: document.getElementById('questionsCount'),
    sidebarQuestionsCount: document.getElementById('sidebarQuestionsCount'),
    totalBubbles: document.getElementById('totalBubbles'),
    totalPoints: document.getElementById('totalPoints'),
    perQuestionTime: document.getElementById('perQuestionTime'),
    maxAttempts: document.getElementById('maxAttempts'),
    pointsCorrect: document.getElementById('pointsCorrect'),
    pointsWrong: document.getElementById('pointsWrong'),
    qList: document.getElementById('qList'),
    qForm: document.getElementById('qForm'),
    qId: document.getElementById('qId'),
    qTitle: document.getElementById('qTitle'),
    qSelectType: document.getElementById('qSelectType'),
    qPoints: document.getElementById('qPoints'),
    qOrder: document.getElementById('qOrder'),
    qStatus: document.getElementById('qStatus'),
    bubblesList: document.getElementById('bubblesList'),
    bubblesCount: document.getElementById('bubblesCount'),
    btnAddBubble: document.getElementById('btnAddBubble'),
    btnNewQuestion: document.getElementById('btnNewQuestion'),
    btnSave: document.getElementById('btnSave'),
    btnCancel: document.getElementById('btnCancel'),
    btnDelete: document.getElementById('btnDelete'),
    btnHelp: document.getElementById('btnHelp'),
    btnPreview: document.getElementById('btnPreview'),
    formTitle: document.getElementById('formTitle'),
    contentLoader: document.getElementById('contentLoader'),
    previewOverlay: document.getElementById('previewOverlay'),
    previewTitle: document.getElementById('previewTitle'),
    previewChips: document.getElementById('previewChips'),
    previewContent: document.getElementById('previewContent'),
    previewCloseBtn: document.getElementById('previewCloseBtn'),
    previewCloseBtn2: document.getElementById('previewCloseBtn2'),
    qSearch: document.getElementById('qSearch'),
    answerOrderList: document.getElementById('answerOrderList'),
    btnAutoOrder: document.getElementById('btnAutoOrder'),
  };

  function showToast(type, message) {
    const toast = document.getElementById(`${type}Toast`);
    const msgEl = document.getElementById(`${type}Msg`);
    if (!toast || !msgEl) return;
    msgEl.textContent = message;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3000);
  }

  function showLoader(show) {
    if (elements.contentLoader) {
      elements.contentLoader.style.display = show ? 'flex' : 'none';
    }
  }

  function escapeHtml(text) {
    if (!text) return '';
    const map = { '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;' };
    return text.toString().replace(/[&<>"']/g, m => map[m]);
  }

  function formatNumber(n) {
    if (typeof n !== 'number' || !isFinite(n)) return 'ERR';
    if (Number.isInteger(n)) return String(n);
    const fixed = n.toFixed(6);
    return fixed.replace(/\.?0+$/,'');
  }

  function safeEvalEquation(raw) {
    let expr = (raw || '').toString().trim();
    if (!expr) return { ok:false, value: null, error: 'Empty equation' };
    expr = expr.replace(/×/g, '*').replace(/÷/g, '/');
    expr = expr.replace(/\^/g, '**');
    if (!/^[0-9+\-*/().\s]*$/.test(expr)) return { ok:false, value:null, error:'Invalid characters' };
    if (expr.length > 120) return { ok:false, value:null, error:'Too long' };

    try {
      // eslint-disable-next-line no-new-func
      const v = Function('"use strict"; return (' + expr + ');')();
      if (typeof v !== 'number' || !isFinite(v)) return { ok:false, value:null, error:'Not a number' };
      return { ok:true, value:v, error:null };
    } catch (e) {
      return { ok:false, value:null, error:'Invalid expression' };
    }
  }

  function resolveGameUuid() {
    const fromHidden = (document.getElementById('gameUuid')?.value || '').trim();
    const url = new URL(window.location.href);
    const p = url.searchParams;

    let v = fromHidden
      || (p.get('game') || '').trim()
      || (p.get('game_uuid') || '').trim()
      || (p.get('uuid') || '').trim()
      || (p.get('id') || '').trim();

    if (['null','undefined','0'].includes((v || '').toLowerCase())) v = '';

    if (!v) {
      const m = url.pathname.match(/bubble-games\/([^\/]+)\//i);
      if (m && m[1]) v = m[1].trim();
    }

    const hidden = document.getElementById('gameUuid');
    if (hidden && v) hidden.value = v;

    return v;
  }

  async function apiFetch(url, options = {}) {
    const headers = { 'Accept':'application/json', 'X-Requested-With':'XMLHttpRequest', ...options.headers };
    if (TOKEN) headers['Authorization'] = `Bearer ${TOKEN}`;
    if (!(options.body instanceof FormData) && !headers['Content-Type']) headers['Content-Type'] = 'application/json';

    try {
      const res = await fetch(url, { ...options, headers });

      let data = null;
      const contentType = res.headers.get('content-type') || '';
      if (contentType.includes('application/json')) { try { data = await res.json(); } catch(e) { data = null; } }
      else { try { data = await res.text(); } catch(e) { data = null; } }

      if (res.status === 401 || res.status === 419) {
        showToast('error', 'Session expired. Please login again.');
        setTimeout(() => window.location.href = '/login', 1500);
        return { ok:false, status:res.status, data };
      }

      return { ok:res.ok, status:res.status, data };
    } catch (err) {
      console.error('API Error:', err);
      showToast('error', 'Network error. Please check your connection.');
      return { ok:false, status:0, data:null };
    }
  }

  let gameUuid = resolveGameUuid();
  let gameData = null;
  let questions = [];
  let editingId = null;
  let currentQuestion = null;

  if (!gameUuid) {
    if (elements.qList) {
      elements.qList.innerHTML = `
        <div class="empty-state">
          <i class="fa fa-triangle-exclamation"></i>
          <div style="margin-top:8px;font-weight:600;">Game ID missing</div>
          <div style="margin-top:6px;">Open this page with <code>?game=&lt;uuid&gt;</code></div>
          <div style="margin-top:10px;">
            <button class="btn btn-primary btn-sm" id="goGamesBtn">
              <i class="fa fa-list"></i> Go to Games
            </button>
          </div>
        </div>
      `;
      document.getElementById('goGamesBtn')?.addEventListener('click', () => window.location.href = '/bubblegame');
    }
    showToast('error', 'Game ID is required. Please select a game first.');
    return;
  }

  // ========= Sortable =========
  let sortableBubbles;
  let sortableAnswers;

  function initSortableBubbles() {
    if (!elements.bubblesList) return;
    if (sortableBubbles) sortableBubbles.destroy();
    sortableBubbles = Sortable.create(elements.bubblesList, {
      animation: 150,
      ghostClass: 'sortable-ghost',
      dragClass: 'sortable-drag',
      handle: '.bubble-handle',
      onEnd: function() {
        updateBubblesCount();
        rebuildAnswerOrderList(true);
      }
    });
  }

  function initSortableAnswers() {
    if (!elements.answerOrderList) return;
    if (sortableAnswers) sortableAnswers.destroy();
    sortableAnswers = Sortable.create(elements.answerOrderList, {
      animation: 150,
      ghostClass: 'sortable-ghost',
      dragClass: 'sortable-drag',
      handle: '.answer-handle',
      onEnd: function() {
        // always snap back to computed order (auto-arrange always)
        autoArrangeAnswers();
      }
    });
  }

  // ========= Bubble management =========
  function makeKey() {
    return 'b_' + Math.random().toString(16).slice(2) + '_' + Date.now().toString(16);
  }

  let autoArrangeTimer = null;
  function scheduleAutoArrange() {
    clearTimeout(autoArrangeTimer);
    autoArrangeTimer = setTimeout(() => autoArrangeAnswers(), 220);
  }

  function createBubbleElement(key, label = '', value = '') {
    const div = document.createElement('div');
    div.className = 'bubble-item';
    div.dataset.key = key;

    div.innerHTML = `
      <div class="bubble-handle"><i class="fa fa-grip-vertical"></i></div>
      <div class="bubble-inputs">
        <div class="bubble-label">
          <input type="text" class="form-control bubble-label-input" placeholder="Equation (e.g., (1+2)*3)" value="${escapeHtml(label)}">
        </div>
        <div class="bubble-value">
          <input type="text" class="form-control bubble-value-input" placeholder="(optional) note/value" value="${escapeHtml(value)}">
        </div>
      </div>
      <div class="bubble-actions">
        <button type="button" class="bubble-btn delete" title="Delete bubble"><i class="fa fa-trash"></i></button>
      </div>
    `;

    div.querySelector('.bubble-label-input')?.addEventListener('input', () => {
      updateAnswerRowForKey(key);
      scheduleAutoArrange(); // ✅ auto arrange while typing
    });

    div.querySelector('.bubble-btn.delete')?.addEventListener('click', () => {
      if (elements.bubblesList.children.length > 1) {
        div.remove();
        updateBubblesCount();
        rebuildAnswerOrderList(true);
      } else {
        showToast('error', 'At least one bubble is required');
      }
    });

    return div;
  }

  function addBubble(label = '', value = '') {
    if (!elements.bubblesList) return;
    const key = makeKey();
    elements.bubblesList.appendChild(createBubbleElement(key, label, value));
    updateBubblesCount();
    rebuildAnswerOrderList(true); // ✅ auto arrange inside
  }

  function updateBubblesCount() {
    if (!elements.bubblesList || !elements.bubblesCount) return;
    const count = elements.bubblesList.children.length;
    elements.bubblesCount.textContent = `${count} bubble${count !== 1 ? 's' : ''}`;
  }

  function getBubbleEls() {
    return Array.from(elements.bubblesList?.querySelectorAll('.bubble-item') || []);
  }

  function getBubblesDataClean() {
    const bubbles = [];
    getBubbleEls().forEach(item => {
      const label = item.querySelector('.bubble-label-input')?.value?.trim() || '';
      const value = item.querySelector('.bubble-value-input')?.value?.trim() || '';
      if (label) bubbles.push({ label, value: value || null });
    });
    return bubbles;
  }

  function setBubblesData(bubbles) {
    if (!elements.bubblesList) return;
    elements.bubblesList.innerHTML = '';

    if (Array.isArray(bubbles) && bubbles.length) {
      bubbles.forEach(b => {
        const key = makeKey();
        elements.bubblesList.appendChild(createBubbleElement(key, b?.label || '', b?.value || ''));
      });
    } else {
      const key = makeKey();
      elements.bubblesList.appendChild(createBubbleElement(key, 'Bubble 1', ''));
    }

    initSortableBubbles();
    updateBubblesCount();
    rebuildAnswerOrderList(false);
  }

  // ========= Answer Order (AUTO ARRANGE ALWAYS) =========
  function buildAnswerRow(key, equation, bubbleIndex) {
    const ev = safeEvalEquation(equation);
    const resultText = ev.ok ? formatNumber(ev.value) : 'ERR';

    const row = document.createElement('div');
    row.className = 'answer-item';
    row.dataset.key = key;

    row.innerHTML = `
      <div class="answer-handle"><i class="fa fa-grip-vertical"></i></div>
      <div class="answer-orderno">1</div>
      <div class="answer-main">
        <p class="answer-eq">${escapeHtml(equation || '(empty)')}</p>
        <div class="answer-meta">
          <span class="answer-pill ${ev.ok ? 'ok' : 'err'}">
            <i class="fa ${ev.ok ? 'fa-check' : 'fa-triangle-exclamation'}"></i>
            = <span class="ans-val">${escapeHtml(resultText)}</span>
          </span>
          <span class="answer-pill">
            <i class="fa fa-circle-nodes"></i>
            Bubble #<span class="ans-bidx">${bubbleIndex + 1}</span>
          </span>
        </div>
      </div>
    `;
    return row;
  }

  function refreshAnswerOrderNumbers() {
    const items = Array.from(elements.answerOrderList?.querySelectorAll('.answer-item') || []);
    items.forEach((it, idx) => {
      const box = it.querySelector('.answer-orderno');
      if (box) box.textContent = String(idx + 1);
    });
  }

  function autoArrangeAnswers() {
    const list = elements.answerOrderList;
    if (!list) return;

    const items = Array.from(list.querySelectorAll('.answer-item'));
    if (!items.length) return;

    const descending = (elements.qSelectType?.value || 'ascending') === 'descending';
    const dir = descending ? -1 : 1;

    const bubbleEls = getBubbleEls();
    const bubbleMap = new Map(bubbleEls.map((b, idx) => {
      const eq = b.querySelector('.bubble-label-input')?.value?.trim() || '';
      const ev = safeEvalEquation(eq);
      const num = ev.ok ? ev.value : (descending ? Number.NEGATIVE_INFINITY : Number.POSITIVE_INFINITY);
      return [b.dataset.key, { idx, eq, num, ok: ev.ok }];
    }));

    const withVal = items.map(it => {
      const key = it.dataset.key;
      const info = bubbleMap.get(key) || { idx: 999999, num: (descending ? Number.NEGATIVE_INFINITY : Number.POSITIVE_INFINITY) };
      return { it, key, num: info.num, bidx: info.idx };
    });

    withVal.sort((a,b) => {
      const d = (a.num - b.num) * dir;
      if (d !== 0) return d;
      return (a.bidx - b.bidx); // stable tie-break
    });

    list.innerHTML = '';
    withVal.forEach(x => list.appendChild(x.it));

    // refresh computed labels
    bubbleEls.forEach(b => updateAnswerRowForKey(b.dataset.key));
    refreshAnswerOrderNumbers();
  }

  function rebuildAnswerOrderList(preserveExistingOrder) {
    if (!elements.answerOrderList) return;

    const bubbleEls = getBubbleEls();
    if (!bubbleEls.length) {
      elements.answerOrderList.innerHTML = `
        <div class="empty-state" style="padding:26px 10px;">
          <i class="fa fa-circle-info"></i>
          <div>Add bubbles to see computed answers here.</div>
        </div>
      `;
      return;
    }

    const existingKeys = preserveExistingOrder
      ? Array.from(elements.answerOrderList.querySelectorAll('.answer-item')).map(x => x.dataset.key).filter(Boolean)
      : [];

    const bubbleInfo = bubbleEls.map((el, idx) => {
      const key = el.dataset.key || (el.dataset.key = makeKey());
      const eq = el.querySelector('.bubble-label-input')?.value?.trim() || '';
      return { key, eq, idx };
    });

    const map = new Map(bubbleInfo.map(b => [b.key, b]));

    let ordered = [];
    if (preserveExistingOrder && existingKeys.length) {
      existingKeys.forEach(k => { if (map.has(k)) ordered.push(map.get(k)); });
      bubbleInfo.forEach(b => { if (!existingKeys.includes(b.key)) ordered.push(b); });
    } else {
      ordered = bubbleInfo.slice();
    }

    elements.answerOrderList.innerHTML = '';
    ordered.forEach(b => elements.answerOrderList.appendChild(buildAnswerRow(b.key, b.eq, b.idx)));

    initSortableAnswers();
    autoArrangeAnswers(); // ✅ always auto arrange after rebuild
  }

  function updateAnswerRowForKey(key) {
    if (!elements.answerOrderList) return;
    const row = elements.answerOrderList.querySelector(`.answer-item[data-key="${key}"]`);
    if (!row) { rebuildAnswerOrderList(true); return; }

    const bubbleEls = getBubbleEls();
    const bubbleIndex = bubbleEls.findIndex(b => b.dataset.key === key);
    const eq = bubbleEls[bubbleIndex]?.querySelector('.bubble-label-input')?.value?.trim() || '';

    const eqEl = row.querySelector('.answer-eq');
    if (eqEl) eqEl.textContent = eq || '(empty)';

    const idxEl = row.querySelector('.ans-bidx');
    if (idxEl) idxEl.textContent = String((bubbleIndex >= 0 ? bubbleIndex + 1 : 0));

    const ev = safeEvalEquation(eq);
    const pill = row.querySelector('.answer-pill');
    const valEl = row.querySelector('.ans-val');

    if (pill) {
      pill.classList.remove('ok','err');
      pill.classList.add(ev.ok ? 'ok' : 'err');
      const icon = pill.querySelector('i');
      if (icon) icon.className = 'fa ' + (ev.ok ? 'fa-check' : 'fa-triangle-exclamation');
    }
    if (valEl) valEl.textContent = ev.ok ? formatNumber(ev.value) : 'ERR';
  }

  // ✅ manual button (just in case)
  elements.btnAutoOrder?.addEventListener('click', () => {
    autoArrangeAnswers();
    showToast('success', 'Auto arranged');
  });

  // ✅ whenever select type changes -> auto arrange
  elements.qSelectType?.addEventListener('change', () => autoArrangeAnswers());
  function sanitizeGameDesc(raw) {
  if (!raw) return '';

  const str = String(raw);

  // If it's plain text, keep it safe and support new lines
  const looksLikeHtml = /<\/?[a-z][\s\S]*>/i.test(str);
  if (!looksLikeHtml) {
    return escapeHtml(str).replace(/\n/g, '<br>');
  }

  // If it contains HTML → sanitize (allowlist)
  const parser = new DOMParser();
  const doc = parser.parseFromString(str, 'text/html');

  const allowedTags = new Set([
    'B','STRONG','I','EM','U','BR',
    'P','DIV','SPAN',
    'UL','OL','LI',
    'CODE','PRE','SMALL',
    'H1','H2','H3','H4','H5','H6',
    'A'
  ]);

  const killTags = doc.body.querySelectorAll('script,style,iframe,object,embed,link,meta');
  killTags.forEach(n => n.remove());

  const all = Array.from(doc.body.querySelectorAll('*'));
  all.forEach(el => {
    // remove any non-allowed tag but keep its text
    if (!allowedTags.has(el.tagName)) {
      const txt = doc.createTextNode(el.textContent || '');
      el.replaceWith(txt);
      return;
    }

    // strip unsafe attributes
    Array.from(el.attributes).forEach(attr => {
      const name = attr.name.toLowerCase();

      // remove inline JS handlers like onclick=
      if (name.startsWith('on')) {
        el.removeAttribute(attr.name);
        return;
      }

      // only allow href on <a>
      if (el.tagName === 'A' && name === 'href') {
        const href = (el.getAttribute('href') || '').trim();
        if (!/^(https?:|mailto:|tel:|\/)/i.test(href)) {
          el.removeAttribute('href');
        } else {
          el.setAttribute('target', '_blank');
          el.setAttribute('rel', 'noopener noreferrer');
        }
        return;
      }

      // remove everything else (class/style/data-* etc)
      el.removeAttribute(attr.name);
    });
  });

  return (doc.body.innerHTML || '').trim();
}

  // ========= Game & Questions =========
  function updateGameHeader() {
    if (!gameData) return;
    elements.gameTitle.textContent = gameData.title || 'Untitled Game';
const desc = gameData.description || '';
elements.gameDesc.innerHTML = desc
  ? sanitizeGameDesc(desc)
  : 'No description provided';
    elements.perQuestionTime.textContent = `${gameData.per_question_time_sec || 30}s`;
    elements.maxAttempts.textContent = gameData.max_attempts || 1;
    elements.pointsCorrect.textContent = gameData.points_correct || 1;
    elements.pointsWrong.textContent = gameData.points_wrong || 0;
  }

  function updateQuestionsCount() {
    const count = questions.length;
    elements.questionsCount.textContent = count;
    elements.sidebarQuestionsCount.textContent = count;

    let totalBubbles = 0;
    let totalPoints = 0;
    questions.forEach(q => {
      totalBubbles += (q.bubbles_count || 0);
      totalPoints += (q.points || 0);
    });

    elements.totalBubbles.textContent = totalBubbles;
    elements.totalPoints.textContent = totalPoints;
  }

  function renderQuestionList() {
    if (!elements.qList) return;

    if (!questions.length) {
      elements.qList.innerHTML = `
        <div class="empty-state">
          <i class="fa fa-inbox"></i>
          <div>No questions yet</div>
          <button class="btn btn-primary btn-sm mt-3" id="btnAddFirst">
            <i class="fa fa-plus"></i> Add First Question
          </button>
        </div>
      `;
      document.getElementById('btnAddFirst')?.addEventListener('click', resetForm);
      return;
    }

    elements.qList.innerHTML = '';
    questions.sort((a,b) => (a.order_no || 0) - (b.order_no || 0));

    questions.forEach((q, index) => {
      const item = document.createElement('div');
      item.className = 'question-item';
      item.dataset.id = q.uuid;

      const typeBadge = q.select_type === 'ascending'
        ? '<span class="q-badge type-asc">Asc</span>'
        : '<span class="q-badge type-desc">Desc</span>';

      item.innerHTML = `
        <div class="q-number">${q.order_no || index + 1}</div>
        <div class="q-content">
          <div class="q-title">${escapeHtml(q.title || ('Question ' + (index + 1)))}</div>
          <div class="q-meta">
            <span class="q-badge bubbles">${q.bubbles_count || 0} bubbles</span>
            <span class="q-badge points">${q.points || 1} pts</span>
            ${typeBadge}
          </div>
        </div>
      `;

      item.addEventListener('click', () => openQuestion(q.uuid));
      elements.qList.appendChild(item);
    });
  }

  function resetForm() {
    editingId = null;
    currentQuestion = null;

    elements.qId.value = '';
    elements.qTitle.value = '';
    elements.qSelectType.value = 'ascending';
    elements.qPoints.value = '1';
    elements.qOrder.value = (questions.length ? (Math.max(...questions.map(q => q.order_no || 0)) + 1) : 1);
    elements.qStatus.value = 'active';

    setBubblesData([{ label: '(1+2)*3', value: '' }]);

    elements.formTitle.textContent = 'New Question';
    elements.btnDelete.style.display = 'none';

    document.querySelectorAll('.question-item').forEach(i => i.classList.remove('active'));
  }

  async function loadGameData() {
    showLoader(true);
    const res = await apiFetch(`/api/bubble-games/${gameUuid}`);
    showLoader(false);

    if (!res.ok) {
      if (elements.qList) {
        elements.qList.innerHTML = `
          <div class="empty-state">
            <i class="fa fa-triangle-exclamation"></i>
            <div style="margin-top:8px;font-weight:600;">Failed to load game</div>
            <div style="margin-top:6px;">Check API route: <code>/api/bubble-games/${escapeHtml(gameUuid)}</code></div>
          </div>
        `;
      }
      showToast('error', res.data?.message || 'Failed to load game data');
      return;
    }

    gameData = res.data?.data || res.data;
    updateGameHeader();
    await loadQuestions();
  }

  async function loadQuestions() {
    showLoader(true);
    const res = await apiFetch(`/api/bubble-games/${gameUuid}/questions?paginate=false`);
    showLoader(false);

    if (!res.ok) {
      if (elements.qList) {
        elements.qList.innerHTML = `
          <div class="empty-state">
            <i class="fa fa-triangle-exclamation"></i>
            <div style="margin-top:8px;font-weight:600;">Failed to load questions</div>
            <div style="margin-top:6px;">Check API route & auth.</div>
            <div style="margin-top:10px;">
              <button class="btn btn-primary btn-sm" id="retryBtn">
                <i class="fa fa-rotate"></i> Retry
              </button>
            </div>
          </div>
        `;
        document.getElementById('retryBtn')?.addEventListener('click', loadQuestions);
      }
      showToast('error', res.data?.message || 'Failed to load questions');
      return;
    }

    const payload = res.data;
    let arr = [];
    if (Array.isArray(payload)) arr = payload;
    else if (payload && Array.isArray(payload.data)) arr = payload.data;
    else if (payload && payload.success && Array.isArray(payload.data)) arr = payload.data;

    questions = arr;
    updateQuestionsCount();
    renderQuestionList();
  }

  async function openQuestion(questionUuid) {
    showLoader(true);
    const res = await apiFetch(`/api/bubble-games/${gameUuid}/questions/${questionUuid}`);
    showLoader(false);

    if (!res.ok) { showToast('error', 'Failed to load question'); return; }

    currentQuestion = res.data?.data || res.data;
    editingId = questionUuid;

    elements.qId.value = currentQuestion.uuid;
    elements.qTitle.value = currentQuestion.title || '';
    elements.qSelectType.value = currentQuestion.select_type || 'ascending';
    elements.qPoints.value = currentQuestion.points || 1;
    elements.qOrder.value = currentQuestion.order_no || 1;
    elements.qStatus.value = currentQuestion.status || 'active';

    if (Array.isArray(currentQuestion.bubbles_json)) setBubblesData(currentQuestion.bubbles_json);
    else setBubblesData([{ label: '(1+2)*3', value: '' }]);

    elements.formTitle.textContent = `Edit Question #${currentQuestion.order_no || ''}`;
    elements.btnDelete.style.display = 'inline-flex';

    document.querySelectorAll('.question-item').forEach(item => {
      item.classList.toggle('active', item.dataset.id === questionUuid);
    });

    elements.qForm.scrollIntoView({ behavior: 'smooth' });
  }

  async function saveQuestion() {
    const bubbles = getBubblesDataClean();
    if (!bubbles.length) { showToast('error', 'Add at least one bubble'); return; }

    autoArrangeAnswers(); // ✅ ensure latest order before saving

    const answerItems = Array.from(elements.answerOrderList?.querySelectorAll('.answer-item') || []);
    const answerKeys = answerItems.map(x => x.dataset.key).filter(Boolean);

    const bubbleEls = getBubbleEls();
    const keyToIndex = new Map(bubbleEls.map((b, idx) => [b.dataset.key, idx]));

    const answerValues = [];
    const answerSequence = [];

    for (const key of answerKeys) {
      const idx = keyToIndex.get(key);
      if (typeof idx !== 'number') continue;

      const eq = bubbleEls[idx]?.querySelector('.bubble-label-input')?.value?.trim() || '';
      const ev = safeEvalEquation(eq);

      if (!ev.ok) { showToast('error', `Invalid equation in Bubble #${idx + 1}. Please fix it before saving.`); return; }

      answerSequence.push(idx);
      answerValues.push(ev.value);
    }

    if (!answerSequence.length) { showToast('error', 'Correct order list is empty.'); return; }

    const payload = {
      title: elements.qTitle.value.trim() || null,
      select_type: elements.qSelectType.value,
      bubbles_json: bubbles,
      points: parseInt(elements.qPoints.value) || 1,
      order_no: parseInt(elements.qOrder.value) || 1,
      status: elements.qStatus.value,
      answer_sequence_json: answerSequence,
      answer_value_json: answerValues
    };

    const saveBtn = elements.btnSave;
    const original = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Saving...';

    showLoader(true);

    const url = editingId
      ? `/api/bubble-games/${gameUuid}/questions/${editingId}`
      : `/api/bubble-games/${gameUuid}/questions`;

    const method = editingId ? 'PUT' : 'POST';
    const res = await apiFetch(url, { method, body: JSON.stringify(payload) });

    showLoader(false);
    saveBtn.disabled = false;
    saveBtn.innerHTML = original;

    if (!res.ok) {
      const msg = res.data?.errors ? Object.values(res.data.errors).flat().join(', ') : (res.data?.message || 'Failed to save question');
      showToast('error', msg);
      return;
    }

    showToast('success', editingId ? 'Question updated successfully' : 'Question created successfully');
    await loadQuestions();
    if (!editingId) resetForm();
  }

  async function deleteQuestion(questionUuid) {
    const result = await Swal.fire({
      title: 'Delete Question?',
      text: 'This action cannot be undone',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Delete',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#ef4444'
    });
    if (!result.isConfirmed) return;

    showLoader(true);
    const res = await apiFetch(`/api/bubble-games/${gameUuid}/questions/${questionUuid}`, { method: 'DELETE' });
    showLoader(false);

    if (!res.ok) { showToast('error', 'Failed to delete question'); return; }

    showToast('success', 'Question deleted successfully');
    await loadQuestions();
    if (editingId === questionUuid) resetForm();
  }

  // ========= Events =========
  elements.btnAddBubble.addEventListener('click', () => addBubble('(2+3)*4', ''));
  elements.btnNewQuestion.addEventListener('click', resetForm);

  elements.btnSave.addEventListener('click', (e) => { e.preventDefault(); saveQuestion(); });
  elements.btnCancel.addEventListener('click', resetForm);

  elements.btnDelete.addEventListener('click', () => { if (editingId) deleteQuestion(editingId); });

  function closePreview() {
    if (!elements.previewOverlay) return;
    elements.previewOverlay.style.display = 'none';
    document.body.style.overflow = '';
  }
  elements.previewCloseBtn?.addEventListener('click', closePreview);
  elements.previewCloseBtn2?.addEventListener('click', closePreview);
  elements.previewOverlay?.addEventListener('click', (e) => { if (e.target === elements.previewOverlay) closePreview(); });

  elements.qSearch.addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase().trim();
    document.querySelectorAll('.question-item').forEach(item => {
      const title = item.querySelector('.q-title')?.textContent?.toLowerCase() || '';
      item.style.display = (!searchTerm || title.includes(searchTerm)) ? '' : 'none';
    });
  });

  function init() {
    console.log('Init bubble question page. gameUuid=', gameUuid);
    setBubblesData([{ label: '(1+2)*3', value: '' }]);
    resetForm();
    loadGameData();
  }

  init();
});
  </script>
@endpush
