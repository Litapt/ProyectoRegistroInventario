// Abre la ventana de confirmación con los textos del botón que se presionó
// y prepara el formulario para enviarlo a su dirección.
(function () {
  const modal = document.getElementById('modalConfirmar');
  if (!modal) {
    return;
  }

  modal.addEventListener('show.bs.modal', function (event) {
    const boton = event.relatedTarget;
    document.getElementById('modalConfirmarForm').action = boton.dataset.confirmarUrl;
    document.getElementById('modalConfirmarMetodo').value = boton.dataset.confirmarMetodo || 'DELETE';
    document.getElementById('modalConfirmarTitulo').textContent = boton.dataset.confirmarTitulo;
    document.getElementById('modalConfirmarTexto').textContent = boton.dataset.confirmarTexto;
    document.getElementById('modalConfirmarBoton').textContent = boton.dataset.confirmarBoton;
  });

  // Evita enviar dos veces con doble clic
  document.getElementById('modalConfirmarForm').addEventListener('submit', function () {
    document.getElementById('modalConfirmarBoton').disabled = true;
  });
})();
