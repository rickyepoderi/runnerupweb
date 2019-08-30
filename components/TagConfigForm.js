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
import HtmlToReact from 'html-to-react';
import SelectOperation from './SelectOperation';
import {FormattedMessage, FormattedHTMLMessage} from 'react-intl';

export default class TagConfigForm extends React.Component {

  constructor(props) {
    super(props);
    var config = {};
    if (this.props.tagConfig.extra) {
      this.props.tagConfig.extra.map(extra => config[extra.name] = extra.value);
    }
    this.state = {
      tagConfig: this.props.tagConfig,
      config: config
    };
    this.renderElement = this.renderElement.bind(this);
    this.handleTag = this.handleTag.bind(this);
    this.handleDescription = this.handleDescription.bind(this);
    this.updateConfig = this.updateConfig.bind(this);
    this.doSave = this.doSave.bind(this);
    this.onUpdateSuccess = this.onUpdateSuccess.bind(this);
    this.doDelete = this.doDelete.bind(this);
    this.confirmDelete = this.confirmDelete.bind(this);
  }

  handleTag(event) {
    var tagConfig = this.state.tagConfig;
    tagConfig.tag = event.target.value;
    this.setState({tagConfig: tagConfig});
  }

  handleDescription(event) {
    var tagConfig = this.state.tagConfig;
    tagConfig.description = event.target.value;
    this.setState({tagConfig: tagConfig});
  }

  updateConfig(event) {
    var config = this.state.config;
    config[event.target.name] = event.target.value
    this.setState({config: config});
  }

  onUpdateSuccess(result) {
    if (result.status === 'SUCCESS') {
      this.props.app.requestAvailableTags();
      this.props.app.moveToTagList();
    } else {
      this.props.app.showMessage('error', result.errorMessage);
    }
  }

  doSave() {
    if (this.props.app.checkInputs('#form')) {
      var tagConfig = Object.assign({}, this.state.tagConfig);
      if (this.state.config) {
        tagConfig.extra = this.state.config;
      }
      $.ajax({
        url: 'rpc/json/workout/set_tag_config.php?mode=' + this.props.mode,
        type: 'post',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify(tagConfig),
        success: this.onUpdateSuccess,
        error: this.props.app.onErrorCommunication
      });
    }
  }

  renderElement(extra) {
    var htmlToReactParser = new HtmlToReact.Parser();
    var processNodeDefinitions = new HtmlToReact.ProcessNodeDefinitions(React);
    var updateConfig = this.updateConfig;
    var config = this.state.config;
    var isValidNode = function () {
      return true;
    };
    var processingInstructions = [
      {
        shouldProcessNode: function (node) {
          return node.name === 'input' || node.name === 'select' || node.name === 'textarea';
        },
        processNode: function (node, children, index) {
          node.attribs.name = extra.name;
          node.attribs.value = config[node.attribs.name];
          node.attribs.onChange = updateConfig;
          return processNodeDefinitions.processDefaultNode(node, children, index);
        }
      },
      {
        shouldProcessNode: function (node) {
          return true;
        },
        processNode: processNodeDefinitions.processDefaultNode
      }
    ];
    var reactElement = htmlToReactParser.parseWithInstructions(extra.html, isValidNode, processingInstructions);
    return reactElement;
  }

  doDelete(answer) {
    if (answer === 'yes') {
      $.ajax({
        url: 'rpc/json/workout/delete_tag_config.php?tag=' + encodeURIComponent(this.state.tagConfig.tag),
        type: 'post',
        dataType: 'json',
        contentType: 'application/json',
        success: this.onUpdateSuccess,
        error: this.props.app.onErrorCommunication
      });
    }
  }
  
  confirmDelete() {
    this.props.app.showSelect(SelectOperation.createSelect(
      this.props.app.getIntl().formatMessage({id: 'runnerupweb.are.you.sure.you.want.to.delete.tagconfig'}, {tag: this.state.tagConfig.tag}),
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
            <h1><FormattedMessage id="runnerupweb.Tag"/> {this.props.tagConfig.tag}</h1>
        </div>
        <div className="form">
          <form id="form">
            <div>
              <label htmlFor="tag">
                <FormattedMessage id="runnerupweb.Tag" defaultMessage="Tag"/>:
              </label>
              <input id="tag" type="text" maxLength="128" value={this.state.tagConfig.tag} onChange={this.handleTag} disabled={this.props.mode !== 'create'} required autoFocus={this.props.mode === 'create'}/>
            </div>
            <div>
              <label htmlFor="description">
                <FormattedMessage id="runnerupweb.Description" defaultMessage="Description"/>:
              </label>
              <textarea id="description" autoFocus={this.props.mode !== 'create'} value={this.state.tagConfig.description} onChange={this.handleDescription} />
            </div>
            {this.state.tagConfig.extra && this.state.tagConfig.extra.map(extra =>
              <div key={extra.name}>
                <label htmlFor={extra.name}>
                  <FormattedMessage id={extra.name}/>:
                </label>
                {this.renderElement(extra)}
              </div>
            )}
            <p className="submit">
              {this.props.mode === 'edit' &&
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