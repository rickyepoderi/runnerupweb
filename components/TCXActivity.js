import $ from 'jquery';
import L from 'leaflet';
  
export default class Options {

  constructor(id, mapName, opts, data) {
    this.opts = opts;
    this.mapName = mapName;
    this.polylines = [];
    this.lapMarkers = [];
    this.startMarker = null;
    this.clickPopup = L.popup();
    this.map = null;
    this.data = this.activityXmlToJson(data.getElementsByTagName('Activity')[0]);
    this.joinTracks();
    this.calculateMagnitudes();
  }

  calculateAverageSpeedTrackpoint(trackNow, trackPrev) {
    if (trackNow.lap !== trackPrev.lap || trackNow.trackpoint !== trackPrev.trackpoint) {
        var meters = this.data.Lap[trackNow.lap].Track.Trackpoint[trackNow.trackpoint].DistanceMeters
                - this.data.Lap[trackPrev.lap].Track.Trackpoint[trackPrev.trackpoint].DistanceMeters;
        var seconds = (Date.parse(this.data.Lap[trackNow.lap].Track.Trackpoint[trackNow.trackpoint].Time)
                - Date.parse(this.data.Lap[trackPrev.lap].Track.Trackpoint[trackPrev.trackpoint].Time)) / 1000;
        return meters / seconds;
    } else {
        return 0.0;
    }
  }

  avancePreviousTrackpoint = function (trackNow, trackPrev, period) {
    var now = Date.parse(this.data.Lap[trackNow.lap].Track.Trackpoint[trackNow.trackpoint].Time);
    var seconds = (now - Date.parse(this.data.Lap[trackPrev.lap].Track.Trackpoint[trackPrev.trackpoint].Time)) / 1000;
    while (seconds > period) {
        trackPrev.trackpoint++;
        if (trackPrev.trackpoint >= this.data.Lap[trackPrev.lap].Track.Trackpoint.length) {
            trackPrev.trackpoint = 0;
            trackPrev.lap++;
        }
        seconds = (now - Date.parse(this.data.Lap[trackPrev.lap].Track.Trackpoint[trackPrev.trackpoint].Time)) / 1000;
    }
  }

  drawPopupMagnitude(div, container, magnitude, title, url, converterMethod) {
    if (container && container.hasOwnProperty(magnitude) && container[magnitude] != 0) {
        var img = $('<img>', {src: url, alt: title, title: title, width: 10});
        div.append(img);
        div.append('&nbsp;&nbsp;');
        div.append(converterMethod ? converterMethod(container[magnitude]) : container[magnitude]);
        div.append($('<br>'));
    }
  }

  pointMessage(track) {
    var content = $('<div/>');
    this.drawPopupMagnitude(content, track, 'Time', 'Time', 'resources/open-iconic/svg-black/clock.svg', (function(time) {
        return this.opts.getTime((Date.parse(time) - Date.parse(this.data.Lap[0].Track.Trackpoint[0].Time)) / 1000);
    }).bind(this));
    this.drawPopupMagnitude(content, track, 'DistanceMeters', 'Distance', 'resources/open-iconic/svg-black/flag.svg', this.opts.getDistanceUnit.bind(this.opts));
    this.drawPopupMagnitude(content, track, 'AverageSpeed', 'Avg. Speed', 'resources/open-iconic/svg-black/graph.svg', this.opts.getSpeedUnit.bind(this.opts));
    this.drawPopupMagnitude(content, track, 'AltitudeMeters', 'Altitude', 'resources/open-iconic/svg-black/arrow-circle-top.svg', this.opts.getAltitudeUnit.bind(this.opts));
    this.drawPopupMagnitude(content, track.HeartRateBpm, 'Value', 'Heart Rate', 'resources/open-iconic/svg-black/heart.svg', this.opts.getHeartRateUnit.bind(this.opts));
    return content;
  }

