<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $title ?? 'API Docs' }}</title>
    <style>
        body {
            margin: 0;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            background: #0f172a;
            color: #e2e8f0;
        }
        .container {
            max-width: 100%;
            margin: 0 auto;
            padding: 20px;
        }
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }
        .title {
            font-size: 20px;
            font-weight: 700;
        }
        .meta {
            font-size: 12px;
            color: #94a3b8;
        }
        .raw-link {
            color: #93c5fd;
            text-decoration: none;
            font-size: 13px;
        }
        .panel {
            border: 1px solid #334155;
            border-radius: 10px;
            background: #020617;
            overflow: auto;
            max-height: calc(100vh - 100px);
        }
        pre {
            margin: 0;
            padding: 16px;
            white-space: pre-wrap;
            word-break: break-word;
            line-height: 1.5;
            font-size: 13px;
        }
        .markdown {
            margin: 0;
            padding: 16px;
            line-height: 1.6;
            font-size: 14px;
        }
        .markdown h1, .markdown h2, .markdown h3, .markdown h4 {
            color: #f8fafc;
            margin-top: 20px;
        }
        .markdown a {
            color: #93c5fd;
            text-decoration: underline;
        }
        .markdown code {
            background: #111827;
            border: 1px solid #334155;
            border-radius: 4px;
            padding: 1px 4px;
        }
        .markdown pre {
            background: #111827;
            border: 1px solid #334155;
            border-radius: 8px;
            padding: 12px;
            overflow: auto;
        }
        .markdown table {
            border-collapse: collapse;
            width: 100%;
        }
        .markdown th, .markdown td {
            border: 1px solid #334155;
            padding: 8px;
            text-align: left;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="topbar">
        <div>
            <div class="title">{{ $title ?? 'API Docs' }}</div>
            <div class="meta">{{ $meta ?? 'docs/public/api/api-frontend-v1.md' }}</div>
        </div>
        <a class="raw-link" href="{{ route($rawRoute ?? 'admin.docs.api.frontend_v1.raw', $rawRouteParams ?? []) }}">Open Raw</a>
    </div>

    <div class="panel">
        <div class="markdown">{!! \Illuminate\Support\Str::markdown($markdown ?? '') !!}</div>
    </div>
</div>
</body>
</html>
