<?php
declare(strict_types=1);

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => false
]);

session_start();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $preference = $_POST['preference'] ?? '';

    if (is_string($preference) &&
        in_array($preference, ['blue', 'green'], true)) {
        $_SESSION['demo_preference'] = $preference;
        $message = 'Preference updated successfully.';
    }
}

$currentPreference = $_SESSION['demo_preference'] ?? 'blue';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Vulnerable CSRF Demonstration</title>
</head>
<body>
    <h1>Vulnerable CSRF Demonstration</h1>
    <p>This application has no CSRF token validation.</p>

    <?php if ($message !== ''): ?>
        <p style="color:green;">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <p>Current preference:
        <strong><?= htmlspecialchars($currentPreference, ENT_QUOTES, 'UTF-8') ?></strong>
    </p>

    <form method="POST">
        <label for="preference">Choose preference:</label>
        <select name="preference" id="preference">
            <option value="blue">Blue</option>
            <option value="green">Green</option>
        </select>
        <button type="submit">Update Preference</button>
    </form>
</body>
</html>
