M.filter_mathjax = {
    init: function (Y, mathjaxroot) {
        var nodes = Y.all('.filter-mathjax');
        MathJax.Hub.Config({
            skipStartupTypeset: true,
            root: mathjaxroot
        });
        if (!nodes.isEmpty()) {
            MathJax.Hub.Queue(["Typeset", MathJax.Hub, nodes.getDOMNodes(), function() {}]);
        }
        MathJax.Hub.Configured();
    }
};
