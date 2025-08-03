# Olabie Math Solver

A web-based mathematical expression solver built with PHP.

## Description

Olabie Math Solver is a powerful and intuitive web application that allows users to solve a variety of mathematical problems, from simple arithmetic to complex calculus. The application is built with a custom PHP MVC framework and features a robust math engine that can parse and solve a wide range of mathematical expressions.

## Features

*   **Arithmetic:** Solve basic arithmetic operations such as addition, subtraction, multiplication, and division.
*   **Expression Simplification:** Simplify complex mathematical expressions.
*   **Linear Equations:** Solve linear equations with one or more variables.
*   **Quadratic Equations:** Solve quadratic equations of the form ax^2 + bx + c = 0.
*   **Integral Calculus:** Solve definite and indefinite integrals.
*   **Complex Numbers:** Perform calculations with complex numbers.
*   **Polynomials:** Perform operations with polynomials.

## Installation

1.  Clone the repository:
    ```bash
    git clone https://github.com/user/olabieframework.git
    ```
2.  Install PHP dependencies:
    ```bash
    composer install
    ```
3.  Install front-end dependencies:
    ```bash
    npm install
    ```
4.  Run the development server:
    ```bash
    npm run dev
    ```
5.  Start the PHP development server:
    ```bash
    php -S localhost:8000 -t public
    ```

## Usage

1.  Open your web browser and navigate to `http://localhost:8000`.
2.  Enter a mathematical expression in the input field.
3.  Click the "Solve" button to see the solution.

## Technologies Used

*   **Back-end:** PHP
*   **Front-end:** JavaScript, Tailwind CSS, Vite.js
*   **Web Server:** Nginx
*   **Dependency Management:** Composer, npm

## Nginx Configuration

A sample Nginx configuration file is available in the `nginx` directory. You can use this as a starting point for your own configuration.
