var React = require('react')

var Date = require('./../components/Date');

module.exports = React.createClass({
    render: function() {
        var opt = this.props.data.option.toLowerCase();

        var vote = (opt.indexOf('yes') == 0 || opt.indexOf('allow') == 0)
            ? 'fa-thumbs-up'
            : (opt.indexOf('no') == 0 ? 'fa-thumbs-down' : 'fa-volume-up');
        var bg = (opt.indexOf('yes') == 0 || opt.indexOf('allow') == 0)
            ? 'bg-green'
            : (opt.indexOf('no') == 0 ? 'bg-red' : 'bg-grey');

        var className = 'fa ' + vote + ' ' + bg;

        var peopleUrl = "http://people.php.net/" + this.props.data.user;

        return (
            <li>
                <i className={className}></i>
                <div className="timeline-item">
                    <Date date={this.props.data.date} />

                    <h3 className="timeline-header">User voted</h3>
                    <div className="timeline-body">
                        <strong><a href={peopleUrl}>{this.props.data.user}</a></strong> voted <strong>{this.props.data.option}</strong> on RFC <a href={this.props.data.vote.url}>{this.props.data.vote.title}</a>
                    </div>
                </div>
            </li>
        );
    }
});
