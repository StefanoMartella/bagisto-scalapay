<p align="center"><img src="src/Resources/assets/images/scalapay.svg" width="288"></p>

# Scalapay Payment Gateway
[![License](https://poser.pugx.org/StefanoMartella/bagisto-scalapay/license)](https://github.com/StefanoMartella/bagisto-scalapay/master/LICENSE)
[![Total Downloads](https://poser.pugx.org/StefanoMartella/bagisto-scalapay/d/total)](StefanoMartella/bagisto-scalapay)

## 1. Introduction:

Install this package now to receive secure payments in your online store. Scalapay offers an easy and secure payment gateway

## 2. Requirements:

* **PHP**: 8.1 or higher.
* **Bagisto**: v2.*
* **Composer**: 1.6.5 or higher.

## 3. Installation:

- Run the following command
```
composer require stefanomartella/bagisto-scalapay
```

- Run these commands below to complete the setup
```
composer dump-autoload
```

- Run these commands below to complete the setup
```
php artisan optimize
```

- Go to the Bagisto admin panel, find the Scalapay payment gateway, enter your API key and start receiving payments.
```
http://localhost:8000/admin/configuration/sales/payment_methods
```

- To use the demo API key, paste the key into the Scalapay Client Secret section.
```
qhtfs87hjnc12kkos
```

> That's it, now just execute the project on your specified domain.

## How to contribute
Scalapay Payment Gateway is always open for direct contributions. Contributions can be in the form of design suggestions, documentation improvements, new component suggestions, code improvements, adding new features or fixing problems. For more information please check our [Contribution Guideline document.](https://github.com/StefanoMartella/bagisto-scalapay/blob/master/CONTRIBUTING.md)