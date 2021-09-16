# KNOWN APLICATION TEST

----------

# Getting started

## Installation

Please check the official laravel installation guide for server requirements before you start. [Official Documentation](https://laravel.com/docs/7.x/installation)

Clone the repository

    git clone https://github.com/rodrigoUriarte/testKnown.git

Switch to the repo folder

    cd testKnown

Install all the dependencies using composer

    composer install

Copy the example env file and make the required configuration changes in the .env file

    cp .env.example .env

Run the database migrations (**Set the database connection in .env before migrating**)

    php artisan migrate

**TL;DR command list**

    git clone https://github.com/rodrigoUriarte/testKnown.git
    cd testKnown
    composer install
    cp .env.example .env

**Make sure you set the correct database connection information before running the migrations**

    php artisan migrate

# Using

**To perform the synchronization run the following command**

    php artisan sync:db

This command will ask for the following filters:

    "fecha desde": with the following mask => (dd-mm-yyyy)
    "fecha hasta": with the following mask => (dd-mm-yyyy)
    "estado": must be one of the following => ('waiting-for-sellers-confirmation', 'payment-pending', 'payment-approved', 'ready-for-handling', 'handling', 'invoiced', 'canceled') or hit Enter to skip this filter.

If the program runs correctly, the following message will be displayed at the end.

    Proceso ejecutado de forma correcta.
