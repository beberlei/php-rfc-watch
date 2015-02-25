/** @jsx React.DOM */

'use strict'

var React = require('react')
var RfcEventTimeline = require('./RfcEventTimeline');
var RfcList = require('./RfcList');
var EventsStore = require('./store/events.js');

var rfcs = [
    {title: "Scalar Type Hints", id: "1234", showEvents: true},
    {title: "Coercive Type Hints", id: "1235", showEvents: true}
];

var App = React.createClass({
    render: function() {
        return (
            <div className="row">
                <div className="col-md-8">
                    <RfcEventTimeline events={this.props.events} />
                </div>
                <div className="col-md-4">
                    <RfcList rfcs={this.props.rfcs} />
                </div>
            </div>
        )
    }
});

EventsStore.on('change', function() {
    React.render(<App events={EventsStore.getAllActive()} rfcs={rfcs} />, document.getElementById('content'));
});

React.render(<App events={EventsStore.getAllActive()} rfcs={rfcs} />, document.getElementById('content'));
