document.addEventListener('DOMContentLoaded', function() {
    const roleSelect = document.getElementById('role');
    const reportsToSelect = document.getElementById('reports_to');
    const divisionSelect = document.getElementById('division_id');
    const districtSelect = document.getElementById('district_id');
    const upazilaSelect = document.getElementById('upazila_id');
    const territorySelect = document.getElementById('territory_id');

    const reportsToGroup = document.getElementById('reports-to-group');
    const divisionGroup = document.getElementById('division-group');
    const districtGroup = document.getElementById('district-group');
    const upazilaGroup = document.getElementById('upazila-group');
    const territoryGroup = document.getElementById('territory-group');

    function updateFormFields() {
        const selectedRole = roleSelect.value;

        // Hide all optional fields by default
        reportsToGroup.style.display = 'none';
        divisionGroup.style.display = 'none';
        districtGroup.style.display = 'none';
        upazilaGroup.style.display = 'none';
        territoryGroup.style.display = 'none';

        // Filter managers based on the selected role
        const validManagerRoles = {
            'SR': ['TSM'],
            'TSM': ['ASM'],
            'ASM': ['DSM'],
            'DSM': ['NSM'],
            'NSM': ['HOM']
        };

        const managerOptions = reportsToSelect.options;
        for (let i = 0; i < managerOptions.length; i++) {
            const option = managerOptions[i];
            if (option.value === '') continue;
            const managerRole = option.dataset.role;
            option.style.display = (validManagerRoles[selectedRole] && validManagerRoles[selectedRole].includes(managerRole)) ? '' : 'none';
        }

        // Show fields based on the selected role
        switch (selectedRole) {
            case 'SR':
                reportsToGroup.style.display = 'block';
                territoryGroup.style.display = 'block';
                upazilaGroup.style.display = 'block';
                districtGroup.style.display = 'block';
                divisionGroup.style.display = 'block';
                break;
            case 'TSM':
                reportsToGroup.style.display = 'block';
                upazilaGroup.style.display = 'block';
                districtGroup.style.display = 'block';
                divisionGroup.style.display = 'block';
                break;
            case 'ASM':
                reportsToGroup.style.display = 'block';
                districtGroup.style.display = 'block';
                divisionGroup.style.display = 'block';
                break;
            case 'DSM':
                reportsToGroup.style.display = 'block';
                divisionGroup.style.display = 'block';
                break;
            case 'NSM':
                reportsToGroup.style.display = 'block';
                break;
        }
    }

    function populateDistricts() {
        const selectedDivisionId = divisionSelect.value;
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

    function populateUpazilas() {
        const selectedDistrictId = districtSelect.value;
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

    function populateTerritories() {
        const selectedUpazilaId = upazilaSelect.value;
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

    roleSelect.addEventListener('change', updateFormFields);
    divisionSelect.addEventListener('change', populateDistricts);
    districtSelect.addEventListener('change', populateUpazilas);
    upazilaSelect.addEventListener('change', populateTerritories);

    updateFormFields();
});