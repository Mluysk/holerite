<?php
/** @var string $title */
/** @var string $content */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title ?? 'Holerite'); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        :root {
            font-family: "Segoe UI", Arial, sans-serif;
            color: #1f2933;
            background: #f5f7fa;
        }

        body {
            margin: 0;
            background: #f5f7fa;
        }

        header {
            background: #2563eb;
            color: #fff;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        header h1 {
            font-size: 1.4rem;
            margin: 0;
        }

        nav a {
            color: #bfdbfe;
            text-decoration: none;
            margin-left: 1rem;
            font-weight: 600;
        }

        nav a:hover {
            color: #fff;
        }

        main {
            max-width: 1100px;
            margin: 2rem auto;
            background: #fff;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 20px 25px -15px rgba(15, 23, 42, 0.2);
        }

        .flash {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .flash-success {
            background: #dcfce7;
            color: #166534;
        }

        .flash-error {
            background: #fee2e2;
            color: #991b1b;
        }

        .flash-warning {
            background: #fef3c7;
            color: #92400e;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.5rem;
        }

        th, td {
            padding: 0.75rem;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }

        th {
            background: #eff6ff;
        }

        .actions a,
        .actions form {
            display: inline-block;
            margin-right: 0.5rem;
        }

        .button {
            display: inline-block;
            padding: 0.6rem 1.2rem;
            background: #2563eb;
            color: #fff;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
        }

        .button-secondary {
            background: #475569;
        }

        form label {
            display: block;
            margin-bottom: 0.4rem;
            font-weight: 600;
        }

        form input,
        form select,
        form textarea {
            width: 100%;
            padding: 0.6rem;
            border-radius: 8px;
            border: 1px solid #cbd5f5;
            margin-bottom: 1rem;
            font-size: 1rem;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .card {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: inset 0 0 0 1px #e2e8f0;
        }

        .muted {
            color: #6b7280;
            font-size: 0.95rem;
        }

        .text-right {
            text-align: right;
        }
    </style>
</head>
<body>
<header>
    <h1>Holerite</h1>
    <nav>
        <a href="?action=dashboard">Dashboard</a>
        <a href="?action=list_employees">Colaboradores</a>
        <a href="?action=list_payrolls">Holerites</a>
    </nav>
</header>
<main>
    <?php if (!empty($_SESSION['flash'])): ?>
        <?php foreach ($_SESSION['flash'] as $type => $messages): ?>
            <?php foreach ($messages as $message): ?>
                <div class="flash flash-<?= htmlspecialchars($type); ?>"><?= htmlspecialchars($message); ?></div>
            <?php endforeach; ?>
        <?php endforeach; ?>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <?= $content; ?>
</main>
</body>
</html>
