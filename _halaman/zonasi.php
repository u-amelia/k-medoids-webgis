<?php
$title = "Zonasi";
$judul = $title;
$url = "zonasi";
$fileJs = 'markerjs';
?>

<style type="text/css">
#mapid {
    height: 80vh;
}

.icon {
    display: inline-block;
    margin: 2px;
    height: 16px;
    width: 16px;
    background-color: #ccc;
}

.icon-bar {
    background: url('assets/js/leaflet-panel-layers-master/examples/images/icons/bar.png') center center no-repeat;
}

.info {
    padding: 6px 8px;
    font: 14px/16px Arial, Helvetica, sans-serif;
    background: white;
    background: rgba(255, 255, 255, 0.8);
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
    border-radius: 5px;
}

.info h4 {
    margin: 0 0 5px;
    color: #777;
}

.legend {
    text-align: left;
    line-height: 18px;
    color: #555;
}

.legend i {
    width: 18px;
    height: 18px;
    float: left;
    margin-right: 8px;
    opacity: 0.7;
}
</style>


<div id="mapid"></div>