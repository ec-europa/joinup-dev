/**
 * @file
 * Semic map renderer.
 */

L.custom = {
  init: function (obj, params) {

    // CUSTOM.
    obj.style.minHeight = "400px";

    // INIT MAP OBJECT.
    var map = L.map(obj, {"center": [48, 9], "zoom": 4});

    // TILES.
    var tileLayer = L.wt.tileLayer().addTo(map);

    // MARKERS.
    var kml = drupalSettings.semic.map.adoptersListUrl;

    var kml_options = {
      onEachFeature: function (feature, layer) {
        layer.bindInfo("<p><b>" + feature.properties.name + "</b></p>" + feature.properties.description);
      }
    };

    var markers = L.wt.markers([kml], kml_options).addTo(map);

    $wt._queue("next"); // Process next components.
  }
};
