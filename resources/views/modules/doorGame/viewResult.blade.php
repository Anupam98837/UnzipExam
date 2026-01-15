{{-- resources/views/door-game/doorGameResultStandalone.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>

  <title>Door Game Result</title>

  <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/media/images/favicons/favicon.png') }}">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="{{ asset('/assets/css/common/main.css') }}">

  <style id="erStyles">
    .er-wrap{max-width:1100px;margin:16px auto 40px;}
    .er-shell{
      border-radius:16px;border:1px solid var(--line-strong);
      background:var(--surface);box-shadow:var(--shadow-2);
      padding:16px 18px 18px;position:relative;
    }
    .er-head{display:flex;align-items:flex-start;gap:14px;margin-bottom:12px;}
    .er-title{font-family:var(--font-head);font-weight:700;color:var(--ink);font-size:1.25rem;margin:0;}
    .er-sub{font-size:var(--fs-13);color:var(--muted-color);margin-top:3px;}
    .er-actions{margin-left:auto;display:flex;flex-wrap:wrap;gap:8px;}
    .er-actions .btn{border-radius:999px;padding-inline:12px;}
    .er-actions .btn i{margin-right:6px;}
    .er-row{margin-top:10px;}

    .er-card{
      border-radius:14px;border:1px solid var(--line-strong);
      background:var(--surface-2);padding:12px 12px 10px;
      box-shadow:var(--shadow-1);
    }
    .er-card-head{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:8px;}
    .er-card-title{font-family:var(--font-head);font-weight:600;color:var(--ink);font-size:.95rem;margin:0;}

    .er-chip{
      display:inline-flex;align-items:center;gap:6px;
      padding:3px 8px;border-radius:999px;font-size:11px;
      border:1px solid var(--line-strong);background:var(--surface);
      color:var(--muted-color);
    }
    .er-chip i{font-size:10px;}
    .er-chip-primary{
      background:var(--t-primary);
      border-color:rgba(20,184,166,.25);
      color:#0f766e;
    }

    .er-score-main{display:flex;align-items:center;gap:14px;margin-bottom:10px;}
    .er-score-circle{
      width:72px;height:72px;border-radius:50%;
      border:5px solid rgba(20,184,166,.16);
      display:flex;align-items:center;justify-content:center;flex-direction:column;
      font-family:var(--font-head);color:var(--ink);position:relative;
    }
    .er-score-circle::after{
      content:"";position:absolute;inset:6px;border-radius:inherit;
      border:3px solid var(--accent-color);opacity:.5;
    }
    .er-score-value{font-size:1.25rem;font-weight:700;}
    .er-score-label{font-size:11px;color:var(--muted-color);}
    .er-score-text{font-size:var(--fs-13);color:var(--muted-color);}
    .er-score-text strong{font-weight:600;}

    .er-metrics{
      display:grid;grid-template-columns:repeat(3,minmax(0,1fr));
      gap:8px;margin-top:4px;
    }
    .er-metric{
      border-radius:10px;background:var(--surface);
      border:1px dashed var(--line-strong);
      padding:6px 8px;font-size:var(--fs-12);
    }
    .er-metric-label{color:var(--muted-color);margin-bottom:3px;}
    .er-metric-value{font-weight:600;color:var(--ink);}

    .er-bar-wrap{margin-top:4px;}
    .er-bar-bg{width:100%;height:8px;border-radius:999px;background:#e5eff0;overflow:hidden;}
    .er-bar-fill{height:100%;border-radius:inherit;background:var(--accent-color);width:0%;transition:width .4s ease;}
    .er-bar-label{font-size:var(--fs-12);color:var(--muted-color);margin-top:2px;}

    .er-pill-row{display:flex;flex-wrap:wrap;gap:6px;font-size:var(--fs-12);}
    .er-pill{
      padding:4px 8px;border-radius:999px;border:1px solid var(--line-strong);
      display:inline-flex;align-items:center;gap:5px;background:var(--surface);
    }
    .er-pill i{font-size:11px;}
    .er-pill-green{background:var(--t-success);border-color:rgba(22,163,74,.25);color:#15803d;}
    .er-pill-red{background:var(--t-danger);border-color:rgba(220,38,38,.25);color:#b91c1c;}
    .er-pill-gray{background:var(--surface-3);}

    .er-table-card{
      margin-top:14px;border-radius:14px;border:1px solid var(--line-strong);
      background:var(--surface-2);box-shadow:var(--shadow-1);
      padding:10px 12px 12px;
    }
    .er-table-head{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px;}
    .er-table-title{font-family:var(--font-head);font-weight:600;color:var(--ink);font-size:1rem;margin:0;}
    .er-table-sub{font-size:var(--fs-13);color:var(--muted-color);}

    .er-empty{
      margin-top:8px;border:1px dashed var(--line-strong);
      border-radius:10px;padding:16px;text-align:center;
      font-size:var(--fs-13);color:var(--muted-color);
      background:var(--surface-2);
    }

    .er-loader-wrap{
      position:absolute;inset:0;display:none;align-items:center;justify-content:center;
      background:rgba(0,0,0,.04);z-index:5;
    }
    .er-loader-wrap.show{display:flex;}
    .er-loader{
      width:22px;height:22px;border-radius:50%;
      border:3px solid #0001;border-top-color:var(--accent-color);
      animation:er-rot 1s linear infinite;
    }
    @keyframes er-rot{to{transform:rotate(360deg)}}
    .er-error{margin-top:8px;font-size:12px;color:var(--danger-color);display:none;}
    .er-error.show{display:block;}

    /* === Move list cards === */
    .er-q-list{margin-top:8px;display:flex;flex-direction:column;gap:8px;}
    .er-qcard{
      border-radius:12px;border:1px solid var(--line-strong);
      background:var(--surface);padding:8px 9px 8px;box-shadow:var(--shadow-1);
    }
    .er-qcard-head{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:4px;}
    .er-q-left{display:flex;align-items:center;gap:8px;}
    .er-q-badge{
      min-width:44px;height:22px;border-radius:999px;border:1px solid var(--line-strong);
      background:var(--surface-3);display:flex;align-items:center;justify-content:center;
      font-size:11px;color:var(--muted-color);
    }
    .er-q-meta{display:flex;flex-wrap:wrap;gap:8px;justify-content:flex-end;font-size:11px;color:var(--muted-color);}
    .er-q-meta span strong{color:var(--ink);}

    .er-q-question-main{font-size:var(--fs-13);color:var(--text-color);}
    .er-qcard-answers{
      margin-top:6px;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;
    }
    @media (max-width: 768px){ .er-qcard-answers{grid-template-columns:1fr;} }
    .er-q-answer-block{
      border-radius:10px;border:1px solid var(--line-strong);
      background:var(--surface-2);padding:6px 8px;font-size:11px;
    }
    .er-q-answer-block.correct{
      background:var(--t-success);border-color:rgba(22,163,74,.35);color:#14532d;
    }
    .er-q-answer-block.your{border-style:dashed;}
    .er-q-answer-label{
      font-weight:600;text-transform:uppercase;letter-spacing:.03em;
      margin-bottom:2px;color:var(--muted-color);
    }
    .er-q-answer-text{font-size:var(--fs-12);color:var(--text-color);word-break:break-word;}

    @media print{
      #sidebar,.w3-sidebar,.w3-appbar,#sidebarOverlay{display:none!important;}
      body{background:#fff!important;}
      main.w3-content{max-width:100%!important;padding:0!important;margin:0!important;}
      .panel{border:none!important;box-shadow:none!important;padding:0!important;}
      .er-wrap{margin:0!important;max-width:100%!important;}
      .er-actions{display:none!important;}
    }

    html.theme-dark .er-shell,
    html.theme-dark .er-card,
    html.theme-dark .er-table-card{background:#04151f;}
    html.theme-dark .er-empty{background:#020b13;}
    html.theme-dark .er-qcard{background:#020b13;}
  </style>
</head>

<body>
  <div class="er-wrap">
    <div id="resultShell"
         class="er-shell"
         data-result-id="{{ $resultId }}">

      <div class="er-loader-wrap" id="erLoader">
        <div class="er-loader"></div>
      </div>

      <div class="er-head">
        <img id="logo" src="{{ asset('/assets/media/images/web/logo.png') }}" alt="Unzip Examination" style="height:50px;width:auto;">

        <div>
          <h1 class="er-title" id="erGameTitle">Door Game Result</h1>
          <div class="er-sub" id="erAttemptMeta">Loading attempt details...</div>
        </div>

        <div class="er-actions">
          <button type="button" class="btn btn-primary btn-sm" id="erPdfExport">
            <i class="fa-regular fa-file-pdf"></i> Export PDF
          </button>
        </div>
      </div>

      <div class="row g-3 er-row">
        <div class="col-md-7">
          <div class="er-card">
            <div class="er-card-head">
              <h2 class="er-card-title">Attempt summary</h2>
              <span class="er-chip er-chip-primary" id="erScoreChip">
                <i class="fa-solid fa-award"></i> Overall
              </span>
            </div>

            <div class="er-score-main">
              <div class="er-score-circle">
                <div class="er-score-value" id="erPercent">0%</div>
                <div class="er-score-label">Success</div>
              </div>
              <div class="er-score-text" id="erScoreText">
                Your attempt summary will appear here once the data is loaded.
              </div>
            </div>

            <div class="er-metrics">
              <div class="er-metric">
                <div class="er-metric-label">Score</div>
                <div class="er-metric-value" id="erMarks">0</div>
              </div>
              <div class="er-metric">
                <div class="er-metric-label">Moves</div>
                <div class="er-metric-value" id="erAttempted">0</div>
              </div>
              <div class="er-metric">
                <div class="er-metric-label">Time spent</div>
                <div class="er-metric-value" id="erTimeSpent">-</div>
              </div>
            </div>

            <div class="er-bar-wrap">
              <div class="er-bar-bg">
                <div class="er-bar-fill" id="erScoreBar"></div>
              </div>
              <div class="er-bar-label" id="erBarLabel">
                Success: 0%
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-5">
          <div class="er-card">
            <div class="er-card-head">
              <h2 class="er-card-title">Status & key events</h2>
              <span class="er-chip" id="erAttemptChip">
                <i class="fa-regular fa-circle-check"></i>
                Attempt
              </span>
            </div>

            <div class="er-pill-row mb-2">
              <span class="er-pill er-pill-green">
                <i class="fa-solid fa-key"></i>
                <span id="erKeyPicked">Key: -</span>
              </span>
              <span class="er-pill er-pill-red">
                <i class="fa-solid fa-door-open"></i>
                <span id="erDoorOpened">Door: -</span>
              </span>
              <span class="er-pill er-pill-gray">
                <i class="fa-regular fa-clock"></i>
                <span id="erTimeoutInfo">Timeout: -</span>
              </span>
            </div>

            <ul class="mb-0 small text-muted" id="erMetaList">
              <li>Result ID: <span id="erResultId">-</span></li>
              <li>Attempt no: <span id="erAttemptNo">-</span></li>
              <li>Submitted at: <span id="erSubmittedAt">-</span></li>
            </ul>
          </div>
        </div>
      </div>

      <div class="er-table-card mt-3">
        <div class="er-table-head">
          <div>
            <h2 class="er-table-title">Move-wise analysis</h2>
            <div class="er-table-sub">
              Path + moves + key/door events from attempt snapshot.
            </div>
          </div>
        </div>

        <div id="erNoQuestions" class="er-empty d-none">
          No move-level data is available for this attempt.
        </div>

        <div id="erQuestionList" class="er-q-list">
          {{-- Filled by JS --}}
        </div>
      </div>

      <div id="erError" class="er-error"></div>

    </div>
  </div>

  <script>
  (function () {
    "use strict";

    function initResultPage() {
      var resultShell = document.getElementById("resultShell");
      if (!resultShell) return;

      var RESULT_ID = resultShell.dataset.resultId;
      if (!RESULT_ID) {
        var errEl = document.getElementById("erError");
        if (errEl) {
          errEl.textContent = "Result reference is missing.";
          errEl.classList.add("show");
        }
        console.error("Missing result id on resultShell");
        return;
      }

      var loaderEl    = document.getElementById("erLoader");
      var errorEl     = document.getElementById("erError");

      var gameTitleEl   = document.getElementById("erGameTitle");
      var attemptMetaEl = document.getElementById("erAttemptMeta");
      var scoreChipEl   = document.getElementById("erScoreChip");

      var percentEl   = document.getElementById("erPercent");
      var scoreTextEl = document.getElementById("erScoreText");
      var marksEl     = document.getElementById("erMarks");
      var attemptedEl = document.getElementById("erAttempted");
      var timeSpentEl = document.getElementById("erTimeSpent");
      var scoreBarEl  = document.getElementById("erScoreBar");
      var barLabelEl  = document.getElementById("erBarLabel");

      var keyPickedEl   = document.getElementById("erKeyPicked");
      var doorOpenedEl  = document.getElementById("erDoorOpened");
      var timeoutInfoEl = document.getElementById("erTimeoutInfo");

      var resultIdEl    = document.getElementById("erResultId");
      var attemptNoEl   = document.getElementById("erAttemptNo");
      var submittedAtEl = document.getElementById("erSubmittedAt");

      var questionListEl = document.getElementById("erQuestionList");
      var noQuestionsEl  = document.getElementById("erNoQuestions");

      var pdfBtn  = document.getElementById("erPdfExport");

      function getToken() {
        try { return sessionStorage.getItem("token") || localStorage.getItem("token") || null; }
        catch (e) { return null; }
      }

      function clearAuthStorage() {
        try { sessionStorage.removeItem("token"); } catch (e) {}
        try { sessionStorage.removeItem("role"); } catch (e) {}
        try { localStorage.removeItem("token"); } catch (e) {}
        try { localStorage.removeItem("role"); } catch (e) {}
      }

      function showLoader(show) {
        if (!loaderEl) return;
        if (show) loaderEl.classList.add("show");
        else loaderEl.classList.remove("show");
      }

      function showError(msg) {
        if (!errorEl) return;
        errorEl.textContent = msg || "Something went wrong while loading the result.";
        errorEl.classList.add("show");
      }

      function formatDateTime(str) {
        if (!str) return "-";
        var d = new Date(str);
        if (isNaN(d.getTime())) return str;
        return d.toLocaleString();
      }

      function formatDurationMs(ms) {
        var v = Number(ms || 0);
        if (!v) return "-";
        var sec = Math.round(v / 1000);
        var m = Math.floor(sec / 60);
        var s = sec % 60;
        if (m && s) return m + "m " + s + "s";
        if (m) return m + "m";
        return s + "s";
      }

      function safeJsonParse(raw, fallback) {
        try {
          if (raw == null) return fallback;
          if (typeof raw === "object") return raw;
          var s = String(raw).trim();
          if (!s) return fallback;
          return JSON.parse(s);
        } catch (e) {
          return fallback;
        }
      }

      function statusToChip(status) {
        var s = String(status || "").toLowerCase();
        if (s === "win") return { label: "WIN", icon: "trophy", pct: 100 };
        if (s === "fail") return { label: "FAIL", icon: "circle-xmark", pct: 0 };
        if (s === "timeout") return { label: "TIMEOUT", icon: "clock", pct: 0 };
        if (s === "auto_submitted") return { label: "AUTO", icon: "bolt", pct: 0 };
        return { label: (status || "ATTEMPT"), icon: "circle-check", pct: null };
      }

      function renderMoves(snapshot) {
        questionListEl.innerHTML = "";

        var moves = snapshot && Array.isArray(snapshot.moves) ? snapshot.moves : [];
        var path  = snapshot && Array.isArray(snapshot.path) ? snapshot.path : [];
        var events = snapshot && snapshot.events ? snapshot.events : {};

        if ((!moves || !moves.length) && (!path || !path.length) && (!events || !Object.keys(events).length)) {
          noQuestionsEl.classList.remove("d-none");
          questionListEl.classList.add("d-none");
          return;
        }

        noQuestionsEl.classList.add("d-none");
        questionListEl.classList.remove("d-none");

        var frag = document.createDocumentFragment();

        // Card: Path
        if (path && path.length) {
          var cardP = document.createElement("div");
          cardP.className = "er-qcard";
          cardP.innerHTML = `
            <div class="er-qcard-head">
              <div class="er-q-left">
                <span class="er-q-badge">PATH</span>
              </div>
              <div class="er-q-meta">
                <span>Steps: <strong>${path.length}</strong></span>
              </div>
            </div>
            <div class="er-q-question-main">Visited cells:</div>
            <div class="er-qcard-answers">
              <div class="er-q-answer-block correct">
                <div class="er-q-answer-label">Sequence</div>
                <div class="er-q-answer-text">${path.join(" → ")}</div>
              </div>
              <div class="er-q-answer-block your">
                <div class="er-q-answer-label">Start index</div>
                <div class="er-q-answer-text">${snapshot.start_index != null ? snapshot.start_index : "—"}</div>
              </div>
            </div>
          `;
          frag.appendChild(cardP);
        }

        // Cards: Moves
        if (moves && moves.length) {
          moves.forEach(function (m, i) {
            var from = (m && m.from != null) ? m.from : "—";
            var to   = (m && m.to != null) ? m.to : "—";
            var tms  = (m && m.t_ms != null) ? m.t_ms : null;

            var card = document.createElement("div");
            card.className = "er-qcard";

            card.innerHTML = `
              <div class="er-qcard-head">
                <div class="er-q-left">
                  <span class="er-q-badge">MOVE ${i+1}</span>
                </div>
                <div class="er-q-meta">
                  <span>t(ms): <strong>${tms != null ? tms : "—"}</strong></span>
                </div>
              </div>

              <div class="er-q-question-main">
                Movement: <strong>${from}</strong> → <strong>${to}</strong>
              </div>

              <div class="er-qcard-answers">
                <div class="er-q-answer-block correct">
                  <div class="er-q-answer-label">From</div>
                  <div class="er-q-answer-text">${from}</div>
                </div>
                <div class="er-q-answer-block your">
                  <div class="er-q-answer-label">To</div>
                  <div class="er-q-answer-text">${to}</div>
                </div>
              </div>
            `;
            frag.appendChild(card);
          });
        }

        // Card: Events
        var hasKey = events && events.key;
        var hasDoor = events && events.door;
        if (hasKey || hasDoor) {
          var keyTxt = "—";
          var doorTxt = "—";
          if (hasKey) keyTxt = "Picked at index " + (events.key.picked_at_index ?? "—") + " (t_ms " + (events.key.t_ms ?? "—") + ")";
          if (hasDoor) doorTxt = "Opened at index " + (events.door.opened_at_index ?? "—") + " (t_ms " + (events.door.t_ms ?? "—") + ")";

          var cardE = document.createElement("div");
          cardE.className = "er-qcard";
          cardE.innerHTML = `
            <div class="er-qcard-head">
              <div class="er-q-left">
                <span class="er-q-badge">EVENTS</span>
              </div>
              <div class="er-q-meta"></div>
            </div>
            <div class="er-qcard-answers">
              <div class="er-q-answer-block correct">
                <div class="er-q-answer-label">Key</div>
                <div class="er-q-answer-text">${keyTxt}</div>
              </div>
              <div class="er-q-answer-block your">
                <div class="er-q-answer-label">Door</div>
                <div class="er-q-answer-text">${doorTxt}</div>
              </div>
            </div>
          `;
          frag.appendChild(cardE);
        }

        questionListEl.appendChild(frag);
      }

      function renderResult(payloadRaw) {
        var payload = payloadRaw && payloadRaw.data ? payloadRaw.data : (payloadRaw || {});
        var game    = payload.game || payload.door_game || payload.quiz || {};
        var result  = payload.result || payload || {};

        var gameTitle = game.title || game.game_title || game.name || "Door Game Result";
        gameTitleEl.textContent = gameTitle;

        var submittedAt = result.result_created_at || result.created_at || payload.result_created_at || null;
        var attemptNo   = result.attempt_no || result.attempt_number || "-";
        var status      = result.status || result.attempt_status || "-";

        attemptMetaEl.textContent =
          "Attempt no " + attemptNo + " | Submitted at " + formatDateTime(submittedAt);

        var snap = safeJsonParse(result.user_answer_json || result.snapshot || result.payload, {});
        var timing = (snap && snap.timing) ? snap.timing : {};
        var timeTakenMs = Number(result.time_taken_ms || timing.time_taken_ms || 0);

        // Moves count
        var movesCount = (snap && Array.isArray(snap.moves)) ? snap.moves.length : 0;

        // Score + percent (door game score is 0/1)
        var score = Number(result.score || 0);
        var percent = Math.max(0, Math.min(100, Math.round(score * 100)));

        var chip = statusToChip(status);
        scoreChipEl.innerHTML = '<i class="fa-solid fa-' + chip.icon + '"></i> ' + chip.label;

        percentEl.textContent = percent + "%";
        marksEl.textContent   = String(score);
        attemptedEl.textContent = String(movesCount);
        timeSpentEl.textContent  = formatDurationMs(timeTakenMs);

        scoreTextEl.innerHTML =
          "Status: <strong>" + String(status || "-") +
          "</strong>. Moves: <strong>" + movesCount +
          "</strong>. Time: <strong>" + (formatDurationMs(timeTakenMs)) + "</strong>.";

        requestAnimationFrame(function () {
          scoreBarEl.style.width = percent + "%";
        });
        barLabelEl.textContent = "Success: " + percent + "%";

        // Events pills
        var ev = snap && snap.events ? snap.events : {};
        var keyOk = !!(ev && ev.key);
        var doorOk = !!(ev && ev.door);

        keyPickedEl.textContent  = "Key: " + (keyOk ? "Picked" : "Not picked");
        doorOpenedEl.textContent = "Door: " + (doorOk ? "Opened" : "Not opened");
        timeoutInfoEl.textContent = "Timeout: " + (String(status).toLowerCase() === "timeout" ? "Yes" : "No");

        resultIdEl.textContent  = result.result_id || result.id || result.result_uuid || result.uuid || "-";
        attemptNoEl.textContent = attemptNo;
        submittedAtEl.textContent = formatDateTime(submittedAt);

        renderMoves(snap);
      }

      async function loadResult() {
        var token = getToken();
        if (!token) {
          clearAuthStorage();
          window.location.replace("/login");
          return;
        }

        showLoader(true);
        if (errorEl) { errorEl.classList.remove("show"); errorEl.textContent = ""; }

        try {
          // ✅ Door Game Result detail API
          var res = await fetch("/api/door-game-results/detail/" + encodeURIComponent(RESULT_ID), {
            method: "GET",
            headers: {
              "Accept": "application/json",
              "Authorization": "Bearer " + token
            }
          });

          var json;
          try { json = await res.json(); } catch (e) { json = {}; }

          if (res.status === 401 || res.status === 403) {
            clearAuthStorage();
            window.location.replace("/login");
            return;
          }

          if (!res.ok) throw new Error(json.message || json.error || "Failed to load result.");

          renderResult(json);
        } catch (err) {
          console.error(err);
          showError(err.message || "Failed to load result.");
        } finally {
          showLoader(false);
        }
      }

      if (pdfBtn) {
        pdfBtn.addEventListener("click", function () { window.print(); });
      }

      loadResult();
    }

    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", initResultPage);
    } else {
      initResultPage();
    }
  })();
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
