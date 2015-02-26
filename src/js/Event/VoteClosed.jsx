var React = require('react')

module.exports = React.createClass({
    time: function() {
        var d = new Date(this.props.data.date);
        return d.toLocaleString();
    },
    render: function() {
        return (
            <li>
                <i className="fa fa-close bg-red"></i>
                <div className="timeline-item">
                    <span className="time"><i className="fa fa-clock-o"></i> {this.time()}</span>
                    <h3 className="timeline-header">Vote closed</h3>
                    <div className="timeline-body">
                        {this.props.data.user} closed the vote
                        on RFC <a href={this.props.data.vote.url}>{this.props.data.vote.title}</a>
                    </div>
                </div>
            </li>
        );
    }
});
