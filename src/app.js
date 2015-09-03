var Index = React.createClass({
    getInitialState: function() {
        return {
            "sample": "$stauros = new Stauros\\Stauros;\r\n$clean = $stauros->scanHTML($dirty);",
            "sampleAttack": "<img src=\"javascript:alert('XSS');\">",
            "sampleAttackOutput": "<img>"
        };
    },
    render: function() {
        return (
            <div className="panel panel-default">
                <div className="panel-body">
                    <h1>Stauros - A fast XSS sanitizer for PHP</h1>
                    <div className="panel panel-danger">
                        <div className="panel-heading"><h3 className="panel-title">Warning</h3></div>
                        <div className="panel-body">
                            <h4>Stauros is currently an experimental library. It is not recommended for production use.</h4>
                        </div>
                    </div>
                    <div className="panel panel-default">
                        <div className="panel-heading"><h4>Usage:</h4></div>
                        <div className="panel-body">
                            <pre>
                                <code className="language-php">{this.state.sample}</code>
                            </pre>
                        </div>
                    </div>
                    <div className="panel panel-default">
                        <div className="panel-heading"><h4>An Example Of Bad Input:</h4></div>
                        <div className="panel-body">
                            <h5>Original Input</h5>
                            <pre>
                                <code className="language-html">{this.state.sampleAttack}</code>
                            </pre>
                            <h5>Cleaned Output</h5>
                            <pre>
                                <code className="language-html">{this.state.sampleAttackOutput}</code>
                            </pre>
                        </div>
                    </div>

                </div>
            </div>
        );
    }
});

var Demo = React.createClass({
    getInitialState: function() {
        return {
        };
    },
    handleSubmit: function(e) {
        e.preventDefault();
        var code = React.findDOMNode(this.refs.code).value.trim();
        if (!code) {
            return;
        }

        jQuery.post("/code/new", code).done(function(data) {
            App.pushState(data);
        });
    },
    render: function() {
        return (
            <div className="panel panel-default">
                <div className="panel-heading"><h3 className="panel-title">Code</h3></div>
                <div className="panel-body">
                    <form className="form-horizontal" onSubmit={this.handleSubmit}>
                        <div className="form-group">
                            <label for="code" className="col-sm-2 control-label">Input</label>
                            <textarea id="code" className="form-control" rows="6" ref="code">{this.props.code}</textarea>
                        </div>
                        <div className="form-group">
                            <input className="btn btn-default" type="submit" value="Post" />
                        </div>
                        <div className="form-group">
                            <label for="escaped" className="col-sm-2 control-label">Result</label>
                            <textarea id="escaped" className="form-control" rows="6" disabled ref="escaped">{this.props.escaped}</textarea>
                        </div>
                    </form>
                </div>
            </div>
        );
    }
});

var App = (function(Index, Demo) {
    var currentComponent;

    this.loadUrl = function(url, data) {
        var content = document.getElementById("content");
        jQuery('.navbar-nav li').removeClass("active");
        React.unmountComponentAtNode(content);
        if (url === '/') {
            jQuery('.navbar-nav li.home').addClass("active");
            currentComponent = React.render(<Index />, content);
            return;
        }
        if (url.indexOf('/demo') === -1) {
            currentComponent = React.render(<div>404</div>, content);
            return;
        }
        jQuery('.navbar-nav li.demo').addClass("active");
        var parts = url.match(/\/demo\/(.+)/);
        if (parts) {
            if (!data) {
                // fetch the data
                jQuery.get("/code/" + parts[1]).done(function(data) {
                    history.replaceState(data, "Code " + data.publicId, "/code/" + parts[1]);
                    currentComponent = React.render(<Demo {...data} />, content);
                });
                return;
            }
            currentComponent = React.render(<Demo {...data} />, content);
        } else {
            currentComponent = React.render(<Demo />, content);
        }
    };

    this.pushState = function(data) {
        history.pushState(data, "Code " + data.publicId, "/demo/" + data.publicId);
        this.loadUrl("/demo/" + data.publicId, data);
    };

    window.onpopstate = function(e) {
        e.preventDefault();
        this.loadUrl(document.location.pathname, event.state);
        return false;
    }

    jQuery('a').click(function(e) {
        var link = jQuery(this).attr('href');
        if (/^https?:\/\//i.test(link)) {
            /* absolute url */
            return true;
        }
        e.preventDefault();
        history.pushState(null, jQuery(this).attr('title'), link);
        App.loadUrl(link);
        return false;
    });


    this.loadUrl(location.pathname);
    return this;
})(Index, Demo);