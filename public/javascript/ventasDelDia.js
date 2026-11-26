// Ventana "Imprimir mis ventas del día" (administrador, gerente y cajero).
// La hoja se imprime en un marco invisible (javascript/imprimir.js); aquí sólo se revisan
// los datos antes de enviar y, al mandar a imprimir, se cierra la ventana y se borra la contraseña.

(function () {
  const form = document.getElementById('formVentasDelDia');
  if (!form) { return; }
  const $ = (id) => document.getElementById(id);

  form.addEventListener('submit', function (e) {
    for (const id of ['ventasDelDiaFecha', 'ventasDelDiaCorreo', 'ventasDelDiaContrasena']) {
      if ($(id).value.trim() === '') {
        e.preventDefault();
        $(id).classList.add('is-invalid');
        $(id).focus();
        // imprimir.js ya había puesto "Preparando…": se regresa el texto
        setTimeout(function () { $('ventasDelDiaBoton').textContent = 'Imprimir'; }, 10);
        return;
      }
    }
    // Se envía al marco invisible; la ventana se cierra y la contraseña no se queda escrita
    setTimeout(function () {
      $('ventasDelDiaContrasena').value = '';
      bootstrap.Modal.getOrCreateInstance(document.getElementById('modalVentasDelDia')).hide();
    }, 0);
  });

  form.addEventListener('input', function (e) { e.target.classList.remove('is-invalid'); });
})();
