var React = require('react');

var moment = require('./../vendor/moment.timezone');

module.exports = React.createClass({
    timeDisplay: function() {
        var time = moment(this.props.date).local().fromNow();
        return time;
    },
    timeTitle: function() {
        var time = moment(this.props.date).local().format('YYYY-MM-DD HH:mm:ss Z');
        return time;
    },
    render: function() {
        return (
            <span className="time">
                <i className="fa fa-clock-o"></i>
                <time dateTime={this.props.date} title={this.timeTitle()}>{this.timeDisplay()}</time>
            </span>
        );
    }
});
