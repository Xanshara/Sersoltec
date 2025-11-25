<?php
/**
 * SERSOLTEC v2.3c - Email Translations
 * Multi-language support for email templates: PL/EN/ES
 */

$emailTranslations = [
    
    // POLISH - Welcome Email
    'pl' => [
        'welcome' => [
            'welcome_title' => 'Witamy w SERSOLTEC!',
            'greeting' => 'Cześć',
            'welcome_message' => 'Dziękujemy za założenie konta w SERSOLTEC! Cieszymy się, że dołączyłeś do naszej społeczności. Twoje konto zostało utworzone pomyślnie.',
            'your_email' => 'Twój email',
            'verify_instruction' => 'Aby aktywować swoje konto i zacząć korzystać ze wszystkich funkcji, kliknij przycisk poniżej:',
            'verify_button' => 'Zweryfikuj Email',
            'link_not_working' => 'Link nie działa? Skopiuj i wklej ten adres URL do przeglądarki:',
            'what_you_can_do' => 'Co możesz robić?',
            'feature_1_title' => 'Przeglądaj Produkty',
            'feature_1_desc' => 'Odkryj naszą szeroką gamę okien i drzwi',
            'feature_2_title' => 'Lista Życzeń',
            'feature_2_desc' => 'Zapisuj ulubione produkty',
            'feature_3_title' => 'Szybkie Zamówienia',
            'feature_3_desc' => 'Złóż zamówienie w kilka kliknięć',
            'feature_4_title' => 'Opinie i Oceny',
            'feature_4_desc' => 'Dziel się swoimi doświadczeniami',
            'thanks' => 'Dziękujemy za zaufanie!',
            'footer_text' => 'Ten email został wysłany automatycznie. Jeśli nie zakładałeś konta, zignoruj tę wiadomość.',
            'visit_website' => 'Odwiedź stronę',
            'contact_us' => 'Kontakt',
        ],
        'password_reset' => [
            'reset_title' => 'Resetowanie Hasła',
            'greeting' => 'Cześć',
            'reset_message' => 'Otrzymaliśmy żądanie zresetowania hasła do Twojego konta SERSOLTEC. Jeśli to Ty złożyłeś to żądanie, kliknij przycisk poniżej, aby ustawić nowe hasło.',
            'security_notice' => 'Uwaga bezpieczeństwa',
            'security_message' => 'Ten link resetujący jest ważny przez 1 godzinę i może być użyty tylko raz.',
            'click_instruction' => 'Kliknij przycisk poniżej, aby kontynuować:',
            'reset_button' => 'Zresetuj Hasło',
            'expiration_notice' => 'Link wygasa za 1 godzinę',
            'link_not_working' => 'Link nie działa? Skopiuj i wklej ten adres URL do przeglądarki:',
            'not_requested' => 'Jeśli nie prosiłeś o reset hasła, możesz zignorować ten email. Twoje hasło pozostanie niezmienione.',
            'thanks' => 'Pozdrawiamy,',
            'footer_text' => 'Ten email został wysłany, ponieważ ktoś poprosił o reset hasła dla tego konta.',
            'visit_website' => 'Strona główna',
            'contact_support' => 'Pomoc techniczna',
        ],
        'password_changed' => [
            'success_title' => 'Hasło Zmienione',
            'greeting' => 'Cześć',
            'success_message' => 'Hasło do Twojego konta SERSOLTEC zostało pomyślnie zmienione. Od teraz możesz logować się używając nowego hasła.',
            'email_label' => 'Email',
            'time_label' => 'Data zmiany',
            'ip_label' => 'Adres IP',
            'confirmation_message' => 'To jest email potwierdzający zmianę hasła. Nie musisz podejmować żadnych działań.',
            'security_title' => 'Nie dokonałeś tej zmiany?',
            'security_message' => 'Jeśli nie zmieniałeś hasła, natychmiast skontaktuj się z naszym zespołem wsparcia. Może to oznaczać, że ktoś uzyskał dostęp do Twojego konta.',
            'contact_button' => 'Skontaktuj się z supportem',
            'tips_title' => 'Wskazówki dotyczące bezpieczeństwa',
            'tip_1' => 'Używaj silnych, unikalnych haseł dla każdego konta',
            'tip_2' => 'Nigdy nie udostępniaj swojego hasła innym osobom',
            'tip_3' => 'Zmieniaj hasło regularnie (co 3-6 miesięcy)',
            'tip_4' => 'Uważaj na phishing - zawsze sprawdzaj adres URL',
            'thanks' => 'Dziękujemy,',
            'footer_text' => 'Email wysłany automatycznie po zmianie hasła.',
            'visit_website' => 'Strona główna',
            'contact_support' => 'Pomoc techniczna',
        ],
    ],
    
    // ENGLISH - Welcome Email
    'en' => [
        'welcome' => [
            'welcome_title' => 'Welcome to SERSOLTEC!',
            'greeting' => 'Hi',
            'welcome_message' => 'Thank you for creating an account with SERSOLTEC! We\'re excited to have you join our community. Your account has been successfully created.',
            'your_email' => 'Your email',
            'verify_instruction' => 'To activate your account and start using all features, click the button below:',
            'verify_button' => 'Verify Email',
            'link_not_working' => 'Link not working? Copy and paste this URL into your browser:',
            'what_you_can_do' => 'What You Can Do',
            'feature_1_title' => 'Browse Products',
            'feature_1_desc' => 'Explore our wide range of windows and doors',
            'feature_2_title' => 'Wishlist',
            'feature_2_desc' => 'Save your favorite products',
            'feature_3_title' => 'Quick Orders',
            'feature_3_desc' => 'Place orders in just a few clicks',
            'feature_4_title' => 'Reviews & Ratings',
            'feature_4_desc' => 'Share your experiences',
            'thanks' => 'Thank you for your trust!',
            'footer_text' => 'This email was sent automatically. If you didn\'t create an account, please ignore this message.',
            'visit_website' => 'Visit Website',
            'contact_us' => 'Contact Us',
        ],
        'password_reset' => [
            'reset_title' => 'Password Reset',
            'greeting' => 'Hi',
            'reset_message' => 'We received a request to reset your SERSOLTEC account password. If you made this request, click the button below to set a new password.',
            'security_notice' => 'Security Notice',
            'security_message' => 'This reset link is valid for 1 hour and can only be used once.',
            'click_instruction' => 'Click the button below to continue:',
            'reset_button' => 'Reset Password',
            'expiration_notice' => 'Link expires in 1 hour',
            'link_not_working' => 'Link not working? Copy and paste this URL into your browser:',
            'not_requested' => 'If you didn\'t request a password reset, you can ignore this email. Your password will remain unchanged.',
            'thanks' => 'Best regards,',
            'footer_text' => 'This email was sent because someone requested a password reset for this account.',
            'visit_website' => 'Homepage',
            'contact_support' => 'Technical Support',
        ],
        'password_changed' => [
            'success_title' => 'Password Changed',
            'greeting' => 'Hi',
            'success_message' => 'Your SERSOLTEC account password has been successfully changed. You can now log in using your new password.',
            'email_label' => 'Email',
            'time_label' => 'Change Date',
            'ip_label' => 'IP Address',
            'confirmation_message' => 'This is a confirmation email for your password change. No action is required.',
            'security_title' => 'Didn\'t make this change?',
            'security_message' => 'If you didn\'t change your password, immediately contact our support team. This may indicate that someone has accessed your account.',
            'contact_button' => 'Contact Support',
            'tips_title' => 'Security Tips',
            'tip_1' => 'Use strong, unique passwords for each account',
            'tip_2' => 'Never share your password with others',
            'tip_3' => 'Change your password regularly (every 3-6 months)',
            'tip_4' => 'Beware of phishing - always check the URL',
            'thanks' => 'Thank you,',
            'footer_text' => 'Email sent automatically after password change.',
            'visit_website' => 'Homepage',
            'contact_support' => 'Technical Support',
        ],
    ],
    
    // SPANISH - Welcome Email
    'es' => [
        'welcome' => [
            'welcome_title' => '¡Bienvenido a SERSOLTEC!',
            'greeting' => 'Hola',
            'welcome_message' => '¡Gracias por crear una cuenta en SERSOLTEC! Nos emociona tenerte en nuestra comunidad. Tu cuenta ha sido creada exitosamente.',
            'your_email' => 'Tu email',
            'verify_instruction' => 'Para activar tu cuenta y comenzar a usar todas las funciones, haz clic en el botón a continuación:',
            'verify_button' => 'Verificar Email',
            'link_not_working' => '¿El enlace no funciona? Copia y pega esta URL en tu navegador:',
            'what_you_can_do' => '¿Qué Puedes Hacer?',
            'feature_1_title' => 'Explorar Productos',
            'feature_1_desc' => 'Descubre nuestra amplia gama de ventanas y puertas',
            'feature_2_title' => 'Lista de Deseos',
            'feature_2_desc' => 'Guarda tus productos favoritos',
            'feature_3_title' => 'Pedidos Rápidos',
            'feature_3_desc' => 'Realiza pedidos en pocos clics',
            'feature_4_title' => 'Reseñas y Calificaciones',
            'feature_4_desc' => 'Comparte tus experiencias',
            'thanks' => '¡Gracias por tu confianza!',
            'footer_text' => 'Este correo fue enviado automáticamente. Si no creaste una cuenta, ignora este mensaje.',
            'visit_website' => 'Visitar Sitio',
            'contact_us' => 'Contacto',
        ],
        'password_reset' => [
            'reset_title' => 'Restablecer Contraseña',
            'greeting' => 'Hola',
            'reset_message' => 'Recibimos una solicitud para restablecer la contraseña de tu cuenta SERSOLTEC. Si hiciste esta solicitud, haz clic en el botón a continuación para establecer una nueva contraseña.',
            'security_notice' => 'Aviso de Seguridad',
            'security_message' => 'Este enlace de restablecimiento es válido por 1 hora y solo puede usarse una vez.',
            'click_instruction' => 'Haz clic en el botón a continuación para continuar:',
            'reset_button' => 'Restablecer Contraseña',
            'expiration_notice' => 'El enlace expira en 1 hora',
            'link_not_working' => '¿El enlace no funciona? Copia y pega esta URL en tu navegador:',
            'not_requested' => 'Si no solicitaste un restablecimiento de contraseña, puedes ignorar este correo. Tu contraseña permanecerá sin cambios.',
            'thanks' => 'Saludos cordiales,',
            'footer_text' => 'Este correo fue enviado porque alguien solicitó un restablecimiento de contraseña para esta cuenta.',
            'visit_website' => 'Página Principal',
            'contact_support' => 'Soporte Técnico',
        ],
        'password_changed' => [
            'success_title' => 'Contraseña Cambiada',
            'greeting' => 'Hola',
            'success_message' => 'La contraseña de tu cuenta SERSOLTEC ha sido cambiada exitosamente. Ahora puedes iniciar sesión usando tu nueva contraseña.',
            'email_label' => 'Email',
            'time_label' => 'Fecha de Cambio',
            'ip_label' => 'Dirección IP',
            'confirmation_message' => 'Este es un correo de confirmación de tu cambio de contraseña. No se requiere ninguna acción.',
            'security_title' => '¿No hiciste este cambio?',
            'security_message' => 'Si no cambiaste tu contraseña, contacta inmediatamente a nuestro equipo de soporte. Esto puede indicar que alguien ha accedido a tu cuenta.',
            'contact_button' => 'Contactar Soporte',
            'tips_title' => 'Consejos de Seguridad',
            'tip_1' => 'Usa contraseñas fuertes y únicas para cada cuenta',
            'tip_2' => 'Nunca compartas tu contraseña con otros',
            'tip_3' => 'Cambia tu contraseña regularmente (cada 3-6 meses)',
            'tip_4' => 'Cuidado con el phishing - siempre verifica la URL',
            'thanks' => 'Gracias,',
            'footer_text' => 'Correo enviado automáticamente después del cambio de contraseña.',
            'visit_website' => 'Página Principal',
            'contact_support' => 'Soporte Técnico',
        ],
    ],
];

/**
 * Get email translation
 * 
 * @param string $type Email type (welcome, password_reset, password_changed)
 * @param string $key Translation key
 * @param string $lang Language (pl, en, es)
 * @return string
 */
function et($type, $key, $lang = 'pl') {
    global $emailTranslations;
    return $emailTranslations[$lang][$type][$key] ?? $key;
}

/**
 * Replace placeholders in email template
 * 
 * @param string $template HTML template
 * @param array $data Data to replace
 * @return string
 */
function emailReplace($template, $data) {
    foreach ($data as $key => $value) {
        $template = str_replace('{{' . $key . '}}', $value, $template);
    }
    return $template;
}

/**
 * Load and process email template
 * 
 * @param string $templateFile Template filename
 * @param array $data Data to replace
 * @param string $lang Language
 * @return string
 */
function loadEmailTemplate($templateFile, $data, $lang = 'pl') {
    $templatePath = __DIR__ . '/../email-templates/' . $templateFile;
    
    if (!file_exists($templatePath)) {
        error_log("Email template not found: $templatePath");
        return '';
    }
    
    $template = file_get_contents($templatePath);
    
    // Add language to data
    $data['lang'] = $lang;
    
    // Replace placeholders
    return emailReplace($template, $data);
}
