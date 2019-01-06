export default class Options {
  
  static DEFAULT_COLORS = ["DarkBlue", "DarkViolet", "DarkCyan", "DarkGoldenRod",
    "DarkGreen", "Darkorange", "DarkRed", "DarkSlateBlue", "DarkSlateGray", "DarkSlateGrey", 
    "DarkOliveGreen", "DarkTurquoise", "DarkViolet", "DarkSeaGreen", "DeepPink", 
    "DarkSalmon", "DeepSkyBlue", "DarkMagenta"];
  
  opts = {};
  
  constructor(opts) {
    this.opts = opts;
  }
  
  clear() {
    this.opts = {};
  }

  clone() {
    var cloned = JSON.parse(JSON.stringify(this.opts));
    return new Options(cloned);
  }

  get(name) {
    var idxs = name.split('.');
    var el = this.opts;
    for (var i = 0; i < idxs.length; i++) {
      if (el[idxs[i]]) {
        el = el[idxs[i]];
      } else {
        return null;
      }
    }
    return el;
  }
  
  delete(name) {
    var idxs = name.split('.');
    var el = this.opts;
    for (var i = 0; i < idxs.length; i++) {
      if (el[idxs[i]] && i !== idxs.length -1) {
        el = el[idxs[i]];
      } else if (el[idxs[i]] === '' || el[idxs[i]]){
        delete el[idxs[i]];
      } else {
        return;
      }
    }
  }

  set(name, value) {
    var idxs = name.split('.');
    var el = this.opts;
    for (var i = 0; i < idxs.length; i++) {
      if (i === idxs.length - 1) {
        el[idxs[i]] = value;
      } else if (typeof(el[idxs[i]]) === 'object') {
        el = el[idxs[i]];
      } else {
        el[idxs[i]] = {};
        el = el[idxs[i]];
      }
    }
  }

  getWithDefault(name, def) {
    var r = this.get(name);
    if (r === null || typeof r !== 'string') {
      return def;
    } else {
      return r;
    }
  }

  flat() {
    var res = {};
    this.flatInternal(res, "", this.opts);
    return res;
  }

  flatInternal(res, name, options) {
    for (var key in options) {
      var newName = (name.length === 0)? key : name + "." + key;
      if (typeof(options[key]) === 'object') {
        this.flatInternal(res, newName, options[key]);
      } else {
        res[newName] = options[key];
      }
    }
    return res;
  }

  getPreferredUnitDistance() {
    return this.getWithDefault('preferred.unit.distance', 'km');
  }

  getPreferredUnitAltitude() {
    return this.getWithDefault('preferred.unit.altitude', 'm');
  }

  getPreferredUnitSpeed() {
    return this.getWithDefault('preferred.unit.speed', 'm/km');
  }

  getPreferredActivityListPageSize() {
    return this.getWithDefault('preferred.activity-list.page-size', '20');
  }

  getPreferredActivityListPeriod() {
    return this.getWithDefault('preferred.activity-list.period', 'month');
  }

  getActivityCalculationPeriod() {
    return this.getWithDefault('activity.calculation.period', '10');
  }
  
  getBackgroundImage() {
    return this.getWithDefault('background.image');
  }

  getActivityLapColor(idx) {
    var colors = this.get('activity.lap.colors');
    if (colors) {
      var array = colors.split(",");
      return colors[idx % colors.length].trim();
    } else {
      return Options.DEFAULT_COLORS[idx % Options.DEFAULT_COLORS.length];
    }
  }

  getTileLayer() {
    var name = this.getWithDefault('activity.map.tilelayer', 'openstreetmap');
    // http://wiki.openstreetmap.org/wiki/Slippy_map_tilenames
    // https://gist.github.com/davidkeen/3729820
    // new tiles:
    // https://leaflet-extras.github.io/leaflet-providers/preview/
    // https://wiki.openstreetmap.org/wiki/Tile_servers
    var tileLayer;
    switch (name) {
      case 'opentopomap':
        tileLayer = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
	attribution: 'Map data: &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>, <a href="http://viewfinderpanoramas.org">SRTM</a> | Map style: &copy; <a href="https://opentopomap.org">OpenTopoMap</a> (<a href="https://creativecommons.org/licenses/by-sa/3.0/">CC-BY-SA</a>)'
        });
        break;
      case 'openmapsurfer':
        tileLayer = L.tileLayer('https://korona.geog.uni-heidelberg.de/tiles/roads/x={x}&y={y}&z={z}', {
	  attribution: 'Imagery from <a href="http://giscience.uni-hd.de/">GIScience Research Group @ University of Heidelberg</a> &mdash; Map data &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        });
        break;
      case 'hydda':
        tileLayer = L.tileLayer('https://{s}.tile.openstreetmap.se/hydda/full/{z}/{x}/{y}.png', {
	  attribution: 'Tiles courtesy of <a href="http://openstreetmap.se/" target="_blank">OpenStreetMap Sweden</a> &mdash; Map data &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        });
        break;
      case 'esri':
        tileLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}', {
	  attribution: 'Tiles &copy; Esri &mdash; Source: Esri, DeLorme, NAVTEQ, USGS, Intermap, iPC, NRCAN, Esri Japan, METI, Esri China (Hong Kong), Esri (Thailand), TomTom, 2012'
        });
        break;
      case 'esri.worldimagery':
        tileLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
          type: 'sat',
	  attribution: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community'
        });
        break;
      case 'cartodb':
        tileLayer = L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
	  attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/attributions">CARTO</a>',
	  subdomains: 'abcd', 
        });
        break;
      case 'wikimedia':
        tileLayer = L.tileLayer('https://maps.wikimedia.org/osm-intl/{z}/{x}/{y}{r}.png', {
	  attribution: '<a href="https://wikimediafoundation.org/wiki/Maps_Terms_of_Use">Wikimedia</a>',
        });
        break;
      case 'opencyclemap':
        tileLayer = L.tileLayer('http://{s}.tile.opencyclemap.org/cycle/{z}/{x}/{y}.png', {
          attribution: '&copy; OpenCycleMap, Map data &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        });
        break;
      
      default:
        var tileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
	  attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        });
        break; 
    }
    return tileLayer;
  }
  
  getDistance(value) {
    var res;
    switch (this.getPreferredUnitDistance()) {
      case 'm':
        res = parseFloat(value);
        break;
      case 'mile':
        res = value / 1610.0;
        break;
      default: // 'km'
        res = value / 1000.0;
    }
    return res;
  }

  getDistanceUnit(value) {
    return this.getDistance(value).toFixed(3) + this.getPreferredUnitDistance();
  }

  getAltitude(value) {
    var res;
    switch (this.getPreferredUnitAltitude()) {
      case 'mile':
        res = value / 1610.0;
        break;
      case 'km':
        res = value / 1000.0;
      default:
        res = parseFloat(value);
        break;
    }
    return res;
  }

  getAltitudeUnit(value) {
    return this.getAltitude(value).toFixed(3) + this.getPreferredUnitAltitude();
  }

  getTime(value) {
    var seconds = parseInt(value) % 60;
    var res = '' + (seconds < 10? '0' + seconds:seconds) + 's';
    if (value > 60) {
      var minutes = parseInt(value / 60) % 60;
      res = '' + (minutes < 10? '0' + minutes:minutes) + 'm' + res;
      if (value > 60 * 60) {
        var hours = parseInt(value / (60 * 60)) % 24;
        res = '' + hours + 'h' + res;
        if (value > 60 * 60 * 24) {
          var days = parseInt(value / (60 * 60 * 24));
          res = '' + days + 'd' + res;
        }
      }
    }
    return res;
  }

  getDateTime(value) {
    var date = new Date(Date.parse(value));
    return date.toGMTString();
  }

  getSpeed(value) {
    var res;
    switch(this.getPreferredUnitSpeed()) {
      case 'm/s':
        res = parseFloat(value);
        break;
      case 'km/h':
        res = value * 3600.0 / 1000.0;
        break;
      case 'mile/h':
        res = value * 3600.0 / 1610.0;
        break;
      default:
        // m/km
        if (value <= 0) {
          res = 0;
        } else {
          res = 1000.0 / (value * 60.0);
        }
    }
    return res;
  }

  getSpeedUnit(value) {
    var speed = this.getSpeed(value);
    if (this.getPreferredUnitSpeed() === 'm/km') {
      //var min = 1000.0 / (speed * 60.0);
      //speed = '' +  parseInt(min) + "." + Math.round(60*(min - parseInt(min)));
      var min = Math.floor(speed);
      var sec = Math.round((speed % 1) * 60);
      if (sec === 60) {
        sec = 0;
        min += 1;
      }
      return min + "." + ((sec<10)? "0" + sec : sec) + this.getPreferredUnitSpeed();
    } else {
      return speed.toFixed(3) + this.getPreferredUnitSpeed();
    }
  }

  getHeartRate(value) {
    return parseInt(value);
  }

  getHeartRateUnit(value) {
    return '' + this.getHeartRate(value) + 'bpm';
  }
}