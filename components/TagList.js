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

export default class TagList extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      filter: '',
      selected: null
    };
    this.filter = this.filter.bind(this);
    this.handleFilter = this.handleFilter.bind(this);
    this.startsWithFilter = this.startsWithFilter.bind(this);
    this.doDelete = this.doDelete.bind(this);
    this.confirmDelete = this.confirmDelete.bind(this);
    this.clearFilter = this.clearFilter.bind(this);
    this.onGetTagSuccess = this.onGetTagSuccess.bind(this);
    this.getTagConfig = this.getTagConfig.bind(this);
  }

  doDelete(answer) {
    if (answer === 'yes') {
      $.ajax({
        url: 'rpc/json/workout/delete_tag_config.php?tag=' + encodeURIComponent(this.state.selected),
        type: 'post',
        dataType: 'json',
        contentType: 'application/json',
        success: this.props.app.requestAvailableTags,
        error: this.props.app.onErrorCommunication
      });
    }
  }

  confirmDelete(tag) {
    this.setState({selected: tag});
    this.props.app.showSelect(SelectOperation.createSelect(
      this.props.app.getIntl().formatMessage({id: 'runnerupweb.are.you.sure.you.want.to.delete.tagconfig'}, {tag: tag}),
      {
        yes: this.props.app.getIntl().formatMessage({id: 'runnerupweb.Yes'}),
        no: this.props.app.getIntl().formatMessage({id: 'runnerupweb.No'})
      },
      this.doDelete));
  }

  startsWithFilter(tag) {
    return tag.tag.toLowerCase().indexOf(this.state.filter.toLocaleLowerCase()) !== -1;
  }

  filter() {
    if (this.state.filter && this.state.filter.length > 0) {
      return this.props.app.getAvailableTags().filter(this.startsWithFilter);
    } else {
      return this.props.app.getAvailableTags();
    }
  }

  handleFilter(event) {
    this.setState({filter: event.target.value});
  }

  clearFilter(event) {
    this.setState({filter: ''});
  }
  
  onGetTagSuccess(data) {
    if (data.status === 'SUCCESS') {
      this.props.app.moveToTagConfigEdit(data.response);
    } else {
      this.props.app.showMessage('error', data.errorMessage);
    }
  }

  getTagConfig(tag) {
    $.ajax({
      url: 'rpc/json/workout/get_tag_config.php?tag=' + encodeURIComponent(tag),
      type: 'get',
      dataType: 'json',
      contentType: 'application/json',
      success: this.onGetTagSuccess,
      error: this.props.app.onErrorCommunication
    });
  }

  render() {
    return(
     <React.Fragment>
       <div className="main">
         <div className="header">
           {this.props.app.renderPopupMenu()}
           <h1><FormattedMessage id="runnerupweb.Tag.Management" defaultMessage="Tag Management"/></h1>
           <p className="left">
             <FormattedMessage id="runnerupweb.displaying.tags" values={{number: this.filter().length}}/>
             <span className="link" onClick={() => this.props.app.moveToTagConfigCreate()}><FormattedMessage id="runnerupweb.create.new.tag"/></span>
           </p>
           <p className="right">
             <span className="tag-short">
               <input type="text" maxLength="128" placeholder={this.props.app.getIntl().formatMessage({id: 'runnerupweb.tag.filter'})} value={this.state.filter}  onChange={this.handleFilter}></input>
               <img onClick={this.clearFilter} src="resources/open-iconic/svg-white/x.svg"/>
             </span>
           </p>
         </div>
         <div>
           {this.filter().map(tag =>
           <span key={tag.tag} className={tag.auto? 'tag-auto':'tag'}>
             <span className="maginute-pointer" onClick={() => this.getTagConfig(tag.tag)}>{tag.tag}</span>
             <img onClick={() => this.confirmDelete(tag.tag)} src="resources/open-iconic/svg-white/x.svg"/>
           </span>
           )}
         </div>
       </div>
     </React.Fragment>
    );
  }
}