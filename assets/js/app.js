'use strict'

import React from 'react'
import ReactDOM from 'react-dom'
import _ from 'underscore'

class VoteResults extends React.Component {
    renderVote (vote, idx) {
        var bgs = ['bg-green-400', 'bg-red-400', 'bg-blue-400', 'bg-teal-400'];

        return <div className="col-lg" key={vote.option}>
            <div className={bgs[idx] + " rounded-sm mr-2 h-2 w-2 inline-block"}></div>
            <small>{vote.option}: {vote.votes}</small>
        </div>;
    }

    renderVoteProgress () {
        var bgs = ['bg-green-400', 'bg-red-400', 'bg-blue-400', 'bg-teal-400'];

        return <div className="w-full">
            <div className="shadow w-full bg-grey-light rounded-sm flex items-stretch">
                {this.props.vote.results.map(function (result, idx) {
                    var share = Math.round(result.share * 100, 2);

                    if (share === 0) {
                        return null;
                    }

                    return <div key={result.option} className={bgs[idx] + " text-xs inline-block leading-none py-1 text-center text-white flex-none"} style={{width: share + "%"}}>{share} %</div> 
                })}
            </div>
        </div>
    }

    computeTotalVotesCasted () {
        var total = 0;
        for (var i = 0; i < this.props.vote.results.length; i++) {
            total += this.props.vote.results[i].votes;
        }
        return total;
    }

    render () {
        return (
            <div>
                <div className="mb-2">
                    {this.props.vote.question}
                </div>

                <div className="mb-2">
                    {this.renderVoteProgress()}
                </div>

                <div className="row">
                    {this.props.vote.results.map(this.renderVote)}
                </div>
                <div className="row">
                    <div className="col-lg meta ml-3">Total number of votes cast: {this.computeTotalVotesCasted()}</div>
                </div>

                {this.props.last ? null : <hr className="mb-4" />}
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

        return <div>
                <strong>Discussions:</strong>
                <span>&nbsp;</span>
                {intersperse(this.props.discussions.map(x => {
                    var label;
                    var url = new URL(x);
                    if (url.host === "externals.io") {
                        label = 'Mailinglist';
                    } else if (x.indexOf("derickrethans.nl/phpinternalsnews") > 0) {
                        label = "PHP Internals News";
                    } else {
                        label = url.host;
                    }
                    idx++;
                    return <a href={x} target="_blank" style={{whiteSpace: 'nowrap'}}>#{idx} {label}</a>
                }), ", ")}
                <hr />
            </div>
    }
}

class RfcVoteItem extends React.Component {
    render() {
        const voteCount = this.props.rfc.questions.length;

        return <div className="w-full md:w-1/2 p-2">
            <div className="bg-white rounded shadow-lg md:flex-auto flex-none">
                <div className="px-6 py-4">
                    {this.props.rfc.status == 'open' ?
                        <span className="inline-block bg-blue-500 text-white rounded-full px-3 mr-2 text-sm font-semibold">Active</span>
                        : null }
                    <a className="font-bold" href={this.props.rfc.url} target="_blank">{this.props.rfc.title}</a>
                    {this.props.rfc.targetPhpVersion.length > 0 && <div className={"float-right"}><span className="badge badge-secondary">PHP {this.props.rfc.targetPhpVersion}</span></div>}
                </div>
                <div className="px-6 py-4">
                    <div className="meta">
                        <RfcDiscussions discussions={this.props.rfc.discussions} />
                    </div>

                    {this.props.rfc.questions.map((item, idx) => {
                        return <VoteResults key={idx} vote={item} last={voteCount == idx+1} />
                    })}
                </div>
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
            <h2 className="text-lg font-semibold mb-2 p-2 uppercase">{this.props.title}</h2>
            <div className="flex flex-wrap items-start mb-10">
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
                return <RfcList key={version} rfcs={this.state.data.others[version]} title={"Accepted RFCs for PHP " + version} />
            })}
            <RfcList rfcs={this.state.data.rejected} title="Rejected RFCs"/>
        </div>
    }
}

ReactDOM.render(<RfcWatch />, document.getElementById("app"))
