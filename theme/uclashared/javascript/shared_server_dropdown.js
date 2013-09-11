// Javascript


YUI().use('node', 'event', function(Y) {

    Y.on('domready', function() {
        
        var sharedserver = Y.one('.frontpage-shared-server .server-link');

        if (sharedserver) {
            sharedserver.on('click', function(e) {

                var target = e.target.ancestor('.frontpage-shared-server');
                var list = target.one('.shared-server-list');

                if (list.getStyle('display') === 'none') {
                   list.setStyle('display', 'block');
                   target.one('.server-link .glyphicon').removeClass('glyphicon-chevron-up').addClass('glyphicon-chevron-down');
                } else {
                   list.setStyle('display', 'none');
                   target.one('.server-link .glyphicon').removeClass('glyphicon-chevron-down').addClass('glyphicon-chevron-up');
                }

            });
        }
    });
    
});

