var React = require('react')

module.exports = React.createClass({
    render: function() {
        return (
            <li>
                <i className="fa fa-meh-o bg-orange"></i>
                <div className="timeline-item">
                    <Date date={this.props.data.date} />

                    <h3 className="timeline-header">User removed vote</h3>
                    <div className="timeline-body">
                        <strong>{this.props.data.user}</strong> removed his <strong>{this.props.data.option}</strong> vote on RFC <a href={this.props.data.vote.url}>{this.props.data.vote.title}</a>
                    </div>
                </div>
            </li>
        );
    }
});

