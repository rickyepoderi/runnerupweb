import $ from "jquery";
import React from 'react';
import LoginForm from './LoginForm';
import ActivityList from './ActivityList';
import UserForm from './UserForm';
import UserList from './UserList';
import Options from './Options';
import OptionsForm from './OptionsForm';
import SelectOperation from './SelectOperation';
import {FormattedMessage, FormattedHTMLMessage, injectIntl} from 'react-intl';

export default class RunnerUpWeb extends React.Component {
  
  constructor(props) {
    super(props);
    var userInfo = sessionStorage.getItem("user-info");
    this.state = {
      page: 'login',
      info: userInfo? JSON.parse(userInfo) : null,
      options: null,
      level: 'info',
      user: null,
      opts: null,
      message: ''
    };
    this.showMessage = this.showMessage.bind(this);
    this.hideMessage = this.hideMessage.bind(this);
    this.clearTimeout = this.clearTimeout.bind(this);
    this.saveInfo = this.saveInfo.bind(this);
    this.saveUserOptions = this.saveUserOptions.bind(this);
    this.saveNewUserOptions = this.saveNewUserOptions.bind(this);
    this.logout = this.logout.bind(this);
    this.renderPage = this.renderPage.bind(this);
    this.showSelect = this.showSelect.bind(this);
    this.showUpload = this.showUpload.bind(this);
    this.hideSelect = this.hideSelect.bind(this);
    this.moveToLogin = this.moveToLogin.bind(this);
    this.moveToIndex = this.moveToIndex.bind(this);
    this.moveToUserEdit = this.moveToUserEdit.bind(this);
    this.moveToUserCreate = this.moveToUserCreate.bind(this);
    this.moveToUserList = this.moveToUserList.bind(this);
    this.moveToOptionsEdit = this.moveToOptionsEdit.bind(this);
    this.onErrorCommunication = this.onErrorCommunication.bind(this);
    this.requestUserOptions = this.requestUserOptions.bind(this);
    this.doUpload = this.doUpload.bind(this);
    this.renderPopupMenu = this.renderPopupMenu.bind(this);
    
    this.onUploadSuccess = this.onUploadSuccess.bind(this);
    if (userInfo) {
      this.requestUserOptions();
    }
  }
  
  clearTimeout() {
    if (this.state.timeout) {
      clearTimeout(this.state.timeout);
      this.setState({timeout: null});
    }
  }
  
  showMessage(level, message) {
    this.clearTimeout();
    var timeout = setTimeout(function () {$('#message-box').fadeOut('slow');}, 5000);
    this.setState({level: level, message: message, timeout: timeout});
    $('#message-box').fadeIn('fast');
  }
  
  hideMessage() {
      $('#message-box').fadeOut('fast');
      this.clearTimeout();
  }
  
  showSelect(selectOperation) {
    this.setState({selectOperation: selectOperation});
    $('#overlay').fadeIn('slow');
    $(document).bind('keydown', this.hideSelect.bind(this));
  }
  
  showUpload() {
    this.setState({selectOperation: SelectOperation.createUpload(this.doUpload)});
    $('#overlay').fadeIn('slow');
    $(document).bind('keydown', this.hideSelect.bind(this));
  }
  
  onUploadSuccess(data, textStatus, xhr) {
    this.hideSelect();
  }
  
  doUpload() {
    var input = $("#uploadInput");
    var fd = new FormData();    
    fd.append('userFiles', input.prop('files')[0]);
    $.ajax({
        url: 'rpc/json/workout/upload.php',
        type: 'post',
        processData: false,
        contentType: false,
        data: fd,
        success: this.onUploadSuccess,
        error: this.onErrorCommunication
    });
  }
  
  hideSelect(event) {
    if (event == null || event.type === 'click' || 
            (event.type == 'keydown' && event.keyCode === 27)) {
      $('#overlay').fadeOut('slow');
      $(document).unbind('keydown');
    }
  }
  
  setBackgroundImage(image) {
    // set the background image
    var html = $('html');
    html.css('background', 'url(resources/images/' + image + ') no-repeat center top fixed');
    html.css('-webkit-background-size', 'cover');
    html.css('-moz-background-size', 'cover');
    html.css('-o-background-size', 'cover');
    html.css('background-size', 'cover');
  }

  saveUserOptions(result) {
    if (result.status === 'SUCCESS') {
      var opts = new Options(result.response);
      if (opts.getBackgroundImage()) {
        this.setBackgroundImage(opts.getBackgroundImage())
      }
      this.setState({opts: opts, page: 'activity-list'});
    } else {
      this.showMessage('error', result.errorMessage);
    }
  }
  
  saveNewUserOptions(opts) {
    this.setState({opts: opts, page: 'activity-list'});
  }
  
  requestUserOptions() {
    $.ajax({
      url: 'rpc/json/user/get_options.php',
      type: 'get',
      contentType: 'application/json',
      success: this.saveUserOptions,
      error: this.onErrorCommunication
    });
  }
  
  saveInfo(info) {
    sessionStorage.setItem('user-info', JSON.stringify(info));
    if (this.state.opts) {
      this.setState({page: 'activity-list', info: info});
    } else {
      this.setState({info: info});
      this.requestUserOptions();
    }
  }
  
  moveToLogin() {
    sessionStorage.clear();
    this.setState({info: null, opts: null, page: 'login'});
  }
  
  moveToIndex() {
    if (this.state.page !== 'activity-list') {
      this.setState({page: 'activity-list'});
    }
  }
  
