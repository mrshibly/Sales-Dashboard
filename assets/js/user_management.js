import { showToast, populateDistricts, populateUpazilas, populateTerritories, showLoadingSpinner, hideLoadingSpinner } from './utils.js';

document.addEventListener('DOMContentLoaded', function() {
    const userModal = new bootstrap.Modal(document.getElementById('userModal'));
    const userForm = document.getElementById('userForm');
    const userTableBody = document.getElementById('userTableBody');
    const divisionSelect = document.getElementById('division_id');
    const districtSelect = document.getElementById('district_id');
    const upazilaSelect = document.getElementById('upazila_id');
    const territorySelect = document.getElementById('territory_id');
    const reportsToSelect = document.getElementById('reports_to');
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
                                <td>${user.reports_to_name || 'N/A'}</td>
                                <td>${user.division_name || 'N/A'}</td>
                                <td>${user.district_name || 'N/A'}</td>
                                <td>${user.upazila_name || 'N/A'}</td>
                                <td>${user.territory_name || 'N/A'}</td>
                                <td>
                                    <button class="btn btn-sm btn-primary edit-btn" data-id="${user.id}">Edit</button>
                                    <button class="btn btn-sm btn-danger delete-btn" data-id="${user.id}">Delete</button>
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

    // Populate reports_to dropdown based on selected role
    function updateReportsToDropdown(selectedRole, currentReportsToId = null) {
        reportsToSelect.innerHTML = '<option value="">None</option>';
        let eligibleRoles = [];

        switch (selectedRole) {
            case 'SR': eligibleRoles = ['TSM', 'ASM', 'DSM', 'NSM', 'HOM']; break;
            case 'TSM': eligibleRoles = ['ASM', 'DSM', 'NSM', 'HOM']; break;
            case 'ASM': eligibleRoles = ['DSM', 'NSM', 'HOM']; break;
            case 'DSM': eligibleRoles = ['NSM', 'HOM']; break;
            case 'NSM': eligibleRoles = ['HOM']; break;
            case 'HOM': eligibleRoles = []; break; // HOM reports to no one
        }

        allUsers.forEach(user => {
            if (eligibleRoles.includes(user.role) && user.id !== editUserId) {
                const option = document.createElement('option');
                option.value = user.id;
                option.textContent = `${user.name} (${user.role})`;
                reportsToSelect.appendChild(option);
            }
        });

        if (currentReportsToId) {
            reportsToSelect.value = currentReportsToId;
        }
    }

    // Populate geographic dropdowns based on selected role
    function updateGeographicDropdowns(selectedRole, user = {}) {
        // Reset all geographic dropdowns
        divisionSelect.innerHTML = '<option value="">Select Division</option>';
        districtSelect.innerHTML = '<option value="">Select District</option>';
        upazilaSelect.innerHTML = '<option value="">Select Upazila</option>';
        territorySelect.innerHTML = '<option value="">Select Territory</option>';

        // Repopulate divisions (always available)
        allDivisions.forEach(div => {
            const option = document.createElement('option');
            option.value = div.id;
            option.textContent = div.name;
            divisionSelect.appendChild(option);
        });

        // Hide all geographic groups by default
        document.getElementById('division-group').style.display = 'none';
        document.getElementById('district-group').style.display = 'none';
        document.getElementById('upazila-group').style.display = 'none';
        document.getElementById('territory-group').style.display = 'none';

        // Show relevant geographic groups based on role
        switch (selectedRole) {
            case 'SR':
                document.getElementById('territory-group').style.display = 'block';
            case 'TSM':
                document.getElementById('upazila-group').style.display = 'block';
            case 'ASM':
                document.getElementById('district-group').style.display = 'block';
            case 'DSM':
                document.getElementById('division-group').style.display = 'block';
                break;
        }

        // Set pre-selected values if editing a user
        if (user.division_id) {
            divisionSelect.value = user.division_id;
            populateDistricts(divisionSelect, districtSelect, upazilaSelect, territorySelect, allDistricts, allUpazilas, allTerritories, user.division_id);
        }
        if (user.district_id) {
            districtSelect.value = user.district_id;
            populateUpazilas(upazilaSelect, territorySelect, allUpazilas, allTerritories, user.district_id);
        }
        if (user.upazila_id) {
            upazilaSelect.value = user.upazila_id;
            populateTerritories(territorySelect, allTerritories, user.upazila_id);
        }
        if (user.territory_id) {
            territorySelect.value = user.territory_id;
        }
    }

    // Event Listeners for dropdowns
    document.getElementById('role').addEventListener('change', function() {
        updateReportsToDropdown(this.value);
        updateGeographicDropdowns(this.value);
    });
    divisionSelect.addEventListener('change', () => populateDistricts(divisionSelect, districtSelect, upazilaSelect, territorySelect, allDistricts, allUpazilas, allTerritories, divisionSelect.value));
    districtSelect.addEventListener('change', () => populateUpazilas(upazilaSelect, territorySelect, allUpazilas, allTerritories, districtSelect.value));
    upazilaSelect.addEventListener('change', () => populateTerritories(territorySelect, allTerritories, upazilaSelect.value));

    // Open the modal for adding a new user
    document.querySelector('[data-bs-target="#userModal"]').addEventListener('click', function() {
        editUserId = null;
        userForm.reset();
        document.getElementById('modalTitle').textContent = 'Add User';
        document.getElementById('password').setAttribute('required', 'required');
        updateReportsToDropdown(document.getElementById('role').value);
        updateGeographicDropdowns(document.getElementById('role').value);
    });

    // Open the modal for editing an existing user
    userTableBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-btn')) {
            editUserId = e.target.dataset.id;
            showLoadingSpinner();
            fetch(`/sales_dashboard_php/api/users.php?id=${editUserId}`)
                .then(response => response.json())
                .then(user => {
                    document.getElementById('modalTitle').textContent = 'Edit User';
                    document.getElementById('name').value = user.name;
                    document.getElementById('email').value = user.email;
                    document.getElementById('role').value = user.role;
                    document.getElementById('password').removeAttribute('required'); // Password not required for edit
                    
                    updateReportsToDropdown(user.role, user.reports_to);
                    updateGeographicDropdowns(user.role, user);

                    userModal.show();
                })
                .catch(error => {
                    showToast(error.message || 'An error occurred while fetching user data.', 'error');
                })
                .finally(() => {
                    hideLoadingSpinner();
                });
        }
    });

    // Handle form submission
    userForm.addEventListener('submit', function(e) {
        e.preventDefault();
        showLoadingSpinner();
        const userData = {
            name: document.getElementById('name').value,
            email: document.getElementById('email').value,
            password: document.getElementById('password').value,
            role: document.getElementById('role').value,
            reports_to: document.getElementById('reports_to').value === '' ? null : document.getElementById('reports_to').value,
            division_id: document.getElementById('division_id').value === '' ? null : document.getElementById('division_id').value,
            district_id: document.getElementById('district_id').value === '' ? null : document.getElementById('district_id').value,
            upazila_id: document.getElementById('upazila_id').value === '' ? null : document.getElementById('upazila_id').value,
            territory_id: document.getElementById('territory_id').value === '' ? null : document.getElementById('territory_id').value
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
            userForm.reset();
            editUserId = null;
        })
        .catch(error => {
            showToast(error.message || 'An error occurred.', 'error');
        })
        .finally(() => {
            hideLoadingSpinner();
        });
    });

    // Delete a user
    userTableBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-btn')) {
            const userId = e.target.dataset.id;
            if (confirm('Are you sure you want to delete this user?')) {
                showLoadingSpinner();
                fetch(`/sales_dashboard_php/api/users.php?id=${userId}`, { method: 'DELETE' })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(errorData => {
                                throw new Error(errorData.message || 'Failed to delete user.');
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        showToast(data.message, 'success');
                        fetchUsers();
                    })
                    .catch(error => {
                        showToast(error.message || 'An error occurred.', 'error');
                    })
                    .finally(() => {
                        hideLoadingSpinner();
                    });
            }
        }
    });

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
        populateDistricts(filterUserDivisionSelect, document.getElementById('filterUserDistrict'), null, null, allDistricts, allUpazilas, allTerritories, filterUserDivisionSelect.value);
        fetchUsers();
    });
    document.getElementById('filterUserDistrict').addEventListener('change', fetchUsers);

    // Initial population of reports_to and geographic dropdowns for Add User modal
    updateReportsToDropdown(document.getElementById('role').value);
    updateGeographicDropdowns(document.getElementById('role').value);
});