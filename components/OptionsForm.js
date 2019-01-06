import $ from "jquery";
import React from 'react';
import Options from './Options';
import HtmlToReact from 'html-to-react';
import SelectOperation from './SelectOperation';
import {FormattedMessage, FormattedHTMLMessage} from 'react-intl';

export default class OptionsForm extends React.Component {
  constructor(props) {
    super(props);
    var opts = this.props.opts.clone();
    var empty = {};
    this.state = {
      opts: opts,
      defs: new Options(empty)
    };
    this.onLoadDefnitionsSuccess = this.onLoadDefnitionsSuccess.bind(this);
    this.loadDefinitions = this.loadDefinitions.bind(this);
    this.updateOption = this.updateOption.bind(this);
    this.deleteOption = this.deleteOption.bind(this);
    this.addOption = this.addOption.bind(this);
    this.showNewOptions = this.showNewOptions.bind(this);
    this.doSave = this.doSave.bind(this);
    this.onSaveSuccess = this.onSaveSuccess.bind(this);
    this.loadDefinitions();
  }
  
  onLoadDefnitionsSuccess(result) {
    if (result.status === 'SUCCESS') {
      this.setState({defs: new Options(result.response)});
    } else {
        this.props.app.showMessage('error', result.errorMessage);
    }
  }
  
  loadDefinitions() {
    $.ajax({
        url: 'rpc/json/user/get_option_definitions.php',
        type: 'get',
        contentType: 'application/json',
        success: this.onLoadDefnitionsSuccess,
        error: this.props.app.onErrorCommunication
    });
  }
  
  deleteOption(opt) {
    var opts = this.state.opts;
    opts.delete(opt);
    this.setState({opts: opts});
  }
  
  updateOption(event) {
    var id = event.target.id;
    var value = event.target.value;
    var opts = this.state.opts;
    opts.set(id, value);
    this.setState({opts: opts});
  }
  
  addOption(opt) {
    var opts = this.state.opts;
    var def = this.state.defs.get(opt);
    var parser = new DOMParser();
    var xmlDoc = parser.parseFromString(def, 'text/xml');
    if (xmlDoc.firstChild.nodeName === 'select') {
      opts.set(opt, xmlDoc.getElementsByTagName("option")[0].textContent);
    } else {
      opts.set(opt, '');
    }
    this.setState({opts: opts});
  }
  
  showNewOptions() {
    var defs = this.state.defs.flat();
    var opts = this.state.opts;
    var values = {};
    Object.keys(defs).filter(i => opts.get(i) === null).forEach(v => values[v] = v);
    this.props.app.showSelect(SelectOperation.createSelect('', values, this.addOption));
  }
  
  onSaveSuccess(result) {
    if (result.status === 'SUCCESS') {
      this.props.app.requestUserOptions();
    } else {
      this.props.app.showMessage('error', result.errorMessage);
    }
  }
  
  checkInputs() {
    var inputs = $('#form').find(':input');
    for (var i = 0; i < inputs.length; i++) {
      var input = inputs[i];
      if ($(input).is(":invalid")) {
        this.props.app.showMessage('error', 'The entry ' + $(input).attr('id') + ' is invalid.');
        $(input).focus();
        return false;
      }
    }
    return true;
  }
  
  doSave() {
    if (this.checkInputs()) {
      var opts = this.state.opts.flat();
      Object.keys(opts).forEach(key => opts[key].length == 0 && delete opts[key]);
      $.ajax({
          url: 'rpc/json/user/set_options.php',
          type: 'post',
          dataType: 'json',
          contentType: 'application/json',
          data: JSON.stringify(opts),
          success: this.onSaveSuccess,
          error: this.props.app.onErrorCommunication
      });
    }
  }
  
  renderElement (html, id, value) {
    var htmlToReactParser = new HtmlToReact.Parser();
    var processNodeDefinitions = new HtmlToReact.ProcessNodeDefinitions(React);
    var updateOption = this.updateOption;
    var isValidNode = function () {
      return true;
    };
    var processingInstructions = [
      {
        shouldProcessNode: function (node) {
          return node.name === 'input' || node.name === 'select';
        },
        processNode: function (node, children, index) {
          node.attribs.id = id;
          node.attribs.value = value;
          node.attribs.onChange = updateOption;
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
    var reactElement = htmlToReactParser.parseWithInstructions(html, isValidNode, processingInstructions);
    return reactElement;
  }
  
  renderOption(opt, flatOpts, flatDefs) {
    var html = flatDefs[opt];
    if (html) {
      return(
        <div className="row" key={opt}>
          <div className="left">
            <label className="large"><FormattedMessage id={opt}/></label>
            <img src="resources/open-iconic/svg-white/delete.svg" onClick={() => this.deleteOption(opt)}/>
          </div>
          <div className="left" onChange={this.updateOption}>
            {this.renderElement(html, opt, flatOpts[opt])}
          </div>
        </div>
      );
    }
  }
  
  render() {
    var flatOpts = this.state.opts.flat();
    var flatDefs = this.state.defs.flat();
    return (
      <div className="main">
        <div className="header">
          {this.props.app.renderPopupMenu()}
          <h1>
            <FormattedMessage id="runnerupweb.options.for" values={{user: this.props.info.login}}/>
          </h1>
        </div>
        <div className="form form-short">
          <form id="form">
            {Object.keys(flatOpts).map(opt =>
              this.renderOption(opt, flatOpts, flatDefs)
            )}
          </form>
          <p className="submit">
            <input type="button" value={this.props.app.getIntl().formatMessage({id: 'runnerupweb.New'})} onClick={this.showNewOptions}/>
            <input type="button" value={this.props.app.getIntl().formatMessage({id: 'runnerupweb.Save'})} onClick={this.doSave}/>
          </p>
        </div>
      </div>
    );
  }
}