<?php
/**
 * Funkcja zwracająca kod SVG na podstawie sluga kategorii.
 * W przypadku braku dopasowania zwraca pusty ciąg.
 */
function getCategorySvgIcon(string $slug): string
{
    $icons = [
        // 1. Okna PVC (slug: okna-pvc)
        'okna-pvc' => '
  <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
  <rect x="4" y="4" width="16" height="16" rx="2" fill="#DEDEDE" stroke="#212529" stroke-width="1.5"/>
  <rect x="6" y="6" width="12" height="12" fill="#F0F8FF" stroke="#212529" stroke-width="1.5"/>
  <line x1="12" y1="6" x2="12" y2="18" stroke="#212529" stroke-width="1.5"/>
  <line x1="6" y1="12" x2="18" y2="12" stroke="#212529" stroke-width="1.5"/>
</svg>
        ',
        // 2. Okna Drewniane (slug: okna-drewniane)
        'okna-drewniane' => '
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <rect x="4" y="4" width="16" height="16" rx="2" fill="#8B4513" stroke="#212529" stroke-width="1.5"/>
                  <rect x="6" y="6" width="12" height="12" fill="#FFC300" stroke="#212529" stroke-width="1.5"/>
                  <line x1="12" y1="6" x2="12" y2="18" stroke="#212529" stroke-width="1.5"/>
                  <line x1="6" y1="12" x2="18" y2="12" stroke="#212529" stroke-width="1.5"/>
                </svg>
        ',
        // 3. Panele Grzewcze (slug: panele-grzewcze)
        'panele-grzewcze' => '
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#cc5200" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="4" y="8" width="16" height="8" rx="1" ry="1"/>
                    <path d="M 7 5 V 8 M 12 5 V 8 M 17 5 V 8"/>
                    <path d="M 7 16 V 19 M 12 16 V 19 M 17 16 V 19"/>
                </svg>
        ',
        // 4. Folie Grzewcze (slug: folie-grzewcze)
        'folie-grzewcze' => '
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#212529" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M 4 20 C 6 10, 18 10, 20 20" stroke-dasharray="4 2"/>
                    <path d="M 12 5 L 10 10 L 14 10 L 12 15" stroke="#FDB813" stroke-width="1.5"/>
                </svg>
        ',
        // 5. Profile PVC (slug: profile-pvc)
        'profile-pvc' => '
<svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
  <path d="M 4 7 L 4 17 L 20 17 L 20 7 Z" fill="#DEDEDE" stroke="#212529" stroke-width="1.5"/>
  <line x1="6" y1="9.5" x2="18" y2="9.5" stroke="#4C4C4C" stroke-width="1"/>
  <line x1="6" y1="12" x2="18" y2="12" stroke="#4C4C4C" stroke-width="1.5"/>
  <line x1="6" y1="14.5" x2="18" y2="14.5" stroke="#4C4C4C" stroke-width="1"/>
  <rect x="5" y="6" width="14" height="2" fill="#f58025" rx="0.5"/>
</svg>
        ',
        // 6. Drzwi Wewnętrzne (slug: drzwi-wewnetrzne)
        'drzwi-wewnetrzne' => '
<svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
  <rect x="5" y="3" width="14" height="18" rx="1" fill="#CCCCCC" stroke="#212529" stroke-width="1.5"/>
  <rect x="7" y="5" width="10" height="6" rx="0.5" fill="#B0B0B0"/>
  <rect x="7" y="11.5" width="10" height="6" rx="0.5" fill="#B0B0B0"/>
  <path d="M 16 14 H 18 V 16" stroke="#f58025" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
        ',
        // 7. Drzwi Zewnętrzne (slug: drzwi-zewnetrzne)
        'drzwi-zewnetrzne' => '
<svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
  <rect x="5" y="2" width="14" height="20" rx="2" fill="#4C4C4C" stroke="#212529" stroke-width="1.5"/>
  <circle cx="12" cy="6" r="1.5" fill="#F0F8FF" stroke="#212529" stroke-width="1"/>
  <rect x="15" y="10" width="1.5" height="5" rx="0.5" fill="#f58025"/>
  <circle cx="15.75" cy="16" r="1" fill="#FFFFFF"/>
</svg>
        ',
        // 8. Akcesoria (slug: akcesoria)
        'akcesoria' => '
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#212529" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="8" cy="8" r="3"/>
                    <line x1="12" y1="12" x2="18" y2="18"/>
                    <path d="M 18 12 L 12 18"/>
                    <rect x="15" y="15" width="6" height="6" rx="1" ry="1"/>
                </svg>
        ',
        // 9. Projektowanie (slug: projektowanie)
        'projektowanie' => '
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#212529" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="8"/>
                    <line x1="12" y1="12" x2="18" y2="18"/>
                    <line x1="6" y1="18" x2="18" y2="6"/>
                    <path d="M 18 6 L 14 10 M 10 14 L 6 18"/>
                </svg>
        ',
        // Dodaj więcej ikon tutaj...
    ];

    // Użyj trim, aby usunąć niepotrzebne białe znaki wokół SVG
    return trim($icons[$slug] ?? ''); 
}