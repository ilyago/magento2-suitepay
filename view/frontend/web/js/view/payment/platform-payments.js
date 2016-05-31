/**
 * Suitepay_Platform Magento JS component
 *
 * @category    Suitepay
 * @package     Suitepay_Platform
 * @author      Ilya Gokadze
 * @copyright   Suitepay (http://suitepay.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'suitepay_platform',
                component: 'Suitepay_Platform/js/view/payment/method-renderer/platform-method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);