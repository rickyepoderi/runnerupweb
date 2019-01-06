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
              <a href="javascript:void(0)" onClick={() => {this.method(option); app.hideSelect()}}>
                {app.getIntl().formatMessage({id: this.options[option], defaultMessage: this.options[option]})}
              </a>
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
          <a href="javascript:void(0)" onClick={this.method}>
            <FormattedMessage id="runnerupweb.Submit" defaultMessage="Submit"/>
          </a>
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