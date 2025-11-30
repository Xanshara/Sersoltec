<?php
/**
 * SERSOLTEC - UNIFIED CHATBOT WIDGET API v2.6 ULTIMATE
 * 
 * Ścieżka: /api/chatbot-widget.php
 * 
 * POPRAWKI v2.6:
 * - Naprawiono zapytanie dla wielojęzycznej struktury (name_pl, price_base)
 * - Dodano automatyczne wykrywanie języka dla kolumn
 */

// Załaduj konfigurację
require_once __DIR__ . '/../config.php';

// Załaduj tłumaczenia
if (file_exists(__DIR__ . '/../translations.php')) {
    require_once __DIR__ . '/../translations.php';
}

// Dodaj funkcję t() jeśli nie istnieje
if (!function_exists('t')) {
    function t($key, $lang = 'pl') {
        global $translations;
        
        if (isset($translations[$lang][$key])) {
            return $translations[$lang][$key];
        }
        
        return $key;
    }
}

// Wyłącz wyświetlanie błędów w produkcji, włącz logowanie
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Stwórz katalog logs jeśli nie istnieje
$log_dir = __DIR__ . '/../logs';
if (!is_dir($log_dir)) {
    @mkdir($log_dir, 0777, true);
}
ini_set('error_log', $log_dir . '/chatbot-errors.log');

header('Content-Type: application/json; charset=utf-8');

// ===== SECURITY =====
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Obsługa różnych formatów requestów
$input = file_get_contents('php://input');
$jsonData = json_decode($input, true);

if ($jsonData) {
    $action = $jsonData['action'] ?? '';
    $message = $jsonData['message'] ?? '';
    $email = $jsonData['email'] ?? '';
    $lang = $jsonData['lang'] ?? 'pl';
    $conversationHistory = $jsonData['history'] ?? [];
} else {
    $action = $_POST['action'] ?? '';
    $message = $_POST['message'] ?? '';
    $email = $_POST['email'] ?? '';
    $lang = $_POST['lang'] ?? 'pl';
    $conversationHistory = isset($_POST['history']) ? json_decode($_POST['history'], true) : [];
}

// Funkcja sanitize jeśli nie istnieje
if (!function_exists('sanitize')) {
    function sanitize($data) {
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }
}

// Funkcja walidacji emaila
if (!function_exists('isValidEmail')) {
    function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

// ===== HELPER: Get Products Info =====
function getProductsInfo($pdo = null, $conn = null, $lang = 'pl') {
    $products_info = [];
    
    try {
        // ✅ POPRAWKA: Zapytania dostosowane do wielojęzycznej struktury
        $name_col = "name_{$lang}";  // name_pl, name_en, name_es
        $desc_col = "description_{$lang}";  // description_pl, description_en, description_es
        
        $possible_queries = [
            // Wariant 1: Wielojęzyczna struktura z price_base (TWOJA BAZA)
            "SELECT id, {$name_col} as name, price_base as price, {$desc_col} as description FROM products WHERE active = 1 LIMIT 20",
            
            // Wariant 2: Wielojęzyczna struktura bez price_base
            "SELECT id, {$name_col} as name, price_min as price, {$desc_col} as description FROM products WHERE active = 1 LIMIT 20",
            
            // Wariant 3: Standardowa struktura
            "SELECT id, name, price, description FROM products WHERE active = 1 LIMIT 20",
            
            // Wariant 4: Bez filtra active
            "SELECT id, {$name_col} as name, price_base as price FROM products LIMIT 20",
            
            // Wariant 5: Tylko name_pl (fallback na polski)
            "SELECT id, name_pl as name, price_base as price, description_pl as description FROM products WHERE active = 1 LIMIT 20",
            
            // Wariant 6: Minimalna wersja
            "SELECT id, {$name_col} as name, price_base as price FROM products LIMIT 20",
            
            // Wariant 7: Bez niczego
            "SELECT id, name_pl as name FROM products LIMIT 20",
        ];
        
        $success = false;
        
        foreach ($possible_queries as $query) {
            try {
                if (isset($pdo)) {
                    $result = $pdo->query($query);
                    if ($result) {
                        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                            $name = $row['name'] ?? 'Produkt';
                            $price = isset($row['price']) ? floatval($row['price']) : 0;
                            $desc = isset($row['description']) ? substr($row['description'], 0, 100) : '';
                            
                            if ($price > 0) {
                                $products_info[] = sprintf(
                                    "- %s (%.2f PLN)%s",
                                    $name,
                                    $price,
                                    $desc ? ": " . $desc : ''
                                );
                            } else {
                                // Bez ceny
                                $products_info[] = sprintf(
                                    "- %s%s",
                                    $name,
                                    $desc ? ": " . $desc : ''
                                );
                            }
                        }
                        
                        if (count($products_info) > 0) {
                            $success = true;
                            error_log("Products query SUCCESS with: " . $query);
                            break;
                        }
                    }
                } elseif (isset($conn)) {
                    $result = $conn->query($query);
                    if ($result) {
                        while ($row = $result->fetch_assoc()) {
                            $name = $row['name'] ?? 'Produkt';
                            $price = isset($row['price']) ? floatval($row['price']) : 0;
                            $desc = isset($row['description']) ? substr($row['description'], 0, 100) : '';
                            
                            if ($price > 0) {
                                $products_info[] = sprintf(
                                    "- %s (%.2f PLN)%s",
                                    $name,
                                    $price,
                                    $desc ? ": " . $desc : ''
                                );
                            } else {
                                $products_info[] = sprintf(
                                    "- %s%s",
                                    $name,
                                    $desc ? ": " . $desc : ''
                                );
                            }
                        }
                        
                        if (count($products_info) > 0) {
                            $success = true;
                            error_log("Products query SUCCESS with: " . $query);
                            break;
                        }
                    }
                }
            } catch (Exception $e) {
                // Spróbuj następnego query
                continue;
            }
        }
        
        if (!$success) {
            error_log("Products query: All attempts failed, using fallback");
        }
        
    } catch (Exception $e) {
        error_log("Products info error: " . $e->getMessage());
    }
    
    return $products_info;
}

