# Backend Setup Guide

This guide will help you get the backend up and running in a few simple steps.

## Requirements

-   PHP >= 8.1
-   Composer
-   A compatible database (e.g., MySQL)
-   Laravel CLI (optional but recommended)

## Installation Steps

1. **Install dependencies**

    Run the following command to install all required PHP packages:

    ```bash
    composer install
    ```

2. **Set up environment configuration**

    Copy the example environment file and fill in your own database and service credentials:

    ```bash
    cp .env.example .env
    ```

    Then, edit the `.env` file with the necessary information (e.g., database credentials, app key, etc.).

3. **Initialize the project**

    Run the custom setup script (this may include migrations, seeds, and other bootstrapping):

    ```bash
    composer run wakeup
    ```

4. **Done!**

    The backend is now configured and ready to use.
