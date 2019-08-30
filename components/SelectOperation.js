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

import React from 'react';
import {FormattedMessage, FormattedHTMLMessage} from 'react-intl';

export default class SelectOperation {

  static OPERATION_SELECT = 'SELECT';
  static OPERATION_UPLOAD = 'UPLOAD';

  operation = '';
  options = {};
  message = '';
  
  constructor(operation, message, options, method) {
    this.operation = operation;
    this.options = options;
    this.message = message;
    this.method = method;
  }
  
  render(app) {
    if (this.operation === SelectOperation.OPERATION_SELECT) {
      return (
        <React.Fragment>
          {this.message}<br/>
          {Object.keys(this.options).map(option =>
            <React.Fragment key={option}>
              <span className="link" onClick={() => {this.method(option); app.hideSelect()}}>
                {app.getIntl().formatMessage({id: this.options[option], defaultMessage: this.options[option]})}
              </span>
              <br/>
            </React.Fragment>
          )}
        </React.Fragment>
      )
    } else if (this.operation === SelectOperation.OPERATION_UPLOAD) {
      return(
        <React.Fragment>
          <input type="file" id="uploadInput" accept=".tcx"/>
          &nbsp;
          <span className="link" onClick={this.method}>
            <FormattedMessage id="runnerupweb.Submit" defaultMessage="Submit"/>
          </span>
        </React.Fragment>
      );
    }
  }
  
  static createSelect(message, options, method) {
    return new SelectOperation(SelectOperation.OPERATION_SELECT, message, options, method);
  }
  
  static createUpload(method) {
    return new SelectOperation(SelectOperation.OPERATION_UPLOAD, null, null, method);
  }
}