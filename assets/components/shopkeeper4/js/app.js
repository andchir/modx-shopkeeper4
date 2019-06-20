
const $ = require('jquery');
const Shopkeeper = require('../js/shopkeeper.js');
const DotsMenu = require('../js/dots-menu.js');
const wNumb = require('../node_modules/wnumb/wNumb.js');
const noUiSlider = require('../node_modules/nouislider/distribute/nouislider.min.js');
require('bootstrap');

global.$ = global.jQuery = $;
global.wNumb = wNumb;
global.noUiSlider = noUiSlider;
global.DotsMenu = DotsMenu;
global.shk = new Shopkeeper();
global.dotsMenu = new DotsMenu({
    dotsMenuButtonPosition: 'left'
});

$(document).ready(function() {
    $('[data-toggle="tooltip"],.js-tooltip').tooltip({
        trigger: 'hover',
        placement: 'bottom'
    });
});
