
M.alert_block = {};

M.alert_block.init = function(Y) {
    
    YUI().use('dd-constrain', 'dd-proxy', 'dd-drop', 'io', function(Y) {
        
        //Listen for all drop:over events
        Y.DD.DDM.on('drop:over', function(e) {
            //Get a reference to our drag and drop nodes
            var drag = e.drag.get('node'),
                drop = e.drop.get('node');

            //Are we dropping on a li node?
            if (drop.get('tagName').toLowerCase() === 'li') {
                //Are we not going up?
                if (!goingUp) {
                    drop = drop.get('nextSibling');
                }
                //Add the node to this list
                e.drop.get('node').get('parentNode').insertBefore(drag, drop);
                //Resize this nodes shim, so we can drop on it later.
                e.drop.sizeShim();
            }
        });
        //Listen for all drag:drag events
        Y.DD.DDM.on('drag:drag', function(e) {
            //Get the last y point
            var y = e.target.lastXY[1];
            //is it greater than the lastY var?
            if (y < lastY) {
                //We are going up
                goingUp = true;
            } else {
                //We are going down.
                goingUp = false;
            }
            //Cache for next check
            lastY = y;
        });
        //Listen for all drag:start events
        Y.DD.DDM.on('drag:start', function(e) {
            //Get our drag object
            var drag = e.target;
            //Set some styles here
            drag.get('node').setStyle('opacity', '.25');
            drag.get('dragNode').set('innerHTML', drag.get('node').get('innerHTML'));
            drag.get('dragNode').setStyles({
                opacity: '.5',
                borderColor: drag.get('node').getStyle('borderColor'),
                backgroundColor: drag.get('node').getStyle('backgroundColor')
            });
        });
        //Listen for a drag:end events
        Y.DD.DDM.on('drag:end', function(e) {
            var drag = e.target;
            //Put our styles back
            drag.get('node').setStyles({
                visibility: '',
                opacity: '1'
            });
        });
        //Listen for all drag:drophit events
        Y.DD.DDM.on('drag:drophit', function(e) {
            var drop = e.drop.get('node'),
                drag = e.drag.get('node');

            //if we are not on an li, we must have been dropped on a ul
            if (drop.get('tagName').toLowerCase() !== 'li') {
                if (!drop.contains(drag)) {
                    drop.appendChild(drag);
                }
            }
        });

        //Static Vars
        var goingUp = false, lastY = 0;

        //Get the list of li's in the lists and make them draggable
        var lis = Y.Node.all('#alert-block-edit ul li');
        lis.each(function(v, k) {
            var dd = new Y.DD.Drag({
                node: v,
                target: {
                    padding: '0 0 0 20'
                }
            }).plug(Y.Plugin.DDProxy, {
                moveOnEnd: false
            }).plug(Y.Plugin.DDConstrained, {
                constrain2node: '#alert-block-edit'
            });
        });

        //Create simple targets for the 2 lists.
        var uls = Y.Node.all('#alert-block-edit ul');
        
        uls.each(function(v, k) {
            var tar = new Y.DD.Drop({
                node: v
            });
            
        });
        
        // Make nodes editable
        function clearStyles(node) {
            var colors = new Array('blue','cyan','green','orange','red');
            
            node.removeClass('alert-block-body-subtitle');
            node.removeClass('alert-edit-delete');
            
            colors.forEach(function(v, k) {
                node.removeClass('alert-block-list-' + v);
            })
        }
        
        // Hook button events on all nodes
        var allnodes = Y.all('#alert-block-edit ul li')
        
        allnodes.each(function(v, k) {
            var this_id = v.getAttribute('id');
            
            // Title
            var target_node = Y.one('#' + this_id + ' .alert-edit-title');
         
            if(target_node) {
                var tt = Y.one('#' + this_id + ' .alert-edit-content');
                
                target_node.on('click', function(e) {
                    clearStyles(tt);
                    tt.addClass('alert-block-body-subtitle');
                    tt.setAttribute('alerttype', 'title');
                });
            }
            
            // Message button
            target_node = Y.one('#' + this_id + ' .alert-edit-message');
            if(target_node) {
                tt = Y.one('#' + this_id + ' .alert-edit-content');
                target_node.on('click', function(e) {
                    clearStyles(tt);
                    tt.setAttribute('alerttype', 'msg')
                });
            }
            
            // Color buttons
            target_node = Y.all('#' + this_id + ' .alert-edit-list-color');
            if(target_node) {
                tt = Y.one('#' + this_id + ' .alert-edit-content');

                target_node.each(function(v, k) {
                    v.on('click', function(e) {
                        clearStyles(tt);
                        var color = v.getAttribute('rel');
                        tt.addClass('alert-block-list-' + color);
                        tt.setAttribute('alerttype', color)
                    })
                });
            }
            
            // Delete button
            target_node = Y.one('#' + this_id + ' .alert-edit-delete');
            target_node.on('click', function(e) {
                clearStyles(tt);
                tt.addClass('alert-edit-delete');
                tt.setAttribute('alerttype', 'delete')
            });
        })
        
        // Save event ajax
        var save_button = Y.one('#alert-save');
        save_button.on('click', function(e) {
           // Store nodes
           var display_nodes = new Array();
           var index = 0;
           
           // @todo
           // This is repetetive code.. put into function
           
           // Get the active list nodes 
           var list_nodes = Y.all('#alert-active-list li');
           list_nodes.each(function(v, k) {
               var nt = v.one('.alert-edit-content');
               var alert_type = nt.getAttribute('alerttype');
               var alert_id = nt.getAttribute('alertid');
               var content = nt.getContent();
               
               var ob = {
                   'type': alert_type,
                   'content': content,
                   'sortorder': k,
                   'alertid': alert_id,
                   'visible': 1,
                   'module': 'body'
               }
               // Store it
               display_nodes[index++] = ob;
           });
           
           // Get inactive list nodes
           list_nodes = Y.all('#alert-inactive-list li');
           list_nodes.each(function(v, k) {
               var nt = v.one('.alert-edit-content');
               var alert_type = nt.getAttribute('alerttype');
               var alert_id = nt.getAttribute('alertid');
               var content = nt.getContent();
               
               var ob = {
                   'type': alert_type,
                   'content': content,
                   'sortorder': k,
                   'alertid': alert_id,
                   'visible': 0,
                   'module': 'body'
               }
               // Store it
               display_nodes[index++] = ob;
           });
           
           // Get header nodes
           list_nodes = Y.all('#alert-active-list-header li');
           list_nodes.each(function(v, k) {
               var nt = v.one('.alert-edit-content');
               var alert_type = nt.getAttribute('alerttype');
               var alert_id = nt.getAttribute('alertid');
               var content = nt.getContent();
               
               var ob = {
                   'type': alert_type,
                   'content': content,
                   'sortorder': k,
                   'alertid': alert_id,
                   'visible': 1,
                   'module': 'header'
               }
               // Store it
               display_nodes[index++] = ob;
           });
           
           
           // Prepare JSON POST
           var json_out = JSON.stringify(display_nodes);
          
           // Send AJAX request
           Y.io('rest.php', {
               method: 'POST',
               data: 'incoming=' + json_out,
               on: {
                   success: function (id, result) {
//                       console.log('success...');
//                       console.log(result.responseText);
                       window.location = 'edit.php';
                   },
                   failure: function (id, result) {
//                       console.log('failure...');
//                       console.log(result.responseText);
                   }
               }
           });
        });
    });
};
