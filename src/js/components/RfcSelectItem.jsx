var React = require('react');
var Dispatcher = require('./../dispatcher');

var VoteResults = require('./VoteResults.jsx');

module.exports = React.createClass({
    getInitialState: function() {
        return {visible: true}
    },
    handleChange: function (ev) {
        var newVisibility = !this.state.visible;
        this.setState({visible: newVisibility});

        Dispatcher.emitView('RfcVisibilityToggled', {Id: this.props.rfc.id, Visible: newVisibility});
    },
    render: function() {
        return (
            <li>
                <div className="checkbox">
                    <label>
                        <input type="checkbox" checked={this.state.visible} onChange={this.handleChange} />
                        {this.props.rfc.title}
                    </label>
                    <VoteResults results={this.props.rfc.results} />
                </div>
            </li>
        );
    }
});
