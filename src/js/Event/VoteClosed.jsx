var React = require('react')

var Date = require('./../components/Date');

module.exports = React.createClass({
    render: function() {
        return (
            <li>
                <i className="fa fa-close bg-red"></i>
                <div className="timeline-item">
                    <Date date={this.props.data.date} />

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
