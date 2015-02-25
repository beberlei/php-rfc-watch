/** @jsx React.DOM */

'use strict'

var React = require('react')

$(document).ready(function() {
    var components = {
        RfcEventTimeline: require('./RfcEventTimeline'),
    }

    $("[data-component]").each(function() {
        var name = $(this).data('component');

        if (typeof(components[name]) == 'undefined') {
            throw "Unknown component: " + name
        }

        React.render(React.createElement(components[name]), $(this)[0])
    });
});

