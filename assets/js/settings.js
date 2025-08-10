document.addEventListener('DOMContentLoaded', function() {
    const profileForm = document.getElementById('profileForm');
    const passwordForm = document.getElementById('passwordForm');
    const avatarForm = document.getElementById('avatarForm');

    const divisionSelect = document.getElementById('division_id');
    const districtSelect = document.getElementById('district_id');
    const upazilaSelect = document.getElementById('upazila_id');
    const territorySelect = document.getElementById('territory_id');

    // Fetch user data and populate the form
    function fetchUserData() {
        fetch('/sales_dashboard_php/api/settings.php')
            .then(response => response.json())
            .then(user => {
                document.getElementById('name').value = user.name;
                document.getElementById('email').value = user.email;
                document.getElementById('role').value = user.role;
                document.getElementById('reports_to').value = user.reports_to;
                if (user.avatar) {
                    document.getElementById('avatar-img').src = `../uploads/avatars/${user.avatar}`;
                }

                // Populate geographic dropdowns for editing
                if (user.territory_id) {
                    const userTerritory = allTerritories.find(t => t.id == user.territory_id);
                    if (userTerritory) {
                        const userUpazila = allUpazilas.find(u => u.id == userTerritory.upazila_id);
                        if (userUpazila) {
                            const userDistrict = allDistricts.find(d => d.id == userUpazila.district_id);
                            if (userDistrict) {
                                divisionSelect.value = userDistrict.division_id;
                                populateDistricts(userDistrict.division_id);
                                districtSelect.value = userDistrict.id;
                                populateUpazilas(userDistrict.id);
                                upazilaSelect.value = userUpazila.id;
                                populateTerritories(userUpazila.id);
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
                            populateDistricts(userDistrict.division_id);
                            districtSelect.value = userDistrict.id;
                            populateUpazilas(userDistrict.id);
                            upazilaSelect.value = user.upazila_id;
                        }
                    }
                } else if (user.district_id) {
                    const userDistrict = allDistricts.find(d => d.id == user.district_id);
                    if (userDistrict) {
                        divisionSelect.value = userDistrict.division_id;
                        populateDistricts(userDistrict.division_id);
                        districtSelect.value = user.district_id;
                    }
                } else if (user.division_id) {
                    divisionSelect.value = user.division_id;
                    populateDistricts(user.division_id);
                }
            });
    }

    // Populate dropdowns
    function populateDistricts(selectedDivisionId) {
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

    function populateUpazilas(selectedDistrictId) {
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

    function populateTerritories(selectedUpazilaId) {
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
    divisionSelect.addEventListener('change', () => populateDistricts(divisionSelect.value));
    districtSelect.addEventListener('change', () => populateUpazilas(districtSelect.value));
    upazilaSelect.addEventListener('change', () => populateTerritories(upazilaSelect.value));

    // Handle profile form submission
    profileForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(profileForm);
        formData.append('action', 'update_profile');
        formData.append('division_id', divisionSelect.value);
        formData.append('district_id', districtSelect.value);
        formData.append('upazila_id', upazilaSelect.value);
        formData.append('territory_id', territorySelect.value);

        fetch('/sales_dashboard_php/api/settings.php', { method: 'POST', body: formData })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(errorData => {
                        throw new Error(errorData.message || 'Failed to process request.');
                    });
                }
                return response.json();
            })
            .then(data => {
                showToast(data.message);
            })
            .catch(error => {
                showToast(error.message || 'An error occurred.', 'error');
            });
    });

    // Handle password form submission
    passwordForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(passwordForm);
        formData.append('action', 'change_password');

        fetch('/sales_dashboard_php/api/settings.php', { method: 'POST', body: formData })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(errorData => {
                        throw new Error(errorData.message || 'Failed to process request.');
                    });
                }
                return response.json();
            })
            .then(data => {
                showToast(data.message);
                passwordForm.reset();
            })
            .catch(error => {
                showToast(error.message || 'An error occurred.', 'error');
            });
    });

    // Handle avatar form submission
    avatarForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(avatarForm);
        formData.append('action', 'upload_avatar');

        fetch('/sales_dashboard_php/api/settings.php', { method: 'POST', body: formData })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(errorData => {
                        throw new Error(errorData.message || 'Failed to process request.');
                    });
                }
                return response.json();
            })
            .then(data => {
                showToast(data.message);
                if (data.avatar) {
                    document.getElementById('avatar-img').src = `../uploads/avatars/${data.avatar}`;
                }
            })
            .catch(error => {
                showToast(error.message || 'An error occurred.', 'error');
            });
    });

    // Toast notification function
    function showToast(message, type = 'success') {
        const toastContainer = document.getElementById('toastContainer');
        const toast = `
            <div class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;
        toastContainer.innerHTML = toast;
        const toastEl = document.querySelector('.toast');
        const bsToast = new bootstrap.Toast(toastEl);
        bsToast.show();
    }

    // Initial fetch of user data
    fetchUserData();
});