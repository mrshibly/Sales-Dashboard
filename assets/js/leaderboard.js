document.addEventListener('DOMContentLoaded', function() {
    const leaderboardContent = document.getElementById('leaderboard-content');
    const filterButtons = document.querySelectorAll('.btn-group .btn');

    function fetchLeaderboard(period) {
        fetch(`/sales_dashboard_php/api/leaderboard-data.php?period=${period}`)
            .then(response => {
                if (!response.ok) {
                    return response.json().then(errorData => {
                        throw new Error(errorData.message || 'Failed to fetch leaderboard data.');
                    });
                }
                return response.json();
            })
            .then(data => {
                renderLeaderboards(data);
            })
            .catch(error => {
                showToast(error.message || 'An error occurred while fetching leaderboard data.', 'error');
            });
    }

    function renderLeaderboards(data) {
        document.getElementById('sales-leaderboard').querySelector('.card-body').innerHTML = renderTable(data.sales_leaderboard, ['Rank', 'Sales Representative', 'Total Sales'], ['rank', 'name', 'total_sales']);
        document.getElementById('target-leaderboard').querySelector('.card-body').innerHTML = renderTable(data.target_leaderboard, ['Rank', 'Sales Representative', 'Target Completion (%)'], ['rank', 'name', 'target_completion']);
        document.getElementById('order-leaderboard').querySelector('.card-body').innerHTML = renderTable(data.order_leaderboard, ['Rank', 'Sales Representative', 'Order Count'], ['rank', 'name', 'order_count']);
    }

    function renderTable(data, headers, columns) {
        let table = '<div class="table-responsive"><table><thead><tr>';
        headers.forEach(header => {
            table += `<th>${header}</th>`;
        });
        table += '</tr></thead><tbody>';
        data.forEach((row, index) => {
            table += '<tr>';
            columns.forEach(column => {
                if (column === 'rank') {
                    table += `<td>${index + 1}</td>`;
                } else {
                    table += `<td>${row[column]}</td>`;
                }
            });
            table += '</tr>';
        });
        table += '</tbody></table></div>';
        return table;
    }

    filterButtons.forEach(button => {
        button.addEventListener('click', () => {
            filterButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            fetchLeaderboard(button.dataset.period);
        });
    });

    // Initial load
    fetchLeaderboard('weekly');
});