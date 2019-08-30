/*
 * Copyright (C) 2019 <https://github.com/rickyepoderi/runnerupweb>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

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
      tags: new Array(),
      notAssignedTags: new Array(),
      selectedTag: null,
      automaticTagProviders: new Array(),
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
    this.listActivityTags = this.listActivityTags.bind(this);
    this.onListActivityTagsSuccess = this.onListActivityTagsSuccess.bind(this);
    this.assignTagToActivity = this.assignTagToActivity.bind(this);
    this.realAssignTagToActivity = this.realAssignTagToActivity.bind(this);
    this.onAssignTagToActivity = this.onAssignTagToActivity.bind(this);
    this.removeTagFromActivity = this.removeTagFromActivity.bind(this);
    this.realremoveTagFromActivity = this.realremoveTagFromActivity.bind(this);
    this.onRemoveTagToActivity = this.onRemoveTagToActivity.bind(this);
    this.keyPress = this.keyPress.bind(this);
    this.listAutomaticTags = this.listAutomaticTags.bind(this);
    this.onListAutomaticTagsSuccess = this.onListAutomaticTagsSuccess.bind(this);
    this.createAutomaticTag = this.createAutomaticTag.bind(this);
    this.requestTagConfigFromProvider = this.requestTagConfigFromProvider.bind(this);
    this.onRequestTagConfigFromProviderSuccess = this.onRequestTagConfigFromProviderSuccess.bind(this);
    this.realRecalculateAutomaticTags = this.realRecalculateAutomaticTags.bind(this);
    this.recalculateAutomaticTags = this.recalculateAutomaticTags.bind(this);
    
    this.downloadActivity();
    this.listActivityTags();
    this.listAutomaticTags();
  }

   onListActivityTagsSuccess(data) {
    if (data.status === 'SUCCESS') {
      this.setState({tags: data.response, notAssignedTags: this.calculateNotAssignedTags(data.response)});
    } else {
      this.props.app.showMessage('error', data.errorMessage);
    }
  }

  listActivityTags() {
    $.ajax({
      url: 'rpc/json/workout/list_workout_tags.php?id=' + this.props.activity.id,
      type: 'get',
      contentType: 'application/xml',
      ifModified: false,
      success: this.onListActivityTagsSuccess,
      error: this.props.app.onErrorCommunication
    });
  }

  calculateNotAssignedTags(tags) {
    var allTags = this.props.app.getAvailableTags();
    var allTagNames = allTags.map(tag => {return tag.tag});
    var assignedTagNames = tags.map(tag => {return tag.tag});
    return allTagNames.filter(name => !assignedTagNames.includes(name));
  }

  onAssignTagToActivity(data) {
    if (data.status === 'SUCCESS') {
      $('#new-tag-input').val('');
      this.listActivityTags();
    } else {
      this.props.app.showMessage('error', data.errorMessage);
    }
  }

  realAssignTagToActivity(answer) {
    if (answer === 'yes') {
      var input = $('#new-tag-input');
      if (input.val() && input[0].checkValidity()) {
        $.ajax({
          url: 'rpc/json/workout/manage_workout_tag.php?op=ASSIGN&id=' + this.props.activity.id + "&tag=" + encodeURIComponent(input.val()),
          type: 'post',
          dataType: 'json',
          contentType: 'application/json',
          success: this.onAssignTagToActivity,
          error: this.props.app.onErrorCommunication
        });
      }
    }
  }

  assignTagToActivity() {
    var input = $('#new-tag-input');
    if (input.val() && input[0].checkValidity()) {
      var tag = this.props.app.getAvailableTags().find(t => t.tag === input.val());
      if (tag) {
        if (tag.auto) {
          this.props.app.showSelect(SelectOperation.createSelect(
            this.props.app.getIntl().formatMessage({id: 'runnerupweb.are.you.sure.you.want.to.assign.tag'}, {tag: tag.tag}),
            {
              yes: this.props.app.getIntl().formatMessage({id: 'runnerupweb.Yes'}),
              no: this.props.app.getIntl().formatMessage({id: 'runnerupweb.No'})
            },
            this.realAssignTagToActivity)
          );
        } else {
          this.realAssignTagToActivity('yes')
        }
      }
    }
  }

  onRemoveTagToActivity(data) {
    if (data.status === 'SUCCESS') {
      this.listActivityTags();
    } else {
      this.props.app.showMessage('error', data.errorMessage);
    }
  }

  realremoveTagFromActivity(answer, tag) {
    if (!tag) {
      tag = this.state.selectedTag;
    }
    if (answer === 'yes') {
      $.ajax({
        url: 'rpc/json/workout/manage_workout_tag.php?op=UNASSIGN&id=' + this.props.activity.id + "&tag=" + encodeURIComponent(tag.tag),
        type: 'post',
        dataType: 'json',
        contentType: 'application/json',
        success: this.onRemoveTagToActivity,
        error: this.props.app.onErrorCommunication
      });
    }
  }

  removeTagFromActivity(event) {
    var txt = $(event.target).parent().text();
    var tag = this.state.tags.find(t => t.tag === txt);
    this.setState({selectedTag: tag});
    if (tag.auto) {
      this.props.app.showSelect(SelectOperation.createSelect(
        this.props.app.getIntl().formatMessage({id: 'runnerupweb.are.you.sure.you.want.to.delete.tag'}, {tag: tag.tag}),
        {
          yes: this.props.app.getIntl().formatMessage({id: 'runnerupweb.Yes'}),
          no: this.props.app.getIntl().formatMessage({id: 'runnerupweb.No'})
        },
        this.realremoveTagFromActivity)
      );
    } else {
      this.realremoveTagFromActivity('yes', tag)
    }
  }

  onDownloadSuccess(data) {
    var activity = new TCXActivity(this.props.activity.id, 'map', this.props.app.getOptions(), data);
    this.setState({tcx: activity});
    activity.prepareMap();
  }
  
  downloadActivity() {
    $.ajax({
      url: 'rpc/json/workout/download.php?id=' + this.props.activity.id,
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

  keyPress(event) {
    if (event.which === 13) {
      this.assignTagToActivity();
    }
  }

  onListAutomaticTagsSuccess(data) {
    if (data.status === 'SUCCESS') {
      var providers = {};
      for (var i = 0; i < data.response.length; i++) {
        providers[data.response[i]] = data.response[i];
      }
      this.setState({automaticTagProviders: providers});
    } else {
      this.props.app.showMessage('error', data.errorMessage);
    }
  }
  
  listAutomaticTags() {
    $.ajax({
      url: 'rpc/json/workout/automatic_tag.php?',
      type: 'get',
      contentType: 'application/json',
      success: this.onListAutomaticTagsSuccess,
      error: this.props.app.onErrorCommunication
    });
  }

  onRequestTagConfigFromProviderSuccess(data) {
    if (data.status === 'SUCCESS') {
      this.props.app.moveToTagConfigCreate(data.response, this.props.activityList.state);
    } else {
      this.props.app.showMessage('error', data.errorMessage);
    }
  }
  
  requestTagConfigFromProvider(provider) {
    $.ajax({
      url: 'rpc/json/workout/automatic_tag.php?provider=' + encodeURIComponent(provider) + '&id=' + this.props.activity.id,
      type: 'get',
      contentType: 'application/json',
      success: this.onRequestTagConfigFromProviderSuccess,
      error: this.props.app.onErrorCommunication
    });
  }

  createAutomaticTag() {
    this.props.app.showSelect(SelectOperation.createSelect('',
      this.state.automaticTagProviders,
      this.requestTagConfigFromProvider)
    );
  }

  realRecalculateAutomaticTags($answer) {
    $.ajax({
      url: 'rpc/json/workout/calculate_automatic_tags.php?id=' + this.props.activity.id + '&delete=' + $answer,
      type: 'post',
      contentType: 'application/json',
      success: this.listActivityTags,
      error: this.props.app.onErrorCommunication
    });
  }

  recalculateAutomaticTags() {
    this.props.app.showSelect(SelectOperation.createSelect(
      this.props.app.getIntl().formatMessage({id: 'runnerupweb.delete.tags.recalculate'}),
      {
        true: this.props.app.getIntl().formatMessage({id: 'runnerupweb.Yes'}),
        false: this.props.app.getIntl().formatMessage({id: 'runnerupweb.No'})
      },
      this.realRecalculateAutomaticTags)
    );
  }

  render() {
    return(
      <React.Fragment>
        <div className="header">
          {this.props.app.renderPopupMenu(this.props.activityList.state, this.props.activityList.unselect)}
          <h1 id="title">{this.props.app.getOptions().getDateTime(this.state.activity.startTime)}</h1>
          <p className="left">
            {this.state.tags.map(tag =>
                <span key={tag.tag} className={tag.auto? 'tag-auto-short':'tag-short'}>{tag.tag}<img src="resources/open-iconic/svg-white/x.svg" onClick={this.removeTagFromActivity}/></span>
            )}
            <span className="tag-short">
              <input id="new-tag-input" list="free-tags" maxLength="128" onChange={this.props.app.checkInputDataList} onKeyPress={this.keyPress}
                     placeholder={this.props.app.getIntl().formatMessage({id: 'runnerupweb.tag.new'})}/>
              <img onClick={this.assignTagToActivity} src="resources/open-iconic/svg-white/play-circle.svg"/>
            </span>
            <datalist id="free-tags">
              {this.state.notAssignedTags.map(name =>
                <option key={name} value={name}/>
              )}
            </datalist>
          </p>
          <p className="right">
            <img src="resources/open-iconic/svg-white/tags.svg" alt={this.props.app.getIntl().formatMessage({id: 'runnerupweb.create.new.automatic.tag'})}
                 title={this.props.app.getIntl().formatMessage({id: 'runnerupweb.create.new.automatic.tag'})} onClick={this.createAutomaticTag}/>
            <img src="resources/open-iconic/svg-white/reload.svg" alt={this.props.app.getIntl().formatMessage({id: 'runnerupweb.recalculate.automatic.tags'})}
                 title={this.props.app.getIntl().formatMessage({id: 'runnerupweb.recalculate.automatic.tags'})} onClick={this.recalculateAutomaticTags}/>
            <img src="resources/open-iconic/svg-white/trash.svg" alt={this.props.app.getIntl().formatMessage({id: 'runnerupweb.Delete'})}
                 title={this.props.app.getIntl().formatMessage({id: 'runnerupweb.Delete'})} onClick={this.confirmDelete}/>
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
