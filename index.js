import $ from "jquery";
import React from 'react';
import ReactDOM from 'react-dom';
import RunnerUpWeb from './components/RunnerUpWeb';
import {IntlProvider, addLocaleData} from "react-intl";
import locale_en from 'react-intl/locale-data/en';
import locale_es from 'react-intl/locale-data/es';
import messages_en from "./translations/en.json";
import messages_es from "./translations/es.json";

function hidePreload() {
  $('#preload-loading').fadeOut('slow', function() { $(this).remove(); });;
}

function start(background) {
  $('html').css('background', 'url(resources/images/' + background + ') no-repeat center top fixed');
  $('html').css('-webkit-background-size', 'cover');
  $('html').css('-moz-background-size', 'cover');
  $('html').css('-o-background-size', 'cover');
  $('html').css('background-size', 'cover');
  ReactDOM.render(
    <IntlProvider locale={language} messages={messages[language]}>
      <RunnerUpWeb />
    </IntlProvider>, 
    document.getElementById('root')
  );
  setTimeout(hidePreload, 300);
}

function preloadBackgroundImage() {
  var background = backgrounds[Math.floor(Math.random() * backgrounds.length)];
  $.ajax({ 
   url: 'resources/images/' + background,
   cache: true,
   ifModified: true,
   timeout: 5000,
   success: function() {
     start(background)
   }
  });
}

addLocaleData([ 
  ...locale_es,
  ...locale_en
]);

// default only en languaje but react-intl is used
const messages = {
    'es': messages_es,
    'en': messages_en,
};
var language = navigator.language.split(/[-_]/)[0];
if (!messages[language]) {
  language = 'en';
}

var backgrounds = [
    'pedestrian-653729_1280.jpg',
    'run-750466_1280.jpg',
    'runner-557580_1280.jpg',
    'runner-761262_1280.jpg',
    'runners-635906_1280.jpg',
    'runners-752493_1280.jpg',
    'runners-760431_1280.jpg',
    'running-573762_1280.jpg'
];
if ((document.readyState === 'complete' || document.readyState === 'loaded' || document.readyState === 'interactive') && document.body) {
    preloadBackgroundImage();
} else {
    window.addEventListener('DOMContentLoaded', preloadBackgroundImage, false);
}