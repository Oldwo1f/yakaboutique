/*
*  @author Marcin Kubiak <support@add-ons.eu>
*  @copyright  Smart Soft
*  @license    Commercial license
*  International Registered Trademark & Property of Smart Soft
*/
  
  // show preloder function      
  function  showPreloder()  {
    jq162('#PageTree').css({ opacity: 0 });
    jq162('#preloder').show();
    
    return false;
  }   
  //hide preloder        
  function  hidePreloder()  {
    jq162('#PageTree').css({ opacity: 1 });
    jq162('#preloder').hide();
    
    return false;
  }       
        
         jq162(document).ready(function(){
		 
            //setup ajax handle errors
            jq162.ajaxSetup({
              error:function(x,e){
                if(x.status==0){
                alert('You are offline!!\n Please Check Your Network.');
                }else if(x.status==404){
                alert('Requested URL not found.');
                }else if(x.status==500){
                alert('Internel Server Error.');
                }else if(e=='parsererror'){
                alert('Error.\nParsing JSON Request failed. \n'+x.responseText);
                }else if(e=='timeout'){
                alert('Request Time out.');
                }else {
                alert('Unknow Error.\n'+x.responseText);
                }
              }
            });
            //tabs
            jq162( "#maintabs" ).tabs();
            // select home prevent from add/edit none selected element
            jq162('#home a').addClass('selected');
            
            //remove Image
			jq162('#photo').live('click', function(){
			   var id = jq162('#id_megamenu').val();
			   //send
               jq162.getJSON( urlJson + '&action=removeimage', {id: id}, function(resp) { 
              		jq162('#photo img').remove();
					jq162('#photo input').remove();
               });   
               
               return false; 
			});
            // add 
            jq162('#add').bind('click', function() {
                // get id parent element
                var parent = jq162("#PageTree a.selected").attr('rel');
                var custom = jq162("#PageTree a.selected").parent('li').hasClass('custom');
				
			   var li = jq162("ul#PageTree  a.selected").parent('li');
			   var pos = jq162(li).find('ul').children('li').last().attr('pos');
				if( pos == undefined ) {
					pos = jq162(li).attr('pos');		    	
				}
				var position = parseFloat(pos) + 0.01;

                jq162('#input_custom').val(custom);
				jq162('#photo img').attr('src', '');
				jq162('#photo input').val('');
				jq162('#position').val(position);
                //if undefined put 1
                if(parent == undefined || parent == ''){
                   jq162('#parent').val(1);
                } else {
                   jq162('#parent').val(parent);
                }
                //set action
                action='add';  
                //open form
                jq162( "#add_form" ).dialog( "open" ); 
                
                return false;               
            });

            jq162('#edit').bind('click', function() {
               //element id
               var id = jq162("#PageTree a.selected").attr('rel');
               //show preloder
               showPreloder();

               //set action
               action = 'saveedit';
               //send
               jq162.getJSON( urlJson + '&action=edit', {id: id}, function(resp) { 
			     var d = new Date(); 
			     var img = _MODULE_DIR_ + 'megamenu/images/' + resp.id + '.jpg?' + d.getTime() ;               
                 //set response data to form
                 jq162('.name').val(resp.name);  
                 jq162('.title').val(resp.title);
                 jq162('.url').val(resp.url); 
                 jq162('#id_megamenu').val(resp.id);  
                 jq162('#parent').val(resp.parent);
				 jq162('#photo img').attr('src', img);
                 //open form
                 jq162( "#add_form" ).dialog( "open" ); 
                 //hide preloder
                 hidePreloder();                
               });   
               
               return false;                
            });
           
            jq162('#delete').bind('click', function() {
                
               var id =  jq162("#PageTree a.selected").attr('rel');
               // root can't delete
               if( jq162("#PageTree a.selected").attr('id') != 'home' ){
                 //show preloder
                 showPreloder();
           
                 jq162.getJSON( urlJson + '&action=delete', {id: id}, function(resp) {

                    if (jq162("#PageTree a.selected").parent('li').parent().children('li').length == 1)
                          jq162("#PageTree a.selected").parent('li').parent().closest('li').removeClass('parent');
                    //element       
                    jq162("#PageTree a.selected").parent('li').remove();
                    //add msg
                    addMsg(resp); 
                    //chceck first
                    jq162('li:first a').addClass('selected');
                    //hide preloder
                    hidePreloder(); 
                    jq162('#home a:first').addClass('selected');                                                    
                 });    
                 
               } else {
                 hidePreloder();
                 addMsg("You can't delete root");
               }
               return false;          
            });
            
            jq162( '#enable' ).bind('click', function() {
               //show preloder
                showPreloder();
               //get checked element id
               var id = jq162("#PageTree a.selected").attr('rel');
               var custom = jq162("#PageTree a.selected").parent('li').hasClass('custom');
               //if root display cant disable
               if( jq162("#PageTree a.selected").attr('id') != 'home' ){
                  
                 jq162.getJSON( urlJson + '&action=enable', {id: id, custom: custom}, function(resp) { 
                    //change colors text
                    if(parseInt(resp.active) == 0){
                      jq162("#PageTree a.selected").removeClass('enable');                     
                      jq162("#PageTree a.selected").addClass('disable');                    
                    } else if (parseInt(resp.active) == 1){                 
                      jq162("#PageTree a.selected").addClass('enable');                     
                      jq162("#PageTree a.selected").removeClass('disable'); 
                    }
                    // add msg
                    addMsg(resp.msg) ;
                    //hide preloder
                    hidePreloder();
                  });
                  
                 } else {
                   hidePreloder();
                   addMsg("You can't disable root element");
               } 
                                                 
               return false;
            });
            
            jq162( '#copy' ).bind('click', function() {
               var custom = jq162("#PageTree a.selected").parent('li').hasClass('custom');
			   
			   var li = jq162("ul#PageTree  a.selected").parent('li');
				var pos = jq162(li).find('ul').children('li').last().attr('pos');
				if( pos == undefined ) {
					pos = jq162(li).attr('pos');		    	
				}
				var position = parseFloat(pos) + 0.01;
		
               jq162('#copyinput_custom').val(custom);
			   jq162('#copy_position').val(position);
               //get parent id
               var parent = jq162("#PageTree a.selected").attr('rel');
			   
                // assign 1 if parent not defined
                if(parent == undefined || parent == ''){                  
                   jq162('#parentid').val(1);                   
                } else {                  
                   jq162('#parentid').val(parent);
                }
               //set action
               action="copycms";
               //open form
               jq162( "#copycms" ).dialog( "open" );    
               
               
               return false;
            });
            
            /****************************************************
             *************      COPY CMS FORM       *************
             ****************************************************/

            jq162( "#copycms" ).dialog({
                autoOpen: false,
                height: 240,
                width: 420,
                modal: true,
                buttons: {
                  "Copy link": function() {
				  	  if( jq162('select[name=id_cms]').val() != 0 )
					  {
	                      //show preloder
	                      showPreloder();
	                      // get form data                   
	                      var  data = jq162('#copycms_form').serializeArray();
	                      //send
	                      jq162.getJSON( urlJson + '&action=' + action, data, function(resp) { 
	                     
	                             //check element
	                             var name = '#PageTree a.selected';
	                             // li to add
	                             var li = '<li class="custom" data-id="' + resp.id + '" pos="' + jq162('#copy_position').val() +'">' +
	                                         '<span class="custom-img"></span>' +
	                                         '<a class="caption ui-droppable ui-draggable" rel="' + resp.id + '">'+ resp.name +'</a>'  +
	                                      '</li>';
	                                  jq162( name ).parent('li').addClass('parent collapsed');
	                                  // check if has paret
	                                  if( jq162( name ).parent('li').find('ul').children().size() > 0 )
	                                  {
	                                    // add without ul
	                                    jq162( name ).parent('li').find('ul:first').append(li);
	                                  }else {
	                                    // add with ul
	                                    jq162( name ).parent('li').append( '<ul>' + li + '</ul>' );
	                                  }
	                            //if explorer add last to list    
	                            if (jq162.browser.msie) { jq162('li').removeClass('last'); jq162('li:last-child').addClass('last'); } 
	                            //add msg                                                     
	                            addMsg(resp.msg);
	                            //hide preloder
	                            hidePreloder(); 
	                            
	                       });  
	                   
	                      jq162( this ).dialog( "close" );
						  
					  } else {
					  	alert('Please select cms page.');
					  }
                    
                  },
                  Cancel: function() {
                    jq162( this ).dialog( "close" );
                  }
                },
                close: function() {
                  allFields.val( "" ).removeClass( "ui-state-error" );
                }
             });
             
             /***************************************************
             *************      ADD FORM       ******************
             ****************************************************/
             
            //jquery ui form
            var name = jq162( "#name_" + id_language ),
                title = jq162( "#title_" + id_language),
                url = jq162( "#url_" + id_language ),
                allFields = jq162( [] ).add( name ).add( title ).add( url ),
                tips = jq162( ".validateTips" );
           // highlight errors
              function updateTips( t ) {
                tips
                  .text( t )
                  .addClass( "ui-state-highlight" );
                setTimeout(function() {
                  tips.removeClass( "ui-state-highlight", 1500 );
                }, 500 );
              }
            //form validation length
              function checkEmpty( o, n ) {
                if ( o.val() == null || o.val() == '' ) {
                  o.addClass( "ui-state-error" );
                  updateTips( "Field " + n + " can't be empty for default language." );
                  return false;
                } else {
                  return true;
                }
              }
              
           // add msg with fade effect   
           function addMsg( msg ) {
                jq162('#msg').fadeOut("slow");
                jq162('#msg').text(msg);
                jq162('#msg').fadeIn("slow");
           }
           //add form
           jq162( "#add_form" ).dialog({
                autoOpen: false,
                height: 500,
                width: 560,
                modal: true,
                buttons: {
                  "Create link": function() {

                    var bValid = true;
                    allFields.removeClass( "ui-state-error" );
          
                    bValid = bValid && checkEmpty( name , 'name');
                    bValid = bValid && checkEmpty( title, 'title');
                    bValid = bValid && checkEmpty( url, 'url');
                    // validation form
                    if ( bValid ) {
                      //show preloder
                      showPreloder();
                    
                      // get form data
                      var  data  = jq162('#form').serializeArray();
					  
                      jq162.getJSON( urlJson + '&action=' + action, data, function(resp) {  
                       
                         if(action == 'add'){
                              
                             //check element
                             var name = '#PageTree a.selected';
                             // li to add
                             var li = '<li class="custom" data-id="' + resp.id + '" pos="' + jq162('#position').val() +'">' +
                                         '<span class="custom-img"></span>' +
                                         '<a class="caption ui-droppable ui-draggable" rel="' + resp.id + '">'+ resp.name +'</a>'  +
                                      '</li>';
                                  jq162( name ).parent('li').addClass('parent collapsed');
                                  // check if has paret
                                  if( jq162( name ).parent('li').find('ul').children().size() > 0 )
                                  {
                                    // add without ul
                                    jq162( name ).parent('li').find('ul:first').append(li);
                                  }else {
                                    // add with ul
                                    jq162( name ).parent('li').append( '<ul>' + li + '</ul>' );
                                  }
                                  //if explorer add last to list
                                  if (jq162.browser.msie) { jq162('li').removeClass('last'); jq162('li:last-child').addClass('last'); } 
                                
                          } else {  
                             jq162( '#PageTree a.selected' ).html(resp.item.name);
                          }
                             //hide preloder
                             hidePreloder();                                                   
                             addMsg(resp.msg);                                 
  
                       });  
                   
                      jq162( this ).dialog( "close" );
                    }
                  },
                  Cancel: function() {
                    jq162( this ).dialog( "close" );
                  }
                },
                close: function() {
                  allFields.val( "" ).removeClass( "ui-state-error" );
                }
              });
            
            /***************************************************
             *************      LiveFLEX       ******************
             ****************************************************/
             
            var jq162events = jq162('#Events');
            var alReadyClicked = false;
            
            function log(m){
              jq162events.prepend(m + "<br />");
            }
            
           jq162("ul.tree").liveflex_treeview({
              handle:'a.caption'
              ,itemMoved: function(csv){
                if(csv != null){
                //the csv is [ItemId]-[NewParentId].[Position]
                jq162.getJSON( urlJson + '&action=position', {csv: csv}, function(resp) {  
                      // add response msg
                      addMsg(resp);
                      //if explorer add last to list 
                      if (jq162.browser.msie) { jq162('li').removeClass('last'); jq162('li:last-child').addClass('last'); }   
                    }); 
                }    
              },dirClick:function(node){                
                // add selected class
                jq162("ul.tree a").removeClass('selected'); 
                node.find('a:first').addClass('selected');
                var Id = parseInt(node.attr('data-id')); //get the data id
                // check if has custom class
                var custom = node.hasClass('custom'); 
                if(custom == true) {
                  jq162('#edit').show();
                  jq162('#delete').show();
                } else {
                  jq162('#edit').hide();
                  jq162('#delete').hide();
                }
                if(node.children('ul').length  == 0 && alReadyClicked == false){ //if the node hasn't any children yet
                  alReadyClicked = true;
                  //show preloder
                  showPreloder();
                  //call your server routine
                  jq162.ajax({
                    url: urlJson + "&action=gettree&id=" + Id + "&custom=" + custom,
                    success: function(data){
                      //append the new li's
                      node.append('<ul>' + data + '</ul>'); 
                      //add expanded class so the folding works 
                      node.addClass('expanded');
                      //setto true so he can click again
                      alReadyClicked = false;
                      //hide preloder
                      hidePreloder(); 
                      if (jq162.browser.msie) { jq162('li').removeClass('last'); jq162('li:last-child').addClass('last'); } 
                    } 
                  });
                } 
                
              },nodeClick: function(node){
                  var custom = node.hasClass('custom');
                  if(custom == true) {
                    jq162('#edit').show();
                    jq162('#delete').show();
                  } else {
                    jq162('#edit').hide();
                    jq162('#delete').hide();
                  }
                  jq162("ul.tree a").removeClass('selected');
                  node.find('a:first').addClass('selected');
              }
            });

            jq162('#home').click();
            
            
        });