// ===== ACTION: SEND MESSAGE =====
if ($action === 'send_message') {
    $message = sanitize($message);
    
    if (empty($message)) {
        http_response_code(400);
        echo json_encode(['error' => 'Message cannot be empty']);
        exit;
    }
    
    try {
        // Sprawdź czy sesja już istnieje
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $user_name = null;
        $user_id = null;
        
        if (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
            
            if (isset($pdo)) {
                $stmt = $pdo->prepare("SELECT first_name FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $user_name = $row['first_name'];
                }
            } elseif (isset($conn)) {
                $stmt = $conn->prepare("SELECT first_name FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    $user_name = $row['first_name'];
                }
            }
        }
        
        // Pobierz informacje o produktach - z uwzględnieniem języka
        $products_info = getProductsInfo($pdo ?? null, $conn ?? null, $lang);
        
        if (!empty($products_info)) {
            $products_context = implode("\n", $products_info);
        } else {
            $products_context = "Oferujemy szeroki wybór produktów: okna PCV, okna drewniane, drzwi, panele grzewcze i akcesoria budowlane.";
        }
        
        // Przygotuj kontekst systemowy
        $system_context = "Jesteś pomocnym asystentem sklepu internetowego Sersoltec. ";
        
        if ($user_name) {
            $system_context .= "Rozmawiasz z użytkownikiem o imieniu {$user_name}. ";
            $system_context .= "Używaj jego imienia w rozmowie, aby była bardziej osobista i przyjazna. ";
        }
        
        $system_context .= "Twoim zadaniem jest pomóc klientom w wyborze produktów, odpowiadać na pytania dotyczące zamówień i oferować wsparcie.

Dostępne produkty w sklepie:
{$products_context}

Jeśli klient pyta o konkretny produkt, podaj jego cenę i krótki opis.
Jeśli klient potrzebuje pomocy, przekaż go do formularza kontaktowego.
Bądź uprzejmy, pomocny i profesjonalny. Odpowiadaj krótko i na temat.";
        
        // Przygotuj wiadomości dla API
        $messages = [];
        
        if (is_array($conversationHistory)) {
            foreach ($conversationHistory as $msg) {
                if (isset($msg['sender']) && isset($msg['text'])) {
                    $role = ($msg['sender'] === 'user') ? 'user' : 'assistant';
                    $messages[] = [
                        'role' => $role,
                        'content' => $msg['text']
                    ];
                }
            }
        }
        
        $messages[] = [
            'role' => 'user',
            'content' => $message
        ];
        
        // Wywołaj API Anthropic
        $api_key = getenv('ANTHROPIC_API_KEY');
        
        $bot_response = null;
        $is_fallback = false;
        
        if (!empty($api_key)) {
            try {
                $api_url = 'https://api.anthropic.com/v1/messages';
                
                $data = [
                    'model' => 'claude-3-5-sonnet-20241022',
                    'max_tokens' => 1024,
                    'system' => $system_context,
                    'messages' => $messages
                ];
                
                $ch = curl_init($api_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'x-api-key: ' . $api_key,
                    'anthropic-version: 2023-06-01'
                ]);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                
                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($http_code === 200 && $response) {
                    $result = json_decode($response, true);
                    $bot_response = $result['content'][0]['text'] ?? null;
                    
                    if (!$bot_response) {
                        throw new Exception('Invalid API response format');
                    }
                } else {
                    throw new Exception("Claude API unavailable");
                }
            } catch (Exception $e) {
                $is_fallback = true;
            }
        } else {
            $is_fallback = true;
        }
        
        // Jeśli Claude API nie działa, użyj fallback
        if ($is_fallback || empty($bot_response)) {
            $bot_response = generateSimpleResponse($message, $user_name, $products_info, $lang);
            $is_fallback = true;
        }
        
        // Zapisz do chat_history
        try {
            if ($user_id) {
                if (isset($pdo)) {
                    $stmt = $pdo->prepare("INSERT INTO chat_history (user_id, message, response, created_at) VALUES (?, ?, ?, NOW())");
                    $stmt->execute([$user_id, $message, $bot_response]);
                } elseif (isset($conn)) {
                    $stmt = $conn->prepare("INSERT INTO chat_history (user_id, message, response, created_at) VALUES (?, ?, ?, NOW())");
                    $stmt->bind_param("iss", $user_id, $message, $bot_response);
                    $stmt->execute();
                }
            }
        } catch (Exception $e) {
            error_log("Chat history save error: " . $e->getMessage());
        }
        
        // Zapisz inquiry
        try {
            if (isset($pdo)) {
                $stmt = $pdo->prepare(
                    "INSERT INTO inquiries (name, email, subject, message, ip_address) 
                     VALUES (?, ?, ?, ?, ?)"
                );
                $stmt->execute([
                    $user_name ?: 'Widget User',
                    $email ?: 'noemail@example.com',
                    'Widget Message',
                    $message,
                    $_SERVER['REMOTE_ADDR']
                ]);
            } elseif (isset($conn)) {
                $stmt = $conn->prepare(
                    "INSERT INTO inquiries (name, email, subject, message, ip_address) 
                     VALUES (?, ?, ?, ?, ?)"
                );
                $name = $user_name ?: 'Widget User';
                $email_addr = $email ?: 'noemail@example.com';
                $subject = 'Widget Message';
                $ip = $_SERVER['REMOTE_ADDR'];
                $stmt->bind_param("sssss", $name, $email_addr, $subject, $message, $ip);
                $stmt->execute();
            }
        } catch (Exception $e) {
            error_log("Inquiry save error: " . $e->getMessage());
        }
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'response' => $bot_response,
            'user_name' => $user_name,
            'fallback' => $is_fallback
        ]);
        
    } catch (Exception $e) {
        error_log("Chatbot critical error: " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine());
        
        $fallback_texts = [
            'pl' => 'Dziękuję za wiadomość! W czym mogę Ci pomóc?',
            'en' => 'Thank you for your message! How can I help you?',
            'es' => '¡Gracias por tu mensaje! ¿Cómo puedo ayudarte?'
        ];
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'response' => $fallback_texts[$lang] ?? $fallback_texts['pl'],
            'fallback' => true
        ]);
    }
}

