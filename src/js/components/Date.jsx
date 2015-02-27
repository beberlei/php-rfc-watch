var React = require('react');

var moment = require('./../vendor/moment.timezone');

module.exports = React.createClass({
    time: function() {
        var time = moment(this.props.date).local().format('YY-MM-DD HH:mm');
        return time;
    },
    render: function() {
        return (
            <span className="time">
                <i className="fa fa-clock-o"></i> {this.time()}
            </span>
        );
    }
});
