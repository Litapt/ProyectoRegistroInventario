// Usuarios del administrador: ventana Registrar / Editar.
// "Eliminar" usa la ventana de autorización genérica (javascript/autorizacion.js).
(function () {
  const modal = document.getElementById('modalUsuario');
  if (!modal) {
    return;
  }

  const form = document.getElementById('formUsuario');
  const metodo = document.getElementById('usuarioMetodo');
  const guardar = document.getElementById('usuarioGuardar');

  function campo(id) {
    return document.getElementById(id);
  }

  function texto(id, valor) {
    campo(id).textContent = valor;
  }

  // "yo": el administrador se está editando a sí mismo → no puede cambiar su rol
  function bloquearPropio(yo, rol) {
    campo('usuarioRol').disabled = yo;
    campo('usuarioRolFijo').disabled = !yo;
    campo('usuarioRolFijo').value = rol || '';
    texto('usuarioRolAyuda', yo ? 'No puedes cambiar tu propio rol.' : 'Define qué pantallas y acciones puede usar.');
  }

  function limpiar() {
    // Se vacían a mano: form.reset() regresaría lo que se había escrito antes de un error
    form.querySelectorAll('input[type=text], input[type=tel], input[type=email], input[type=password]').forEach(function (e) {
      e.value = '';
    });
    campo('usuarioRol').value = '';
    form.querySelectorAll('.is-invalid').forEach(function (e) {
      e.classList.remove('is-invalid');
    });
    const errores = form.querySelector('.alert-danger');
    if (errores) {
      errores.remove();
    }
    guardar.disabled = false;
  }

  function modoRegistrar() {
    limpiar();
    bloquearPropio(false, '');
    form.action = modal.dataset.urlRegistrar;
    metodo.value = 'POST';
    texto('modalUsuarioTitulo', 'Registrar usuario');
    texto('modalUsuarioSubtitulo', 'Agrega a una persona que podrá entrar al sistema.');
    texto('usuarioContrasenaTitulo', 'Contraseña');
    texto('usuarioContrasenaAyuda', 'Usa al menos 8 caracteres, con letras y números.');
    texto('usuarioContrasenaEtiqueta', 'Contraseña');
    texto('usuarioCodigo', '');
    texto('usuarioGuardar', 'Registrar usuario');
  }

  function modoEditar(url, datos, yo) {
    limpiar();
    form.action = url;
    metodo.value = 'PUT';
    texto('modalUsuarioTitulo', 'Editar usuario');
    texto('modalUsuarioSubtitulo', 'Actualiza la información de ' + datos.nombre + '.');
    texto('usuarioContrasenaTitulo', 'Cambiar contraseña');
    texto('usuarioContrasenaAyuda', 'Déjalas vacías para conservar la contraseña actual.');
    texto('usuarioContrasenaEtiqueta', 'Nueva contraseña');
    texto('usuarioCodigo', 'Usuario ' + datos.codigo);
    texto('usuarioGuardar', 'Guardar cambios');
    campo('usuarioNombres').value = datos.nombres || '';
    campo('usuarioApellidoPaterno').value = datos.apellidoPaterno || '';
    campo('usuarioApellidoMaterno').value = datos.apellidoMaterno || '';
    campo('usuarioCorreo').value = datos.correo || '';
    campo('usuarioTelefono').value = datos.telefono || '';
    campo('usuarioRfc').value = datos.rfc || '';
    campo('usuarioRol').value = String(datos.rol || '');
    bloquearPropio(yo, String(datos.rol || ''));
  }

  modal.addEventListener('show.bs.modal', function (event) {
    const boton = event.relatedTarget;
    if (!boton) {
      return; // se abrió sola porque regresó con errores: ya viene llena
    }
    if (boton.dataset.modo === 'editar') {
      modoEditar(boton.dataset.url, JSON.parse(boton.dataset.usuario), boton.dataset.yo === '1');
    } else {
      modoRegistrar();
    }
  });

  form.addEventListener('submit', function () {
    guardar.disabled = true; // evita doble clic
  });

  if (modal.dataset.reabrir) {
    bootstrap.Modal.getOrCreateInstance(modal).show();
  }
})();
