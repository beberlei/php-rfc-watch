var Dispatcher = require('./../dispatcher');
var EventEmitter = require('events').EventEmitter;
var assign = require('object-assign');

var events = [];
var rfcs = [];

var visibleRfcs = [];

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
    fetchSuccess: function (data) {
        events = data.events;
        rfcs = data.rfcs;

        for (var idx in rfcs) {
            var rfc = rfcs[idx];
            visibleRfcs.push(rfc.id);
        }

        this.emit('change');
    },
    fetch: function() {
        $.ajax({
            url: "/data.json",
            success: this.fetchSuccess.bind(this),
        });
    },
    getRfcs: function() {
        return rfcs;
    },
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
