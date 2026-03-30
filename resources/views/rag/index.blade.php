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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            background: white;
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
            color: #333;
            font-size: 0.95em;
        }

        .form-group input[type="file"],
        .form-group input[type="text"],
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 0.95em;
            font-family: inherit;
            transition: border-color 0.3s;
        }

        .form-group input[type="file"]:focus,
        .form-group input[type="text"]:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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
            align-items: center;
            background: #f0f0f0;
            border-radius: 25px;
            padding: 4px;
            width: 100px;
            cursor: pointer;
            position: relative;
        }

        .toggle-switch input {
            display: none;
        }

        .toggle-slider {
            width: 50%;
            height: 32px;
            background: white;
            border-radius: 20px;
            position: absolute;
            left: 4px;
            transition: left 0.3s;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .toggle-switch input:checked ~ .toggle-slider {
            left: calc(50% + 2px);
        }

        .toggle-label {
            width: 50%;
            text-align: center;
            font-size: 0.85em;
            font-weight: 600;
            z-index: 1;
            color: #666;
            cursor: pointer;
        }

        .toggle-switch input:checked ~ .toggle-label-off {
            color: #999;
        }

        .toggle-switch input:checked ~ .toggle-label-on {
            color: #667eea;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-secondary {
            background: #f0f0f0;
            color: #333;
        }

        .btn-secondary:hover:not(:disabled) {
            background: #e0e0e0;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }

        .alert.error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
            display: block;
        }

        .alert.warning {
            background: #ffeaa7;
            color: #d63031;
            border: 1px solid #ffde9c;
            display: block;
        }

        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            display: block;
        }

        .alert.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
            display: block;
        }

        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
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
            background: #f9f9f9;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 4px;
        }

        .result-score {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .result-text {
            color: #555;
            line-height: 1.6;
            font-size: 0.95em;
            margin-bottom: 8px;
        }

        .result-meta {
            font-size: 0.85em;
            color: #999;
        }

        .answer-box {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            border: 1px solid rgba(102, 126, 234, 0.3);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .answer-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
            font-size: 0.95em;
        }

        .answer-text {
            color: #555;
            line-height: 1.8;
            font-size: 1em;
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .metric-box {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }

        .metric-value {
            font-size: 1.8em;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 5px;
        }

        .metric-label {
            font-size: 0.85em;
            color: #999;
            font-weight: 600;
        }

        .cache-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            background: #f0f0f0;
            border-radius: 6px;
            font-size: 0.85em;
            font-weight: 600;
        }

        .cache-status.enabled {
            background: #d4edda;
            color: #155724;
        }

        .cache-status.disabled {
            background: #f8d7da;
            color: #721c24;
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
            color: #333;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .error-detail {
            background: #f5f5f5;
            padding: 12px;
            border-radius: 6px;
            margin-top: 10px;
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 0.85em;
            color: #555;
            max-height: 200px;
            overflow-y: auto;
        }

        .text-muted {
            color: #999;
            font-size: 0.9em;
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
                <h2 style="margin-bottom: 20px; color: #333;">📄 Indexar Documento</h2>

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

            <hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;">

            <!-- RAG Query Section -->
            <form id="ragForm">
                <h2 style="margin-bottom: 20px; color: #333;">❓ Fazer Pergunta</h2>

                <div class="form-group">
                    <label for="query">Pergunta:</label>
                    <textarea id="query" name="query" placeholder="Digite sua pergunta sobre os documentos indexados..." required></textarea>
                </div>

                <div class="form-group">
                    <label>Executar com cache:</label>
                    <div class="toggle-group">
                        <div class="toggle-switch">
                            <input type="checkbox" id="useCache" name="use_cache">
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
                <h2 style="margin-bottom: 20px; color: #333;">💡 Resposta Gerada</h2>
                <div class="answer-box">
                    <div class="answer-label">Resposta do GPT-4o:</div>
                    <div class="answer-text" id="answerText"></div>
                </div>

                <!-- Metrics -->
                <div class="metrics-grid" id="metricsGrid"></div>
            </div>

            <!-- Chunks -->
            <div class="card">
                <div class="chunks-header">
                    <span>📚 Chunks Recuperados</span>
                    <span id="chunksCount" style="font-size: 0.9em; color: #999;"></span>
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
        document.getElementById('useCache').dispatchEvent(new Event('change'));

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

            // Metrics
            const metricsGrid = document.getElementById('metricsGrid');
            metricsGrid.innerHTML = `
                <div class="metric-box">
                    <div class="metric-value">${data.timing.total.toFixed(0)}</div>
                    <div class="metric-label">Total (ms)</div>
                </div>
                <div class="metric-box">
                    <div class="metric-value">${data.timing.embedding.toFixed(0)}</div>
                    <div class="metric-label">Embedding</div>
                </div>
                <div class="metric-box">
                    <div class="metric-value">${data.timing.search.toFixed(0)}</div>
                    <div class="metric-label">Busca Vetorial</div>
                </div>
                <div class="metric-box">
                    <div class="metric-value">${data.timing.generation.toFixed(0)}</div>
                    <div class="metric-label">Geração LLM</div>
                </div>
                <div class="metric-box">
                    <div class="metric-value">${data.cache_hits}</div>
                    <div class="metric-label">Cache Hits</div>
                </div>
            `;

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
