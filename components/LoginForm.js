import $ from "jquery";
import React from 'react';

export default class LoginForm extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      username: '',
      password: ''
    };
    this.handleUsername = this.handleUsername.bind(this);
    this.handlePassword = this.handlePassword.bind(this);
    this.doLogin = this.doLogin.bind(this);
    this.keyPress = this.keyPress.bind(this);
    this.onError = this.onError.bind(this);
    this.onLoginSuccess = this.onLoginSuccess.bind(this);
    this.onGetUserSuccess = this.onGetUserSuccess.bind(this);
  }
  
  handleUsername(event) {
    this.setState({username: event.target.value});
  }
  
  handlePassword(event) {
    this.setState({password: event.target.value});
  }
  
  onGetUserSuccess(result) {
    if (result.status === 'SUCCESS') {
        this.props.app.saveInfo(result.response);
    } else {
        this.props.app.showMessage('error', result.errorMessage);
    }
  }
  
  onLoginSuccess(result) {
    if (result.status === 'SUCCESS') {
      $.ajax({
        url: 'rpc/json/user/get_user.php?login=' + this.state.username,
        type: 'get',
        contentType: 'application/json',
        success: this.onGetUserSuccess,
        error: this.onError
      });
    } else {
      this.props.app.showMessage('error', result.errorMessage);
    }
  }
  
  onError(result) {
    this.props.app.showMessage('error', result.status + ' ' + result.statusText);
  }
  
  doLogin(event) {
    if (!this.state.username) {
      this.props.app.showMessage('error', 'runnerupweb.error.no.username');
      $('#username').focus();
      return;
    }
    if (!this.state.password) {
      this.props.app.showMessage('error', 'runnerupweb.error.no.password');
      $('#password').focus();
      return;
    }
    var data = {login: this.state.username, password: this.state.password};
    $.ajax({
        url: 'site/authenticate.php?type=json',
        type: 'post',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: this.onLoginSuccess,
        error: this.onError
    });
  }
  
  keyPress(event) {
    if (event.which === 13) {
      this.doLogin();
    }
  }

  render() {
    return (
      <div className="login">
	<img src='resources/images/runnerupweb-white.png'/>
        <input id="username" required autoFocus type="text" id="username" name="username" value={this.state.username} 
            placeholder={this.props.app.getIntl().formatMessage({id: 'runnerupweb.username'})} onChange={this.handleUsername} onKeyPress={this.keyPress}/>
        <input id="password" required type="password" id="password" name="password" value={this.state.password} 
            placeholder={this.props.app.getIntl().formatMessage({id: 'runnerupweb.password'})} onChange={this.handlePassword} onKeyPress={this.keyPress} />
        <p className="submit"><input type="button" name="login" value={this.props.app.getIntl().formatMessage({id: 'runnerupweb.Login'})} onClick={this.doLogin}/></p>
      </div>
    );
  }
}