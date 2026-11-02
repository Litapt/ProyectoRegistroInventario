<?php
// Búsqueda de piezas del inventario.
//
// IMPORTANTE: todavía no existe la tabla de productos en MySQL, así que por ahora
// buscamos dentro de una lista de EJEMPLO escrita aquí mismo (no son datos reales).
// Cuando exista la tabla, searchProducts() se cambiará por una consulta con PDO, por ejemplo:
//   SELECT * FROM products
//   WHERE part_name LIKE :part_name AND serial_number LIKE :serial_number ...
// usando sentencias preparadas (nunca pegando el texto del usuario dentro del SQL).

/**
 * Piezas de ejemplo para probar la pantalla mientras no hay base de datos.
 */
function getSampleProducts(): array
{
    return [
        ['id' => 1, 'part_name' => 'Rodamiento de bolas 6204', 'serial_number' => 'SKF-6204-00852', 'brand' => 'SKF', 'description' => 'Rígido de bolas, sellos de goma. 20 × 47 × 14 mm.', 'purchase_price' => 185.00, 'sale_price' => 280.00, 'stock' => 24, 'min_stock' => 5, 'location' => 'Almacén principal', 'shelf' => 'A-03', 'rack' => 'R-02'],
        ['id' => 2, 'part_name' => 'Rodamiento de bolas 6205', 'serial_number' => 'SKF-6205-00853', 'brand' => 'SKF', 'description' => 'Rígido de bolas, sellos de goma. 25 × 52 × 15 mm.', 'purchase_price' => 220.00, 'sale_price' => 330.00, 'stock' => 18, 'min_stock' => 5, 'location' => 'Almacén principal', 'shelf' => 'A-03', 'rack' => 'R-02'],
        ['id' => 3, 'part_name' => 'Rodamiento de bolas 6206', 'serial_number' => 'SKF-6206-00854', 'brand' => 'SKF', 'description' => 'Rígido de bolas, blindaje metálico. 30 × 62 × 16 mm.', 'purchase_price' => 265.00, 'sale_price' => 395.00, 'stock' => 12, 'min_stock' => 4, 'location' => 'Almacén principal', 'shelf' => 'A-04', 'rack' => 'R-02'],
        ['id' => 4, 'part_name' => 'Rodamiento de bolas 6304', 'serial_number' => 'SKF-6304-00855', 'brand' => 'SKF', 'description' => 'Rígido de bolas, sellos de goma. 20 × 52 × 15 mm.', 'purchase_price' => 245.00, 'sale_price' => 365.00, 'stock' => 32, 'min_stock' => 8, 'location' => 'Almacén secundario', 'shelf' => 'B-01', 'rack' => 'R-01'],
        ['id' => 5, 'part_name' => 'Rodamiento de bolas 6305', 'serial_number' => 'SKF-6305-00856', 'brand' => 'SKF', 'description' => 'Rígido de bolas, blindaje metálico. 25 × 62 × 17 mm.', 'purchase_price' => 310.00, 'sale_price' => 465.00, 'stock' => 16, 'min_stock' => 5, 'location' => 'Almacén secundario', 'shelf' => 'B-02', 'rack' => 'R-01'],
        ['id' => 6, 'part_name' => 'Balata delantera cerámica', 'serial_number' => 'BRB-D1092-0041', 'brand' => 'Brembo', 'description' => 'Juego de 4 balatas cerámicas para disco delantero.', 'purchase_price' => 540.00, 'sale_price' => 790.00, 'stock' => 10, 'min_stock' => 3, 'location' => 'Almacén principal', 'shelf' => 'C-01', 'rack' => 'R-03'],
        ['id' => 7, 'part_name' => 'Filtro de aceite', 'serial_number' => 'BSH-3323-1187', 'brand' => 'Bosch', 'description' => 'Filtro de aceite roscado para motor de 4 cilindros.', 'purchase_price' => 95.00, 'sale_price' => 150.00, 'stock' => 40, 'min_stock' => 10, 'location' => 'Almacén secundario', 'shelf' => 'B-03', 'rack' => 'R-01'],
    ];
}

/**
 * Lee de la dirección (GET) la búsqueda que hizo el usuario en registroEntrada.php:
 * los 4 criterios y la página de resultados.
 * Se pasa de pantalla en pantalla para que "Volver" regrese a la misma tabla.
 */
function readSearchParams(array $input): array
{
    return [
        'part_name'     => trim($input['part_name'] ?? ''),
        'serial_number' => trim($input['serial_number'] ?? ''),
        'brand'         => trim($input['brand'] ?? ''),
        'description'   => trim($input['description'] ?? ''),
        'page'          => max(1, (int) ($input['page'] ?? 1)),
    ];
}

/**
 * Busca una sola pieza por su número de id. Si no existe, regresa null.
 * Con base de datos será: SELECT * FROM products WHERE id = :id
 */
function getProductById(int $id): ?array
{
    foreach (getSampleProducts() as $product) {
        if ($product['id'] === $id) {
            return $product;
        }
    }
    return null;
}

/**
 * Regresa las piezas que coinciden con TODOS los criterios que tengan texto.
 * Los criterios vacíos se ignoran; si todos están vacíos, regresa todas las piezas.
 * La comparación no distingue mayúsculas y busca el texto en cualquier parte del dato.
 */
function searchProducts(array $criteria): array
{
    $results = [];
    foreach (getSampleProducts() as $product) {
        $matches = true;
        foreach ($criteria as $field => $value) {
            if ($value !== '' && mb_stripos($product[$field], $value) === false) {
                $matches = false;
                break;
            }
        }
        if ($matches) {
            $results[] = $product;
        }
    }
    return $results;
}
