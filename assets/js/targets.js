import { showToast, populateDistricts, populateUpazilas, populateTerritories, showLoadingSpinner, hideLoadingSpinner } from './utils.js';

const targetModal = new bootstrap.Modal(document.getElementById('targetModal'));
const targetForm = document.getElementById('targetForm');
const targetTableBody = document.getElementById('targetTableBody');
const userSelect = document.getElementById('user_id');
const divisionSelect = document.getElementById('division_id');
const districtSelect = document.getElementById('district_id');
const upazilaSelect = document.getElementById('upazila_id');
const territorySelect = document.getElementById('territory_id');
const exportTargetsBtn = document.getElementById('exportTargetsBtn');
const searchTargetUserInput = document.getElementById('searchTargetUser');
const filterTargetMonthInput = document.getElementById('filterTargetMonth');
const filterTargetDivisionSelect = document.getElementById('filterTargetDivision');

let editTargetId = null;

// Fetch and display targets
function fetchTargets() {
    showLoadingSpinner();
    const searchTerm = searchTargetUserInput.value;
    const filterMonth = filterTargetMonthInput.value;
    const filterDivision = filterTargetDivisionSelect.value;

    let url = '/sales_dashboard_php/api/targets.php?';
    const params = [];

    if (searchTerm) {
        params.push(`search=${searchTerm}`);
    }
    if (filterMonth) {
        params.push(`month=${filterMonth}`);
    }
    if (filterDivision) {
        params.push(`division_id=${filterDivision}`);
    }

    url += params.join('&');

    fetch(url)
        .then(response => {
            if (!response.ok) {
                return response.json().then(errorData => {
                    throw new Error(errorData.message || 'Failed to fetch targets.');
                });
            }
            return response.json();
        })
        .then(targets => {
            targetTableBody.innerHTML = '';
            if (targets.length === 0) {
                targetTableBody.innerHTML = `<tr><td colspan="7" class="text-center">No targets found.</td></tr>`;
            } else {
                targets.forEach(target => {
                    const row = `
                        <tr>
                            <td>${target.id}</td>
                            <td>${target.user_name}</td>
                            <td>${target.month}</td>
                            <td>${target.target_amount}</td>
                            <td>${target.achieved_amount}</td>
                            <td>${target.territory_id || 'N/A'}</td>
                            <td>
                                ${currentUserRole === 'HOM' || currentUserRole === 'NSM' || currentUserRole === 'DSM' || currentUserRole === 'ASM' || currentUserRole === 'TSM' || (currentUserRole === 'SR' && target.user_id == currentUserId) ? `<button class="btn btn-sm btn-primary" onclick="openEditModal(${target.id})">Edit</button>` : ''}
                                ${currentUserRole === 'HOM' || currentUserRole === 'NSM' || currentUserRole === 'DSM' || currentUserRole === 'ASM' || currentUserRole === 'TSM' ? `<button class="btn btn-sm btn-danger" onclick="deleteTarget(${target.id})">Delete</button>` : ''}
                            </td>
                        </tr>
                    `;
                    targetTableBody.innerHTML += row;
                });
            }
        })
        .catch(error => {
            showToast(error.message || 'An error occurred while fetching targets.', 'error');
        })
        .finally(() => {
            hideLoadingSpinner();
        });
}

