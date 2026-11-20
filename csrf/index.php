<?php
declare(strict_types=1);

// Local CSRF defense demonstration
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => false // Local HTTP lab only
]);

session_start();

// Generate a session-bound CSRF token.
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $submittedToken = $_POST['csrf_token'] ?? '';

    // Validate the submitted token against the session token.
    if (
        !is_string($submittedToken) ||
        !hash_equals($_SESSION['csrf_token'], $submittedToken)
    ) {
        http_response_code(403);
        $error = 'Request rejected: invalid or missing CSRF token.';
    } else {

        // Only allow predefined harmless preferences.
        $allowedPreferences = ['blue', 'green'];

        $preference = $_POST['preference'] ?? '';

        if (
            is_string($preference) &&
            in_array($preference, $allowedPreferences, true)
        ) {
            $_SESSION['demo_preference'] = $preference;
            $message = 'Preference updated successfully.';
        } else {
            $error = 'Invalid preference selected.';
        }
    }
}

$currentPreference = $_SESSION['demo_preference'] ?? 'blue';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSRF Defense Demonstration</title>
</head>
<body>

    <h1>Session-Based CSRF Protection</h1>

    <p>
        This local demonstration protects a harmless
        session preference using a CSRF token.
    </p>

    <?php if ($message !== ''): ?>
        <p style="color: green;">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <p style="color: red;">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <p>
        Current preference:
        <strong>
            <?= htmlspecialchars($currentPreference, ENT_QUOTES, 'UTF-8') ?>
        </strong>
    </p>

    <form method="POST" action="">
        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>"
        >

        <label for="preference">Choose a preference:</label>

        <select name="preference" id="preference">
            <option value="blue">Blue</option>
            <option value="green">Green</option>
        </select>

        <button type="submit">Update Preference</button>
    </form>

</body>
</html>