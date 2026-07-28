function applyTableCards(table) {
  if (!table || table.tagName !== 'TABLE') return;

  table.classList.add('table-cards');

  const headers = [...table.querySelectorAll('thead th')].map((th) => th.textContent.trim());

  table.querySelectorAll('tbody tr').forEach((row) => {
    const cells = row.querySelectorAll('td');
    if (cells.length === 1 && cells[0].hasAttribute('colspan')) {
      cells[0].removeAttribute('data-label');
      row.classList.add('table-cards-empty');
      return;
    }

    row.classList.remove('table-cards-empty');
    cells.forEach((cell, index) => {
      if (headers[index]) {
        cell.setAttribute('data-label', headers[index]);
      }
    });
  });
}

function resolveTable(el) {
  return el.tagName === 'TABLE' ? el : el.querySelector('table');
}

export default {
  mounted(el) {
    const table = resolveTable(el);
    if (table) applyTableCards(table);
  },
  updated(el) {
    const table = resolveTable(el);
    if (table) applyTableCards(table);
  },
};