// Populate dropdowns
function populateDistricts(divisionSelect, districtSelect, upazilaSelect, territorySelect, allDistricts, allUpazilas, allTerritories, selectedDivisionId) {
    districtSelect.innerHTML = '<option value="">Select District</option>';
    upazilaSelect.innerHTML = '<option value="">Select Upazila</option>';
    territorySelect.innerHTML = '<option value="">Select Territory</option>';

    if (selectedDivisionId) {
        const filteredDistricts = allDistricts.filter(d => d.division_id == selectedDivisionId);
        filteredDistricts.forEach(district => {
            const option = document.createElement('option');
            option.value = district.id;
            option.textContent = district.name;
            districtSelect.appendChild(option);
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

// Open the modal for editing an existing target
window.openEditModal = function(id) {
    showLoadingSpinner();
    fetch(`/sales_dashboard_php/api/targets.php?id=${id}`)
        .then(response => response.json())
        .then(target => {
            editTargetId = target.id;
            document.getElementById('modalTitle').textContent = 'Edit Target';
            document.getElementById('user_id').value = target.user_id;
            document.getElementById('month').value = target.month;
            document.getElementById('target_amount').value = target.target_amount;
            document.getElementById('achieved_amount').value = target.achieved_amount;

            // Populate geographic dropdowns for editing
            const targetUser = allUsers.find(u => u.id == target.user_id); // Assuming allUsers is available
            if (targetUser) {
                if (targetUser.territory_id) {
                    const userTerritory = allTerritories.find(t => t.id == targetUser.territory_id);
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
                                territorySelect.value = userTerritory.id;
                            }
                        }
                    }
                } else if (targetUser.upazila_id) {
                    const userUpazila = allUpazilas.find(u => u.id == targetUser.upazila_id);
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
                } else if (targetUser.district_id) {
                    const userDistrict = allDistricts.find(d => d.id == targetUser.district_id);
                    if (userDistrict) {
                        divisionSelect.value = userDistrict.division_id;
                        populateDistricts(divisionSelect, districtSelect, upazilaSelect, territorySelect, allDistricts, allUpazilas, allTerritories, userDistrict.division_id);
                        districtSelect.value = userDistrict.id;
                    }
                } else if (targetUser.division_id) {
                    divisionSelect.value = targetUser.division_id;
                    populateDistricts(divisionSelect, districtSelect, upazilaSelect, territorySelect, allDistricts, allUpazilas, allTerritories, targetUser.division_id);
                }
            }

            targetModal.show();
        })
        .finally(() => {
            hideLoadingSpinner();
        });
};

// Handle form submission
targetForm.addEventListener('submit', function(e) {
    e.preventDefault();
    showLoadingSpinner();
    const targetData = {
        user_id: document.getElementById('user_id').value,
        month: document.getElementById('month').value,
        target_amount: document.getElementById('target_amount').value,
        achieved_amount: document.getElementById('achieved_amount').value
    };

    const url = editTargetId ? `/sales_dashboard_php/api/targets.php?id=${editTargetId}` : '/sales_dashboard_php/api/targets.php';
    const method = editTargetId ? 'PUT' : 'POST';

    fetch(url, {
        method: method,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(targetData)
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
        targetModal.hide();
        fetchTargets();
    })
    .catch(error => {
        showToast(error.message || 'An error occurred.', 'error');
    })
    .finally(() => {
        hideLoadingSpinner();
    });
});

// Delete a target
window.deleteTarget = function(id) {
    if (confirm('Are you sure you want to delete this target?')) {
        showLoadingSpinner();
        fetch(`/sales_dashboard_php/api/targets.php?id=${id}`, { method: 'DELETE' })
            .then(response => response.json())
            .then(data => {
                showToast(data.message);
                fetchTargets();
            })
            .catch(error => {
                showToast('An error occurred.', 'error');
            })
            .finally(() => {
                hideLoadingSpinner();
            });
    }
};

// Initial fetch of targets
fetchTargets();

// Export button event listener
exportTargetsBtn.addEventListener('click', function() {
    exportTableToCSV('targets.csv', 'targetTable');
});

// Filter event listeners
searchTargetUserInput.addEventListener('input', fetchTargets);
filterTargetMonthInput.addEventListener('change', fetchTargets);
filterTargetDivisionSelect.addEventListener('change', () => {
    populateDistricts(filterTargetDivisionSelect, filterTargetDistrictSelect, filterTargetUpazilaSelect, filterTargetTerritorySelect, allDistricts, allUpazilas, allTerritories, filterTargetDivisionSelect.value);
    fetchTargets();
});

fetchTargets();