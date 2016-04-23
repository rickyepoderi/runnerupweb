/*
 * Copyright (c) 2016 ricky <https://github.com/rickyepoderi/runnerupweb>
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

(function($){

  $.event.special.doubletap = {
    bindType: 'touchend',
    delegateType: 'touchend',

    handle: function(event) {
      var handleObj   = event.handleObj,
          targetData  = jQuery.data(event.target),
          now         = new Date().getTime(),
          delta       = targetData.lastTouch ? now - targetData.lastTouch : 0,
          delay       = delay == null ? 300 : delay;

      if (delta < delay && delta > 30) {
        targetData.lastTouch = null;
        event.type = handleObj.origType;
        ['clientX', 'clientY', 'pageX', 'pageY'].forEach(function(property) {
          event[property] = event.originalEvent.changedTouches[0][property];
        });

        // let jQuery handle the triggering of "doubletap" event handlers
        handleObj.handler.apply(this, arguments);
      } else {
        targetData.lastTouch = now;
      }
    }
  };

})(jQuery);

//
// message 

function message(mess, type) {
    var messageBox = $('#message-box');
    var imgClose;
    var messageSpan;
    if (messageBox.length === 0) {
        // create the box
        messageBox = $("<div>", {id: "message-box", class: "message"});
        imgClose = $("<img>", {id: "message-close", class: "close", alt: "X"});
        imgClose.on('click', function () {
            $("#message-box").stop(true, true);
            $('#message-box').fadeOut('fast');
        });
        messageBox.append(imgClose);
        messageSpan = $("<span>", {id: "message-span"});
        messageBox.append(messageSpan);
        $("body").append(messageBox);
    } else {
        imgClose = $('#message-close');
        messageSpan = $('#message-span');
    }
    var image, color;
    switch (type) {
        case 'warning':
            image = 'resources/open-iconic/svg-yellow/warning.svg';
            color = 'yellow';
            break;
        case 'info':
            image = 'resources/open-iconic/svg-white/warning.svg';
            color = 'white';
            break;
        default:
            image = 'resources/open-iconic/svg-red/ban.svg';
            color = 'red';
    }
    $("#password").val("");
    $("#message-span").html(mess);
    messageBox.css('background', '#003366 url("' + image + '") 15px 20px / 16px no-repeat');
    messageBox.css('color', color);
    imgClose.attr("src", 'resources/open-iconic/svg-' + color + "/x.svg");
    messageBox.fadeIn('slow');
    messageBox.stop(true, true);
    messageBox.delay(8000).fadeOut('slow');
}

//
// showPopupMenu
function showPopupMenu(user) {
    var $popup = $('#div-popup');
    if ($popup.length === 0) {
        // append it
        $popup = $("<div>", {id: "div-popup", class: "popupmenu"});
        var $menu = $('#div-menu');
        $popup.css({left: $menu.offset().left + $menu.width(), top: $menu.offset().top + $menu.height() + 2});
        // index
        $popup.append($("<a>", {text: 'Index', title: 'Index', href: 'index.html'}
        ).css({background: 'url(resources/open-iconic/svg-white/home.svg) left center/14px no-repeat'}));
        $popup.append('<br>');
        $('body').append($popup);
        // user
        $popup.append($("<a>", {text: 'User information', title: 'User information', href: 'user.html'}
        ).css({background: 'url(resources/open-iconic/svg-white/person.svg) left center/14px no-repeat'}));
        $popup.append('<br>');
        $('body').append($popup);
        // user management if admin
        if (user && user.info && user.info.role && user.info.role === 'ADMIN') {
            $popup.append($("<a>", {text: 'User Management', title: 'User Management', href: 'users.html'}
            ).css({background: 'url(resources/open-iconic/svg-white/people.svg) left center/14px no-repeat'}));
            $popup.append('<br>');
            $('body').append($popup);
        }
        // options
        $popup.append($("<a>", {text: 'Options', title: 'Options', href: 'useroptions.html'}
        ).css({background: 'url(resources/open-iconic/svg-white/puzzle-piece.svg) left center/14px no-repeat'}));
        $popup.append('<br>');
        $('body').append($popup);
        // upload a TCX file
        $popup.append($("<a>", {text: 'Upload', title: 'Options', href: 'javascript:void(0)', onclick: 'new SelectDisplay().upload();$(\'#div-popup\').hide();'}
            ).css({background: 'url(resources/open-iconic/svg-white/cloud-upload.svg) left center/14px no-repeat'}));
        $popup.append('<br>');
        $('body').append($popup);
        // logout
        $popup.append($("<a>", {text: 'Logout', title: 'Logout', href: 'javascript:void(0)', onclick: 'opts.logout()'}
        ).css({background: 'url(resources/open-iconic/svg-white/account-logout.svg) left center/14px no-repeat'}));
        $popup.append('<br>');
        $('body').append($popup);
    } else {
        // just toggle visibility
        $popup.toggle();
    }
}

//
// Date toXMLString

Date.prototype.toXMLString = function() {
    var string = '';
    var intVal;
    // year
    string = string + this.getFullYear() + '-';
    // month
    intVal = this.getMonth() + 1;
    if (intVal < 10) {
        string = string + '0' + intVal + '-';
    } else {
        string = string + intVal + '-';
    }
    // day
    intVal = this.getDate();
    if (intVal < 10) {
        string = string + '0' + intVal;
    } else {
        string = string + intVal;
    }
    // T
    string = string + 'T';
    // hours
    intVal = this.getHours();
    if (intVal < 10) {
        string = string + '0' + intVal + ':';
    } else {
        string = string + intVal + ':';
    }
    // minutes
    intVal = this.getMinutes();
    if (intVal < 10) {
        string = string + '0' + intVal + ':';
    } else {
        string = string + intVal + ':';
    }
    // seconds
    intVal = this.getSeconds();
    if (intVal < 10) {
        string = string + '0' + intVal;
    } else {
        string = string + intVal;
    }
    // Z
    string = string + 'Z';
    return string;
};

//
// CLient class

function Client(url){ 
	this.url = url;
} 

Client.prototype.login = function(username, passwd, onSuccess, onError) {
    var data = {login: username, password: passwd};
    $.ajax({
        url: this.url,
        type: 'post',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: onSuccess,
        error: onError
    });
};

Client.prototype.logout = function (onSuccess, onError) {
    $.ajax({
        url: this.url,
        type: 'get',
        contentType: 'application/json',
        success: onSuccess,
        error: onError
    });
};

Client.prototype.searchActivities = function(start, end, offset, limit, onSuccess, onError) {
    var url = this.url;
    url = url + '?start=' +  start.toXMLString();
        
    if (end) {
        url = url + '&end=' +  end.toXMLString();
    }
    if (offset) {
        url = url + '&offset=' +  offset;
    }
    if (limit) {
        url = url + '&limit=' +  limit;
    }
    $.ajax({
        url: url,
        type: 'get',
        contentType: 'application/json',
        success: onSuccess,
        error: onError
    });
};

Client.prototype.searchUsers = function(op, login, firstname, lastname, offset, limit, onSuccess, onError) {
    var url = this.url;
    var first = true;
    if (op) {
        url = url + ((first)?'?':'&') + 'op=' +  op;
        first = false;
    }
    if (login) {
        url = url + ((first)?'?':'&') + 'login=' +  login;
        first = false;
    }
    if (firstname) {
        url = url + ((first)?'?':'&') + 'firstname=' +  firstname;
        first = false;
    }
    if (lastname) {
        url = url + ((first)?'?':'&') + 'lastname=' +  lastname;
        first = false;
    }
    if (offset) {
        url = url + ((first)?'?':'&') + 'offset=' +  offset;
        first = false;
    }
    if (limit) {
        url = url + ((first)?'?':'&') + 'limit=' +  limit;
        first = false;
    }
    $.ajax({
        url: url,
        type: 'get',
        contentType: 'application/json',
        success: onSuccess,
        error: onError
    });
};

Client.prototype.downloadActivity = function(id, onSuccess, onError) {
    $.ajax({
        url: this.url + '?id=' + id,
        type: 'get',
        contentType: 'application/xml',
        ifModified: false,
        success: onSuccess,
        error: onError
    });
};

Client.prototype.getUserOptions = function(onSuccess, onError) {
    $.ajax({
        url: this.url,
        type: 'get',
        contentType: 'application/json',
        success: onSuccess,
        error: onError
    });
};

Client.prototype.setUserOptions = function(data, onSuccess, onError) {
    $.ajax({
        url: this.url,
        type: 'post',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: onSuccess,
        error: onError
    });
};

Client.prototype.getUserInformation = function(onSuccess, onError) {
    $.ajax({
        url: this.url,
        type: 'get',
        contentType: 'application/json',
        success: onSuccess,
        error: onError
    });
};

Client.prototype.setUserInformation = function(info, onSuccess, onError) {
    $.ajax({
        url: this.url,
        type: 'post',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify(info),
        success: onSuccess,
        error: onError
    });
};

Client.prototype.deleteUser = function(login, onSuccess, onError) {
    $.ajax({
        url: this.url + '?login=' + login,
        type: 'post',
        dataType: 'json',
        contentType: 'application/json',
        success: onSuccess,
        error: onError
    });
};

Client.prototype.upload = function(fd, onSuccess, onError) {
    $.ajax({
        url: this.url,
        type: 'post',
        processData: false,
        contentType: false,
        data: fd,
        success: onSuccess,
        error: onError
    });
};

//
// SelectDisplay

function SelectDisplay() {
    this.element = $('#overlay');
    if (this.element.length === 0) {
        this.element = $('<div>', {id: "overlay", class: "overlay"});
        $("body").append(this.element);
    }
}

SelectDisplay.prototype.initialize = function() {
    $(this.element).empty();
    var img = $('<img>', {src: 'resources/open-iconic/svg-white/x.svg'});
    img.on('click', this.clickHide.bind(this));
    this.element.append(img);
    $(this.element).fadeIn('slow');
    $(document).bind('keydown', this.hide.bind(this));
};

SelectDisplay.prototype.show = function (options, onClick) {
    this.initialize();
    for (var key in options) {
        var link = $('<a/>', {
            href: 'javascript:void(0)',
            text: options[key]
        });
        link.click({key: key, value: options[key], method: onClick}, this.click.bind(this));
        $(this.element).append(link).append('<br/>');
    }
};

SelectDisplay.prototype.confirm = function(message, onYes, onNo) {
    this.initialize();
    $(this.element).append(message).append('<br/>');
    var linkYes = $('<a/>', {href: 'javascript:void(0)', text: "Yes"});
    linkYes.click({key: "yes", value: "yes", method: onYes}, this.click.bind(this));
    $(this.element).append(linkYes).append('&nbsp;&nbsp;&nbsp;&nbsp;');
    var linkNo = $('<a/>', {href: 'javascript:void(0)', text: "No"});
    linkNo.click({key: "no", value: "no", method: onNo}, this.click.bind(this));
    $(this.element).append(linkNo);
};

SelectDisplay.prototype.upload = function() {
    this.initialize();
    var input = $('<input/>', {type: 'file', id: 'uploadInput', accept: '.tcx', });
    $(this.element).append(input);
    $(this.element).append('&nbsp;');
    var link = $('<a/>', {href: 'javascript:void(0)', text: "Submit"});
    link.click({key: "no", value: "no", method: null}, this.doUpload.bind(this));
    $(this.element).append(link);
};

SelectDisplay.prototype.doUpload = function() {
    var input = $("#uploadInput");
    var fd = new FormData();    
    fd.append('userFiles', input.prop('files')[0]);
    new Client('rpc/json/workout/upload.php').upload(fd, this.clickHide.bind(this), this.errorMessage.bind(this));
};

SelectDisplay.prototype.clickHide = function() {
    $(this.element).fadeOut('slow');
    $(document).unbind('keydown');
};

SelectDisplay.prototype.hide = function(event) {
    if (event.keyCode === 27) {
        this.clickHide();
    }
};

SelectDisplay.prototype.click = function(event) {
    $(this.element).fadeOut('slow');
    $(document).unbind('keydown');
    if (event.data.method) {
        event.data.method(event.data.key, event.data.value);
    }
};

SelectDisplay.prototype.errorMessage = function(data) {
    if (data.status === 403) {
        sessionStorage.clear();
        $(location).attr('href', 'login.html');
    } else {
        message(data.statusText + ' (' + data.status + ')', 'error');
    }
};

//
// UserOption class

function UserOption(onLoadExecute, forDefinitions) {
    this.onLoadExecute = onLoadExecute;
    this.forDefs = forDefinitions || false;
    if (sessionStorage.getItem('user-options') && !forDefinitions) {
        this.opts = JSON.parse(sessionStorage.getItem("user-options"));
        if (this.onLoadExecute) {
            this.onLoadExecute(this);
        }
    } else {
        if (forDefinitions) {
            new Client('rpc/json/user/get_option_definitions.php').getUserOptions(this.onSuccess.bind(this), this.onError.bind(this));
        } else {
            new Client('rpc/json/user/get_options.php').getUserOptions(this.onSuccess.bind(this), this.onError.bind(this));
        }
    }
}

UserOption.DEFAULT_COLORS = ["DarkBlue", "DarkViolet", "DarkCyan", "DarkGoldenRod",
    "DarkGreen", "Darkorange", "DarkRed", "DarkSlateBlue", "DarkSlateGray", "DarkSlateGrey", 
    "DarkOliveGreen", "DarkTurquoise", "DarkViolet", "DarkSeaGreen", "DeepPink", 
    "DarkSalmon", "DeepSkyBlue", "DarkMagenta"];

UserOption.prototype.logout = function() {
    new Client('site/logout.php').logout(
            function () {
                // clear storage and go to login
                sessionStorage.clear();
                $(location).attr('href', 'login.html');
            },
            function (data) {
                if (data.status === 403) {
                    sessionStorage.clear();
                    $(location).attr('href', 'login.html');
                } else {
                    message(data.statusText + ' (' + data.status + ')', 'error');
                }
            }
    );
};

UserOption.prototype.save = function(onLoadExecute) {
    this.onLoadExecute = onLoadExecute;
    new Client('rpc/json/user/set_options.php').setUserOptions(this.opts, this.onSuccess.bind(this), this.onError.bind(this));
};

UserOption.prototype.onSuccess = function(data) {
    if (data.status === 'SUCCESS') {
        if (data.response) {
            // load => new options in response
            this.opts = data.response;
        } else {
            // save => delete any possible item in the storage cos can be dependent of opts
            sessionStorage.clear();
        }
        if (!this.forDefs) {
            sessionStorage.setItem('user-options', JSON.stringify(this.opts));
        }
        if (this.onLoadExecute) {
            this.onLoadExecute(this);
        }
    } else {
        message(data.errorMessage, 'error');
    }
};

UserOption.prototype.onError = function(data) {
    if (data.status === 403) {
        sessionStorage.clear();
        $(location).attr('href', 'login.html');
    } else {
        message(data.statusText + ' (' + data.status + ')', 'error');
    }
};

UserOption.prototype.clear = function() {
    this.opts = {};
};

UserOption.prototype.get = function(name) {
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
};

UserOption.prototype.set = function(name, value) {
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
};

UserOption.prototype.getWithDefault = function(name, def) {
    var r = this.get(name);
    if (r === null || typeof r !== 'string') {
        return def;
    } else {
        return r;
    }
};

UserOption.prototype.flat = function () {
    var res = {};
    this.flatInternal(res, "", this.opts);
    return res;
};

UserOption.prototype.flatInternal = function (res, name, options) {
    for (var key in options) {
        var newName = (name.length === 0)? key : name + "." + key;
        if (typeof(options[key]) === 'object') {
            this.flatInternal(res, newName, options[key]);
        } else {
            res[newName] = options[key];
        }
    }
    return res;
};

UserOption.prototype.getPreferredUnitDistance = function() {
    return this.getWithDefault('preferred.unit.distance', 'km');
};

UserOption.prototype.getPreferredUnitAltitude = function() {
    return this.getWithDefault('preferred.unit.altitude', 'm');
};

UserOption.prototype.getPreferredUnitSpeed = function() {
    return this.getWithDefault('preferred.unit.speed', 'm/km');
};

UserOption.prototype.getPreferredActivityListPageSize = function() {
    return this.getWithDefault('preferred.activity-list.page-size', '20');
};

UserOption.prototype.getPreferredActivityListPeriod = function() {
    return this.getWithDefault('preferred.activity-list.period', 'month');
};

UserOption.prototype.getActivityCalculationPeriod = function() {
    return this.getWithDefault('activity.calculation.period', '10');
};

UserOption.prototype.getActivityLapColor = function(idx) {
    var colors = this.get('activity.lap.colors');
    if (colors) {
        var array = colors.split(",");
        return colors[idx % colors.length].trim();
    } else {
        return UserOption.DEFAULT_COLORS[idx % UserOption.DEFAULT_COLORS.length];
    }
};

UserOption.prototype.getScatterMin = function(magnitude) {
    return this.getWithDefault('activity.graphic.' + magnitude.toLowerCase() + ".minimum", undefined);
};

UserOption.prototype.getScatterMax = function(magnitude) {
    return this.getWithDefault('activity.graphic.' + magnitude.toLowerCase() + ".maximum", undefined);
};

UserOption.prototype.getBackgroundImage = function() {
    // TODO: I have set any image, select it better
    return this.getWithDefault('background.image', 'running-573762_1280.jpg');
};

UserOption.prototype.setBackgroundImage = function () {
    // set the background image
    var html = $('html');
    html.css('background', 'url(resources/images/' + this.getBackgroundImage() + ') no-repeat center top fixed');
    html.css('-webkit-background-size', 'cover');
    html.css('-moz-background-size', 'cover');
    html.css('-o-background-size', 'cover');
    html.css('background-size', 'cover');
};


UserOption.prototype.getTileLayer = function() {
    var name = this.getWithDefault('activity.map.tilelayer', 'openstreetmap');
    // http://wiki.openstreetmap.org/wiki/Slippy_map_tilenames
    // https://gist.github.com/davidkeen/3729820
    var tileLayer;
    var osmAttr = '&copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>';
    var mqTilesAttr = 'Tiles &copy; <a href="http://www.mapquest.com/" target="_blank">MapQuest</a> <img src="http://developer.mapquest.com/content/osm/mq_logo.png" />';
    switch (name) {
        case 'opencyclemap':
            tileLayer = L.tileLayer(
                    'http://{s}.tile.opencyclemap.org/cycle/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenCycleMap, ' + 'Map data ' + osmAttr
                    });
            break;
        case 'mapquest':
            tileLayer = L.tileLayer(
                    'http://otile{s}.mqcdn.com/tiles/1.0.0/osm/{z}/{x}/{y}.jpg', {
                        subdomains: '1234',
                        type: 'osm',
                        attribution: 'Map data ' + L.TileLayer.OSM_ATTR + ', ' + mqTilesAttr
                    });
            break;
        case 'mapquest-openaereal':
            tileLayer = L.tileLayer(
                    'http://otile{s}.mqcdn.com/tiles/1.0.0/sat/{z}/{x}/{y}.jpg', {
                        subdomains: '1234',
                        type: 'sat',
                        attribution: 'Imagery &copy; NASA/JPL-Caltech and U.S. Depart. of Agriculture, Farm Service Agency, ' + mqTilesAttr
                    });
            break;
        default:
            tileLayer = L.tileLayer(
                    'http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: osmAttr
                    });
            break; 
    }
    return tileLayer;
};

//
// Converter

function Converter(opts) {
    this.opts = opts;
}

Converter.prototype.getDistance = function(value) {
    var res;
    switch (this.opts.getPreferredUnitDistance()) {
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
};

Converter.prototype.getDistanceUnit = function(value) {
    return this.getDistance(value).toFixed(3) + this.opts.getPreferredUnitDistance();
};

Converter.prototype.getAltitude = function (value) {
    var res;
    switch (this.opts.getPreferredUnitAltitude()) {
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
};

Converter.prototype.getAltitudeUnit = function (value) {
    return this.getAltitude(value).toFixed(3) + this.opts.getPreferredUnitAltitude();
};

Converter.prototype.getTime = function(value) {
    var seconds = parseInt(value) % 60;
    var res = '' + (seconds < 10? '0' + seconds:seconds) + 's';
    var minutes = parseInt(value / 60) % 60;
    if (minutes > 0) {
        res = '' + (minutes < 10? '0' + minutes:minutes) + 'm' + res;
        var hours = parseInt(value / (60 * 60));
        if (hours > 0) {
            res = '' + hours + 'h' + res;
        }
    }
    return res;
};

Converter.prototype.getDateTime = function(value) {
    var date = new Date(Date.parse(value));
    return date.toGMTString();
};

Converter.prototype.getSpeed = function(value) {
    var res;
    switch(this.opts.getPreferredUnitSpeed()) {
        case 'm/s':
            res = parseFloat(value);
            break;
        case 'km/h':
            res = value * 3600.0 / 1000.0;
            break;
        case 'mile/h':
            res = value * 3600.0 / 1610.0;
        default:
            // m/km
            if (value <= 0) {
                res = 0;
            } else {
                res = 1000.0 / (value * 60.0);
            }
    }
    return res;
};

Converter.prototype.getSpeedUnit = function(value) {
    var speed = this.getSpeed(value);
    if (this.opts.getPreferredUnitSpeed() === 'm/km') {
        //var min = 1000.0 / (speed * 60.0);
        //speed = '' +  parseInt(min) + "." + Math.round(60*(min - parseInt(min)));
        var min = Math.floor(speed);
        var sec = Math.round((speed % 1) * 60);
        if (sec === 60) {
            sec = 0;
            min += 1;
        }
        return min + "." + ((sec<10)? "0" + sec : sec) + this.opts.getPreferredUnitSpeed();
    } else {
        return speed.toFixed(3) + this.opts.getPreferredUnitSpeed();
    }
};

Converter.prototype.getHeartRate = function(value) {
    return parseInt(value);
};

Converter.prototype.getHeartRateUnit = function(value) {
    return '' + this.getHeartRate(value) + 'bpm';
};

//
// ActivityList - the object to use in th eindex page with activity list

function ActivityList(print, opts, json) {
    if (json) {
        this.start = new Date(json.start);
        this.end = new Date(json.end);
        this.activities = json.activities;
        this.period = json.period;
        this.page = json.page;
        this.moredata = json.moredata;
        this.activities = json.activities;
    } else {
        this.start = new Date();
        this.end = null;
        this.activities = new Array();
        this.period = opts.getPreferredActivityListPeriod();
        this.page = opts.getPreferredActivityListPageSize();
        this.moredata = true;
    }
    this.initialDates();
    this.client = new Client('rpc/json/workout/search.php');
    this.print = print;
}

ActivityList.prototype.initialDates = function () {
    this.start.setHours(0);
    this.start.setMinutes(0);
    this.start.setSeconds(0);
    this.start.setMilliseconds(0);
    switch (this.period) {
        case 'week':
            // beginning of the week
            this.start.setDate(this.start.getDate() - this.start.getDay());
            this.end = new Date(this.start.getTime());
            this.end.setDate(this.end.getDate() + 7);
            break;
        case 'month':
            // beggining of the month
            this.start.setDate(1);
            this.end = new Date(this.start.getTime());
            this.end.setMonth(this.end.getMonth() + 1);
            break;
        case 'three-months':
            // beggining of the trimester
            this.start.setDate(1);
            this.start.setMonth(this.start.getMonth() - (this.start.getMonth() % 3));
            this.end = new Date(this.start.getTime());
            this.end.setMonth(this.end.getMonth() + 3);
            break;
        case 'six-months':
            // beggining of the semester
            this.start.setDate(1);
            this.start.setMonth(this.start.getMonth() - (this.start.getMonth() % 6));
            this.end = new Date(this.start.getTime());
            this.end.setMonth(this.end.getMonth() + 6);
            break;
        case 'year':
            this.start.setDate(1);
            this.start.setMonth(0);
            this.end = new Date(this.start.getTime());
            this.end.setFullYear(this.end.getFullYear() + 1);
            break;
    }
};

ActivityList.prototype.next = function() {
    this.moveDates(1);
    this.refreshSearch();
};
      
ActivityList.prototype.previous = function() {
    this.moveDates(-1);
    this.refreshSearch();
};
      
ActivityList.prototype.today = function () {
    this.start = new Date();
    this.end = null;
    this.initialDates();
    this.refreshSearch();
};

ActivityList.prototype.periodTime = function() {
    var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dec'];
    switch (this.period) {
        case 'week':
            return this.start.getDate() + ' ' + months[this.start.getMonth()] + ' ' + this.start.getFullYear();
        case 'month':
            return months[this.start.getMonth()] + ' ' + this.start.getFullYear();
        case 'three-months':
            return this.start.getFullYear() + "Q" + (parseInt(this.start.getMonth() / 3) + 1);
        case 'six-months':
            return this.start.getFullYear() + "H" + (this.start.getMonth() < 6 ? 1 : 2);
        case 'year':
            return this.start.getFullYear();
        default:
            return null;
    }
};
      
ActivityList.prototype.moveDates = function (value) {
    switch (this.period) {
        case 'week':
            this.start.setDate(this.start.getDate() + (7 * value));
            this.end = new Date(this.start.getTime());
            this.end.setDate(this.end.getDate() + 7);
            break;
        case 'month':
            this.start.setMonth(this.start.getMonth() + value);
            this.end = new Date(this.start.getTime());
            this.end.setMonth(this.end.getMonth() + 1);
            break;
        case 'three-months':
            this.start.setMonth(this.start.getMonth() + (3 * value));
            this.end = new Date(this.start.getTime());
            this.end.setMonth(this.end.getMonth() + 3);
            break;
        case 'six-months':
            this.start.setMonth(this.start.getMonth() + (6 * value));
            this.end = new Date(this.start.getTime());
            this.end.setMonth(this.end.getMonth() + 6);
            break;
        case 'year':
            this.start.setFullYear(this.start.getFullYear() + value);
            this.end = new Date(this.start.getTime());
            this.end.setYear(this.end.getFullYear() + 1);
            break;
    }
};

ActivityList.prototype.moreSearch = function() {
    this.client.searchActivities(this.start, this.end, this.activities.length, this.page, 
            this.onSuccess.bind(this), this.onError.bind(this));
};
      
ActivityList.prototype.refreshSearch = function() {
    // start the search for a new period or page size
    this.activities = new Array();
    this.initialDates();
    this.moreSearch();
};
      
ActivityList.prototype.onChange = function(select) {
    var s = $(select);
    if ('period' === s.attr('id')) {
        this.period = s.val();
        this.refreshSearch();
    } else if ('page' === s.attr('id')) {
        this.page = s.val();
        this.refreshSearch();
    }
};

ActivityList.prototype.setPeriod = function(period) {
    this.period = period;
    this.refreshSearch();
};

ActivityList.prototype.setPage = function(page) {
    this.page = page;
    this.refreshSearch();
};                                                                          
      
ActivityList.prototype.onSuccess = function(data) {
    if (data.status === 'SUCCESS') {
        var rows = this.activities.length;
        this.activities = this.activities.concat(data.response);
        this.moredata = data.response.length === parseInt(this.page);
        sessionStorage.setItem("activity-list", JSON.stringify(this));
        this.print(rows);
    } else {
        message(data.errorMessage, 'error');
    }
};
      
ActivityList.prototype.onError = function (data) {
    if (data.status === 403) {
        sessionStorage.clear();
        $(location).attr('href', 'login.html');
    } else {
        message(data.statusText + ' (' + data.status + ')', 'error');
    }
};

//
// UserList

function UserList(print, opts) {
    this.client = new Client('rpc/json/user/search.php');
    this.print = print;
    this.users = new Array();
    this.page = 10;
    this.op = null;
    this.login = null;
    this.firstname = null;
    this.lastname = null;
    this.opts = opts;
    this.moreData = false;
}

UserList.prototype.search = function() {
    // start the search for a new period or page size
    this.users = new Array();
    this.moreSearch();
};

UserList.prototype.moreSearch = function() {
    this.client.searchUsers(this.op, this.login, this.firstname, this.lastname,
            this.users.length, this.page, this.onSuccess.bind(this), this.onError.bind(this));
};

UserList.prototype.onSuccess = function(data) {
    if (data.status === 'SUCCESS') {
        var rows = this.users.length;
        this.users = this.users.concat(data.response);
        this.moredata = data.response.length === parseInt(this.page);
        if (this.print) {
            this.print(rows);
        }
    } else {
        message(data.errorMessage, 'error');
    }
};
      
UserList.prototype.onError = function (data) {
    if (data.status === 403) {
        sessionStorage.clear();
        $(location).attr('href', 'login.html');
    } else {
        message(data.statusText + ' (' + data.status + ')', 'error');
    }
};

//
// TCXActivity

function TCXActivity(id, mapName, opts, onLoadExecute) {
    this.onLoadExecute = onLoadExecute;
    this.opts = opts;
    this.mapName = mapName;
    this.client = new Client('rpc/json/workout/download.php');
    this.client.downloadActivity(id, this.onSuccess.bind(this), this.onError.bind(this));
    this.polylines = [];
    this.lapMarkers = [];
    this.startMarker = null;
    this.clickPopup = L.popup();
    this.map = null;
    this.data = null;
}

TCXActivity.prototype.onSuccess = function(data) {
    this.data = this.activityXmlToJson(data.getElementsByTagName('Activity')[0]);
    this.joinTracks();
    this.calculateMagnitudes();
    if (this.onLoadExecute) {
        this.onLoadExecute(this);
    }
};

TCXActivity.prototype.calculateAverageSpeedTrackpoint = function (trackNow, trackPrev) {
    if (trackNow.lap !== trackPrev.lap || trackNow.trackpoint !== trackPrev.trackpoint) {
        var meters = this.data.Lap[trackNow.lap].Track.Trackpoint[trackNow.trackpoint].DistanceMeters
                - this.data.Lap[trackPrev.lap].Track.Trackpoint[trackPrev.trackpoint].DistanceMeters;
        var seconds = (Date.parse(this.data.Lap[trackNow.lap].Track.Trackpoint[trackNow.trackpoint].Time)
                - Date.parse(this.data.Lap[trackPrev.lap].Track.Trackpoint[trackPrev.trackpoint].Time)) / 1000;
        return meters / seconds;
    } else {
        return 0.0;
    }
};

TCXActivity.prototype.avancePreviousTrackpoint = function (trackNow, trackPrev, period) {
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
};


TCXActivity.prototype.drawPopupMagnitude = function (div, container, magnitude, title, url, converterMethod) {
    if (container && container.hasOwnProperty(magnitude) && container[magnitude] != 0) {
        var img = $('<img>', {src: url, alt: title, title: title, width: 10});
        div.append(img);
        div.append('&nbsp;&nbsp;');
        div.append(converterMethod ? converterMethod(container[magnitude]) : container[magnitude]);
        div.append($('<br>'));
    }
};

TCXActivity.prototype.pointMessage = function (track) {
    var converter = new Converter(this.opts);
    var content = $('<div/>');
    this.drawPopupMagnitude(content, track, 'Time', 'Time', '../resources/open-iconic/svg-black/clock.svg', (function(time) {
        return converter.getTime((Date.parse(time) - Date.parse(this.data.Lap[0].Track.Trackpoint[0].Time)) / 1000);
    }).bind(this));
    this.drawPopupMagnitude(content, track, 'DistanceMeters', 'Distance', '../resources/open-iconic/svg-black/flag.svg', converter.getDistanceUnit.bind(converter));
    this.drawPopupMagnitude(content, track, 'AverageSpeed', 'Avg. Speed', '../resources/open-iconic/svg-black/graph.svg', converter.getSpeedUnit.bind(converter));
    this.drawPopupMagnitude(content, track, 'AltitudeMeters', 'Altitude', '../resources/open-iconic/svg-black/arrow-circle-top.svg', converter.getAltitudeUnit.bind(converter));
    this.drawPopupMagnitude(content, track.HeartRateBpm, 'Value', 'Heart Rate', '../resources/open-iconic/svg-black/heart.svg', converter.getHeartRateUnit.bind(converter));
    return content;
};

TCXActivity.prototype.lapMessage = function (l) {
    var lap = this.data.Lap[l];
    var converter = new Converter(this.opts);
    var content = $('<div/>');
    this.drawPopupMagnitude(content, lap, 'DistanceMeters', 'Distance', '../resources/open-iconic/svg-black/flag.svg', converter.getDistanceUnit.bind(converter));
    this.drawPopupMagnitude(content, lap, 'AverageSpeed', 'Avg. Speed', '../resources/open-iconic/svg-black/graph.svg', converter.getSpeedUnit.bind(converter));
    this.drawPopupMagnitude(content, lap, 'MaximumSpeed', 'Max. Speed', '../resources/open-iconic/svg-red/graph.svg', converter.getSpeedUnit.bind(converter));
    this.drawPopupMagnitude(content, lap, 'MinimumAltitude', 'Min. Altitude', '../resources/open-iconic/svg-black/arrow-circle-bottom.svg', converter.getAltitudeUnit.bind(converter));
    this.drawPopupMagnitude(content, lap, 'MaximumAltitude', 'Max. Altitude', '../resources/open-iconic/svg-red/arrow-circle-top.svg', converter.getAltitudeUnit.bind(converter));
    this.drawPopupMagnitude(content, lap.AverageHeartRateBpm, 'Value', 'Avg. Heart Rate', '../resources/open-iconic/svg-black/heart.svg', converter.getHeartRateUnit.bind(converter));
    this.drawPopupMagnitude(content, lap.MaximumHeartRateBpm, 'Value', 'Max. Heart Rate', '../resources/open-iconic/svg-red/heart.svg', converter.getHeartRateUnit.bind(converter));
    return content;
};

TCXActivity.prototype.activityMessage = function () {
    var act = this.data;
    var converter = new Converter(this.opts);
    var content = $('<div/>');
    this.drawPopupMagnitude(content, act, 'Sport', 'Sport', '../resources/open-iconic/svg-black/pin.svg');
    this.drawPopupMagnitude(content, act, 'TotalTimeSeconds', 'Time', '../resources/open-iconic/svg-black/clock.svg', converter.getTime.bind(converter));
    this.drawPopupMagnitude(content, act, 'DistanceMeters', 'Distance', '../resources/open-iconic/svg-black/flag.svg', converter.getDistanceUnit.bind(converter));
    this.drawPopupMagnitude(content, act, 'AverageSpeed', 'Avg. Speed', '../resources/open-iconic/svg-black/graph.svg', converter.getSpeedUnit.bind(converter));
    this.drawPopupMagnitude(content, act, 'MaximumSpeed', 'Max. Speed', '../resources/open-iconic/svg-red/graph.svg', converter.getSpeedUnit.bind(converter));
    this.drawPopupMagnitude(content, act, 'Calories', 'Calories', '../resources/open-iconic/svg-red/calculator.svg');
    this.drawPopupMagnitude(content, act, 'MinimumAltitude', 'Min. Altitude', '../resources/open-iconic/svg-black/arrow-circle-bottom.svg', converter.getAltitudeUnit.bind(converter));
    this.drawPopupMagnitude(content, act, 'MaximumAltitude', 'Max. Altitude', '../resources/open-iconic/svg-red/arrow-circle-top.svg', converter.getAltitudeUnit.bind(converter));
    this.drawPopupMagnitude(content, act, 'AverageHeartRateBpm', 'Avg. Heart Rate', '../resources/open-iconic/svg-black/heart.svg', converter.getHeartRateUnit.bind(converter));
    this.drawPopupMagnitude(content, act, 'MaximumHeartRateBpm', 'Max. Heart Rate', '../resources/open-iconic/svg-red/heart.svg', converter.getHeartRateUnit.bind(converter));
    return content;
};

TCXActivity.prototype.distance = function (a, b) {
    return Math.sqrt(Math.pow(b.lat - a.lat, 2) + Math.pow(b.lng - a.lng, 2));
};

TCXActivity.prototype.onPolylineClick = function(e) {
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
};

TCXActivity.prototype.showPopup = function(track) {
    var content = this.pointMessage(track);
    this.clickPopup.setLatLng([track.Position.LatitudeDegrees, track.Position.LongitudeDegrees]).setContent(content[0]).openOn(this.map);
};

TCXActivity.prototype.joinTracks = function() {
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
};

TCXActivity.prototype.calculateMagnitudes = function() {
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
};
      
TCXActivity.prototype.onError = function (data) {
    if (data.status === 403) {
        sessionStorage.clear();
        $(location).attr('href', 'login.html');
    } else {
        message(data.statusText + ' (' + data.status + ')', 'error');
    }
}; 

TCXActivity.prototype.clickLap = function (e) {
    var id = e.target._leaflet_id;
    for (var i in this.lapMarkers) {
        if (id === this.lapMarkers[i]._leaflet_id) {
            this.switchLap(i);
            break;
        }
    }
};

TCXActivity.prototype.clickStart = function (e) {
    for (var i in this.data.Lap) {
        this.switchLap(i, true);
    }
};

TCXActivity.prototype.prepareMap = function () {
    // TODO: use som try / catch to check Track and Trackpoints
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
};

TCXActivity.prototype.switchLap = function(lap, value) {
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
};

TCXActivity.prototype.locateTrackByIndex = function(idx) {
    for (var l in this.data.Lap) {
        if (idx < this.data.Lap[l].Track.Trackpoint.length) {
            return this.data.Lap[l].Track.Trackpoint[idx];
        } else {
            idx = idx - this.data.Lap[l].Track.Trackpoint.length;
        }
    }
};

TCXActivity.prototype.locateLapByIndex = function(idx) {
    for (var l in this.data.Lap) {
        if (idx < this.data.Lap[l].Track.Trackpoint.length) {
            return l;
        } else {
            idx = idx - this.data.Lap[l].Track.Trackpoint.length;
        }
    }
};


TCXActivity.prototype.getArray = function(converter, attrArray, min, max) {
    // it would have been better using ES6 Proxy to not re-create arrays
    // but ut seems not supported by most browsers now
    min = (typeof min !== 'undefined')? parseFloat(min):undefined;
    max = (typeof max !== 'undefined')? parseFloat(max):undefined;
    var array = [];
    var startTime = Date.parse(this.data.Lap[0].Track.Trackpoint[0].Time);
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
                val = parseFloat(val);
            }
            if (typeof(min) !== 'undefined'  && val < min) {
                val = min;
            } else if (typeof(max) !== 'undefined' && val > max) {
                val = max;
            }
            array.push([
                Date.parse(this.data.Lap[l].Track.Trackpoint[t].Time) - startTime,
                converter(val)
            ]);
        }
    }
    return array;
};

TCXActivity.prototype.activityXmlToJson = function(xml) {
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
};


//
// UserInfo

function UserInfo(login, load, onLoadExecute) {
    this.onLoadExecute = onLoadExecute;
    if (load) {
        var storeUser = sessionStorage.getItem("user-info");
        if (storeUser && (!login || login === storeUser.login)) {
            // the user is the logged user => read from session storage
            this.info = JSON.parse(storeUser);
            if (this.onLoadExecute) {
                this.onLoadExecute(this);
            }
        } else {
            var client = new Client('rpc/json/user/get_user.php?login=' + login);
            client.getUserInformation(this.onSuccess.bind(this), this.onError.bind(this));
        }
    }
}

UserInfo.prototype.getInfo = function() {
    return this.info;
};

UserInfo.prototype.setInfo = function(info) {
    this.info = info;
    sessionStorage.setItem('user-info', JSON.stringify(this.info));
};

UserInfo.prototype.save = function(onLoadExecute) {
    this.onLoadExecute = onLoadExecute;
    var client = new Client('rpc/json/user/set_user.php');
    client.setUserInformation(this.info, this.onSuccess.bind(this), this.onError.bind(this));
};

UserInfo.prototype.delete = function(onLoadExecute) {
    this.onLoadExecute = onLoadExecute;
    var client = new Client('rpc/json/user/delete_user.php');
    client.deleteUser(this.info.login, this.onSuccess.bind(this), this.onError.bind(this));
};

UserInfo.prototype.onSuccess = function(data) {
    if (data.status === 'SUCCESS') {
        if (data.response) {
            this.info = data.response;
            var storeUser = sessionStorage.getItem("user-info");
            if (storeUser && storeUser.login === this.info.login) {
                sessionStorage.setItem('user-info', JSON.stringify(this.info));
            }
        }
        if (this.onLoadExecute) {
            this.onLoadExecute(this);
        }
    } else {
        message(data.errorMessage, 'error');
    }
};

UserInfo.prototype.onError = function(data) {
    if (data.status === 403) {
        sessionStorage.clear();
        $(location).attr('href', 'login.html');
    } else {
        message(data.statusText + ' (' + data.status + ')', 'error');
    }
};