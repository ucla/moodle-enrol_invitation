
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
        
        // Nodes
        var nodeConstrain = '#ucla-alert-edit';
        var nodeAll = nodeConstrain + ' ul li';
        var nodeLists = nodeConstrain + ' ul';
        
        //Get the list of li's in the lists and make them draggable
        var lis = Y.Node.all(nodeAll);
        lis.each(function(v, k) {
            var dd = new Y.DD.Drag({
                node: v,
                target: {
                    padding: '0 0 0 20'
                }
            }).plug(Y.Plugin.DDProxy, {
                moveOnEnd: false
            }).plug(Y.Plugin.DDConstrained, {
                constrain2node: nodeConstrain
            });
        });
        
        //Create simple targets for the 2 lists.
        var uls = Y.Node.all(nodeLists);
        uls.each(function(v, k) {
            var tar = new Y.DD.Drop({
                node: v
            });
        });
        
        // HELPER FUNCTIONS
        function toggleDisplayAreas(display, edit) {
            var d = display.getStyle('display');
            var e = edit.getStyle('display');
            
            display.setStyle('display', (d == 'none' ? 'block' : 'none'));
            edit.setStyle('display', (e == 'none' ? 'block' : 'none'));
        }

        // ADD ITEM EDIT
        Y.all('#ucla-alert-edit li').on('dblclick', function(e) {
            
            var target = e.target.ancestor('li');
            // Get saved text
            var savedText = target.getAttribute('rel');
            
            var displayArea = target.one('.box-boundary');
            var editArea = target.one('.alert-edit-text-box');

//            var displayHeight = displayArea.get('clientHeight');
//            editArea.setStyle('height', displayHeight);
            toggleDisplayAreas(displayArea, editArea);

            editArea.one('textarea').set('value', savedText);

        });
        
        // Prevent double click propagation on 'edit' area
        Y.all('.alert-edit-text-box').on('dblclick', function(e) {
            e.stopPropagation();
        });

        // Attach cancel events
        Y.all('.alert-edit-text-box .alert-edit-cancel').on('click', function(e) {
            var targetNode = e.target.ancestor('.alert-edit-element');
            var displayNode = targetNode.one('.box-boundary');
            var editNode = targetNode.one('.alert-edit-text-box');
            
            // Restore text
//            editNode.one('textarea').set('value', targetNode.getAttribute('rel'));
            
            // Toggle displays
            toggleDisplayAreas(displayNode, editNode);
        });

        Y.all('.alert-edit-text-box .alert-edit-save').on('click', function(e) {
            var targetNode = e.target.ancestor('.alert-edit-element');
            var displayNode = targetNode.one('.box-boundary');
            var editNode = targetNode.one('.alert-edit-text-box');
            
            var updatedText = editNode.one('textarea').get('value');

            var json_out = JSON.stringify({
                'text' : updatedText,
                'type' : 'item'
            });

            // Send AJAX request
            Y.io('rest.php', {
                method: 'GET',
                data: 'render=' + json_out,
                on: {
                    success: function (id, result) {
//                              console.log('success...');
//                              console.log(result.responseText);
                        var newNode = Y.Node.create(result.responseText);
                        
                        // Update newNode
                        newNode.replaceClass('box-boundary', displayNode.getAttribute('class'));
                        displayNode.replace(newNode);
                        targetNode.setAttribute('rel', updatedText);
                        editNode.setStyle('display', 'none');
                    },
                    failure: function (id, result) {
                          console.log('failure...');
                          console.log(result.responseText);
                    }
                }
            });
        });
        

        // ADD 'CLICK' SELECT TO HEADER NODES
        var headerNodes = Y.all('#ucla-alert-edit .alert-edit-header .alert-edit-header-wrapper');
        
        headerNodes.each(function(node) {
            var target = node.one('.header-box');
            
            if(target.getAttribute('visible') == '1') {
                target.addClass('header-selected');
            }
        });
        
        // HEADER SELECT ON 'CLICK'
        headerNodes.on('click', function(e) {
            var target = e.target.ancestor('.header-box');
            if(target) {
                headerNodes.each(function(node) {
                    var target = node.one('.header-box');
                    target.removeClass('header-selected');
                    target.setAttribute('visible', '0');
                })
                target.addClass('header-selected');
                target.setAttribute('visible', '1');
            }
        })
        
        // HEADER EDIT ON 'DOUBLE CLICK'
        headerNodes.on('dblclick', function(e) {
            var target = e.target.ancestor('.alert-edit-header-wrapper');
            
            var displayArea = target.one('.header-box');
            var editArea = target.one('.alert-edit-text-box');
            var savedText = displayArea.getAttribute('rel');
            
            editArea.one('textarea').set('value', savedText);
            // Swap visibility
            toggleDisplayAreas(displayArea, editArea);
        })
    });
}    