// ===== ACTION: GET FAQ =====
else if ($action === 'get_faq') {
    $lang = sanitize($lang);
    
    $faq_items = [];
    
    try {
        for ($i = 1; $i <= 5; $i++) {
            $question_key = "faq_{$i}_question";
            $answer_key = "faq_{$i}_answer";
            
            $question = t($question_key, $lang);
            $answer = t($answer_key, $lang);
            
            if ($question !== $question_key && $answer !== $answer_key) {
                $faq_items[] = [
                    'question' => $question,
                    'answer' => $answer
                ];
            }
        }
        
        if (empty($faq_items)) {
            $default_faq = [
                'pl' => [
                    ['question' => 'Jakie produkty oferujecie?', 'answer' => 'Oferujemy okna PCV, okna drewniane, drzwi, panele grzewcze i akcesoria budowlane.'],
                    ['question' => 'Jak długo trwa dostawa?', 'answer' => 'Standardowa dostawa trwa 7-14 dni roboczych. Express dostawa 3-5 dni.'],
                    ['question' => 'Czy oferujecie montaż?', 'answer' => 'Tak, oferujemy profesjonalny montaż wszystkich naszych produktów przez wykwalifikowany zespół.'],
                    ['question' => 'Jakie formy płatności akceptujecie?', 'answer' => 'Akceptujemy przelewy bankowe, płatności online, karty kredytowe oraz raty 0%.'],
                    ['question' => 'Czy produkty są objęte gwarancją?', 'answer' => 'Tak, wszystkie nasze produkty są objęte gwarancją producenta od 2 do 10 lat.']
                ],
                'en' => [
                    ['question' => 'What products do you offer?', 'answer' => 'We offer PVC windows, wooden windows, doors, heating panels and construction accessories.'],
                    ['question' => 'How long does delivery take?', 'answer' => 'Standard delivery takes 7-14 business days. Express delivery 3-5 days.'],
                    ['question' => 'Do you offer installation?', 'answer' => 'Yes, we offer professional installation by a qualified team.']
                ],
                'es' => [
                    ['question' => '¿Qué productos ofrecen?', 'answer' => 'Ofrecemos ventanas de PVC, ventanas de madera, puertas, paneles de calefacción.'],
                    ['question' => '¿Cuánto tarda la entrega?', 'answer' => 'La entrega estándar demora 7-14 días hábiles.'],
                    ['question' => '¿Ofrecen instalación?', 'answer' => 'Sí, ofrecemos instalación profesional.']
                ]
            ];
            
            $faq_items = $default_faq[$lang] ?? $default_faq['pl'];
        }
    } catch (Exception $e) {
        error_log("FAQ error: " . $e->getMessage());
        $faq_items = [
            ['question' => 'Jak mogę złożyć zamówienie?', 'answer' => 'Skontaktuj się z nami przez formularz kontaktowy.']
        ];
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'faq' => $faq_items
    ]);
