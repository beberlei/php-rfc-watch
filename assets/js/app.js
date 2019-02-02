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

function intersperse(arr, sep) {
    if (arr.length === 0) {
        return [];
    }

    return arr.slice(1).reduce(function(xs, x, i) {
        return xs.concat([sep, x]);
    }, [arr[0]]);
}

class RfcDiscussions extends React.Component {
    render() {
        if (this.props.discussions.length == 0) {
            return null;
        }

        var idx = 0;

        return <p>
                <strong>Discussions:</strong>
                {intersperse(this.props.discussions.map(x => {
                    var label;
                    if (x.indexOf('externals.io')) {
                        label = 'Mailinglist';
                    } else if (x.indexOf('reddit')) {
                        label = 'Reddit';
                    }
                    idx++;
                    return <a href={x} target="_blank">#{idx} {label}</a>
                }), ", ")}
            </p>
    }
}

class RfcVoteItem extends React.Component {
    render() {
        const voteCount = this.props.rfc.questions.length;

        return <div className="card">
            <div className="card-header">
                {this.props.rfc.status == 'open' ?
                    <span className="badge badge-primary mr-1">Active</span>
                    : null }
                <a href={this.props.rfc.url} target="_blank">{this.props.rfc.title}</a>
            </div>
            <div className="card-body">
                {this.props.rfc.targetPhpVersion.length > 0 && <span><strong>Target PHP Version:</strong> {this.props.rfc.targetPhpVersion}</span>}

                <RfcDiscussions discussions={this.props.rfc.discussions} />

                {this.props.rfc.questions.map((item, idx) => {
                    return <VoteResults key={idx} question={item.question} results={item.results} share={item.share} last={voteCount == idx+1} />
                })}
            </div>
        </div>
    }
}

class RfcList extends React.Component {
    render () {
        if (this.props.rfcs.length == 0) {
            return null;
        }

        return <div>
            <h2>{this.props.title}</h2>
            <div className="card-columns">
                {this.props.rfcs.map(item => { return <RfcVoteItem key={item.id} rfc={item} /> })}
            </div>
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
            <RfcList rfcs={this.state.data.active} title="Currently Active RFCs"/>
            {Object.keys(this.state.data.others).map( (version) => {
                return <RfcList key={version} rfcs={this.state.data.others[version]} title={"Completed RFCs for " + version} />
            })}
            <RfcList rfcs={this.state.data.rejected} title="Rejected RFCs"/>
        </div>
    }
}

ReactDOM.render(<RfcWatch />, document.getElementById("app"))
