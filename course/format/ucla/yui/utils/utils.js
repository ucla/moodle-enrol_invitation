/*
 * UCLA course format utils
 */

YUI.add('moodle-format_ucla-utils', function(Y) {
    
    M.format_ucla = M.format_ucla || {};
    
    M.format_ucla.utils = {
        init: function(config) {
            
            var desc = Y.one('.registrar-summary .text_to_html');
            var height = desc.get('offsetHeight');
            
            desc.transition({
                easing: 'ease-in',
                duration: 1,
                height: '0px'
            }).addClass('collapsed');
            
            Y.one('.registrar-summary .collapse-toggle').on('click', function(e) {
                e.preventDefault();
                
                if(desc.hasClass('collapsed')) {
                    desc.removeClass('collapsed');
                    desc.transition({
                        easing: 'ease-out',
                        duration: 0.5,
                        height: height + 'px'
                        
                    }, function() {
                        e.target.transition({
                            easing: 'ease-in',
                            duration: 0.5,
                            opacity: 0
                        }, function() {
                            e.target.remove();
                        });
                    })
                } 
//                else {
//                    desc.addClass('collapsed');
//                    desc.transition({
//                        easing: 'ease-out',
//                        duration: 0.5,
//                        height: '0px'
//                    });
//                }
            });
        }
    }
    
    

}, '@VERSION@', {
    requires: ['node', 'transition']
});