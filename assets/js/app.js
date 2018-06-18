'use strict'

import React from 'react'
import ReactDOM from 'react-dom'
import _ from 'underscore'

class VoteResults extends React.Component {
    renderVote (vote, idx) {
        var share = Math.round(vote.share * 100, 2);
        return <li>{vote.option}: {vote.votes} ({share}%)</li>;
    }

    renderVoteProgress () {
        var positive = this.props.share + "%";
        var negative = (100 - this.props.share) + "%";
        return <div className="progress">
            <div className="progress-bar bg-success" style={{width: positive}}>{positive}</div>
            <div className="progress-bar bg-danger" style={{width: negative}}>{negative}</div>
        </div>
    }

    computeTotalVotesCasted () {
        var total = 0;
        for (var i = 0; i < this.props.results.length; i++) {
            total += this.props.results[i].votes;
        }
        return total;
    }

    render () {
        return (
            <div>
                <div className="vote-results">
                    {this.renderVoteProgress()}
                </div>

                <ul>
                    <li>Votes cast: {this.computeTotalVotesCasted()}</li>
                    {this.props.results.map(this.renderVote)}
                </ul>
            </div>
        );
    }
}

class RfcVoteItem extends React.Component {
    render() {
        return <div className="card">
            <div className="card-header">
                <a href={this.props.rfc.url} target="_blank">{this.props.rfc.title}</a>
            </div>
            <div className="card-body">
                <VoteResults results={this.props.rfc.results} share={this.props.rfc.share} />
            </div>
        </div>
    }
}

class RfcList extends React.Component {
    render () {
        return this.props.rfcs.map(item => { return <RfcVoteItem key={item.id} rfc={item} /> })
    }
}

class RfcWatch extends React.Component {
    constructor(props) {
        super(props);

        this.state = {
            loading: true,
            data: [],
        };
    }

    componentDidMount() {
        fetch('/data.json')
            .then(response => response.json())
            .then(data => this.setState({ data: data, loading: false }));
    }

    render () {
        if (this.state.loading) {
            return <div>Loading...</div>
        }

        return <RfcList rfcs={this.state.data.rfcs} />
    }
}

ReactDOM.render(<RfcWatch />, document.getElementById("app"))
