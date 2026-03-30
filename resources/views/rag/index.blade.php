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

        .benchmark-block {
            margin-bottom: 24px;
        }

        .benchmark-block h2 {
            color: #333;
            font-size: 1.15em;
            margin-bottom: 12px;
            font-weight: 600;
        }

        .data-table-wrap {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid #eee;
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
            border-bottom: 1px solid #eee;
        }

        .data-table th {
            background: #f0f2ff;
            color: #333;
            font-weight: 600;
        }

        .data-table tbody tr:last-child td {
            border-bottom: none;
        }

        .data-table tbody tr:hover {
            background: #fafafa;
        }

        .data-table .num {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .benchmark-note {
            font-size: 0.82em;
            color: #666;
            margin-top: 8px;
            line-height: 1.45;
        }

        .benchmark-empty {
            background: #fff9e6;
            border: 1px solid #ffe0a3;
            color: #856404;
            padding: 14px 16px;
            border-radius: 8px;
            font-size: 0.9em;
        }

        .benchmark-meta {
            font-size: 0.8em;
            color: #888;
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

        @php
            $off = $baseline['pipeline_off'] ?? null;
            $on = $baseline['pipeline_on'] ?? null;
            $ref = $referenceBaseline;
            $fmt = fn ($v, $d = 2) => is_numeric($v) ? number_format((float) $v, $d, ',', '.') : '—';
            $rowWide = function ($label, $payload) use ($fmt) {
                if (!$payload || ($payload['queries_total'] ?? 0) === 0) {
                    return null;
                }
                $s = $payload['stages'];
                return [
                    'label' => $label,
                    'emb' => $fmt($s['embedding']['p50_ms']),
                    'vec' => $fmt($s['vector_search']['p50_ms']),
                    'llm' => $fmt($s['llm']['p50_ms']),
                    'tot' => $fmt($payload['total']['p50_ms']),
                    'ce' => $fmt($s['embedding']['avg_cost_usd'], 6),
                    'cv' => $fmt($s['vector_search']['avg_cost_usd'], 6),
                    'cl' => $fmt($s['llm']['avg_cost_usd'], 6),
                    'ct' => $fmt($payload['total']['avg_cost_usd'], 6),
                ];
            };
            $stageDetail = function ($payload) use ($fmt) {
                if (!$payload || ($payload['queries_total'] ?? 0) === 0) {
                    return [];
                }
                $s = $payload['stages'];
                $labels = [
                    'embedding' => 'Embedding',
                    'vector_search' => 'Busca vetorial (Qdrant)',
                    'llm' => 'Geração LLM (gpt-4o)',
                ];
                $rows = [];
                foreach ($labels as $key => $name) {
                    $rows[] = [
                        'name' => $name,
                        'p50' => $fmt($s[$key]['p50_ms']),
                        'p95' => $fmt($s[$key]['p95_ms']),
                        'p99' => $fmt($s[$key]['p99_ms']),
                        'cost' => $fmt($s[$key]['avg_cost_usd'], 6),
                    ];
                }
                return $rows;
            };
        @endphp

        <div class="card">
            <h2 style="margin-bottom: 8px; color: #333;">📊 Métricas de benchmark (baseline)</h2>
            <p class="benchmark-meta">
                Preços de referência: embedding {{ number_format($pricingRef['embedding_usd_per_1k_tokens'], 5, ',', '.') }} USD/1k tokens;
                gpt-4o {{ number_format($pricingRef['gpt4o_input_usd_per_1m'], 2, ',', '.') }}/1M in +
                {{ number_format($pricingRef['gpt4o_output_usd_per_1m'], 2, ',', '.') }}/1M out.
            </p>

            @if(!empty($baseline['has_pipeline']))
                @php
                    $rOff = $rowWide('Cache OFF (medido)', $off);
                    $rOn = $rowWide('Cache ON (medido)', $on);
                @endphp
                <div class="benchmark-block">
                    <h2>Tabela comparativa (P50)</h2>
                    <div class="data-table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Cenário</th>
                                    <th class="num">Embedding (ms)</th>
                                    <th class="num">Busca vetorial (ms)</th>
                                    <th class="num">LLM (ms)</th>
                                    <th class="num">Total (ms)</th>
                                    <th class="num">$ embedding</th>
                                    <th class="num">$ busca*</th>
                                    <th class="num">$ LLM</th>
                                    <th class="num">$ total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if($rOff)
                                    <tr>
                                        <td>{{ $rOff['label'] }}</td>
                                        <td class="num">{{ $rOff['emb'] }}</td>
                                        <td class="num">{{ $rOff['vec'] }}</td>
                                        <td class="num">{{ $rOff['llm'] }}</td>
                                        <td class="num">{{ $rOff['tot'] }}</td>
                                        <td class="num">{{ $rOff['ce'] }}</td>
                                        <td class="num">{{ $rOff['cv'] }}</td>
                                        <td class="num">{{ $rOff['cl'] }}</td>
                                        <td class="num">{{ $rOff['ct'] }}</td>
                                    </tr>
                                @endif
                                @if($rOn)
                                    <tr>
                                        <td>{{ $rOn['label'] }}</td>
                                        <td class="num">{{ $rOn['emb'] }}</td>
                                        <td class="num">{{ $rOn['vec'] }}</td>
                                        <td class="num">{{ $rOn['llm'] }}</td>
                                        <td class="num">{{ $rOn['tot'] }}</td>
                                        <td class="num">{{ $rOn['ce'] }}</td>
                                        <td class="num">{{ $rOn['cv'] }}</td>
                                        <td class="num">{{ $rOn['cl'] }}</td>
                                        <td class="num">{{ $rOn['ct'] }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <td>{{ $ref['label'] }}</td>
                                    <td class="num">—</td>
                                    <td class="num">—</td>
                                    <td class="num">—</td>
                                    <td class="num"><strong>{{ $ref['typical_latency_ms'] }}</strong></td>
                                    <td class="num">—</td>
                                    <td class="num">—</td>
                                    <td class="num">—</td>
                                    <td class="num"><strong>{{ $fmt($ref['typical_cost_usd'], 4) }}</strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="benchmark-note">* Busca em Qdrant auto-hospedado: custo de API OpenAI ≈ 0 USD; só infraestrutura.</p>
                </div>

                <div class="benchmark-block">
                    <h2>Detalhe por etapa — sem cache (percentis)</h2>
                    <div class="data-table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Etapa</th>
                                    <th class="num">P50 (ms)</th>
                                    <th class="num">P95 (ms)</th>
                                    <th class="num">P99 (ms)</th>
                                    <th class="num">Custo médio/query (USD)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stageDetail($off) as $row)
                                    <tr>
                                        <td>{{ $row['name'] }}</td>
                                        <td class="num">{{ $row['p50'] }}</td>
                                        <td class="num">{{ $row['p95'] }}</td>
                                        <td class="num">{{ $row['p99'] }}</td>
                                        <td class="num">{{ $row['cost'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="benchmark-block">
                    <h2>Detalhe por etapa — com cache (percentis)</h2>
                    <div class="data-table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Etapa</th>
                                    <th class="num">P50 (ms)</th>
                                    <th class="num">P95 (ms)</th>
                                    <th class="num">P99 (ms)</th>
                                    <th class="num">Custo médio/query (USD)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stageDetail($on) as $row)
                                    <tr>
                                        <td>{{ $row['name'] }}</td>
                                        <td class="num">{{ $row['p50'] }}</td>
                                        <td class="num">{{ $row['p95'] }}</td>
                                        <td class="num">{{ $row['p99'] }}</td>
                                        <td class="num">{{ $row['cost'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                @if(!empty($baseline['cache_comparison']))
                    @php $cc = $baseline['cache_comparison']; @endphp
                    <div class="benchmark-block">
                        <h2>Benchmark só busca (latência agregada)</h2>
                        <div class="data-table-wrap">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Métrica</th>
                                        <th class="num">Sem cache (ms)</th>
                                        <th class="num">Com cache (ms)</th>
                                        <th class="num">Δ %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>P50</td>
                                        <td class="num">{{ $fmt($cc['baseline_no_cache']['latency_p50_ms'] ?? 0) }}</td>
                                        <td class="num">{{ $fmt($cc['baseline_with_cache']['latency_p50_ms'] ?? 0) }}</td>
                                        <td class="num">{{ isset($cc['delta_percentual']['p50_percent']) ? $fmt($cc['delta_percentual']['p50_percent'], 1) : '—' }}</td>
                                    </tr>
                                    <tr>
                                        <td>P95</td>
                                        <td class="num">{{ $fmt($cc['baseline_no_cache']['latency_p95_ms'] ?? 0) }}</td>
                                        <td class="num">{{ $fmt($cc['baseline_with_cache']['latency_p95_ms'] ?? 0) }}</td>
                                        <td class="num">{{ isset($cc['delta_percentual']['p95_percent']) ? $fmt($cc['delta_percentual']['p95_percent'], 1) : '—' }}</td>
                                    </tr>
                                    <tr>
                                        <td>P99</td>
                                        <td class="num">{{ $fmt($cc['baseline_no_cache']['latency_p99_ms'] ?? 0) }}</td>
                                        <td class="num">{{ $fmt($cc['baseline_with_cache']['latency_p99_ms'] ?? 0) }}</td>
                                        <td class="num">{{ isset($cc['delta_percentual']['p99_percent']) ? $fmt($cc['delta_percentual']['p99_percent'], 1) : '—' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p class="benchmark-note">Script <code>run_cache_off.sh</code> / <code>run_cache_on.sh</code> (apenas <code>searchDocuments</code>, sem etapa LLM).</p>
                    </div>
                @endif

                <p class="benchmark-meta">
                    Pipeline: gerado em
                    @if(!empty($baseline['generated_at_off']))<span>sem cache {{ $baseline['generated_at_off'] }}</span>@endif
                    @if(!empty($baseline['generated_at_on'])) · com cache {{ $baseline['generated_at_on'] }}@endif
                </p>
            @else
                <div class="benchmark-empty">
                    Nenhum ficheiro <code>results/baseline/pipeline_metrics_cache_*.json</code> encontrado.
                    Execute <code>php artisan rag:benchmark-pipeline --both</code> (ou <code>bash scripts/benchmark/run_pipeline_metrics.sh</code>) para preencher estas tabelas.
                </div>
            @endif
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

                <!-- Métricas da execução atual (tabela) -->
                <h3 style="margin: 16px 0 8px; color: #333; font-size: 1em;">Tempos desta requisição</h3>
                <div class="data-table-wrap">
                    <table class="data-table live-timing-table" id="metricsTable">
                        <thead>
                            <tr>
                                <th>Métrica</th>
                                <th class="num">Valor</th>
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

            const metricsBody = document.getElementById('metricsTableBody');
            const br = (n) => Number(n).toLocaleString('pt-BR', { minimumFractionDigits: 0, maximumFractionDigits: 1 });
            const rows = [
                ['Total', `${br(data.timing.total)} ms`],
                ['Embedding', `${br(data.timing.embedding)} ms`],
                ['Busca vetorial', `${br(data.timing.search)} ms`],
                ['Geração LLM', `${br(data.timing.generation)} ms`],
                ['Cache habilitado (servidor)', data.cache_enabled ? 'Sim' : 'Não'],
                ['Cache hits (estimado)', String(data.cache_hits ?? 0)],
            ];
            metricsBody.innerHTML = rows.map(([k, v]) => `<tr><td>${escapeHtml(k)}</td><td class="num">${escapeHtml(String(v))}</td></tr>`).join('');

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
