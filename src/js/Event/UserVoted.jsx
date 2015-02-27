var React = require('react')

var Date = require('./../components/Date');

module.exports = React.createClass({
    render: function() {
        return (
            <li>
                <i className="fa fa-volume-up bg-green"></i>
                <div className="timeline-item">
                    <Date date={this.props.data.date} />

                    <h3 className="timeline-header">User voted</h3>
                    <div className="timeline-body">
                        <strong>{this.props.data.user}</strong> voted <strong>{this.props.data.option}</strong> on RFC <a href={this.props.data.vote.url}>{this.props.data.vote.title}</a>
                    </div>
                </div>
            </li>
        );
    }
});
