<?php

class BlogManager {
    // Ścieżka do katalogu z plikami postów (zakładamy, że jest na głównym poziomie)
    private $data_dir = __DIR__ . '/../blog-data/';

    public function __construct() {
    // Ścieżka do katalogu z plikami postów
		$this->data_dir = __DIR__ . '/../blog-data/';

    // !!! USUŃ LUB ZAKOMENTUJ TĘ CZĘŚĆ !!!
    // if (!is_dir($this->data_dir)) {
    //     mkdir($this->data_dir, 0777, true);
    // }
}

    /**
     * Zapisuje nowy post lub aktualizuje istniejący.
     * @param array $data Dane posta (tytuły, treści, slug)
     * @return bool|string Zwraca 'success' lub wiadomość o błędzie
     */
    public function savePost(array $data) {
        $slug = $data['slug'] ?? '';
        
        if (empty($slug) || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            return "Slug jest wymagany i musi być w formacie 'slug-wpisu'";
        }
        
        $filepath = $this->data_dir . $slug . '.json';
        
        // Zbudowanie struktury danych JSON
        $postData = [
            'slug'      => $slug,
            'author_id' => $data['author_id'] ?? 0, // Id admina
            'date'      => date('Y-m-d H:i:s'),
            'title' => [
                'pl' => $data['title_pl'] ?? '',
                'en' => $data['title_en'] ?? '',
                'de' => $data['title_de'] ?? '',
            ],
            'content' => [
                'pl' => $data['content_pl'] ?? '',
                'en' => $data['content_en'] ?? '',
                'de' => $data['content_de'] ?? '',
            ],
            // Można dodać więcej pól, np. 'is_active', 'image', 'meta_description'
        ];

        try {
            // Zapis do pliku
            $result = file_put_contents($filepath, json_encode($postData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            if ($result === false) {
                 return "Błąd zapisu pliku: " . $filepath;
            }
            return 'success';
        } catch (Exception $e) {
            return "Wystąpił błąd: " . $e->getMessage();
        }
    }

    /**
     * Odczytuje pojedynczy post na podstawie sluga.
     * @param string $slug Slug posta
     * @return array|null Dane posta lub null
     */
    public function getPost(string $slug): ?array {
        $filepath = $this->data_dir . $slug . '.json';

        if (!file_exists($filepath)) {
            return null;
        }

        $content = file_get_contents($filepath);
        if ($content === false) {
             // Zaloguj błąd w pliku 'logs/debug.log' za pomocą klasy Logger
             // Logger::log('Błąd odczytu pliku: ' . $filepath); 
             return null;
        }

        // Tłumaczenie JSON na tablicę PHP
        return json_decode($content, true);
    }

    /**
     * Odczytuje wszystkie posty (tylko metadane).
     * @return array Lista wszystkich postów
     */
    public function getAllPosts(): array {
        $posts = [];
        $files = glob($this->data_dir . '*.json');
        
        foreach ($files as $filepath) {
            $slug = basename($filepath, '.json');
            
            // Wczytujemy cały plik i dekodujemy
            $content = file_get_contents($filepath);
            $post = json_decode($content, true);
            
            if ($post) {
                // Dla listy postów w adminie wystarczą nam tylko kluczowe informacje
                $posts[] = [
                    'slug' => $post['slug'],
                    'date' => $post['date'],
                    'author_id' => $post['author_id'] ?? 0,
                    // Bierzemy tytuł polski jako główny w adminie
                    'title_pl' => $post['title']['pl'] ?? $slug,
                ];
            }
        }
        
        // Sortowanie (opcjonalnie, np. od najnowszego)
        usort($posts, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));
        
        return $posts;
    }

    /**
     * Usuwa post na podstawie sluga.
     * @param string $slug Slug posta
     * @return bool Czy usunięcie się powiodło
     */
    public function deletePost(string $slug): bool {
        $filepath = $this->data_dir . $slug . '.json';
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        return false;
    }
}