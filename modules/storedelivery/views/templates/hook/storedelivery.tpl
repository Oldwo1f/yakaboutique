
<div id="storedelivery">
	<h3>{l s='Select a store for delivery' mod='storedelivery'}</h3>
	<label>{l s='Store' mod='storedelivery'} : </label>
	<select name="storedelivery" id="locationSelect">
        {if $opc == false }
            <option value="0">Choisissez</option>
        {/if}
		{foreach from=$stores item=store name=stores}
			<option value="{$store.id_store|escape:'html':'UTF-8'}">{$store.name|escape:'html':'UTF-8'} - {$store.address1|escape:'html':'UTF-8'} {$store.postcode|escape:'html':'UTF-8'} {$store.city|escape:'html':'UTF-8'}</option>
		{/foreach}
	</select>
    
    {if $map == true}
        <div id="map" style="height: {$heightMap|escape:'html':'UTF-8'}; width: {$widthMap|escape:'html':'UTF-8'};"></div>
    {/if}
</div>


<script type="text/javascript">
    //Put inline JS here because all node is reload by ajax when click on a carrier option
    $carrier = {$carrier|escape:'html':'UTF-8'};
    $error_store = '{l s='You must select a store' mod='storedelivery'}';
    
    {if $opc == false }
        var selectedIndex = -1;
    {else}
        var selectedIndex = 0;
    {/if}
    
    {if $map == true}
        var markers = [];
        var defaultLat = '{$defaultLat|escape:'html':'UTF-8'}';
        var defaultLong = '{$defaultLong|escape:'html':'UTF-8'}';
        var hasStoreIcon = '{$hasStoreIcon|escape:'html':'UTF-8'}';
        var logo_store = '{$logo_store|escape:'html':'UTF-8'}';
        var img_ps_dir = '{$img_ps_dir|escape:'html':'UTF-8'}';
        var img_store_dir = '{$img_store_dir|escape:'html':'UTF-8'}';
        var direction = '{l s='Get directions' mod='storedelivery'}';
    {/if}
    
    {literal}
    $(document).ready(function(){
        
        function findAddress(selectedOption) {
            availableCarriers = selectedOption.split(',');
            $find = false;
            for (var i=0; i<availableCarriers.length; i++) {
                if(availableCarriers[i] == $carrier) {
                    $find = true;
                }
            }
            
            return $find;
        }
        
        /*********************************************
         * DISPLAY / HIDE STORE DELIVERY OPTION
         */
        //Hide at click
        $('.delivery_options input[type=radio]').click(function() {
            
            if(findAddress($(this).val()) == false ) {
                $('#storedelivery').slideUp();
                document.getElementById('locationSelect').selectedIndex = 0;
            }
            else {
                //$('#storedelivery').slideDown();
            }
        });

        //Display at load
        $find = findAddress($('.delivery_options input[type=radio]:checked').val());      
        if($find == true){
            $('#storedelivery').slideDown(400, function() {
                document.getElementById('locationSelect').selectedIndex = 0;
                
                if($('#map').length == 1) {
                    //CREATE MAP************************************************
                    map = new google.maps.Map(document.getElementById('map'), {
                        center: new google.maps.LatLng(defaultLat, defaultLong),
                        zoom: 10,
                        mapTypeId: 'roadmap',
                        mapTypeControlOptions: {style: google.maps.MapTypeControlStyle.DROPDOWN_MENU}
                    });
                    infoWindow = new google.maps.InfoWindow();

                    initMarkers();
                    
                    //INIT CHANGE SELECT STORE**********************************     
                    $('#storedelivery select').change(function() {
                        var markerNum = parseInt(document.getElementById('locationSelect').selectedIndex + selectedIndex);
                        google.maps.event.trigger(markers[markerNum], 'click');
                    });
                }
            });
        }

        //Alert on next button if no store was selected
        $(".cart_navigation input[type=submit], .cart_navigation .standard-checkout").unbind('click').click(function(){
            if(findAddress($('.delivery_options input[type=radio]:checked').val()) == true && $('#storedelivery select').val() == 0){
                alert($error_store);
                return false;
            }
        });

        
        /*********************************************
         * GOOGLE MAPS
         */
        function initMarkers() {
            var bounds = new google.maps.LatLngBounds();
            {/literal}
                {foreach from=$stores item=store name=stores}
                    var name = "{$store.name|escape:'html':'UTF-8'}";
                    var address = "{$store.address1|escape:'html':'UTF-8'} {$store.address2|escape:'html':'UTF-8'} {$store.postcode|escape:'html':'UTF-8'} {$store.city|escape:'html':'UTF-8'}";
                    var phone = '{$store.phone|escape:'html':'UTF-8'}';
                    var hours = '{$store.hours|unescape:'htmlall'}';
                    var id_store = {$store.id_store|escape:'html':'UTF-8'};
                    var has_store_picture = '{$store.has_store_picture|escape:'html':'UTF-8'}';
                    var latlng = new google.maps.LatLng(
                        parseFloat({$store.latitude|escape:'html':'UTF-8'}),
                        parseFloat({$store.longitude|escape:'html':'UTF-8'})
                    );
                    createMarker(latlng, name, address, phone, hours, id_store, has_store_picture);
                    bounds.extend(latlng);
                {/foreach}
            {literal}
            
            //De zoom to see all result
            map.fitBounds(bounds);
        }

        function createMarker(latlng, name, address, phone, hours, id_store, has_store_picture) {
            var html = '<b>'+name+'</b><br/>'+address+(has_store_picture === 1 ? '<br /><br /><img src="'+img_store_dir+parseInt(id_store)+'-medium.jpg" alt="" />' : '')+"<br />"+phone+hours+'<br /><a href="http://maps.google.com/maps?saddr=&daddr='+latlng+'" target="_blank">'+direction+'<\/a>';
            var image = new google.maps.MarkerImage(img_ps_dir+logo_store);
            var marker = '';

            if (hasStoreIcon)
                marker = new google.maps.Marker({ map: map, icon: image, position: latlng });
            else
                marker = new google.maps.Marker({ map: map, position: latlng });
            google.maps.event.addListener(marker, 'click', function() {
                infoWindow.setContent(html);
                infoWindow.open(map, marker);
            });
            markers.push(marker);
        }
        
        //Only for Order OPC****************************************************
        if($(".order-opc").length == 1) {
            function changeStore() {
                
                if($("#storedelivery:visible").length == 1) {
                    $data = 'storedelivery=' + $('select#locationSelect').val() + '&token=' + static_token +
                            '&carrier=' + $('.delivery_options input[type=radio]:checked').val();
                }
                else {
                    $data = 'storedelivery=0&token=' + static_token +
                            '&carrier=' + $('.delivery_options input[type=radio]:checked').val();
                }
                
                $.ajax({
                    type: 'POST',
                    headers: { "cache-control": "no-cache" },
                    url: baseDir + 'modules/storedelivery/storedelivery-ajax.php',
                    async: true,
                    cache: false,
                    dataType : "json",
                    data: $data,
                    success: function(jsonData)
                    {
                        //console.log("ok");
                    },
                    error: function(XMLHttpRequest, textStatus, errorThrown) {
                        //console.log('erreur');
                    }
                });
            }

            $('select#locationSelect').change(function() {
                changeStore(); 
            });
            changeStore();
        }
    });
    {/literal}
</script>

