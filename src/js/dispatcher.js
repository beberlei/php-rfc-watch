var Dispatcher = require('flux').Dispatcher;

var assign = require('object-assign');

var appDispatcher = assign(new Dispatcher(), {
    emitServer: function (type, action) {
        this.dispatch({
            source: 'server',
            type: type,
            action: action
        })
    },
    emitView: function (type, action) {
        this.dispatch({
            source: 'view',
            type: type,
            action: action
        });
    }
});

module.exports = appDispatcher;
