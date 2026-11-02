// Comportamiento de los formularios de movimiento de stock.
// Lo usan las DOS pantallas: entradaStock.php (entrada) y salidaStock.php (salida).
// Cada página le dice qué hacer con atributos "data-" en su HTML:
//   - data-stock-form      → el formulario
//   - data-direction       → "in" (entrada, suma) u "out" (salida, resta)
//   - data-preview-label   → texto de la vista previa, por ejemplo "Stock después de la entrada"
//   - data-max-message     → mensaje cuando la cantidad pasa del máximo permitido
//   - data-auto-show       → la ventana de éxito que se abre sola
// Nota: PHP vuelve a revisar todo al enviar; esto solo avisa más rápido.

// 1. Si existe la ventana de éxito (la página viene de un registro correcto), abrirla
const successElement = document.querySelector('[data-auto-show]');
if (successElement) {
  new bootstrap.Modal(successElement).show();
}

const stockForm = document.querySelector('[data-stock-form]');

// La pieza puede no existir; en ese caso no hay formulario y no hacemos nada más
if (stockForm) {
  const isOut = stockForm.dataset.direction === 'out';
  const quantityInput = document.getElementById('quantity');
  const quantityBox = quantityInput.closest('.input-affix');
  const helpText = document.getElementById('quantityHelp');
  const currentStock = Number(document.getElementById('currentStock').dataset.stock);
  const maxQuantity = Number(quantityInput.max);
  const defaultHelp = 'Introduce la cantidad en unidades.';

  // Campo de motivo (solo existe en la salida)
  const reasonInput = document.getElementById('reason');
  const reasonHelp = document.getElementById('reasonHelp');
  const defaultReasonHelp = reasonHelp ? reasonHelp.textContent : '';

  // Revisa la cantidad y regresa un mensaje de error, o '' si está bien
  function getQuantityError() {
    const value = quantityInput.value.trim();
    if (!/^\d+$/.test(value)) {
      return 'Escribe una cantidad en números enteros, por ejemplo 10.';
    }
    if (Number(value) < 1) {
      return 'La cantidad debe ser mayor que 0.';
    }
    if (Number(value) > maxQuantity) {
      return quantityInput.dataset.maxMessage;
    }
    return '';
  }

  // Revisa el motivo (solo en la salida)
  function getReasonError() {
    if (!reasonInput) {
      return '';
    }
    const length = reasonInput.value.trim().length;
    if (length < Number(reasonInput.minLength)) {
      return 'Describe el motivo con al menos ' + reasonInput.minLength + ' caracteres.';
    }
    return '';
  }

  // Muestra un texto debajo de la cantidad; si es error, lo pinta de rojo
  function showHelp(message, isError) {
    helpText.textContent = message;
    helpText.classList.toggle('is-error', isError);
    quantityBox.classList.toggle('is-invalid', isError);
  }

  function showReasonHelp(message, isError) {
    reasonHelp.textContent = message;
    reasonHelp.classList.toggle('is-error', isError);
    reasonInput.classList.toggle('is-invalid', isError);
  }

  // 2. Botón "Agregar" / "Retirar": revisa la cantidad y muestra cómo quedará el stock
  document.getElementById('previewButton').addEventListener('click', function () {
    const error = getQuantityError();
    if (error) {
      showHelp(error, true);
      quantityInput.focus();
      return;
    }
    const quantity = Number(quantityInput.value);
    const newStock = isOut ? currentStock - quantity : currentStock + quantity;
    showHelp(stockForm.dataset.previewLabel + ': ' + newStock + ' unidades. Da clic en Aceptar para registrarla.', false);
  });

  // Al escribir de nuevo, se quitan los errores
  quantityInput.addEventListener('input', function () {
    showHelp(defaultHelp, false);
  });
  if (reasonInput) {
    reasonInput.addEventListener('input', function () {
      showReasonHelp(defaultReasonHelp, false);
    });
  }

  // 3. Botón "Aceptar": si algo está mal, no se envía el formulario
  stockForm.addEventListener('submit', function (event) {
    const quantityError = getQuantityError();
    const reasonError = getReasonError();

    if (reasonError) {
      showReasonHelp(reasonError, true);
    }
    if (quantityError) {
      showHelp(quantityError, true);
    }
    if (quantityError || reasonError) {
      event.preventDefault();
      (quantityError ? quantityInput : reasonInput).focus();
    }
  });
}
