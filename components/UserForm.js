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
import SelectOperation from './SelectOperation';
import {FormattedMessage, FormattedHTMLMessage} from 'react-intl';

export default class UserForm extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      user: this.fillUser(this.props.user)
    };
    this.handleAttribute = this.handleAttribute.bind(this);
    this.handleLogin = this.handleLogin.bind(this);
    this.handlePassword = this.handlePassword.bind(this);
    this.handleConfirmPassword = this.handleConfirmPassword.bind(this);
    this.handleFirstname = this.handleFirstname.bind(this);
    this.handleLastname = this.handleLastname.bind(this);
    this.handleEmail = this.handleEmail.bind(this);
    this.handleRole = this.handleRole.bind(this);
    this.doSave = this.doSave.bind(this);
    this.onUpdateSuccess = this.onUpdateSuccess.bind(this);
    this.onDeleteSuccess = this.onDeleteSuccess.bind(this);
    this.confirmDelete = this.confirmDelete.bind(this);
    this.doDelete = this.doDelete.bind(this);
    this.fillUser = this.fillUser.bind(this);
    this.convertUser = this.convertUser.bind(this);
  }
  
  componentDidUpdate(prevProps, prevState, snapshot) {
    if ((prevProps.user.login && !this.props.user.login) ||
            (!prevProps.user.login && this.props.user.login) ||
            (prevProps.user.login !== this.props.user.login)) {
              this.setState({user: this.fillUser(this.props.user)});
    }
  }
  
  fillUser(user) {
    var clone = {};
    var attrs = ['login', 'password', 'confirmPassword', 'firstname', 'lastname', 'email', 'role'];
    for (var i in attrs) {
      if (user[attrs[i]]) {
        clone[attrs[i]] = user[attrs[i]];
      } else {
        clone[attrs[i]] = '';
      }
    }
    return clone;
  }
  
  convertUser(user) {
    var clone =  {};
    var attrs = ['login', 'password', 'confirmPassword', 'firstname', 'lastname', 'email', 'role'];
    for (var i in attrs) {
      if (user[attrs[i]] && user[attrs[i]] !== '') {
        clone[attrs[i]] = user[attrs[i]];
      }
    }
    return clone;
  }
  
  handleAttribute(attr, event) {
    var user = this.state.user;
    user[attr] = event.target.value;
    this.setState({user: user});
  }
  
  handleLogin(event) {
    this.handleAttribute('login', event);
  }
  
  handlePassword(event) {
    this.handleAttribute('password', event);
  }
  
  handleConfirmPassword(event) {
    this.handleAttribute('confirmPassword', event);
  }
  
  handleFirstname(event) {
    this.handleAttribute('firstname', event);
  }
  
  handleLastname(event) {
    this.handleAttribute('lastname', event);
  }
  
  handleEmail(event) {
    this.handleAttribute('email', event);
  }
  
  handleRole(event) {
    this.handleAttribute('role', event);
  }
  
  onUpdateSuccess(result) {
    if (result.status === 'SUCCESS') {
      var user = this.state.user;
      delete user.password;
      delete user.confirmPassword;
      if (this.props.info.login === user.login) {
        this.props.app.saveInfo(user);
      } else {
        this.props.app.moveToUserList();
      }
    } else {
      this.props.app.showMessage('error', result.errorMessage);
    }
  }
  
  onDeleteSuccess(result) {
    if (result.status === 'SUCCESS') {
      this.props.app.moveToUserList();
    } else {
      this.props.app.showMessage('error', result.errorMessage);
    }
  }

  doSave() {
    if (this.props.app.checkInputs('#form')) {
      var user = this.convertUser(this.state.user);
      var orig = this.convertUser(this.fillUser(this.props.user));
      if ((user.password || user.confirmPassword) &&
              user.password !== user.confirmPassword) {
        this.props.app.showMessage('error', 'Passwords are different');
        $('#password').focus();
      } else if (!user.password && !user.confirmPassword &&
              orig.firstname === user.firstname &&
              orig.lastname === user.lastname &
              orig.email === user.email &&
              orig.role === user.role) {
        this.props.app.showMessage('warning', 'User information has not been modified');
      } else {
        delete user.confirmPassword;
        $.ajax({
          url: 'rpc/json/user/set_user.php',
          type: 'post',
          dataType: 'json',
          contentType: 'application/json',
          data: JSON.stringify(user),
          success: this.onUpdateSuccess,
          error: this.props.app.onErrorCommunication
        });
      }
    }
  }
  
  doDelete(answer) {
    if (answer === 'yes') {
      $.ajax({
        url: 'rpc/json/user/delete_user.php?login=' + this.state.user.login,
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
      this.props.app.getIntl().formatMessage({id: 'runnerupweb.are.you.sure.you.want.to.delete.user'}, {user: this.state.user.login}),
      {
        yes: this.props.app.getIntl().formatMessage({id: 'runnerupweb.Yes'}),
        no: this.props.app.getIntl().formatMessage({id: 'runnerupweb.No'})
      }, 
      this.doDelete));
  }
 
  render() {
    return(
      <div className="main">
        <div className="header">
            {this.props.app.renderPopupMenu()}
            <h1><FormattedMessage id="runnerupweb.USER"/> {this.state.user.login}</h1>
        </div>
        <div className="form">
          <form id="form">
            <div>
              <label htmlFor="login">
                <FormattedMessage id="runnerupweb.Login" defaultMessage="Login"/>:
              </label>
              <input id="login" type="text" maxLength="64" value={this.state.user.login} onChange={this.handleLogin} disabled={this.props.mode !== 'create'} required autoFocus={this.props.mode === 'create'}/>
            </div>
            <div>
              <label htmlFor="password">
                <FormattedMessage id="runnerupweb.Password" defaultMessage="Password"/>:
              </label>
              <input id="password" type="password" onChange={this.handlePassword} required={this.props.mode === 'create'} autoFocus={this.props.mode !== 'create'}/>
            </div>
            <div>
              <label htmlFor="confirmPassword">
                <FormattedMessage id="runnerupweb.Confirm.password" defaultMessage="Confirm password"/>:
              </label>
              <input id="confirmPassword" type="password" onChange={this.handleConfirmPassword} required={this.props.mode === 'create'}/>
            </div>
            <div>
              <label htmlFor="firstname">
                <FormattedMessage id="runnerupweb.Firstname" defaultMessage="Firstname"/>:
              </label>
              <input id="firstname" type="text" maxLength="100"  value={this.state.user.firstname} onChange={this.handleFirstname}/>
            </div>
            <div>
              <label htmlFor="lastname">
                <FormattedMessage id="runnerupweb.Lastname" defaultMessage="Lastname"/>:
              </label>
              <input id="lastname" type="text" maxLength="100"  value={this.state.user.lastname} onChange={this.handleLastname}/>
            </div>
            <div>
              <label htmlFor="email">
                <FormattedMessage id="runnerupweb.Email" defaultMessage="E-mail"/>:
              </label>
              <input id="email" type="email" maxLength="100"  value={this.state.user.email}  onChange={this.handleEmail}/>
            </div>
            <div>
              <label htmlFor="email">
                <FormattedMessage id="runnerupweb.Role" defaultMessage="E-mail"/>:
              </label>
              <select id="role" value={this.state.user.role} disabled={this.props.info.role !== 'ADMIN' }  onChange={this.handleRole}>
                <option value="USER">{this.props.app.getIntl().formatMessage({id: 'runnerupweb.USER'})}</option>
                <option value="ADMIN">{this.props.app.getIntl().formatMessage({id: 'runnerupweb.ADMIN'})}</option>
              </select>
            </div>
            <p className="submit">
              {this.props.info.role == 'ADMIN' && this.props.info.login !== this.state.user.login && this.props.mode === 'edit' &&
                      <input type="button" value={this.props.app.getIntl().formatMessage({id: 'runnerupweb.Delete'})} onClick={this.confirmDelete}/>
              }
              <input type="button" value={this.props.app.getIntl().formatMessage({id: 'runnerupweb.Save'})} onClick={this.doSave}/>
            </p>
          </form>
        </div>
      </div>
    );
  }
 
}