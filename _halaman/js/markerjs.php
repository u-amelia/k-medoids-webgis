<!-- Make sure you put this AFTER Leaflet's CSS -->
<script src="https://unpkg.com/leaflet@1.3.4/dist/leaflet.js"
    integrity="sha512-nMMmRyTVoLYqjP9hrbed9S+FzjZHW5gY1TWCHA5ckwXZBadntCNs8kEqAWdrb9O7rxbCaA4lKTIWjDXZxflOcA=="
    crossorigin=""></script>

<script src="assets/js/leaflet-panel-layers-master/src/leaflet-panel-layers.js"></script>
<script src="assets/js/leaflet.ajax.js"></script>

<script type="text/javascript">
var map = L.map('mapid').setView([5.208950, 96.833068], 13);

var LayerKita = L.tileLayer('https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token={accessToken}', {
    attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, <a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="https://www.mapbox.com/">Mapbox</a>',
    maxZoom: 18,
    id: 'mapbox.streets',
    accessToken: 'pk.eyJ1IjoidGFsaGFraHdqYTA2IiwiYSI6ImNsZGxmeHoyZDAxYnUzb3RjcWkwcTl6Y2kifQ.fILG1KajQ1DgZgYQWzG3xw'
});
map.addLayer(LayerKita);

var myStyle2 = {
    "color": "#ffff00",
    "weight": 1,
    "opacity": 0.9
};

function popUp(f, l) {
    var out = [];
    if (f.properties) {
        // for(key in f.properties){
        // 	console.log(key);

        // }
        out.push("Cluster: " + f.properties['CLUSTER']);
        out.push("Kabupaten: " + f.properties['KABKOT']);
        out.push("Kecamatan: " + f.properties['KECAMATAN']);
        out.push("Desa: " + f.properties['DESA']);
        out.push("Gizi Baik: " + f.properties['ANGKAG1']);
        out.push("Gizi Lebih: " + f.properties['ANGKAG2']);
        out.push("Gizi Kurang: " + f.properties['ANGKAG3']);
        out.push("Gizi Buruk: " + f.properties['ANGKAG4']);
        out.push("Obesitas: " + f.properties['ANGKAG5']);
        l.bindPopup(out.join("<br />"));
    }
}

// legend

function iconByName(name) {
    return '<i class="icon" style="background-color:' + name + ';border-radius:50%"></i>';
}

function featureToMarker(feature, latlng) {
    return L.marker(latlng, {
        icon: L.divIcon({
            className: 'marker-' + feature.properties.amenity,
            html: iconByName(feature.properties.amenity),
            iconUrl: '../images/markers/' + feature.properties.amenity + '.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        })
    });
}

var baseLayers = [{
        name: "OpenStreetMap",
        layer: LayerKita
    },
    {
        name: "OpenCycleMap",
        layer: L.tileLayer('http://{s}.tile.opencyclemap.org/cycle/{z}/{x}/{y}.png')
    },
    {
        name: "Outdoors",
        layer: L.tileLayer('http://{s}.tile.thunderforest.com/outdoors/{z}/{x}/{y}.png')
    }
];

<?php
    $getDesa = $db->ObjectBuilder()->get('data_desa');
    foreach ($getDesa as $row) {
    ?>

var myStyle<?= $row->id_desa ?> = {
    "color": "<?= $row->warna_desa ?>",
    "weight": 1,
    "opacity": 1
};

<?php
        $arrayKec[] = '{
			name: "' . $row->nama_desa . '",
			icon: iconByName("' . $row->warna_desa . '"),
			layer: new L.GeoJSON.AJAX(["assets/unggah/geojson/' . $row->geojson_desa . '"],{onEachFeature:popUp,style: myStyle' . $row->id_desa . ',pointToLayer: featureToMarker }).addTo(map)
			}';
    }
    ?>

var overLayers = [{
    group: "Layer Desa",
    layers: [
        <?= implode(',', $arrayKec); ?>
    ]
}];

var panelLayers = new L.Control.PanelLayers(baseLayers, overLayers, {
    collapsibleGroups: true
});

map.addControl(panelLayers);
</script>