<?php

require_once __DIR__ . '/vendor/autoload.php';

$parsedown = new Parsedown();
$markdown  = file_get_contents(__DIR__ . '/README.md');
$content   = $parsedown->text($markdown);

?><!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tester Exam – README</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/highlight.js@11.9.0/styles/github.min.css">
    <style>
        body { padding: 2rem 0 4rem; background: #f8f9fa; }
        .readme { background: #fff; border-radius: 8px; padding: 2.5rem 3rem; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
        pre { background: #f6f8fa; border: 1px solid #e1e4e8; border-radius: 6px; padding: 1rem; overflow-x: auto; }
        pre code { background: none; padding: 0; font-size: .875rem; }
        code { background: #f6f8fa; padding: .2em .4em; border-radius: 4px; font-size: .875em; }
        pre code { font-size: .875rem; }
        table { width: 100%; margin-bottom: 1rem; border-collapse: collapse; }
        th, td { padding: .5rem .75rem; border: 1px solid #dee2e6; }
        th { background: #f1f3f5; }
        h1, h2 { border-bottom: 1px solid #e1e4e8; padding-bottom: .4rem; margin-top: 2rem; }
        h3 { margin-top: 1.5rem; }
        blockquote { border-left: 4px solid #dee2e6; padding: .5rem 1rem; color: #6c757d; margin: 1rem 0; }
        hr { border-color: #e1e4e8; }
    </style>
</head>
<body>
    <div class="container" style="max-width: 960px;">
        <div class="readme">
            <?= $content ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/highlight.js@11.9.0/highlight.min.js"></script>
    <script>hljs.highlightAll();</script>
</body>
</html>
