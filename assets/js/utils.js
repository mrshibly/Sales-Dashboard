export function showToast(message, type = 'success') {
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

export function populateDistricts(divisionSelect, districtSelect, upazilaSelect, territorySelect, allDistricts, allUpazilas, allTerritories, selectedDivisionId) {
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

export function populateUpazilas(upazilaSelect, territorySelect, allUpazilas, allTerritories, selectedDistrictId) {
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

export function populateTerritories(territorySelect, allTerritories, selectedUpazilaId) {
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

export function showLoadingSpinner() {
    const spinnerHtml = `
        <div id="loadingSpinner" style="
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 9999;
            background-color: rgba(255, 255, 255, 0.8);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
        ">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading data...</p>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', spinnerHtml);
}

export function hideLoadingSpinner() {
    const spinner = document.getElementById('loadingSpinner');
    if (spinner) {
        spinner.remove();
    }
}