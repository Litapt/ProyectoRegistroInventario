<?php

namespace App\Models;

use Database\Factories\ClienteFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/*
    Un cliente de la refaccionaria. Tabla: clientes (llave idCliente).
    El Figma pide "Nombre completo" en un solo campo: se guarda en "nombres".
*/
class Cliente extends Model
{
    /** @use HasFactory<ClienteFactory> */
    use HasFactory;

    protected $table = 'clientes';
    protected $primaryKey = 'idCliente';
    public $timestamps = false;

    // "activo" no está: sólo cambia con desactivar / reactivar
    protected $fillable = ['nombres', 'telefono', 'correo', 'RFC', 'direccion', 'notas', 'tipoCliente'];

    /* La fecha de registro la pone Laravel (misma zona horaria que todo el sistema) */
    protected static function booted(): void
    {
        static::creating(function (self $cliente) {
            $cliente->fechaRegistro ??= now();
        });
    }

    protected function casts(): array
    {
        return ['activo' => 'boolean', 'fechaRegistro' => 'datetime'];
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    /* Busca por nombre, teléfono, correo, RFC o código (CLI-00042) */
    public function scopeBuscar(Builder $query, string $texto): Builder
    {
        $texto = trim($texto);
        if ($texto === '') {
            return $query;
        }

        $id = preg_match('/^CLI-?0*(\d+)$/i', $texto, $m) ? (int) $m[1] : null;
        $digitos = preg_replace('/\D/', '', $texto);

        return $query->where(function (Builder $q) use ($texto, $id, $digitos) {
            $q->where('nombres', 'like', "%{$texto}%")
                ->orWhere('nombreComercial', 'like', "%{$texto}%")
                ->orWhere('correo', 'like', "%{$texto}%")
                ->orWhere('RFC', 'like', "%{$texto}%")
                ->orWhere('telefono', 'like', "%{$texto}%");

            // "551234" también encuentra "55 1234 5678"
            if (strlen($digitos) >= 4) {
                $q->orWhereRaw("REPLACE(REPLACE(REPLACE(telefono, ' ', ''), '-', ''), '+', '') LIKE ?", ["%{$digitos}%"]);
            }

            if ($id !== null) {
                $q->orWhere('idCliente', $id);
            }
        });
    }

    /* Nombre para mostrar (persona o negocio) */
    public function nombre(): string
    {
        $persona = trim($this->nombres . ' ' . $this->apellidoPaterno . ' ' . $this->apellidoMaterno);

        return $persona !== '' ? $persona : (string) ($this->nombreComercial ?: $this->razonSocial);
    }

    /* 42 → "CLI-00042" */
    public function codigo(): string
    {
        return 'CLI-' . str_pad((string) $this->idCliente, 5, '0', STR_PAD_LEFT);
    }

    /* Datos que usa el JavaScript para llenar la ventana "Editar cliente" */
    public function datosParaEditar(): array
    {
        return [
            'codigo' => $this->codigo(),
            'nombre' => $this->nombre(),
            'telefono' => $this->telefono,
            'correo' => $this->correo,
            'rfc' => $this->RFC,
            'direccion' => $this->direccion,
            'notas' => $this->notas,
        ];
    }
}
