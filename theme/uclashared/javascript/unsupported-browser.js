/**
 * Message for unsupported browsers. (IE 6,7,8)
 */

YUI().use('node', 'event', function(Y) {
    Y.on('domready', function() {
        var template = '<div class="unsupported-browser">' +
                            '<div class="container" >' +
                                '<h1>Please note that CCLE does not support Internet Explorer version 8 or less</h1>' +
                                '<p>We recommend upgrading to the latest ' +
                                    '<a href="http://ie.microsoft.com">Internet Explorer</a>, ' +
                                    '<a href="http://chrome.google.com">Google Chrome</a>, or ' +
                                    '<a href="http://www.mozilla.org/firefox">Firefox</a>.</p> ' +
                            '</div>' +
                        '</div>';

        Y.one('#page').insert(Y.Node.create(template), 'before');
    })
})
