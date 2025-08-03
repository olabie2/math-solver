<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php yieldBlock('title') ?></title>
    <?= vite(['css/app.css', 'js/app.js']) ?>
</head>
<body>
    <?php include __DIR__ . '/../partials/header.html.php'; ?>

    <main class="p-4">
        <?php yieldBlock('content') ?>
    </main>

    <?php include __DIR__ . '/../partials/footer.html.php'; ?>
</body>
</html>
