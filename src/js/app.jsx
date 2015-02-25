/** @jsx React.DOM */

'use strict'

var React = require('react')
var RfcEventTimeline = require('./RfcEventTimeline');

var events = [
    {type: "UserVoted", option: "No", user: "otherlei", vote: {title: "Scalar Type Hints", id: "1234"}, date: "2015-02-29 14:14"},
    {type: "UserVoted", option: "Yes", user: "beberlei", vote: {title: "Scalar Type Hints", id: "1234"}, date: "2015-02-28 14:16"},
    {type: "VoteOpened", user: "beberlei", vote: {title: "Scalar Type Hints", id: "1234"}, date: "2015-02-28 14:14"}
];

React.render(
  <RfcEventTimeline events={events} />,
  document.getElementById('content')
);
