var React = require('react');

var RfcSelectItem = require('./components/RfcSelectItem');

module.exports = React.createClass({
    renderRfcItem: function(rfc, idx) {
        return (
            <RfcSelectItem rfc={rfc} key={idx} />
        );
    },
    render: function() {
        return (
            <div className="box">
                <div className="box-header">
                    <h3 className="box-title">RFCs</h3>
                </div>

                <div className="box-body">
                    <ul>
                        {this.props.rfcs.map(this.renderRfcItem)}
                    </ul>
                </div>
            </div>
        );
    }
});