  lapMessage(l) {
    var lap = this.data.Lap[l];
    var content = $('<div/>');
    this.drawPopupMagnitude(content, lap, 'DistanceMeters', 'Distance', 'resources/open-iconic/svg-black/flag.svg', this.opts.getDistanceUnit.bind(this.opts));
    this.drawPopupMagnitude(content, lap, 'AverageSpeed', 'Avg. Speed', 'resources/open-iconic/svg-black/graph.svg', this.opts.getSpeedUnit.bind(this.opts));
    this.drawPopupMagnitude(content, lap, 'MaximumSpeed', 'Max. Speed', 'resources/open-iconic/svg-red/graph.svg', this.opts.getSpeedUnit.bind(this.opts));
    this.drawPopupMagnitude(content, lap, 'MinimumAltitude', 'Min. Altitude', 'resources/open-iconic/svg-black/arrow-circle-bottom.svg', this.opts.getAltitudeUnit.bind(this.opts));
    this.drawPopupMagnitude(content, lap, 'MaximumAltitude', 'Max. Altitude', 'resources/open-iconic/svg-red/arrow-circle-top.svg', this.opts.getAltitudeUnit.bind(this.opts));
    this.drawPopupMagnitude(content, lap.AverageHeartRateBpm, 'Value', 'Avg. Heart Rate', 'resources/open-iconic/svg-black/heart.svg', this.opts.getHeartRateUnit.bind(this.opts));
    this.drawPopupMagnitude(content, lap.MaximumHeartRateBpm, 'Value', 'Max. Heart Rate', 'resources/open-iconic/svg-red/heart.svg', this.opts.getHeartRateUnit.bind(this.opts));
    return content;
  }

  activityMessage() {
    var act = this.data;
    var content = $('<div/>');
    this.drawPopupMagnitude(content, act, 'Sport', 'Sport', 'resources/open-iconic/svg-black/pin.svg');
    this.drawPopupMagnitude(content, act, 'TotalTimeSeconds', 'Time', 'resources/open-iconic/svg-black/clock.svg', this.opts.getTime.bind(this.opts));
    this.drawPopupMagnitude(content, act, 'DistanceMeters', 'Distance', 'resources/open-iconic/svg-black/flag.svg', this.opts.getDistanceUnit.bind(this.opts));
    this.drawPopupMagnitude(content, act, 'AverageSpeed', 'Avg. Speed', 'resources/open-iconic/svg-black/graph.svg', this.opts.getSpeedUnit.bind(this.opts));
    this.drawPopupMagnitude(content, act, 'MaximumSpeed', 'Max. Speed', 'resources/open-iconic/svg-red/graph.svg', this.opts.getSpeedUnit.bind(this.opts));
    this.drawPopupMagnitude(content, act, 'Calories', 'Calories', 'resources/open-iconic/svg-red/calculator.svg');
    this.drawPopupMagnitude(content, act, 'MinimumAltitude', 'Min. Altitude', 'resources/open-iconic/svg-black/arrow-circle-bottom.svg', this.opts.getAltitudeUnit.bind(this.opts));
    this.drawPopupMagnitude(content, act, 'MaximumAltitude', 'Max. Altitude', 'resources/open-iconic/svg-red/arrow-circle-top.svg', this.opts.getAltitudeUnit.bind(this.opts));
    this.drawPopupMagnitude(content, act, 'AverageHeartRateBpm', 'Avg. Heart Rate', 'resources/open-iconic/svg-black/heart.svg', this.opts.getHeartRateUnit.bind(this.opts));
    this.drawPopupMagnitude(content, act, 'MaximumHeartRateBpm', 'Max. Heart Rate', 'resources/open-iconic/svg-red/heart.svg', this.opts.getHeartRateUnit.bind(this.opts));
    return content;
  }

  distance(a, b) {
    return Math.sqrt(Math.pow(b.lat - a.lat, 2) + Math.pow(b.lng - a.lng, 2));
  }

