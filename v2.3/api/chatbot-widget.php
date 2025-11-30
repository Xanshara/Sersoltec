<?php
/**
 * SERSOLTEC - CHATBOT WIDGET API
 * 
 * Ścieżka: /api/chatbot-widget.php
 * 
 * Obsługuje wiadomości AJAX z floating widgetu
 * Zapisuje do bazy danych (inquiries)
 * Wysyła emaile (admin + auto-reply)
 */

// Załaduj konfigurację (z głównego katalogu)
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

// ===== SECURITY =====
// Akceptuj tylko POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Pobierz akcję
$action = isset($_POST['action']) ? sanitize($_POST['action']) : '';

// ===== SEND MESSAGE =====
if ($action === 'send_message') {
    $user_message = isset($_POST['message']) ? sanitize($_POST['message']) : '';
    $user_email = isset($_POST['email']) ? sanitize($_POST['email']) : '';
    
    // Walidacja
    if (empty($user_message)) {
        http_response_code(400);
        echo json_encode(['error' => 'Message cannot be empty']);
        exit;
    }
    
    // Zapisz wiadomość w bazie
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO inquiries (name, email, subject, message, ip_address) 
             VALUES (?, ?, ?, ?, ?)"
        );
        
        $stmt->execute([
            'Widget User',
            $user_email ?: 'noemail@example.com',
            'Widget Message',
            $user_message,
            $_SERVER['REMOTE_ADDR']
        ]);
        
        // ===== WYŚLIJ EMAIL DO ADMINA =====
        $to = CONTACT_EMAIL;
        $subject = "Nowa wiadomość z Chatbot Widget";
        $message_body = "
Nowa wiadomość z floating chatbot widgetu

Email: " . ($user_email ? htmlspecialchars($user_email) : 'Nie podany') . "
IP: " . $_SERVER['REMOTE_ADDR'] . "
Data: " . date('Y-m-d H:i:s') . "

=== WIADOMOŚĆ ===
" . htmlspecialchars($user_message) . "
";
        
        $headers = "From: " . SMTP_FROM . "\r\n";
        $headers .= "Reply-To: " . ($user_email ? $user_email : CONTACT_EMAIL) . "\r\n";
        
        mail($to, $subject, $message_body, $headers);
        
        // ===== WYŚLIJ AUTO-REPLY DO USERA =====
        if ($user_email && isValidEmail($user_email)) {
            $reply_subject = "Potwierdzenie wiadomości - Sersoltec";
            $reply_body = "
Dziękujemy za Twoją wiadomość!

Odebrano Twoją wiadomość i skontaktujemy się wkrótce.

Pozdrawiamy,
Sersoltec
" . CONTACT_EMAIL;
            
            mail($user_email, $reply_subject, $reply_body, $headers);
        }
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Wiadomość wysłana! Dziękujemy za kontakt.'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
}

// ===== GET FAQ =====
else if ($action === 'get_faq') {
    $lang = isset($_POST['lang']) ? sanitize($_POST['lang']) : 'pl';
    
    // Zwróć FAQ z translations.php
    $faq_items = [
        [
            'question' => t('faq_1_question', $lang),
            'answer' => t('faq_1_answer', $lang)
        ],
        [
            'question' => t('faq_2_question', $lang),
            'answer' => t('faq_2_answer', $lang)
        ],
        [
            'question' => t('faq_3_question', $lang),
            'answer' => t('faq_3_answer', $lang)
        ],
        [
            'question' => t('faq_4_question', $lang),
            'answer' => t('faq_4_answer', $lang)
        ],
        [
            'question' => t('faq_5_question', $lang),
            'answer' => t('faq_5_answer', $lang)
        ]
    ];
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'faq' => $faq_items
    ]);
}

// ===== INVALID ACTION =====
else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
?>
