<?php
// Piezas de ejemplo que comparten las pantallas de consulta y de ajuste de stock.
// Es temporal: cuando exista la base de datos, estos datos vendrán de ahí.
// Los primeros 5 son del diseño y los demás sirven para probar la búsqueda.
$pieces = array(
    array('name' => 'Rodamiento de bolas 6204', 'serial' => 'SKF-6204-00852', 'brand' => 'SKF', 'description' => 'Rígido de bolas, sellos de goma.', 'size' => '20 × 47 × 14 mm.', 'purchase' => 185, 'sale' => 280, 'stock' => 24, 'min' => 5, 'location' => 'Almacén principal', 'shelf' => 'A-03', 'rack' => 'R-02'),
    array('name' => 'Rodamiento de bolas 6205', 'serial' => 'SKF-6205-00853', 'brand' => 'SKF', 'description' => 'Rígido de bolas, sellos de goma.', 'size' => '25 × 52 × 15 mm.', 'purchase' => 220, 'sale' => 330, 'stock' => 18, 'min' => 5, 'location' => 'Almacén principal', 'shelf' => 'A-03', 'rack' => 'R-02'),
    array('name' => 'Rodamiento de bolas 6206', 'serial' => 'SKF-6206-00854', 'brand' => 'SKF', 'description' => 'Rígido de bolas, blindaje metálico.', 'size' => '30 × 62 × 16 mm.', 'purchase' => 265, 'sale' => 395, 'stock' => 12, 'min' => 4, 'location' => 'Almacén principal', 'shelf' => 'A-04', 'rack' => 'R-02'),
    array('name' => 'Rodamiento de bolas 6304', 'serial' => 'SKF-6304-00855', 'brand' => 'SKF', 'description' => 'Rígido de bolas, sellos de goma.', 'size' => '20 × 52 × 15 mm.', 'purchase' => 245, 'sale' => 365, 'stock' => 32, 'min' => 8, 'location' => 'Almacén secundario', 'shelf' => 'B-01', 'rack' => 'R-01'),
    array('name' => 'Rodamiento de bolas 6305', 'serial' => 'SKF-6305-00856', 'brand' => 'SKF', 'description' => 'Rígido de bolas, blindaje metálico.', 'size' => '25 × 62 × 17 mm.', 'purchase' => 310, 'sale' => 465, 'stock' => 16, 'min' => 5, 'location' => 'Almacén secundario', 'shelf' => 'B-02', 'rack' => 'R-01'),
    array('name' => 'Rodamiento de rodillos 30205', 'serial' => 'TIM-30205-01120', 'brand' => 'Timken', 'description' => 'Rodillos cónicos, una hilera.', 'size' => '25 × 52 × 16 mm.', 'purchase' => 340, 'sale' => 510, 'stock' => 9, 'min' => 3, 'location' => 'Almacén principal', 'shelf' => 'A-05', 'rack' => 'R-03'),
    array('name' => 'Filtro de aceite universal', 'serial' => 'BOS-F026-00412', 'brand' => 'Bosch', 'description' => 'Filtro roscado para motor a gasolina.', 'size' => 'Rosca de 3/4 pulgada.', 'purchase' => 75, 'sale' => 120, 'stock' => 30, 'min' => 10, 'location' => 'Almacén principal', 'shelf' => 'C-01', 'rack' => 'R-04'),
    array('name' => 'Filtro de aire para motor', 'serial' => 'BOS-S034-00538', 'brand' => 'Bosch', 'description' => 'Elemento de papel plisado.', 'size' => '240 × 180 × 50 mm.', 'purchase' => 130, 'sale' => 210, 'stock' => 14, 'min' => 6, 'location' => 'Almacén principal', 'shelf' => 'C-02', 'rack' => 'R-04'),
    array('name' => 'Balatas delanteras', 'serial' => 'BRE-P850-00271', 'brand' => 'Brembo', 'description' => 'Juego de cuatro, material cerámico.', 'size' => 'Para disco de 280 mm.', 'purchase' => 520, 'sale' => 790, 'stock' => 11, 'min' => 4, 'location' => 'Almacén secundario', 'shelf' => 'B-03', 'rack' => 'R-02'),
    array('name' => 'Bujía de encendido estándar', 'serial' => 'NGK-BKR5-00690', 'brand' => 'NGK', 'description' => 'Electrodo de níquel, rosca de 14 mm.', 'size' => 'Largo de rosca de 19 mm.', 'purchase' => 45, 'sale' => 80, 'stock' => 60, 'min' => 20, 'location' => 'Almacén principal', 'shelf' => 'C-03', 'rack' => 'R-05'),
    array('name' => 'Correa de distribución', 'serial' => 'GAT-T168-00344', 'brand' => 'Gates', 'description' => 'Hule reforzado, 123 dientes.', 'size' => 'Ancho de 25 mm.', 'purchase' => 410, 'sale' => 620, 'stock' => 7, 'min' => 3, 'location' => 'Almacén secundario', 'shelf' => 'B-04', 'rack' => 'R-03'),
    array('name' => 'Aceite de motor 5W-30 · 1 L', 'serial' => 'MOB-5W30-00915', 'brand' => 'Mobil', 'description' => 'Sintético para motor a gasolina.', 'size' => 'Envase de 1 litro.', 'purchase' => 150, 'sale' => 230, 'stock' => 40, 'min' => 15, 'location' => 'Almacén principal', 'shelf' => 'D-01', 'rack' => 'R-01'),
);

// Ubicaciones disponibles para las listas de las pantallas
$locations = array('Almacén principal', 'Almacén secundario');

// Convierte 185 en "$185.00"
function formatPrice($amount)
{
    return '$' . number_format((float) $amount, 2);
}
