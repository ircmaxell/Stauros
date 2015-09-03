var Index = React.createClass({displayName: "Index",
    getInitialState: function() {
        return {
            "sample": "$stauros = new Stauros\\Stauros;\r\n$clean = $stauros->scanHTML($dirty);",
            "sampleAttack": "<img src=\"javascript:alert('XSS');\">",
            "sampleAttackOutput": "<img>"
        };
    },
    render: function() {
        return (
            React.createElement("div", {className: "panel panel-default"}, 
                React.createElement("div", {className: "panel-body"}, 
                    React.createElement("h1", null, "Stauros - A fast XSS sanitizer for PHP"), 
                    React.createElement("div", {className: "panel panel-danger"}, 
                        React.createElement("div", {className: "panel-heading"}, React.createElement("h3", {className: "panel-title"}, "Warning")), 
                        React.createElement("div", {className: "panel-body"}, 
                            React.createElement("h4", null, "Stauros is currently an experimental library. It is not recommended for production use.")
                        )
                    ), 
                    React.createElement("div", {className: "panel panel-default"}, 
                        React.createElement("div", {className: "panel-heading"}, React.createElement("h4", null, "Usage:")), 
                        React.createElement("div", {className: "panel-body"}, 
                            React.createElement("pre", null, 
                                React.createElement("code", {className: "language-php"}, this.state.sample)
                            )
                        )
                    ), 
                    React.createElement("div", {className: "panel panel-default"}, 
                        React.createElement("div", {className: "panel-heading"}, React.createElement("h4", null, "An Example Of Bad Input:")), 
                        React.createElement("div", {className: "panel-body"}, 
                            React.createElement("h5", null, "Original Input"), 
                            React.createElement("pre", null, 
                                React.createElement("code", {className: "language-html"}, this.state.sampleAttack)
                            ), 
                            React.createElement("h5", null, "Cleaned Output"), 
                            React.createElement("pre", null, 
                                React.createElement("code", {className: "language-html"}, this.state.sampleAttackOutput)
                            )
                        )
                    )

                )
            )
        );
    }
});

var Demo = React.createClass({displayName: "Demo",
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
            React.createElement("div", {className: "panel panel-default"}, 
                React.createElement("div", {className: "panel-heading"}, React.createElement("h3", {className: "panel-title"}, "Code")), 
                React.createElement("div", {className: "panel-body"}, 
                    React.createElement("form", {className: "form-horizontal", onSubmit: this.handleSubmit}, 
                        React.createElement("div", {className: "form-group"}, 
                            React.createElement("label", {for: "code", className: "col-sm-2 control-label"}, "Input"), 
                            React.createElement("textarea", {id: "code", className: "form-control", rows: "6", ref: "code"}, this.props.code)
                        ), 
                        React.createElement("div", {className: "form-group"}, 
                            React.createElement("input", {className: "btn btn-default", type: "submit", value: "Post"})
                        ), 
                        React.createElement("div", {className: "form-group"}, 
                            React.createElement("label", {for: "escaped", className: "col-sm-2 control-label"}, "Result"), 
                            React.createElement("textarea", {id: "escaped", className: "form-control", rows: "6", disabled: true, ref: "escaped"}, this.props.escaped)
                        )
                    )
                )
            )
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
            currentComponent = React.render(React.createElement(Index, null), content);
            return;
        }
        if (url.indexOf('/demo') === -1) {
            currentComponent = React.render(React.createElement("div", null, "404"), content);
            return;
        }
        jQuery('.navbar-nav li.demo').addClass("active");
        var parts = url.match(/\/demo\/(.+)/);
        if (parts) {
            if (!data) {
                // fetch the data
                jQuery.get("/code/" + parts[1]).done(function(data) {
                    history.replaceState(data, "Code " + data.publicId, "/demo/" + parts[1]);
                    currentComponent = React.render(React.createElement(Demo, React.__spread({},  data)), content);
                });
                return;
            }
            currentComponent = React.render(React.createElement(Demo, React.__spread({},  data)), content);
        } else {
            currentComponent = React.render(React.createElement(Demo, null), content);
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