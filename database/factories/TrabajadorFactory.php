<?php

namespace Database\Factories;

use App\Models\Rol;
use App\Models\Trabajador;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/*
    Crea trabajadores FALSOS para las pruebas automáticas (php artisan test).
    Nunca se usa con trabajadores reales. Su contraseña es "password".

    Trabajador::factory()->create()                      → vendedor
    Trabajador::factory()->administrador()->create()
    Trabajador::factory()->conRol('GERENTE')->create()
    Trabajador::factory()->inactivo()->create()

    @extends Factory<Trabajador>
*/
class TrabajadorFactory extends Factory
{
    protected $model = Trabajador::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'nombres' => fake()->firstName(),
            'apellidoPaterno' => fake()->lastName(),
            'apellidoMaterno' => fake()->lastName(),
            'RFC' => strtoupper(fake()->unique()->bothify('????######???')),
            'telefono' => '613 ' . fake()->numerify('### ####'),
            'correoElectronico' => fake()->unique()->safeEmail(),
            'hashContrasena' => static::$password ??= Hash::make('password'),
            'fk_idRol' => fn () => Rol::idDe(Trabajador::ROL_VENDEDOR),
            'activo' => true,
        ];
    }

    public function conRol(string $rol): static
    {
        return $this->state(fn () => ['fk_idRol' => Rol::idDe($rol)]);
    }

    public function administrador(): static
    {
        return $this->conRol(Trabajador::ROL_ADMINISTRADOR);
    }

    public function inactivo(): static
    {
        return $this->state(fn () => ['activo' => false]);
    }
}
