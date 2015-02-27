var React = require('react');

module.exports = React.createClass({
    renderVote: function (vote, idx) {
        var share = Math.round(vote.share * 100, 2);
        return <li>{vote.option}: {vote.votes} ({share}%)</li>;
    },
    renderVoteProgress: function (vote, idx) {
        var style = {
            width: Math.round(vote.share * 100, 2) + "%",
            height: "4px",
            verticalAlign: "top",
            display: "inline-block",
            backgroundColor: "hsl(" + (490 - idx * 70) % 360 + ", 80%, 50%)"
        };
        return <div class="progress-bar" style={style}></div>
    },
    computeTotalVotesCasted: function () {
        var total = 0;
        for (var i = 0; i < this.props.results.length; i++) {
            total += this.props.results[i].votes;
        }
        return total;
    },
    render: function () {
        var style = {
            height: "4px",
            lineHeight: "4px",
            borderRadius: "2px",
            overflow: "hidden",
            whiteSpace: "nowrap",
            margin: "5px 0"
        };

        return (
            <div>
                <div style={style}>
                    {this.props.results.map(this.renderVoteProgress)}
                </div>

                <ul>
                    <li>Votes cast: {this.computeTotalVotesCasted()}</li>
                    {this.props.results.map(this.renderVote)}
                </ul>
            </div>
        );
    }
});
