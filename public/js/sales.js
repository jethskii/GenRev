// public/js/sales.js

document.addEventListener('DOMContentLoaded', () => {
    loadSalesData();
    setupEventListeners();
});

let salesData = [...window.sampleData];
let currentPage = 1;
const itemsPerPage = 10;
let currentSort = { column: null, direction: 'asc' };
let currentFilter = 'all';
let searchTerm = '';

function setupEventListeners() {
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const tableHeaders = document.querySelectorAll('th[data-sort]');
    const addSaleBtn = document.getElementById('addSaleBtn');
    const closeModal = document.getElementById('closeModal');
    const saleForm = document.getElementById('saleForm');
    const sidebarItems = document.querySelectorAll('.sidebar-item');
    const calendarIcon = document.getElementById('calendarIcon');
    const profileIcon = document.getElementById('profileIcon');

    searchInput.addEventListener('input', () => {
        searchTerm = searchInput.value.toLowerCase();
        currentPage = 1;
        loadSalesData();
    });

    statusFilter.addEventListener('change', () => {
        currentFilter = statusFilter.value;
        currentPage = 1;
        loadSalesData();
    });

    tableHeaders.forEach(header => {
        header.addEventListener('click', () => {
            const column = header.dataset.sort;
            if (currentSort.column === column) {
                currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
            } else {
                currentSort.column = column;
                currentSort.direction = 'asc';
            }

            tableHeaders.forEach(h => h.classList.remove('sort-asc', 'sort-desc'));
            header.classList.add(`sort-${currentSort.direction}`);
            loadSalesData();
        });
    });

    addSaleBtn.addEventListener('click', () => openModal('add'));
    closeModal.addEventListener('click', () => saleModal.style.display = 'none');

    saleForm.addEventListener('submit', (e) => {
        e.preventDefault();
        saveSale();
    });

    sidebarItems.forEach(item => {
        item.addEventListener('click', () => {
            const page = item.dataset.page;
            window.location.href = `/${page}`;
        });
    });

    calendarIcon.addEventListener('click', () => alert('Calendar functionality placeholder'));
    profileIcon.addEventListener('click', () => alert('Profile functionality placeholder'));

    window.addEventListener('click', (e) => {
        if (e.target === saleModal) {
            saleModal.style.display = 'none';
        }
    });
}

function loadSalesData() {
    const tableBody = document.getElementById('salesTableBody');
    const pagination = document.getElementById('pagination');
    let filteredData = [...salesData];

    if (searchTerm) {
        filteredData = filteredData.filter(s => s.invoice.toLowerCase().includes(searchTerm) || s.product.toLowerCase().includes(searchTerm));
    }

    if (currentFilter !== 'all') {
        filteredData = filteredData.filter(s => s.status.toLowerCase() === currentFilter);
    }

    if (currentSort.column) {
        filteredData.sort((a, b) => {
            let valueA = a[currentSort.column];
            let valueB = b[currentSort.column];
            if (typeof valueA === 'string') {
                valueA = valueA.toLowerCase();
                valueB = valueB.toLowerCase();
            }
            return (valueA < valueB ? -1 : valueA > valueB ? 1 : 0) * (currentSort.direction === 'asc' ? 1 : -1);
        });
    }

    const totalPages = Math.ceil(filteredData.length / itemsPerPage);
    const startIndex = (currentPage - 1) * itemsPerPage;
    const paginatedData = filteredData.slice(startIndex, startIndex + itemsPerPage);

    if (paginatedData.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="8" class="loading">No sales data found</td></tr>';
    } else {
        tableBody.innerHTML = paginatedData.map(sale => `
            <tr>
                <td>${sale.invoice}</td>
                <td>${sale.product}</td>
                <td>${formatDate(sale.date)}</td>
                <td>${sale.quantity}</td>
                <td>₱${sale.price.toFixed(2)}</td>
                <td>₱${sale.total.toFixed(2)}</td>
                <td class="status-${sale.status.toLowerCase()}">${sale.status}</td>
                <td>
                    <div class="action-buttons">
                        <button class="edit-button" onclick="editSale(${sale.id})">Edit</button>
                        <button class="delete-button" onclick="deleteSale(${sale.id})">Delete</button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    renderPagination(totalPages);
}

function renderPagination(totalPages) {
    const pagination = document.getElementById('pagination');
    if (totalPages <= 1) return pagination.innerHTML = '';

    let html = `
        <button class="pagination-button" ${currentPage === 1 ? 'disabled' : ''} onclick="changePage(${currentPage - 1})">&laquo;</button>
    `;
    for (let i = 1; i <= totalPages; i++) {
        html += `<button class="pagination-button ${currentPage === i ? 'active' : ''}" onclick="changePage(${i})">${i}</button>`;
    }
    html += `<button class="pagination-button" ${currentPage === totalPages ? 'disabled' : ''} onclick="changePage(${currentPage + 1})">&raquo;</button>`;
    pagination.innerHTML = html;
}

function changePage(page) {
    currentPage = page;
    loadSalesData();
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { month: '2-digit', day: '2-digit', year: 'numeric' }).replace(/\//g, '-');
}

function openModal(mode, saleId = null) {
    const modalTitle = document.getElementById('modalTitle');
    const form = document.getElementById('saleForm');

    if (mode === 'add') {
        modalTitle.textContent = 'Add Sale';
        form.reset();
        document.getElementById('saleId').value = '';
        document.getElementById('dateInput').value = new Date().toISOString().split('T')[0];
    } else {
        modalTitle.textContent = 'Edit Sale';
        const sale = salesData.find(s => s.id === saleId);
        if (!sale) return;
        document.getElementById('saleId').value = sale.id;
        document.getElementById('invoiceInput').value = sale.invoice;
        document.getElementById('productInput').value = sale.product;
        document.getElementById('dateInput').value = sale.date;
        document.getElementById('quantityInput').value = sale.quantity;
        document.getElementById('priceInput').value = sale.price;
        document.getElementById('statusInput').value = sale.status;
    }

    document.getElementById('saleModal').style.display = 'flex';
}

function saveSale() {
    const saleId = document.getElementById('saleId').value;
    const invoice = document.getElementById('invoiceInput').value;
    const product = document.getElementById('productInput').value;
    const date = document.getElementById('dateInput').value;
    const quantity = parseInt(document.getElementById('quantityInput').value);
    const price = parseFloat(document.getElementById('priceInput').value);
    const status = document.getElementById('statusInput').value;
    const total = quantity * price;

    if (saleId) {
        const index = salesData.findIndex(s => s.id == saleId);
        if (index !== -1) {
            salesData[index] = { id: parseInt(saleId), invoice, product, date, quantity, price, total, status };
        }
    } else {
        const newId = salesData.length ? Math.max(...salesData.map(s => s.id)) + 1 : 1;
        salesData.push({ id: newId, invoice, product, date, quantity, price, total, status });
    }

    document.getElementById('saleModal').style.display = 'none';
    loadSalesData();
    alert('Sale saved successfully!');
}

function editSale(id) {
    openModal('edit', id);
}

function deleteSale(id) {
    if (confirm('Are you sure you want to delete this sale?')) {
        salesData = salesData.filter(s => s.id !== id);
        loadSalesData();
        alert('Sale deleted successfully!');
    }
}

window.changePage = changePage;
window.editSale = editSale;
window.deleteSale = deleteSale;
