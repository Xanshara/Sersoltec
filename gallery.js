// --- LIGHTBOX GALLERY LOGIC ---

document.addEventListener('DOMContentLoaded', () => {
    // 1. Zmienne globalne i elementy DOM
    const galleryItems = document.querySelectorAll('.gallery-lightbox-trigger');
    const lightbox = document.getElementById('lightbox');
    const lightboxImage = document.getElementById('lightboxImage');
    const lightboxSourceWebp = document.getElementById('lightboxSourceWebp');
    const lightboxClose = document.getElementById('lightboxClose');
    const lightboxPrev = document.getElementById('lightboxPrev');
    const lightboxNext = document.getElementById('lightboxNext');
    
    let currentIndex = 0; // Aktualnie wyświetlany indeks zdjęcia
    let imagesData = []; // Tablica do przechowywania ścieżek do wszystkich obrazów
    
    // 2. Przygotowanie danych (zbieranie ścieżek z atrybutów danych)
    galleryItems.forEach((item, index) => {
        // Zapisujemy ścieżki i indeks w łatwej do nawigacji tablicy
        imagesData.push({
            webp: item.getAttribute('data-full-webp'),
            jpg: item.getAttribute('data-full-jpg'),
            alt: item.getAttribute('aria-label')
        });
        
        // Ustawienie nasłuchu na kliknięcie każdego elementu galerii
        item.addEventListener('click', (e) => {
            e.preventDefault(); // Zapobieganie domyślnej akcji (przejście na href="#")
            currentIndex = index; // Ustawienie klikniętego indeksu
            openLightbox(currentIndex);
        });
    });
    
    /**
     * Otwiera lightboxa i ładuje obraz o podanym indeksie.
     * @param {number} index Indeks zdjęcia do załadowania.
     */
    function openLightbox(index) {
        if (imagesData.length === 0) return;

        // Upewnienie się, że indeks jest w zakresie
        currentIndex = (index + imagesData.length) % imagesData.length;
        const data = imagesData[currentIndex];
        
        // Ustawienie ścieżek dla tagu <picture>
        lightboxSourceWebp.srcset = data.webp;
        lightboxImage.src = data.jpg;
        lightboxImage.alt = data.alt;
        
        // Otwarcie lightboxa przez dodanie klasy
        lightbox.classList.add('is-open');
        
        // Dodatkowe: zablokowanie przewijania tła
        document.body.style.overflow = 'hidden';

        // Ustawienie fokusu na przycisku zamykania dla lepszej dostępności
        lightboxClose.focus();
    }
    
    /**
     * Zamyka lightboxa.
     */
    function closeLightbox() {
        lightbox.classList.remove('is-open');
        // Przywrócenie przewijania tła
        document.body.style.overflow = '';
    }
    
    /**
     * Przełącza na poprzednie zdjęcie.
     */
    function showPrevImage() {
        currentIndex = (currentIndex - 1 + imagesData.length) % imagesData.length;
        openLightbox(currentIndex);
    }
    
    /**
     * Przełącza na następne zdjęcie.
     */
    function showNextImage() {
        currentIndex = (currentIndex + 1) % imagesData.length;
        openLightbox(currentIndex);
    }
    
    // 3. Obsługa zdarzeń
    
    // Kliknięcie na przycisk zamywania
    lightboxClose.addEventListener('click', closeLightbox);
    
    // Kliknięcie na tło lightboxa (zamykanie)
    lightbox.addEventListener('click', (e) => {
        // Zamykaj tylko, jeśli kliknięto bezpośrednio na tło modala (nie na obraz czy przyciski w środku)
        if (e.target.id === 'lightbox') {
            closeLightbox();
        }
    });

    // Kliknięcie na przyciski nawigacji
    lightboxPrev.addEventListener('click', showPrevImage);
    lightboxNext.addEventListener('click', showNextImage);
    
    // Obsługa klawiatury (ESC i strzałki)
    document.addEventListener('keydown', (e) => {
        // Sprawdź, czy lightbox jest otwarty
        if (lightbox.classList.contains('is-open')) {
            switch (e.key) {
                case 'Escape': // Klawisz ESC zamyka
                    e.preventDefault();
                    closeLightbox();
                    break;
                case 'ArrowLeft': // Strzałka w lewo
                    e.preventDefault();
                    showPrevImage();
                    break;
                case 'ArrowRight': // Strzałka w prawo
                    e.preventDefault();
                    showNextImage();
                    break;
            }
        }
    });

});