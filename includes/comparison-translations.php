<?php
/**
 * SERSOLTEC v2.5 - Comparison System Translations
 * Tłumaczenia dla systemu porównywania produktów
 * 
 * @version 2.5.6
 * @languages PL / EN / ES
 */

// Merge comparison translations with global $translations array
$comparison_translations = [
    'pl' => [
        // Header & Navigation
        'compare_title' => 'Porównanie produktów',
        'compare_subtitle' => 'Porównaj wybrane produkty i znajdź najlepszy dla siebie',
        'compare_count' => 'produktów',
        'compare_nav_link' => 'Porównaj',
        
        // Buttons
        'compare_add' => 'Dodaj do porównania',
        'compare_remove' => 'Usuń z porównania',
        'compare_clear' => 'Wyczyść porównanie',
        'compare_view' => 'Zobacz porównanie',
        'compare_close' => 'Zamknij',
        'compare_browse' => 'Przeglądaj produkty',
        
        // Empty state
        'compare_empty_title' => 'Brak produktów do porównania',
        'compare_empty_text' => 'Dodaj produkty do porównania, aby zobaczyć je tutaj',
        'compare_empty_icon' => '📦',
        
        // Table headers
        'compare_feature' => 'Cecha',
        'compare_image' => 'Zdjęcie',
        'compare_name' => 'Nazwa',
        'compare_price' => 'Cena',
        'compare_category' => 'Kategoria',
        'compare_description' => 'Opis',
        'compare_stock' => 'Dostępność',
        'compare_sku' => 'SKU',
        'compare_actions' => 'Akcje',
        'compare_specifications' => 'Specyfikacja',
        
        // Product status
        'compare_available' => '✓ Dostępny',
        'compare_unavailable' => '✗ Niedostępny',
        'compare_no_category' => 'Bez kategorii',
        'compare_no_description' => 'Brak opisu',
        'compare_no_image' => 'Brak zdjęcia',
        'compare_in_stock' => 'Na stanie',
        'compare_out_of_stock' => 'Brak w magazynie',
        
        // Actions
        'compare_view_details' => 'Zobacz szczegóły',
        'compare_add_to_cart' => 'Dodaj do koszyka',
        'compare_view_product' => 'Zobacz produkt',
        
        // Messages (for JavaScript)
        'compare_added' => 'Produkt dodany do porównania',
        'compare_removed' => 'Produkt usunięty z porównania',
        'compare_cleared' => 'Porównanie wyczyszczone',
        'compare_max_reached' => 'Możesz porównać maksymalnie {max} produktów',
        'compare_error' => 'Wystąpił błąd',
        'compare_already_added' => 'Ten produkt jest już w porównaniu',
        
        // Confirmation dialogs
        'compare_confirm_clear' => 'Czy na pewno chcesz wyczyścić całe porównanie?',
        'compare_confirm_remove' => 'Czy na pewno chcesz usunąć ten produkt z porównania?',
        
        // Loading
        'compare_loading' => 'Ładowanie...',
        'compare_loading_products' => 'Ładowanie produktów...',
    ],
    
    'en' => [
        // Header & Navigation
        'compare_title' => 'Product Comparison',
        'compare_subtitle' => 'Compare selected products and find the best one for you',
        'compare_count' => 'products',
        'compare_nav_link' => 'Compare',
        
        // Buttons
        'compare_add' => 'Add to comparison',
        'compare_remove' => 'Remove from comparison',
        'compare_clear' => 'Clear comparison',
        'compare_view' => 'View comparison',
        'compare_close' => 'Close',
        'compare_browse' => 'Browse products',
        
        // Empty state
        'compare_empty_title' => 'No products to compare',
        'compare_empty_text' => 'Add products to comparison to see them here',
        'compare_empty_icon' => '📦',
        
        // Table headers
        'compare_feature' => 'Feature',
        'compare_image' => 'Image',
        'compare_name' => 'Name',
        'compare_price' => 'Price',
        'compare_category' => 'Category',
        'compare_description' => 'Description',
        'compare_stock' => 'Availability',
        'compare_sku' => 'SKU',
        'compare_actions' => 'Actions',
        'compare_specifications' => 'Specifications',
        
        // Product status
        'compare_available' => '✓ Available',
        'compare_unavailable' => '✗ Unavailable',
        'compare_no_category' => 'No category',
        'compare_no_description' => 'No description',
        'compare_no_image' => 'No image',
        'compare_in_stock' => 'In stock',
        'compare_out_of_stock' => 'Out of stock',
        
        // Actions
        'compare_view_details' => 'View details',
        'compare_add_to_cart' => 'Add to cart',
        'compare_view_product' => 'View product',
        
        // Messages (for JavaScript)
        'compare_added' => 'Product added to comparison',
        'compare_removed' => 'Product removed from comparison',
        'compare_cleared' => 'Comparison cleared',
        'compare_max_reached' => 'You can compare up to {max} products',
        'compare_error' => 'An error occurred',
        'compare_already_added' => 'This product is already in comparison',
        
        // Confirmation dialogs
        'compare_confirm_clear' => 'Are you sure you want to clear the entire comparison?',
        'compare_confirm_remove' => 'Are you sure you want to remove this product from comparison?',
        
        // Loading
        'compare_loading' => 'Loading...',
        'compare_loading_products' => 'Loading products...',
    ],
    
    'es' => [
        // Header & Navigation
        'compare_title' => 'Comparación de productos',
        'compare_subtitle' => 'Compara los productos seleccionados y encuentra el mejor para ti',
        'compare_count' => 'productos',
        'compare_nav_link' => 'Comparar',
        
        // Buttons
        'compare_add' => 'Añadir a comparación',
        'compare_remove' => 'Eliminar de comparación',
        'compare_clear' => 'Limpiar comparación',
        'compare_view' => 'Ver comparación',
        'compare_close' => 'Cerrar',
        'compare_browse' => 'Ver productos',
        
        // Empty state
        'compare_empty_title' => 'No hay productos para comparar',
        'compare_empty_text' => 'Añade productos a la comparación para verlos aquí',
        'compare_empty_icon' => '📦',
        
        // Table headers
        'compare_feature' => 'Característica',
        'compare_image' => 'Imagen',
        'compare_name' => 'Nombre',
        'compare_price' => 'Precio',
        'compare_category' => 'Categoría',
        'compare_description' => 'Descripción',
        'compare_stock' => 'Disponibilidad',
        'compare_sku' => 'SKU',
        'compare_actions' => 'Acciones',
        'compare_specifications' => 'Especificaciones',
        
        // Product status
        'compare_available' => '✓ Disponible',
        'compare_unavailable' => '✗ No disponible',
        'compare_no_category' => 'Sin categoría',
        'compare_no_description' => 'Sin descripción',
        'compare_no_image' => 'Sin imagen',
        'compare_in_stock' => 'En stock',
        'compare_out_of_stock' => 'Agotado',
        
        // Actions
        'compare_view_details' => 'Ver detalles',
        'compare_add_to_cart' => 'Añadir al carrito',
        'compare_view_product' => 'Ver producto',
        
        // Messages (for JavaScript)
        'compare_added' => 'Producto añadido a la comparación',
        'compare_removed' => 'Producto eliminado de la comparación',
        'compare_cleared' => 'Comparación limpiada',
        'compare_max_reached' => 'Puedes comparar hasta {max} productos',
        'compare_error' => 'Ocurrió un error',
        'compare_already_added' => 'Este producto ya está en la comparación',
        
        // Confirmation dialogs
        'compare_confirm_clear' => '¿Está seguro de que desea limpiar toda la comparación?',
        'compare_confirm_remove' => '¿Está seguro de que desea eliminar este producto de la comparación?',
        
        // Loading
        'compare_loading' => 'Cargando...',
        'compare_loading_products' => 'Cargando productos...',
    ],
];

// Merge with existing global translations if available
if (isset($translations)) {
    foreach ($comparison_translations as $lang => $strings) {
        if (!isset($translations[$lang])) {
            $translations[$lang] = [];
        }
        $translations[$lang] = array_merge($translations[$lang], $strings);
    }
} else {
    // If no global translations exist yet, use comparison translations as base
    $translations = $comparison_translations;
}

// Cleanup
unset($comparison_translations);
