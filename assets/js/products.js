import { showToast, showLoadingSpinner, hideLoadingSpinner } from './utils.js';

document.addEventListener('DOMContentLoaded', function() {
    const productModal = new bootstrap.Modal(document.getElementById('productModal'));
    const productForm = document.getElementById('productForm');
    const productTableBody = document.getElementById('productTableBody');
    const exportProductsBtn = document.getElementById('exportProductsBtn');
    const searchProductInput = document.getElementById('searchProduct');
    const filterProductStockInput = document.getElementById('filterProductStock');
    let editProductId = null;

    // Fetch and display products
    function fetchProducts() {
        showLoadingSpinner();
        const searchTerm = searchProductInput.value;
        const minStock = filterProductStockInput.value;

        let url = '/sales_dashboard_php/api/products.php?';
        const params = [];

        if (searchTerm) {
            params.push(`search=${searchTerm}`);
        }
        if (minStock) {
            params.push(`min_stock=${minStock}`);
        }

        url += params.join('&');

        fetch(url)
            .then(response => {
                if (!response.ok) {
                    return response.json().then(errorData => {
                        throw new Error(errorData.message || 'Failed to fetch products.');
                    });
                }
                return response.json();
            })
            .then(products => {
                productTableBody.innerHTML = '';
                if (products.length === 0) {
                    productTableBody.innerHTML = `<tr><td colspan="5" class="text-center">No products found.</td></tr>`;
                } else {
                    products.forEach(product => {
                        const row = `
                            <tr>
                                <td>${product.id}</td>
                                <td>${product.name}</td>
                                <td>${product.price}</td>
                                <td>${product.stock}</td>
                                <td>
                                    ${currentUserRole === 'HOM' ? `<button class="btn btn-sm btn-primary" onclick="openEditModal(${product.id})">Edit</button>` : ''}
                                    ${currentUserRole === 'HOM' ? `<button class="btn btn-sm btn-danger" onclick="deleteProduct(${product.id})">Delete</button>` : ''}
                                </td>
                            </tr>
                        `;
                        productTableBody.innerHTML += row;
                    });
                }
            })
            .catch(error => {
                showToast(error.message || 'An error occurred while fetching products.', 'error');
            })
            .finally(() => {
                hideLoadingSpinner();
            });
    }

    // Open the modal for editing an existing product
    window.openEditModal = function(id) {
        showLoadingSpinner();
        fetch(`/sales_dashboard_php/api/products.php?id=${id}`)
            .then(response => response.json())
            .then(product => {
                editProductId = product.id;
                document.getElementById('modalTitle').textContent = 'Edit Product';
                document.getElementById('name').value = product.name;
                document.getElementById('price').value = product.price;
                document.getElementById('stock').value = product.stock;
                productModal.show();
            })
            .finally(() => {
                hideLoadingSpinner();
            });
    };

    // Handle form submission
    productForm.addEventListener('submit', function(e) {
        e.preventDefault();
        showLoadingSpinner();
        const formData = new FormData(productForm);
        const productData = Object.fromEntries(formData.entries());

        const url = editProductId ? `/sales_dashboard_php/api/products.php?id=${editProductId}` : '/sales_dashboard_php/api/products.php';
        const method = editProductId ? 'PUT' : 'POST';

        fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(productData)
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
            productModal.hide();
            fetchProducts();
        })
        .catch(error => {
            showToast(error.message || 'An error occurred.', 'error');
        })
        .finally(() => {
            hideLoadingSpinner();
        });
    });

    // Delete a product
    window.deleteProduct = function(id) {
        if (confirm('Are you sure you want to delete this product?')) {
            showLoadingSpinner();
            fetch(`/sales_dashboard_php/api/products.php?id=${id}`, { method: 'DELETE' })
                .then(response => response.json())
                .then(data => {
                    showToast(data.message);
                    fetchProducts();
                })
                .catch(error => {
                    showToast('An error occurred.', 'error');
                })
                .finally(() => {
                    hideLoadingSpinner();
                });
        }
    };

    // Initial fetch of products
    fetchProducts();

    // Export button event listener
    exportProductsBtn.addEventListener('click', function() {
        exportTableToCSV('products.csv', 'productTable');
    });

    // Filter event listeners
    searchProductInput.addEventListener('input', fetchProducts);
    filterProductStock.addEventListener('input', fetchProducts);

    fetchProducts();
});