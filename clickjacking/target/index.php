<?php
session_start();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = 'Demo button clicked successfully.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Clickjacking Target Demo</title>
</head>
<body>

    <h1>Clickjacking Target Page</h1>

    <p>This is a harmless local demonstration.</p>

    <form method="POST">
        <button type="submit">Click Demo Button</button>
    </form>

    <?php if ($message !== ''): ?>
        <p style="color: green;">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

</body>
</html>