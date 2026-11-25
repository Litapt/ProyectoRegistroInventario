// Clientes del administrador: ventana Registrar / Editar.
// Al editar, antes de guardar se pide la autorización (javascript/autorizacion.js).
(function () {
  const modal = document.getElementById('modalCliente');
  if (!modal) {
    return;
  }

  const form = document.getElementById('formCliente');
  const metodo = document.getElementById('clienteMetodo');
  const guardar = document.getElementById('clienteGuardar');

  function campo(id) {
    return document.getElementById(id);
  }

  function limpiar() {
    // Se vacían a mano: form.reset() regresaría lo que se había escrito antes de un error
    form.querySelectorAll('input[type=text], input[type=tel], input[type=email], textarea').forEach(function (e) {
      e.value = '';
    });
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
    form.action = modal.dataset.urlRegistrar;
    metodo.value = 'POST';
    form.dataset.nombreOriginal = '';
    campo('modalClienteTitulo').textContent = 'Registrar cliente';
    campo('modalClienteSubtitulo').textContent = 'Agrega los datos del nuevo cliente.';
    campo('clienteCodigo').textContent = '';
    guardar.textContent = 'Guardar';
  }

  function modoEditar(url, datos) {
    limpiar();
    form.action = url;
    metodo.value = 'PUT';
    form.dataset.nombreOriginal = datos.nombre;
    form.dataset.codigo = datos.codigo;
    campo('modalClienteTitulo').textContent = 'Editar cliente';
    campo('modalClienteSubtitulo').textContent = 'Actualiza la información de ' + datos.nombre + '.';
    campo('clienteCodigo').textContent = 'Cliente ' + datos.codigo;
    guardar.textContent = 'Guardar cambios';
    campo('clienteNombre').value = datos.nombre || '';
    campo('clienteTelefono').value = datos.telefono || '';
    campo('clienteCorreo').value = datos.correo || '';
    campo('clienteRfc').value = datos.rfc || '';
    campo('clienteDireccion').value = datos.direccion || '';
    campo('clienteNotas').value = datos.notas || '';
  }

  modal.addEventListener('show.bs.modal', function (event) {
    const boton = event.relatedTarget;
    if (!boton) {
      return; // se abrió sola porque regresó con errores: ya viene llena
    }
    if (boton.dataset.modo === 'editar') {
      modoEditar(boton.dataset.url, JSON.parse(boton.dataset.cliente));
    } else {
      modoRegistrar();
    }
  });

  form.addEventListener('submit', function (event) {
    if (metodo.value !== 'PUT') {
      guardar.disabled = true; // registrar: se manda directo
      return;
    }
    // Editar: primero la autorización ("Confirmar edición")
    event.preventDefault();
    bootstrap.Modal.getOrCreateInstance(modal).hide();
    const correo = campo('clienteCorreo').value.trim();
    Autorizacion.abrir({
      titulo: 'Confirmar edición',
      texto: 'Vas a editar los datos del cliente:',
      nombre: form.dataset.nombreOriginal,
      detalle: form.dataset.codigo + (correo ? ' · ' + correo : ''),
      boton: 'Confirmar edición',
      clase: 'btn-app-primary',
      formulario: form
    });
  });

  if (modal.dataset.reabrir) {
    bootstrap.Modal.getOrCreateInstance(modal).show();
  }
})();
