
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
        .layout-grid{ 
            grid-template-columns: 1fr; 
        } 
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
    .question-list{ 
        padding: 0; 
        margin: 0; 
        list-style: none; 
    }
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
    .question-item:hover{ 
        background: var(--bg-gray); 
    }
    .question-item.active{ 
        background: #eef2ff; 
        border-left: 3px solid var(--primary);
    }
    html.theme-dark .question-item.active{ 
        background: #312e81; 
    }
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
    .q-content{
        flex: 1;
        min-width: 0;
    }
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
    .q-badge.bubbles{
        background: #dbeafe;
        color: #1e40af;
    }
    .q-badge.points{
        background: #dcfce7;
        color: #166534;
    }
    .q-badge.type-asc{
        background: #f0f9ff;
        color: #0369a1;
    }
    .q-badge.type-desc{
        background: #fef7cd;
        color: #92400e;
    }
    .question-menu{
        position: relative;
    }
    .menu-btn{
        width: 24px;
        height: 24px;
        border: none;
        background: transparent;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: var(--muted);
        transition: all 0.2s;
    }
    .menu-btn:hover{
        background: var(--bg-gray);
        color: var(--ink);
    }
    .menu-dropdown{
        position: absolute;
        top: 100%;
        right: 0;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        z-index: 100;
        min-width: 120px;
        display: none;
    }
    .menu-dropdown.show{
        display: block;
    }
    .menu-item{
        padding: 8px 12px;
        font-size: 13px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
        white-space: nowrap;
    }
    .menu-item:hover{
        background: var(--bg-gray);
    }
    .menu-item.view{ color: var(--secondary); }
    .menu-item.edit{ color: var(--primary); }
    .menu-item.delete{ color: var(--danger); }

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
    .content-header-actions{
        display: flex;
        gap: 8px;
        align-items: center;
    }
    .content-body{
        padding: 24px;
        min-height: 500px;
        position: relative;
    }

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
        gap: 8px;
    }
    .row{ 
        display: flex; 
        gap: 16px; 
        margin-bottom: 20px; 
    }
    .col{ 
        flex: 1; 
    }
    .form-group{ 
        margin-bottom: 20px; 
    }
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
    .bubble-handle{
        color: var(--muted);
        cursor: move;
        font-size: 14px;
    }
    .bubble-inputs{
        flex: 1;
        display: flex;
        gap: 12px;
    }
    .bubble-label{
        flex: 2;
    }
    .bubble-value{
        flex: 1;
    }
    .bubble-actions{
        display: flex;
        gap: 8px;
    }
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
    .bubble-btn:hover{
        background: var(--bg-gray);
    }
    .bubble-btn.delete:hover{
        background: #fee;
        color: var(--danger);
    }
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
    .add-bubble-btn:hover{
        border-color: var(--primary);
        background: #f5f3ff;
    }

    /* JSON Editors */
    .json-editor{
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 8px;
        overflow: hidden;
        margin-bottom: 16px;
    }
    .json-header{
        padding: 12px 16px;
        background: var(--bg-gray);
        border-bottom: 1px solid var(--border);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .json-title{
        font-size: 13px;
        font-weight: 600;
        color: var(--ink);
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .json-help{
        font-size: 12px;
        color: var(--muted);
        cursor: help;
    }
    .json-area{
        width: 100%;
        min-height: 100px;
        padding: 12px;
        border: none;
        background: var(--surface);
        color: var(--ink);
        font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
        font-size: 13px;
        line-height: 1.5;
        resize: vertical;
        outline: none;
    }
    .json-area:focus{
        background: var(--bg-gray);
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
    .btn-primary{
        background: var(--primary);
        color: white;
    }
    .btn-primary:hover{
        background: #4338ca;
        transform: translateY(-1px);
    }
    .btn-secondary{
        background: var(--secondary);
        color: white;
    }
    .btn-secondary:hover{
        background: #0284c7;
        transform: translateY(-1px);
    }
    .btn-light{
        background: var(--surface);
        color: var(--ink);
        border: 1px solid var(--border);
    }
    .btn-light:hover{
        background: var(--bg-gray);
        border-color: var(--primary);
    }
    .btn-danger{
        background: var(--danger);
        color: white;
    }
    .btn-danger:hover{
        background: #dc2626;
    }
    .btn-sm{
        padding: 6px 12px;
        font-size: 13px;
    }

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
    html.theme-dark .loader-overlay{
        background: rgba(0,0,0,0.6);
    }
    .loader{
        width: 40px;
        height: 40px;
        border: 4px solid var(--border);
        border-top: 4px solid var(--primary);
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    @keyframes spin{
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

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
    .toast.show{ 
        display: flex; 
    }
    .toast.success{
        background: var(--success);
        color: white;
    }
    .toast.error{
        background: var(--danger);
        color: white;
    }
    @keyframes slideIn{
        from{ transform: translateX(100%); opacity: 0; }
        to{ transform: translateX(0); opacity: 1; }
    }

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
    .preview-chips{
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    .preview-chip{
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
    }
    .preview-body{
        padding: 20px;
        overflow-y: auto;
        flex: 1;
    }
    .preview-footer{
        padding: 16px 20px;
        border-top: 1px solid var(--border);
        display: flex;
        justify-content: flex-end;
        gap: 8px;
    }

    /* Bubble Preview */
    .bubbles-preview{
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin: 20px 0;
    }
    .bubble-preview{
        min-width: 100px;
        padding: 16px;
        background: linear-gradient(135deg, #818cf8, #6366f1);
        color: white;
        border-radius: 12px;
        text-align: center;
        font-weight: 600;
        font-size: 14px;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        position: relative;
    }
    .bubble-label-preview{
        font-size: 12px;
        opacity: 0.9;
        margin-bottom: 4px;
    }
    .bubble-value-preview{
        font-size: 16px;
    }
    .bubble-index{
        position: absolute;
        top: -8px;
        right: -8px;
        width: 24px;
        height: 24px;
        background: var(--primary);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 600;
    }

    /* Answer Preview */
    .answer-preview{
        margin-top: 24px;
        padding: 16px;
        background: var(--bg-gray);
        border-radius: 8px;
        border: 1px solid var(--border);
    }
    .answer-title{
        font-size: 14px;
        font-weight: 600;
        margin: 0 0 12px;
        color: var(--ink);
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .answer-sequence{
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 12px;
    }
    .seq-item{
        padding: 8px 12px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 6px;
        font-family: monospace;
        font-size: 13px;
    }

    /* Drag & Drop */
    .sortable-ghost{
        opacity: 0.4;
        background: #f0f0f0;
    }
    .sortable-drag{
        opacity: 0.8;
        transform: rotate(5deg);
    }

    /* Scrollbar */
    .sidebar-body::-webkit-scrollbar,
    .preview-body::-webkit-scrollbar{
        width: 6px;
    }
    .sidebar-body::-webkit-scrollbar-track,
    .preview-body::-webkit-scrollbar-track{
        background: transparent;
    }
    .sidebar-body::-webkit-scrollbar-thumb,
    .preview-body::-webkit-scrollbar-thumb{
        background: var(--border);
        border-radius: 3px;
    }

    /* Responsive */
    @media (max-width: 768px){
        .row{ flex-direction: column; gap: 12px; }
        .bubble-inputs{ flex-direction: column; }
        .game-header-top{ flex-direction: column; gap: 16px; }
        .content-header{ flex-direction: column; gap: 12px; align-items: flex-start; }
        .content-footer{ flex-direction: column; }
        .content-footer .btn{ width: 100%; }
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
                        <button id="btnPreview" class="btn btn-secondary btn-sm">
                            <i class="fa fa-eye"></i> Preview
                        </button>
                    </div>
                </div>

                <div class="content-body">
                    <form id="qForm" novalidate>
                        <input type="hidden" id="qId">
                        <!-- Get gameUuid from query parameter -->
<input type="hidden" id="gameUuid" value="{{ request()->query('game') ?? request()->query('game_uuid') ?? request()->query('uuid') ?? request()->query('id') }}">

                        <!-- Basic Information -->
                        <div class="section-title">
                            <i class="fa fa-info-circle"></i> Basic Information
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
                            <i class="fa fa-circle-nodes"></i> Bubbles
                            <span class="ml-2 text-sm text-muted" id="bubblesCount">0 bubbles</span>
                        </div>
                        
                        <div class="bubbles-editor">
                            <div class="form-group">
                                <label class="form-label">Bubble List</label>
                                <p class="text-sm text-muted mb-3">Drag to reorder bubbles. Each bubble should have a label and an optional value.</p>
                                
                                <div id="bubblesList" class="bubbles-list">
                                    <!-- Bubbles will be generated here -->
                                </div>
                                
                                <button type="button" id="btnAddBubble" class="add-bubble-btn">
                                    <i class="fa fa-plus"></i> Add Bubble
                                </button>
                            </div>
                        </div>

                        <!-- Answer Sequence -->
                        <div class="section-title">
                            <i class="fa fa-list-ol"></i> Answer Configuration
                        </div>
                        
                        <div class="row">
                            <div class="col">
                                <div class="json-editor">
                                    <div class="json-header">
                                        <div class="json-title">
                                            <i class="fa fa-arrow-up-1-9"></i>
                                            Answer Sequence (Optional)
                                            <i class="fa fa-info-circle json-help" title="JSON array of indices representing the correct sequence"></i>
                                        </div>
                                    </div>
                                    <textarea id="answerSequence" class="json-area" placeholder="[0, 1, 2, 3]"></textarea>
                                </div>
                            </div>
                            <div class="col">
                                <div class="json-editor">
                                    <div class="json-header">
                                        <div class="json-title">
                                            <i class="fa fa-hashtag"></i>
                                            Answer Values (Optional)
                                            <i class="fa fa-info-circle json-help" title="JSON array of values for the correct sequence"></i>
                                        </div>
                                    </div>
                                    <textarea id="answerValues" class="json-area" placeholder='["value1", "value2", "value3"]'></textarea>
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
                                        <strong>Ascending</strong>: Bubbles should be arranged from smallest to largest value.<br>
                                        <strong>Descending</strong>: Bubbles should be arranged from largest to smallest value.<br>
                                        <strong>Answer Sequence</strong>: Define the correct order of bubble indices (0-based).<br>
                                        <strong>Answer Values</strong>: Define the correct values in sequence (optional).
                                    </p>
                                    <p class="text-xs text-blue-600">
                                        Tip: Drag bubbles to reorder them. The order shown here is the initial display order.
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

  // DOM Elements (safe)
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
    answerSequence: document.getElementById('answerSequence'),
    answerValues: document.getElementById('answerValues'),
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
  };

  // ========= Helpers =========
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

  function parseJsonSafe(jsonString) {
    try { return jsonString ? JSON.parse(jsonString) : null; }
    catch(e){ console.error(e); return null; }
  }

  function formatJson(json) {
    try { return JSON.stringify(json, null, 2); }
    catch(e){ return json || ''; }
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

    // handle bad strings
    if (['null','undefined','0'].includes((v || '').toLowerCase())) v = '';

    // optional: extract from path like /bubble-games/{uuid}/questions
    if (!v) {
      const m = url.pathname.match(/bubble-games\/([^\/]+)\//i);
      if (m && m[1]) v = m[1].trim();
    }

    // keep hidden field synced
    const hidden = document.getElementById('gameUuid');
    if (hidden && v) hidden.value = v;

    return v;
  }

  async function apiFetch(url, options = {}) {
    const headers = {
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      ...options.headers
    };

    if (TOKEN) headers['Authorization'] = `Bearer ${TOKEN}`;
    if (!(options.body instanceof FormData) && !headers['Content-Type']) {
      headers['Content-Type'] = 'application/json';
    }

    try {
      const res = await fetch(url, { ...options, headers });

      let data = null;
      const contentType = res.headers.get('content-type') || '';

      if (contentType.includes('application/json')) {
        try { data = await res.json(); } catch(e) { data = null; }
      } else {
        // could be html/text
        try { data = await res.text(); } catch(e) { data = null; }
      }

      // handle auth errors even if JSON
      if (res.status === 401 || res.status === 419) {
        showToast('error', 'Session expired. Please login again.');
        setTimeout(() => window.location.href = '/login', 1500);
        return { ok: false, status: res.status, data };
      }

      return { ok: res.ok, status: res.status, data };
    } catch (err) {
      console.error('API Error:', err);
      showToast('error', 'Network error. Please check your connection.');
      return { ok: false, status: 0, data: null };
    }
  }

  // ========= State =========
  let gameUuid = resolveGameUuid();
  let gameData = null;
  let questions = [];
  let editingId = null;
  let currentQuestion = null;

  // ========= Early guard (also stop infinite loading UI) =========
  if (!gameUuid) {
    // replace spinner with a clear message
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
      document.getElementById('goGamesBtn')?.addEventListener('click', () => {
        window.location.href = '/bubblegame';
      });
    }
    showToast('error', 'Game ID is required. Please select a game first.');
    return;
  }

  // ========= Sortable =========
  let sortable;
  function initSortable() {
    if (!elements.bubblesList) return;
    if (sortable) sortable.destroy();
    sortable = Sortable.create(elements.bubblesList, {
      animation: 150,
      ghostClass: 'sortable-ghost',
      dragClass: 'sortable-drag',
      handle: '.bubble-handle',
      onEnd: function() { updateBubblesCount(); }
    });
  }

  // ========= Bubble management =========
  function createBubbleElement(index, label = '', value = '') {
    const div = document.createElement('div');
    div.className = 'bubble-item';
    div.dataset.index = index;
    div.innerHTML = `
      <div class="bubble-handle"><i class="fa fa-grip-vertical"></i></div>
      <div class="bubble-inputs">
        <div class="bubble-label">
          <input type="text" class="form-control bubble-label-input" placeholder="Bubble label" value="${escapeHtml(label)}">
        </div>
        <div class="bubble-value">
          <input type="text" class="form-control bubble-value-input" placeholder="Value (optional)" value="${escapeHtml(value)}">
        </div>
      </div>
      <div class="bubble-actions">
        <button type="button" class="bubble-btn delete" title="Delete bubble"><i class="fa fa-trash"></i></button>
      </div>
    `;

    div.querySelector('.bubble-btn.delete')?.addEventListener('click', () => {
      if (elements.bubblesList.children.length > 1) {
        div.remove();
        updateBubblesCount();
      } else {
        showToast('error', 'At least one bubble is required');
      }
    });

    return div;
  }

  function addBubble(label = '', value = '') {
    if (!elements.bubblesList) return;
    const index = elements.bubblesList.children.length;
    elements.bubblesList.appendChild(createBubbleElement(index, label, value));
    updateBubblesCount();
  }

  function updateBubblesCount() {
    if (!elements.bubblesList || !elements.bubblesCount) return;
    const count = elements.bubblesList.children.length;
    elements.bubblesCount.textContent = `${count} bubble${count !== 1 ? 's' : ''}`;
  }

  function getBubblesData() {
    const bubbles = [];
    elements.bubblesList?.querySelectorAll('.bubble-item')?.forEach(item => {
      const label = item.querySelector('.bubble-label-input')?.value?.trim() || '';
      const value = item.querySelector('.bubble-value-input')?.value?.trim() || '';
      if (label) bubbles.push({ label, value: value || null });
    });
    return bubbles;
  }

  function setBubblesData(bubbles) {
    if (!elements.bubblesList) return;
    elements.bubblesList.innerHTML = '';
    (bubbles || []).forEach(b => addBubble(b.label, b.value || ''));
    if (!bubbles || !bubbles.length) addBubble('Bubble 1', '1');
    initSortable();
    updateBubblesCount();
  }

  // ========= Game & Questions =========
  function updateGameHeader() {
    if (!gameData) return;
    elements.gameTitle.textContent = gameData.title || 'Untitled Game';
    elements.gameDesc.textContent = gameData.description || 'No description provided';
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
    elements.answerSequence.value = '';
    elements.answerValues.value = '';

    setBubblesData([{ label: 'Bubble 1', value: '1' }]);

    elements.formTitle.textContent = 'New Question';
    elements.btnDelete.style.display = 'none';

    document.querySelectorAll('.question-item').forEach(i => i.classList.remove('active'));
  }

  async function loadGameData() {
    showLoader(true);

    // ⚠️ Make sure this endpoint matches your routes
    const res = await apiFetch(`/api/bubble-games/${gameUuid}`);

    showLoader(false);

    if (!res.ok) {
      // stop infinite loading UI
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

    // supports: {success:true,data:{...}} OR {...}
    gameData = res.data?.data || res.data;
    updateGameHeader();

    await loadQuestions();
  }

  async function loadQuestions() {
    showLoader(true);

    // ⚠️ Make sure this endpoint matches your routes
    const res = await apiFetch(`/api/bubble-games/${gameUuid}/questions?paginate=false`);

    showLoader(false);

    if (!res.ok) {
      // replace spinner so it doesn't look stuck
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

    // supports:
    // 1) {success:true,data:[...]}
    // 2) {data:[...]}
    // 3) direct array
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

    if (!res.ok) {
      showToast('error', 'Failed to load question');
      return;
    }

    currentQuestion = res.data?.data || res.data;
    editingId = questionUuid;

    elements.qId.value = currentQuestion.uuid;
    elements.qTitle.value = currentQuestion.title || '';
    elements.qSelectType.value = currentQuestion.select_type || 'ascending';
    elements.qPoints.value = currentQuestion.points || 1;
    elements.qOrder.value = currentQuestion.order_no || 1;
    elements.qStatus.value = currentQuestion.status || 'active';

    if (Array.isArray(currentQuestion.bubbles_json)) setBubblesData(currentQuestion.bubbles_json);
    else setBubblesData([{ label: 'Bubble 1', value: '1' }]);

    elements.answerSequence.value = currentQuestion.answer_sequence_json ? formatJson(currentQuestion.answer_sequence_json) : '';
    elements.answerValues.value = currentQuestion.answer_value_json ? formatJson(currentQuestion.answer_value_json) : '';

    elements.formTitle.textContent = `Edit Question #${currentQuestion.order_no || ''}`;
    elements.btnDelete.style.display = 'inline-flex';

    document.querySelectorAll('.question-item').forEach(item => {
      item.classList.toggle('active', item.dataset.id === questionUuid);
    });

    elements.qForm.scrollIntoView({ behavior: 'smooth' });
  }

  async function saveQuestion() {
    const bubbles = getBubblesData();
    if (!bubbles.length) {
      showToast('error', 'Add at least one bubble');
      return;
    }

    let answerSequence = null;
    let answerValues = null;

    if (elements.answerSequence.value.trim()) {
      answerSequence = parseJsonSafe(elements.answerSequence.value);
      if (!Array.isArray(answerSequence)) {
        showToast('error', 'Answer sequence must be a JSON array');
        return;
      }
    }
    if (elements.answerValues.value.trim()) {
      answerValues = parseJsonSafe(elements.answerValues.value);
      if (!Array.isArray(answerValues)) {
        showToast('error', 'Answer values must be a JSON array');
        return;
      }
    }

    const payload = {
      title: elements.qTitle.value.trim() || null,
      select_type: elements.qSelectType.value,
      bubbles_json: bubbles,
      points: parseInt(elements.qPoints.value) || 1,
      order_no: parseInt(elements.qOrder.value) || 1,
      status: elements.qStatus.value
    };
    if (answerSequence) payload.answer_sequence_json = answerSequence;
    if (answerValues) payload.answer_value_json = answerValues;

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
      const msg = res.data?.errors
        ? Object.values(res.data.errors).flat().join(', ')
        : (res.data?.message || 'Failed to save question');
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

    if (!res.ok) {
      showToast('error', 'Failed to delete question');
      return;
    }

    showToast('success', 'Question deleted successfully');
    await loadQuestions();
    if (editingId === questionUuid) resetForm();
  }

  // ========= Events =========
  elements.btnAddBubble.addEventListener('click', () => {
    const index = (elements.bubblesList?.children?.length || 0) + 1;
    addBubble(`Bubble ${index}`, index.toString());
  });

  elements.btnNewQuestion.addEventListener('click', resetForm);

  elements.btnSave.addEventListener('click', (e) => {
    e.preventDefault();
    saveQuestion();
  });

  elements.btnCancel.addEventListener('click', resetForm);

  elements.btnDelete.addEventListener('click', () => {
    if (editingId) deleteQuestion(editingId);
  });

  function closePreview() {
    if (!elements.previewOverlay) return;
    elements.previewOverlay.style.display = 'none';
    document.body.style.overflow = '';
  }
  elements.previewCloseBtn?.addEventListener('click', closePreview);
  elements.previewCloseBtn2?.addEventListener('click', closePreview);
  elements.previewOverlay?.addEventListener('click', (e) => {
    if (e.target === elements.previewOverlay) closePreview();
  });

  elements.qSearch.addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase().trim();
    document.querySelectorAll('.question-item').forEach(item => {
      const title = item.querySelector('.q-title')?.textContent?.toLowerCase() || '';
      item.style.display = (!searchTerm || title.includes(searchTerm)) ? '' : 'none';
    });
  });

  // ========= Init =========
  function init() {
    console.log('Init bubble question page. gameUuid=', gameUuid);
    setBubblesData([{ label: 'Bubble 1', value: '1' }]);
    resetForm();
    loadGameData();
  }

  init();
});
</script>

@endpush