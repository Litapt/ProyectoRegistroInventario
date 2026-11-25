<?php

namespace Database\Factories;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Factories\Factory;

/*
    Crea clientes FALSOS para las pruebas automáticas.
    @extends Factory<Cliente>
*/
class ClienteFactory extends Factory
{
    protected $model = Cliente::class;

    public function definition(): array
    {
        return [
            'nombres' => fake()->name(),
            'telefono' => '613 ' . fake()->numerify('### ####'),
            'correo' => fake()->unique()->safeEmail(),
            'RFC' => null,
            'tipoCliente' => 'NORMAL',
            'activo' => true,
        ];
    }

    public function desactivado(): static
    {
        return $this->state(fn () => ['activo' => false]);
    }
}
