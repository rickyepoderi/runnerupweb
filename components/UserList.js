import $ from "jquery";
import React from 'react';
import {FormattedMessage, FormattedHTMLMessage} from 'react-intl';

export default class UserList extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      users: new Array(),
      page: 10,
      moredata: true
    };
    this.search = this.search.bind(this);
    this.onSearchSuccess = this.onSearchSuccess.bind(this);
    this.moreData = this.moreData.bind(this);
    this.renderMoreData = this.renderMoreData.bind(this);
    this.search(null, null, null, null, 0, this.state.page);
  }
 
  onSearchSuccess(result) {
    if (result.status === 'SUCCESS') {
      var users = this.state.users;
      users = users.concat(result.response);
      var moredata = result.response.length === parseInt(this.state.page)
      this.setState({users: users, moredata: moredata});
    } else {
      this.props.app.showMessage('error', result.errorMessage);
    }
  }
  
  search(op, login, firstname, lastname, offset, limit) {
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
        url: 'rpc/json/user/search.php',
        type: 'get',
        contentType: 'application/json',
        success: this.onSearchSuccess,
        error: this.props.app.onErrorCommunication
    });
  }
  
  moreData() {
    this.search(null, null, null, null, this.state.users.length, this.state.page);
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
  
  render() {
    return(
     <React.Fragment>
       <div className="main">
         <div className="header">
           {this.props.app.renderPopupMenu()}
           <h1><FormattedMessage id="runnerupweb.User.Management" defaultMessage="User Management"/></h1>
           <p className="left">
             <FormattedMessage id="runnerupweb.displaying.users" values={{number: this.state.users.length}}/>
             <a href="javascript:void(0)" onClick={this.props.app.moveToUserCreate}><FormattedMessage id="runnerupweb.create.new.user"/></a>
           </p>
         </div>
         <table id="user-table">
           <thead>
             <tr>
               <th><FormattedMessage id="runnerupweb.Login" defaultMessage="Login"/></th>
               <th><FormattedMessage id="runnerupweb.Firstname" defaultMessage="Firstname"/></th>
               <th><FormattedMessage id="runnerupweb.Lastname" defaultMessage="Lastname"/></th>
               <th><FormattedMessage id="runnerupweb.Email" defaultMessage="E-mail"/></th>
             </tr>
           </thead>
           <tbody>
             {this.state.users.map(user =>
                <tr key={user.login} onClick={(event) => this.props.app.moveToUserEdit(event, user)}>
                  <td>{user.login}</td>
                  <td>{user.firstname}</td>
                  <td>{user.lastname}</td>
                  <td>{user.email}</td>
                </tr>
              )}
           </tbody>
         </table>
       </div>
       {this.renderMoreData()}
     </React.Fragment>
    );
  }
}