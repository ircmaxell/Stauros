var Index = React.createClass({displayName: "Index",
    getInitialState: function() {
        return {};
    },
    render: function() {
        return (
            React.createElement("div", null, "Stauros!")
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
            React.createElement("div", null, 
                React.createElement("form", {onSubmit: this.handleSubmit}, 
                    React.createElement("textarea", {ref: "code"}, this.props.code), 
                    React.createElement("input", {type: "submit", value: "Post"}), 
                    React.createElement("textarea", {ref: "escaped"}, this.props.escaped)
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
                    history.replaceState(data, "Code " + data.publicId, "/code/" + parts[1]);
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