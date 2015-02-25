var React = require('react');

module.exports = React.createClass({
    getInitialState: function() {
        return {visible: true}
    },
    handleChange: function (ev) {
        this.setState({visible: !this.state.visible});
    },
    render: function() {
        return (
            <li>
                <div className="checkbox">
                    <label>
                        <input type="checkbox" checked={this.state.visible} onChange={this.handleChange} />
                        {this.props.rfc.title}
                    </label>
                </div>
            </li>
        );
    }
});
