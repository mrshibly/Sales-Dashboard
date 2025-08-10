import { showToast } from './utils.js';

let salesChartInstance = null; // Declare a variable to hold the chart instance

// Fetch and update dashboard metrics
function updateDashboardMetrics() {
    fetch('/sales_dashboard_php/api/dashboard-data.php')
        .then(response => {
            if (!response.ok) {
                return response.json().then(errorData => {
                    throw new Error(errorData.message || 'Failed to fetch dashboard metrics.');
                });
            }
            return response.json();
        })
        .then(data => {
            document.getElementById('total-sales').textContent = `${parseFloat(data.total_sales).toFixed(2)}`;
            document.getElementById('total-orders').textContent = data.total_orders;
            document.getElementById('target-progress').textContent = `${parseFloat(data.target_progress).toFixed(2)}%`;
        })
        .catch(error => {
            showToast(error.message || 'An error occurred while fetching dashboard metrics.', 'error');
        });
}

updateDashboardMetrics();

// Sales Chart
const salesChartCanvas = document.getElementById('salesChart');
if (salesChartCanvas) {
    fetch('/sales_dashboard_php/api/sales-data.php')
        .then(response => {
            if (!response.ok) {
                return response.json().then(errorData => {
                    throw new Error(errorData.message || 'Failed to fetch sales data.');
                });
            }
            return response.json();
        })
        .then(data => {
            const dates = data.map(item => item.date);
            const sales = data.map(item => item.total_sales);

            // Destroy existing chart if it exists
            if (salesChartInstance) {
                salesChartInstance.destroy();
            }

            salesChartInstance = new Chart(salesChartCanvas, {
                type: 'line',
                data: {
                    labels: dates,
                    datasets: [{
                        label: 'Sales',
                        data: sales,
                        borderColor: '#4a90e2',
                        backgroundColor: 'rgba(74, 144, 226, 0.2)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        })
        .catch(error => {
            showToast(error.message || 'An error occurred while fetching sales data.', 'error');
        });
}

// Real-Time Activity Feed
const activityFeed = document.querySelector('.activity-feed');
if (activityFeed) {
    function fetchActivity() {
        fetch('/sales_dashboard_php/api/activity-feed.php')
            .then(response => {
                if (!response.ok) {
                    return response.json().then(errorData => {
                        throw new Error(errorData.message || 'Failed to fetch activity feed.');
                    });
                }
                return response.json();
            })
            .then(activities => {
                activityFeed.innerHTML = ''; // Clear existing activities
                activities.forEach(activity => {
                    const activityItem = document.createElement('div');
                    activityItem.classList.add('activity-item');
                    activityItem.innerHTML = `
                        <p><strong>${activity.user}</strong> ${activity.action}</p>
                        <span>${activity.time}</span>
                    `;
                    activityFeed.appendChild(activityItem);
                });
            })
            .catch(error => {
                showToast(error.message || 'An error occurred while fetching activity feed.', 'error');
            });
    }

    fetchActivity(); // Initial fetch
    setInterval(fetchActivity, 30000); // Refresh every 30 seconds
}
