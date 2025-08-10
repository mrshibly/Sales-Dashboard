import { showToast, populateDistricts, populateUpazilas, populateTerritories, showLoadingSpinner, hideLoadingSpinner } from './utils.js';

const orderModal = new bootstrap.Modal(document.getElementById('orderModal'));
const orderForm = document.getElementById('orderForm');
const orderTableBody = document.getElementById('orderTableBody');
const divisionSelect = document.getElementById('division_id');
const districtSelect = document.getElementById('district_id');
const upazilaSelect = document.getElementById('upazila_id');
const territorySelect = document.getElementById('territory_id');
const exportOrdersBtn = document.getElementById('exportOrdersBtn');
const searchOrderInput = document.getElementById('searchOrder');
const filterOrderStatusSelect = document.getElementById('filterOrderStatus');
const filterOrderDateStartInput = document.getElementById('filterOrderDateStart');
const filterOrderDateEndInput = document.getElementById('filterOrderDateEnd');

let editOrderId = null;

// Fetch and display orders
function fetchOrders() {
    showLoadingSpinner();
    const searchTerm = searchOrderInput.value;
    const orderStatus = filterOrderStatusSelect.value;
    const orderDateStart = filterOrderDateStartInput.value;
    const orderDateEnd = filterOrderDateEndInput.value;

    let url = '/sales_dashboard_php/api/orders.php?';
    const params = [];

    if (searchTerm) {
        params.push(`search=${searchTerm}`);
    }
    if (orderStatus) {
        params.push(`status=${orderStatus}`);
    }
    if (orderDateStart) {
        params.push(`start_date=${orderDateStart}`);
    }
    if (orderDateEnd) {
        params.push(`end_date=${orderDateEnd}`);
    }

    url += params.join('&');

    fetch(url)
        .then(response => {
            if (!response.ok) {
                return response.json().then(errorData => {
                    throw new Error(errorData.message || 'Failed to fetch orders.');
                });
            }
            return response.json();
        })
        .then(orders => {
            orderTableBody.innerHTML = '';
            if (orders.length === 0) {
                orderTableBody.innerHTML = `<tr><td colspan="7" class="text-center">No orders found.</td></tr>`;
            } else {
                orders.forEach(order => {
                    const row = `
                        <tr>
                            <td>${order.id}</td>
                            <td>${order.customer_name}</td>
                            <td>${order.sales_rep_name}</td>
                            <td>${order.order_date}</td>
                            <td>${order.status}</td>
                            <td>${order.territory_id || 'N/A'}</td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="openEditModal(${order.id})">Edit</button>
                                <button class="btn btn-sm btn-danger" onclick="deleteOrder(${order.id})">Delete</button>
                            </td>
                        </tr>
                    `;
                    orderTableBody.innerHTML += row;
                });
            }
        })
        .catch(error => {
            showToast(error.message || 'An error occurred while fetching orders.', 'error');
        })
        .finally(() => {
            hideLoadingSpinner();
        });
    }

    // Event Listeners for dropdowns
    divisionSelect.addEventListener('change', () => populateDistricts(divisionSelect, districtSelect, upazilaSelect, territorySelect, allDistricts, allUpazilas, allTerritories, divisionSelect.value));
    districtSelect.addEventListener('change', () => populateUpazilas(upazilaSelect, territorySelect, allUpazilas, allTerritories, districtSelect.value));
    upazilaSelect.addEventListener('change', () => populateTerritories(territorySelect, allTerritories, upazilaSelect.value));

    // Open the modal for editing an existing order
    window.openEditModal = function(id) {
        showLoadingSpinner();
        fetch(`/sales_dashboard_php/api/orders.php?id=${id}`)
            .then(response => response.json())
            .then(order => {
                editOrderId = order.id;
                document.getElementById('modalTitle').textContent = 'Edit Order';
                document.getElementById('customer_id').value = order.customer_id;
                document.getElementById('sales_rep_id').value = order.sales_rep_id;
                document.getElementById('order_date').value = order.order_date;
                document.getElementById('status').value = order.status;

                // Populate geographic dropdowns for editing
                const orderCustomer = allCustomers.find(c => c.id == order.customer_id); // Assuming allCustomers is available
                if (orderCustomer && orderCustomer.territory_id) {
                    const customerTerritory = allTerritories.find(t => t.id == orderCustomer.territory_id);
                    if (customerTerritory) {
                        const customerUpazila = allUpazilas.find(u => u.id == customerTerritory.upazila_id);
                        if (customerUpazila) {
                            const customerDistrict = allDistricts.find(d => d.id == customerUpazila.district_id);
                            if (customerDistrict) {
                                divisionSelect.value = customerDistrict.division_id;
                                populateDistricts(divisionSelect, districtSelect, upazilaSelect, territorySelect, allDistricts, allUpazilas, allTerritories, customerDistrict.division_id);
                                districtSelect.value = customerDistrict.id;
                                populateUpazilas(upazilaSelect, territorySelect, allUpazilas, allTerritories, customerDistrict.id);
                                upazilaSelect.value = customerUpazila.id;
                                populateTerritories(territorySelect, allTerritories, customerUpazila.id);
                                territorySelect.value = customerTerritory.id;
                            }
                        }
                    }
                }

                orderModal.show();
            })
            .finally(() => {
                hideLoadingSpinner();
            });
    };

    // Handle form submission
    orderForm.addEventListener('submit', function(e) {
        e.preventDefault();
        showLoadingSpinner();
        const orderData = {
            customer_id: document.getElementById('customer_id').value,
            sales_rep_id: document.getElementById('sales_rep_id').value,
            order_date: document.getElementById('order_date').value,
            status: document.getElementById('status').value,
            territory_id: document.getElementById('territory_id').value // Include territory_id
        };

        const url = editOrderId ? `/sales_dashboard_php/api/orders.php?id=${editOrderId}` : '/sales_dashboard_php/api/orders.php';
        const method = editOrderId ? 'PUT' : 'POST';

        fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(orderData)
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(errorData => {
                    throw new Error(errorData.message || 'Failed to process request.');
                });
            }
            return response.json();
        })
        .then(data => {
            showToast(data.message, 'success');
            orderModal.hide();
            fetchOrders();
        })
        .catch(error => {
            showToast(error.message || 'An error occurred.', 'error');
        })
        .finally(() => {
            hideLoadingSpinner();
        });
    });

    // Delete an order
    window.deleteOrder = function(id) {
        if (confirm('Are you sure you want to delete this order?')) {
            showLoadingSpinner();
            fetch(`/sales_dashboard_php/api/orders.php?id=${id}`, { method: 'DELETE' })
                .then(response => response.json())
                .then(data => {
                    showToast(data.message);
                    fetchOrders();
                })
                .catch(error => {
                    showToast('An error occurred.', 'error');
                })
                .finally(() => {
                    hideLoadingSpinner();
                });
        }
    };

    // Initial fetch of orders
    fetchOrders();

    // Export button event listener
    exportOrdersBtn.addEventListener('click', function() {
        exportTableToCSV('orders.csv', 'orderTable');
    });

    // Filter event listeners
    searchOrderInput.addEventListener('input', fetchOrders);
    filterOrderStatusSelect.addEventListener('change', fetchOrders);
    filterOrderDateStartInput.addEventListener('change', fetchOrders);
        filterOrderDateEnd.addEventListener('change', fetchOrders);

    fetchOrders();
