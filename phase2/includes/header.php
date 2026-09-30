<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wewo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f3f7ff;
            margin: 0;
            color: #1d2a39;
        }
        .container {
            max-width: 1100px;
            margin: 30px auto;
            padding: 20px;
        }
        .card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.08);
        }
        nav {
            background: #102b45;
            padding: 15px 30px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        nav a {
            color: white;
            text-decoration: none;
            margin-left: 10px;
        }
        form {
            display: grid;
            gap: 12px;
            margin-top: 16px;
        }
        input, button {
            padding: 12px 14px;
            border-radius: 8px;
            border: 1px solid #d9e1f2;
            font-size: 14px;
        }
        button {
            background: #1f78ff;
            color: white;
            border: none;
            cursor: pointer;
        }
        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .success { background: #eafaf1; color: #176a3a; }
        .error { background: #fdecec; color: #af1e2d; }
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .history-list, .favorites-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        .chip {
            background: #eef4ff;
            border-radius: 999px;
            padding: 8px 12px;
            font-size: 13px;
        }
        @media (max-width: 700px) {
            .grid { grid-template-columns: 1fr; }
        }
    </style>
    <link rel="stylesheet" href="<?= htmlspecialchars(APP_BASE_PATH, ENT_QUOTES, 'UTF-8') ?>/auth.css">
    <link rel="stylesheet" href="<?= htmlspecialchars(APP_BASE_PATH, ENT_QUOTES, 'UTF-8') ?>/dashboard.css">
</head>
<body>
