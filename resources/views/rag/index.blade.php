<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RAG — Demonstração</title>
    @vite('resources/css/app.css')
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, var(--proby-gray-dark) 0%, var(--proby-primary) 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            color: white;
            margin-bottom: 40px;
            padding-top: 20px;
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .header p {
            font-size: 1.1em;
            opacity: 0.9;
        }

        .card {
            background: var(--proby-white);
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--proby-gray-900);
            font-size: 0.95em;
        }

        .form-group input[type="file"],
        .form-group input[type="text"],
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--proby-gray-300);
            border-radius: 8px;
            font-size: 0.95em;
            font-family: inherit;
            transition: border-color 0.3s;
        }

        .form-group input[type="file"]:focus,
        .form-group input[type="text"]:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--proby-primary);
            box-shadow: 0 0 0 3px rgba(var(--proby-primary-rgb), 0.2);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .toggle-group {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .toggle-switch {
            display: flex;
            align-items: stretch;
            justify-content: stretch;
            background: var(--proby-gray-200);
            border-radius: 999px;
            padding: 4px;
            min-width: 220px;
            width: fit-content;
            max-width: 100%;
            cursor: pointer;
            position: relative;
            box-sizing: border-box;
            border: 1px solid var(--proby-gray-300);
        }

        /* Checkbox fora do fluxo visual; os <label> mantêm o foco e o clique */
        .toggle-switch input {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        .toggle-slider {
            width: calc(50% - 6px);
            min-height: 36px;
            background: var(--proby-white);
            border-radius: 999px;
            position: absolute;
            top: 4px;
            left: 4px;
            transition: left 0.25s ease;
            box-shadow: 0 1px 3px rgba(17, 24, 39, 0.12);
            z-index: 0;
            pointer-events: none;
        }

        .toggle-switch input:checked ~ .toggle-slider {
            left: calc(50% + 2px);
        }

        .toggle-label {
            flex: 1 1 50%;
            min-width: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            font-size: 0.8125rem;
            font-weight: 600;
            line-height: 1.2;
            white-space: nowrap;
            padding: 8px 16px;
            position: relative;
            z-index: 2;
            cursor: pointer;
            user-select: none;
            -webkit-tap-highlight-color: transparent;
            text-shadow: none;
        }

        .toggle-switch input:checked ~ .toggle-label-off {
            color: var(--proby-gray-500);
        }

        .toggle-switch input:not(:checked) ~ .toggle-label-off {
            color: var(--proby-primary);
        }

        .toggle-switch input:checked ~ .toggle-label-on {
            color: var(--proby-primary);
        }

        .toggle-switch input:not(:checked) ~ .toggle-label-on {
            color: var(--proby-gray-500);
        }

        .buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        button {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 0.95em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: var(--proby-primary);
            color: var(--proby-gray-900);
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(var(--proby-primary-rgb), 0.45);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-secondary {
            background: var(--proby-white-mid);
            color: var(--proby-gray-900);
        }

        .btn-secondary:hover:not(:disabled) {
            background: var(--proby-gray-200);
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }

        .alert.error {
            background: #fef2f2;
            color: var(--proby-danger);
            border: 1px solid #fecaca;
            display: block;
        }

        .alert.warning {
            background: #fefce8;
            color: #a16207;
            border: 1px solid #fde047;
            display: block;
        }

        .alert.success {
            background: #f0fdf4;
            color: var(--proby-success);
            border: 1px solid #bbf7d0;
            display: block;
        }

        .alert.info {
            background: var(--proby-gray-50);
            color: var(--proby-gray-dark);
            border: 1px solid var(--proby-gray-200);
            display: block;
        }

        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.35);
            border-radius: 50%;
            border-top-color: var(--proby-gray-900);
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .results-section {
            display: none;
        }

        .results-section.visible {
            display: block;
        }

        .result-item {
            background: var(--proby-gray-50);
            border-left: 4px solid var(--proby-primary);
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 4px;
        }

        .result-score {
            display: inline-block;
            background: var(--proby-primary);
            color: var(--proby-gray-900);
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .result-text {
            color: var(--proby-gray-600);
            line-height: 1.6;
            font-size: 0.95em;
            margin-bottom: 8px;
        }

        .result-meta {
            font-size: 0.85em;
            color: var(--proby-gray-400);
        }

        .answer-box {
            background: linear-gradient(135deg, rgba(var(--proby-primary-rgb), 0.12) 0%, rgba(var(--proby-secondary-rgb), 0.08) 100%);
            border: 1px solid rgba(var(--proby-primary-rgb), 0.35);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .answer-label {
            font-weight: 600;
            color: var(--proby-gray-900);
            margin-bottom: 10px;
            font-size: 0.95em;
        }

        .answer-text {
            color: var(--proby-gray-600);
            line-height: 1.8;
            font-size: 1em;
        }

        .cache-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            background: var(--proby-white-mid);
            border-radius: 6px;
            font-size: 0.85em;
            font-weight: 600;
        }

        .cache-status.enabled {
            background: #f0fdf4;
            color: var(--proby-success);
        }

        .cache-status.disabled {
            background: #fef2f2;
            color: var(--proby-danger);
        }

        .cache-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
        }

        .chunks-header {
            font-weight: 600;
            font-size: 1.05em;
            color: var(--proby-gray-900);
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .error-detail {
            background: var(--proby-gray-100);
            padding: 12px;
            border-radius: 6px;
            margin-top: 10px;
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 0.85em;
            color: var(--proby-gray-600);
            max-height: 200px;
            overflow-y: auto;
        }

        .text-muted {
            color: var(--proby-gray-400);
            font-size: 0.9em;
        }

        .benchmark-block {
            margin-bottom: 24px;
        }

        .benchmark-block h2 {
            color: var(--proby-gray-900);
            font-size: 1.15em;
            margin-bottom: 12px;
            font-weight: 600;
        }

        .data-table-wrap {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid var(--proby-gray-200);
            margin-bottom: 16px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.88em;
            min-width: 640px;
        }

        .data-table th,
        .data-table td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid var(--proby-gray-200);
        }

        .data-table th {
            background: var(--proby-gray-100);
            color: var(--proby-gray-900);
            font-weight: 600;
        }

        .data-table tbody tr:last-child td {
            border-bottom: none;
        }

        .data-table tbody tr:hover {
            background: var(--proby-gray-50);
        }

        .data-table .num {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .data-table .component-cell {
            font-style: italic;
        }

        .data-table td.volume-cell {
            font-size: 0.86em;
            line-height: 1.45;
            max-width: 22rem;
            white-space: pre-line;
        }

        .data-table td.cost-cell {
            font-size: 0.86em;
            line-height: 1.45;
            max-width: 20rem;
            white-space: pre-line;
        }

        .benchmark-note {
            font-size: 0.82em;
            color: var(--proby-gray-600);
            margin-top: 8px;
            line-height: 1.45;
        }

        .benchmark-empty {
            background: #fefce8;
            border: 1px solid var(--proby-secondary);
            color: var(--proby-gray-dark);
            padding: 14px 16px;
            border-radius: 8px;
            font-size: 0.9em;
        }

        .benchmark-meta {
            font-size: 0.8em;
            color: var(--proby-gray-500);
            margin-bottom: 10px;
        }

        .live-timing-table {
            margin-top: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🤖 RAG Reproduzível</h1>
            <p>Demonstração do pipeline de busca semântica + geração com LLM</p>
        </div>

        <!-- Main Card -->
        <div class="card">
            <!-- Alert for missing API key -->
            @if(empty(env('OPENAI_API_KEY')))
            <div class="alert error">
                <strong>⚠ Erro de Configuração:</strong> OPENAI_API_KEY não está configurada no arquivo .env.
                <div class="error-detail">
                    Antes de usar a interface, configure sua chave OpenAI:
                    <br><br>
                    1. Edite o arquivo .env na raiz do projeto
                    <br>
                    2. Adicione ou atualize: OPENAI_API_KEY=sk-...
                    <br>
                    3. Salve e recarregue esta página
                </div>
            </div>
            @endif

            <!-- Upload Section -->
            <form id="uploadForm" enctype="multipart/form-data">
                @csrf
                <h2 style="margin-bottom: 20px; color: var(--proby-gray-900);">📄 Indexar Documento</h2>

                <div class="form-group">
                    <label for="document">Arquivo (TXT ou PDF):</label>
                    <input type="file" id="document" name="file" accept=".txt,.pdf" required>
                    <p class="text-muted" style="margin-top: 8px;">Máximo 10 MB • Formatos: TXT, PDF</p>
                </div>

                <div class="buttons">
                    <button type="submit" class="btn-primary" id="uploadBtn">
                        Indexar Documento
                    </button>
                </div>

                <div id="uploadAlert" class="alert"></div>
            </form>

            <hr style="margin: 30px 0; border: none; border-top: 1px solid var(--proby-gray-200);">

            <!-- RAG Query Section -->
            <form id="ragForm">
                <h2 style="margin-bottom: 20px; color: var(--proby-gray-900);">❓ Fazer Pergunta</h2>

                <div class="form-group">
                    <label for="query">Pergunta:</label>
                    <textarea id="query" name="query" placeholder="Digite sua pergunta sobre os documentos indexados..." required></textarea>
                </div>

                <div class="form-group">
                    <label>Executar com cache:</label>
                    <div class="toggle-group">
                        <div class="toggle-switch">
                            <input type="checkbox" id="useCache" name="use_cache" role="switch" aria-label="Usar cache na resposta">
                            <div class="toggle-slider"></div>
                            <label class="toggle-label toggle-label-off" for="useCache">Sem cache</label>
                            <label class="toggle-label toggle-label-on" for="useCache">Com cache</label>
                        </div>
                        <div class="cache-status" id="cacheStatus">
                            <div class="cache-dot"></div>
                            <span id="cacheStatusText">Cache desabilitado</span>
                        </div>
                    </div>
                </div>

                <div class="buttons">
                    <button type="submit" class="btn-primary" id="askBtn">Processar com RAG</button>
                    <button type="reset" class="btn-secondary">Limpar</button>
                </div>

                <div id="queryAlert" class="alert"></div>
            </form>
        </div>

        <!-- Results Section -->
        <div class="results-section" id="resultsSection">
            <!-- Answer -->
            <div class="card">
                <h2 style="margin-bottom: 20px; color: var(--proby-gray-900);">💡 Resposta Gerada</h2>
                <div class="answer-box">
                    <div class="answer-label">Resposta do GPT-4o:</div>
                    <div class="answer-text" id="answerText"></div>
                </div>

                <!-- Tabela no formato da Tabela 1 do artigo (componente, volume, custo unitário, tempo medido) -->
                <h3 id="requestMetricsTitle" style="margin: 16px 0 8px; color: var(--proby-gray-900); font-size: 1em; font-weight: 600;">
                    {{ config('rag.article_table.title') }}
                </h3>
                <div class="data-table-wrap">
                    <table class="data-table live-timing-table" id="metricsTable">
                        <thead>
                            <tr>
                                <th>Componente</th>
                                <th>Volume</th>
                                <th>Custo</th>
                                <th class="num">Tempo (ms)</th>
                            </tr>
                        </thead>
                        <tbody id="metricsTableBody"></tbody>
                    </table>
                </div>
            </div>

            <!-- Chunks -->
            <div class="card">
                <div class="chunks-header">
                    <span>📚 Chunks Recuperados</span>
                    <span id="chunksCount" style="font-size: 0.9em; color: var(--proby-gray-400);"></span>
                </div>
                <div id="chunksContainer"></div>
            </div>
        </div>
    </div>

    <script>
        // Upload handler
        document.getElementById('uploadForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('uploadBtn');
            const alert = document.getElementById('uploadAlert');
            const formData = new FormData(e.target);

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner"></span> Indexando...';
            alert.className = 'alert';

            try {
                const response = await fetch('/upload', {
                    method: 'POST',
                    body: formData,
                });

                const data = await response.json();

                if (response.ok) {
                    alert.className = 'alert success';
                    alert.innerHTML = `✓ ${data.filename} indexado com sucesso (${data.points_count} vetores)`;
                    document.getElementById('uploadForm').reset();
                } else {
                    alert.className = 'alert error';
                    alert.innerHTML = `✗ Erro: ${data.message}`;
                }
            } catch (err) {
                alert.className = 'alert error';
                alert.innerHTML = `✗ Erro na requisição: ${err.message}`;
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'Indexar Documento';
            }
        });

        // Cache toggle handler
        document.getElementById('useCache').addEventListener('change', (e) => {
            e.target.setAttribute('aria-checked', e.target.checked ? 'true' : 'false');
            const status = document.getElementById('cacheStatus');
            const text = document.getElementById('cacheStatusText');
            if (e.target.checked) {
                status.classList.add('enabled');
                status.classList.remove('disabled');
                text.textContent = 'Cache habilitado';
            } else {
                status.classList.remove('enabled');
                status.classList.add('disabled');
                text.textContent = 'Cache desabilitado';
            }
        });

        // Initialize cache status
        (function initCacheToggle() {
            const cb = document.getElementById('useCache');
            cb.setAttribute('aria-checked', cb.checked ? 'true' : 'false');
            cb.dispatchEvent(new Event('change'));
        })();

        // RAG query handler
        document.getElementById('ragForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('askBtn');
            const alert = document.getElementById('queryAlert');
            const useCache = document.getElementById('useCache').checked;
            const query = document.getElementById('query').value;

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner"></span> Processando...';
            alert.className = 'alert';

            try {
                const response = await fetch('/ask', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    },
                    body: JSON.stringify({
                        query,
                        use_cache: useCache,
                        top_k: 5,
                        min_score: 0.0,
                    }),
                });

                const data = await response.json();

                if (response.ok) {
                    displayResults(data);
                } else {
                    alert.className = 'alert error';
                    alert.innerHTML = `✗ Erro: ${data.message || response.statusText}`;
                }
            } catch (err) {
                alert.className = 'alert error';
                alert.innerHTML = `✗ Erro na requisição: ${err.message}`;
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'Processar com RAG';
            }
        });

        // Display results
        function displayResults(data) {
            // Show results section
            document.getElementById('resultsSection').classList.add('visible');

            // Answer
            document.getElementById('answerText').textContent = data.answer;

            // Chunks count
            document.getElementById('chunksCount').textContent = `(${data.chunks.length} recuperados)`;

            const tbl = data.request_metrics_table;
            if (tbl && tbl.title) {
                document.getElementById('requestMetricsTitle').textContent = tbl.title;
            }
            const fmtMs = (ms) => {
                const n = Number(ms);
                return n.toLocaleString('pt-BR', { minimumFractionDigits: 0, maximumFractionDigits: 1 }) + ' ms';
            };
            const metricsBody = document.getElementById('metricsTableBody');
            const rows = (tbl && tbl.rows) ? tbl.rows : [];
            metricsBody.innerHTML = rows.map((r) => `
                <tr>
                    <td class="component-cell">${escapeHtml(r.component)}</td>
                    <td class="volume-cell">${escapeHtml(r.volume)}</td>
                    <td class="cost-cell">${escapeHtml(r.cost)}</td>
                    <td class="num">${escapeHtml(fmtMs(r.time_ms))}</td>
                </tr>
            `).join('');

            // Chunks
            const container = document.getElementById('chunksContainer');
            container.innerHTML = data.chunks.map((chunk, idx) => `
                <div class="result-item">
                    <div class="result-score">Score: ${chunk.score.toFixed(4)}</div>
                    <div class="result-text">${escapeHtml(chunk.text)}</div>
                    <div class="result-meta">📄 ${escapeHtml(chunk.document_name)} • Chunk #${chunk.chunk_index}</div>
                </div>
            `).join('');

            // Scroll to results
            document.getElementById('resultsSection').scrollIntoView({ behavior: 'smooth' });
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>