  onPolylineClick(e) {
    // search the polyline clicked
    var id = e.target._leaflet_id;
    for (var l = 0; l < this.polylines.length; l++) {
        if (id === this.polylines[l]._leaflet_id) {
            break;
        }
    }
    if (l < this.polylines.length) {
        var min = this.polylines[l]._latlngs[0];
        var dist = this.distance(e.latlng, min);
        var idx = 0;
        for (var i = 1; i < this.polylines[l]._latlngs.length; i++) {
            var d = this.distance(e.latlng, this.polylines[l]._latlngs[i]);
            if (d < dist) {
                idx = i;
                dist = d;
                min = this.polylines[l]._latlngs[i];
            }
        }
        this.showPopup(this.data.Lap[l].Track.Trackpoint[idx]);
    }
  }

  showPopup(track) {
    var content = this.pointMessage(track);
    this.clickPopup.setLatLng([track.Position.LatitudeDegrees, track.Position.LongitudeDegrees]).setContent(content[0]).openOn(this.map);
  }

  joinTracks() {
    for (var l = 0; l < this.data.Lap.length; l++) {
        // check if the Track is an arrany to merge it into just one Track
        if (Array.isArray(this.data.Lap[l].Track)) {
            var all = this.data.Lap[l].Track[0].Trackpoint;
            for (var i = 1; i < this.data.Lap[l].Track.length; i++) {
                Array.prototype.push.apply(all, this.data.Lap[l].Track[i].Trackpoint);
            }
            this.data.Lap[l].Track = {};
            this.data.Lap[l].Track.Trackpoint = all;
        }
    }
  }

