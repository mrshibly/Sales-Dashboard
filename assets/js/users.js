import { showToast, populateDistricts, populateUpazilas, populateTerritories, showLoadingSpinner, hideLoadingSpinner } from './utils.js';

document.addEventListener('DOMContentLoaded', function() {
    const userModal = new bootstrap.Modal(document.getElementById('userModal'));
    const userForm = document.getElementById('userForm');
    const userTableBody = document.getElementById('userTableBody');
    const divisionSelect = document.getElementById('division_id');
    const districtSelect = document.getElementById('district_id');
    const upazilaSelect = document.getElementById('upazila_id');
    const territorySelect = document.getElementById('territory_id');
    const exportUsersBtn = document.getElementById('exportUsersBtn');
    const searchUserInput = document.getElementById('searchUser');
    const filterUserRoleSelect = document.getElementById('filterUserRole');
    const filterUserDivisionSelect = document.getElementById('filterUserDivision');
    const filterUserDistrictSelect = document.getElementById('filterUserDistrict');

    let editUserId = null;

    // Fetch and display users
    function fetchUsers() {
        showLoadingSpinner();
        const searchTerm = searchUserInput.value;
        const filterRole = filterUserRoleSelect.value;
        const filterDivision = filterUserDivisionSelect.value;
        const filterDistrict = filterUserDistrictSelect.value;

        let url = '/sales_dashboard_php/api/users.php?';
        const params = [];

        if (searchTerm) {
            params.push(`search=${searchTerm}`);
        }
        if (filterRole) {
            params.push(`role=${filterRole}`);
        }
        if (filterDivision) {
            params.push(`division_id=${filterDivision}`);
        }
        if (filterDistrict) {
            params.push(`district_id=${filterDistrict}`);
        }

        url += params.join('&');

        fetch(url)
            .then(response => {
                if (!response.ok) {
                    return response.json().then(errorData => {
                        throw new Error(errorData.message || 'Failed to fetch users.');
                    });
                }
                return response.json();
            })
            .then(users => {
                userTableBody.innerHTML = '';
                if (users.length === 0) {
                    userTableBody.innerHTML = `<tr><td colspan="10" class="text-center">No users found.</td></tr>`;
                } else {
                    users.forEach(user => {
                        const row = `
                            <tr>
                                <td>${user.id}</td>
                                <td>${user.name}</td>
                                <td>${user.email}</td>
                                <td>${user.role}</td>
                                <td>${user.reports_to || 'N/A'}</td>
                                <td>${user.division_id || 'N/A'}</td>
                                <td>${user.district_id || 'N/A'}</td>
                                <td>${user.upazila_id || 'N/A'}</td>
                                <td>${user.territory_id || 'N/A'}</td>
                                <td>
                                    ${currentUserRole === 'HOM' ? `<button class="btn btn-sm btn-primary" onclick="openEditModal(${user.id})">Edit</button>` : ''}
                                    ${currentUserRole === 'HOM' ? `<button class="btn btn-sm btn-danger" onclick="deleteUser(${user.id})">Delete</button>` : ''}
                                </td>
                            </tr>
                        `;
                        userTableBody.innerHTML += row;
                    });
                }
            })
            .catch(error => {
                showToast(error.message || 'An error occurred while fetching users.', 'error');
            })
            .finally(() => {
                hideLoadingSpinner();
            });
    }

    // Populate dropdowns
    function populateDistricts(divisionSelect, districtSelect, upazilaSelect, territorySelect, allDistricts, allUpazilas, allTerritories, selectedDivisionId) {
        filterUserDistrictSelect.innerHTML = '<option value="">Filter by District</option>';
        districtSelect.innerHTML = '<option value="">Select District</option>';
        upazilaSelect.innerHTML = '<option value="">Select Upazila</option>';
        territorySelect.innerHTML = '<option value="">Select Territory</option>';

        if (selectedDivisionId) {
            const filteredDistricts = allDistricts.filter(d => d.division_id == selectedDivisionId);
            filteredDistricts.forEach(district => {
                const option = document.createElement('option');
                option.value = district.id;
                option.textContent = district.name;
                filterUserDistrictSelect.appendChild(option);
                // Also for modal
                const optionModal = document.createElement('option');
                optionModal.value = district.id;
                optionModal.textContent = district.name;
                districtSelect.appendChild(optionModal);
            });
        }
    }

    function populateUpazilas(upazilaSelect, territorySelect, allUpazilas, allTerritories, selectedDistrictId) {
        upazilaSelect.innerHTML = '<option value="">Select Upazila</option>';
        territorySelect.innerHTML = '<option value="">Select Territory</option>';

        if (selectedDistrictId) {
            const filteredUpazilas = allUpazilas.filter(u => u.district_id == selectedDistrictId);
            filteredUpazilas.forEach(upazila => {
                const option = document.createElement('option');
                option.value = upazila.id;
                option.textContent = upazila.name;
                upazilaSelect.appendChild(option);
            });
        }
    }

    function populateTerritories(territorySelect, allTerritories, selectedUpazilaId) {
        territorySelect.innerHTML = '<option value="">Select Territory</option>';

        if (selectedUpazilaId) {
            const filteredTerritories = allTerritories.filter(t => t.upazila_id == selectedUpazilaId);
            filteredTerritories.forEach(territory => {
                const option = document.createElement('option');
                option.value = territory.id;
                option.textContent = territory.name;
                territorySelect.appendChild(option);
            });
        }
    }

    // Event Listeners for dropdowns
    divisionSelect.addEventListener('change', () => populateDistricts(divisionSelect, districtSelect, upazilaSelect, territorySelect, allDistricts, allUpazilas, allTerritories, divisionSelect.value));
    districtSelect.addEventListener('change', () => populateUpazilas(upazilaSelect, territorySelect, allUpazilas, allTerritories, districtSelect.value));
    upazilaSelect.addEventListener('change', () => populateTerritories(territorySelect, allTerritories, upazilaSelect.value));

    // Open the modal for editing an existing user
    window.openEditModal = function(id) {
        showLoadingSpinner();
        fetch(`/sales_dashboard_php/api/users.php?id=${id}`)
            .then(response => response.json())
            .then(user => {
                editUserId = user.id;
                document.getElementById('modalTitle').textContent = 'Edit User';
                document.getElementById('name').value = user.name;
                document.getElementById('email').value = user.email;
                document.getElementById('role').value = user.role;
                document.getElementById('reports_to').value = user.reports_to;

                // Populate geographic dropdowns for editing
                if (user.territory_id) {
                    const userTerritory = allTerritories.find(t => t.id == user.territory_id);
                    if (userTerritory) {
                        const userUpazila = allUpazilas.find(u => u.id == userTerritory.upazila_id);
                        if (userUpazila) {
                            const userDistrict = allDistricts.find(d => d.id == userUpazila.district_id);
                            if (userDistrict) {
                                divisionSelect.value = userDistrict.division_id;
                                populateDistricts(divisionSelect, districtSelect, upazilaSelect, territorySelect, allDistricts, allUpazilas, allTerritories, userDistrict.division_id);
                                districtSelect.value = userDistrict.id;
                                populateUpazilas(upazilaSelect, territorySelect, allUpazilas, allTerritories, userDistrict.id);
                                upazilaSelect.value = userUpazila.id;
                                populateTerritories(territorySelect, allTerritories, userUpazila.id);
                                territorySelect.value = user.territory_id;
                            }
                        }
                    }
                } else if (user.upazila_id) {
                    const userUpazila = allUpazilas.find(u => u.id == user.upazila_id);
                    if (userUpazila) {
                        const userDistrict = allDistricts.find(d => d.id == userUpazila.district_id);
                        if (userDistrict) {
                            divisionSelect.value = userDistrict.division_id;
                            populateDistricts(divisionSelect, districtSelect, upazilaSelect, territorySelect, allDistricts, allUpazilas, allTerritories, userDistrict.division_id);
                            districtSelect.value = userDistrict.id;
                            populateUpazilas(upazilaSelect, territorySelect, allUpazilas, allTerritories, userDistrict.id);
                            upazilaSelect.value = userUpazila.id;
                        }
                    }
                } else if (user.district_id) {
                    const userDistrict = allDistricts.find(d => d.id == user.district_id);
                    if (userDistrict) {
                        divisionSelect.value = userDistrict.division_id;
                        populateDistricts(divisionSelect, districtSelect, upazilaSelect, territorySelect, allDistricts, allUpazilas, allTerritories, userDistrict.division_id);
                        districtSelect.value = userDistrict.id;
                    }
                } else if (user.division_id) {
                    divisionSelect.value = user.division_id;
                    populateDistricts(divisionSelect, districtSelect, upazilaSelect, territorySelect, allDistricts, allUpazilas, allTerritories, user.division_id);
                }

                userModal.show();
            })
            .finally(() => {
                hideLoadingSpinner();
            });
    };

    // Handle form submission
    userForm.addEventListener('submit', function(e) {
        e.preventDefault();
        showLoadingSpinner();
        const userData = {
            name: document.getElementById('name').value,
            email: document.getElementById('email').value,
            password: document.getElementById('password').value,
            role: document.getElementById('role').value,
            reports_to: document.getElementById('reports_to').value,
            division_id: document.getElementById('division_id').value,
            district_id: document.getElementById('district_id').value,
            upazila_id: document.getElementById('upazila_id').value,
            territory_id: document.getElementById('territory_id').value
        };

        const url = editUserId ? `/sales_dashboard_php/api/users.php?id=${editUserId}` : '/sales_dashboard_php/api/users.php';
        const method = editUserId ? 'PUT' : 'POST';

        fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(userData)
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
            userModal.hide();
            fetchUsers();
        })
        .catch(error => {
            showToast(error.message || 'An error occurred.', 'error');
        })
        .finally(() => {
            hideLoadingSpinner();
        });
    });

    // Delete a user
    window.deleteUser = function(id) {
        if (confirm('Are you sure you want to delete this user?')) {
            showLoadingSpinner();
            fetch(`/sales_dashboard_php/api/users.php?id=${id}`, { method: 'DELETE' })
                .then(response => response.json())
                .then(data => {
                    showToast(data.message);
                    fetchUsers();
                })
                .catch(error => {
                    showToast('An error occurred.', 'error');
                })
                .finally(() => {
                    hideLoadingSpinner();
                });
        }
    };

    // Initial fetch of users
    fetchUsers();

    // Export button event listener
    exportUsersBtn.addEventListener('click', function() {
        exportTableToCSV('users.csv', 'userTable');
    });

    // Filter event listeners
    searchUserInput.addEventListener('input', fetchUsers);
    filterUserRoleSelect.addEventListener('change', fetchUsers);
    filterUserDivisionSelect.addEventListener('change', () => {
        populateDistricts(filterUserDivisionSelect, filterUserDistrictSelect, upazilaSelect, territorySelect, allDistricts, allUpazilas, allTerritories, filterUserDivisionSelect.value);
        fetchUsers();
    });
        filterUserDistrict.addEventListener('change', fetchUsers);

    fetchUsers();
});
});