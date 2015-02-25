var React = require('react')

module.exports = React.createClass({
    time: function() {
        return '12:05';
    },
    render: function() {
        return (
            <li>
                <i className="fa fa-volume-up bg-green"></i>
                <div className="timeline-item">
                    <span className="time"><i className="fa fa-clock-o"></i> {this.time()}</span>

                    <h3 className="timeline-header">User voted</h3>
                    <div className="timeline-body">
                        <strong>{this.props.data.user}</strong> voted <strong>{this.props.data.option}</strong> on RFC <a>{this.props.data.vote.title}</a>
                    </div>
                </div>
            </li>
        );
    }
});