  calculateMagnitudes = function() {
    // data that depends on laps
    this.data.TotalTimeSeconds = 0;
    this.data.DistanceMeters = 0;
    this.data.AverageHeartRateBpm = 0;
    this.data.MaximumHeartRateBpm = 0;
    this.data.MaximumSpeed = 0;
    this.data.Calories = 0;
    for (var l = 0; l < this.data.Lap.length; l++) {
        if (this.data.Lap[l].TotalTimeSeconds) {
            this.data.TotalTimeSeconds += parseInt(this.data.Lap[l].TotalTimeSeconds);
        }
        if (this.data.Lap[l].DistanceMeters) {
            this.data.DistanceMeters += parseInt(this.data.Lap[l].DistanceMeters);
        }
        if (this.data.Lap[l].AverageHeartRateBpm && this.data.Lap[l].TotalTimeSeconds) {
            this.data.AverageHeartRateBpm += (parseInt(this.data.Lap[l].AverageHeartRateBpm.Value) * parseInt(this.data.Lap[l].TotalTimeSeconds));
        }
        if (this.data.Lap[l].MaximumHeartRateBpm && parseInt(this.data.Lap[l].MaximumHeartRateBpm.Value) > this.data.MaximumHeartRateBpm) {
            this.data.MaximumHeartRateBpm = parseInt(this.data.Lap[l].MaximumHeartRateBpm.Value);
        }
        if (this.data.Lap[l].Calories) {
            this.data.Calories += parseInt(this.data.Lap[l].Calories);
        }
        // calculate the average speed for the lap
        if (this.data.Lap[l].TotalTimeSeconds && this.data.Lap[l].DistanceMeters) {
            this.data.Lap[l].AverageSpeed = this.data.Lap[l].DistanceMeters / this.data.Lap[l].TotalTimeSeconds;
        }
        // select the lap at the beginning
        this.data.Lap[l].selected = true;
    }
    if (this.data.AverageHeartRateBpm > 0 && this.data.TotalTimeSeconds > 0) {
        this.data.AverageHeartRateBpm = parseInt(this.data.AverageHeartRateBpm / this.data.TotalTimeSeconds);
    }
    // calculated avg speed exercise
    if (this.data.TotalTimeSeconds && this.data.DistanceMeters) {
        this.data.AverageSpeed = this.data.DistanceMeters / this.data.TotalTimeSeconds;
    }
    // data that depends on Trackpoints
    this.data.MinimumAltitude = this.data.Lap[0].Track.Trackpoint[0].AltitudeMeters;
    this.data.MaximumAltitude = this.data.Lap[0].Track.Trackpoint[0].AltitudeMeters;
    var prev = {lap: 0, trackpoint: 0};
    var calcPeriod = this.opts.getActivityCalculationPeriod();
    this.startMarker = L.marker([
        this.data.Lap[0].Track.Trackpoint[0].Position.LatitudeDegrees,
        this.data.Lap[0].Track.Trackpoint[0].Position.LongitudeDegrees
    ]);
    for (var l = 0; l < this.data.Lap.length; l++) {
        this.data.Lap[l].MinimumAltitude = this.data.Lap[l].Track.Trackpoint[0].AltitudeMeters;
        this.data.Lap[l].MaximumAltitude = this.data.Lap[l].Track.Trackpoint[0].AltitudeMeters;
        this.polylines[l] = L.polyline([], {color: this.opts.getActivityLapColor(l)});
        this.polylines[l].on('click', this.onPolylineClick.bind(this));
        var icon = L.divIcon({className: 'ldap-icon', 
            html: '<span style="color:' + this.opts.getActivityLapColor(l) + '">' + (l+1) + '</span>', 
            iconSize: '20'});
        this.lapMarkers[l] = L.marker([this.data.Lap[l].Track.Trackpoint[this.data.Lap[l].Track.Trackpoint.length - 1].Position.LatitudeDegrees,
                this.data.Lap[l].Track.Trackpoint[this.data.Lap[l].Track.Trackpoint.length - 1].Position.LongitudeDegrees], {icon: icon});
        for (var t = 0; t < this.data.Lap[l].Track.Trackpoint.length; t++) {
            if (typeof(this.data.Lap[l].Track.Trackpoint[t].Position) === "undefined") {
                // incorrect trackpooint
                this.data.Lap[l].Track.Trackpoint.splice(t, 1);
                t--;
                continue;
            }
            var altitude = parseInt(this.data.Lap[l].Track.Trackpoint[t].AltitudeMeters);
            if (altitude < parseInt(this.data.Lap[l].MinimumAltitude)) {
                this.data.Lap[l].MinimumAltitude = altitude;
            }
            if (altitude > parseInt(this.data.Lap[l].MaximumAltitude)) {
                this.data.Lap[l].MaximumAltitude = altitude;
            }
            // calculate trackpoint average speed
            this.data.Lap[l].Track.Trackpoint[t].AverageSpeed = this.calculateAverageSpeedTrackpoint({lap: l, trackpoint: t}, prev);
            // create the poont in the polylines
            this.polylines[l].addLatLng([
                this.data.Lap[l].Track.Trackpoint[t].Position.LatitudeDegrees,
                this.data.Lap[l].Track.Trackpoint[t].Position.LongitudeDegrees
              ]);
            // avance the previous point in order to maintain period
            this.avancePreviousTrackpoint({lap: l, trackpoint: t}, prev, calcPeriod);
        }
        if (parseInt(this.data.MinimumAltitude) > parseInt(this.data.Lap[l].MinimumAltitude)) {
            this.data.MinimumAltitude = this.data.Lap[l].MinimumAltitude;
        }
        if (parseInt(this.data.MaximumAltitude) < parseInt(this.data.Lap[l].MaximumAltitude)) {
            this.data.MaximumAltitude = this.data.Lap[l].MaximumAltitude;
        }
    }
  }

  clickLap(e) {
    var id = e.target._leaflet_id;
    for (var i in this.lapMarkers) {
        if (id === this.lapMarkers[i]._leaflet_id) {
            this.switchLap(i);
            break;
        }
    }
  }

