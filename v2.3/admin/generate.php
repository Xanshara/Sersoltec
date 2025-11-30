<?php
// generate-password.php
echo password_hash('admin123', PASSWORD_DEFAULT);
// Skopiuj wynik i wklej do bazy w kolumnie `password`
?>