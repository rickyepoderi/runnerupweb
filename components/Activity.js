import $ from 'jquery';
import React from 'react';
import Dygraph from 'dygraphs';
import TCXActivity from './TCXActivity';
import {FormattedMessage, FormattedHTMLMessage} from 'react-intl';
import SelectOperation from './SelectOperation';

export default class Activity extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      activity: this.props.activity,
      tcx: null
    };
    this.onDownloadSuccess = this.onDownloadSuccess.bind(this);
    this.downloadActivity = this.downloadActivity.bind(this);
    this.scatterAltitude = this.scatterAltitude.bind(this);
    this.scatterSpeed = this.scatterSpeed.bind(this);
    this.scatterHeartRate = this.scatterHeartRate.bind(this);
    this.legendFormatter = this.legendFormatter.bind(this);
    this.clickScatter = this.clickScatter.bind(this);
    this.confirmDelete = this.confirmDelete.bind(this);
    this.doDelete = this.doDelete.bind(this);
    this.onDeleteSuccess = this.onDeleteSuccess.bind(this);
    
    this.downloadActivity(this.props.activity.id);
  }
  
  onDownloadSuccess(data) {
    var activity = new TCXActivity(this.props.activity.id, 'map', this.props.app.getOptions(), data);
    this.setState({tcx: activity});
    activity.prepareMap();
  }
  
  downloadActivity(id) {
    $.ajax({
      url: 'rpc/json/workout/download.php?id=' + id,
      type: 'get',
      contentType: 'application/xml',
      ifModified: false,
      success: this.onDownloadSuccess,
      error: this.props.app.onErrorCommunication
    });
  }
  
  onDeleteSuccess(result) {
    if (result.status === 'SUCCESS') {
      this.props.activityList.backToList(true);
    } else {
      this.props.app.showMessage('error', result.errorMessage);
    }
  }
  
  doDelete(answer) {
    if (answer === 'yes') {
      $.ajax({
        url: 'rpc/json/workout/delete.php?id=' + this.props.activity.id,
        type: 'post',
        dataType: 'json',
        contentType: 'application/json',
        success: this.onDeleteSuccess,
        error: this.props.app.onErrorCommunication
      });
    }
  }
  
  confirmDelete() {
    this.props.app.showSelect(SelectOperation.createSelect(
      this.props.app.getIntl().formatMessage({id: 'runnerupweb.are.you.sure.you.want.to.delete.activity'}),
      {
        yes: this.props.app.getIntl().formatMessage({id: 'runnerupweb.Yes'}),
        no: this.props.app.getIntl().formatMessage({id: 'runnerupweb.No'})
      }, 
      this.doDelete)
    );
  }
  
  toggleLapDetails() {
    var laps = $('#laps');
    if (laps.is(':hidden')) {
      laps.slideDown('slow');
    } else {
      laps.slideUp('slow');
    }
  }
  
  clickLap(i) {
    this.state.tcx.switchLap(i);
  }
  
  doubleClickLap(event) {
    var clickElement = $(event.target);
    if (clickElement.is('span')) {
      clickElement = $(event.target).parent().parent().parent().children()[1];
    } else {
      clickElement = $(event.target).parent().parent().children()[1];
    }
    var element = $(clickElement);
    if (element.is(':hidden')) {
      element.css('display', 'inline');
    } else {
      element.hide();
    }
  }
  
  clickScatter(event, x, points) {
    // locate the value in the laps
    var lap = this.state.tcx.locateLapByTime(x);
    if (lap) {
      this.state.tcx.switchLap(lap, true);
      var track = this.state.tcx.locateTrackByTime(x, lap);
      if (track) {
        this.state.tcx.showPopup(track);
      }
    }
  }
  
  legendFormatter(data) {
    var g = data.dygraph;
    var sepLines = g.getOption('labelsSeparateLines');
    var html;
    if (typeof(data.x) === 'undefined') {
      // TODO: this check is duplicated in generateLegendHTML. Put it in one place.
      if (g.getOption('legend') != 'always') {
        return '';
      }
      html = '';
      for (var i = 0; i < data.series.length; i++) {
        var series = data.series[i];
        if (!series.isVisible) continue;

        if (html !== '') html += (sepLines ? '<br/>' : ' ');
        html += `<span style='font-weight: bold; color: ${series.color};'>${series.dashHTML} ${series.labelHTML}</span>`;
      }
      return html;
    }
    var time = (data.x - Date.parse(this.state.tcx.data.Lap[0].Track.Trackpoint[0].Time)) / 1000;
    html = this.props.app.getOptions().getTime(time);
    for (var i = 0; i < data.series.length; i++) {
      var series = data.series[i];
      if (!series.isVisible) continue;
      if (sepLines) html += '<br>';
      var cls = series.isHighlighted ? ' class="highlight"' : '';
      html += `<span${cls}> <b><span style='color: ${series.color};'>${series.labelHTML}</span></b>:&#160;${series.yHTML}</span>`;
    }
    return html;
  }
  
  scatterSpeed() {
    var div = $('#scatter-speed');
    var options = {
      legend: 'always',
      title: this.props.app.getIntl().formatMessage({id: 'runnerupweb.Speed'}),
      titleHeight: 32,
      ylabel: this.props.app.getIntl().formatMessage({id: 'runnerupweb.Speed'}) + ' (' + this.props.app.getOptions().getPreferredUnitSpeed() + ")",
      xlabel: 'Time',
      strokeWidth: 1.5,
      // legend formmater is not supported in dygraphs 1.x
      legendFormatter: this.legendFormatter,
      labels: ['X', this.props.app.getIntl().formatMessage({id: 'runnerupweb.Speed'})],
      clickCallback: this.clickScatter
    };
    if (div.is(':hidden')) {
      div.show();
      new Dygraph(
        document.getElementById("scatter-speed"),
        this.state.tcx.getArray(this.props.app.getOptions().getSpeed.bind(this.props.app.getOptions()), ['AverageSpeed']), options);
    } else {
      div.hide();
    }
  }

  scatterAltitude() {
    var div = $('#scatter-altitude');
    var options = {
      legend: 'always',
      title: this.props.app.getIntl().formatMessage({id: 'runnerupweb.Altitude'}),
      titleHeight: 32,
      ylabel: this.props.app.getIntl().formatMessage({id: 'runnerupweb.Altitude'}) + ' (' + this.props.app.getOptions().getPreferredUnitAltitude() + ")",
      xlabel: 'Time',
      strokeWidth: 1.5,
      // legend formmater is not supported in dygraphs 1.x
      legendFormatter: this.legendFormatter,
      labels: ['X', this.props.app.getIntl().formatMessage({id: 'runnerupweb.Altitude'})],
      clickCallback: this.clickScatter
    }
    if (div.is(':hidden')) {
      div.show();
      new Dygraph(
        document.getElementById("scatter-altitude"),
        this.state.tcx.getArray(this.props.app.getOptions().getAltitude.bind(this.props.app.getOptions()), ['AltitudeMeters']), options);
    } else {
      div.hide();
    }
  }
  
  scatterHeartRate() {
    var div = $('#scatter-heart-rate');
    var options = {
      legend: 'always',
      title: this.props.app.getIntl().formatMessage({id: 'runnerupweb.HeartRate'}),
      titleHeight: 32,
      ylabel: this.props.app.getIntl().formatMessage({id: 'runnerupweb.HeartRate'}) + ' (bpm)',
      xlabel: 'Time',
      strokeWidth: 1.5,
      // legend formmater is not supported in dygraphs 1.x
      legendFormatter: this.legendFormatter,
      labels: ['X', this.props.app.getIntl().formatMessage({id: 'runnerupweb.HeartRate'})],
      clickCallback: this.clickScatter
    }
    if (div.is(':hidden')) {
      div.show();
      new Dygraph(
        document.getElementById("scatter-heart-rate"),
        this.state.tcx.getArray(this.props.app.getOptions().getHeartRate.bind(this.props.app.getOptions()), ['HeartRateBpm', 'Value']), options);
    } else {
      div.hide();
    }
  }
  
  renderScatter() {
    var style={minWidth: '400px', width: '100%', height: '400px'};
    return(
      <React.Fragment>
        <div className="chart graphic" id="scatter-speed" style={style}></div>
        <div className="chart graphic" id="scatter-altitude" style={style}></div>
        <div className="chart graphic" id="scatter-heart-rate" style={style}></div>
      </React.Fragment>
    );
  }
  
  renderCommonMagnitudes(data, click, styleHeader, styleValue) {
    var style={cursor: 'pointer'};
    return(
      <React.Fragment>
        {data.TotalTimeSeconds?
          <div className="magnitude">
            <div className="magnitude-header magnitude-header-time" style={styleHeader}>
              <FormattedMessage id="runnerupweb.Time" defaultMessage="Time"/>
            </div>
            <div className="magnitude-value" style={styleValue}>{this.props.app.getOptions().getTime(data.TotalTimeSeconds)}</div>
          </div> : null
        }
        {data.DistanceMeters?
          <div className="magnitude" onClick={click? this.scatterSpeed : null}>
            <div className="magnitude-header magnitude-header-distance" style={styleHeader}>
              <FormattedMessage id="runnerupweb.Distance" defaultMessage="Distance"/>
            </div>
            <div className="magnitude-value" style={styleValue}>{this.props.app.getOptions().getDistanceUnit(data.DistanceMeters)}</div>
          </div> : null
        }
        {data.AverageSpeed?
          <div className="magnitude" style={click? style:null} onClick={click? this.scatterSpeed : null}>
            <div className="magnitude-header magnitude-header-speed" style={styleHeader}>
              <FormattedMessage id="runnerupweb.AvgSpeed" defaultMessage="Avg. Speed"/>
            </div>
            <div className="magnitude-value" style={styleValue}>{this.props.app.getOptions().getSpeedUnit(data.AverageSpeed)}</div>
          </div> : null
        }
        {data.MinimumAltitude?
          <div className="magnitude" style={click? style:null} onClick={click? this.scatterAltitude : null}>
            <div className="magnitude-header magnitude-header-min-alt" style={styleHeader}>
              <FormattedMessage id="runnerupweb.MinAltitude" defaultMessage="Min. Altitude"/>
            </div>
            <div className="magnitude-value" style={styleValue}>{this.props.app.getOptions().getAltitudeUnit(data.MinimumAltitude)}</div>
          </div> : null
        }
        {data.MaximumAltitude?
          <div className="magnitude" style={click? style:null} onClick={click? this.scatterAltitude : null}>
            <div className="magnitude-header magnitude-header-max-alt" style={styleHeader}>
              <FormattedMessage id="runnerupweb.MaxAltitude" defaultMessage="Max. Altitude"/>
            </div>
            <div className="magnitude-value" style={styleValue}>{this.props.app.getOptions().getAltitudeUnit(data.MaximumAltitude)}</div>
          </div> : null
        }
        {data.AverageHeartRateBpm?
          <div className="magnitude" style={click? style:null} onClick={click? this.scatterHeartRate : null}>
            <div className="magnitude-header magnitude-header-heart" style={styleHeader}>
              <FormattedMessage id="runnerupweb.AvgHeartRate" defaultMessage="Avg. Heart Rate"/>
            </div>
            <div className="magnitude-value" style={styleValue}>{this.props.app.getOptions().getHeartRateUnit(data.AverageHeartRateBpm.Value || data.AverageHeartRateBpm)}</div>
          </div> : null
        }
        {data.MaximumHeartRateBpm?
          <div className="magnitude" style={click? style:null} onClick={click? this.scatterHeartRate : null}>
            <div className="magnitude-header magnitude-header-max-heart" style={styleHeader}>
              <FormattedMessage id="runnerupweb.MaxHeartRate" defaultMessage="Max. Heart Rate"/>
            </div>
            <div className="magnitude-value" style={styleValue}>{this.props.app.getOptions().getHeartRateUnit(data.MaximumHeartRateBpm.Value || data.MaximumHeartRateBpm)}</div>
          </div> : null
        }
      </React.Fragment>
   );
  }
  
  renderMagnitudes() {
    if (this.state.tcx && this.state.tcx.data) {
      return(
        <div className="magnitude-row">
          {this.state.tcx.data.Sport?
            <div className="magnitude">
              <div className="magnitude-header magnitude-header-sport">
                <FormattedMessage id="runnerupweb.Sport" defaultMessage="Sport"/>
              </div>
              <div className="magnitude-value">{this.state.tcx.data.Sport}</div>
            </div> : null
          }
          {this.state.tcx.data.Lap.length?
            <div className="magnitude maginute-pointer" onClick={this.toggleLapDetails}>
            <div className="magnitude-header magnitude-header-laps">
              <FormattedMessage id="runnerupweb.Laps" defaultMessage="Laps"/>
            </div>
            <div className="magnitude-value">{this.state.tcx.data.Lap.length}</div>
            </div> : null
          }
          {this.renderCommonMagnitudes(this.state.tcx.data, true)}
        </div>
      );
    }
  }
  
  renderLap(i) {
    var styleHeader = {borderColor: this.props.app.getOptions().getActivityLapColor(i), 
      backgroundColor: this.props.app.getOptions().getActivityLapColor(i)};
    var styleValue = {borderColor: this.props.app.getOptions().getActivityLapColor(i)};
    return(
      <div key={i} className="magnitude-lap-row">
        <div className="magnitude maginute-pointer" onClick={() => this.clickLap(i)} onDoubleClick={this.doubleClickLap}>
          <div className="magnitude-header magnitude-header-laps" style={styleHeader}>
            <FormattedMessage id="runnerupweb.Lap" defaultMessage="Lap"/>
          </div>
          <div className="magnitude-value" style={styleValue}>{i+1}</div>
        </div>
        <div className="magnitude-div">
          {this.renderCommonMagnitudes(this.state.tcx.data.Lap[i], false, styleHeader, styleValue)}
        </div>
      </div>
    );
  }
  
  renderLaps() {
    if (this.state.tcx && this.state.tcx.data) {
      return(
        <div id="laps" className="laps">
          {this.state.tcx.data.Lap.map((ldap, i) =>
            this.renderLap(i)
          )}
        </div>
      );
    }
  }
  
  render() {
    return(
      <React.Fragment>
        <div className="header">
          {this.props.app.renderPopupMenu('activity-list', this.props.activityList.unselect)}
          <h1 id="title">{this.props.app.getOptions().getDateTime(this.state.activity.startTime)}</h1>
          <p className="right">
            <a href="javascript:void(0)" title={this.props.app.getIntl().formatMessage({id: 'runnerupweb.Delete'})} onClick={this.confirmDelete}>
              <img src="resources/open-iconic/svg-white/trash.svg" alt={this.props.app.getIntl().formatMessage({id: 'runnerupweb.Delete'})}/>
            </a>
          </p>
        </div>
        {this.renderMagnitudes()}
        {this.renderLaps()}
        <div id="map" className="map"></div>
        {this.renderScatter()}
      </React.Fragment>   
    );
  }
}