  clickStart(e) {
    for (var i in this.data.Lap) {
        this.switchLap(i, true);
    }
  }

  prepareMap() {
    // TODO: use som try / catch to check Track and Trackpoints
    L.Icon.Default.imagePath = 'resources/images/';
    var x = this.data.Lap[0].Track.Trackpoint[0].Position.LatitudeDegrees;
    var y = this.data.Lap[0].Track.Trackpoint[0].Position.LongitudeDegrees;
    this.map = L.map(this.mapName).setView([x, y], 17);
    /*// openstreetmap
    // TODO: set the tile layer => 
    //    http://wiki.openstreetmap.org/wiki/Slippy_map_tilenames
    //    https://gist.github.com/davidkeen/3729820
    var osmAttr = '&copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>';
    L.tileLayer(
     'http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
     attribution: osmAttr,
     }).addTo(this.map);
    // opencyclemap
    L.tileLayer(
            'http://{s}.tile.opencyclemap.org/cycle/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenCycleMap, ' + 'Map data ' + osmAttr
            }).addTo(map);*/
    // MapQuest
    /*var mqTilesAttr = 'Tiles &copy; <a href="http://www.mapquest.com/" target="_blank">MapQuest</a> <img src="http://developer.mapquest.com/content/osm/mq_logo.png" />';
     L.tileLayer(
     'http://otile{s}.mqcdn.com/tiles/1.0.0/osm/{z}/{x}/{y}.jpg', {
     subdomains: '1234',
     type: 'osm',
     attribution: 'Map data ' + L.TileLayer.OSM_ATTR + ', ' + mqTilesAttr
     }).addTo(map);*/
    // MapQuest Open Aerial (only higher tiles)
    /*L.tileLayer(
     'http://otile{s}.mqcdn.com/tiles/1.0.0/sat/{z}/{x}/{y}.jpg', {
     subdomains: '1234',
     type: 'sat',
     attribution: 'Imagery &copy; NASA/JPL-Caltech and U.S. Depart. of Agriculture, Farm Service Agency, ' + mqTilesAttr
     }).addTo(map);*/
    this.opts.getTileLayer().addTo(this.map);
    var bounds = null;
    this.startMarker.bindPopup(this.activityMessage()[0]);
    this.startMarker.addTo(this.map);
    this.startMarker.on("dblclick", this.clickStart.bind(this));
    this.startMarker.on("doubletap", this.clickStart.bind(this));
    for (var i = 0; i < this.data.Lap.length; i++) {
        this.polylines[i].addTo(this.map);
        this.lapMarkers[i].addTo(this.map);
        this.lapMarkers[i].bindPopup(this.lapMessage(i)[0]);
        this.lapMarkers[i].on("dblclick", this.clickLap.bind(this));
        this.lapMarkers[i].on("doubletap", this.clickLap.bind(this));
        if (bounds) {
            bounds.extend(this.polylines[i].getBounds());
        } else {
            bounds = this.polylines[i].getBounds();
        }
    }
    this.map.fitBounds(bounds);
  }

  switchLap(lap, value) {
    if (typeof(value) === 'undefined') {
        value = !(this.data.Lap[lap].selected);
    }
    if (lap < this.data.Lap.length && this.data.Lap[lap].selected !== value) {
        this.data.Lap[lap].selected = value;
        if (this.data.Lap[lap].selected) {
            this.polylines[lap].addTo(this.map);
            this.lapMarkers[lap].addTo(this.map);
        } else {
            for (var i in this.map._layers) {
                // only delete the polyline, not the marker
                if (this.map._layers[i]._leaflet_id === this.polylines[lap]._leaflet_id) {
                    this.map.removeLayer(this.map._layers[i]);
                }
            }
        }
    }
  }

