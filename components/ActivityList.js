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

import $ from "jquery";
import React from 'react';
import Activity from './Activity';
import ActivityArray from './ActivityArray';
import SelectOperation from './SelectOperation';
import {FormattedMessage, FormattedHTMLMessage} from 'react-intl';

export default class ActivityList extends React.Component {
  constructor(props) {
    super(props);
    if (props.app.getActivityListState()) {
      this.state = Object.assign({}, props.app.getActivityListState());
    } else {
      var [start, end] = this.initialDates(this.props.app.getOptions().getPreferredActivityListPeriod());
      this.state = {
        start: start,
        end: end,
        activities: new ActivityArray(),
        selected: null,
        period: this.props.app.getOptions().getPreferredActivityListPeriod(),
        page: this.props.app.getOptions().getPreferredActivityListPageSize(),
        tagFilter: null,
        moredata: true
      };
    }
    this.initialDates = this.initialDates.bind(this);
    this.periodName = this.periodName.bind(this);
    this.search = this.search.bind(this);
    this.onSearchSuccess = this.onSearchSuccess.bind(this);
    this.toXMLString = this.toXMLString.bind(this);
    this.moveDates = this.moveDates.bind(this);
    this.next = this.next.bind(this);
    this.previous = this.previous.bind(this);
    this.today = this.today.bind(this);
    this.moreData = this.moreData.bind(this);
    this.showPeriodOptions = this.showPeriodOptions.bind(this);
    this.updatePeriod = this.updatePeriod.bind(this);
    this.updatePage = this.updatePage.bind(this);
    this.showPageOptions = this.showPageOptions.bind(this);
    this.unselect = this.unselect.bind(this);
    this.handleTagFilter = this.handleTagFilter.bind(this);
    this.clearTagFilter = this.clearTagFilter.bind(this);

    if (!props.app.getActivityListState()) {
      this.search(this.state.start, this.state.end, this.state.tagFilter, this.state.activities.size(), this.state.page);
    }
  }

  componentDidUpdate(prevProps, prevState, snapshot) {
    var input = $('#filter-tag-input');
    if (input.length && input.val() !== this.state.tagFilter) {
      input.val(this.state.tagFilter);
      input[0].setCustomValidity('');
    }
  }
  
  backToList(refresh) {
    if (refresh) {
      var start = this.state.start;
      var end = this.state.end;
      this.setState({activities: new ActivityArray(), selected: null});
      this.search(start, end, this.state.tagFilter, 0, this.state.page);
    } else {
      this.setState({selected: null});
    }
  }
  
  periodName() {
    var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dec'];
    switch (this.state.period) {
      case 'week':
        return this.state.start.getDate() + ' ' + months[this.state.start.getMonth()] + ' ' + this.state.start.getFullYear();
      case 'month':
        return months[this.state.start.getMonth()] + ' ' + this.state.start.getFullYear();
      case 'three-months':
        return this.state.start.getFullYear() + "Q" + (parseInt(this.state.start.getMonth() / 3) + 1);
      case 'six-months':
        return this.state.start.getFullYear() + "H" + (this.state.start.getMonth() < 6 ? 1 : 2);
      case 'year':
        return this.state.start.getFullYear();
      default:
        return null;
    }
  }
  
  initialDates(period) {
    var start = new Date();
    var end = null;
    start.setHours(0);
    start.setMinutes(0);
    start.setSeconds(0);
    start.setMilliseconds(0);
    switch (period) {
      case 'week':
        // beginning of the week
        start.setDate(start.getDate() - start.getDay());
        end = new Date(start.getTime());
        end.setDate(end.getDate() + 7);
        break;
      case 'month':
        // beggining of the month
        start.setDate(1);
        end = new Date(start.getTime());
        end.setMonth(end.getMonth() + 1);
        break;
      case 'three-months':
        // beggining of the trimester
        start.setDate(1);
        start.setMonth(start.getMonth() - (start.getMonth() % 3));
        end = new Date(start.getTime());
        end.setMonth(end.getMonth() + 3);
        break;
      case 'six-months':
        // beggining of the semester
        start.setDate(1);
        start.setMonth(start.getMonth() - (start.getMonth() % 6));
        end = new Date(start.getTime());
        end.setMonth(end.getMonth() + 6);
        break;
      case 'year':
        start.setDate(1);
        start.setMonth(0);
        end = new Date(start.getTime());
        end.setFullYear(end.getFullYear() + 1);
        break;
    }
    return [start, end];
  }
  
