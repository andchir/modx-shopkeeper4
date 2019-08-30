/**
 * ShoppingCart
 * @version 1.0.0
 * @author Andchir<andchir@gmail.com>
 */

(function (factory) {

    if ( typeof define === 'function' && define.amd ) {

        // AMD. Register as an anonymous module.
        define([], factory);

    } else if ( typeof exports === 'object' ) {

        // Node/CommonJS
        module.exports = factory();

    } else {

        // Browser globals
        window.ShoppingCart = factory();
    }

}(function( ){

    'use strict';

    function ShoppingCart(options) {

        var self = this, isInitialized = false, container = null, callbacks = [];

        var mainOptions = {
            baseUrl: '/',
            connectorUrl: 'assets/components/shopping_cart/connector.php',
            snippetPropertySetName: '',
            selector: '#shoppingCartContainer',
            useNumberFormat: true,
            selectorPriceTotal: '.shopping-cart-price-total',
            selectorCountTotal: '.shopping-cart-count-total',
            selectorCountUniqueTotal: '.shopping-cart-count-unique-total',
            selectorDeclension: '.shopping-cart-declension',
            productFormSelector: ''
        };

        /**
         * Initialization
         */
        this.init = function() {
            options = options || {};
            if (Object.keys(options).length > 0) {
                this.extend(mainOptions, options);
            }
            container = document.querySelector(mainOptions.selector);
            if (!container) {
                if (console && console.log) {
                    console.log('[ShoppingCart] Container selector not found.');
                }
                return;
            }
            this.submitFormInit();
            this.productSubmitFormInit();
            isInitialized = true;
        };

        /**
         * Submit shopping cart form initialize
         */
        this.submitFormInit = function() {
            if (!container) {
                return;
            }
            var formEl = container.querySelector('form'),
                actionName = '',
                actionValue = '';
            if (!formEl) {
                if (console && console.log) {
                    console.log('[ShoppingCart] Shopping Cart form element not found.');
                }
                return;
            }
            formEl.addEventListener('submit', function(event) {
                event.preventDefault();

                var formData = new FormData(formEl);
                formData.append(actionName, actionValue);
                formData.append('propertySetName', mainOptions.snippetPropertySetName);

                self.formDataSend(formData);
            });
            formEl.querySelectorAll('button[type="submit"]').forEach(function(buttonEl) {
                buttonEl.addEventListener('click', function(event) {
                    actionName = buttonEl.getAttribute('name');
                    actionValue = buttonEl.value;
                });
            });
        };

        /**
         * Product add to shopping cart initialize
         */
        this.productSubmitFormInit = function() {
            if (!mainOptions.productFormSelector) {
                return;
            }
            document.querySelectorAll(mainOptions.productFormSelector).forEach(function(formEl) {
                formEl.addEventListener('submit', function(event) {
                    event.preventDefault();

                    var formData = new FormData(formEl);
                    formData.append('action', 'add_to_cart');
                    formData.append('propertySetName', mainOptions.snippetPropertySetName);

                    self.formDataSend(formData);
                });
            });
        };

        /**
         * Replace content of container element
         * @param html
         */
        this.containerUpdate = function(html) {
            container.outerHTML = html;
            container = document.querySelector(mainOptions.selector);
            this.submitFormInit();
        };

        /**
         * Update the contents of elements with data of the shopping cart
         * @param data
         */
        this.updateElementsBySelectors = function(data) {
            if (!data || !data.price_total || !data.items_total) {
                return;
            }
            var elementsTotalPrice = document.querySelectorAll(mainOptions.selectorPriceTotal),
                elementsCountTotal = document.querySelectorAll(mainOptions.selectorCountTotal),
                elementsCountUniqueTotal = document.querySelectorAll(mainOptions.selectorCountUniqueTotal),
                elementsDeclension = document.querySelectorAll(mainOptions.selectorDeclension);

            elementsTotalPrice.forEach(function (el) {
                el.textContent = mainOptions.useNumberFormat ? self.numFormat(data.price_total) : data.price_total;
            });
            elementsCountTotal.forEach(function (el) {
                el.textContent = data.items_total;
            });
            elementsCountUniqueTotal.forEach(function (el) {
                el.textContent = data.items_unique_total;
            });
        };

        /**
         * Send FormData to connector
         * @param formData
         */
        this.formDataSend = function(formData) {
            this.showLoading(true);
            self.ajax(mainOptions.baseUrl + mainOptions.connectorUrl, formData, function(response) {
                if (!response.success) {
                    self.showLoading(false);
                    return;
                }
                self.updateElementsBySelectors(response);
                self.containerUpdate(response.html);
            }, function(response) {
                self.showLoading(false);
            }, 'POST');
        };

        /**
         * Ajax request
         * @param url
         * @param data
         * @param successFn
         * @param failFn
         * @param method
         */
        this.ajax = function(url, data, successFn, failFn, method) {
            method = method || 'GET';
            var request = new XMLHttpRequest();
            request.open(method, url, true);

            request.onload = function() {
                var result = ['{','['].indexOf(request.responseText.substr(0,1)) > -1
                    ? JSON.parse(request.responseText)
                    : {};
                if (request.status >= 200 && request.status < 400) {
                    if (typeof successFn === 'function') {
                        successFn(result);
                    }
                } else {
                    if (typeof failFn === 'function') {
                        failFn(result);
                    }
                }
            };

            request.onerror = function() {
                if (typeof failFn === 'function') {
                    failFn(request);
                }
            };

            request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            if (!(data instanceof FormData)) {
                request.setRequestHeader('Content-type', 'application/json; charset=utf-8');
            }
            if (method === 'POST') {
                request.send(data);
            } else {
                request.send();
            }
        };

        /**
         * Show loading animation
         * @param show
         */
        this.showLoading = function(show) {
            if (!container) {
                return;
            }
            show = show || false;
            if (show) {
                container.classList.add('shopping-cart-loading');
            } else {
                container.classList.remove('shopping-cart-loading');
            }
        };

        /**
         * Call function on document ready
         * @param cb
         */
        this.onReady = function(cb) {
            if (document.readyState !== 'loading') {
                cb();
            } else {
                document.addEventListener('DOMContentLoaded', cb);
            }
        };

        /**
         * Extend object
         * @param out
         * @returns {*|{}}
         */
        this.extend = function(out) {
            out = out || {};
            for (var i = 1; i < arguments.length; i++) {
                if (!arguments[i])
                    continue;
                for (var key in arguments[i]) {
                    if (arguments[i].hasOwnProperty(key))
                        out[key] = arguments[i][key];
                }
            }
            return out;
        };

        /**
         * Number format for price
         * @param n
         */
        this.numFormat = function(n){
            return this.number_format(n, (Math.floor(n)===n ? 0 : 2), '.', ' ');
        };

        /**
         * Number format
         * @param number
         * @param decimals
         * @param dec_point
         * @param thousands_sep
         */
        this.number_format = function(number, decimals, dec_point, thousands_sep) {
            number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
            var n = !isFinite(+number) ? 0 : +number,
                prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
                sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
                dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
                s = '',
                toFixedFix = function (n, prec) {
                    var k = Math.pow(10, prec);
                    return '' + Math.round(n * k) / k;
                };
            // Fix for IE parseFloat(0.55).toFixed(0) = 0;
            s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
            if (s[0].length > 3) {
                s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
            }
            if ((s[1] || '').length < prec) {
                s[1] = s[1] || '';
                s[1] += new Array(prec - s[1].length + 1).join('0');
            }
            return s.join(dec);
        };

        this.init();
    }

    return ShoppingCart;

}));