  locateTrackByIndex(idx) {
    for (var l in this.data.Lap) {
        if (idx < this.data.Lap[l].Track.Trackpoint.length) {
            return this.data.Lap[l].Track.Trackpoint[idx];
        } else {
            idx = idx - this.data.Lap[l].Track.Trackpoint.length;
        }
    }
  }

  locateLapByIndex(idx) {
    for (var l in this.data.Lap) {
        if (idx < this.data.Lap[l].Track.Trackpoint.length) {
            return l;
        } else {
            idx = idx - this.data.Lap[l].Track.Trackpoint.length;
        }
    }
  }

  locateLapByTime(time) {
    for (var l in this.data.Lap) {
      var start = Date.parse(this.data.Lap[l].Track.Trackpoint[0].Time);
      var end = Date.parse(this.data.Lap[l].Track.Trackpoint[this.data.Lap[l].Track.Trackpoint.length - 1].Time);
      if (time >= start && time <= end) {
        return l;
      }
    }
  }
  
  locateTrackByTime(time, lap) {
    if (!lap) {
      lap = this.locateLapByTime(time);
    }
    if (!lap) {
      return;
    }
    var l = this.data.Lap[lap];
    var start = 0;
    var end = l.Track.Trackpoint.length - 1;
    var middle = start + Math.floor((end - start) / 2);
    while (start !== middle) {
      var middleTime = Date.parse(l.Track.Trackpoint[middle].Time);
      if (time >= middleTime) {
        start = middle;
      } else {
        end = middle;
      }
      middle = start + Math.floor((end - start) / 2);
    }
    return l.Track.Trackpoint[start];
  }

  getArray(converter, attrArray) {
    var array = [];
    for (var l in this.data.Lap) {
      for (var t in this.data.Lap[l].Track.Trackpoint) {
        var val = this.data.Lap[l].Track.Trackpoint[t];
        for (var i in attrArray) {
          if (typeof(val) !== 'undefined') {
            val = val[attrArray[i]];
          }
        }
        if (typeof(val) === 'undefined') {
          val = 0;
        } else {
          val = converter(parseFloat(val));
        }
        array.push([new Date(Date.parse(this.data.Lap[l].Track.Trackpoint[t].Time)), val]);
      }
    }
    return array;
  }

  activityXmlToJson(xml) {
    // create the empty value checker
    var contentsre = /^\s*$/;
    // Create the return object
    var obj = {};
    if (xml.nodeType === 1) { 
        // element => do attributes
        if (xml.attributes.length > 0) {
            for (var j = 0; j < xml.attributes.length; j++) {
                var attribute = xml.attributes.item(j);
                obj[attribute.nodeName] = attribute.nodeValue;
            }
        }
    } else if (xml.nodeType === 3) {
        // text
        obj = xml.nodeValue;
    }
    // do children
    if (xml.hasChildNodes()) {
        for (var i = 0; i < xml.childNodes.length; i++) {
            var item = xml.childNodes.item(i);
            var nodeName = item.nodeName;
            if (item.nodeType === 3 && contentsre.test(item.nodeValue)) {
                // empty string
                continue;
            } else if (item.nodeType === 3 && Object.keys(obj).length === 0) {
                // text of the node, empty for the moment and no empty value (return and whitespaces)
                obj = item.nodeValue;
            } else if (typeof (obj[nodeName]) === "undefined" && nodeName !== 'Lap' && nodeName !== 'Trackpoint') {
                // assign the value as normal object
                obj[nodeName] = this.activityXmlToJson(item);
            } else {
                // assign the value as array
                if (typeof (obj[nodeName]) === "undefined") {
                    // empty array
                    obj[nodeName] = [];
                } else if (typeof (obj[nodeName].push) === "undefined") {
                    // from object to array
                    var old = obj[nodeName];
                    obj[nodeName] = [];
                    obj[nodeName].push(old);
                }
                obj[nodeName].push(this.activityXmlToJson(item));
            }
        }
    }
    return obj;
  }
}