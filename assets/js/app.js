'use strict'

import React from 'react'
import ReactDOM from 'react-dom'
import _ from 'underscore'

class VoteResults extends React.Component {
    renderVote (vote, idx) {
        var share = Math.round(vote.share * 100, 2);
        return <div className="col-lg" key={vote.option}>{vote.option}: {vote.votes} ({share}%)</div>;
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
                <div className="mb-2">
                    {this.props.question}
                </div>

                <div className="vote-results mb-2">
                    {this.renderVoteProgress()}
                </div>

                <div className="row">
                    <div className="col-lg">Votes cast: {this.computeTotalVotesCasted()}</div>
                    {this.props.results.map(this.renderVote)}
                </div>

                {this.props.last ? null : <hr />}
            </div>
        );
    }
}

class RfcVoteItem extends React.Component {
    render() {
        const voteCount = this.props.rfc.questions.length;

        return <div className="col-md-6">
            <div className="card">
                <div className="card-header">
                    {this.props.rfc.status == 'open' ?
                        <span className="badge badge-primary mr-1">Active</span>
                        : null }
                    <a href={this.props.rfc.url} target="_blank">{this.props.rfc.title}</a>
                </div>
                <div className="card-body">
                    {this.props.rfc.questions.map((item, idx) => {
                        return <VoteResults question={item.question} results={item.results} share={item.share} last={voteCount == idx+1} />
                    })}
                </div>
            </div>
        </div>
    }
}

class RfcList extends React.Component {
    render () {
        return <div className="card-columns row">
            {this.props.rfcs.map(item => { return <RfcVoteItem key={item.id} rfc={item} /> })}
        </div>
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

    fetchData () {
        fetch('/data.json')
            .then(response => response.json())
            .then(data => this.setState({ data: data, loading: false }));
    }

    componentDidMount() {
        this.fetchData()
        setInterval(() => { this.fetchData() }, 60000);
    }

    render () {
        if (this.state.loading) {
            return <div>Loading...</div>
        }

        return <div>
            <RfcList rfcs={this.state.data.active} />
            <RfcList rfcs={this.state.data.other} />
        </div>
    }
}

ReactDOM.render(<RfcWatch />, document.getElementById("app"))
