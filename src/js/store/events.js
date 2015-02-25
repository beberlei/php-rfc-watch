var Dispatcher = require('./../dispatcher');
var EventEmitter = require('events').EventEmitter;
var assign = require('object-assign');

var events = [
    {type: "UserVoted", option: "No", user: "otherlei", vote: {title: "Scalar Type Hints", id: "1234"}, date: "2015-02-29 14:14"},
    {type: "UserVoted", option: "Yes", user: "beberlei", vote: {title: "Scalar Type Hints", id: "1234"}, date: "2015-02-28 14:16"},
    {type: "VoteOpened", user: "beberlei", vote: {title: "Scalar Type Hints", id: "1234"}, date: "2015-02-28 14:14"}
];

var visibleRfcs = ["1234", "1235"];

var in_array = function(needle, list) {
    var length = list.length;
    var i = 0;

    for (; i < length; i++) {
        if (list[i] === needle) {
            return true
        }
    }

    return false
};

var eventsStore = assign({}, EventEmitter.prototype, {
    getAllActive: function () {
        return events.filter(function (ev) {
            return in_array(ev.vote.id, visibleRfcs);
        });
    },
    getAll: function () {
        return events;
    }
});

eventsStore.dispatchToken = Dispatcher.register(function(payload) {
    var actions = {
        RfcVisibilityToggled: function (ev) {
            if (ev.Visible) {
                visibleRfcs.push(ev.Id);
            } else {
                var idx = visibleRfcs.indexOf(ev.Id);
                if (idx > -1) {
                    visibleRfcs.splice(idx, 1)
                }
            }

            eventsStore.emit('change');
        }
    };

    return actions[payload.type] && actions[payload.type](payload.action);
});

module.exports = eventsStore