  moveToUserEdit(event, user) {
    if (user) {
      this.setState({page: 'user-edit', user: user});
    } else {
      this.setState({page: 'user-edit', user: this.state.info});
    }
  }
  
  moveToUserCreate() {
    this.setState({page: 'user-create', user: {role: 'USER'}});
  }
  
  moveToUserList() {
    this.setState({page: 'user-list'});
  }
  
  moveToOptionsEdit() {
    this.setState({page: 'options-edit'});
  }
  
  onErrorCommunication(result) {
    if (result.status === 403) {
      this.moveToLogin();
    } else {
      this.showMessage('error', result.status + ' ' + result.statusText);
    }
  }
  
  logout() {
    $.ajax({
        url: 'site/logout.php',
        type: 'get',
        contentType: 'application/json',
        success: this.moveToLogin,
        error: this.onErrorCommunication
    });
  }
  
  getOptions() {
    return this.state.opts;
  }
  
  renderMessage() {
    if (this.state.level && this.state.message) {
      var style, imgClose;
      switch (this.state.level) {
        case 'warning':
          style = {
            background: '#003366 url(\'resources/open-iconic/svg-yellow/warning.svg\') 15px 20px / 16px no-repeat',
            color: 'yellow'
          };
          imgClose = 'resources/open-iconic/svg-yellow/x.svg';
          break;
        case 'info':
          style = {
            background: '#003366 url(\'resources/open-iconic/svg-white/warning.svg\') 15px 20px / 16px no-repeat',
            color: 'white'
          };
          imgClose = 'resources/open-iconic/svg-white/x.svg';
          break;
        default:
          style = {
            background: '#003366 url(\'resources/open-iconic/svg-red/ban.svg\') 15px 20px / 16px no-repeat',
            color: 'red'
          };
          imgClose = 'resources/open-iconic/svg-red/x.svg';
      }
      return (
          <div id="message-box" className="message" style={style} onClick={this.hideMessage}>
            <img alt ="X" src={imgClose}/>
            <FormattedMessage id={this.state.message}/>
          </div>
      );
    } else {
      return (
        <div id="message-box" className="message"/>
      );
    }
  }
  
  getIntl() {
    return this.props.intl;
  }
  
  renderSelect() {
    if (this.state.selectOperation) {
      return(
        <div id="overlay" className="overlay">
          <img src="resources/open-iconic/svg-white/x.svg" onClick={this.hideSelect}/>
          {this.state.selectOperation.render(this)}
        </div>
      );
    } else {
      return(
        <div id="overlay" className="overlay" />
      );
    }
  }
  
  popupMethod(commonMethod, toPage, fromPage, pageMethod) {
    if (pageMethod && toPage === fromPage) {
      return pageMethod;
    }
    return commonMethod;
  }
  
  renderPopupMenu(fromPage, pageMethod) {
    return(
      <div className="left">
        <a title={this.getIntl().formatMessage({id: 'runnerupweb.Index'})} href="javascript:void(0)" onClick={this.popupMethod(this.moveToIndex, 'activity-list', fromPage, pageMethod)}>
          <img src="resources/open-iconic/svg-white/home.svg"/>
        </a>
        <a title={this.getIntl().formatMessage({id: 'runnerupweb.User.Information'})} href="javascript:void(0)" onClick={this.popupMethod(this.moveToUserEdit, 'user-edit', fromPage, pageMethod)}>
          <img src="resources/open-iconic/svg-white/person.svg"/>
        </a>
        {this.state.info && this.state.info.role && this.state.info.role === 'ADMIN' &&
          <a title={this.getIntl().formatMessage({id: 'runnerupweb.User.Management'})} href="javascript:void(0)" onClick={this.popupMethod(this.moveToUserList, 'user-list', fromPage, pageMethod)}>
            <img src="resources/open-iconic/svg-white/people.svg"/>
          </a>
        }
        <a title={this.getIntl().formatMessage({id: 'runnerupweb.User.Options'})} href="javascript:void(0)" onClick={this.popupMethod(this.moveToOptionsEdit, 'options-edit', fromPage, pageMethod)}>
          <img src="resources/open-iconic/svg-white/puzzle-piece.svg"/>
        </a>
        <a title={this.getIntl().formatMessage({id: 'runnerupweb.Upload'})} href="javascript:void(0)" onClick={this.popupMethod(this.showUpload, 'upload', fromPage, pageMethod)}>
          <img src="resources/open-iconic/svg-white/cloud-upload.svg"/>
        </a>
        <a title={this.getIntl().formatMessage({id: 'runnerupweb.Logout'})} href="javascript:void(0)" onClick={this.popupMethod(this.logout, 'logout', fromPage, pageMethod)}>
          <img src="resources/open-iconic/svg-white/account-logout.svg"/>
        </a>
      </div>
    );
  }
  
  renderPage() {
    switch (this.state.page) {
      case 'login':
        return (<LoginForm app={this}/>);
      case 'activity-list':
        return (<ActivityList app={this}/>);
      case 'user-edit':
        return (<UserForm app={this} info={this.state.info} user={this.state.user} mode={'edit'}/>);
      case 'user-list':
        return (<UserList app={this}/>);
      case 'user-create':
        return (<UserForm app={this} info={this.state.info} user={this.state.user} mode={'create'}/>);
      case 'options-edit':
        return (<OptionsForm app={this} info={this.state.info} opts={this.state.opts}/>);
      default:
        return {};
    }
  }
  
  render() {
      return (
        <React.Fragment>
          {this.renderMessage()}
          {this.renderPage()}
          {this.renderSelect()}
        </React.Fragment>
      );
  }
}

RunnerUpWeb = injectIntl(RunnerUpWeb);