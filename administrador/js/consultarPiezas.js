// Búsqueda de piezas: la tabla se actualiza mientras escribes.
// Sin criterios se ven todas las piezas activas; cada dato que agregues reduce los resultados.
// El bote de basura pasa una pieza a la sección de desactivadas y "Reactivar" la regresa.
// Trabaja solo con las filas de la pantalla; no consulta la base de datos.
(function () {
  const PAGE_SIZE = 8;
  // Las piezas desactivadas se recuerdan solo mientras la pestaña del navegador siga abierta
  const STORAGE_KEY = 'deactivatedPieces';

  const searchForm = document.getElementById('searchForm');
  const statusSelect = document.getElementById('searchStatus');
  const tableBody = document.getElementById('piecesBody');
  const rows = Array.from(tableBody.querySelectorAll('.piece-row'));
  const emptyRow = document.getElementById('emptyRow');
  const emptyText = document.getElementById('emptyText');
  const resultsCount = document.getElementById('resultsCount');
  const resultsText = document.getElementById('resultsText');
  const criteriaBox = document.getElementById('criteriaBox');
  const criteriaList = document.getElementById('criteriaList');
  const rangeText = document.getElementById('rangeText');
  const pageNumbers = document.getElementById('pageNumbers');
  const previousButton = document.getElementById('previousPage');
  const nextButton = document.getElementById('nextPage');
  const deactivateNotice = document.getElementById('deactivateNotice');
  const confirmOverlay = document.getElementById('confirmOverlay');
  const pageSections = document.querySelectorAll('header, main');

  // Cada campo del formulario, el dato de la fila que revisa y su nombre en las etiquetas
  const criteria = [
    { input: document.getElementById('searchName'), key: 'name', label: 'Nombre' },
    { input: document.getElementById('searchSerial'), key: 'serial', label: 'Serie' },
    { input: document.getElementById('searchBrand'), key: 'brand', label: 'Marca' },
    { input: document.getElementById('searchDescription'), key: 'description', label: 'Descripción' }
  ];

  // Textos de la confirmación para cada acción
  const confirmActions = {
    deactivate: {
      title: '¿Deseas eliminar este producto?',
      getText: function (name) {
        return '"' + name + '" se desactivará y dejará de aparecer en la consulta. Podrás verlo eligiendo "Desactivadas" en el filtro de estado.';
      },
      button: 'Sí, eliminar',
      getNotice: function (name) {
        return '"' + name + '" se desactivó correctamente.';
      }
    },
    reactivate: {
      title: '¿Deseas reactivar este producto?',
      getText: function (name) {
        return '"' + name + '" volverá a aparecer en la consulta con las piezas activas.';
      },
      button: 'Sí, reactivar',
      getNotice: function (name) {
        return '"' + name + '" se reactivó correctamente.';
      }
    }
  };

  let currentPage = 1;
  // Lo que se va a confirmar: la fila, la acción y el botón que abrió el aviso
  let pendingConfirm = null;

  // El navegador puede bloquear este almacenamiento; en ese caso la pantalla sigue funcionando
  function loadDeactivatedSerials() {
    try {
      return JSON.parse(sessionStorage.getItem(STORAGE_KEY)) || [];
    } catch (error) {
      return [];
    }
  }

  function saveDeactivatedSerials() {
    const serials = rows.filter(isDeactivated).map(function (row) {
      return row.dataset.serial;
    });
    try {
      sessionStorage.setItem(STORAGE_KEY, JSON.stringify(serials));
    } catch (error) {
      // Sin almacenamiento, la pieza solo queda desactivada hasta recargar la página
    }
  }

  function isDeactivated(row) {
    return row.dataset.status === 'inactive';
  }

  // Cambia la fila a desactivada: oculta sus acciones y evita que se pueda abrir
  function markAsDeactivated(row) {
    row.dataset.status = 'inactive';
    row.querySelector('.piece-link').setAttribute('tabindex', '-1');
  }

  // Regresa la fila a activa, con sus acciones normales
  function markAsActive(row) {
    row.dataset.status = 'active';
    row.querySelector('.piece-link').removeAttribute('tabindex');
  }

  // Quita acentos y mayúsculas para que "almacen" también encuentre "Almacén"
  function normalize(text) {
    return text.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
  }

  // Cada palabra escrita debe aparecer en el dato, sin importar el orden
  function matchesCriterion(row, criterion) {
    const rowText = normalize(row.dataset[criterion.key]);
    return normalize(criterion.input.value).split(/\s+/).filter(Boolean).every(function (word) {
      return rowText.indexOf(word) !== -1;
    });
  }

  function createTag(text) {
    const tag = document.createElement('span');
    tag.className = 'search-tag fw-medium';
    tag.textContent = text;
    return tag;
  }

  function renderCriteria(activeCriteria, showDeactivated) {
    criteriaList.textContent = '';
    activeCriteria.forEach(function (criterion) {
      criteriaList.appendChild(createTag(criterion.label + ': ' + criterion.input.value.trim()));
    });
    if (showDeactivated) {
      criteriaList.appendChild(createTag('Estado: Desactivadas'));
    }
    criteriaBox.classList.toggle('d-none', activeCriteria.length === 0 && !showDeactivated);
  }

  function createPageButton(pageNumber) {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'page-number' + (pageNumber === currentPage ? ' is-active' : '');
    button.textContent = pageNumber;
    button.addEventListener('click', function () {
      currentPage = pageNumber;
      render();
    });
    return button;
  }

  // Texto debajo del título, según lo que se esté viendo
  function getResultsText(activeCriteria, showDeactivated) {
    if (activeCriteria.length > 0) {
      return 'Piezas que coinciden con todos los criterios ingresados.';
    }
    return showDeactivated ? 'Se muestran las piezas desactivadas.' : 'Se muestran todas las piezas registradas.';
  }

  // Vuelve a dibujar la tabla con los criterios y la página actuales
  function render() {
    const showDeactivated = statusSelect.value === 'inactive';
    const activeCriteria = criteria.filter(function (criterion) {
      return criterion.input.value.trim() !== '';
    });
    const visibleRows = rows.filter(function (row) {
      return isDeactivated(row) === showDeactivated && activeCriteria.every(function (criterion) {
        return matchesCriterion(row, criterion);
      });
    });
    const total = visibleRows.length;
    const totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));
    currentPage = Math.min(currentPage, totalPages);
    const firstIndex = (currentPage - 1) * PAGE_SIZE;
    const pageRows = visibleRows.slice(firstIndex, firstIndex + PAGE_SIZE);

    rows.forEach(function (row) {
      row.classList.add('d-none');
      row.classList.remove('is-striped');
    });
    pageRows.forEach(function (row, index) {
      row.classList.remove('d-none');
      row.classList.toggle('is-striped', index % 2 === 1);
    });
    emptyRow.classList.toggle('d-none', total > 0);
    emptyText.textContent = showDeactivated && activeCriteria.length === 0
      ? 'No hay piezas desactivadas.'
      : 'No hay piezas que coincidan con tu búsqueda.';

    resultsCount.textContent = total === 1 ? '1 pieza encontrada' : total + ' piezas encontradas';
    resultsText.textContent = getResultsText(activeCriteria, showDeactivated);
    renderCriteria(activeCriteria, showDeactivated);

    rangeText.textContent = total === 0
      ? 'Sin piezas que mostrar'
      : 'Mostrando ' + (firstIndex + 1) + '–' + (firstIndex + pageRows.length) + ' de ' + total + (total === 1 ? ' pieza' : ' piezas');
    pageNumbers.textContent = '';
    for (let pageNumber = 1; pageNumber <= totalPages; pageNumber++) {
      pageNumbers.appendChild(createPageButton(pageNumber));
    }
    previousButton.disabled = currentPage === 1;
    nextButton.disabled = currentPage === totalPages;
  }

  function goToFirstPageAndRender() {
    currentPage = 1;
    render();
  }

  // Abre la confirmación; mientras se ve, la página de atrás no recibe clics ni teclado
  function openConfirmation(row, actionName, trigger) {
    const action = confirmActions[actionName];
    pendingConfirm = { row: row, actionName: actionName, trigger: trigger };
    document.getElementById('confirmTitle').textContent = action.title;
    document.getElementById('confirmText').textContent = action.getText(row.dataset.name);
    document.getElementById('confirmAccept').textContent = action.button;
    document.getElementById('confirmIconDeactivate').classList.toggle('d-none', actionName !== 'deactivate');
    document.getElementById('confirmIconReactivate').classList.toggle('d-none', actionName !== 'reactivate');
    confirmOverlay.classList.remove('d-none');
    pageSections.forEach(function (section) {
      section.inert = true;
    });
    document.getElementById('confirmCancel').focus();
  }

  function closeConfirmation() {
    confirmOverlay.classList.add('d-none');
    pageSections.forEach(function (section) {
      section.inert = false;
    });
    pendingConfirm = null;
  }

  criteria.forEach(function (criterion) {
    criterion.input.addEventListener('input', goToFirstPageAndRender);
  });
  statusSelect.addEventListener('change', function () {
    deactivateNotice.classList.add('d-none');
    goToFirstPageAndRender();
  });

  // "Buscar piezas" aplica los criterios sin recargar la página
  searchForm.addEventListener('submit', function (event) {
    event.preventDefault();
    goToFirstPageAndRender();
  });

  // reset limpia los campos justo después de este evento; por eso se espera un instante
  searchForm.addEventListener('reset', function () {
    deactivateNotice.classList.add('d-none');
    setTimeout(goToFirstPageAndRender, 0);
  });

  tableBody.addEventListener('click', function (event) {
    const row = event.target.closest('.piece-row');
    if (!row) {
      return;
    }
    // Una pieza desactivada no se puede abrir: solo se puede reactivar
    if (isDeactivated(row)) {
      event.preventDefault();
      const reactivateButton = event.target.closest('.js-reactivate');
      if (reactivateButton) {
        openConfirmation(row, 'reactivate', reactivateButton);
      }
      return;
    }
    const deactivateButton = event.target.closest('.js-deactivate');
    if (deactivateButton) {
      openConfirmation(row, 'deactivate', deactivateButton);
      return;
    }
    // Un clic en la fila abre la pieza, salvo que sea en uno de sus enlaces
    if (!event.target.closest('a, button')) {
      window.location.href = row.dataset.url;
    }
  });

  document.getElementById('confirmCancel').addEventListener('click', function () {
    const trigger = pendingConfirm.trigger;
    closeConfirmation();
    trigger.focus();
  });

  document.getElementById('confirmAccept').addEventListener('click', function () {
    const row = pendingConfirm.row;
    const action = confirmActions[pendingConfirm.actionName];
    if (pendingConfirm.actionName === 'deactivate') {
      markAsDeactivated(row);
    } else {
      markAsActive(row);
    }
    saveDeactivatedSerials();
    closeConfirmation();
    deactivateNotice.textContent = action.getNotice(row.dataset.name);
    deactivateNotice.classList.remove('d-none');
    render();
  });

  // La tecla Esc cancela la confirmación
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && pendingConfirm) {
      document.getElementById('confirmCancel').click();
    }
  });

  previousButton.addEventListener('click', function () {
    currentPage -= 1;
    render();
  });
  nextButton.addEventListener('click', function () {
    currentPage += 1;
    render();
  });

  // Al abrir la pantalla, vuelve a marcar las piezas que ya se habían desactivado
  const deactivatedSerials = loadDeactivatedSerials();
  rows.forEach(function (row) {
    if (deactivatedSerials.indexOf(row.dataset.serial) !== -1) {
      markAsDeactivated(row);
    }
  });

  render();
})();
