import { showToast, populateDistricts, populateUpazilas, populateTerritories } from './utils.js';

const customerModal = new bootstrap.Modal(document.getElementById('customerModal'));
const customerForm = document.getElementById('customerForm');
const customerTableBody = document.getElementById('customerTableBody');
const divisionSelect = document.getElementById('division_id');
const districtSelect = document.getElementById('district_id');
const upazilaSelect = document.getElementById('upazila_id');
const territorySelect = document.getElementById('territory_id');
const searchCustomerInput = document.getElementById('searchCustomer');
const filterSalesRepSelect = document.getElementById('filterSalesRep');
const filterTerritorySelect = document.getElementById('filterTerritory');

let editCustomerId = null;

function fetchCustomers() {
    const searchTerm = searchCustomerInput.value;
    const salesRepId = filterSalesRepSelect.value;
    const territoryId = filterTerritorySelect.value;

    let url = '/sales_dashboard_php/api/customers.php?';
    const params = [];

    if (searchTerm) {
        params.push(`search=${searchTerm}`);
    }
    if (salesRepId) {
        params.push(`sales_rep_id=${salesRepId}`);
    }
    if (territoryId) {
        params.push(`territory_id=${territoryId}`);
    }

    url += params.join('&');

    fetch(url)
        .then(response => response.json())
        .then(customers => {
            customerTableBody.innerHTML = '';
            if (customers.length === 0) {
                customerTableBody.innerHTML = `<tr><td colspan="8" class="text-center">No customers found.</td></tr>`;
            } else {
                customers.forEach(customer => {
                    const row = `
                        <tr>
                            <td>${customer.id}</td>
                            <td>${customer.name}</td>
                            <td>${customer.phone}</td>
                            <td>${customer.email}</td>
                            <td>${customer.address}</td>
                            <td>${customer.sales_rep_name}</td>
                            <td>${customer.territory_id || 'N/A'}</td>
                            <td>
                                <button class="btn btn-sm btn-primary edit-btn" data-id="${customer.id}">Edit</button>
                                <button class="btn btn-sm btn-danger delete-btn" data-id="${customer.id}">Delete</button>
                            </td>
                        </tr>
                    `;
                    customerTableBody.innerHTML += row;
                });
            }
        })
        .catch(error => {
            showToast('An error occurred while fetching customers.', 'error');
        });
}

divisionSelect.addEventListener('change', () => populateDistricts(divisionSelect, districtSelect, upazilaSelect, territorySelect, allDistricts, allUpazilas, allTerritories, divisionSelect.value));
districtSelect.addEventListener('change', () => populateUpazilas(upazilaSelect, territorySelect, allUpazilas, allTerritories, districtSelect.value));
upazilaSelect.addEventListener('change', () => populateTerritories(territorySelect, allTerritories, upazilaSelect.value));

customerForm.addEventListener('submit', function(e) {
    e.preventDefault();
    const customerData = {
        name: document.getElementById('name').value,
        phone: document.getElementById('phone').value,
        email: document.getElementById('email').value,
        address: document.getElementById('address').value,
        assigned_sales_rep_id: document.getElementById('assigned_sales_rep_id').value,
        territory_id: document.getElementById('territory_id').value
    };

    const url = editCustomerId ? `/sales_dashboard_php/api/customers.php?id=${editCustomerId}` : '/sales_dashboard_php/api/customers.php';
    const method = editCustomerId ? 'PUT' : 'POST';

    fetch(url, {
        method: method,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(customerData)
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
        customerModal.hide();
        fetchCustomers();
        customerForm.reset();
        editCustomerId = null;
    })
    .catch(error => {
        showToast('An error occurred.', 'error');
    });
});

customerTableBody.addEventListener('click', function(e) {
    if (e.target.classList.contains('edit-btn')) {
        editCustomerId = e.target.dataset.id;
        fetch(`/sales_dashboard_php/api/customers.php?id=${editCustomerId}`)
            .then(response => response.json())
            .then(customer => {
                document.getElementById('modalTitle').textContent = 'Edit Customer';
                document.getElementById('name').value = customer.name;
                document.getElementById('phone').value = customer.phone;
                document.getElementById('email').value = customer.email;
                document.getElementById('address').value = customer.address;
                document.getElementById('assigned_sales_rep_id').value = customer.assigned_sales_rep_id;
                
                const customerTerritory = allTerritories.find(t => t.id == customer.territory_id);
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
                customerModal.show();
            });
    } else if (e.target.classList.contains('delete-btn')) {
        const customerId = e.target.dataset.id;
        if (confirm('Are you sure you want to delete this customer?')) {
            fetch(`/sales_dashboard_php/api/customers.php?id=${customerId}`, { method: 'DELETE' })
                .then(response => response.json())
                .then(data => {
                    showToast(data.message);
                    fetchCustomers();
                })
                .catch(error => {
                    showToast('An error occurred.', 'error');
                });
        }
    }
});

searchCustomerInput.addEventListener('input', fetchCustomers);
filterSalesRepSelect.addEventListener('change', fetchCustomers);
filterTerritorySelect.addEventListener('change', fetchCustomers);

fetchCustomers();