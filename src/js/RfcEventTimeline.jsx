var React = require('react')

var UserVotedEvent = require('./Event/UserVoted');
var VoteOpenedEvent = require('./Event/VoteOpened');
var VoteClosedEvent = require('./Event/VoteClosed');

module.exports = React.createClass({
    propTypes: {
        events: React.PropTypes.array
    },
    renderEventItem: function (data, idx) {
        switch (data.type) {
            case 'UserVoted':
                return <UserVotedEvent data={data} key={idx} />;
            case 'VoteOpened':
                return <VoteOpenedEvent data={data} key={idx} />;
            case 'VoteClosed':
                return <VoteClosedEvent data={data} key={idx} />;
        }
    },
    render: function() {
        return (
            <ul className="timeline">
                {this.props.events.map(this.renderEventItem)}
                <li>
                    <i className="fa fa-clock-o bg-gray"></i>
                </li>
            </ul>
        );
    }
});
