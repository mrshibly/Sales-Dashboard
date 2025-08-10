

document.addEventListener('DOMContentLoaded', function() {
    fetch('/sales_dashboard_php/api/analytics-data.php')
        .then(response => {
            if (!response.ok) {
                return response.json().then(errorData => {
                    throw new Error(errorData.message || 'Failed to fetch analytics data.');
                });
            }
            return response.json();
        })
        .then(data => {
            const ctx = document.getElementById('salesChart').getContext('2d');
            const salesChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.months,
                    datasets: [{
                        label: 'Monthly Sales',
                        data: data.sales,
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        })
        .catch(error => {
            showToast(error.message || 'An error occurred while fetching analytics data.', 'error');
        });
});