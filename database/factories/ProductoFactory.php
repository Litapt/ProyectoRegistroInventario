<?php

namespace Database\Factories;

use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\Ubicacion;
use Illuminate\Database\Eloquent\Factories\Factory;

/*
    Crea productos FALSOS para las pruebas automáticas (php artisan test).
    Usa las categorías y marcas que pone la migración.

    Producto::factory()->create(['existencia' => 4, 'stockMinimo' => 10])
    Producto::factory()->desactivado()->create()

    @extends Factory<Producto>
*/
class ProductoFactory extends Factory
{
    protected $model = Producto::class;

    public function definition(): array
    {
        $compra = fake()->randomFloat(2, 50, 2000);

        return [
            'codigoInterno' => strtoupper(fake()->unique()->bothify('??-???-###')),
            'nombre' => fake()->randomElement(['Balatas delanteras', 'Filtro de aceite', 'Bujía iridium', 'Amortiguador', 'Batería 12 V']) . ' ' . fake()->unique()->numberBetween(1, 99999),
            'fk_idCategoria' => fn () => Categoria::query()->value('idCategoria'),
            'fk_idMarca' => fn () => Marca::query()->value('idMarca'),
            'fk_idUbicacion' => fn () => Ubicacion::desdeTexto('')->idUbicacion,
            'precioCompraActual' => $compra,
            'precioVentaActual' => round($compra * 1.4, 2),
            'existencia' => 20,
            'stockMinimo' => 5,
            'activo' => true,
        ];
    }

    public function desactivado(): static
    {
        return $this->state(fn () => ['activo' => false]);
    }
}
