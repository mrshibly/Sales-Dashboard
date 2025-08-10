# Sales Dashboard

A modern, responsive sales dashboard application built with PHP, MySQL, and JavaScript.

## Features

*   **Dashboard:** View key metrics like total sales, total orders, and target progress.
*   **Real-Time Activity Feed:** See the latest activities in the system.
*   **Customer Management:** Add, edit, and delete customers.
*   **Product Management:** Add, edit, and delete products.
*   **Order Management:** Add, edit, and delete orders.
*   **Target Management:** Set and track sales targets.
*   **Authentication:** Secure user login and registration.

## Technologies Used

*   **Front-End:** HTML, CSS, JavaScript, Bootstrap
*   **Back-End:** PHP
*   **Database:** MySQL

## Setup

1.  **Clone the repository:**

    ```bash
    git clone https://github.com/your-username/sales-dashboard-php.git
    ```

2.  **Database Setup:**

    *   Create a new MySQL database.
    *   Import the `sql/database_schema.sql` file to set up the database tables.
    *   Update the database credentials in `config/database.php`.

3.  **Web Server:**

    *   Point your web server (e.g., Apache, Nginx) to the project directory.

4.  **Run the application:**

    *   Open the application in your web browser.

## API Endpoints

The application uses a RESTful API to handle data operations. The API endpoints are located in the `api/` directory.

*   `GET /api/customers.php`: Get all customers.
*   `POST /api/customers.php`: Create a new customer.
*   `PUT /api/customers.php`: Update an existing customer.
*   `DELETE /api/customers.php`: Delete a customer.
*   `GET /api/products.php`: Get all products.
*   `POST /api/products.php`: Create a new product.
*   `PUT /api/products.php`: Update an existing product.
*   `DELETE /api/products.php`: Delete a product.
*   `GET /api/orders.php`: Get all orders.
*   `POST /api/orders.php`: Create a new order.
*   `PUT /api/orders.php`: Update an existing order.
*   `DELETE /api/orders.php`: Delete an order.
*   `GET /api/targets.php`: Get all targets.
*   `POST /api/targets.php`: Create a new target.
*   `PUT /api/targets.php`: Update an existing target.
*   `DELETE /api/targets.php`: Delete a target.
*   `GET /api/dashboard-data.php`: Get dashboard metrics.
*   `GET /api/activity-feed.php`: Get real-time activity feed.

## Contributing

Contributions are welcome! Please feel free to submit a pull request.
