<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? 'es'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <meta name="theme-color" content="#667eea">
    <title>AiNi Travel</title>

    <!-- Bootstrap 5 ONLY — no Tailwind -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Flag Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/lipis/flag-icons@7.0.0/css/flag-icons.min.css">
    <!-- AiNi Mobile CSS -->
    <link rel="stylesheet" href="/mobile/css/mobile.css?v=2.0">
</head>
<body data-lang="<?php echo $_SESSION['lang'] ?? 'es'; ?>">
<!-- Translation engine: runs after page renders, no-op if lang=es -->
<script src="/mobile/js/mobile_translate.js?v=1.0" defer></script>
