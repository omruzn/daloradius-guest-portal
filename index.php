<?php
session_start();
date_default_timezone_set('Europe/Istanbul');

// Show errors (for development)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// === daloRADIUS-compatible configuration ===
define('ADMIN_PASSWORD', 'Aa12345');                     // Admin panel password
define('DB_SERVER', 'localhost');                        // DB server
define('DB_USERNAME', 'radius');                         // DB username
define('DB_PASSWORD', 'radiuspassword');                 // DB password
define('DB_NAME', 'radius');                             // Database name
define('FROM_EMAIL', 'wifi@yourdomain.com');             // Sender email address

// Connect to RADIUS DB
$mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($mysqli->connect_error) {
    die("Database connection failed: " . $mysqli->connect_error);
}

// === Admin login handling ===
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login_password'])) {
    if ($_POST['login_password'] === ADMIN_PASSWORD) {
        $_SESSION['authenticated'] = true;
    } else {
        $error_message = "Incorrect password!";
    }
}

// Show login form if not authenticated
if (!isset($_SESSION['authenticated'])) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head><meta charset="UTF-8"><title>daloRADIUS Admin Login</title></head>
    <body>
        <h2>Admin Login</h2>
        <?php if (isset($error_message)) echo "<p style='color: red;'>$error_message</p>"; ?>
        <form method="post">
            <input type="password" name="login_password" placeholder="Password" required>
            <input type="submit" value="Login">
        </form>
    </body>
    </html>
    <?php exit;
}

// === Handle new user creation ===
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['username'])) {
    // Sanitize & normalize input
    $usernameRaw = trim($_POST['username']);
    $username = preg_replace('/[^+\d]/', '', $usernameRaw); // Remove non-numeric and non-+ chars

    $firstname = $mysqli->real_escape_string(trim($_POST['firstname']));
    $lastname = $mysqli->real_escape_string(trim($_POST['lastname']));
    $email = $mysqli->real_escape_string(trim($_POST['email']));
    $sessionDays = max(1, intval($_POST['session_days']));
    $sessionTimeout = $sessionDays * 86400;
    $password = strval(mt_rand(100000, 999999));
    $currentDate = date('Y-m-d H:i:s');

    // Validate international phone number (E.164 format: +xxxxxxxxxx)
    if (!preg_match('/^\+?[1-9]\d{7,14}$/', $username)) {
        die("Invalid phone number format. Use international format like +905XXXXXXXXX");
    }

    // === radcheck: Store username and password ===
    $mysqli->query("INSERT INTO radcheck (id, username, attribute, op, value)
                    VALUES (0, '$username', 'Cleartext-Password', ':=', '$password')
                    ON DUPLICATE KEY UPDATE value = '$password'");

    // === radreply: Set session timeout ===
    $mysqli->query("INSERT INTO radreply (id, username, attribute, op, value)
                    VALUES (0, '$username', 'Session-Timeout', ':=', '$sessionTimeout')
                    ON DUPLICATE KEY UPDATE value = '$sessionTimeout'");

    // === userinfo: Store user metadata for daloRADIUS ===
    $mysqli->query("INSERT INTO userinfo (id, username, firstname, lastname, email, department, company,
                    workphone, homephone, mobilephone, address, city, state, country, zip, notes, changeuserinfo,
                    enableportallogin, portalloginpassword, creationdate, creationby)
                    VALUES (0, '$username', '$firstname', '$lastname', '$email', '', '', '', '', '', '', '', '', '', '', '', 
                    '0', '0', '', '$currentDate', 'admin')
                    ON DUPLICATE KEY UPDATE firstname='$firstname', lastname='$lastname', email='$email'");

    // === Send email to user with credentials ===
    $subject = "Your Guest Wi-Fi Access";
    $body = "Hello $firstname $lastname,\n\n"
          . "Your Wi-Fi credentials have been created.\n\n"
          . "Username: $username\n"
          . "Password: $password\n"
          . "Valid for: $sessionDays day(s)\n\n"
          . "Enjoy your connection!";
    $headers = "From: " . FROM_EMAIL . "\r\n" .
               "Content-Type: text/plain; charset=UTF-8\r\n";

    if (!mail($email, $subject, $body, $headers)) {
        $success_message = "User created, but email could not be sent.";
    } else {
        $success_message = "User created and email sent successfully.";
    }
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Guest Wi-Fi User</title>
</head>
<body>
    <h2>Create daloRADIUS Guest Account</h2>
    <?php if (isset($success_message)) echo "<p style='color:green;'>$success_message</p>"; ?>

    <!-- User registration form -->
    <form method="post">
        <input type="text" name="username" placeholder="Phone (e.g., +905XXXXXXXXX)" required><br>
        <input type="text" name="firstname" placeholder="First Name" required><br>
        <input type="text" name="lastname" placeholder="Last Name" required><br>
        <input type="email" name="email" placeholder="Email Address" required><br>
        <input type="number" name="session_days" placeholder="Valid Days" min="1" required><br>
        <input type="submit" value="Create User">
    </form>
</body>
</html>
