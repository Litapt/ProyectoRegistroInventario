// Ajuste de stock: calcula la vista previa y muestra el mensaje de éxito.
// Todavía no hay base de datos: el stock real de la pieza no cambia.
(function () {
  const adjustForm = document.getElementById('adjustForm');
  const stock = Number(adjustForm.dataset.stock);
  const minStock = Number(adjustForm.dataset.min);
  const moveOptions = document.querySelectorAll('.move-option');
  const quantityInput = document.getElementById('quantity');
  const quantityHelp = document.getElementById('quantityHelp');
  const reasonInput = document.getElementById('reason');
  const previewOperation = document.getElementById('previewOperation');
  const resultStock = document.getElementById('resultStock');
  const minStockStatus = document.getElementById('minStockStatus');
  const summaryTitle = document.getElementById('summaryTitle');
  const summaryText = document.getElementById('summaryText');
  const confirmLabel = document.getElementById('confirmLabel');
  const confirmIconEntry = document.getElementById('confirmIconEntry');
  const confirmIconExit = document.getElementById('confirmIconExit');
  const successOverlay = document.getElementById('successOverlay');
  const pageContent = document.getElementById('pageContent');

  // Convierte 24 en "24 unidades" y 1 en "1 unidad"
  function formatUnits(amount) {
    return amount + (amount === 1 ? ' unidad' : ' unidades');
  }

  function isExitSelected() {
    return adjustForm.elements.movement.value === 'exit';
  }

  // Devuelve la cantidad si es un número entero mayor que 0; si no, devuelve 0
  function getQuantity() {
    const quantity = Number(quantityInput.value);
    return Number.isInteger(quantity) && quantity > 0 ? quantity : 0;
  }

  function getMinStockStatus(resultAmount) {
    if (resultAmount < minStock) {
      return 'Por debajo del mínimo';
    }
    return resultAmount === minStock ? 'En el mínimo' : 'Por encima del mínimo';
  }

  // Actualiza la vista previa, las validaciones y los textos de confirmación
  function update() {
    const isExit = isExitSelected();
    const quantity = getQuantity();
    const isTooMuch = isExit && quantity > stock;
    const resultAmount = isExit ? stock - quantity : stock + quantity;

    moveOptions.forEach(function (option) {
      option.classList.toggle('is-selected', option.dataset.move === (isExit ? 'exit' : 'entry'));
    });

    // El egreso no puede retirar más de lo que hay y debe explicar su motivo
    if (isExit) {
      quantityInput.max = stock;
    } else {
      quantityInput.removeAttribute('max');
    }
    quantityHelp.textContent = 'Introduce un número entero mayor que 0.'
      + (isExit ? ' Puedes retirar hasta ' + formatUnits(stock) + '.' : '');
    reasonInput.disabled = !isExit;
    reasonInput.required = isExit;
    reasonInput.setCustomValidity(isExit && reasonInput.value.trim() === '' ? 'Indica el motivo del egreso.' : '');

    previewOperation.textContent = (isExit ? '− ' : '+ ') + quantity + '  =';
    resultStock.textContent = isTooMuch ? 'No disponible' : formatUnits(resultAmount);
    minStockStatus.textContent = isTooMuch ? 'Cantidad mayor al stock' : getMinStockStatus(resultAmount);

    const movementName = isExit ? 'egreso' : 'entrada';
    confirmIconEntry.classList.toggle('d-none', isExit);
    confirmIconExit.classList.toggle('d-none', !isExit);
    confirmLabel.textContent = 'Confirmar ' + movementName + (quantity > 0 ? ' de ' + formatUnits(quantity) : '');

    if (quantity === 0) {
      summaryTitle.textContent = 'Ingresa la cantidad a ajustar.';
      summaryText.textContent = 'Aquí verás cómo quedará el stock antes de confirmar.';
    } else if (isTooMuch) {
      summaryTitle.textContent = 'La cantidad supera el stock disponible.';
      summaryText.textContent = 'Solo puedes retirar hasta ' + formatUnits(stock) + '.';
    } else {
      summaryTitle.textContent = (isExit ? 'Se retirará' : 'Se agregará') + (quantity === 1 ? ' ' : 'n ')
        + formatUnits(quantity) + (isExit ? ' del almacén.' : ' al almacén.');
      summaryText.textContent = 'El stock pasará de ' + stock + ' a ' + formatUnits(resultAmount)
        + '. El movimiento se guardará como ' + movementName + '.';
    }
  }

  moveOptions.forEach(function (option) {
    option.querySelector('input').addEventListener('change', update);
  });
  quantityInput.addEventListener('input', update);
  reasonInput.addEventListener('input', update);

  // Este evento solo ocurre si el formulario pasó todas las validaciones
  adjustForm.addEventListener('submit', function (event) {
    event.preventDefault();
    const isExit = isExitSelected();
    document.getElementById('successTitle').textContent = isExit ? 'Egreso de stock registrado' : 'Entrada de stock registrada';
    document.getElementById('successText').textContent = isExit
      ? 'El egreso de stock se registró correctamente.'
      : 'La entrada de stock se registró correctamente.';
    successOverlay.classList.remove('d-none');
    // Mientras se ve el mensaje, la página de atrás no recibe clics ni teclado
    pageContent.inert = true;
    document.getElementById('backToConsult').focus();
  });

  document.getElementById('currentStock').textContent = formatUnits(stock);
  document.getElementById('minStockText').textContent = 'Stock mínimo: ' + formatUnits(minStock);
  update();
})();