  onSearchSuccess(result) {
    if (result.status === 'SUCCESS') {
      var activities = this.state.activities;
      activities.concat(result.response);
      var moredata = result.response.length === parseInt(this.state.page)
      this.setState({activities: activities, moredata: moredata});
    } else {
      this.props.app.showMessage('error', result.errorMessage);
    }
  }
  
  search(start, end, tag, offset, limit) {
    var url = 'rpc/json/workout/search.php?start=' +  this.toXMLString(start);
    if (end) {
        url = url + '&end=' +  this.toXMLString(end);
    }
    if (tag) {
      url = url + '&tag=' + encodeURIComponent(tag);
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
        success: this.onSearchSuccess,
        error: this.props.app.onErrorCommunication
    });
  }
  
  moreData() {
    this.search(this.state.start, this.state.end, this.state.tagFilter, this.state.activities.size(), this.state.page);
  }
  
  moveDates(value) {
    var start = this.state.start;
    var end = this.state.end;
    switch (this.state.period) {
      case 'week':
        start.setDate(start.getDate() + (7 * value));
        end = new Date(start.getTime());
        end.setDate(end.getDate() + 7);
        break;
      case 'month':
        start.setMonth(start.getMonth() + value);
        end = new Date(start.getTime());
        end.setMonth(end.getMonth() + 1);
        break;
      case 'three-months':
        start.setMonth(start.getMonth() + (3 * value));
        end = new Date(start.getTime());
        end.setMonth(end.getMonth() + 3);
        break;
      case 'six-months':
        start.setMonth(start.getMonth() + (6 * value));
        end = new Date(start.getTime());
        end.setMonth(end.getMonth() + 6);
        break;
      case 'year':
        start.setFullYear(start.getFullYear() + value);
        end = new Date(start.getTime());
        end.setYear(end.getFullYear() + 1);
        break;
    }
    this.setState({start: start, end: end, activities: new ActivityArray()});
    this.search(start, end, this.state.tagFilter, 0, this.state.page);
  }
  
  next() {
    this.moveDates(1);
  }

  previous() {
    this.moveDates(-1);
  }

  today() {
    var [start, end] = this.initialDates(this.state.period);
    this.setState({start: start, end: end, activities: new ActivityArray()});
    this.search(start, end, this.state.tagFilter, 0, this.state.page);
  }
  
  toXMLString(date) {
    var string = '';
    var intVal;
    // year
    string = string + date.getFullYear() + '-';
    // month
    intVal = date.getMonth() + 1;
    if (intVal < 10) {
      string = string + '0' + intVal + '-';
    } else {
      string = string + intVal + '-';
    }
    // day
    intVal = date.getDate();
    if (intVal < 10) {
      string = string + '0' + intVal;
    } else {
      string = string + intVal;
    }
    // T
    string = string + 'T';
    // hours
    intVal = date.getHours();
    if (intVal < 10) {
      string = string + '0' + intVal + ':';
    } else {
      string = string + intVal + ':';
    }
    // minutes
    intVal = date.getMinutes();
    if (intVal < 10) {
      string = string + '0' + intVal + ':';
    } else {
      string = string + intVal + ':';
    }
    // seconds
    intVal = date.getSeconds();
    if (intVal < 10) {
      string = string + '0' + intVal;
    } else {
      string = string + intVal;
    }
    // Z
    string = string + 'Z';
    return string;
  }
  
  updatePeriod(period) {
    var [start, end] = this.initialDates(period);
    this.setState({start: start, end: end, period: period, activities: new ActivityArray()});
    this.search(start, end, 0, this.state.tagFilter, this.state.page);
  }
  
  updatePage(page) {
    this.setState({page: page, activities: new ActivityArray()});
    this.search(this.state.start, this.state.tagFilter, this.state.end, 0, page);
  }
  
  showPeriodOptions() {
    this.props.app.showSelect(SelectOperation.createSelect('',
      {
        week: 'runnerupweb.week',
        month: 'runnerupweb.month', 
        'three-months': 'runnerupweb.three-months', 
        'six-months': 'runnerupweb.six-months', 
        year: 'runnerupweb.year'
      }, 
      this.updatePeriod));
  }
  
  showPageOptions() {
    this.props.app.showSelect(SelectOperation.createSelect('', {5: '5', 10: '10', 20: '20', 50: '50', 100: '100'}, this.updatePage));
  }
  
  selectActivity(i) {
    var activity = this.state.activities.get(i);
    this.setState({selected: activity});
  }
  
  unselect() {
    this.setState({selected: null});
  }

  handleTagFilter(event) {
    this.props.app.checkInputDataList(event);
    if (this.state.tagFilter !== event.target.value) {
      if (event.target.value && event.target.checkValidity()) {
        this.setState({tagFilter: event.target.value, activities: new ActivityArray()});
        this.search(this.state.start, this.state.end, event.target.value, 0, this.state.page);
      } else if (event.target.value === '') {
        this.setState({tagFilter: null, activities: new ActivityArray()});
        this.search(this.state.start, this.state.end, null, 0, this.state.page);
      }
    }
  }

  clearTagFilter(event) {
    $('#filter-tag-input').val('');
    if (this.state.filter !== '') {
      this.setState({tagFilter: null, activities: new ActivityArray()});
      this.search(this.state.start, this.state.end, null, 0, this.state.page);
    }
  }
  
  renderMoreData() {
    if (this.state.moredata) {
      var style = {cursor:'pointer'};
      return(
        <div className="footer" onClick={this.moreData} style={style}>
          <FormattedMessage id="runnerupweb.MoreData" defaultMessage="More Data"/>
        </div>
      );
    } else {
      return(
        <div className="footer">&nbsp;</div>
      );
    }
  }
  
  renderSummarySport(sport, i) {
    var styleHeader = {borderColor: this.props.app.getOptions().getActivityLapColor(i), 
      backgroundColor: this.props.app.getOptions().getActivityLapColor(i)};
    var styleValue = {borderColor: this.props.app.getOptions().getActivityLapColor(i)};
    return(
      <div key={sport.sport} className="magnitude-lap-row">
        <div className="magnitude">
          <div className="magnitude-header magnitude-header-sport" style={styleHeader}>
            <FormattedMessage id="runnerupweb.Sport" defaultMessage="Sport"/>
          </div>
          <div className="magnitude-value" style={styleValue}>{sport.sport}</div>
        </div>
        <div className="magnitude">
          <div className="magnitude-header magnitude-header-laps" style={styleHeader}>
            <FormattedMessage id="runnerupweb.Activities" defaultMessage="Activities"/>
          </div>
          <div className="magnitude-value" style={styleValue}>{sport.activities}</div>
        </div>
        {sport.totalTimeSeconds && sport.totalTimeSeconds > 0?
          <div className="magnitude">
            <div className="magnitude-header magnitude-header-time" style={styleHeader}>
              <FormattedMessage id="runnerupweb.Time" defaultMessage="Time"/>
            </div>
            <div className="magnitude-value" style={styleValue}>{this.props.app.getOptions().getTime(sport.totalTimeSeconds)}</div>
          </div> : null
        }
        {sport.distanceMeters && sport.distanceMeters > 0?
          <div className="magnitude">
            <div className="magnitude-header magnitude-header-distance" style={styleHeader}>
              <FormattedMessage id="runnerupweb.Distance" defaultMessage="Distance"/>
            </div>
            <div className="magnitude-value" style={styleValue}>{this.props.app.getOptions().getDistanceUnit(sport.distanceMeters)}</div>
          </div> : null
        }
        {sport.averageSpeed && sport.averageSpeed > 0?
          <div className="magnitude">
            <div className="magnitude-header magnitude-header-speed" style={styleHeader}>
              <FormattedMessage id="runnerupweb.AvgSpeed" defaultMessage="Avg. Speed"/>
            </div>
            <div className="magnitude-value" style={styleValue}>{this.props.app.getOptions().getSpeedUnit(sport.averageSpeed)}</div>
          </div> : null
        }
        {sport.averageHeartRateBpm && sport.averageHeartRateBpm > 0?
          <div className="magnitude">
            <div className="magnitude-header magnitude-header-heart" style={styleHeader}>
              <FormattedMessage id="runnerupweb.AvgHeartRate" defaultMessage="Avg. Heart Rate"/>
            </div>
            <div className="magnitude-value" style={styleValue}>{this.props.app.getOptions().getHeartRateUnit(sport.averageHeartRateBpm)}</div>
          </div> : null
        }
        {sport.maximumHeartRateBpm && sport.maximumHeartRateBpm > 0?
          <div className="magnitude">
            <div className="magnitude-header magnitude-header-max-heart" style={styleHeader}>
              <FormattedMessage id="runnerupweb.MaxHeartRate" defaultMessage="Max. Heart Rate"/>
            </div>
            <div className="magnitude-value" style={styleValue}>{this.props.app.getOptions().getHeartRateUnit(sport.maximumHeartRateBpm)}</div>
          </div> : null
        }
      </div>
    );
  }
  
  renderSummary() {
    var i = 0;
    if (this.state.activities && this.state.activities.size() > 0) {
      return(
        Object.keys(this.state.activities.summary).map(sport =>
          this.renderSummarySport(this.state.activities.summary[sport], i++)
        )
      );
    }
  }
  
  renderList() {
    return (
      <React.Fragment>
        <div className="main">
          <div className="header">
            {this.props.app.renderPopupMenu(this.state)}
            <h1>{this.periodName()}</h1>
            <p className="left">
                <FormattedMessage id="runnerupweb.displaying.activities.period" 
                  defaultMessage="Displaying {number} activities for period " 
                  values={{ number: this.state.activities.size() }}/>
                <span className="link" onClick={this.showPeriodOptions}>
                <FormattedMessage id={'runnerupweb.' + this.state.period} 
                  defaultMessage={this.state.period} />
                </span>
                <FormattedMessage id="runnerupweb.and.page.size" 
                  defaultMessage=" and page size "/>
                <span id='page' className="link" onClick={this.showPageOptions}>{this.state.page}</span>
            </p>
            <p className="right">
                <span className="tag-short">
                  <input id="filter-tag-input" list="free-tags" maxLength="128" onChange={this.handleTagFilter}
                    placeholder={this.props.app.getIntl().formatMessage({id: 'runnerupweb.tag.filter'})}/>
                  <img onClick={this.clearTagFilter} src="resources/open-iconic/svg-white/x.svg"/>
                </span>
                <datalist id="free-tags">
                  {this.props.app.getAvailableTags().map(tag =>
                    <option key={tag.tag} value={tag.tag}/>
                  )}
                </datalist>
                <img src="resources/open-iconic/svg-white/media-skip-backward.svg" alt={this.props.app.getIntl().formatMessage({id: 'runnerupweb.Previous.period'})}
                     onClick={this.previous} title={this.props.app.getIntl().formatMessage({id: 'runnerupweb.Previous.period'})}/>
                <img src="resources/open-iconic/svg-white/media-record.svg" alt={this.props.app.getIntl().formatMessage({id: 'runnerupweb.Today'})}
                    onClick={this.today} title={this.props.app.getIntl().formatMessage({id: 'runnerupweb.Today'})}/>
                <img src="resources/open-iconic/svg-white/media-skip-forward.svg" alt={this.props.app.getIntl().formatMessage({id: 'runnerupweb.Next.period'})}
                     onClick={this.next} title={this.props.app.getIntl().formatMessage({id: 'runnerupweb.Next.period'})}/>
            </p>
          </div>
          {this.renderSummary()}
          <table>
            <thead>
                <tr>
                    <th><FormattedMessage id="runnerupweb.Sport" defaultMessage="Sport"/></th>
                    <th><FormattedMessage id="runnerupweb.Date" defaultMessage="Date"/></th>
                    <th><FormattedMessage id="runnerupweb.Time" defaultMessage="Time"/></th>
                    <th><FormattedMessage id="runnerupweb.Distance" defaultMessage="Distance"/></th>
                </tr>
            </thead>
            <tbody>
              {this.state.activities.getActivities().map((activity, i) =>
                <tr key={activity.id} onClick={() => this.selectActivity(i)}>
                  <td>{activity.sport}</td>
                  <td>{this.props.app.getOptions().getDateTime(activity.startTime)}</td>
                  <td>{this.props.app.getOptions().getTime(activity.totalTimeSeconds)}</td>
                  <td>{this.props.app.getOptions().getDistanceUnit(activity.distanceMeters)}</td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
        {this.renderMoreData()}
      </React.Fragment>
    );
  }
  
  render() {
    if (this.state.selected) {
      return (<Activity app={this.props.app} activityList={this} activity={this.state.selected}/>);
    } else {
      return this.renderList();
    }
  }
}
