// Hace que cada fila de resultados funcione como un botón:
// al dar clic (o Enter con el teclado) abre el detalle de la pieza elegida.
// La dirección a donde lleva viene en el atributo data-href de cada fila.

document.querySelectorAll('.result-row').forEach(function (row) {
  function openRow() {
    const href = row.dataset.href;
    if (href) {
      window.location.href = href;
    }
  }

  row.addEventListener('click', openRow);

  row.addEventListener('keydown', function (event) {
    if (event.key === 'Enter') {
      openRow();
    }
  });
});
