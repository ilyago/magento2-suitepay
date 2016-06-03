magento2-Suitepay_Platform
======================

Suitepay payment gateway Magento2 extension

You can read the full documentation [here](https://support.suitepay.com/).

Other notes on extension: https://github.com/ilyago/magento2-suitepay/wiki

Install
=======

1. Go to Magento2 root folder

2. Enter following commands to install module:

    ```bash
    composer config repositories.suitepayplatform git https://github.com/ilyago/magento2-suitepay.git
    composer require suitepay/platform:dev-master
    ```
   Wait while dependencies are updated.

3. Enter following commands to enable module:

    ```bash
    php bin/magento module:enable Suitepay_Platform --clear-static-content
    php bin/magento setup:upgrade
    ```
4. Enable and configure Suitepay in Magento Admin under Stores/Configuration/Sales/Payment Methods/Suitepay


