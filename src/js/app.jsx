/** @jsx React.DOM */

'use strict'

var React = require('react')
var RfcEventTimeline = require('./RfcEventTimeline');
var RfcList = require('./RfcList');

var events = [
    {type: "UserVoted", option: "No", user: "otherlei", vote: {title: "Scalar Type Hints", id: "1234"}, date: "2015-02-29 14:14"},
    {type: "UserVoted", option: "Yes", user: "beberlei", vote: {title: "Scalar Type Hints", id: "1234"}, date: "2015-02-28 14:16"},
    {type: "VoteOpened", user: "beberlei", vote: {title: "Scalar Type Hints", id: "1234"}, date: "2015-02-28 14:14"}
];

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

React.render(<App events={events} rfcs={rfcs} />, document.getElementById('content'));