// ===== HELPER: Advanced Response Generator with Extended Rules =====
function generateSimpleResponse($message, $user_name, $products, $lang = 'pl') {
    $message_lower = mb_strtolower($message, 'UTF-8');
    
    $greeting = $user_name ? "Cześć {$user_name}! " : "Cześć! ";
    
    // ========================================
    // 1. POWITANIA
    // ========================================
    if (preg_match('/(dzień dobry|cześć|hej|witaj|siema|hello|hi|hola|witam|buenos días|good morning)/ui', $message)) {
        $responses = [
            'pl' => $greeting . "Miło Cię widzieć! W czym mogę Ci dzisiaj pomóc?",
            'en' => $greeting . "Nice to see you! How can I help you today?",
            'es' => $greeting . "¡Encantado de verte! ¿Cómo puedo ayudarte hoy?"
        ];
        return $responses[$lang] ?? $responses['pl'];
    }
    
    // ========================================
    // 2. POŻEGNANIA
    // ========================================
    if (preg_match('/(do widzenia|żegnaj|bye|papa|adios|goodbye|hasta luego)/ui', $message)) {
        $responses = [
            'pl' => "Do widzenia! Zapraszam ponownie! Jeśli będziesz mieć pytania, jestem do Twojej dyspozycji. 😊",
            'en' => "Goodbye! Come back anytime! If you have questions, I'm here to help. 😊",
            'es' => "¡Adiós! ¡Vuelve pronto! Si tienes preguntas, estoy aquí para ayudar. 😊"
        ];
        return $responses[$lang] ?? $responses['pl'];
    }
    
    // ========================================
    // 3. PODZIĘKOWANIA
    // ========================================
    if (preg_match('/(dzięk|dziękuję|thank|gracias|merci|thx)/ui', $message)) {
        $responses = [
            'pl' => "Nie ma za co! Cieszę się, że mogłem pomóc. Czy mogę Ci jeszcze w czymś pomóc?",
            'en' => "You're welcome! I'm glad I could help. Can I help you with anything else?",
            'es' => "¡De nada! Me alegro de poder ayudar. ¿Puedo ayudarte con algo más?"
        ];
        return $responses[$lang] ?? $responses['pl'];
    }
    
    // ========================================
    // 4. JAK SIĘ MASZ / CO SŁYCHAĆ
    // ========================================
    if (preg_match('/(jak się masz|co słychać|how are you|como estas|que tal)/ui', $message)) {
        $responses = [
            'pl' => "Dziękuję za pytanie! Jestem gotowy do pomocy. 😊 W czym mogę Ci pomóc?",
            'en' => "Thanks for asking! I'm ready to help. 😊 What can I do for you?",
            'es' => "¡Gracias por preguntar! Estoy listo para ayudar. 😊 ¿En qué puedo ayudarte?"
        ];
        return $responses[$lang] ?? $responses['pl'];
    }
    
    // ========================================
    // 5. PRODUKTY - OGÓLNE
    // ========================================
    if (preg_match('/(produkt|ofert|katalog|asortyment|co macie|what do you have|que tienen)/ui', $message)) {
        $responses = [
            'pl' => $greeting . "Mamy szeroki wybór produktów:\n\n" .
                    "🪟 Okna PVC i drewniane\n" .
                    "🚪 Drzwi wewnętrzne i zewnętrzne\n" .
                    "🔥 Panele i folie grzewcze\n" .
                    "🔧 Profile i akcesoria\n\n" .
                    "Możesz przeglądać pełną ofertę na stronie produktów. Czy szukasz czegoś konkretnego?",
            'en' => $greeting . "We have a wide range of products:\n\n" .
                    "🪟 PVC and wooden windows\n" .
                    "🚪 Interior and exterior doors\n" .
                    "🔥 Heating panels and films\n" .
                    "🔧 Profiles and accessories\n\n" .
                    "You can browse the full offer on the products page. Are you looking for something specific?",
            'es' => $greeting . "Tenemos una amplia gama de productos:\n\n" .
                    "🪟 Ventanas PVC y de madera\n" .
                    "🚪 Puertas interiores y exteriores\n" .
                    "🔥 Paneles y películas de calefacción\n" .
                    "🔧 Perfiles y accesorios\n\n" .
                    "¿Buscas algo específico?"
        ];
        return $responses[$lang] ?? $responses['pl'];
    }
    
    // ========================================
    // 6. OKNA
    // ========================================
    if (preg_match('/(okn|window|ventana)/ui', $message)) {
        $responses = [
            'pl' => $greeting . "Oferujemy kilka rodzajów okien:\n\n" .
                    "🪟 **Okna PVC** - od 450 PLN\n" .
                    "   • Energooszczędne\n" .
                    "   • Doskonała izolacja\n" .
                    "   • Funkcja uchylno-rozwieralna\n\n" .
                    "🪟 **Okna Drewniane** - od 850 PLN\n" .
                    "   • Naturalne materiały\n" .
                    "   • Elegancki wygląd\n" .
                    "   • Wysoka trwałość\n\n" .
                    "Czy mogę pomóc w wyborze konkretnego modelu?",
            'en' => $greeting . "We offer several types of windows:\n\n" .
                    "🪟 **PVC Windows** - from 450 PLN\n" .
                    "   • Energy efficient\n" .
                    "   • Excellent insulation\n" .
                    "   • Tilt & turn function\n\n" .
                    "🪟 **Wooden Windows** - from 850 PLN\n" .
                    "   • Natural materials\n" .
                    "   • Elegant look\n" .
                    "   • High durability\n\n" .
                    "Can I help you choose a specific model?",
            'es' => $greeting . "Ofrecemos varios tipos de ventanas:\n\n" .
                    "🪟 **Ventanas PVC** - desde 450 PLN\n" .
                    "🪟 **Ventanas de Madera** - desde 850 PLN\n\n" .
                    "¿Puedo ayudarte a elegir un modelo específico?"
        ];
        return $responses[$lang] ?? $responses['pl'];
    }
    
    // ========================================
    // 7. DRZWI
    // ========================================
    if (preg_match('/(drzw|door|puerta)/ui', $message)) {
        $responses = [
            'pl' => $greeting . "W naszej ofercie znajdziesz:\n\n" .
                    "🚪 **Drzwi Wewnętrzne** - od 399 PLN\n" .
                    "   • Różne kolory i wzory\n" .
                    "   • Bezprzylgowe\n" .
                    "   • Wysoka jakość wykonania\n\n" .
                    "🚪 **Drzwi Zewnętrzne** - od 1299 PLN\n" .
                    "   • Antywłamaniowe\n" .
                    "   • Izolacja termiczna\n" .
                    "   • Odporne na warunki atmosferyczne\n\n" .
                    "Jakiego typu drzwi szukasz?",
            'en' => $greeting . "In our offer you will find:\n\n" .
                    "🚪 **Interior Doors** - from 399 PLN\n" .
                    "🚪 **Exterior Doors** - from 1299 PLN\n\n" .
                    "What type of doors are you looking for?",
            'es' => $greeting . "En nuestra oferta encontrarás:\n\n" .
                    "🚪 **Puertas Interiores** - desde 399 PLN\n" .
                    "🚪 **Puertas Exteriores** - desde 1299 PLN\n\n" .
                    "¿Qué tipo de puertas buscas?"
        ];
        return $responses[$lang] ?? $responses['pl'];
    }
    
    // ========================================
    // 8. PANELE GRZEWCZE
    // ========================================
    if (preg_match('/(panel|grzew|ogrzew|heating|calefacción)/ui', $message)) {
        $responses = [
            'pl' => $greeting . "Oferujemy nowoczesne systemy ogrzewania:\n\n" .
                    "🔥 **Panele Grzewcze** - od 499 PLN\n" .
                    "   • Na podczerwień\n" .
                    "   • Montaż ścienny lub sufitowy\n" .
                    "   • Energooszczędne\n" .
                    "   • Moc: 300W - 1200W\n\n" .
                    "🔥 **Folie Grzewcze** - od 89 PLN/m²\n" .
                    "   • Do ogrzewania podłogowego\n" .
                    "   • Równomierne ciepło\n" .
                    "   • Łatwy montaż\n\n" .
                    "Jakie powierzchnie chcesz ogrzać?",
            'en' => $greeting . "We offer modern heating systems:\n\n" .
                    "🔥 **Heating Panels** - from 499 PLN\n" .
                    "🔥 **Heating Films** - from 89 PLN/m²\n\n" .
                    "What surfaces do you want to heat?",
            'es' => $greeting . "Ofrecemos sistemas de calefacción modernos:\n\n" .
                    "🔥 **Paneles de Calefacción** - desde 499 PLN\n" .
                    "🔥 **Películas de Calefacción** - desde 89 PLN/m²\n\n" .
                    "¿Qué superficies quieres calentar?"
        ];
        return $responses[$lang] ?? $responses['pl'];
    }
    
    // ========================================
    // 9. CENY
    // ========================================
    if (preg_match('/(cena|cen|ile kosztuje|price|precio|koszt|how much)/ui', $message)) {
        $responses = [
            'pl' => $greeting . "Oto przegląd naszych cen:\n\n" .
                    "💰 **Okna PVC:** 450-1600 PLN\n" .
                    "💰 **Okna Drewniane:** 850-2500 PLN\n" .
                    "💰 **Drzwi Wewnętrzne:** 399-899 PLN\n" .
                    "💰 **Drzwi Zewnętrzne:** 1299-3500 PLN\n" .
                    "💰 **Panele Grzewcze:** 499-1200 PLN\n" .
                    "💰 **Folie Grzewcze:** 89 PLN/m²\n\n" .
                    "Dokładna cena zależy od wymiarów i specyfikacji. Skontaktuj się z nami, aby otrzymać indywidualną wycenę!",
            'en' => $greeting . "Here's an overview of our prices:\n\n" .
                    "💰 **PVC Windows:** 450-1600 PLN\n" .
                    "💰 **Wooden Windows:** 850-2500 PLN\n" .
                    "💰 **Interior Doors:** 399-899 PLN\n" .
                    "💰 **Exterior Doors:** 1299-3500 PLN\n" .
                    "💰 **Heating Panels:** 499-1200 PLN\n\n" .
                    "Contact us for a personalized quote!",
            'es' => $greeting . "Aquí está una visión general de nuestros precios:\n\n" .
                    "💰 **Ventanas PVC:** 450-1600 PLN\n" .
                    "💰 **Ventanas de Madera:** 850-2500 PLN\n" .
                    "💰 **Puertas Interiores:** 399-899 PLN\n\n" .
                    "¡Contáctanos para un presupuesto personalizado!"
        ];
        return $responses[$lang] ?? $responses['pl'];
    }
    
    // ========================================
    // 10. DOSTAWA
    // ========================================
    if (preg_match('/(dostaw|wysyłk|transport|shipping|delivery|envío|kiedy dostanę)/ui', $message)) {
        $responses = [
            'pl' => "📦 **Opcje dostawy:**\n\n" .
                    "🚚 **Dostawa Standardowa** - 7-14 dni roboczych\n" .
                    "   • Bezpłatna przy zamówieniu powyżej 2000 PLN\n" .
                    "   • Koszt: 150 PLN\n\n" .
                    "⚡ **Dostawa Ekspresowa** - 3-5 dni roboczych\n" .
                    "   • Koszt: 350 PLN\n" .
                    "   • Dostępna dla wybranych produktów\n\n" .
                    "📍 **Odbiór osobisty** - 0 PLN\n" .
                    "   • Możliwy po wcześniejszym umówieniu\n\n" .
                    "Dostarczamy na terenie całej Polski!",
            'en' => "📦 **Delivery options:**\n\n" .
                    "🚚 **Standard Delivery** - 7-14 business days\n" .
                    "   • Free for orders over 2000 PLN\n" .
                    "   • Cost: 150 PLN\n\n" .
                    "⚡ **Express Delivery** - 3-5 business days\n" .
                    "   • Cost: 350 PLN\n\n" .
                    "We deliver throughout Poland!",
            'es' => "📦 **Opciones de entrega:**\n\n" .
                    "🚚 **Entrega Estándar** - 7-14 días hábiles\n" .
                    "   • Gratis para pedidos superiores a 2000 PLN\n" .
                    "   • Costo: 150 PLN\n\n" .
                    "⚡ **Entrega Exprés** - 3-5 días hábiles\n" .
                    "   • Costo: 350 PLN\n\n" .
                    "¡Entregamos en toda Polonia!"
        ];
        return $responses[$lang] ?? $responses['pl'];
    }
    
    // ========================================
    // 11. MONTAŻ / INSTALACJA
    // ========================================
    if (preg_match('/(montaż|instalacj|installation|instalar|czy montujecie|czy instalujecie)/ui', $message)) {
        $responses = [
            'pl' => "🔧 **Usługi montażowe:**\n\n" .
                    "✅ Tak! Oferujemy profesjonalny montaż wszystkich produktów\n\n" .
                    "👷 **Nasz zespół:**\n" .
                    "   • Wieloletnie doświadczenie\n" .
                    "   • Profesjonalne narzędzia\n" .
                    "   • Gwarancja na wykonane prace\n" .
                    "   • Sprzątnięcie po montażu\n\n" .
                    "💰 **Koszt montażu:**\n" .
                    "   • Okna: od 150 PLN/szt\n" .
                    "   • Drzwi: od 200 PLN/szt\n" .
                    "   • Panele grzewcze: od 100 PLN\n\n" .
                    "Skontaktuj się z nami, aby umówić termin!",
            'en' => "🔧 **Installation services:**\n\n" .
                    "✅ Yes! We offer professional installation\n\n" .
                    "👷 **Our team:**\n" .
                    "   • Years of experience\n" .
                    "   • Professional tools\n" .
                    "   • Warranty on work\n\n" .
                    "💰 **Installation cost:**\n" .
                    "   • Windows: from 150 PLN/pc\n" .
                    "   • Doors: from 200 PLN/pc\n\n" .
                    "Contact us to schedule!",
            'es' => "🔧 **Servicios de instalación:**\n\n" .
                    "✅ ¡Sí! Ofrecemos instalación profesional\n\n" .
                    "👷 **Nuestro equipo:**\n" .
                    "   • Años de experiencia\n" .
                    "   • Herramientas profesionales\n" .
                    "   • Garantía en el trabajo\n\n" .
                    "💰 **Costo de instalación:**\n" .
                    "   • Ventanas: desde 150 PLN/ud\n" .
                    "   • Puertas: desde 200 PLN/ud\n\n" .
                    "¡Contáctanos para programar!"
        ];
        return $responses[$lang] ?? $responses['pl'];
    }
    
    // ========================================
    // 12. PŁATNOŚCI
    // ========================================
    if (preg_match('/(płatnoś|payment|payment|forma płatności|jak zapłacić|pago)/ui', $message)) {
        $responses = [
            'pl' => "💳 **Formy płatności:**\n\n" .
                    "✅ Przelew bankowy tradycyjny\n" .
                    "✅ Płatność online (PayU, Przelewy24)\n" .
                    "✅ Karta kredytowa/debetowa\n" .
                    "✅ BLIK\n" .
                    //"✅ Raty 0% (przy zamówieniach powyżej 1000 PLN)\n" .
                    "✅ Płatność przy odbiorze (za pobraniem)\n\n" .
                    "🔒 Wszystkie płatności są zabezpieczone!",
            'en' => "💳 **Payment methods:**\n\n" .
                    "✅ Bank transfer\n" .
                    "✅ Online payment\n" .
                    "✅ Credit/debit card\n" .
                    //"✅ 0% installments (orders over 1000 PLN)\n" .
                    "✅ Cash on delivery\n\n" .
                    "🔒 All payments are secured!",
            'es' => "💳 **Métodos de pago:**\n\n" .
                    "✅ Transferencia bancaria\n" .
                    "✅ Pago en línea\n" .
                    "✅ Tarjeta de crédito/débito\n" .
                    //"✅ Cuotas 0% (pedidos superiores a 1000 PLN)\n" .
                    "✅ Pago contra reembolso\n\n" .
                    "🔒 ¡Todos los pagos están asegurados!"
        ];
        return $responses[$lang] ?? $responses['pl'];
    }
    
    // ========================================
    // 13. GWARANCJA
    // ========================================
    if (preg_match('/(gwarancj|warranty|garantía|reklamacj)/ui', $message)) {
        $responses = [
            'pl' => "🛡️ **Gwarancja:**\n\n" .
                    "✅ **Okna i drzwi:** 5-10 lat gwarancji producenta\n" .
                    "✅ **Panele grzewcze:** 2-5 lat\n" .
                    "✅ **Montaż:** 2 lata gwarancji na usługę\n\n" .
                    "📋 **W ramach gwarancji:**\n" .
                    "   • Naprawa lub wymiana wadliwego produktu\n" .
                    "   • Bezpłatny serwis\n" .
                    "   • Wsparcie techniczne\n\n" .
                    "📞 W razie problemów, skontaktuj się z nami!",
            'en' => "🛡️ **Warranty:**\n\n" .
                    "✅ **Windows & doors:** 5-10 years manufacturer warranty\n" .
                    "✅ **Heating panels:** 2-5 years\n" .
                    "✅ **Installation:** 2 years service warranty\n\n" .
                    "📋 **Warranty includes:**\n" .
                    "   • Repair or replacement\n" .
                    "   • Free service\n" .
                    "   • Technical support\n\n" .
                    "📞 Contact us if you have any issues!",
            'es' => "🛡️ **Garantía:**\n\n" .
                    "✅ **Ventanas y puertas:** 5-10 años garantía del fabricante\n" .
                    "✅ **Paneles de calefacción:** 2-5 años\n" .
                    "✅ **Instalación:** 2 años garantía de servicio\n\n" .
                    "📞 ¡Contáctanos si tienes algún problema!"
        ];
        return $responses[$lang] ?? $responses['pl'];
    }
    
    // ========================================
    // 14. ZWROTY / REKLAMACJE
    // ========================================
    if (preg_match('/(zwrot|zwrócić|return|refund|devoluci|oddać|zmienić)/ui', $message)) {
        $responses = [
            'pl' => "🔄 **Zwroty i reklamacje:**\n\n" .
                    "✅ **Prawo do zwrotu:** 14 dni od otrzymania\n" .
                    "   • Produkt musi być nieużywany\n" .
                    "   • W oryginalnym opakowaniu\n\n" .
                    "✅ **Reklamacja:**\n" .
                    "   • W okresie gwarancji\n" .
                    "   • Bezpłatna naprawa lub wymiana\n\n" .
                    "📧 Zgłoszenie: kontakt@sersoltec.com\n" .
                    "📞 Tel: +34 666 666 666\n\n" .
                    "Odpowiadamy w ciągu 24h!",
            'en' => "🔄 **Returns and complaints:**\n\n" .
                    "✅ **Right to return:** 14 days from receipt\n" .
                    "   • Product must be unused\n" .
                    "   • In original packaging\n\n" .
                    "✅ **Complaint:**\n" .
                    "   • During warranty period\n" .
                    "   • Free repair or replacement\n\n" .
                    "📧 Report: kontakt@sersoltec.com\n" .
                    "📞 Tel: +34 666 666 666\n\n" .
                    "We respond within 24h!",
            'es' => "🔄 **Devoluciones y reclamaciones:**\n\n" .
                    "✅ **Derecho de devolución:** 14 días desde la recepción\n" .
                    "   • El producto debe estar sin usar\n" .
                    "   • En embalaje original\n\n" .
                    "📧 Reporte: kontakt@sersoltec.com\n" .
                    "📞 Tel: +34 666 666 666"
        ];
        return $responses[$lang] ?? $responses['pl'];
    }
    
    // ========================================
    // 15. JAK ZAMÓWIĆ
    // ========================================
    if (preg_match('/(jak zamówić|jak kupić|how to order|como ordenar|order|zamówienie)/ui', $message)) {
        if ($user_name) {
            $responses = [
                'pl' => "🛒 **Jak złożyć zamówienie:**\n\n" .
                        "1️⃣ Przeglądaj produkty w katalogu\n" .
                        "2️⃣ Dodaj wybrane produkty do zapytania\n" .
                        "3️⃣ Wypełnij formularz kontaktowy\n" .
                        "4️⃣ Otrzymasz wycenę w ciągu 24h\n" .
                        "5️⃣ Potwierdzasz zamówienie\n" .
                        "6️⃣ Realizacja i dostawa\n\n" .
                        "💡 Możesz też zadzwonić: +34 666 666 666",
                'en' => "🛒 **How to order:**\n\n" .
                        "1️⃣ Browse products in catalog\n" .
                        "2️⃣ Add products to inquiry\n" .
                        "3️⃣ Fill contact form\n" .
                        "4️⃣ Get quote within 24h\n" .
                        "5️⃣ Confirm order\n" .
                        "6️⃣ Delivery\n\n" .
                        "💡 Or call: +34 666 666 666",
                'es' => "🛒 **Cómo ordenar:**\n\n" .
                        "1️⃣ Navega productos en catálogo\n" .
                        "2️⃣ Agrega productos a consulta\n" .
                        "3️⃣ Completa formulario de contacto\n" .
                        "4️⃣ Recibe presupuesto en 24h\n" .
                        "5️⃣ Confirma pedido\n" .
                        "6️⃣ Entrega\n\n" .
                        "💡 O llama: +34 666 666 666"
            ];
            return $responses[$lang] ?? $responses['pl'];
        } else {
            $responses = [
                'pl' => "🛒 **Jak złożyć zamówienie:**\n\n" .
                        "Aby zobaczyć historię swoich zamówień, musisz się zalogować.\n\n" .
                        "Jeśli chcesz złożyć nowe zamówienie:\n" .
                        "1️⃣ Przeglądaj produkty\n" .
                        "2️⃣ Wypełnij formularz kontaktowy\n" .
                        "3️⃣ Otrzymasz wycenę w 24h",
                'en' => "🛒 **How to order:**\n\n" .
                        "To see your order history, please log in.\n\n" .
                        "To place a new order:\n" .
                        "1️⃣ Browse products\n" .
                        "2️⃣ Fill contact form\n" .
                        "3️⃣ Get quote in 24h",
                'es' => "🛒 **Cómo ordenar:**\n\n" .
                        "Para ver tu historial, inicia sesión.\n\n" .
                        "Para hacer un nuevo pedido:\n" .
                        "1️⃣ Navega productos\n" .
                        "2️⃣ Completa formulario\n" .
                        "3️⃣ Recibe presupuesto en 24h"
            ];
            return $responses[$lang] ?? $responses['pl'];
        }
    }
    
    // ========================================
    // 16. KONTAKT
    // ========================================
    if (preg_match('/(kontakt|pomoc|help|contact|ayuda|email|telefon|phone|numer)/ui', $message)) {
        $contact_email = defined('CONTACT_EMAIL') ? CONTACT_EMAIL : 'kontakt@sersoltec.com';
        $responses = [
            'pl' => "📞 **Skontaktuj się z nami:**\n\n" .
                    "📧 Email: " . $contact_email . "\n" .
                    "📱 Telefon: +34 666 666 666\n" .
                    "💬 WhatsApp: +34 666 666 666\n\n" .
                    "🕐 **Godziny pracy:**\n" .
                    "Pon-Pt: 8:00-17:00\n" .
                    "Sob: 9:00-14:00\n\n" .
                    "Możesz też wypełnić formularz kontaktowy na stronie!",
            'en' => "📞 **Contact us:**\n\n" .
                    "📧 Email: " . $contact_email . "\n" .
                    "📱 Phone: +34 666 666 666\n" .
                    "💬 WhatsApp: +34 666 666 666\n\n" .
                    "🕐 **Working hours:**\n" .
                    "Mon-Fri: 8:00-17:00\n" .
                    "Sat: 9:00-14:00\n\n" .
                    "You can also fill the contact form!",
            'es' => "📞 **Contáctanos:**\n\n" .
                    "📧 Email: " . $contact_email . "\n" .
                    "📱 Teléfono: +34 666 666 666\n" .
                    "💬 WhatsApp: +34 666 666 666\n\n" .
                    "🕐 **Horario:**\n" .
                    "Lun-Vie: 8:00-17:00\n" .
                    "Sáb: 9:00-14:00\n\n" .
                    "¡También puedes completar el formulario de contacto!"
        ];
        return $responses[$lang] ?? $responses['pl'];
    }
    
    // ========================================
    // 17. LOKALIZACJA / GDZIE JESTEŚCIE
    // ========================================
    if (preg_match('/(gdzie jesteście|lokalizacj|adres|address|ubicación|location)/ui', $message)) {
        $responses = [
            'pl' => "📍 **Nasza lokalizacja:**\n\n" .
                    "🏢 Sersoltec Sp. z o.o.\n" .
                    "📮 Przemyśl, Polska\n\n" .
                    "🚚 Dostarczamy na terenie całej Polski!\n\n" .
                    "Skontaktuj się z nami, aby umówić wizytę lub odbiór osobisty:\n" .
                    "📞 +34 666 666 666",
            'en' => "📍 **Our location:**\n\n" .
                    "🏢 Sersoltec Sp. z o.o.\n" .
                    "📮 Valencia, Spanish\n\n" .
                    "🚚 We deliver throughout Poland!\n\n" .
                    "Contact us to schedule a visit:\n" .
                    "📞 +34 666 666 666",
            'es' => "📍 **Nuestra ubicación:**\n\n" .
                    "🏢 Sersoltec Sp. z o.o.\n" .
                    "📮 Valencia, Spanish\n\n" .
                    "🚚 ¡Entregamos en toda Polonia!\n\n" .
                    "Contáctanos para programar una visita:\n" .
                    "📞 +34 666 666 666"
        ];
        return $responses[$lang] ?? $responses['pl'];
    }
    
    // ========================================
    // 18. RABATY / PROMOCJE
    // ========================================
    if (preg_match('/(rabat|zniżk|promocj|discount|descuento|sale|oferta specjalna)/ui', $message)) {
        $responses = [
            'pl' => "🎉 **Aktywne promocje:**\n\n" .
                    "💰 Rabat 5% przy zakupie powyżej 5000 PLN\n" .
                    "💰 Rabat 8% przy zakupie powyżej 10000 PLN\n" .
              //      "💰 Darmowa dostawa przy zamówieniu powyżej 2000 PLN\n" .
                   // "💰 Raty 0% przy zakupie powyżej 1000 PLN\n\n" .
                    "📧 Zapisz się do newslettera, aby otrzymywać ekskluzywne oferty!\n\n" .
                    "Skontaktuj się z nami, aby poznać szczegóły!",
            'en' => "🎉 **Active promotions:**\n\n" .
                    "💰 5% discount on purchases over 5000 PLN\n" .
                    "💰 8% discount on purchases over 10000 PLN\n" .
                //    "💰 Free delivery for orders over 2000 PLN\n" .
                 //   "💰 0% installments for purchases over 1000 PLN\n\n" .
                    "📧 Subscribe to newsletter for exclusive offers!\n\n" .
                    "Contact us for details!",
            'es' => "🎉 **Promociones activas:**\n\n" .
                    "💰 5% descuento en compras superiores a 5000 PLN\n" .
                    "💰 8% descuento en compras superiores a 10000 PLN\n" .
                ///    "💰 Envío gratis para pedidos superiores a 2000 PLN\n\n" .
                //    "📧 ¡Suscríbete al newsletter para ofertas exclusivas!\n\n" .
                    "¡Contáctanos para más detalles!"
        ];
        return $responses[$lang] ?? $responses['pl'];
    }
    
    // ========================================
    // 19. KTO TO JEST / INFORMACJE O FIRMIE
    // ========================================
    if (preg_match('/(kim jesteście|o firmie|about|about us|quienes somos|who are you)/ui', $message)) {
        $responses = [
            'pl' => "🏢 **O nas:**\n\n" .
                    "Jesteśmy **Sersoltec** - liderem w branży stolarki budowlanej!\n\n" .
                    "✨ **Nasza historia:**\n" .
                    "   • Wieloletnie doświadczenie\n" .
                    "   • Tysiące zadowolonych klientów\n" .
                    "   • Najwyższa jakość produktów\n\n" .
                    "🎯 **Nasza misja:**\n" .
                    "Dostarczać produkty najwyższej jakości w konkurencyjnych cenach, z pełnym wsparciem dla klientów.\n\n" .
                    "🤝 Zaufało nam już ponad 1000+ klientów!",
            'en' => "🏢 **About us:**\n\n" .
                    "We are **Sersoltec** - leader in construction joinery!\n\n" .
                    "✨ **Our story:**\n" .
                    "   • Years of experience\n" .
                    "   • Thousands of satisfied customers\n" .
                    "   • Highest quality products\n\n" .
                    "🤝 Over 1000+ customers trust us!",
            'es' => "🏢 **Sobre nosotros:**\n\n" .
                    "Somos **Sersoltec** - ¡líder en carpintería de construcción!\n\n" .
                    "✨ **Nuestra historia:**\n" .
                    "   • Años de experiencia\n" .
                    "   • Miles de clientes satisfechos\n" .
                    "   • Productos de máxima calidad\n\n" .
                    "🤝 ¡Más de 1000+ clientes confían en nosotros!"
        ];
        return $responses[$lang] ?? $responses['pl'];
    }
    
    // ========================================
    // 20. DOMYŚLNA ODPOWIEDŹ
    // ========================================
    $responses = [
        'pl' => $greeting . "Dziękuję za wiadomość!\n\n" .
                "Jestem botem Sersoltec i mogę pomóc Ci z:\n\n" .
                "🪟 Produktami (okna, drzwi, panele)\n" .
                "💰 Cenami i promocjami\n" .
                "📦 Dostawą i montażem\n" .
                "💳 Płatnościami i gwarancją\n" .
                "📞 Kontaktem z firmą\n\n" .
                "W czym dokładnie mogę Ci pomóc?",
        'en' => $greeting . "Thank you for your message!\n\n" .
                "I'm Sersoltec bot and I can help you with:\n\n" .
                "🪟 Products (windows, doors, panels)\n" .
                "💰 Prices and promotions\n" .
                "📦 Delivery and installation\n" .
                "💳 Payment and warranty\n" .
                "📞 Company contact\n\n" .
                "What exactly can I help you with?",
        'es' => $greeting . "¡Gracias por tu mensaje!\n\n" .
                "Soy el bot de Sersoltec y puedo ayudarte con:\n\n" .
                "🪟 Productos (ventanas, puertas, paneles)\n" .
                "💰 Precios y promociones\n" .
                "📦 Entrega e instalación\n" .
                "💳 Pago y garantía\n" .
                "📞 Contacto con la empresa\n\n" .
                "¿En qué exactamente puedo ayudarte?"
    ];
    return $responses[$lang] ?? $responses['pl'];
}
