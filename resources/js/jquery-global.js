import $ from 'jquery';

// The jQuery plugins below (bootstrap 3, icheck, slimscroll, toggle) read the global at
// import time, so this module has to be imported before any of them.
window.$ = window.jQuery = $;

export default $;
