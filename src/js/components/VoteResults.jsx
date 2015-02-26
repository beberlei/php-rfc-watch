var React = require('react');

module.exports = React.createClass({
    renderVote: function(vote, idx) {
        var share = Math.round(vote.share * 100, 2);
        return <li>{vote.option}: {vote.votes} ({share}%)</li>;
    },
    computeTotalVotesCasted: function() {
        var total = 0;
        for (var i = 0; i < this.props.results.length; i++) {
            total += this.props.results[i].votes;
        }
        return total;
    },
    render: function() {
        return (
            <ul>
                <li>Votes casted: {this.computeTotalVotesCasted()}</li>
                {this.props.results.map(this.renderVote)}
            </ul>
        );
    }
});
