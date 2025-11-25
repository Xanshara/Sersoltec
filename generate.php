<?php
// generate-password.php
echo password_hash('test123', PASSWORD_DEFAULT);
// Skopiuj wynik i wklej do bazy w kolumnie `password`
?>