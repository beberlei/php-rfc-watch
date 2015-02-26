/** @jsx React.DOM */

'use strict'

var React = require('react')
var RfcEventTimeline = require('./RfcEventTimeline');
var RfcList = require('./RfcList');
var EventsStore = require('./store/events.js');

var App = React.createClass({
    render: function() {
        return (
            <div className="row">
                <div className="col-md-8">
                    <RfcEventTimeline events={this.props.events} />
                </div>
                <div className="col-md-4">
                    <RfcList rfcs={this.props.rfcs} />

                    <div className="box">
                        <div className="box-header">
                            <h3 className="box-title">About</h3>
                        </div>
                        <div className="box-body">
                            <p>
                                No need to frantically refresh the voting pages for RFCs anymore.
                                You will get both the results and an event based stream of all the votes
                                for RFCs.
                            </p>

                            <p>
                                This project is built with Symfony2, React.JS,  Webpack and Doctrine CouchDB ODM.
                                You can look at the source code on <a href="https://github.com/beberlei/php-rfc-watch">Github</a>.
                            </p>

                            <p>
                                I have written a blog post on how to integrate <a href="http://www.whitewashing.de/2015/02/26/integrate_symfony_and_webpack.html">Symfony with Webpack and React.js</a> if you are interested.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        )
    }
});

EventsStore.on('change', function() {
    React.render(<App events={EventsStore.getAllActive()} rfcs={EventsStore.getRfcs()} />, document.getElementById('content'));
});
EventsStore.fetch();

setInterval(function() {
    EventsStore.fetch();
}, 60000);
