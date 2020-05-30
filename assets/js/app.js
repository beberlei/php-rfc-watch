'use strict'

import React from 'react'
import ReactDOM from 'react-dom'
import _ from 'underscore'

const AppContext = React.createContext({logged_in: false});

class VoteResults extends React.Component {
    renderVote (vote, idx) {
        var bgs = ['bg-green-400', 'bg-red-400', 'bg-blue-400', 'bg-teal-400', 'bg-orange-400', 'bg-purple-400', 'bg-pink-400', 'bg-yellow-400'];

        return <div className="mb-1" key={vote.option}>
            <div className={bgs[idx] + " rounded-sm mr-2 h-2 w-2 inline-block"} />
            <span className="text-xs">{vote.option}: <span className="font-normal">{vote.votes}</span></span>
        </div>;
    }

    renderVoteProgress () {
        var bgs = ['bg-green-400', 'bg-red-400', 'bg-blue-400', 'bg-teal-400', 'bg-orange-400', 'bg-purple-400', 'bg-pink-400', 'bg-yellow-400'];

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
            <div className={!this.props.last ? "mb-8" : null}>
                <div className="mb-2 break-words">
                    {this.props.vote.question}
                </div>

                <div className="mb-2">
                    {this.renderVoteProgress()}
                </div>

                {this.props.vote.results.map(this.renderVote)}

                <div className="text-gray text-xs mt-2 ml-4">
                    Total number of votes cast: <span className="font-normal">{this.computeTotalVotesCasted()}</span>
                </div>
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
        if (this.props.discussions.length === 0) {
            return null;
        }

        var idx = 0;

        return <div className="text-xs text-gray-800 py-4">
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

                    return <a key={idx} href={x} target="_blank" style={{whiteSpace: 'nowrap'}}>#{idx} {label}</a>
                }), ", ")}
            </div>
    }
}

class RfcCommunityVote extends React.Component {
    constructor() {
        super();
        this.state = {voted: false, communityVote: {}}
    }
    vote(choice, loggedIn) {
        if (!loggedIn) {
            return;
        }

        fetch('/vote', {
            method: 'POST',
            body: JSON.stringify({
                choice: choice,
                id: this.props.rfc.id,
            })
        })
            .then(response => response.json())
            .then(data => this.setState({
                voted: true,
                communityVote: data.communityVote,
            }));
    }
    componentDidUpdate(prevProps) {
        if (this.props.rfc.communityVote.up !== prevProps.rfc.communityVote.up ||
            this.props.rfc.communityVote.down !== prevProps.rfc.communityVote.down) {
            this.setState({voted: false, communityVote: {}});
        }
    }
    render() {
        const communityVote = this.state.voted ? this.state.communityVote : this.props.rfc.communityVote

        return <AppContext.Consumer>
            {ctx => <div className="text-right">
                {!ctx.logged_in ? <a className="underline mr-4 text-sm" href="/login">Login with Github for Community Voting</a> : null}

            <span className="relative z-0 inline-flex shadow-sm">
                <button type="button" onClick={() => this.vote(1, ctx.logged_in)} title={!ctx.logged_in ? "Login required" : ""}
                        className="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm leading-5 font-medium text-gray-500 hover:text-gray-400 focus:z-10 focus:outline-none focus:border-blue-300 focus:shadow-outline-blue active:bg-gray-100 active:text-gray-500 transition ease-in-out duration-150">
                    <svg className={"h-5 w-5"} fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                                                      d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                    </svg>
                    <span className={communityVote.you === 1 ? 'font-bold' : null}>{communityVote.up}</span>
                </button>
                <button type="button" onClick={() => this.vote(-1, ctx.logged_in)} title={!ctx.logged_in ? "Login required" : ""}
                        className="-ml-px relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm leading-5 font-medium text-gray-500 hover:text-gray-400 focus:z-10 focus:outline-none focus:border-blue-300 focus:shadow-outline-blue active:bg-gray-100 active:text-gray-500 transition ease-in-out duration-150">
                    <svg className={"h-5 w-5"} fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                                                      d="M10 14H5.236a2 2 0 01-1.789-2.894l3.5-7A2 2 0 018.736 3h4.018a2 2 0 01.485.06l3.76.94m-7 10v5a2 2 0 002 2h.096c.5 0 .905-.405.905-.904 0-.715.211-1.413.608-2.008L17 13V4m-7 10h2m5-10h2a2 2 0 012 2v6a2 2 0 01-2 2h-2.5"/>
                    </svg>
                    <span className={communityVote.you === -1 ? 'font-bold' : null}>{communityVote.down}</span>
                </button>
            </span>
        </div>}
        </AppContext.Consumer>
    }
}

class RfcVoteItem extends React.Component {
    render() {
        const voteCount = this.props.rfc.questions.length;

        return <div className="w-full md:w-1/2 p-2">
            <div className="bg-white rounded shadow-lg md:flex-auto flex-none">
                <div className="px-6 py-4">
                    {this.props.rfc.status === 'open' ?
                        <span className="inline-block bg-blue-500 text-white rounded-full px-3 mr-2 text-sm font-semibold">Active</span>
                        : null}

                    <a className="font-bold hover:underline no-underline break-words" href={this.props.rfc.url} target="_blank">{this.props.rfc.title}</a>

                    {this.props.rfc.targetPhpVersion.length > 0 && <div className={"float-right"}><span className="badge badge-secondary">PHP {this.props.rfc.targetPhpVersion}</span></div>}

                    <RfcDiscussions discussions={this.props.rfc.discussions} />

                    {this.props.rfc.questions.map((item, idx) => {
                        return <VoteResults key={idx} vote={item} last={voteCount === idx+1} />
                    })}

                    {this.props.rfc.status === 'open' ?
                        <RfcCommunityVote rfc={this.props.rfc} /> : null}
                </div>
            </div>
        </div>
    }
}

class RfcList extends React.Component {
    render () {
        if (this.props.rfcs.length === 0) {
            return null;
        }

        return <div>
            <h2 id={this.props.title} className="text-lg font-semibold mb-2 p-2 uppercase rfc-list-header">
                {this.props.title}
                <a href={'#' + this.props.title} className="rfc-anchor text-gray-600 ml-4">¶</a> 
            </h2>

            <div className="rfc-list flex flex-col flex-wrap items-start mb-10">
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
            <AppContext.Provider value={{logged_in: this.state.data.logged_in}}>
                <RfcList rfcs={this.state.data.active} title="Currently Active RFCs"/>
                {Object.keys(this.state.data.others).map( (version) => {
                    return <RfcList key={version} rfcs={this.state.data.others[version]} title={"Accepted RFCs for PHP " + version} />
                })}
                <RfcList rfcs={this.state.data.rejected} title="Rejected RFCs"/>
            </AppContext.Provider>
        </div>
    }
}

ReactDOM.render(<RfcWatch />, document.getElementById("app"))
