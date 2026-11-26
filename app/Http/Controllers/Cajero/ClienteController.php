<?php

namespace App\Http\Controllers\Cajero;

use App\Http\Controllers\Administrador\ClienteController as ClientesDelAdministrador;

/*
    "Registrar nuevo cliente" desde la pantalla de pedidos del cajero.
    Usa exactamente las mismas reglas que Clientes del administrador (sólo el método store,
    y la pantalla lo pide en JSON para no salir del pedido).
*/
class ClienteController extends ClientesDelAdministrador
{
}
