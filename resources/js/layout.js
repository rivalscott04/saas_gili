/*
Template Name: Velzon - Admin & Dashboard Template
Author: Themesbrand
Version: 4.3.0
Website: https://Themesbrand.com/
Contact: Themesbrand@gmail.com
File: Layout Js File
*/

(function () {

    'use strict';

    if (localStorage.getItem('defaultAttribute')) {

        var attributesValue = document.documentElement.attributes;
        var CurrentLayoutAttributes = {};
        Object.entries(attributesValue).forEach(function(key) {
            if (key[1] && key[1].nodeName && key[1].nodeName != "undefined") {
                var nodeKey = key[1].nodeName;
                CurrentLayoutAttributes[nodeKey] = key[1].nodeValue;
            }
          });
        if(localStorage.getItem('defaultAttribute') !== JSON.stringify(CurrentLayoutAttributes)) {
            localStorage.clear();
            window.location.reload();
        } else {
            var isLayoutAttributes = {};
            isLayoutAttributes['data-layout'] = localStorage.getItem('data-layout');
            isLayoutAttributes['data-sidebar-size'] = localStorage.getItem('data-sidebar-size');
            isLayoutAttributes['data-bs-theme'] = localStorage.getItem('data-bs-theme');
            isLayoutAttributes['data-layout-width'] = localStorage.getItem('data-layout-width');
            isLayoutAttributes['data-sidebar'] = localStorage.getItem('data-sidebar');
            isLayoutAttributes['data-sidebar-image'] = localStorage.getItem('data-sidebar-image');
            isLayoutAttributes['data-layout-direction'] = localStorage.getItem('data-layout-direction');
            isLayoutAttributes['data-layout-position'] = localStorage.getItem('data-layout-position');
            isLayoutAttributes['data-layout-style'] = localStorage.getItem('data-layout-style');
            isLayoutAttributes['data-topbar'] = localStorage.getItem('data-topbar');
            isLayoutAttributes['data-preloader'] = localStorage.getItem('data-preloader');
            isLayoutAttributes['data-body-image'] = localStorage.getItem('data-body-image');
            isLayoutAttributes['data-theme'] = localStorage.getItem('data-theme');
            isLayoutAttributes['data-theme-colors'] = localStorage.getItem('data-theme-colors');

            Object.keys(isLayoutAttributes).forEach(function (x) {
                if (isLayoutAttributes[x] && isLayoutAttributes[x]) {
                    document.documentElement.setAttribute(x, isLayoutAttributes[x]);
                }
            });
        }
    }

})();