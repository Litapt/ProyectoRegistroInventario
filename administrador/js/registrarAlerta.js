// Formulario para crear una alerta de stock bajo.
// Todavía no hay base de datos: la alerta solo se agrega a la tabla de esta pantalla.
(function () {
  const alertForm = document.getElementById('alertForm');
  const products = JSON.parse(alertForm.dataset.products);
  const productInput = document.getElementById('product');
  const categoryInput = document.getElementById('productCategory');
  const locationInput = document.getElementById('productLocation');
  const stockInput = document.getElementById('currentStock');
  const thresholdInput = document.getElementById('threshold');
  const prioritySelect = document.getElementById('priority');
  const priorityHelp = document.getElementById('priorityHelp');
  const conditionText = document.getElementById('conditionText');
  const notesInput = document.getElementById('notes');
  const notesCount = document.getElementById('notesCount');
  const stepperButton = document.getElementById('thresholdStepper');
  const formNotice = document.getElementById('alertNotice');

  const priorityTexts = {
    Alta: 'Atención preferente para reposición.',
    Media: 'Atención normal para reposición.',
    Baja: 'Atención cuando sea posible.'
  };
  const priorityRanks = { Alta: 1, Media: 2, Baja: 3 };
  const priorityTags = { Alta: 'tag-high', Media: 'tag-medium', Baja: 'tag-low' };
  const monthNames = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

  function findProduct() {
    return products.find(function (product) {
      return product.name + ' · ' + product.code === productInput.value;
    });
  }

  // Revisa el producto y el umbral, y muestra la condición detectada
  function updateCondition() {
    const product = findProduct();
    productInput.setCustomValidity(product ? '' : 'Elige un producto de la lista.');
    thresholdInput.setCustomValidity('');
    if (!product) {
      conditionText.textContent = 'Selecciona un producto';
      return;
    }
    const threshold = Number(thresholdInput.value);
    if (thresholdInput.value !== '' && threshold > product.stock) {
      conditionText.textContent = product.stock + ' de ' + threshold + ' ' + product.unit
        + ' · ' + (threshold - product.stock) + ' por debajo del mínimo';
    } else {
      conditionText.textContent = 'Sin condición de stock bajo';
      thresholdInput.setCustomValidity('El umbral debe ser mayor que el stock actual.');
    }
  }

  function updateProductData() {
    const product = findProduct();
    categoryInput.value = product ? product.category : '';
    locationInput.value = product ? product.location : '';
    stockInput.value = product ? product.stock + ' ' + product.unit : '';
    thresholdInput.value = product ? product.min : '';
    document.getElementById('thresholdUnit').textContent = 'Unidad de medida: ' + (product ? product.unit : 'unidades');
    updateCondition();
  }

  function updateNotesCount() {
    notesCount.textContent = notesInput.value.length;
  }

  function twoDigits(number) {
    return String(number).padStart(2, '0');
  }

  // Crea una fila copiando una existente y cambiando sus textos
  function createRow(product) {
    const now = new Date();
    const priority = prioritySelect.value;
    const threshold = thresholdInput.value;
    const hours = twoDigits(now.getHours()) + ':' + twoDigits(now.getMinutes());
    const row = document.querySelector('.alert-row').cloneNode(true);
    const priorityTag = row.querySelector('.js-priority');

    row.dataset.name = product.name;
    row.dataset.code = product.code;
    row.dataset.category = product.category;
    row.dataset.priority = priority;
    row.dataset.status = 'Pendiente';
    row.dataset.rank = priorityRanks[priority];
    row.dataset.time = String(now.getFullYear()) + twoDigits(now.getMonth() + 1) + twoDigits(now.getDate())
      + twoDigits(now.getHours()) + twoDigits(now.getMinutes());

    row.querySelector('.js-name').textContent = product.name;
    row.querySelector('.js-code').textContent = product.code;
    row.querySelector('.js-category').textContent = product.category;
    row.querySelector('.js-location').textContent = product.location;
    row.querySelector('.js-stock').textContent = product.stock + ' ' + product.unit;
    row.querySelector('.js-min').textContent = threshold + ' ' + product.unit;
    priorityTag.className = 'tag js-priority ' + priorityTags[priority];
    priorityTag.textContent = '• ' + priority;
    const statusTag = row.querySelector('.js-status');
    statusTag.className = 'tag js-status tag-low';
    statusTag.textContent = 'Pendiente';
    row.querySelector('.js-date').textContent = twoDigits(now.getDate()) + ' ' + monthNames[now.getMonth()] + ' ' + now.getFullYear();
    row.querySelector('.js-time').textContent = hours + ' h';
    return row;
  }

  productInput.addEventListener('input', function () {
    updateProductData();
    formNotice.classList.add('d-none');
  });
  thresholdInput.addEventListener('input', updateCondition);
  notesInput.addEventListener('input', updateNotesCount);
  prioritySelect.addEventListener('change', function () {
    priorityHelp.textContent = priorityTexts[prioritySelect.value];
  });

  // La mitad de arriba del ícono sube el número y la de abajo lo baja
  stepperButton.addEventListener('click', function (event) {
    const box = stepperButton.getBoundingClientRect();
    if (event.clientY - box.top < box.height / 2) {
      thresholdInput.stepUp();
    } else {
      thresholdInput.stepDown();
    }
    updateCondition();
  });

  alertForm.addEventListener('submit', function (event) {
    event.preventDefault();
    const product = findProduct();
    document.getElementById('alertsBody').appendChild(createRow(product));
    window.refreshAlertsTable();
    alertForm.reset();
    formNotice.classList.remove('d-none');
  });

  // reset limpia los campos justo después de este evento; por eso se espera un instante
  alertForm.addEventListener('reset', function () {
    setTimeout(function () {
      updateProductData();
      updateNotesCount();
      priorityHelp.textContent = priorityTexts[prioritySelect.value];
    }, 0);
  });

  alertForm.addEventListener('click', function (event) {
    if (event.target.id === 'clearForm') {
      formNotice.classList.add('d-none');
    }
  });

  updateProductData();
})();
