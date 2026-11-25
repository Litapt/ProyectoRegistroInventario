<?php

namespace App\Models;

use Database\Factories\TrabajadorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

/*
    Un trabajador de la refaccionaria. Es el usuario que inicia sesión.
    Tabla: trabajadores (llave idTrabajador). Su rol está en la tabla roles.
*/
class Trabajador extends Authenticatable
{
    /** @use HasFactory<TrabajadorFactory> */
    use HasFactory;

    // Nombres de los roles tal como están en la tabla roles
    public const ROL_ADMINISTRADOR = 'ADMINISTRADOR';
    public const ROL_GERENTE = 'GERENTE';
    public const ROL_CAJERO = 'CAJERO';
    public const ROL_VENDEDOR = 'VENDEDOR';
    public const ROL_ALMACENISTA = 'ALMACENISTA';

    // Pantalla a la que llega cada rol al iniciar sesión (nombres de routes/web.php)
    private const RUTAS_INICIO = [
        self::ROL_ADMINISTRADOR => 'administrador.inicio',
        self::ROL_GERENTE => 'gerente.inicio',
        self::ROL_CAJERO => 'cajero.inicio',
        self::ROL_VENDEDOR => 'vendedor.inicio',
        self::ROL_ALMACENISTA => 'almacenista.inicio',
    ];

    protected $table = 'trabajadores';
    protected $primaryKey = 'idTrabajador';

    // La tabla no tiene created_at / updated_at; fechaCreacion la pone MySQL
    public $timestamps = false;

    /*
        Campos que se pueden llenar de golpe con Trabajador::create([...]).
        fk_idRol y activo NO están a propósito: así nadie puede darse
        otro rol mandando un campo extra en un formulario.
    */
    protected $fillable = [
        'nombres',
        'apellidoPaterno',
        'apellidoMaterno',
        'RFC',
        'telefono',
        'correoElectronico',
        'hashContrasena',
    ];

    // Nunca se muestran al convertir el trabajador a JSON
    protected $hidden = [
        'hashContrasena',
        'rememberToken',
    ];

    // Permisos ya leídos de la base (para no consultarlos en cada botón)
    private ?array $permisosCargados = null;

    /*
        La fecha la pone Laravel (zona horaria de config/app.php) y no MySQL,
        para que todas las fechas del sistema usen la misma hora.
    */
    protected static function booted(): void
    {
        static::creating(function (self $modelo) {
            $modelo->fechaCreacion ??= now();
        });
    }

    protected function casts(): array
    {
        return [
            // "hashed" cifra la contraseña con password_hash() al guardarla
            'hashContrasena' => 'hashed',
            'activo' => 'boolean',
            'fechaCreacion' => 'datetime',
            'fechaUltimoAcceso' => 'datetime',
        ];
    }

    /* Laravel busca la contraseña en "password"; la nuestra se llama hashContrasena */
    public function getAuthPasswordName(): string
    {
        return 'hashContrasena';
    }

    /* Columna del "Recordarme" */
    public function getRememberTokenName(): string
    {
        return 'rememberToken';
    }

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'fk_idRol', 'idRol');
    }

    /* "ADMINISTRADOR", "GERENTE"... o '' si no tiene rol */
    public function nombreRol(): string
    {
        return $this->rol?->nombre ?? '';
    }

    /* "ADMINISTRADOR" → "Administrador" */
    public function rolTexto(): string
    {
        return ucfirst(mb_strtolower($this->nombreRol()));
    }

    /* Acepta uno o varios: $trabajador->tieneRol('ADMINISTRADOR', 'GERENTE') */
    public function tieneRol(string ...$roles): bool
    {
        return in_array($this->nombreRol(), $roles, true);
    }

    /*
        ¿Su rol tiene este permiso? $trabajador->puede('ventas.cancelar')
        Los permisos de cada rol están en la tabla rolPermisos.
    */
    public function puede(string $permiso): bool
    {
        if ($this->permisosCargados === null) {
            $this->permisosCargados = $this->rol
                ? $this->rol->permisos()->pluck('nombre')->all()
                : [];
        }

        return in_array($permiso, $this->permisosCargados, true);
    }

    /* Nombre de la ruta a la que llega al iniciar sesión, o null si su rol no tiene pantalla */
    public function rutaInicio(): ?string
    {
        return self::RUTAS_INICIO[$this->nombreRol()] ?? null;
    }

    public function nombreCompleto(): string
    {
        return trim($this->nombres . ' ' . $this->apellidoPaterno . ' ' . $this->apellidoMaterno);
    }

    /* Dirección de la foto de perfil, o null si no tiene (entonces se muestran sus iniciales) */
    public function urlFoto(): ?string
    {
        $foto = $this->getAttribute('foto');

        return $foto && is_file(public_path($foto)) ? asset($foto) : null;
    }

    /* Código que se ve en Usuarios: USR-0001 */
    public function codigo(): string
    {
        return 'USR-' . str_pad((string) $this->idTrabajador, 4, '0', STR_PAD_LEFT);
    }

    /* Datos que la ventana "Editar usuario" necesita (nunca la contraseña) */
    public function datosParaEditar(): array
    {
        return [
            'codigo' => $this->codigo(),
            'nombre' => $this->nombreCompleto(),
            'nombres' => $this->nombres,
            'apellidoPaterno' => $this->apellidoPaterno,
            'apellidoMaterno' => $this->apellidoMaterno,
            'rfc' => $this->RFC,
            'correo' => $this->correoElectronico,
            'telefono' => $this->telefono,
            'rol' => $this->fk_idRol,
            'activo' => (bool) $this->activo,
        ];
    }

    /* "Carlos Méndez" → "CM" (círculo del menú de usuario) */
    public function iniciales(): string
    {
        return mb_strtoupper(mb_substr($this->nombres, 0, 1) . mb_substr($this->apellidoPaterno, 0, 1));
    }
}
