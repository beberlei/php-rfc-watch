var React = require('react')

module.exports = React.createClass({
    time: function() {
        return '12:05';
    },
    render: function() {
        return (
            <li>
                <i className="fa fa-bullhorn bg-blue"></i>
                <div className="timeline-item">
                    <span className="time"><i className="fa fa-clock-o"></i> {this.time()}</span>

                    <h3 className="timeline-header">Vote opened</h3>
                    <div className="timeline-body">
                        <strong>{this.props.data.user}</strong> opened vote
                        on RFC <a>{this.props.data.vote.title}</a>
                    </div>
                </div>
            </li>
        );
    }
});
