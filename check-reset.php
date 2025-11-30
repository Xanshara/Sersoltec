<?php
session_start();

// Database
$pdo = new PDO("mysql:host=localhost;dbname=sersoltec_db", "sersoltec", "m1vg!M2Zj*3BY.QX");

// Get token
$token = isset($_GET['token']) ? $_GET['token'] : '';

echo "<h2>RESET PASSWORD DEBUG</h2>";
echo "<p><strong>Token from URL:</strong> " . ($token ? htmlspecialchars($token) : 'BRAK') . "</p>";

if ($token) {
    // Check database
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ?");
    $stmt->execute(array($token));
    $reset = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($reset) {
        echo "<p style='color:green;'><strong>TOKEN ZNALEZIONY W BAZIE!</strong></p>";
        echo "<p>Email: " . $reset['email'] . "</p>";
        echo "<p>Expires: " . $reset['expires_at'] . "</p>";
        echo "<p>Used: " . ($reset['used'] ? 'TAK' : 'NIE') . "</p>";
        
        // Check if expired
        if (strtotime($reset['expires_at']) < time()) {
            echo "<p style='color:red;'><strong>TOKEN WYGASL!</strong></p>";
        } else {
            echo "<p style='color:green;'><strong>TOKEN WAZNY!</strong></p>";
        }
        
        if ($reset['used']) {
            echo "<p style='color:red;'><strong>TOKEN JUZ UZYTY!</strong></p>";
        }
    } else {
        echo "<p style='color:red;'><strong>TOKEN NIE ISTNIEJE W BAZIE!</strong></p>";
        
        // Show last tokens
        echo "<p>Ostatnie tokeny:</p>";
        $stmt = $pdo->query("SELECT LEFT(token, 20) as token_short, email, expires_at FROM password_resets ORDER BY created_at DESC LIMIT 3");
        echo "<ul>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<li>" . $row['token_short'] . "... - " . $row['email'] . " - " . $row['expires_at'] . "</li>";
        }
        echo "</ul>";
    }
}

// Check if reset-password.php has debug
$resetFile = __DIR__ . '/reset-password.php';
if (file_exists($resetFile)) {
    $content = file_get_contents($resetFile);
    if (strpos($content, 'RESET-PASSWORD:') !== false) {
        echo "<p style='color:green;'><strong>reset-password.php MA debugowanie!</strong></p>";
    } else {
        echo "<p style='color:red;'><strong>reset-password.php NIE MA debugowania! ZASTAP PLIK!</strong></p>";
    }
    echo "<p>Rozmiar pliku: " . filesize($resetFile) . " bajtow</p>";
} else {
    echo "<p style='color:red;'><strong>reset-password.php NIE ISTNIEJE!</strong></p>";
}
?>