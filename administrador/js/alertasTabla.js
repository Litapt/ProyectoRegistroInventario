// Funciones de la tabla de alertas: búsqueda, filtros, pestañas, orden y páginas.
// Trabaja solo con las filas que ya están en la pantalla; no consulta la base de datos.
(function () {
  const tableBody = document.getElementById('alertsBody');
  const searchInput = document.getElementById('alertSearch');
  const filterSelects = document.querySelectorAll('[data-filter]');
  const tabs = document.querySelectorAll('.alert-tab');
  const sortSelect = document.getElementById('alertSort');
  const pageSizeSelect = document.getElementById('pageSize');
  const previousButton = document.getElementById('previousPage');
  const nextButton = document.getElementById('nextPage');
  const pageNumbers = document.getElementById('pageNumbers');
  const rangeText = document.getElementById('rangeText');
  const emptyRow = document.getElementById('emptyRow');
  const clearButton = document.getElementById('clearFilters');

  let activeTab = { key: '', value: '' };
  let currentPage = 1;

  function getRows() {
    return Array.from(tableBody.querySelectorAll('.alert-row'));
  }

  // Devuelve true si la fila cumple la búsqueda, las listas y la pestaña activa
  function matchesFilters(row) {
    const searchText = searchInput.value.trim().toLowerCase();
    const rowText = (row.dataset.name + ' ' + row.dataset.code + ' ' + (row.dataset.folio || '')).toLowerCase();
    if (searchText !== '' && rowText.indexOf(searchText) === -1) {
      return false;
    }
    for (const select of filterSelects) {
      if (select.value !== '' && row.dataset[select.dataset.filter] !== select.value) {
        return false;
      }
    }
    return activeTab.key === '' || row.dataset[activeTab.key] === activeTab.value;
  }

  // Compara dos filas según la opción elegida en "Ordenar por"
  function compareRows(rowA, rowB) {
    const sortBy = sortSelect.value;
    if (sortBy === 'recent') {
      return rowB.dataset.time.localeCompare(rowA.dataset.time);
    }
    if (sortBy === 'name') {
      return rowA.dataset.name.localeCompare(rowB.dataset.name, 'es');
    }
    return Number(rowA.dataset.rank) - Number(rowB.dataset.rank);
  }

  // Actualiza todos los números de las tarjetas y las pestañas
  function updateCounts() {
    const rows = getRows();
    document.querySelectorAll('[data-count-key]').forEach(function (element) {
      const key = element.dataset.countKey;
      const value = element.dataset.countValue;
      const total = key === '' ? rows.length : rows.filter(function (row) {
        return row.dataset[key] === value;
      }).length;
      element.textContent = total;
    });
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

  // Vuelve a dibujar la tabla con los filtros, el orden y la página actuales
  function render() {
    const rows = getRows();
    const visibleRows = rows.filter(matchesFilters).sort(compareRows);
    const pageSize = Number(pageSizeSelect.value);
    const totalPages = Math.max(1, Math.ceil(visibleRows.length / pageSize));
    currentPage = Math.min(currentPage, totalPages);
    const firstIndex = (currentPage - 1) * pageSize;
    const pageRows = visibleRows.slice(firstIndex, firstIndex + pageSize);

    rows.forEach(function (row) {
      row.classList.add('d-none');
    });
    pageRows.forEach(function (row) {
      row.classList.remove('d-none');
      tableBody.appendChild(row);
    });
    emptyRow.classList.toggle('d-none', visibleRows.length > 0);

    // Cada pantalla indica cómo llamar a sus filas: "alertas" o "incidencias"
    const rowsName = tableBody.dataset.rowsName || 'alertas';
    rangeText.textContent = visibleRows.length === 0
      ? 'Sin ' + rowsName + ' que mostrar'
      : 'Mostrando ' + (firstIndex + 1) + '–' + (firstIndex + pageRows.length) + ' de ' + visibleRows.length + ' ' + rowsName;

    pageNumbers.textContent = '';
    for (let pageNumber = 1; pageNumber <= totalPages; pageNumber++) {
      pageNumbers.appendChild(createPageButton(pageNumber));
    }
    previousButton.disabled = currentPage === 1;
    nextButton.disabled = currentPage === totalPages;
    updateCounts();
  }

  function goToFirstPageAndRender() {
    currentPage = 1;
    render();
  }

  searchInput.addEventListener('input', goToFirstPageAndRender);
  filterSelects.forEach(function (select) {
    select.addEventListener('change', goToFirstPageAndRender);
  });
  sortSelect.addEventListener('change', goToFirstPageAndRender);
  pageSizeSelect.addEventListener('change', goToFirstPageAndRender);

  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      tabs.forEach(function (otherTab) {
        otherTab.classList.remove('is-active');
      });
      tab.classList.add('is-active');
      activeTab = { key: tab.dataset.key, value: tab.dataset.value };
      goToFirstPageAndRender();
    });
  });

  previousButton.addEventListener('click', function () {
    currentPage -= 1;
    render();
  });
  nextButton.addEventListener('click', function () {
    currentPage += 1;
    render();
  });

  clearButton.addEventListener('click', function () {
    searchInput.value = '';
    filterSelects.forEach(function (select) {
      select.value = '';
    });
    sortSelect.selectedIndex = 0;
    tabs[0].click();
  });

  // Permite que el formulario de alertas pida redibujar la tabla al agregar una fila
  window.refreshAlertsTable = goToFirstPageAndRender;

  render();
})();
