<?php

namespace App\Support;

/*
    Mensajes de error en español para los formularios.
    Se usan así: $request->validate($reglas, MensajesValidacion::MENSAJES, $nombresDeCampos);
    :attribute se reemplaza por el nombre del campo tal como se ve en pantalla.
*/
class MensajesValidacion
{
    public const MENSAJES = [
        'required' => 'El campo :attribute es obligatorio.',
        'required_if' => 'El campo :attribute es obligatorio.',
        'string' => 'El campo :attribute debe ser texto.',
        'email' => 'El campo :attribute debe ser un correo válido.',
        'numeric' => 'El campo :attribute debe ser un número.',
        'integer' => 'El campo :attribute debe ser un número entero.',
        'decimal' => 'El campo :attribute admite como máximo 2 decimales.',
        'min' => 'El campo :attribute debe ser como mínimo :min.',
        'max' => 'El campo :attribute admite como máximo :max (caracteres o valor).',
        'unique' => 'Ya existe un registro con ese :attribute.',
        'exists' => 'El valor elegido en :attribute no existe.',
        'in' => 'El valor elegido en :attribute no es válido.',
        'confirmed' => 'La confirmación de :attribute no coincide.',
    ];
}
