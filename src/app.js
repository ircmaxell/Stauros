var Index = React.createClass({
    getInitialState: function() {
        return {};
    },
    render: function() {
        return (
            <div>Stauros!</div>
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
            <div>
                <form onSubmit={this.handleSubmit}>
                    <textarea ref="code">{this.props.code}</textarea>
                    <input type="submit" value="Post" />
                    <textarea ref="escaped">{this.props.escaped}</textarea>
                </form>
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